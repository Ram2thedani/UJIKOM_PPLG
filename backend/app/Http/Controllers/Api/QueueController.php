<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function classes(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SchoolClass::withCount('students')
            ->orderBy('major')
            ->orderBy('name');

        if ($user->role === 'penguji') {
            $assignedIds = $user->assignedClasses()->pluck('classes.id');
            $query->whereIn('id', $assignedIds);
        }

        return response()->json([
            'classes' => $query->get(),
        ]);
    }

    public function classQueue(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $schoolClass = SchoolClass::findOrFail($id);

        if ($user->role === 'penguji' && ! $user->assignedClasses()->where('classes.id', $schoolClass->id)->exists()) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak ditugaskan untuk menguji kelas ini.',
            ], 403);
        }

        $students = Student::with(['user', 'schoolClass', 'assessment.examiner'])
            ->where('class_id', $schoolClass->id)
            ->orderBy('nis')
            ->get();

        $mappedStudents = $students->map(function (Student $student) {
            $assessment = $student->assessment;
            $status = 'pending';

            if ($assessment && $assessment->status !== 'pending') {
                $status = $assessment->status;
            } elseif ($student->is_priority) {
                $status = 'priority';
            }

            return [
                'id' => $student->id,
                'user_id' => $student->user_id,
                'class_id' => $student->class_id,
                'major' => $student->schoolClass->major,
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

    public function scanQr(Request $request, string $qrToken): JsonResponse
    {
        $user = $request->user();

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
                'message' => 'Siswa dengan NIS / QR Code tersebut tidak ditemukan.',
            ], 404);
        }

        if ($user->role === 'penguji' && ! $user->assignedClasses()->where('classes.id', $student->class_id)->exists()) {
            return response()->json([
                'message' => "Siswa {$student->user->name} berada di kelas {$student->schoolClass->name} ({$student->schoolClass->major}) yang tidak termasuk dalam penugasan Anda.",
            ], 403);
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
                'major' => $student->schoolClass->major,
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
