-- database/database.sql
-- Database: peminjaman_barang

CREATE DATABASE IF NOT EXISTS `peminjaman_barang` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `peminjaman_barang`;

-- 1. Tabel Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `id_karyawan` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `jabatan` VARCHAR(100) DEFAULT 'Staff',
    `foto` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Kategori Barang
CREATE TABLE IF NOT EXISTS `kategori` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Barang
CREATE TABLE IF NOT EXISTS `barang` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_barang` VARCHAR(50) NOT NULL UNIQUE,
    `nama_barang` VARCHAR(100) NOT NULL,
    `kategori_id` INT NOT NULL,
    `stok_total` INT NOT NULL DEFAULT 1,
    `stok_tersedia` INT NOT NULL DEFAULT 1,
    `ikon` VARCHAR(50) DEFAULT 'laptop', -- laptop, proyektor, mouse, alat_kantor
    `deskripsi` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`kategori_id`) REFERENCES `kategori`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Peminjaman
CREATE TABLE IF NOT EXISTS `peminjaman` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_peminjaman` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `barang_id` INT NOT NULL,
    `jumlah` INT NOT NULL DEFAULT 1,
    `tgl_pinjam` DATE NOT NULL,
    `tgl_kembali_rencana` DATE NOT NULL,
    `tgl_kembali_aktual` DATE DEFAULT NULL,
    `status` ENUM('menunggu', 'disetujui', 'ditolak', 'dikembalikan') NOT NULL DEFAULT 'menunggu',
    `alasan_pinjam` TEXT DEFAULT NULL,
    `alasan_tolak` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`barang_id`) REFERENCES `barang`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Notifikasi
CREATE TABLE IF NOT EXISTS `notifikasi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `peminjaman_id` INT DEFAULT NULL,
    `judul` VARCHAR(100) NOT NULL,
    `pesan` TEXT NOT NULL,
    `tipe` ENUM('menunggu', 'disetujui', 'ditolak', 'jatuh_tempo', 'info') NOT NULL DEFAULT 'info',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- DATA DUMMY / INITIAL SEED
-- Password default: '123456' -> hash bcrypt: '$2y$10$wS2Wb5qD5H4b18uV04iPaeUe5XjI.v5EwZc7iN5M64gV6oEfe37aO'
-- Untuk kemudahan pengujian, kita juga dukung plain password fallback jika diperlukan.

INSERT INTO `kategori` (`id`, `nama_kategori`, `slug`) VALUES
(1, 'Elektronik', 'elektronik'),
(2, 'Alat kantor', 'alat-kantor'),
(3, 'Aksesori', 'aksesori');

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `id_karyawan`, `email`, `jabatan`, `foto`) VALUES
(1, 'bagas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bagas Gusti Saputro', 'user', 'IT-001', 'bagas@kantor.id', 'Staff IT', NULL),
(2, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Utama', 'admin', 'ADM-001', 'admin@kantor.id', 'Kepala Sarpras & IT', NULL);
-- Password untuk username 'bagas' dan 'admin' adalah: password (atau 123456)

INSERT INTO `barang` (`id`, `kode_barang`, `nama_barang`, `kategori_id`, `stok_total`, `stok_tersedia`, `ikon`, `deskripsi`) VALUES
(1, 'LTP', 'Laptop Lenovo ThinkPad E14', 1, 10, 8, 'laptop', 'Intel Core i5, RAM 16GB, SSD 512GB'),
(2, 'PRY', 'Proyektor Epson EB-E01', 1, 5, 3, 'proyektor', '3300 Lumens, HDMI & VGA, Resolusi XGA'),
(3, 'MSE', 'Mouse Logitech M331', 3, 15, 12, 'mouse', 'Silent wireless mouse USB');

INSERT INTO `peminjaman` (`id`, `kode_peminjaman`, `user_id`, `barang_id`, `jumlah`, `tgl_pinjam`, `tgl_kembali_rencana`, `tgl_kembali_aktual`, `status`, `alasan_pinjam`) VALUES
(1, 'PJM-026', 1, 1, 1, '2026-11-09', '2026-11-10', NULL, 'menunggu', 'Kebutuhan remote deployment'),
(2, 'PJM-023', 1, 2, 1, '2026-11-02', '2026-11-03', NULL, 'disetujui', 'Presentasi klien ruang meeting'),
(3, 'PJM-020', 1, 3, 1, '2026-10-28', '2026-10-29', NULL, 'ditolak', 'Stok sedang dalam inventarisir'),
(4, 'PJM-014', 1, 1, 1, '2026-10-30', '2026-11-06', NULL, 'disetujui', 'Tugas pengujian sistem'),
(5, 'PJM-010', 1, 1, 1, '2026-10-20', '2026-10-25', '2026-10-25', 'dikembalikan', 'Pekerjaan selesai tepat waktu');

INSERT INTO `notifikasi` (`id`, `user_id`, `peminjaman_id`, `judul`, `pesan`, `tipe`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'Peminjaman menunggu persetujuan', 'Laptop Lenovo ThinkPad E14 · PJM-026 · 1 unit · 9 Nov 2026 – 10 Nov 2026', 'menunggu', 0, '2026-11-07 10:15:00'),
(2, 1, 2, 'Peminjaman disetujui', 'Proyektor Epson EB-E01 · PJM-023 · 1 unit · 2 Nov 2026 – 3 Nov 2026', 'disetujui', 0, '2026-11-07 09:30:00');
