<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserAndStudentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Admin
        User::updateOrCreate(
            ['username' => 'admin@ujikom.sch.id'],
            [
                'name' => 'Administrator Ujikom',
                'email' => 'admin@ujikom.sch.id',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'revision_pin' => '123456',
            ]
        );

        // 2. Kelas PPLG & Kelas TMS
        $pplgClass = SchoolClass::updateOrCreate(
            ['name' => 'XII PPLG 1', 'academic_year' => '2026/2027'],
            ['major' => 'PPLG']
        );

        $tmsClass = SchoolClass::updateOrCreate(
            ['name' => 'XII TMS 1', 'academic_year' => '2026/2027'],
            ['major' => 'TMS']
        );

        // 3. Akun Penguji PPLG & Penguji TMS (Penugasan kelas masing-masing)
        $examinerPplg = User::updateOrCreate(
            ['username' => 'penguji@ujikom.sch.id'],
            [
                'name' => 'Budi Santoso, S.Kom',
                'email' => 'penguji@ujikom.sch.id',
                'password' => Hash::make('penguji123'),
                'role' => 'penguji',
                'revision_pin' => '123456',
            ]
        );
        $examinerPplg->assignedClasses()->sync([$pplgClass->id]);

        $examinerTms = User::updateOrCreate(
            ['username' => 'penguji.tms@ujikom.sch.id'],
            [
                'name' => 'Agus Wijaya, S.T',
                'email' => 'penguji.tms@ujikom.sch.id',
                'password' => Hash::make('penguji123'),
                'role' => 'penguji',
                'revision_pin' => '123456',
            ]
        );
        $examinerTms->assignedClasses()->sync([$tmsClass->id]);

        // 4. 36 Akun Siswa PPLG (3 siswa prioritas)
        $pplgStudentNames = [
            'Aditya Pratama Wijaya',
            'Aisyah Nur Rahma',
            'Alif Rizky Ramadhan',
            'Ananda Putri Lestari',
            'Bagas Saputra',
            'Bintang Maharani',
            'Cahya Dwi Kusuma',
            'Daffa Maulana Putra',
            'Desi Ratna Sari',
            'Dimas Adi Nugroho',
            'Eka Febrianti',
            'Fajar Sidik Permana',
            'Farah Az-Zahra',
            'Galih Setiawan',
            'Gita Permata Hati',
            'Hafiz Alamsyah',
            'Indah Kusumawati',
            'Iqbal Ramadhan',
            'Jasmine Putri Azzahra',
            'Kevin Sanjaya',
            'Laila Nur Azizah',
            'Muhammad Ilham',
            'Nabila Safitri',
            'Naufal Hakim',
            'Putri Ayu Wulandari',
            'Rafi Ahmad Fauzan',
            'Rahma Dwi Utami',
            'Raka Pradipta',
            'Rina Oktaviani',
            'Rizki Kurniawan',
            'Salsaabila Putri',
            'Satria Wibowo',
            'Syifa Nurul Hidayah',
            'Taufik Hidayat',
            'Vina Melati Sukma',
            'Yoga Prasetyo',
        ];

        $priorityIndices = [
            0 => 'Dispensasi Peserta LKS Tingkat Provinsi',
            4 => 'Jadwal Ujian Masuk Perguruan Tinggi',
            11 => 'Keperluan Medis / Pulang Lebih Awal',
        ];

        foreach ($pplgStudentNames as $index => $name) {
            $nis = (string) (24251001 + $index);
            $this->upsertStudent($name, $nis, $pplgClass->id, array_key_exists($index, $priorityIndices), $priorityIndices[$index] ?? null);
        }

        // 5. Contoh 6 Akun Siswa TMS
        $tmsStudentNames = [
            'Ahmad Fauzi',
            'Bayu Firmansyah',
            'Dendi Kurnia',
            'Fikri Haikal',
            'Hendra Kusuma',
            'Wahyu Saputro',
        ];

        foreach ($tmsStudentNames as $index => $name) {
            $nis = (string) (24252001 + $index);
            $this->upsertStudent($name, $nis, $tmsClass->id, $index === 0, $index === 0 ? 'Persiapan Lomba Teknik' : null);
        }
    }

    private function upsertStudent(string $name, string $nis, int $classId, bool $isPriority, ?string $priorityNote): void
    {
        $user = User::updateOrCreate(
            ['username' => $nis],
            [
                'name' => $name,
                'email' => null,
                'password' => Hash::make($nis),
                'role' => 'siswa',
                'revision_pin' => null,
            ]
        );

        $existingStudent = Student::where('nis', $nis)->first();

        Student::updateOrCreate(
            ['nis' => $nis],
            [
                'user_id' => $user->id,
                'class_id' => $classId,
                'qr_code_token' => $existingStudent?->qr_code_token ?? Str::uuid()->toString(),
                'is_priority' => $isPriority,
                'priority_note' => $priorityNote,
            ]
        );
    }
}
