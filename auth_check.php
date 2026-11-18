<?php
// includes/auth_check.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function get_current_user_data() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            // Normalize field aliases so both MySQL structures ('name' or 'nama_lengkap') work without error
            if (empty($user['nama_lengkap']) && !empty($user['name'])) {
                $user['nama_lengkap'] = $user['name'];
            }
            if (empty($user['nama_lengkap']) && !empty($_SESSION['nama_lengkap'])) {
                $user['nama_lengkap'] = $_SESSION['nama_lengkap'];
            }
            if (empty($user['nama_lengkap'])) {
                $user['nama_lengkap'] = $user['username'] ?? 'Pengguna';
            }

            if (empty($user['id_karyawan']) && !empty($user['employee_id'])) {
                $user['id_karyawan'] = $user['employee_id'];
            }
            if (empty($user['id_karyawan'])) {
                $user['id_karyawan'] = '-';
            }

            if (!isset($user['jabatan'])) {
                $user['jabatan'] = 'Staff';
            }

            return $user;
        }
    } catch (Exception $e) {
        // Fallback to session
    }

    return [
        'id' => $_SESSION['user_id'] ?? 1,
        'username' => $_SESSION['username'] ?? 'user',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Pengguna',
        'role' => $_SESSION['role'] ?? 'user',
        'id_karyawan' => '-',
        'email' => '-',
        'jabatan' => 'Staff',
        'foto' => null
    ];
}

function get_user_initials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        if (!empty($w)) {
            $initials .= strtoupper(mb_substr($w, 0, 1));
        }
    }
    return substr($initials, 0, 3) ?: 'U';
}

function format_tgl_indo($date_str) {
    if (!$date_str) return '-';
    $timestamp = strtotime($date_str);
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $hari_nama = $hari[date('w', $timestamp)];
    $tgl = date('j', $timestamp);
    $bln_nama = $bulan[(int)date('n', $timestamp)];
    $thn = date('Y', $timestamp);

    return "{$hari_nama}, {$tgl} {$bln_nama} {$thn}";
}

function format_tgl_pendek($date_str) {
    if (!$date_str) return '-';
    $timestamp = strtotime($date_str);
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $tgl = date('j', $timestamp);
    $bln_nama = $bulan[(int)date('n', $timestamp)];
    $thn = date('Y', $timestamp);

    return "{$tgl} {$bln_nama} {$thn}";
}

function get_sisa_hari_info($date_str) {
    if (!$date_str) {
        return [
            'days' => 0,
            'badge_text' => '-',
            'badge_class' => 'bg-slate-100 text-slate-600',
            'status' => 'unknown'
        ];
    }

    try {
        $today = new DateTime(date('Y-m-d'));
        $target = new DateTime(date('Y-m-d', strtotime($date_str)));
        $diff = $today->diff($target);
        $days = (int)$diff->format('%r%a');

        if ($days < 0) {
            $late = abs($days);
            return [
                'days' => $days,
                'badge_text' => "Terlambat {$late} hari",
                'badge_class' => 'bg-rose-100 text-rose-800 font-bold',
                'status' => 'overdue'
            ];
        } elseif ($days === 0) {
            return [
                'days' => 0,
                'badge_text' => "Jatuh tempo hari ini",
                'badge_class' => 'bg-amber-100 text-amber-900 font-bold',
                'status' => 'today'
            ];
        } elseif ($days === 1) {
            return [
                'days' => 1,
                'badge_text' => "Jatuh tempo besok",
                'badge_class' => 'bg-[#fef08a] text-yellow-900 font-bold',
                'status' => 'tomorrow'
            ];
        } elseif ($days <= 3) {
            return [
                'days' => $days,
                'badge_text' => "{$days} hari lagi",
                'badge_class' => 'bg-amber-50 text-amber-800 font-bold',
                'status' => 'soon'
            ];
        } else {
            return [
                'days' => $days,
                'badge_text' => "{$days} hari lagi",
                'badge_class' => 'bg-[#e0f2fe] text-blue-700 font-bold',
                'status' => 'safe'
            ];
        }
    } catch (Exception $e) {
        return [
            'days' => 0,
            'badge_text' => '-',
            'badge_class' => 'bg-slate-100 text-slate-600',
            'status' => 'error'
        ];
    }
}
