<?php

namespace Database\Seeders;

use App\Models\RubricItem;
use Illuminate\Database\Seeder;

class RubricItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Halaman 1 (Analisis, Desain, & Arsitektur Database) - Total 35%
            [
                'page_number' => 1,
                'item_order' => 1,
                'title' => 'Menggunakan spesifikasi program',
                'indicators' => [
                    'Analisis kebutuhan',
                    'Diagram program',
                    'Deskripsi/spesifikasi',
                    'Wawancara teknis',
                ],
                'weight' => 10.00,
            ],
            [
                'page_number' => 1,
                'item_order' => 2,
                'title' => 'Menerapkan Struktur Data',
                'indicators' => [
                    'Array',
                    'Kustomisasi tipe data',
                    'Alur manipulasi transaksi pinjam-kembali',
                ],
                'weight' => 10.00,
            ],
            [
                'page_number' => 1,
                'item_order' => 3,
                'title' => 'Menggunakan aplikasi basis data',
                'indicators' => [
                    'Pengelolaan SQL',
                    'Relasi tabel',
                    'DDL/DML',
                    'Stored procedure',
                    'Function',
                    'Trigger',
                    'Commit/rollback',
                ],
                'weight' => 15.00,
            ],

            // Halaman 2 (Implementasi Koding, Fitur, & Standar) - Total 45%
            [
                'page_number' => 2,
                'item_order' => 4,
                'title' => 'Mengimplementasikan pemrograman terstruktur',
                'indicators' => [
                    'Logika terstruktur',
                    'CRUD master & role',
                    'Alur transaksi',
                    'Eksekusi source code',
                ],
                'weight' => 20.00,
            ],
            [
                'page_number' => 2,
                'item_order' => 5,
                'title' => 'Menggunakan fitur bahasa pemrograman',
                'indicators' => [
                    'Tipe data',
                    'Struktur kontrol program',
                    'Modul cetak laporan',
                    'Log aktivitas',
                ],
                'weight' => 15.00,
            ],
            [
                'page_number' => 2,
                'item_order' => 6,
                'title' => 'Menerapkan Coding Guidelines dan Best practices',
                'indicators' => [
                    'Standar penulisan kode',
                    'Kerapihan UI/UX',
                    'Efisiensi ukuran & performansi kode',
                ],
                'weight' => 10.00,
            ],

            // Halaman 3 (Pengujian, Debugging, & Dokumentasi) - Total 20%
            [
                'page_number' => 3,
                'item_order' => 7,
                'title' => 'Melakukan debugging',
                'indicators' => [
                    'Persiapan kode',
                    'Pencarian bug',
                    'Error handling',
                    'Perbaikan program',
                ],
                'weight' => 10.00,
            ],
            [
                'page_number' => 3,
                'item_order' => 8,
                'title' => 'Melaksanakan uji coba perangkat lunak',
                'indicators' => [
                    'Skenario data uji',
                    'Prosedur uji fungsi',
                    'Evaluasi hasil',
                    'Fitur denda/nilai plus',
                ],
                'weight' => 5.00,
            ],
            [
                'page_number' => 3,
                'item_order' => 9,
                'title' => 'Membuat dokumentasi kode program',
                'indicators' => [
                    'Dokumentasi modul',
                    'Fungsi & method',
                    'Generate dokumentasi program',
                ],
                'weight' => 5.00,
            ],
        ];

        foreach ($items as $item) {
            RubricItem::updateOrCreate(
                ['item_order' => $item['item_order']],
                $item
            );
        }
    }
}
