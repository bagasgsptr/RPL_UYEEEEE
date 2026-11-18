<?php
// api/kembalikan.php
require_once __DIR__ . '/../includes/auth_check.php';
check_auth();

$user = get_current_user_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $peminjaman_id = (int)($_POST['peminjaman_id'] ?? 0);
    $kondisi = trim($_POST['kondisi'] ?? 'baik');
    $catatan = trim($_POST['catatan'] ?? '');

    // Check loan ownership
    $stmt = $pdo->prepare("
        SELECT p.*, b.nama_barang 
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        WHERE p.id = :pid AND p.user_id = :uid
    ");
    $stmt->execute([':pid' => $peminjaman_id, ':uid' => $user['id']]);
    $loan = $stmt->fetch();

    if (!$loan) {
        $_SESSION['flash_error'] = 'Data peminjaman tidak ditemukan.';
        header("Location: ../pengembalian.php");
        exit;
    }

    $tgl_kembali = date('Y-m-d');

    // Update loan status to 'dikembalikan'
    $stmt_upd = $pdo->prepare("
        UPDATE peminjaman 
        SET status = 'dikembalikan', tgl_kembali_aktual = :tgl_k
        WHERE id = :id
    ");
    $stmt_upd->execute([
        ':tgl_k' => $tgl_kembali,
        ':id'    => $peminjaman_id
    ]);

    // Restore stock
    $stmt_stk = $pdo->prepare("
        UPDATE barang 
        SET stok_tersedia = stok_tersedia + :jml 
        WHERE id = :bid
    ");
    $stmt_stk->execute([
        ':jml' => $loan['jumlah'],
        ':bid' => $loan['barang_id']
    ]);

    // Create notification
    $notif_pesan = "Pengembalian {$loan['nama_barang']} ({$loan['kode_peminjaman']}) berhasil diselesaikan pada " . format_tgl_pendek($tgl_kembali) . ". Terima kasih.";
    $stmt_notif = $pdo->prepare("
        INSERT INTO notifikasi (user_id, peminjaman_id, judul, pesan, tipe, is_read)
        VALUES (:uid, :pid, 'Barang telah dikembalikan', :pesan, 'disetujui', 0)
    ");
    $stmt_notif->execute([
        ':uid'   => $user['id'],
        ':pid'   => $peminjaman_id,
        ':pesan' => $notif_pesan
    ]);

    $_SESSION['flash_success'] = 'Barang berhasil dikembalikan ke inventaris sarpras!';
    header("Location: ../dashboard.php");
    exit;
}

header("Location: ../pengembalian.php");
exit;
