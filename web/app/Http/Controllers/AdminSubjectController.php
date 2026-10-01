<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectEnrollmentsRequest;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminSubjectController extends Controller
{
    public function index(): View
    {
        return view('admin.subjects.index', [
            'subjects' => Subject::query()
                ->with(['teacher.user', 'students.user'])
                ->withCount(['enrollments as active_students_count' => fn ($query) => $query->where('status', 'active')])
                ->orderByDesc('is_active')
                ->orderBy('code')
                ->get(),
            'teachers' => Teacher::query()
                ->with('user')
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->get()
                ->sortBy(fn (Teacher $teacher): string => $teacher->user->full_name)
                ->values(),
            'students' => Student::query()
                ->with('user')
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->get()
                ->sortBy(fn (Student $student): string => $student->user->full_name)
                ->values(),
        ]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $studentIds = $validated['student_ids'] ?? [];
        unset($validated['student_ids']);

        $subject = DB::transaction(function () use ($validated, $studentIds): Subject {
            $subject = Subject::create($validated);

            foreach ($studentIds as $studentId) {
                Enrollment::create([
                    'student_id' => $studentId,
                    'subject_id' => $subject->id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]);
            }

            return $subject;
        });

        return redirect()
            ->route('admin.subjects.index')
            ->with('status', "{$subject->code} was created with ".count($studentIds).' enrolled student(s).');
    }

    public function updateEnrollments(UpdateSubjectEnrollmentsRequest $request, Subject $subject): RedirectResponse
    {
        $selectedIds = collect($request->validated('student_ids'))->map(fn ($id): int => (int) $id);

        DB::transaction(function () use ($subject, $selectedIds): void {
            $subject->enrollments()->whereNotIn('student_id', $selectedIds)->update(['status' => 'dropped']);

            foreach ($selectedIds as $studentId) {
                Enrollment::updateOrCreate(
                    ['subject_id' => $subject->id, 'student_id' => $studentId],
                    ['status' => 'active', 'enrolled_at' => now()],
                );
            }
        });

        return back()->with('status', "{$subject->code} enrollments were updated.");
    }

    public function toggleStatus(Subject $subject): RedirectResponse
    {
        $subject->update(['is_active' => ! $subject->is_active]);

        return back()->with('status', "{$subject->code} is now ".($subject->is_active ? 'active.' : 'inactive.'));
    }
}
