# Sistem Penilaian Ujikom RPL / PPLG (Tablet-First Full-Stack)

Aplikasi web full-stack untuk manajemen dan penilaian Uji Kompetensi Keahlian (Ujikom) jurusan RPL/PPLG yang dioptimalkan untuk perangkat **Tablet Android (8–10 inci)** dengan sistem Multi-Role Login (**Admin**, **Penguji**, **Siswa**).

## Tech Stack
- **Backend**: Laravel 13 (PHP 8.3+), Laravel Sanctum, Role Middleware, MySQL
- **Frontend**: Vue 3 (Composition API), Vite, Pinia, Vue Router (Navigation Guards), Tailwind CSS
- **Library Frontend**: `tesseract.js` (Semi-OCR NIS), `html5-qrcode`, `qrcode.vue`, `lucide-vue-next`, `xlsx` (SheetJS), `axios`

---

## Struktur Folder Proyek

```text
ujikom-pplg/
├── backend/                          # REST API Laravel 13
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   │   ├── AdminClassController.php
│   │   │   │   ├── AdminDashboardController.php
│   │   │   │   ├── AdminExaminerController.php
│   │   │   │   ├── AdminRubricController.php
│   │   │   │   ├── AdminStudentController.php
│   │   │   │   ├── AssessmentController.php
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── QueueController.php
│   │   │   │   ├── RubricController.php
│   │   │   │   └── StudentDashboardController.php
│   │   │   └── Middleware/RoleMiddleware.php
│   │   └── Models/
│   │       ├── Assessment.php
│   │       ├── AssessmentDetail.php
│   │       ├── RubricItem.php
│   │       ├── SchoolClass.php
│   │       ├── Student.php
│   │       └── User.php
│   ├── bootstrap/app.php
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   │       ├── DatabaseSeeder.php
│   │       ├── RubricItemSeeder.php
│   │       └── UserAndStudentSeeder.php
│   └── routes/api.php
│
└── frontend/                         # SPA Vue 3 + Vite + Tailwind
    ├── src/
    │   ├── components/
    │   │   ├── AppNavbar.vue
    │   │   ├── ToastContainer.vue
    │   │   ├── admin/
    │   │   │   ├── ExaminerFormModal.vue
    │   │   │   ├── PrintQrCardsModal.vue
    │   │   │   ├── StudentFormModal.vue
    │   │   │   └── StudentImportModal.vue
    │   │   └── penguji/
    │   │       ├── DoubleGradingModal.vue
    │   │       └── QrScannerModal.vue
    │   ├── composables/useToast.js
    │   ├── router/index.js
    │   ├── services/api.js
    │   ├── stores/auth.js
    │   ├── utils/
    │   │   ├── excelExport.js
    │   │   └── formatters.js
    │   └── views/
    │       ├── LoginView.vue
    │       ├── admin/AdminDashboardView.vue
    │       ├── penguji/
    │       │   ├── PengujiAssessView.vue
    │       │   └── PengujiQueueView.vue
    │       └── siswa/SiswaDashboardView.vue
    ├── package.json
    ├── tailwind.config.js
    └── vite.config.js
```

---

## Langkah Instalasi & Menjalankan Aplikasi

### Prasyarat
- PHP >= 8.3 & Composer
- MySQL / MariaDB
- Node.js >= 18 & npm

### 1. Setup Backend (Laravel 13)
1. Masuk ke folder `backend`:
   ```bash
   cd ujikom-pplg/backend
   ```
2. Install dependensi PHP:
   ```bash
   composer install
   ```
3. Salin file `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
   *(Di Windows Command Prompt gunakan `copy .env.example .env`)*
4. Buat database MySQL bernama `ujikom_pplg`, lalu sesuaikan kredensial DB di `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ujikom_pplg
   DB_USERNAME=root
   DB_PASSWORD=
   ```
5. Generate application key, jalankan migrasi, dan isi data dummy (seeder):
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```
6. Jalankan server API Laravel:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   > **Catatan Windows:** Di browser pada PC yang sama, buka `http://127.0.0.1:8000` atau `http://localhost:8000` (jangan buka `http://0.0.0.0:8000` karena `0.0.0.0` hanya alamat *bind* server dan akan menghasilkan `ERR_ADDRESS_INVALID` di browser).

### 2. Setup Frontend (Vue 3 + Vite)
1. Buka terminal baru dan masuk ke folder `frontend`:
   ```bash
   cd ujikom-pplg/frontend
   ```
2. Install dependensi NPM:
   ```bash
   npm install
   ```
3. (Opsional) Salin `.env.example` ke `.env`. Secara default Vite sudah mem-proxy `/api` ke `http://127.0.0.1:8000`.
4. Jalankan server pengembangan Vite:
   ```bash
   npm run dev
   ```
5. Buka browser di `http://localhost:5173`.

---

## Akun Demo (Hasil Seeder)

| Role | Email / NIS (Username) | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@ujikom.sch.id` | `admin123` | Akses penuh monitoring, kelas/jurusan (PPLG & TMS), penguji, rubrik dinamis, cetak kartu, ekspor Excel |
| **Penguji PPLG** | `penguji@ujikom.sch.id` | `penguji123` | Ditugaskan ke `XII PPLG 1` • PIN Revisi 6-Digit: `123456` |
| **Penguji TMS** | `penguji.tms@ujikom.sch.id` | `penguji123` | Ditugaskan ke `XII TMS 1` • PIN Revisi 6-Digit: `123456` |
| **Siswa PPLG (36)** | `24251001` s/d `24251036` | Sama dengan NIS | 3 siswa Prioritas (`24251001`, `24251005`, `24251012`) |
| **Siswa TMS (6)** | `24252001` s/d `24252006` | Sama dengan NIS | 1 siswa Prioritas (`24252001`) |

> Anda juga dapat langsung menggunakan **Tombol Demo Login Cepat (1-Klik)** pada halaman `/login`.

---

## Tips Pengujian pada Tablet Android (8–10 Inci)
1. Pastikan Tablet Android dan PC server berada pada jaringan Wi-Fi yang sama.
2. Jalankan `npm run dev` (sudah dikonfigurasi `--host`) dan `php artisan serve --host=0.0.0.0 --port=8000`.
3. Akses dari browser Chrome di tablet ke `http://<IP-LOKAL-PC>:5173` (proxy `/api` meneruskan request API otomatis).
4. **Catatan Kamera QR (`html5-qrcode`)**: Browser Android mewajibkan HTTPS untuk akses kamera pada alamat selain `localhost`. Untuk pengujian lokal via IP LAN tanpa HTTPS, Anda dapat:
   - Menggunakan tunnel HTTPS (seperti Cloudflare Tunnel / ngrok), atau
   - Mengaktifkan flag Chrome Android `chrome://flags/#unsafely-treat-insecure-origin-as-secure` dengan menambahkan `http://<IP-LOKAL-PC>:5173`, atau
   - Memasukkan NIS / token QR secara manual melalui kolom fallback pada modal Scanner QR.
