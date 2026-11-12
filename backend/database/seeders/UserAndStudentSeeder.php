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

        // 2. Akun Penguji
        User::updateOrCreate(
            ['username' => 'penguji@ujikom.sch.id'],
            [
                'name' => 'Budi Santoso, S.Kom',
                'email' => 'penguji@ujikom.sch.id',
                'password' => Hash::make('penguji123'),
                'role' => 'penguji',
                'revision_pin' => '123456',
            ]
        );

        // 3. Kelas XII PPLG 1
        $schoolClass = SchoolClass::firstOrCreate(
            ['name' => 'XII PPLG 1', 'academic_year' => '2026/2027']
        );

        // 4. 36 Akun Siswa (3 siswa prioritas)
        $studentNames = [
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

        foreach ($studentNames as $index => $name) {
            $nis = (string) (24251001 + $index);

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
                    'class_id' => $schoolClass->id,
                    'qr_code_token' => $existingStudent?->qr_code_token ?? Str::uuid()->toString(),
                    'is_priority' => array_key_exists($index, $priorityIndices),
                    'priority_note' => $priorityIndices[$index] ?? null,
                ]
            );
        }
    }
}
