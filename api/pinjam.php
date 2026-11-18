<?php
// api/pinjam.php
require_once __DIR__ . '/../includes/auth_check.php';
check_auth();

$user = get_current_user_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 1);
    $tgl_pinjam = trim($_POST['tgl_pinjam'] ?? date('Y-m-d'));
    $tgl_kembali_rencana = trim($_POST['tgl_kembali_rencana'] ?? date('Y-m-d', strtotime('+3 days')));
    $alasan_pinjam = trim($_POST['alasan_pinjam'] ?? '');

    // Validate barang
    $stmt_b = $pdo->prepare("SELECT * FROM barang WHERE id = :id");
    $stmt_b->execute([':id' => $barang_id]);
    $barang = $stmt_b->fetch();

    if (!$barang) {
        $_SESSION['flash_error'] = 'Barang yang dipilih tidak ditemukan.';
        header("Location: ../peminjaman.php");
        exit;
    }

    if ($jumlah <= 0 || $jumlah > (int)$barang['stok_tersedia']) {
        $_SESSION['flash_error'] = 'Jumlah pinjam melebihi batas stok tersedia (' . $barang['stok_tersedia'] . ' unit).';
        header("Location: ../peminjaman.php");
        exit;
    }

    // Generate unique loan code
    $kode_peminjaman = 'PJM-' . strtoupper(substr(uniqid(), -5));

    // Insert loan record (status = menunggu)
    $stmt_ins = $pdo->prepare("
        INSERT INTO peminjaman (kode_peminjaman, user_id, barang_id, jumlah, tgl_pinjam, tgl_kembali_rencana, status, alasan_pinjam)
        VALUES (:kode, :uid, :bid, :jml, :tgl_p, :tgl_k, 'menunggu', :alasan)
    ");
    $stmt_ins->execute([
        ':kode'   => $kode_peminjaman,
        ':uid'    => $user['id'],
        ':bid'    => $barang_id,
        ':jml'    => $jumlah,
        ':tgl_p'  => $tgl_pinjam,
        ':tgl_k'  => $tgl_kembali_rencana,
        ':alasan' => $alasan_pinjam
    ]);
    $peminjaman_id = $pdo->lastInsertId();

    $peminjam_nama = $user['nama_lengkap'] ?? $user['name'] ?? $user['username'] ?? 'Karyawan';

    // 1. Notifikasi untuk User Pemohon
    $notif_pesan_user = "{$barang['nama_barang']} · {$kode_peminjaman} · {$jumlah} unit · " . format_tgl_pendek($tgl_pinjam) . " – " . format_tgl_pendek($tgl_kembali_rencana);
    $stmt_notif = $pdo->prepare("
        INSERT INTO notifikasi (user_id, peminjaman_id, judul, pesan, tipe, is_read)
        VALUES (:uid, :pid, 'Peminjaman menunggu persetujuan', :pesan, 'menunggu', 0)
    ");
    $stmt_notif->execute([
        ':uid'   => $user['id'],
        ':pid'   => $peminjaman_id,
        ':pesan' => $notif_pesan_user
    ]);

    // 2. Notifikasi untuk Semua Akun Admin (agar lonceng admin berbunyi saat ada permintaan baru)
    try {
        $stmt_adm = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        $admin_ids = $stmt_adm->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admin_ids as $adm_id) {
            $notif_pesan_admin = "{$peminjam_nama} mengajukan pinjam {$barang['nama_barang']} ({$kode_peminjaman}) · {$jumlah} unit. Menunggu persetujuan Anda.";
            $stmt_notif_adm = $pdo->prepare("
                INSERT INTO notifikasi (user_id, peminjaman_id, judul, pesan, tipe, is_read)
                VALUES (:uid, :pid, 'Permintaan persetujuan baru', :pesan, 'menunggu', 0)
            ");
            $stmt_notif_adm->execute([
                ':uid'   => $adm_id,
                ':pid'   => $peminjaman_id,
                ':pesan' => $notif_pesan_admin
            ]);
        }
    } catch (Exception $e) {}

    $_SESSION['flash_success'] = 'Pengajuan peminjaman berhasil dikirim! Silakan cek status di menu Pengembalian atau lonceng notifikasi.';
    header("Location: ../pengembalian.php?tab=diajukan");
    exit;
}

header("Location: ../peminjaman.php");
exit;
