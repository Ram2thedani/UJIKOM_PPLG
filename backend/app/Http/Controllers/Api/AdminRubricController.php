<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RubricItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRubricController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RubricItem::orderBy('major')
            ->orderBy('page_number')
            ->orderBy('item_order');

        if ($request->filled('major')) {
            $query->where('major', strtoupper((string) $request->query('major')));
        }

        $items = $query->get();

        return response()->json([
            'rubrics' => $items,
            'total_weight' => round((float) $items->sum('weight'), 2),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'major' => ['required', 'string', 'max:20'],
            'page_number' => ['required', 'integer', 'min:1', 'max:50'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'item_order' => ['required', 'integer', 'min:1', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'indicators' => ['required', 'array', 'min:1'],
            'indicators.*' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['major'] = strtoupper(trim($validated['major']));

        $item = DB::transaction(function () use ($validated) {
            $item = RubricItem::create($validated);
            $this->assertTotalWeightIsHundred($validated['major']);

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
            'major' => ['sometimes', 'string', 'max:20'],
            'page_number' => ['required', 'integer', 'min:1', 'max:50'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'item_order' => ['required', 'integer', 'min:1', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'indicators' => ['required', 'array', 'min:1'],
            'indicators.*' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $major = strtoupper(trim((string) ($validated['major'] ?? $item->major)));
        $validated['major'] = $major;

        DB::transaction(function () use ($item, $validated, $major) {
            $item->update($validated);
            $this->assertTotalWeightIsHundred($major);
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

        $major = $item->major;

        DB::transaction(function () use ($item, $major) {
            $item->delete();
            if (RubricItem::where('major', $major)->exists()) {
                $this->assertTotalWeightIsHundred($major);
            }
        });

        return response()->json([
            'message' => 'Butir rubrik berhasil dihapus.',
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'major' => ['required', 'string', 'max:20'],
            'rubrics' => ['required', 'array', 'min:1'],
            'rubrics.*.id' => ['nullable', 'integer', 'exists:rubric_items,id'],
            'rubrics.*.page_number' => ['required', 'integer', 'min:1', 'max:50'],
            'rubrics.*.page_title' => ['required', 'string', 'max:255'],
            'rubrics.*.title' => ['required', 'string', 'max:255'],
            'rubrics.*.indicators' => ['required', 'array', 'min:1'],
            'rubrics.*.indicators.*' => ['required', 'string', 'max:255'],
            'rubrics.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'rubrics.*.page_title.required' => 'Judul halaman wajib diisi untuk setiap butir rubrik.',
            'rubrics.*.indicators.min' => 'Setiap butir rubrik harus memiliki minimal 1 indikator.',
        ]);

        $major = strtoupper(trim($validated['major']));

        $submittedSum = collect($validated['rubrics'])->sum(fn ($r) => (float) $r['weight']);
        if (abs($submittedSum - 100.0) > 0.01) {
            return response()->json([
                'message' => "Total bobot rubrik jurusan {$major} harus tepat 100%. Total saat ini: ".round($submittedSum, 2).'%.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $major) {
            $keptIds = collect($validated['rubrics'])
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            $toDelete = RubricItem::where('major', $major)
                ->when(count($keptIds) > 0, fn ($q) => $q->whereNotIn('id', $keptIds))
                ->get();

            foreach ($toDelete as $removedItem) {
                if ($removedItem->assessmentDetails()->exists()) {
                    abort(response()->json([
                        'message' => "Butir '{$removedItem->title}' tidak dapat dihapus karena sudah dipakai pada riwayat penilaian siswa.",
                    ], 422));
                }
                $removedItem->delete();
            }

            // Urutkan per halaman lalu simpan urutan item_order 1..N
            $orderedRows = collect($validated['rubrics'])
                ->sortBy('page_number')
                ->values();

            foreach ($orderedRows as $index => $row) {
                $payload = [
                    'major' => $major,
                    'page_number' => (int) $row['page_number'],
                    'page_title' => trim($row['page_title']),
                    'item_order' => $index + 1,
                    'title' => trim($row['title']),
                    'indicators' => array_values($row['indicators']),
                    'weight' => (float) $row['weight'],
                ];

                if (! empty($row['id'])) {
                    RubricItem::where('id', $row['id'])->where('major', $major)->update($payload);
                } else {
                    RubricItem::create($payload);
                }
            }

            $this->assertTotalWeightIsHundred($major);
        });

        $updated = RubricItem::where('major', $major)
            ->orderBy('page_number')
            ->orderBy('item_order')
            ->get();

        return response()->json([
            'message' => "Seluruh halaman, butir, dan bobot rubrik jurusan {$major} berhasil disimpan.",
            'major' => $major,
            'rubrics' => $updated,
            'total_weight' => round((float) $updated->sum('weight'), 2),
        ]);
    }

    private function assertTotalWeightIsHundred(string $major): void
    {
        $totalWeight = (float) RubricItem::where('major', $major)->sum('weight');

        if (abs($totalWeight - 100.0) > 0.01) {
            abort(response()->json([
                'message' => "Validasi gagal: total bobot rubrik jurusan {$major} harus 100% (total akan menjadi ".round($totalWeight, 2).'%).',
            ], 422));
        }
    }
}
