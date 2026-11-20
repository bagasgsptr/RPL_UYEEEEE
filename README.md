# Aplikasi Peminjaman dan Pengembalian Barang

Aplikasi web berbasis PHP Native dan MySQL untuk mencatat serta memantau transaksi peminjaman dan pengembalian inventaris barang. Proyek ini disusun sebagai tugas mata kuliah **Rekayasa Perangkat Lunak (RPL)**.

## Deskripsi Singkat

Sistem ini menggantikan pencatatan manual dengan alur pengajuan dan persetujuan terstruktur antara peminjam (User) dan pengelola inventaris (Admin). Tampilan dirancang *mobile-first* agar nyaman diakses melalui ponsel maupun desktop.

## Fitur Aplikasi

**Pengguna (User):**
- **Login & Profil:** Form masuk dengan tombol tampil/sembunyikan password, pembaruan foto profil (JPG/PNG), dan ganti password.
- **Dashboard:** Menampilkan sapaan pengguna aktif, tanggal format Indonesia, ringkasan barang yang sedang dipinjam, pintasan kategori (Laptop, Proyektor), serta riwayat transaksi beserta statusnya (*Menunggu*, *Disetujui*, *Ditolak*, *Dikembalikan*).
- **Notifikasi:** Ikon lonceng dengan penanda belum dibaca untuk info status persetujuan dan pengingat jatuh tempo.
- **Katalog & Pengajuan Pinjam:** Pencarian barang secara langsung, filter kategori (*Semua*, *Elektronik*, *Alat kantor*, *Aksesori*), serta form modal untuk menentukan jumlah unit, tanggal rencana kembali, dan keperluan peminjaman.
- **Pengembalian Barang:** Pemisahan tab *Dipinjam* dan *Diajukan*, peringatan mendekati jatuh tempo, serta konfirmasi kondisi fisik barang saat dikembalikan.

**Administrator (Admin):**
- **Persetujuan (`persetujuan.php`):** Meninjau pengajuan peminjaman masuk untuk disetujui atau ditolak disertai alasan.
- **Data Barang (`admin_barang.php`):** Menambah, mengubah, dan mengelola ketersediaan stok barang.
- **Akun Karyawan (`admin_karyawan.php`):** Menambah akun baru, generate ID karyawan, mengatur divisi/jabatan, reset password, dan menghapus akun.

## Teknologi yang Digunakan

- **Backend:** PHP Native
- **Database:** MySQL (tersedia juga mode *fallback* SQLite untuk pengujian cepat)
- **Frontend:** HTML, CSS, JavaScript

## Instalasi dan Cara Menjalankan

### Opsi 1: PHP Built-in Server (SQLite)
Dapat dijalankan langsung tanpa konfigurasi database manual karena sudah dilengkapi *seeder* data awal:

```bash
php -S localhost:8000