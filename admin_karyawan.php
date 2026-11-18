<?php
// admin_karyawan.php
require_once __DIR__ . '/includes/auth_check.php';
check_auth();

$user = get_current_user_data();

// Admin check
if (($user['role'] ?? '') !== 'admin' && ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['flash_error'] = 'Akses ditolak. Halaman ini hanya untuk Administrator.';
    header("Location: dashboard.php");
    exit;
}

$page_title = 'Kelola Karyawan';
$active_tab = 'karyawan';

// Helper to get columns of users table
function get_table_columns($pdo, $table) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM {$table}");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $stmt = $pdo->query("PRAGMA table_info({$table})");
        $cols = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cols[] = $row['name'];
        }
        return $cols;
    }
}

$user_cols = get_table_columns($pdo, 'users');

// Handle Form Submissions
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Tambah Akun Karyawan
    if ($action === 'add_user') {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $id_karyawan = trim($_POST['id_karyawan'] ?? '');
        $jabatan = trim($_POST['jabatan'] ?? 'Staff');
        $role = trim($_POST['role'] ?? 'user');
        $password = trim($_POST['password'] ?? '123456');

        if (empty($username) || empty($nama_lengkap)) {
            $_SESSION['flash_error'] = 'Username dan Nama Lengkap wajib diisi.';
            header("Location: admin_karyawan.php");
            exit;
        }

        if (empty($id_karyawan)) {
            $prefix = ($role === 'admin') ? 'ADM' : 'KRY';
            $id_karyawan = $prefix . '-' . rand(1000, 9999);
        }

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['flash_error'] = "Username '{$username}' sudah digunakan. Silakan gunakan username lain.";
            header("Location: admin_karyawan.php");
            exit;
        }

        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $initials = get_user_initials($nama_lengkap);

        // Dynamically build insert query based on existing columns
        $insert_data = [
            'username' => $username,
            'password' => $hashed_pass,
            'role'     => $role,
        ];

        if (in_array('email', $user_cols)) {
            $insert_data['email'] = $email ?: ($username . '@kantor.id');
        }
        if (in_array('name', $user_cols)) {
            $insert_data['name'] = $nama_lengkap;
        }
        if (in_array('nama_lengkap', $user_cols)) {
            $insert_data['nama_lengkap'] = $nama_lengkap;
        }
        if (in_array('employee_id', $user_cols)) {
            $insert_data['employee_id'] = $id_karyawan;
        }
        if (in_array('id_karyawan', $user_cols)) {
            $insert_data['id_karyawan'] = $id_karyawan;
        }
        if (in_array('jabatan', $user_cols)) {
            $insert_data['jabatan'] = $jabatan;
        }
        if (in_array('initials', $user_cols)) {
            $insert_data['initials'] = $initials;
        }

        $col_names = implode(', ', array_keys($insert_data));
        $placeholders = ':' . implode(', :', array_keys($insert_data));

        try {
            $sql = "INSERT INTO users ({$col_names}) VALUES ({$placeholders})";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($insert_data);
            $_SESSION['flash_success'] = "Akun karyawan '{$nama_lengkap}' berhasil dibuat!";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Gagal membuat akun: " . $e->getMessage();
        }

        header("Location: admin_karyawan.php");
        exit;
    }

    // 2. Edit Akun Karyawan
    if ($action === 'edit_user') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $id_karyawan = trim($_POST['id_karyawan'] ?? '');
        $jabatan = trim($_POST['jabatan'] ?? 'Staff');
        $role = trim($_POST['role'] ?? 'user');

        if ($target_id <= 0 || empty($nama_lengkap)) {
            $_SESSION['flash_error'] = 'Data tidak lengkap.';
            header("Location: admin_karyawan.php");
            exit;
        }

        $update_data = [
            ':id'   => $target_id,
            ':role' => $role
        ];
        $set_clauses = ['role = :role'];

        if (in_array('email', $user_cols)) {
            $set_clauses[] = 'email = :email';
            $update_data[':email'] = $email;
        }
        if (in_array('name', $user_cols)) {
            $set_clauses[] = 'name = :name';
            $update_data[':name'] = $nama_lengkap;
        }
        if (in_array('nama_lengkap', $user_cols)) {
            $set_clauses[] = 'nama_lengkap = :nl';
            $update_data[':nl'] = $nama_lengkap;
        }
        if (in_array('employee_id', $user_cols)) {
            $set_clauses[] = 'employee_id = :eid';
            $update_data[':eid'] = $id_karyawan;
        }
        if (in_array('id_karyawan', $user_cols)) {
            $set_clauses[] = 'id_karyawan = :ik';
            $update_data[':ik'] = $id_karyawan;
        }
        if (in_array('jabatan', $user_cols)) {
            $set_clauses[] = 'jabatan = :jbt';
            $update_data[':jbt'] = $jabatan;
        }

        try {
            $sql = "UPDATE users SET " . implode(', ', $set_clauses) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_data);
            $_SESSION['flash_success'] = "Data akun '{$nama_lengkap}' berhasil diperbarui!";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Gagal memperbarui: " . $e->getMessage();
        }

        header("Location: admin_karyawan.php");
        exit;
    }

    // 3. Reset Password
    if ($action === 'reset_password') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $new_pass = trim($_POST['new_password'] ?? '');

        if ($target_id <= 0 || strlen($new_pass) < 6) {
            $_SESSION['flash_error'] = 'Password baru minimal 6 karakter.';
            header("Location: admin_karyawan.php");
            exit;
        }

        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
        $stmt->execute([':p' => $hashed, ':id' => $target_id]);

        $_SESSION['flash_success'] = 'Password akun berhasil direset!';
        header("Location: admin_karyawan.php");
        exit;
    }

    // 4. Hapus Akun Karyawan
    if ($action === 'delete_user') {
        $target_id = (int)($_POST['user_id'] ?? 0);

        if ($target_id === (int)$user['id']) {
            $_SESSION['flash_error'] = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang login.';
            header("Location: admin_karyawan.php");
            exit;
        }

        // Delete user
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $target_id]);
            $_SESSION['flash_success'] = 'Akun karyawan berhasil dihapus.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Gagal menghapus: ' . $e->getMessage();
        }

        header("Location: admin_karyawan.php");
        exit;
    }
}

