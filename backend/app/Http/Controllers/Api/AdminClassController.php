<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminClassController extends Controller
{
    public function index(): JsonResponse
    {
        $classes = SchoolClass::withCount(['students', 'examiners'])
            ->with(['examiners:id,name,email'])
            ->orderBy('major')
            ->orderBy('name')
            ->get();

        return response()->json([
            'classes' => $classes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'major' => ['required', 'string', 'max:20'],
            'academic_year' => ['required', 'string', 'max:20'],
        ]);

        $validated['major'] = strtoupper(trim($validated['major']));
        $schoolClass = SchoolClass::create($validated);

        return response()->json([
            'message' => 'Kelas berhasil ditambahkan.',
            'class' => $schoolClass,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $schoolClass = SchoolClass::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'major' => ['required', 'string', 'max:20'],
            'academic_year' => ['required', 'string', 'max:20'],
        ]);

        $validated['major'] = strtoupper(trim($validated['major']));
        $schoolClass->update($validated);

        return response()->json([
            'message' => 'Kelas berhasil diperbarui.',
            'class' => $schoolClass,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $schoolClass = SchoolClass::findOrFail($id);

        if ($schoolClass->students()->exists()) {
            return response()->json([
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa terdaftar.',
            ], 422);
        }

        $schoolClass->delete();

        return response()->json([
            'message' => 'Kelas berhasil dihapus.',
        ]);
    }
}
