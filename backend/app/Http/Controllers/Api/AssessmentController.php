<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\RubricItem;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentController extends Controller
{
    public function show(int $studentId): JsonResponse
    {
        $student = Student::with([
            'user',
            'schoolClass',
            'assessment.examiner',
            'assessment.details.rubricItem',
        ])->findOrFail($studentId);

        $rubrics = RubricItem::orderBy('item_order')->get();
        $assessment = $student->assessment;

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->user->name,
                'nis' => $student->nis,
                'class_id' => $student->class_id,
                'class_name' => $student->schoolClass->name,
                'academic_year' => $student->schoolClass->academic_year,
                'qr_code_token' => $student->qr_code_token,
                'is_priority' => $student->is_priority,
                'priority_note' => $student->priority_note,
            ],
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
                'details' => $assessment->details->map(fn ($detail) => [
                    'rubric_item_id' => $detail->rubric_item_id,
                    'raw_score' => $detail->raw_score,
                    'weighted_score' => $detail->weighted_score,
                    'rubric_title' => $detail->rubricItem?->title,
                    'weight' => $detail->rubricItem?->weight,
                ])->values(),
            ] : null,
            'rubrics' => $rubrics,
        ]);
    }

    public function verifyPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'revision_pin' => ['required', 'string', 'size:6'],
        ], [
            'revision_pin.required' => 'PIN revisi wajib diisi.',
            'revision_pin.size' => 'PIN revisi harus 6 digit.',
        ]);

        $user = $request->user();

        if (! $user->revision_pin || ! hash_equals((string) $user->revision_pin, (string) $validated['revision_pin'])) {
            return response()->json([
                'valid' => false,
                'message' => 'PIN revisi 6 digit tidak sesuai.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'PIN revisi valid. Mode revisi dibuka.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.rubric_item_id' => ['required', 'integer', 'exists:rubric_items,id'],
            'scores.*.raw_score' => ['required', 'integer', 'min:0', 'max:100'],
            'quick_tags' => ['nullable', 'array'],
            'quick_tags.*' => ['string', 'max:120'],
            'examiner_note' => ['nullable', 'string', 'max:2000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'revision_pin' => ['nullable', 'string', 'size:6'],
        ]);

        $examiner = $request->user();
        $rubrics = RubricItem::orderBy('item_order')->get()->keyBy('id');

        $submittedScores = collect($validated['scores'])->keyBy('rubric_item_id');

        if ($submittedScores->count() !== $rubrics->count()) {
            throw ValidationException::withMessages([
                'scores' => ['Semua butir rubrik ('.$rubrics->count().' butir) wajib dinilai.'],
            ]);
        }

        foreach ($rubrics as $rubricId => $rubric) {
            if (! $submittedScores->has($rubricId)) {
                throw ValidationException::withMessages([
                    'scores' => ["Skor untuk rubrik '{$rubric->title}' wajib diisi."],
                ]);
            }
        }

        $assessment = DB::transaction(function () use ($validated, $examiner, $rubrics, $submittedScores) {
            $existing = Assessment::where('student_id', $validated['student_id'])
                ->lockForUpdate()
                ->first();

            $isRevision = $existing !== null && $existing->status !== 'pending';

            if ($isRevision) {
                $pin = (string) ($validated['revision_pin'] ?? '');
                if (! $examiner->revision_pin || strlen($pin) !== 6 || ! hash_equals((string) $examiner->revision_pin, $pin)) {
                    abort(response()->json([
                        'message' => 'PIN revisi 6 digit diperlukan dan harus sesuai untuk mengubah nilai siswa yang sudah dinilai.',
                        'require_pin' => true,
                    ], 422));
                }
            }

            $totalWeighted = 0.0;
            $detailsPayload = [];

            foreach ($rubrics as $rubricId => $rubric) {
                $rawScore = (int) $submittedScores[$rubricId]['raw_score'];
                $weightedScore = round(($rawScore * (float) $rubric->weight) / 100, 2);
                $totalWeighted += $weightedScore;

                $detailsPayload[] = [
                    'rubric_item_id' => $rubricId,
                    'raw_score' => $rawScore,
                    'weighted_score' => $weightedScore,
                ];
            }

            $finalScore = round($totalWeighted, 2);
            $status = $finalScore >= 80 ? 'kompeten' : 'remedial';

            if ($existing) {
                $existing->update([
                    'examiner_id' => $examiner->id,
                    'final_score' => $finalScore,
                    'status' => $status,
                    'quick_tags' => array_values($validated['quick_tags'] ?? []),
                    'examiner_note' => $validated['examiner_note'] ?? null,
                    'duration_seconds' => (int) ($validated['duration_seconds'] ?? $existing->duration_seconds),
                    'revision_count' => $isRevision ? $existing->revision_count + 1 : $existing->revision_count,
                    'submitted_at' => now(),
                ]);
                $assessment = $existing;
            } else {
                $assessment = Assessment::create([
                    'student_id' => $validated['student_id'],
                    'examiner_id' => $examiner->id,
                    'final_score' => $finalScore,
                    'status' => $status,
                    'quick_tags' => array_values($validated['quick_tags'] ?? []),
                    'examiner_note' => $validated['examiner_note'] ?? null,
                    'duration_seconds' => (int) ($validated['duration_seconds'] ?? 0),
                    'revision_count' => 0,
                    'submitted_at' => now(),
                ]);
            }

            foreach ($detailsPayload as $detail) {
                AssessmentDetail::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'rubric_item_id' => $detail['rubric_item_id'],
                    ],
                    [
                        'raw_score' => $detail['raw_score'],
                        'weighted_score' => $detail['weighted_score'],
                    ]
                );
            }

            return $assessment;
        });

        $assessment->load(['student.user', 'student.schoolClass', 'examiner', 'details.rubricItem']);

        return response()->json([
            'message' => 'Penilaian berhasil disimpan.',
            'assessment' => $assessment,
        ], 201);
    }
}
