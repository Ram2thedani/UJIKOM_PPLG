<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\RubricItem;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $student = Student::with([
            'user',
            'schoolClass.examiners:id,name',
            'assessments.examiner',
            'assessments.details.rubricItem',
        ])->where('user_id', $user->id)->firstOrFail();

        $classStudents = Student::with('assessments')
            ->where('class_id', $student->class_id)
            ->orderByDesc('is_priority')
            ->orderBy('nis')
            ->get();

        $gradedStudents = $classStudents->filter(
            fn (Student $s) => $s->assessments->where('status', '!=', 'pending')->isNotEmpty()
        );

        $waitingStudents = $classStudents->filter(
            fn (Student $s) => $s->assessments->where('status', '!=', 'pending')->isEmpty()
        )->values();

        $gradedAssessments = $student->assessments->where('status', '!=', 'pending')->values();
        $examinerCount = $gradedAssessments->count();

        $queuePosition = null;
        if ($examinerCount === 0) {
            $pos = $waitingStudents->search(fn ($s) => $s->id === $student->id);
            $queuePosition = $pos !== false ? $pos + 1 : null;
        }

        $kompetenCount = 0;
        $remedialCount = 0;
        foreach ($gradedStudents as $gs) {
            $ga = $gs->assessments->where('status', '!=', 'pending');
            $avg = round((float) $ga->sum('final_score') / max(1, $ga->count()), 2);
            if ($avg >= 80) {
                $kompetenCount++;
            } else {
                $remedialCount++;
            }
        }

        if ($examinerCount > 0) {
            $sumScore = round((float) $gradedAssessments->sum('final_score'), 2);
            $totalHasil = round($sumScore / $examinerCount, 2);
            $finalStatus = $totalHasil >= 80 ? 'kompeten' : 'remedial';

            $rubrics = RubricItem::where('major', $student->schoolClass->major)
                ->orderBy('page_number')
                ->orderBy('item_order')
                ->get();

            $averagedDetails = $rubrics->map(function (RubricItem $rubric) use ($gradedAssessments, $examinerCount) {
                $matchingDetails = $gradedAssessments
                    ->flatMap(fn (Assessment $a) => $a->details)
                    ->where('rubric_item_id', $rubric->id);

                $cnt = max(1, $matchingDetails->count());
                $avgRaw = (int) round((float) $matchingDetails->sum('raw_score') / $cnt);
                $avgWeighted = round((float) $matchingDetails->sum('weighted_score') / $examinerCount, 2);

                return [
                    'rubric_item_id' => $rubric->id,
                    'item_order' => $rubric->item_order,
                    'page_number' => $rubric->page_number,
                    'title' => $rubric->title,
                    'weight' => $rubric->weight,
                    'raw_score' => $avgRaw,
                    'weighted_score' => $avgWeighted,
                ];
            })->values();

            $allTags = $gradedAssessments
                ->flatMap(fn (Assessment $a) => $a->quick_tags ?? [])
                ->unique()
                ->values()
                ->all();

            $combinedNote = $gradedAssessments
                ->filter(fn (Assessment $a) => ! empty($a->examiner_note))
                ->map(fn (Assessment $a) => ($a->examiner?->name ?? 'Penguji').': '.$a->examiner_note)
                ->implode("\n\n");

            $examinerNames = $gradedAssessments
                ->map(fn (Assessment $a) => $a->examiner?->name)
                ->filter()
                ->implode(', ');

            $examinerBreakdown = $gradedAssessments->map(fn (Assessment $a) => [
                'id' => $a->id,
                'examiner_name' => $a->examiner?->name,
                'final_score' => $a->final_score,
                'status' => $a->status,
                'quick_tags' => $a->quick_tags ?? [],
                'examiner_note' => $a->examiner_note,
                'submitted_at' => $a->submitted_at?->toIso8601String(),
            ])->values();

            $latestSubmitted = $gradedAssessments->max('submitted_at');

            $assessmentPayload = [
                'status' => $finalStatus,
                'final_score' => $totalHasil,
                'sum_score' => $sumScore,
                'examiner_count' => $examinerCount,
                'quick_tags' => $allTags,
                'examiner_note' => $combinedNote ?: null,
                'revision_count' => (int) $gradedAssessments->sum('revision_count'),
                'submitted_at' => $latestSubmitted?->toIso8601String(),
                'examiner_name' => $examinerNames,
                'examiner_assessments' => $examinerBreakdown,
                'details' => $averagedDetails,
            ];
        } else {
            $assessmentPayload = [
                'status' => 'pending',
                'final_score' => null,
                'sum_score' => null,
                'examiner_count' => 0,
                'quick_tags' => [],
                'examiner_note' => null,
                'submitted_at' => null,
                'examiner_name' => null,
                'examiner_assessments' => [],
                'details' => [],
            ];
        }

        return response()->json([
            'profile' => [
                'id' => $student->id,
                'name' => $student->user->name,
                'nis' => $student->nis,
                'class_name' => $student->schoolClass->name,
                'major' => $student->schoolClass->major,
                'academic_year' => $student->schoolClass->academic_year,
                'qr_code_token' => $student->qr_code_token,
                'is_priority' => $student->is_priority,
                'priority_note' => $student->priority_note,
            ],
            'queue_summary' => [
                'total_students' => $classStudents->count(),
                'graded_count' => $gradedStudents->count(),
                'kompeten_count' => $kompetenCount,
                'remedial_count' => $remedialCount,
                'waiting_count' => $waitingStudents->count(),
                'my_queue_position' => $queuePosition,
            ],
            'assessment' => $assessmentPayload,
        ]);
    }
}
