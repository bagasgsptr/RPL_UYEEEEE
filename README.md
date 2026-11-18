# Aplikasi Peminjaman dan Pengembalian Barang (RPL)

Aplikasi web modern berbasis **PHP Native** dan antarmuka responsif mobile-first yang dibangun sesuai spesifikasi desain.

---

## 🎯 Fitur & Penyesuaian Sesuai Arahan

1. **Form Login:**
   - Semua kolom input (`Username` & `Password`) dibuat **kosong secara default**.
   - Dilengkapi placeholder, ikon pelindung, tombol intip/sembunyikan password (eye toggle), serta link lupa password dan hubungi admin.
2. **Dashboard Dinamis:**
   - Nama pengguna yang sedang login langsung tertera di bagian atas dashboard (*"Halo, [Nama] 👋"*).
   - Menampilkan tanggal aktif format Indonesia.
   - Ringkasan aktivitas barang yang sedang dipinjam.
   - Akses cepat kategori barang (Laptop, Proyektor).
   - Histori peminjaman terbaru dengan status badge (*Disetujui*, *Ditolak*, *Dikembalikan*, *Menunggu*).
3. **Lonceng Notifikasi:**
   - Terletak di pojok kanan atas dashboard dengan indikator unread.
   - Klik lonceng untuk membuka popup daftar pemberitahuan (pemberitahuan persetujuan peminjaman, barang menunggu, jatuh tempo, dll.).
   - Tombol cepat untuk menandai semua notifikasi telah dibaca.
4. **Peminjaman (Katalog & Pengajuan):**
   - Kolom pencarian barang langsung (*real-time filter*).
   - Filter chip kategori: *Semua*, *Elektronik*, *Alat kantor*, *Aksesori*.
   - Modal pengajuan pinjam dengan pemilihan tanggal rencana kembali, jumlah unit, dan alasan peminjaman.
5. **Pengembalian Barang:**
   - Tab pemisah *Dipinjam* dan *Diajukan*.
   - Banner peringatan barang yang mendekati waktu jatuh tempo.
   - Tombol aksi pengembalian barang disertai konfirmasi kondisi fisik barang.
6. **Profil Sederhana (Simple Profile):**
   - Fokus khusus pada pengetahuan identitas akun (Nama Lengkap, Jabatan/Role, ID Karyawan/NISN, Email kantor, serta ringkasan total pinjam & kembali).
   - Fitur **Ganti Foto Profil** (upload berkas gambar JPG/PNG).
   - Fitur **Ganti Password** (verifikasi password lama, password baru, dan konfirmasi).
   - Tombol keluar (*Logout*).
7. **Mode Admin (Persetujuan & Manajemen Karyawan):**
   - Halaman **Persetujuan** (`persetujuan.php`): Meninjau permohonan pinjam karyawan (Setujui / Tolak dengan alasan).
   - Halaman **Data Barang** (`admin_barang.php`): Mengelola dan menambah stok sarana/barang.
   - Halaman **Akun Karyawan** (`admin_karyawan.php`): Tambah akun baru, acak ID karyawan, edit data divisi/jabatan, reset password, dan hapus akun karyawan.

---

## 🚀 Cara Menjalankan Proyek

### Cara 1: Menggunakan PHP Built-in Server (Langsung Jalan tanpa install DB manual)
Sistem dilengkapi otomatis dengan fallback **SQLite** dan seeder data awal, sehingga Anda bisa langsung menjalankannya lewat terminal:

```bash
php -S localhost:8000
```
Buka browser di alamat: [http://localhost:8000](http://localhost:8000)

### Cara 2: Menggunakan XAMPP / Laragon (MySQL)
1. Nyalakan modul **Apache** dan **MySQL** di XAMPP Control Panel.
2. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Buat database baru bernama `peminjaman_barang`.
4. Import berkas SQL yang ada di: `database/database.sql`.
5. Sesuaikan konfigurasi user & password database di `config/database.php` jika diperlukan (default: user `root`, password `""`).

---

## 🔑 Akun Uji Coba (Demo Accounts)

| Username | Password | Role | Keterangan |
| :--- | :--- | :--- | :--- |
| `bagas` | `123456` | User | Akun User / Karyawan IT |
| `admin` | `123456` | Admin | Akun Administrator |

*(Catatan: Kata sandi `password` juga dapat digunakan).*
