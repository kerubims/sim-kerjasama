# SIM-KERMA (Sistem Informasi Manajemen Kerjasama)

<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

SIM-KERMA adalah platform berbasis web yang dirancang untuk mengelola siklus hidup dokumen kerjasama (MoU, MoA, dan IA) secara digital, kolaboratif, dan efisien.

## 🚀 Fitur Utama

- **Dashboard Real-time**: Statistik dokumen berdasarkan status, jenis, dan kategori.
- **Manajemen Dokumen**: Hierarki dokumen (MoU -> MoA -> IA) dengan pelacakan status.
- **Editor Kolaboratif**: Integrasi TinyMCE untuk penyuntingan dokumen langsung di browser.
- **Import/Export Pintar**: Dukungan import dari DOCX dan export ke PDF dengan layout presisi A4.
- **E-Signature & Stamp**: Manajemen tanda tangan dan stempel digital yang terkompresi otomatis.
- **Role-Based Access Control (RBAC)**: Pengaturan hak akses untuk Admin, Unit Kerja, dan Mitra.
- **Pengingat Masa Berlaku**: Notifikasi otomatis sebelum dokumen kedaluwarsa.

---

## 💻 Panduan Instalasi (Lokal)

### Prasyarat
- PHP >= 8.3
- Composer
- Node.js & NPM
- MySQL/MariaDB

### Langkah-langkah
1. **Clone Repository**
   ```bash
   git clone https://github.com/kerubims/sim-kerjasama.git
   cd sim-kerjasama
   ```

2. **Instal Dependensi Backend**
   ```bash
   composer install
   ```

3. **Instal Dependensi Frontend**
   ```bash
   npm install
   ```

4. **Konfigurasi Environment**
   Salin file `.env.example` ke `.env` dan sesuaikan konfigurasi database Anda.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Migrasi & Seed Database**
   ```bash
   php artisan migrate --seed
   ```

6. **Jalankan Aplikasi**
   Gunakan perintah berikut untuk menjalankan server development dan Vite:
   ```bash
   npm run dev
   ```

---

## 📖 Cara Pengoperasian

### 1. Login Pertama Kali
- Gunakan akun admin default yang telah disediakan melalui seeder (cek `DatabaseSeeder.php`).
- Masuk ke Dashboard untuk melihat ringkasan kerjasama.

### 2. Membuat Dokumen Baru
- Navigasi ke menu **Dokumen**.
- Pilih **Tambah Dokumen**.
- Isi detail kerjasama (Judul, Mitra, Masa Berlaku).
- Gunakan editor TinyMCE untuk menulis konten atau **Import dari Word (.docx)**.

### 3. Alur Persetujuan
- Dokumen yang dibuat akan berstatus `Draft`.
- Unggah tanda tangan atau stempel jika diperlukan melalui modul tanda tangan.
- Ubah status menjadi `Aktif` setelah finalisasi.

### 4. Cetak/Export
- Gunakan tombol **Cetak PDF** pada detail dokumen untuk mengunduh versi fisik dokumen yang sudah diformat secara profesional.

---

## 🛠️ Tech Stack

- **Backend**: Laravel 13
- **Frontend**: Blade, Tailwind CSS, Vite
- **Database**: MySQL 8.0
- **Storage**: Intervention Image (Optimasi gambar)
- **Document Engine**: Gotenberg (PDF Generation), Mammoth.js (Docx Parsing)

## 📄 Lisensi

Proyek ini berada di bawah lisensi [MIT](LICENSE).
