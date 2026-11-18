<?php
// database/seed_sqlite.php

function seed_sqlite_database($pdo) {
    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            nama_lengkap TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'user',
            id_karyawan TEXT NOT NULL,
            email TEXT NOT NULL,
            jabatan TEXT DEFAULT 'Staff',
            foto TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS kategori (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama_kategori TEXT NOT NULL,
            slug TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS barang (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kode_barang TEXT NOT NULL UNIQUE,
            nama_barang TEXT NOT NULL,
            kategori_id INTEGER NOT NULL,
            stok_total INTEGER NOT NULL DEFAULT 1,
            stok_tersedia INTEGER NOT NULL DEFAULT 1,
            ikon TEXT DEFAULT 'laptop',
            deskripsi TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (kategori_id) REFERENCES kategori(id)
        );

        CREATE TABLE IF NOT EXISTS peminjaman (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kode_peminjaman TEXT NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            barang_id INTEGER NOT NULL,
            jumlah INTEGER NOT NULL DEFAULT 1,
            tgl_pinjam TEXT NOT NULL,
            tgl_kembali_rencana TEXT NOT NULL,
            tgl_kembali_aktual TEXT DEFAULT NULL,
            status TEXT NOT NULL DEFAULT 'menunggu',
            alasan_pinjam TEXT DEFAULT NULL,
            alasan_tolak TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (barang_id) REFERENCES barang(id)
        );

        CREATE TABLE IF NOT EXISTS notifikasi (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            peminjaman_id INTEGER DEFAULT NULL,
            judul TEXT NOT NULL,
            pesan TEXT NOT NULL,
            tipe TEXT NOT NULL DEFAULT 'info',
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ");

    // Default password hash for '123456' and 'password'
    $hashed_pass = password_hash('123456', PASSWORD_DEFAULT);

    // Insert categories
    $pdo->exec("
        INSERT INTO kategori (id, nama_kategori, slug) VALUES
        (1, 'Elektronik', 'elektronik'),
        (2, 'Alat kantor', 'alat-kantor'),
        (3, 'Aksesori', 'aksesori');
    ");

    // Insert users
    $stmt = $pdo->prepare("
        INSERT INTO users (id, username, password, nama_lengkap, role, id_karyawan, email, jabatan, foto) VALUES
        (1, 'bagas', :pass, 'Bagas Gusti Saputro', 'user', 'IT-001', 'bagas@kantor.id', 'Staff IT', NULL),
        (2, 'admin', :pass, 'Administrator Utama', 'admin', 'ADM-001', 'admin@kantor.id', 'Kepala Sarpras & IT', NULL);
    ");
    $stmt->execute([':pass' => $hashed_pass]);

    // Insert barang
    $pdo->exec("
        INSERT INTO barang (id, kode_barang, nama_barang, kategori_id, stok_total, stok_tersedia, ikon, deskripsi) VALUES
        (1, 'LTP', 'Laptop Lenovo ThinkPad E14', 1, 10, 8, 'laptop', 'Intel Core i5, RAM 16GB, SSD 512GB'),
        (2, 'PRY', 'Proyektor Epson EB-E01', 1, 5, 3, 'proyektor', '3300 Lumens, HDMI & VGA, Resolusi XGA'),
        (3, 'MSE', 'Mouse Logitech M331', 3, 15, 12, 'mouse', 'Silent wireless mouse USB');
    ");

    // Insert peminjaman
    $pdo->exec("
        INSERT INTO peminjaman (id, kode_peminjaman, user_id, barang_id, jumlah, tgl_pinjam, tgl_kembali_rencana, tgl_kembali_aktual, status, alasan_pinjam) VALUES
        (1, 'PJM-026', 1, 1, 1, '2026-11-09', '2026-11-10', NULL, 'menunggu', 'Kebutuhan remote deployment'),
        (2, 'PJM-023', 1, 2, 1, '2026-11-02', '2026-11-03', NULL, 'disetujui', 'Presentasi klien ruang meeting'),
        (3, 'PJM-020', 1, 3, 1, '2026-10-28', '2026-10-29', NULL, 'ditolak', 'Stok sedang dalam inventarisir'),
        (4, 'PJM-014', 1, 1, 1, '2026-10-30', '2026-11-06', NULL, 'disetujui', 'Tugas pengujian sistem'),
        (5, 'PJM-010', 1, 1, 1, '2026-10-20', '2026-10-25', '2026-10-25', 'dikembalikan', 'Pekerjaan selesai tepat waktu');
    ");

    // Insert notifications
    $pdo->exec("
        INSERT INTO notifikasi (id, user_id, peminjaman_id, judul, pesan, tipe, is_read, created_at) VALUES
        (1, 1, 1, 'Peminjaman menunggu persetujuan', 'Laptop Lenovo ThinkPad E14 · PJM-026 · 1 unit · 9 Nov 2026 – 10 Nov 2026', 'menunggu', 0, datetime('now', '-2 hours')),
        (2, 1, 2, 'Peminjaman disetujui', 'Proyektor Epson EB-E01 · PJM-023 · 1 unit · 2 Nov 2026 – 3 Nov 2026', 'disetujui', 0, datetime('now', '-5 hours'));
    ");
}
