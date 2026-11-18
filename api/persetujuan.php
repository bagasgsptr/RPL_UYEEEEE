<?php
// api/persetujuan.php
require_once __DIR__ . '/../includes/auth_check.php';
check_auth();

$user = get_current_user_data();

// Admin check
if (($user['role'] ?? '') !== 'admin' && ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['flash_error'] = 'Akses ditolak. Tindakan ini hanya untuk Administrator.';
    header("Location: ../dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $peminjaman_id = (int)($_POST['peminjaman_id'] ?? 0);

    // Fetch loan details along with borrower's name (ensuring p.id is not overwritten by u.id)
    $stmt = $pdo->prepare("
        SELECT p.id as peminjaman_id, p.*, 
               b.nama_barang, b.id as barang_id, b.stok_tersedia,
               u.*,
               p.id as id
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        JOIN users u ON p.user_id = u.id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $peminjaman_id]);
    $loan = $stmt->fetch();

    if (!$loan) {
        $_SESSION['flash_error'] = 'Data peminjaman tidak ditemukan.';
        header("Location: ../persetujuan.php");
        exit;
    }

    $borrower_name = $loan['nama_lengkap'] ?? $loan['name'] ?? $loan['username'] ?? 'Karyawan';

    // 1. Action: Setujui
    if ($action === 'setujui') {
        // Check stock availability
        if ((int)$loan['stok_tersedia'] < (int)$loan['jumlah']) {
            $_SESSION['flash_error'] = "Stok barang {$loan['nama_barang']} tidak mencukupi untuk disetujui.";
            header("Location: ../persetujuan.php");
            exit;
        }

        // Update status to disetujui
        $stmt_upd = $pdo->prepare("UPDATE peminjaman SET status = 'disetujui' WHERE id = :id");
        $stmt_upd->execute([':id' => $peminjaman_id]);

        // Decrement stock
        $stmt_stk = $pdo->prepare("UPDATE barang SET stok_tersedia = stok_tersedia - :jml WHERE id = :bid");
        $stmt_stk->execute([':jml' => $loan['jumlah'], ':bid' => $loan['barang_id']]);

        // A. Notifikasi untuk User Pemohon (masuk ke lonceng peminjam)
        $notif_pesan_user = "Permohonan pinjam {$loan['nama_barang']} ({$loan['kode_peminjaman']}) · {$loan['jumlah']} unit telah disetujui oleh admin.";
        $stmt_notif = $pdo->prepare("
            INSERT INTO notifikasi (user_id, peminjaman_id, judul, pesan, tipe, is_read)
            VALUES (:uid, :pid, 'Peminjaman disetujui', :pesan, 'disetujui', 0)
        ");
        $stmt_notif->execute([
            ':uid'   => $loan['user_id'],
            ':pid'   => $peminjaman_id,
            ':pesan' => $notif_pesan_user
        ]);

        // B. Notifikasi untuk Admin (masuk ke lonceng admin bahwa persetujuan telah berhasil)
        $notif_pesan_admin = "Peminjaman {$loan['kode_peminjaman']} ({$loan['nama_barang']}) untuk {$borrower_name} telah berhasil disetujui.";
        $stmt_notif_adm = $pdo->prepare("
            INSERT INTO notifikasi (user_id, peminjaman_id, judul, pesan, tipe, is_read)
            VALUES (:uid, :pid, 'Persetujuan berhasil', :pesan, 'disetujui', 0)
        ");
        $stmt_notif_adm->execute([
            ':uid'   => $user['id'],
            ':pid'   => $peminjaman_id,
            ':pesan' => $notif_pesan_admin
        ]);

        // Simpan aksi terakhir untuk kartu konfirmasi jelas di UI
        $_SESSION['last_action'] = [
            'code'      => $loan['kode_peminjaman'],
            'item'      => $loan['nama_barang'],
            'requester' => $borrower_name,
            'status'    => 'disetujui'
        ];

        $_SESSION['flash_success'] = "Peminjaman {$loan['kode_peminjaman']} ({$loan['nama_barang']} - {$borrower_name}) berhasil disetujui!";
        header("Location: ../persetujuan.php");
        exit;
    }

    // 2. Action: Tolak
    if ($action === 'tolak') {
        $alasan_tolak = trim($_POST['alasan_tolak'] ?? 'Tidak memenuhi syarat peminjaman');

        // Update status to ditolak
        $stmt_upd = $pdo->prepare("UPDATE peminjaman SET status = 'ditolak', alasan_tolak = :alasan WHERE id = :id");
        $stmt_upd->execute([':alasan' => $alasan_tolak, ':id' => $peminjaman_id]);

        // A. Notifikasi untuk User Pemohon
        $notif_pesan_user = "Permohonan pinjam {$loan['nama_barang']} ({$loan['kode_peminjaman']}) ditolak. Alasan: {$alasan_tolak}";
        $stmt_notif = $pdo->prepare("
            INSERT INTO notifikasi (user_id, peminjaman_id, judul, pesan, tipe, is_read)
            VALUES (:uid, :pid, 'Peminjaman ditolak', :pesan, 'ditolak', 0)
        ");
        $stmt_notif->execute([
            ':uid'   => $loan['user_id'],
            ':pid'   => $peminjaman_id,
            ':pesan' => $notif_pesan_user
        ]);

        // B. Notifikasi untuk Admin
        $notif_pesan_admin = "Pengajuan {$loan['kode_peminjaman']} ({$loan['nama_barang']} - {$borrower_name}) telah Anda tolak.";
        $stmt_notif_adm = $pdo->prepare("
            INSERT INTO notifikasi (user_id, peminjaman_id, judul, pesan, tipe, is_read)
            VALUES (:uid, :pid, 'Peminjaman ditolak', :pesan, 'ditolak', 0)
        ");
        $stmt_notif_adm->execute([
            ':uid'   => $user['id'],
            ':pid'   => $peminjaman_id,
            ':pesan' => $notif_pesan_admin
        ]);

        // Simpan aksi terakhir
        $_SESSION['last_action'] = [
            'code'      => $loan['kode_peminjaman'],
            'item'      => $loan['nama_barang'],
            'requester' => $borrower_name,
            'status'    => 'ditolak'
        ];

        $_SESSION['flash_success'] = "Pengajuan peminjaman {$loan['kode_peminjaman']} ({$loan['nama_barang']} - {$borrower_name}) telah ditolak.";
        header("Location: ../persetujuan.php");
        exit;
    }
}

header("Location: ../persetujuan.php");
exit;
