# Sistem Absensi Pintar Digital (Web & Mobile Ready)

Aplikasi web presensi siswa dan manajemen kehadiran sekolah berbasis QR Code yang modern, responsif, dan siap langsung di-deploy secara gratis ke **GitHub Pages**.

---

## 🌟 Fitur Utama
1. **Multi-Role Login**:
   - **Siswa**: Dashboard kartu kehadiran hari ini, status jam datang & pulang, serta Kartu Pelajar Digital dengan QR Code yang bisa langsung dicetak.
   - **Guru / Wali Kelas**: Dashboard statistik kehadiran kelas yang diampu, scanner kamera presensi, serta monitoring realtime siswa dengan dropdown status kehadiran.
   - **Admin**: Dashboard statistik global seluruh siswa, kelola direktori siswa, kelola akun guru, pengaturan waktu buka/tutup absensi, kelola tanggal merah/hari libur, serta rekap laporan kehadiran periode + export ke Excel.
2. **Scanner Kamera QR Code (Html5Qrcode)**:
   - Mendukung kamera depan & belakang di smartphone atau laptop.
   - Otomatis mencatat absen datang (terlambat / tepat waktu) dan absen pulang (pulang cepat / selesai).
3. **Cetak Kartu Pelajar Digital**:
   - Generator QR Code instan berbasis NISN.
   - Dilengkapi tombol cetak kartu siap print / PDF.
4. **Monitoring & Laporan Excel (SheetJS)**:
   - Export Excel instan langsung terunduh ke perangkat (`.xlsx`).
   - Filter laporan berdasarkan rentang tanggal & kelas.
5. **Zero Backend Required (GitHub Pages Ready)**:
   - Menggunakan engine local web storage di browser, data tersimpan secara persisten dan langsung berfungsi tanpa perlu konfigurasi server.

---

## 🔑 Akun Login Bawaan (Demo Data)

| Role | Username / NISN | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Akses penuh semua menu |
| **Guru (Wali Kelas)** | `guru1` | `guru123` | Wali Kelas `X RPL 1` |
| **Guru (Wali Kelas)** | `guru2` | `guru123` | Wali Kelas `XI TKJ 1` |
| **Siswa (Contoh 1)** | `1234567890` | *(Cukup NISN)* | Ahmad Fauzi (`X RPL 1`) |
| **Siswa (Contoh 2)** | `1234567891` | *(Cukup NISN)* | Bella Salsabila (`X RPL 1`) |
| **Siswa (Contoh 3)** | `1234567893` | *(Cukup NISN)* | Dinda Rahmawati (`XI TKJ 1`) |

---

## 🚀 Cara Deploy ke GitHub Pages (Gratis)

### Langkah 1: Buat Repository Baru di GitHub
1. Buka [github.com/new](https://github.com/new) dan login ke akun GitHub Anda.
2. Beri nama repository, misalnya: `sistem-absensi-digital`.
3. Pilih **Public**, lalu klik **Create repository**.

### Langkah 2: Upload File ke GitHub
Buka terminal di folder project ini, lalu jalankan perintah:

```bash
git init
git add .
git commit -m "Initial commit Sistem Absensi Pintar Digital"
git branch -M main
git remote add origin https://github.com/USERNAME-ANDA/sistem-absensi-digital.git
git push -u origin main
```
*(Ganti `USERNAME-ANDA` dengan username akun GitHub Anda)*

### Langkah 3: Aktifkan GitHub Pages
1. Di halaman repository GitHub Anda, klik tab **Settings** (Pengaturan).
2. Di menu sebelah kiri, klik **Pages**.
3. Pada bagian **Build and deployment > Branch**:
   - Pilih branch: **`main`**
   - Folder: **`/ (root)`**
   - Klik **Save**.
4. Tunggu sekitar 1–2 menit. Tautan website Anda akan aktif di:
   `https://USERNAME-ANDA.github.io/sistem-absensi-digital/`

---

## 📱 Akses Scanner Kamera di Smartphone
Untuk dapat menggunakan scanner kamera di smartphone, pastikan membuka website melalui protokol **HTTPS** (GitHub Pages secara otomatis sudah menyediakan HTTPS).
