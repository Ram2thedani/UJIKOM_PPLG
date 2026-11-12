<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'schoolClass',
            'assessment.examiner',
            'assessment.details.rubricItem',
        ])->where('user_id', $user->id)->firstOrFail();

        $classStudents = Student::with('assessment')
            ->where('class_id', $student->class_id)
            ->orderByDesc('is_priority')
            ->orderBy('nis')
            ->get();

        $gradedStudents = $classStudents->filter(
            fn ($s) => $s->assessment && $s->assessment->status !== 'pending'
        );

        $waitingStudents = $classStudents->filter(
            fn ($s) => ! $s->assessment || $s->assessment->status === 'pending'
        )->values();

        $queuePosition = null;
        if (! $student->assessment || $student->assessment->status === 'pending') {
            $pos = $waitingStudents->search(fn ($s) => $s->id === $student->id);
            $queuePosition = $pos !== false ? $pos + 1 : null;
        }

        $assessment = $student->assessment;

        return response()->json([
            'profile' => [
                'id' => $student->id,
                'name' => $student->user->name,
                'nis' => $student->nis,
                'class_name' => $student->schoolClass->name,
                'academic_year' => $student->schoolClass->academic_year,
                'qr_code_token' => $student->qr_code_token,
                'is_priority' => $student->is_priority,
                'priority_note' => $student->priority_note,
            ],
            'queue_summary' => [
                'total_students' => $classStudents->count(),
                'graded_count' => $gradedStudents->count(),
                'kompeten_count' => $gradedStudents->filter(fn ($s) => $s->assessment->status === 'kompeten')->count(),
                'remedial_count' => $gradedStudents->filter(fn ($s) => $s->assessment->status === 'remedial')->count(),
                'waiting_count' => $waitingStudents->count(),
                'my_queue_position' => $queuePosition,
            ],
            'assessment' => $assessment ? [
                'status' => $assessment->status,
                'final_score' => $assessment->final_score,
                'quick_tags' => $assessment->quick_tags ?? [],
                'examiner_note' => $assessment->examiner_note,
                'duration_seconds' => $assessment->duration_seconds,
                'revision_count' => $assessment->revision_count,
                'submitted_at' => $assessment->submitted_at?->toIso8601String(),
                'examiner_name' => $assessment->examiner?->name,
                'details' => $assessment->details
                    ->sortBy(fn ($d) => $d->rubricItem?->item_order ?? 0)
                    ->map(fn ($detail) => [
                        'rubric_item_id' => $detail->rubric_item_id,
                        'item_order' => $detail->rubricItem?->item_order,
                        'page_number' => $detail->rubricItem?->page_number,
                        'title' => $detail->rubricItem?->title,
                        'weight' => $detail->rubricItem?->weight,
                        'raw_score' => $detail->raw_score,
                        'weighted_score' => $detail->weighted_score,
                    ])->values(),
            ] : [
                'status' => 'pending',
                'final_score' => null,
                'quick_tags' => [],
                'examiner_note' => null,
                'submitted_at' => null,
                'examiner_name' => null,
                'details' => [],
            ],
        ]);
    }
}
