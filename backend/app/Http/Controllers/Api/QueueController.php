<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class QueueController extends Controller
{
    public function classes(): JsonResponse
    {
        $classes = SchoolClass::withCount('students')
            ->orderBy('name')
            ->get();

        return response()->json([
            'classes' => $classes,
        ]);
    }

    public function classQueue(int $id): JsonResponse
    {
        $schoolClass = SchoolClass::findOrFail($id);

        $students = Student::with(['user', 'schoolClass', 'assessment.examiner'])
            ->where('class_id', $schoolClass->id)
            ->orderBy('nis')
            ->get();

        $mappedStudents = $students->map(function (Student $student) {
            $assessment = $student->assessment;
            $status = 'pending';

            if ($assessment && $assessment->status !== 'pending') {
                $status = $assessment->status; // 'kompeten' | 'remedial'
            } elseif ($student->is_priority) {
                $status = 'priority';
            }

            return [
                'id' => $student->id,
                'user_id' => $student->user_id,
                'class_id' => $student->class_id,
                'name' => $student->user->name,
                'nis' => $student->nis,
                'qr_code_token' => $student->qr_code_token,
                'is_priority' => $student->is_priority,
                'priority_note' => $student->priority_note,
                'queue_status' => $status,
                'already_graded' => $assessment !== null && $assessment->status !== 'pending',
                'assessment' => $assessment ? [
                    'id' => $assessment->id,
                    'final_score' => $assessment->final_score,
                    'status' => $assessment->status,
                    'quick_tags' => $assessment->quick_tags ?? [],
                    'examiner_note' => $assessment->examiner_note,
                    'duration_seconds' => $assessment->duration_seconds,
                    'revision_count' => $assessment->revision_count,
                    'submitted_at' => $assessment->submitted_at?->toIso8601String(),
                    'examiner_name' => $assessment->examiner?->name,
                ] : null,
            ];
        });

        $stats = [
            'total' => $mappedStudents->count(),
            'kompeten' => $mappedStudents->where('queue_status', 'kompeten')->count(),
            'remedial' => $mappedStudents->where('queue_status', 'remedial')->count(),
            'priority' => $mappedStudents->where('queue_status', 'priority')->count(),
            'pending' => $mappedStudents->where('queue_status', 'pending')->count(),
            'graded' => $mappedStudents->where('already_graded', true)->count(),
        ];

        return response()->json([
            'class' => $schoolClass,
            'stats' => $stats,
            'students' => $mappedStudents->values(),
        ]);
    }

    public function scanQr(string $qrToken): JsonResponse
    {
        $student = Student::with([
            'user',
            'schoolClass',
            'assessment.examiner',
            'assessment.details.rubricItem',
        ])->where('qr_code_token', $qrToken)
            ->orWhere('nis', $qrToken)
            ->first();

        if (! $student) {
            return response()->json([
                'message' => 'QR Code siswa tidak valid atau tidak terdaftar.',
            ], 404);
        }

        $assessment = $student->assessment;
        $alreadyGraded = $assessment !== null && $assessment->status !== 'pending';

        return response()->json([
            'already_graded' => $alreadyGraded,
            'student' => [
                'id' => $student->id,
                'name' => $student->user->name,
                'nis' => $student->nis,
                'class_name' => $student->schoolClass->name,
                'academic_year' => $student->schoolClass->academic_year,
                'qr_code_token' => $student->qr_code_token,
                'is_priority' => $student->is_priority,
                'priority_note' => $student->priority_note,
                'assessment' => $assessment ? [
                    'id' => $assessment->id,
                    'final_score' => $assessment->final_score,
                    'status' => $assessment->status,
                    'quick_tags' => $assessment->quick_tags ?? [],
                    'examiner_note' => $assessment->examiner_note,
                    'duration_seconds' => $assessment->duration_seconds,
                    'revision_count' => $assessment->revision_count,
                    'submitted_at' => $assessment->submitted_at?->toIso8601String(),
                    'examiner_name' => $assessment->examiner?->name,
                ] : null,
            ],
        ]);
    }
}
