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

        $query = SchoolClass::withCount(['students', 'examiners'])
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
        $schoolClass = SchoolClass::with('examiners:id,name')->findOrFail($id);

        if ($user->role === 'penguji' && ! $user->assignedClasses()->where('classes.id', $schoolClass->id)->exists()) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak ditugaskan untuk menguji kelas ini.',
            ], 403);
        }

        $totalAssignedExaminers = max(1, $schoolClass->examiners->count());

        $students = Student::with(['user', 'schoolClass', 'assessments.examiner'])
            ->where('class_id', $schoolClass->id)
            ->orderBy('nis')
            ->get();

        $mappedStudents = $students->map(function (Student $student) use ($user, $totalAssignedExaminers) {
            // Penilaian milik penguji yang sedang login
            $myAssessment = $student->assessments->firstWhere('examiner_id', $user->id);

            // Seluruh penilaian yang sudah selesai untuk siswa ini (dari semua penguji)
            $gradedAssessments = $student->assessments->where('status', '!=', 'pending')->values();
            $gradedCount = $gradedAssessments->count();
            $sumScore = round((float) $gradedAssessments->sum('final_score'), 2);
            $totalHasil = $gradedCount > 0 ? round($sumScore / $gradedCount, 2) : null;

            $status = 'pending';
            if ($myAssessment && $myAssessment->status !== 'pending') {
                $status = $myAssessment->status;
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
                'already_graded' => $myAssessment !== null && $myAssessment->status !== 'pending',
                'graded_by_count' => $gradedCount,
                'total_examiners_assigned' => $totalAssignedExaminers,
                'total_hasil' => $totalHasil,
                'assessment' => $myAssessment ? [
                    'id' => $myAssessment->id,
                    'final_score' => $myAssessment->final_score,
                    'status' => $myAssessment->status,
                    'quick_tags' => $myAssessment->quick_tags ?? [],
                    'examiner_note' => $myAssessment->examiner_note,
                    'duration_seconds' => $myAssessment->duration_seconds,
                    'revision_count' => $myAssessment->revision_count,
                    'submitted_at' => $myAssessment->submitted_at?->toIso8601String(),
                    'examiner_name' => $myAssessment->examiner?->name,
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
            'schoolClass.examiners:id,name',
            'assessments.examiner',
            'assessments.details.rubricItem',
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

        $myAssessment = $student->assessments->firstWhere('examiner_id', $user->id);
        $alreadyGraded = $myAssessment !== null && $myAssessment->status !== 'pending';
        $gradedAssessments = $student->assessments->where('status', '!=', 'pending')->values();
        $gradedCount = $gradedAssessments->count();
        $totalHasil = $gradedCount > 0
            ? round((float) $gradedAssessments->sum('final_score') / $gradedCount, 2)
            : null;

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
                'graded_by_count' => $gradedCount,
                'total_hasil' => $totalHasil,
                'assessment' => $myAssessment ? [
                    'id' => $myAssessment->id,
                    'final_score' => $myAssessment->final_score,
                    'status' => $myAssessment->status,
                    'quick_tags' => $myAssessment->quick_tags ?? [],
                    'examiner_note' => $myAssessment->examiner_note,
                    'duration_seconds' => $myAssessment->duration_seconds,
                    'revision_count' => $myAssessment->revision_count,
                    'submitted_at' => $myAssessment->submitted_at?->toIso8601String(),
                    'examiner_name' => $myAssessment->examiner?->name,
                ] : null,
            ],
        ]);
    }
}
