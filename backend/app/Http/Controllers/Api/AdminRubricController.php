<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RubricItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRubricController extends Controller
{
    public function index(): JsonResponse
    {
        $items = RubricItem::orderBy('item_order')->get();

        return response()->json([
            'rubrics' => $items,
            'total_weight' => round((float) $items->sum('weight'), 2),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_number' => ['required', 'integer', 'min:1', 'max:3'],
            'item_order' => ['required', 'integer', 'min:1', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'indicators' => ['required', 'array', 'min:1'],
            'indicators.*' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $item = DB::transaction(function () use ($validated) {
            $item = RubricItem::create($validated);
            $this->assertTotalWeightIsHundred();

            return $item;
        });

        return response()->json([
            'message' => 'Butir rubrik berhasil ditambahkan.',
            'rubric' => $item,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = RubricItem::findOrFail($id);

        $validated = $request->validate([
            'page_number' => ['required', 'integer', 'min:1', 'max:3'],
            'item_order' => ['required', 'integer', 'min:1', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'indicators' => ['required', 'array', 'min:1'],
            'indicators.*' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::transaction(function () use ($item, $validated) {
            $item->update($validated);
            $this->assertTotalWeightIsHundred();
        });

        return response()->json([
            'message' => 'Butir rubrik berhasil diperbarui.',
            'rubric' => $item->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = RubricItem::findOrFail($id);

        if ($item->assessmentDetails()->exists()) {
            return response()->json([
                'message' => 'Butir rubrik tidak dapat dihapus karena sudah digunakan pada data penilaian.',
            ], 422);
        }

        DB::transaction(function () use ($item) {
            $item->delete();
            $this->assertTotalWeightIsHundred();
        });

        return response()->json([
            'message' => 'Butir rubrik berhasil dihapus.',
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rubrics' => ['required', 'array', 'min:1'],
            'rubrics.*.id' => ['required', 'integer', 'exists:rubric_items,id'],
            'rubrics.*.page_number' => ['required', 'integer', 'min:1', 'max:3'],
            'rubrics.*.item_order' => ['required', 'integer', 'min:1', 'max:20'],
            'rubrics.*.title' => ['required', 'string', 'max:255'],
            'rubrics.*.indicators' => ['required', 'array', 'min:1'],
            'rubrics.*.indicators.*' => ['required', 'string', 'max:255'],
            'rubrics.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $submittedSum = collect($validated['rubrics'])->sum(fn ($r) => (float) $r['weight']);
        if (abs($submittedSum - 100.0) > 0.01) {
            return response()->json([
                'message' => 'Total bobot seluruh butir rubrik harus tepat 100%. Total saat ini: '.round($submittedSum, 2).'%.',
            ], 422);
        }

        DB::transaction(function () use ($validated) {
            foreach ($validated['rubrics'] as $row) {
                RubricItem::where('id', $row['id'])->update([
                    'page_number' => $row['page_number'],
                    'item_order' => $row['item_order'],
                    'title' => $row['title'],
                    'indicators' => array_values($row['indicators']),
                    'weight' => $row['weight'],
                ]);
            }
            $this->assertTotalWeightIsHundred();
        });

        $updated = RubricItem::orderBy('item_order')->get();

        return response()->json([
            'message' => 'Seluruh bobot dan indikator rubrik berhasil disimpan.',
            'rubrics' => $updated,
            'total_weight' => round((float) $updated->sum('weight'), 2),
        ]);
    }

    private function assertTotalWeightIsHundred(): void
    {
        $totalWeight = (float) RubricItem::sum('weight');

        if (abs($totalWeight - 100.0) > 0.01) {
            abort(response()->json([
                'message' => 'Validasi gagal: total bobot seluruh butir rubrik harus 100% (total akan menjadi '.round($totalWeight, 2).'%). Gunakan simpan massal untuk menyesuaikan bobot antar butir sekaligus.',
            ], 422));
        }
    }
}
