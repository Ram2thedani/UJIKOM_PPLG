<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminStudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Student::with(['user', 'schoolClass', 'assessment.examiner'])
            ->orderBy('class_id')
            ->orderBy('nis');

        if ($request->filled('class_id') && $request->class_id !== 'all') {
            $query->where('class_id', (int) $request->class_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $students = $query->get()->map(fn (Student $student) => [
            'id' => $student->id,
            'user_id' => $student->user_id,
            'class_id' => $student->class_id,
            'class_name' => $student->schoolClass->name,
            'major' => $student->schoolClass->major,
            'academic_year' => $student->schoolClass->academic_year,
            'name' => $student->user->name,
            'nis' => $student->nis,
            'qr_code_token' => $student->qr_code_token,
            'is_priority' => $student->is_priority,
            'priority_note' => $student->priority_note,
            'assessment_status' => $student->assessment?->status ?? 'pending',
            'final_score' => $student->assessment?->final_score,
            'examiner_name' => $student->assessment?->examiner?->name,
        ]);

        return response()->json([
            'students' => $students,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:50', 'unique:students,nis', 'unique:users,username'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'is_priority' => ['boolean'],
            'priority_note' => ['nullable', 'string', 'max:255'],
        ]);

        $student = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['nis'],
                'email' => null,
                'password' => Hash::make($validated['password'] ?? $validated['nis']),
                'role' => 'siswa',
                'revision_pin' => null,
            ]);

            return Student::create([
                'user_id' => $user->id,
                'class_id' => $validated['class_id'],
                'nis' => $validated['nis'],
                'qr_code_token' => Str::uuid()->toString(),
                'is_priority' => (bool) ($validated['is_priority'] ?? false),
                'priority_note' => $validated['priority_note'] ?? null,
            ]);
        });

        $student->load(['user', 'schoolClass']);

        return response()->json([
            'message' => 'Data siswa berhasil ditambahkan.',
            'student' => $student,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $student = Student::with('user')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'nis')->ignore($student->id),
                Rule::unique('users', 'username')->ignore($student->user_id),
            ],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'is_priority' => ['boolean'],
            'priority_note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($student, $validated) {
            $userData = [
                'name' => $validated['name'],
                'username' => $validated['nis'],
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $student->user->update($userData);

            $student->update([
                'class_id' => $validated['class_id'],
                'nis' => $validated['nis'],
                'is_priority' => (bool) ($validated['is_priority'] ?? false),
                'priority_note' => ($validated['is_priority'] ?? false) ? ($validated['priority_note'] ?? null) : null,
            ]);
        });

        $student->load(['user', 'schoolClass']);

        return response()->json([
            'message' => 'Data siswa berhasil diperbarui.',
            'student' => $student,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $student = Student::with('user')->findOrFail($id);
        $student->user->delete();

        return response()->json([
            'message' => 'Data siswa berhasil dihapus.',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $rows = [];
        $defaultClassId = $request->input('class_id');

        if ($request->hasFile('file')) {
            $request->validate([
                'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
                'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            ]);

            $handle = fopen($request->file('file')->getRealPath(), 'r');
            if ($handle === false) {
                return response()->json(['message' => 'Gagal membaca file CSV.'], 422);
            }

            $firstLine = fgets($handle);
            $delimiter = str_contains((string) $firstLine, ';') ? ';' : ',';
            rewind($handle);

            $headers = null;
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($headers === null) {
                    $headers = array_map(fn ($h) => strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $h))), $data);
                    continue;
                }
                if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $row = [];
                foreach ($headers as $idx => $key) {
                    $row[$key] = trim((string) ($data[$idx] ?? ''));
                }
                $rows[] = $row;
            }
            fclose($handle);
        } else {
            $validated = $request->validate([
                'class_id' => ['nullable', 'integer', 'exists:classes,id'],
                'students' => ['required', 'array', 'min:1'],
                'students.*.name' => ['required', 'string', 'max:255'],
                'students.*.nis' => ['required', 'string', 'max:50'],
                'students.*.class_id' => ['nullable', 'integer', 'exists:classes,id'],
                'students.*.class_name' => ['nullable', 'string', 'max:100'],
                'students.*.is_priority' => ['nullable', 'boolean'],
                'students.*.priority_note' => ['nullable', 'string', 'max:255'],
                'students.*.password' => ['nullable', 'string', 'max:100'],
            ]);
            $rows = $validated['students'];
        }

        if (count($rows) === 0) {
            return response()->json(['message' => 'Tidak ada data siswa yang dapat diimpor.'], 422);
        }

        $fallbackClass = $defaultClassId
            ? SchoolClass::find($defaultClassId)
            : SchoolClass::orderBy('id')->first();

        $created = 0;
        $updated = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $fallbackClass, &$created, &$updated, &$errors) {
            foreach ($rows as $index => $row) {
                $nis = trim((string) ($row['nis'] ?? ''));
                $name = trim((string) ($row['name'] ?? $row['nama'] ?? ''));

                if ($nis === '' || $name === '') {
                    $errors[] = 'Baris '.($index + 1).': NIS dan Nama wajib diisi.';
                    continue;
                }

                $classId = $row['class_id'] ?? null;
                if (! $classId && ! empty($row['class_name'] ?? $row['kelas'] ?? null)) {
                    $className = trim((string) ($row['class_name'] ?? $row['kelas']));
                    $class = SchoolClass::firstOrCreate(
                        ['name' => $className],
                        ['academic_year' => date('Y').'/'.(date('Y') + 1)]
                    );
                    $classId = $class->id;
                }
                $classId = $classId ?: $fallbackClass?->id;

                if (! $classId) {
                    $errors[] = "NIS {$nis}: Kelas tidak ditemukan.";
                    continue;
                }

                $isPriority = filter_var($row['is_priority'] ?? $row['prioritas'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $priorityNote = $row['priority_note'] ?? $row['catatan_prioritas'] ?? null;
                $password = ! empty($row['password']) ? (string) $row['password'] : $nis;

                $existingStudent = Student::where('nis', $nis)->first();

                if ($existingStudent) {
                    $existingStudent->user->update([
                        'name' => $name,
                    ]);
                    $existingStudent->update([
                        'class_id' => $classId,
                        'is_priority' => $isPriority,
                        'priority_note' => $isPriority ? $priorityNote : null,
                    ]);
                    $updated++;
                } else {
                    $user = User::create([
                        'name' => $name,
                        'username' => $nis,
                        'email' => null,
                        'password' => Hash::make($password),
                        'role' => 'siswa',
                        'revision_pin' => null,
                    ]);

                    Student::create([
                        'user_id' => $user->id,
                        'class_id' => $classId,
                        'nis' => $nis,
                        'qr_code_token' => Str::uuid()->toString(),
                        'is_priority' => $isPriority,
                        'priority_note' => $isPriority ? $priorityNote : null,
                    ]);
                    $created++;
                }
            }
        });

        return response()->json([
            'message' => "Impor selesai: {$created} siswa baru ditambahkan, {$updated} siswa diperbarui.",
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }
}
