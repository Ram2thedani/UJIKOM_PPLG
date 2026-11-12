<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminExaminerController extends Controller
{
    public function index(): JsonResponse
    {
        $examiners = User::where('role', 'penguji')
            ->withCount(['assessmentsGiven' => fn ($q) => $q->where('status', '!=', 'pending')])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'role' => $u->role,
                'has_pin' => $u->hasPinConfigured(),
                'assessments_count' => $u->assessments_given_count,
                'created_at' => $u->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'examiners' => $examiners,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'revision_pin' => ['required', 'digits:6'],
        ]);

        $examiner = User::create([
            'name' => $validated['name'],
            'username' => $validated['email'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'penguji',
            'revision_pin' => $validated['revision_pin'],
        ]);

        return response()->json([
            'message' => 'Akun penguji berhasil ditambahkan.',
            'examiner' => $examiner,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $examiner = User::where('role', 'penguji')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($examiner->id),
                Rule::unique('users', 'username')->ignore($examiner->id),
            ],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'revision_pin' => ['nullable', 'digits:6'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'username' => $validated['email'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        if (! empty($validated['revision_pin'])) {
            $payload['revision_pin'] = $validated['revision_pin'];
        }

        $examiner->update($payload);

        return response()->json([
            'message' => 'Akun penguji berhasil diperbarui.',
            'examiner' => $examiner,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $examiner = User::where('role', 'penguji')->findOrFail($id);

        if ($examiner->assessmentsGiven()->exists()) {
            return response()->json([
                'message' => 'Akun penguji tidak dapat dihapus karena sudah memiliki riwayat penilaian.',
            ], 422);
        }

        $examiner->delete();

        return response()->json([
            'message' => 'Akun penguji berhasil dihapus.',
        ]);
    }
}
