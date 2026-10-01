<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\TeacherStudentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherStudentController extends Controller
{
    public function index(Request $request, TeacherStudentService $students): View
    {
        return view('teacher.students.index', $students->roster($request->user(), [
            'search' => mb_substr(trim((string) $request->query('search')), 0, 100),
            'subject' => $request->integer('subject') ?: null,
            'support' => $request->query('support'),
        ]));
    }

    public function show(Request $request, Student $student, TeacherStudentService $students): View
    {
        $data = $students->analysis(
            $request->user(),
            $student,
            $request->integer('subject') ?: null,
        );

        abort_if($data === null, 404);

        return view('teacher.students.show', $data);
    }
}
