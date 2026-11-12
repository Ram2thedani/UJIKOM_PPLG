<?php

namespace Database\Seeders;

use App\Models\RubricItem;
use Illuminate\Database\Seeder;

class RubricItemSeeder extends Seeder
{
    public function run(): void
    {
        $pplgItems = [
            // Halaman 1 (Analisis, Desain, & Arsitektur Database) - Total 35%
            [
                'major' => 'PPLG',
                'page_number' => 1,
                'page_title' => 'Analisis, Desain, & Arsitektur Database',
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
                'major' => 'PPLG',
                'page_number' => 1,
                'page_title' => 'Analisis, Desain, & Arsitektur Database',
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
                'major' => 'PPLG',
                'page_number' => 1,
                'page_title' => 'Analisis, Desain, & Arsitektur Database',
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
                'major' => 'PPLG',
                'page_number' => 2,
                'page_title' => 'Implementasi Koding, Fitur, & Standar',
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
                'major' => 'PPLG',
                'page_number' => 2,
                'page_title' => 'Implementasi Koding, Fitur, & Standar',
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
                'major' => 'PPLG',
                'page_number' => 2,
                'page_title' => 'Implementasi Koding, Fitur, & Standar',
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
                'major' => 'PPLG',
                'page_number' => 3,
                'page_title' => 'Pengujian, Debugging, & Dokumentasi',
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
                'major' => 'PPLG',
                'page_number' => 3,
                'page_title' => 'Pengujian, Debugging, & Dokumentasi',
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
                'major' => 'PPLG',
                'page_number' => 3,
                'page_title' => 'Pengujian, Debugging, & Dokumentasi',
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

        // Rubrik awal untuk Jurusan TMS (Total 100%, dapat ditambah/edit per halaman oleh Admin)
        $tmsItems = [
            [
                'major' => 'TMS',
                'page_number' => 1,
                'page_title' => 'Persiapan Kerja, K3, & Gambar Teknik',
                'item_order' => 1,
                'title' => 'Penerapan K3 dan Penggunaan APD',
                'indicators' => [
                    'Kelengkapan APD standar bengkel',
                    'Kebersihan area kerja',
                    'Kepatuhan SOP keselamatan kerja',
                ],
                'weight' => 15.00,
            ],
            [
                'major' => 'TMS',
                'page_number' => 1,
                'page_title' => 'Persiapan Kerja, K3, & Gambar Teknik',
                'item_order' => 2,
                'title' => 'Membaca Gambar Teknik & Pengukuran Presisi',
                'indicators' => [
                    'Interpretasi simbol & toleransi gambar kerja',
                    'Penggunaan alat ukur (jangka sorong/mikrometer)',
                ],
                'weight' => 20.00,
            ],
            [
                'major' => 'TMS',
                'page_number' => 2,
                'page_title' => 'Proses Pengerjaan & Pengoperasian Peralatan',
                'item_order' => 3,
                'title' => 'Persiapan Mesin, Perkakas, & Parameter Kerja',
                'indicators' => [
                    'Pemilihan alat potong/perkakas sesuai job sheet',
                    'Setting putaran/parameter kerja tepat',
                ],
                'weight' => 25.00,
            ],
            [
                'major' => 'TMS',
                'page_number' => 2,
                'page_title' => 'Proses Pengerjaan & Pengoperasian Peralatan',
                'item_order' => 4,
                'title' => 'Eksekusi Pemesinan / Perawatan / Perakitan',
                'indicators' => [
                    'Urutan langkah kerja sesuai SOP',
                    'Ketelitian pengerjaan & efisiensi waktu',
                ],
                'weight' => 25.00,
            ],
            [
                'major' => 'TMS',
                'page_number' => 3,
                'page_title' => 'Quality Control, Uji Fungsi, & Pelaporan',
                'item_order' => 5,
                'title' => 'Pemeriksaan Dimensi & Uji Fungsi Hasil Kerja',
                'indicators' => [
                    'Kesesuaian ukuran terhadap toleransi',
                    'Uji fungsi & kerapihan finishing benda kerja',
                ],
                'weight' => 15.00,
            ],
        ];

        foreach (array_merge($pplgItems, $tmsItems) as $item) {
            RubricItem::updateOrCreate(
                [
                    'major' => $item['major'],
                    'item_order' => $item['item_order'],
                ],
                $item
            );
        }
    }
}
