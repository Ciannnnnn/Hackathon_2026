<?php

namespace App\Http\Controllers;

use App\Exceptions\RagServiceException;
use App\Http\Requests\StoreLearningModuleRequest;
use App\Models\LearningModule;
use App\Services\RagExtractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherModuleController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user()->teacher;
        $subjects = $teacher?->subjects()->where('is_active', true)->orderBy('code')->get() ?? collect();
        $selectedSubject = $subjects->firstWhere('id', (int) $request->query('subject')) ?? $subjects->first();

        $modules = LearningModule::query()
            ->with(['subject', 'firstChunk'])
            ->withCount('chunks')
            ->where('teacher_id', $teacher?->id ?? 0)
            ->when($selectedSubject, fn ($query) => $query->where('subject_id', $selectedSubject->id))
            ->orderByDesc('uploaded_at')
            ->get();

        return view('teacher.modules.index', compact('subjects', 'selectedSubject', 'modules'));
    }

    public function store(StoreLearningModuleRequest $request, RagExtractionService $rag): RedirectResponse
    {
        $validated = $request->validated();
        $teacher = $request->user()->teacher;
        $file = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $extension === 'docx'
            ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            : 'application/pdf';
        $storedFilename = Str::uuid().'.'.$extension;
        $directory = "modules/{$teacher->id}/{$validated['subject_id']}";
        $path = $file->storeAs($directory, $storedFilename, 'local');

        if (! is_string($path)) {
            return back()->withErrors(['document' => 'The document could not be stored. Please try again.'])->withInput();
        }

        $module = LearningModule::create([
            'subject_id' => $validated['subject_id'],
            'teacher_id' => $teacher->id,
            'title' => $validated['title'],
            'original_filename' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'stored_filename' => $storedFilename,
            'file_path' => $path,
            'mime_type' => $mimeType,
            'file_size_bytes' => $file->getSize(),
            'processing_status' => 'pending',
            'uploaded_at' => now(),
        ]);

        try {
            $this->process($module, $rag);
        } catch (RagServiceException $exception) {
            return redirect()
                ->route('teacher.modules.index', ['subject' => $module->subject_id])
                ->withErrors(['module' => $exception->getMessage()]);
        }

        return redirect()
            ->route('teacher.modules.index', ['subject' => $module->subject_id])
            ->with('status', "{$module->title} was uploaded and extracted successfully.");
    }

    public function retry(Request $request, LearningModule $module, RagExtractionService $rag): RedirectResponse
    {
        $this->authorizeOwnership($request, $module);

        try {
            $this->process($module, $rag);
        } catch (RagServiceException $exception) {
            return back()->withErrors(['module' => $exception->getMessage()]);
        }

        return back()->with('status', "{$module->title} was processed successfully.");
    }

    public function download(Request $request, LearningModule $module): StreamedResponse
    {
        $this->authorizeOwnership($request, $module);
        abort_unless(Storage::disk('local')->exists($module->file_path), 404);

        return Storage::disk('local')->download(
            $module->file_path,
            $module->original_filename,
            ['Content-Type' => $module->mime_type, 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    public function destroy(Request $request, LearningModule $module): RedirectResponse
    {
        $this->authorizeOwnership($request, $module);
        $path = $module->file_path;
        $title = $module->title;
        $subjectId = $module->subject_id;

        $module->delete();
        Storage::disk('local')->delete($path);

        return redirect()
            ->route('teacher.modules.index', ['subject' => $subjectId])
            ->with('status', "{$title} and its extracted text were deleted.");
    }

    private function process(LearningModule $module, RagExtractionService $rag): void
    {
        $module->update(['processing_status' => 'processing', 'processing_error' => null]);

        try {
            $result = $rag->extract($module->file_path, $module->original_filename);

            DB::transaction(function () use ($module, $result): void {
                $module->chunks()->delete();
                $module->chunks()->createMany($result['chunks']);
                $module->update(['processing_status' => 'ready', 'processing_error' => null]);
            });
        } catch (RagServiceException $exception) {
            $module->update([
                'processing_status' => 'failed',
                'processing_error' => mb_substr($exception->getMessage(), 0, 2_000),
            ]);

            throw $exception;
        }
    }

    private function authorizeOwnership(Request $request, LearningModule $module): void
    {
        abort_unless($request->user()->teacher()->whereKey($module->teacher_id)->exists(), 404);
    }
}
