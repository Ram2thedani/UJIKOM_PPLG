<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\RubricItem;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $classes = SchoolClass::with(['students.assessment'])->orderBy('name')->get();

        $totalStudents = 0;
        $kompetenCount = 0;
        $remedialCount = 0;
        $waitingCount = 0;
        $priorityWaitingCount = 0;
        $allScores = [];

        $classProgress = $classes->map(function (SchoolClass $schoolClass) use (
            &$totalStudents,
            &$kompetenCount,
            &$remedialCount,
            &$waitingCount,
            &$priorityWaitingCount,
            &$allScores
        ) {
            $students = $schoolClass->students;
            $classTotal = $students->count();
            $classKompeten = $students->filter(fn ($s) => $s->assessment?->status === 'kompeten')->count();
            $classRemedial = $students->filter(fn ($s) => $s->assessment?->status === 'remedial')->count();
            $classGraded = $classKompeten + $classRemedial;
            $classWaiting = $classTotal - $classGraded;
            $classPriorityWaiting = $students->filter(
                fn ($s) => $s->is_priority && (! $s->assessment || $s->assessment->status === 'pending')
            )->count();

            foreach ($students as $student) {
                if ($student->assessment && $student->assessment->status !== 'pending') {
                    $allScores[] = (float) $student->assessment->final_score;
                }
            }

            $totalStudents += $classTotal;
            $kompetenCount += $classKompeten;
            $remedialCount += $classRemedial;
            $waitingCount += $classWaiting;
            $priorityWaitingCount += $classPriorityWaiting;

            return [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'academic_year' => $schoolClass->academic_year,
                'total' => $classTotal,
                'graded' => $classGraded,
                'kompeten' => $classKompeten,
                'remedial' => $classRemedial,
                'waiting' => $classWaiting,
                'priority_waiting' => $classPriorityWaiting,
                'progress_percent' => $classTotal > 0 ? round(($classGraded / $classTotal) * 100, 1) : 0,
            ];
        })->values();

        $examiners = User::whereIn('role', ['penguji', 'admin'])
            ->with(['assessmentsGiven' => fn ($q) => $q->where('status', '!=', 'pending')])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $examinerProgress = $examiners->map(function (User $examiner) {
            $assessments = $examiner->assessmentsGiven;
            $count = $assessments->count();

            return [
                'id' => $examiner->id,
                'name' => $examiner->name,
                'username' => $examiner->username,
                'role' => $examiner->role,
                'total_graded' => $count,
                'kompeten_count' => $assessments->where('status', 'kompeten')->count(),
                'remedial_count' => $assessments->where('status', 'remedial')->count(),
                'average_score' => $count > 0 ? round((float) $assessments->avg('final_score'), 2) : 0,
                'average_duration_seconds' => $count > 0 ? (int) round((float) $assessments->avg('duration_seconds')) : 0,
            ];
        })->values();

        $recentAssessments = Assessment::with(['student.user', 'student.schoolClass', 'examiner'])
            ->where('status', '!=', 'pending')
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get()
            ->map(fn (Assessment $a) => [
                'id' => $a->id,
                'student_id' => $a->student_id,
                'student_name' => $a->student->user->name,
                'nis' => $a->student->nis,
                'class_name' => $a->student->schoolClass->name,
                'examiner_name' => $a->examiner->name,
                'final_score' => $a->final_score,
                'status' => $a->status,
                'revision_count' => $a->revision_count,
                'duration_seconds' => $a->duration_seconds,
                'submitted_at' => $a->submitted_at?->toIso8601String(),
            ]);

        $gradedCount = $kompetenCount + $remedialCount;

        return response()->json([
            'summary' => [
                'total_students' => $totalStudents,
                'graded_count' => $gradedCount,
                'kompeten_count' => $kompetenCount,
                'remedial_count' => $remedialCount,
                'waiting_count' => $waitingCount,
                'priority_waiting_count' => $priorityWaitingCount,
                'progress_percent' => $totalStudents > 0 ? round(($gradedCount / $totalStudents) * 100, 1) : 0,
                'average_score' => count($allScores) > 0 ? round(array_sum($allScores) / count($allScores), 2) : 0,
                'total_examiners' => $examiners->where('role', 'penguji')->count(),
            ],
            'class_progress' => $classProgress,
            'examiner_progress' => $examinerProgress,
            'recent_assessments' => $recentAssessments,
        ]);
    }

    public function export(string $classId): JsonResponse
    {
        $rubrics = RubricItem::orderBy('item_order')->get();

        $query = Student::with([
            'user',
            'schoolClass',
            'assessment.examiner',
            'assessment.details',
        ])->orderBy('class_id')->orderBy('nis');

        $classInfo = ['id' => 'all', 'name' => 'Semua Kelas', 'academic_year' => ''];

        if ($classId !== 'all') {
            $schoolClass = SchoolClass::findOrFail((int) $classId);
            $query->where('class_id', $schoolClass->id);
            $classInfo = [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'academic_year' => $schoolClass->academic_year,
            ];
        }

        $rows = $query->get()->map(function (Student $student) use ($rubrics) {
            $assessment = $student->assessment;
            $detailsMap = $assessment
                ? $assessment->details->keyBy('rubric_item_id')
                : collect();

            $scores = [];
            foreach ($rubrics as $rubric) {
                $detail = $detailsMap->get($rubric->id);
                $scores[$rubric->id] = [
                    'raw_score' => $detail?->raw_score,
                    'weighted_score' => $detail?->weighted_score,
                ];
            }

            return [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->user->name,
                'class_name' => $student->schoolClass->name,
                'academic_year' => $student->schoolClass->academic_year,
                'is_priority' => $student->is_priority,
                'priority_note' => $student->priority_note,
                'status' => $assessment ? $assessment->status : 'pending',
                'final_score' => $assessment ? $assessment->final_score : null,
                'examiner_name' => $assessment?->examiner?->name,
                'quick_tags' => $assessment?->quick_tags ?? [],
                'examiner_note' => $assessment?->examiner_note,
                'duration_seconds' => $assessment?->duration_seconds ?? 0,
                'revision_count' => $assessment?->revision_count ?? 0,
                'submitted_at' => $assessment?->submitted_at?->format('Y-m-d H:i:s'),
                'scores' => $scores,
            ];
        })->values();

        return response()->json([
            'class' => $classInfo,
            'rubrics' => $rubrics,
            'rows' => $rows,
        ]);
    }
}