// Fetch all users
$stmt_all = $pdo->query("SELECT * FROM users ORDER BY id ASC");
$users_list = $stmt_all->fetchAll();

$total_users = count($users_list);
$admin_count = 0;
$karyawan_count = 0;
foreach ($users_list as $u) {
    if ($u['role'] === 'admin') {
        $admin_count++;
    } else {
        $karyawan_count++;
    }
}

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="px-5 pt-8 pb-6 flex-1 flex flex-col">
    
    <!-- Top Mode Admin Header with Notification Bell -->
    <div class="relative flex items-center justify-between mb-4">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-[#0a192f] text-white text-xs font-semibold shadow-sm">
            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-400"></i>
            <span>Mode admin</span>
        </span>
        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="openAddUserModal()" 
                class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold flex items-center gap-1 shadow-sm transition-all active:scale-95">
                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                <span>Tambah</span>
            </button>
            <button 
                type="button" 
                id="notificationBtn" 
                class="relative w-8 h-8 rounded-xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-center text-slate-700 hover:text-blue-600 transition-all"
                title="Notifikasi">
                <i data-lucide="bell" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        <?php require_once __DIR__ . '/includes/notification_modal.php'; ?>
    </div>

    <!-- Title & Subtitle -->
    <div class="mb-5">
        <h1 class="text-3xl font-extrabold text-[#0f172a] tracking-tight">Akun Karyawan</h1>
        <p class="text-xs text-slate-400 font-medium mt-1">Kelola data dan hak akses akun karyawan.</p>
    </div>

    <!-- Alert Messages with Auto-Dismiss -->
    <?php if ($flash_success): ?>
        <div id="flashSuccess" class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl flex items-center justify-between shadow-sm transition-all duration-500">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 text-emerald-600"></i>
                <span class="font-medium"><?= htmlspecialchars($flash_success) ?></span>
            </div>
            <button type="button" onclick="dismissFlashAlert('flashSuccess')" class="text-emerald-500 hover:text-emerald-800 ml-2">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
        <script>
            setTimeout(() => { dismissFlashAlert('flashSuccess'); }, 3000);
        </script>
    <?php endif; ?>

    <?php if ($flash_error): ?>
        <div id="flashError" class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-2xl flex items-center justify-between shadow-sm transition-all duration-500">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-rose-600"></i>
                <span class="font-medium"><?= htmlspecialchars($flash_error) ?></span>
            </div>
            <button type="button" onclick="dismissFlashAlert('flashError')" class="text-rose-500 hover:text-rose-800 ml-2">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
        <script>
            setTimeout(() => { dismissFlashAlert('flashError'); }, 4000);
        </script>
    <?php endif; ?>

    <!-- Summary Stats Bar -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-[11px] text-slate-400 font-medium">Total Akun</p>
                <p class="text-base font-extrabold text-slate-800"><?= $total_users ?> <span class="text-xs font-normal text-slate-400">Pengguna</span></p>
            </div>
        </div>

        <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-[11px] text-slate-400 font-medium">Karyawan Aktif</p>
                <p class="text-base font-extrabold text-slate-800"><?= $karyawan_count ?> <span class="text-xs font-normal text-slate-400">User</span></p>
            </div>
        </div>
    </div>

    <!-- Search Input Bar -->
    <div class="relative flex items-center rounded-2xl bg-white border border-slate-200 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 transition-all mb-4">
        <span class="pl-4 text-slate-400">
            <i data-lucide="search" class="w-4 h-4"></i>
        </span>
        <input 
            type="text" 
            id="searchUserInput" 
            onkeyup="searchUser(this.value)"
            placeholder="Cari nama, username, atau ID..." 
            class="w-full bg-transparent px-3 py-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none">
    </div>

    <!-- Employee Cards List -->
    <div id="usersContainer" class="space-y-3 flex-1">
        <?php foreach ($users_list as $u): ?>
            <?php
            $u_name = $u['nama_lengkap'] ?? $u['name'] ?? $u['username'] ?? 'Karyawan';
            $u_id = $u['id_karyawan'] ?? $u['employee_id'] ?? '-';
            $u_jabatan = $u['jabatan'] ?? ($u['role'] === 'admin' ? 'Administrator' : 'Staff');
            $u_initials = get_user_initials($u_name);
            $is_self = ((int)$u['id'] === (int)$user['id']);
            ?>
            <div 
                class="user-card bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm space-y-3"
                data-name="<?= htmlspecialchars($u_name) ?>"
                data-username="<?= htmlspecialchars($u['username']) ?>"
                data-id="<?= htmlspecialchars($u_id) ?>">
                
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Avatar -->
                        <?php if (!empty($u['foto']) && file_exists(__DIR__ . '/' . $u['foto'])): ?>
                            <img src="<?= htmlspecialchars($u['foto']) ?>" alt="Avatar" class="w-12 h-12 rounded-2xl object-cover border border-slate-200 shrink-0">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 font-extrabold text-sm flex items-center justify-center shrink-0 border border-blue-100">
                                <?= htmlspecialchars($u_initials) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <h3 class="font-bold text-slate-800 text-sm tracking-tight truncate"><?= htmlspecialchars($u_name) ?></h3>
                                <?php if ($is_self): ?>
                                    <span class="text-[9px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded">Anda</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-[11px] text-slate-400 font-medium">@<?= htmlspecialchars($u['username']) ?> · <?= htmlspecialchars($u_jabatan) ?></p>
                        </div>
                    </div>

                    <!-- Role Badge -->
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg shrink-0 <?= $u['role'] === 'admin' ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' ?>">
                        <?= strtoupper($u['role']) ?>
                    </span>
                </div>

                <!-- Metadata Row -->
                <div class="grid grid-cols-2 gap-2 text-[11px] pt-2 border-t border-slate-100 text-slate-500">
                    <div>
                        <span class="text-slate-400">ID Karyawan:</span>
                        <span class="font-semibold text-slate-700 ml-1"><?= htmlspecialchars($u_id) ?></span>
                    </div>
                    <div class="truncate">
                        <span class="text-slate-400">Email:</span>
                        <span class="font-medium text-slate-700 ml-1" title="<?= htmlspecialchars($u['email'] ?? '-') ?>"><?= htmlspecialchars($u['email'] ?? '-') ?></span>
                    </div>
                </div>

                <!-- Action Buttons: Edit, Reset Password, Hapus -->
                <div class="flex items-center justify-end gap-1.5 pt-1 border-t border-slate-50">
                    
                    <!-- Reset Pass -->
                    <button 
                        type="button" 
                        onclick="openResetPassModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u_name)) ?>')"
                        class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-colors flex items-center gap-1">
                        <i data-lucide="key" class="w-3.5 h-3.5"></i>
                        <span>Reset Pass</span>
                    </button>

                    <!-- Edit Data -->
                    <button 
                        type="button" 
                        onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u_name)) ?>', '<?= htmlspecialchars(addslashes($u['email'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($u_id)) ?>', '<?= htmlspecialchars(addslashes($u_jabatan)) ?>', '<?= htmlspecialchars(addslashes($u['role'])) ?>')"
                        class="px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-semibold transition-colors flex items-center gap-1">
                        <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                        <span>Edit</span>
                    </button>

                    <!-- Hapus (Proteksi: Tidak bisa hapus diri sendiri) -->
                    <?php if (!$is_self): ?>
                        <form action="admin_karyawan.php" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun karyawan <?= htmlspecialchars(addslashes($u_name)) ?>? Peringatan: data akun akan dihapus permanen.')">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-semibold transition-colors" title="Hapus Akun">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    <?php endif; ?>

                </div>

            </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- Modal: Tambah Akun Karyawan -->
<div id="addUserModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-[430px] bg-white rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl animate-slide-down max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Tambah Akun Karyawan</h3>
                <p class="text-xs text-slate-400">Registrasi akun baru untuk akses aplikasi</p>
            </div>
            <button type="button" onclick="closeAddUserModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form action="admin_karyawan.php" method="POST" class="space-y-3.5">
            <input type="hidden" name="action" value="add_user">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" placeholder="Contoh: Andi Wijaya" required class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Username Login</label>
                    <input type="text" name="username" placeholder="andiwijaya" required autocomplete="off" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500 lowercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ID Karyawan</label>
                    <div class="relative flex items-center">
                        <input type="text" name="id_karyawan" id="inputIdKaryawan" placeholder="KRY-8491" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500 uppercase pr-8">
                        <button type="button" onclick="generateRandomEmpId()" class="absolute right-2 text-blue-600 hover:text-blue-800" title="Acak ID">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan / Divisi</label>
                    <input type="text" name="jabatan" placeholder="Staff IT / Marketing" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Hak Akses (Role)</label>
                    <select name="role" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500 font-medium">
                        <option value="user">User (Peminjam)</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Kantor</label>
                <input type="email" name="email" placeholder="andi@kantor.id" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password Awal</label>
                <input type="text" name="password" value="123456" minlength="6" required class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500 font-mono">
                <p class="text-[10px] text-slate-400 mt-1">Default password: 123456 (karyawan dapat mengubahnya di profil).</p>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button" onclick="closeAddUserModal()" class="w-1/3 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Batal</button>
                <button type="submit" class="w-2/3 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white shadow-md shadow-blue-500/20 transition-all">Buat Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Akun Karyawan -->
<div id="editUserModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-[430px] bg-white rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl animate-slide-down">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Edit Data Karyawan</h3>
                <p class="text-xs text-slate-400">Perbarui profil dan wewenang akun</p>
            </div>
            <button type="button" onclick="closeEditUserModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form action="admin_karyawan.php" method="POST" class="space-y-3.5">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="editUserId">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="editNamaLengkap" required class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ID Karyawan</label>
                    <input type="text" name="id_karyawan" id="editIdKaryawan" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500 uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Hak Akses (Role)</label>
                    <select name="role" id="editRole" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500 font-medium">
                        <option value="user">User (Peminjam)</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan / Divisi</label>
                    <input type="text" name="jabatan" id="editJabatan" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" id="editEmail" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button" onclick="closeEditUserModal()" class="w-1/3 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Batal</button>
                <button type="submit" class="w-2/3 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white shadow-md transition-all">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Password Karyawan -->
<div id="resetPassModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-[430px] bg-white rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl animate-slide-down">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Reset Password Akun</h3>
                <p id="resetTargetName" class="text-xs text-blue-600 font-semibold mt-0.5"></p>
            </div>
            <button type="button" onclick="closeResetPassModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form action="admin_karyawan.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="resetUserId">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password Baru</label>
                <input type="text" name="new_password" value="123456" minlength="6" required class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:border-blue-500 font-mono">
                <p class="text-[10px] text-slate-400 mt-1">Anda bisa mengetik password baru atau tetap gunakan default <code>123456</code>.</p>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button" onclick="closeResetPassModal()" class="w-1/3 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Batal</button>
                <button type="submit" class="w-2/3 py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-xs font-semibold text-white shadow-md transition-all">Simpan Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function generateRandomEmpId() {
    const num = Math.floor(1000 + Math.random() * 9000);
    document.getElementById('inputIdKaryawan').value = 'KRY-' + num;
}

function openAddUserModal() {
    generateRandomEmpId();
    document.getElementById('addUserModal').classList.remove('hidden');
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
}

function openEditUserModal(id, name, email, empid, jabatan, role) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editNamaLengkap').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editIdKaryawan').value = empid;
    document.getElementById('editJabatan').value = jabatan;
    document.getElementById('editRole').value = role;
    document.getElementById('editUserModal').classList.remove('hidden');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

function openResetPassModal(id, name) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetTargetName').innerText = name;
    document.getElementById('resetPassModal').classList.remove('hidden');
}

function closeResetPassModal() {
    document.getElementById('resetPassModal').classList.add('hidden');
}

function searchUser(query) {
    const q = query.toLowerCase().trim();
    const cards = document.querySelectorAll('.user-card');
    cards.forEach(card => {
        const name = (card.getAttribute('data-name') || '').toLowerCase();
        const username = (card.getAttribute('data-username') || '').toLowerCase();
        const id = (card.getAttribute('data-id') || '').toLowerCase();
        if (name.includes(q) || username.includes(q) || id.includes(q)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<!-- Bottom Navigation -->
<?php require_once __DIR__ . '/includes/bottom_nav.php'; ?>

<!-- Script -->
<script src="assets/js/app.js"></script>
</body>
</html>
