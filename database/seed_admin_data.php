<?php
// database/seed_admin_data.php
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Check or add Sinta Maharani
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'sinta' LIMIT 1");
    $stmt->execute();
    $sinta_id = $stmt->fetchColumn();

    if (!$sinta_id) {
        $pass_hash = password_hash('123456', PASSWORD_DEFAULT);
        try {
            $ins = $pdo->prepare("
                INSERT INTO users (username, password, nama_lengkap, role, id_karyawan, email, jabatan)
                VALUES ('sinta', :p, 'Sinta Maharani', 'user', 'MKT-008', 'sinta@kantor.id', 'Marketing Specialist')
            ");
            $ins->execute([':p' => $pass_hash]);
            $sinta_id = $pdo->lastInsertId();
        } catch (Exception $e) {
            // Might be MySQL with `name` and `employee_id` columns
            $ins = $pdo->prepare("
                INSERT INTO users (username, password, name, role, employee_id, email)
                VALUES ('sinta', :p, 'Sinta Maharani', 'user', 'MKT-008', 'sinta@kantor.id')
            ");
            $ins->execute([':p' => $pass_hash]);
            $sinta_id = $pdo->lastInsertId();
        }
    }

    // 2. Ensure PJM-026 (Laptop Lenovo) for Bagas exists with status 'menunggu'
    $stmt = $pdo->prepare("SELECT id FROM peminjaman WHERE kode_peminjaman = 'PJM-026'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("
            INSERT INTO peminjaman (kode_peminjaman, user_id, barang_id, jumlah, tgl_pinjam, tgl_kembali_rencana, status, alasan_pinjam)
            VALUES ('PJM-026', 1, 1, 1, '2026-11-09', '2026-11-10', 'menunggu', 'Kebutuhan remote deployment')
        ");
        $ins->execute();
    } else {
        // Update to status 'menunggu' so it shows in approval
        $pdo->exec("UPDATE peminjaman SET status = 'menunggu' WHERE kode_peminjaman = 'PJM-026'");
    }

    // 3. Ensure PJM-025 (Proyektor Epson) for Sinta exists with status 'menunggu'
    $stmt = $pdo->prepare("SELECT id FROM peminjaman WHERE kode_peminjaman = 'PJM-025'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("
            INSERT INTO peminjaman (kode_peminjaman, user_id, barang_id, jumlah, tgl_pinjam, tgl_kembali_rencana, status, alasan_pinjam)
            VALUES ('PJM-025', :uid, 2, 1, '2026-11-09', '2026-11-09', 'menunggu', 'Presentasi klien ruang meeting A')
        ");
        $ins->execute([':uid' => $sinta_id ?: 1]);
    } else {
        $pdo->exec("UPDATE peminjaman SET status = 'menunggu' WHERE kode_peminjaman = 'PJM-025'");
    }

    echo "Admin seed completed successfully!\n";
} catch (Exception $e) {
    echo "Seed info: " . $e->getMessage() . "\n";
}
