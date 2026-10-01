from __future__ import annotations

import io
import re
from dataclasses import dataclass

from pypdf import PdfReader
from pypdf.errors import PdfReadError


class PdfExtractionError(ValueError):
    """Raised when a PDF cannot produce safe, usable text chunks."""


@dataclass(frozen=True)
class PageText:
    page_number: int
    content: str


def normalize_text(value: str) -> str:
    value = value.replace("\x00", " ")
    value = re.sub(r"[ \t]+", " ", value)
    value = re.sub(r"\n{3,}", "\n\n", value)
    return value.strip()


def chunk_pages(
    pages: list[PageText],
    chunk_size: int = 1_200,
    overlap: int = 200,
) -> list[dict[str, int | str]]:
    if chunk_size < 200 or overlap < 0 or overlap >= chunk_size:
        raise ValueError("Invalid chunking configuration.")

    chunks: list[dict[str, int | str]] = []
    for page in pages:
        text = normalize_text(page.content)
        if not text:
            continue

        start = 0
        while start < len(text):
            end = min(start + chunk_size, len(text))
            if end < len(text):
                boundary = max(text.rfind("\n", start, end), text.rfind(". ", start, end))
                if boundary > start + chunk_size // 2:
                    end = boundary + 1

            content = text[start:end].strip()
            if content:
                chunks.append(
                    {
                        "chunk_index": len(chunks),
                        "page_number": page.page_number,
                        "content": content,
                        "token_count": len(content.split()),
                    }
                )

            if end >= len(text):
                break
            start = end - overlap

    return chunks


def extract_pdf(data: bytes) -> dict[str, int | list[dict[str, int | str]]]:
    if not data.startswith(b"%PDF-"):
        raise PdfExtractionError("The uploaded file is not a valid PDF document.")

    try:
        reader = PdfReader(io.BytesIO(data), strict=False)
    except (PdfReadError, ValueError, OSError) as exception:
        raise PdfExtractionError("The PDF is damaged or uses an unsupported format.") from exception

    if reader.is_encrypted:
        raise PdfExtractionError("Password-protected PDFs are not supported.")

    pages: list[PageText] = []
    try:
        for page_number, page in enumerate(reader.pages, start=1):
            pages.append(PageText(page_number, page.extract_text() or ""))
    except Exception as exception:
        raise PdfExtractionError("Text could not be extracted from this PDF.") from exception

    chunks = chunk_pages(pages)
    if not chunks:
        raise PdfExtractionError(
            "No selectable text was found. Upload a text-based PDF; scanned image PDFs require OCR."
        )

    return {
        "page_count": len(reader.pages),
        "character_count": sum(len(str(chunk["content"])) for chunk in chunks),
        "chunks": chunks,
    }
