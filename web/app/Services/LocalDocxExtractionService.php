<?php

namespace App\Services;

use App\Exceptions\RagServiceException;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LocalDocxExtractionService
{
    private const CHUNK_SIZE = 1_200;

    private const CHUNK_OVERLAP = 200;

    private const MAX_XML_SIZE = 20_000_000;

    /** @return array{page_count: int, character_count: int, chunks: list<array{chunk_index: int, page_number: int, content: string, token_count: int}>} */
    public function extract(string $filePath): array
    {
        try {
            $contents = Storage::disk('local')->get($filePath);
        } catch (Throwable $exception) {
            throw new RagServiceException('The stored Word document could not be read. Upload the file again and retry.', 0, $exception);
        }

        if (! is_string($contents) || ! str_starts_with($contents, 'PK')) {
            throw new RagServiceException('The uploaded file is not a valid DOCX document.');
        }

        $xml = $this->readArchiveEntry($contents, 'word/document.xml');
        $paragraphs = $this->paragraphs($xml);
        $text = implode("\n\n", $paragraphs);
        $sections = $this->chunk($text);

        if ($sections === []) {
            throw new RagServiceException('No readable text was found in the Word document.');
        }

        $chunks = array_map(fn (string $content, int $index): array => [
            'chunk_index' => $index,
            'page_number' => $index + 1,
            'content' => $content,
            'token_count' => count(preg_split('/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: []),
        ], $sections, array_keys($sections));

        return [
            'page_count' => count($chunks),
            'character_count' => array_sum(array_map(fn (array $chunk): int => mb_strlen($chunk['content']), $chunks)),
            'chunks' => $chunks,
        ];
    }

    private function readArchiveEntry(string $archive, string $target): string
    {
        $eocdOffset = strrpos(substr($archive, -65_557), "PK\x05\x06");
        if ($eocdOffset === false) {
            throw new RagServiceException('The DOCX archive is damaged or incomplete.');
        }

        $eocdOffset += max(0, strlen($archive) - 65_557);
        if ($eocdOffset + 22 > strlen($archive)) {
            throw new RagServiceException('The DOCX archive footer is incomplete.');
        }

        $eocd = unpack('vdisk/vcentral_disk/ventries_disk/ventries/Vcentral_size/Vcentral_offset/vcomment_length', substr($archive, $eocdOffset + 4, 18));
        if (! is_array($eocd)
            || $eocd['disk'] !== 0
            || $eocd['central_disk'] !== 0
            || $eocd['entries'] < 1
            || $eocd['entries'] > 10_000
            || strlen($archive) < $eocd['central_offset'] + $eocd['central_size']) {
            throw new RagServiceException('The DOCX archive contains invalid dimensions.');
        }

        $offset = $eocd['central_offset'];
        $centralEnd = $offset + $eocd['central_size'];
        for ($index = 0; $index < $eocd['entries']; $index++) {
            if ($offset + 46 > $centralEnd || substr($archive, $offset, 4) !== "PK\x01\x02") {
                throw new RagServiceException('The DOCX archive directory is malformed.');
            }

            $entry = unpack(
                'vversion_made/vversion_needed/vflags/vcompression/vmod_time/vmod_date/Vcrc/Vcompressed_size/Vuncompressed_size/vfilename_length/vextra_length/vcomment_length/vdisk_start/vinternal_attributes/Vexternal_attributes/Vlocal_offset',
                substr($archive, $offset + 4, 42),
            );
            if (! is_array($entry)) {
                throw new RagServiceException('The DOCX archive directory could not be read.');
            }

            $name = substr($archive, $offset + 46, $entry['filename_length']);
            if ($name === $target) {
                return $this->inflateEntry($archive, $entry);
            }

            $offset += 46 + $entry['filename_length'] + $entry['extra_length'] + $entry['comment_length'];
        }

        throw new RagServiceException('The DOCX document does not contain readable Word content.');
    }

    /** @param array<string, int> $entry */
    private function inflateEntry(string $archive, array $entry): string
    {
        if (($entry['flags'] & 1) === 1) {
            throw new RagServiceException('Password-protected Word documents are not supported.');
        }

        if ($entry['uncompressed_size'] < 1 || $entry['uncompressed_size'] > self::MAX_XML_SIZE) {
            throw new RagServiceException('The Word document contains too much text to process safely.');
        }

        $localOffset = $entry['local_offset'];
        if ($localOffset + 30 > strlen($archive) || substr($archive, $localOffset, 4) !== "PK\x03\x04") {
            throw new RagServiceException('The DOCX archive contains a malformed file entry.');
        }

        $local = unpack('vfilename_length/vextra_length', substr($archive, $localOffset + 26, 4));
        if (! is_array($local)) {
            throw new RagServiceException('The DOCX archive file entry could not be read.');
        }

        $dataOffset = $localOffset + 30 + $local['filename_length'] + $local['extra_length'];
        if ($dataOffset + $entry['compressed_size'] > strlen($archive)) {
            throw new RagServiceException('The DOCX archive file entry is incomplete.');
        }

        $compressed = substr($archive, $dataOffset, $entry['compressed_size']);
        $content = match ($entry['compression']) {
            0 => $compressed,
            8 => @gzinflate($compressed),
            default => false,
        };

        if (! is_string($content)
            || strlen($content) !== $entry['uncompressed_size']
            || sprintf('%u', crc32($content)) !== sprintf('%u', $entry['crc'])) {
            throw new RagServiceException('The DOCX document is damaged or uses an unsupported compression method.');
        }

        return $content;
    }

    /** @return list<string> */
    private function paragraphs(string $xml): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
                throw new RagServiceException('The Word document contains malformed XML.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $paragraphs = [];

        foreach ($xpath->query('//w:body//w:p') ?: [] as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $parts = [];
            foreach ($xpath->query('.//w:t | .//w:tab | .//w:br | .//w:cr', $paragraph) ?: [] as $node) {
                $parts[] = match ($node->localName) {
                    'tab' => "\t",
                    'br', 'cr' => "\n",
                    default => $node->textContent,
                };
            }

            $text = trim(preg_replace('/[ \t]+/u', ' ', implode('', $parts)) ?? '');
            if ($text !== '') {
                $paragraphs[] = $text;
            }
        }

        return $paragraphs;
    }

    /** @return list<string> */
    private function chunk(string $text): array
    {
        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + self::CHUNK_SIZE, $length);
            if ($end < $length) {
                $window = mb_substr($text, $start, self::CHUNK_SIZE);
                $boundary = mb_strrpos($window, "\n");
                if ($boundary !== false && $boundary > intdiv(self::CHUNK_SIZE, 2)) {
                    $end = $start + $boundary;
                }
            }

            $content = trim(mb_substr($text, $start, $end - $start));
            if ($content !== '') {
                $chunks[] = $content;
            }

            if ($end >= $length) {
                break;
            }

            $start = max($start + 1, $end - self::CHUNK_OVERLAP);
        }

        return $chunks;
    }
}
