<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\RubricItem;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $classes = SchoolClass::with(['students.assessments.examiner', 'examiners:id,name'])
            ->orderBy('major')
            ->orderBy('name')
            ->get();

        $totalStudents = 0;
        $kompetenCount = 0;
        $remedialCount = 0;
        $waitingCount = 0;
        $priorityWaitingCount = 0;
        $allTotalHasilScores = [];

        $classProgress = $classes->map(function (SchoolClass $schoolClass) use (
            &$totalStudents,
            &$kompetenCount,
            &$remedialCount,
            &$waitingCount,
            &$priorityWaitingCount,
            &$allTotalHasilScores
        ) {
            $students = $schoolClass->students;
            $classTotal = $students->count();
            $classKompeten = 0;
            $classRemedial = 0;
            $classPriorityWaiting = 0;

            foreach ($students as $student) {
                $graded = $student->assessments->where('status', '!=', 'pending');
                $cnt = $graded->count();

                if ($cnt > 0) {
                    $totalHasil = round((float) $graded->sum('final_score') / $cnt, 2);
                    $allTotalHasilScores[] = $totalHasil;

                    if ($totalHasil >= 80) {
                        $classKompeten++;
                    } else {
                        $classRemedial++;
                    }
                } elseif ($student->is_priority) {
                    $classPriorityWaiting++;
                }
            }

            $classGraded = $classKompeten + $classRemedial;
            $classWaiting = $classTotal - $classGraded;

            $totalStudents += $classTotal;
            $kompetenCount += $classKompeten;
            $remedialCount += $classRemedial;
            $waitingCount += $classWaiting;
            $priorityWaitingCount += $classPriorityWaiting;

            return [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'major' => $schoolClass->major,
                'academic_year' => $schoolClass->academic_year,
                'examiners' => $schoolClass->examiners->pluck('name')->values(),
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
            ->with([
                'assignedClasses:id,name,major',
                'assessmentsGiven' => fn ($q) => $q->where('status', '!=', 'pending'),
            ])
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
                'examiner_type' => $examiner->role === 'penguji' ? ($examiner->examiner_type ?: 'internal') : 'admin',
                'assigned_classes' => $examiner->assignedClasses->map(fn ($c) => $c->name)->values(),
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
            ->limit(100)
            ->get()
            ->map(fn (Assessment $a) => [
                'id' => $a->id,
                'student_id' => $a->student_id,
                'student_name' => $a->student->user->name,
                'nis' => $a->student->nis,
                'class_name' => $a->student->schoolClass->name,
                'major' => $a->student->schoolClass->major,
                'examiner_name' => $a->examiner->name,
                'examiner_type' => $a->examiner->examiner_type ?: 'internal',
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
                'average_score' => count($allTotalHasilScores) > 0
                    ? round(array_sum($allTotalHasilScores) / count($allTotalHasilScores), 2)
                    : 0,
                'total_examiners' => $examiners->where('role', 'penguji')->count(),
            ],
            'class_progress' => $classProgress,
            'examiner_progress' => $examinerProgress,
            'recent_assessments' => $recentAssessments,
        ]);
    }

    public function export(Request $request, string $classId): JsonResponse
    {
        $query = Student::with([
            'user',
            'schoolClass.examiners:id,name,username,examiner_type',
            'assessments.examiner:id,name,username,examiner_type',
            'assessments.details',
        ])->orderBy('class_id')->orderBy('nis');

        if ($classId !== 'all') {
            $schoolClass = SchoolClass::with('examiners:id,name,username,examiner_type')->findOrFail((int) $classId);
            $query->where('class_id', $schoolClass->id);
            $major = $schoolClass->major;
            $classInfo = [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'major' => $major,
                'academic_year' => $schoolClass->academic_year,
            ];
            $assignedExaminers = $schoolClass->examiners;
        } else {
            $major = strtoupper((string) $request->query('major', 'PPLG'));
            $query->whereHas('schoolClass', fn ($q) => $q->where('major', $major));
            $classInfo = [
                'id' => 'all',
                'name' => "Semua Kelas ({$major})",
                'major' => $major,
                'academic_year' => '',
            ];
            $assignedExaminers = SchoolClass::with('examiners:id,name,username,examiner_type')
                ->where('major', $major)
                ->get()
                ->flatMap(fn (SchoolClass $c) => $c->examiners)
                ->unique('id')
                ->values();
        }

        $rubrics = RubricItem::where('major', $major)
            ->orderBy('page_number')
            ->orderBy('item_order')
            ->get();

        $students = $query->get();

        // Kumpulkan semua penguji yang ditugaskan di kelas ini ATAU yang pernah menilai siswa di set ini
        $gradingExaminers = $students
            ->flatMap(fn (Student $s) => $s->assessments->where('status', '!=', 'pending')->map(fn ($a) => $a->examiner))
            ->filter()
            ->unique('id');

        $examiners = $assignedExaminers
            ->concat($gradingExaminers)
            ->unique('id')
            ->sortBy('id')
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'examiner_type' => $u->examiner_type ?: 'internal',
            ])->values();

        $rows = $students->map(function (Student $student) use ($rubrics, $examiners) {
            $gradedAssessments = $student->assessments->where('status', '!=', 'pending')->values();
            $assessmentsByExaminer = $gradedAssessments->keyBy('examiner_id');

            $examinerScores = [];
            foreach ($examiners as $ex) {
                $exId = $ex['id'];
                /** @var Assessment|null $assessment */
                $assessment = $assessmentsByExaminer->get($exId);
                $detailsMap = $assessment ? $assessment->details->keyBy('rubric_item_id') : collect();

                $itemScores = [];
                foreach ($rubrics as $rubric) {
                    $detail = $detailsMap->get($rubric->id);
                    $itemScores[$rubric->id] = [
                        'raw_score' => $detail?->raw_score,
                        'weighted_score' => $detail?->weighted_score,
                    ];
                }

                $examinerScores[$exId] = $assessment ? [
                    'examiner_id' => $exId,
                    'examiner_name' => $ex['name'],
                    'status' => $assessment->status,
                    'final_score' => $assessment->final_score,
                    'quick_tags' => $assessment->quick_tags ?? [],
                    'examiner_note' => $assessment->examiner_note,
                    'duration_seconds' => $assessment->duration_seconds,
                    'revision_count' => $assessment->revision_count,
                    'submitted_at' => $assessment->submitted_at?->format('Y-m-d H:i:s'),
                    'scores' => $itemScores,
                ] : null;
            }

            $gradedCount = $gradedAssessments->count();
            $sumScore = $gradedCount > 0 ? round((float) $gradedAssessments->sum('final_score'), 2) : null;
            // Total Hasil = jumlah nilai seluruh penguji dibagi banyaknya penguji yang telah menilai
            $totalHasil = $gradedCount > 0 ? round($sumScore / $gradedCount, 2) : null;
            $finalStatus = $gradedCount === 0 ? 'pending' : ($totalHasil >= 80 ? 'kompeten' : 'remedial');

            $averageScores = [];
            foreach ($rubrics as $rubric) {
                if ($gradedCount > 0) {
                    $matchingDetails = $gradedAssessments
                        ->flatMap(fn (Assessment $a) => $a->details)
                        ->where('rubric_item_id', $rubric->id);
                    $cnt = max(1, $matchingDetails->count());
                    $averageScores[$rubric->id] = [
                        'raw_score' => round((float) $matchingDetails->sum('raw_score') / $cnt, 2),
                        'weighted_score' => round((float) $matchingDetails->sum('weighted_score') / $gradedCount, 2),
                    ];
                } else {
                    $averageScores[$rubric->id] = [
                        'raw_score' => null,
                        'weighted_score' => null,
                    ];
                }
            }

            $combinedTags = $gradedAssessments
                ->flatMap(fn (Assessment $a) => $a->quick_tags ?? [])
                ->unique()
                ->values()
                ->all();

            $combinedNotes = $gradedAssessments
                ->filter(fn (Assessment $a) => ! empty($a->examiner_note))
                ->map(fn (Assessment $a) => ($a->examiner?->name ?? 'Penguji').': '.$a->examiner_note)
                ->implode(' | ');

            $examinerNames = $gradedAssessments
                ->map(fn (Assessment $a) => $a->examiner?->name)
                ->filter()
                ->implode(', ');

            $classExaminers = $student->schoolClass->examiners;

            $internalNames = $gradedAssessments
                ->map(fn (Assessment $a) => $a->examiner)
                ->filter(fn ($u) => $u && ($u->examiner_type ?: 'internal') === 'internal')
                ->pluck('name')
                ->unique()
                ->values();

            if ($internalNames->isEmpty()) {
                $internalNames = $classExaminers
                    ->filter(fn ($u) => ($u->examiner_type ?: 'internal') === 'internal')
                    ->pluck('name')
                    ->values();
            }

            $externalNames = $gradedAssessments
                ->map(fn (Assessment $a) => $a->examiner)
                ->filter(fn ($u) => $u && $u->examiner_type === 'eksternal')
                ->pluck('name')
                ->unique()
                ->values();

            if ($externalNames->isEmpty()) {
                $externalNames = $classExaminers
                    ->filter(fn ($u) => $u->examiner_type === 'eksternal')
                    ->pluck('name')
                    ->values();
            }

            return [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->user->name,
                'class_name' => $student->schoolClass->name,
                'major' => $student->schoolClass->major,
                'academic_year' => $student->schoolClass->academic_year,
                'is_priority' => $student->is_priority,
                'priority_note' => $student->priority_note,
                'graded_examiner_count' => $gradedCount,
                'assigned_examiner_count' => max(1, $student->schoolClass->examiners->count()),
                'sum_score' => $sumScore,
                'total_hasil' => $totalHasil,
                'final_score' => $totalHasil,
                'status' => $finalStatus,
                'examiner_name' => $examinerNames ?: null,
                'internal_examiners' => $internalNames,
                'external_examiners' => $externalNames,
                'quick_tags' => $combinedTags,
                'examiner_note' => $combinedNotes ?: null,
                'duration_seconds' => (int) $gradedAssessments->sum('duration_seconds'),
                'revision_count' => (int) $gradedAssessments->sum('revision_count'),
                'submitted_at' => $gradedAssessments->max('submitted_at')?->format('Y-m-d H:i:s'),
                'scores' => $averageScores,
                'examiner_scores' => $examinerScores,
            ];
        })->values();

        return response()->json([
            'class' => $classInfo,
            'rubrics' => $rubrics,
            'examiners' => $examiners,
            'rows' => $rows,
        ]);
    }
}
