<?php
// profile.php
require_once __DIR__ . '/includes/auth_check.php';
check_auth();

$user = get_current_user_data();
$page_title = 'Profil';
$active_tab = 'profile';

// Ensure random employee ID if not set or default
if (empty($user['id_karyawan']) || $user['id_karyawan'] === '-' || in_array($user['id_karyawan'], ['IT-001', 'ADM-001'])) {
    $prefix = ($user['role'] === 'admin') ? 'ADM' : 'IT';
    $random_id = $prefix . '-' . rand(1000, 9999);
    $user['id_karyawan'] = $random_id;
    $user['employee_id'] = $random_id;

    // Update in database
    try {
        $stmt_upd = $pdo->prepare("UPDATE users SET id_karyawan = :rid WHERE id = :id");
        $stmt_upd->execute([':rid' => $random_id, ':id' => $user['id']]);
    } catch (Exception $e) {
        try {
            $stmt_upd = $pdo->prepare("UPDATE users SET employee_id = :rid WHERE id = :id");
            $stmt_upd->execute([':rid' => $random_id, ':id' => $user['id']]);
        } catch (Exception $e2) {}
    }
}

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$user_display_name = $user['nama_lengkap'] ?? $user['name'] ?? $user['username'] ?? 'Pengguna';
$user_id_card = $user['id_karyawan'] ?? $user['employee_id'] ?? ('IT-' . rand(1000, 9999));
$user_initials = get_user_initials($user_display_name);

require_once __DIR__ . '/includes/header.php';
?>

<div class="px-5 pt-8 pb-6 flex-1 flex flex-col">
    
    <!-- Top Header -->
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Profil saya</h1>
            <p class="text-xs text-slate-400 mt-1 font-medium">Informasi akun dan pengaturan kata sandi.</p>
        </div>
        <a href="#pengaturan" class="w-10 h-10 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-center text-slate-600 hover:text-blue-600 transition-colors">
            <i data-lucide="sliders" class="w-4 h-4"></i>
        </a>
    </div>

    <!-- Alert Messages with Auto-Dismiss (hilang otomatis setelah 3 detik) -->
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

    <!-- User Account Card (Information Akun - Simple tanpa baris 00) -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm mb-5">
        
        <!-- Header User & Avatar -->
        <div class="flex items-center gap-4 mb-4">
            <div class="relative cursor-pointer" onclick="document.getElementById('avatarInput').click();" title="Klik untuk ganti foto">
                <?php if (!empty($user['foto']) && file_exists(__DIR__ . '/' . $user['foto'])): ?>
                    <img src="<?= htmlspecialchars($user['foto']) ?>" alt="Avatar" class="w-16 h-16 rounded-full object-cover border-2 border-blue-500/30 shadow-sm">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full bg-blue-50 border-2 border-blue-200/60 flex items-center justify-center text-blue-600 font-extrabold text-lg shadow-sm">
                        <?= htmlspecialchars($user_initials) ?>
                    </div>
                <?php endif; ?>
                <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md hover:bg-blue-700 transition-colors">
                    <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <h2 class="text-base font-extrabold text-slate-800 tracking-tight truncate"><?= htmlspecialchars($user_display_name) ?></h2>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs font-semibold text-slate-500"><?= htmlspecialchars($user['jabatan'] ?? 'Staff IT') ?></span>
                    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded uppercase"><?= htmlspecialchars($user['role'] ?? 'user') ?></span>
                </div>
            </div>
        </div>

        <!-- Hidden Form for Avatar Upload -->
        <form id="avatarForm" action="api/update_profile.php" method="POST" enctype="multipart/form-data" class="hidden">
            <input type="hidden" name="action" value="update_avatar">
            <input type="file" name="foto" id="avatarInput" accept="image/*" onchange="document.getElementById('avatarForm').submit();">
        </form>

        <!-- Metadata Grid (ID Karyawan Random & Email Kantor) -->
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100 text-xs">
            <div>
                <p class="text-slate-400 text-[11px] font-medium">ID Karyawan / NIS</p>
                <p class="text-slate-800 font-bold mt-0.5"><?= htmlspecialchars($user_id_card) ?></p>
            </div>
            <div>
                <p class="text-slate-400 text-[11px] font-medium">Email kantor</p>
                <p class="text-slate-800 font-bold mt-0.5 truncate"><?= htmlspecialchars($user['email'] ?? '-') ?></p>
            </div>
        </div>

    </div>

    <!-- Section: Pengaturan Akun (Clean: Ganti Foto & Ganti Password) -->
    <div id="pengaturan" class="space-y-4 mb-6">
        <h3 class="text-sm font-bold text-slate-800">Pengaturan Akun</h3>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
            <!-- Admin: Kelola Karyawan Quick Action -->
            <a href="admin_karyawan.php" class="bg-white p-4 rounded-2xl border border-blue-200/80 shadow-sm flex items-center justify-between hover:bg-blue-50/50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-800">Kelola Akun Karyawan</h4>
                        <p class="text-[11px] text-slate-400">Tambah akun & reset kata sandi</p>
                    </div>
                </div>
                <div class="text-blue-600">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </div>
            </a>
        <?php endif; ?>

        <!-- Ganti Foto Card Clean -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <i data-lucide="image" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Ganti Foto Profil</h4>
                    <p class="text-[11px] text-slate-400">Pilih gambar JPG atau PNG</p>
                </div>
            </div>
            <button 
                type="button" 
                onclick="document.getElementById('avatarInput').click();" 
                class="px-4 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-sm">
                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                <span>Pilih Foto</span>
            </button>
        </div>

        <!-- Ganti Password Card Clean -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center gap-2.5 mb-3.5">
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="key-round" class="w-4 h-4"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Ganti Password</h4>
                    <p class="text-[10px] text-slate-400">Perbarui kata sandi login Anda</p>
                </div>
            </div>

            <form action="api/update_profile.php" method="POST" class="space-y-3">
                <input type="hidden" name="action" value="update_password">

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Password Lama</label>
                    <input 
                        type="password" 
                        name="old_password" 
                        placeholder="Masukan password saat ini" 
                        required 
                        class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Password Baru</label>
                    <input 
                        type="password" 
                        name="new_password" 
                        placeholder="Minimal 6 karakter" 
                        minlength="6"
                        required 
                        class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Konfirmasi Password Baru</label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        placeholder="Ulangi password baru" 
                        minlength="6"
                        required 
                        class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>

                <div class="pt-1">
                    <button 
                        type="submit" 
                        class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white shadow-sm transition-colors">
                        Simpan Password Baru
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Tombol Keluar (Logout) -->
    <div class="mt-auto pt-2">
        <a 
            href="logout.php" 
            onclick="return confirm('Apakah Anda yakin ingin keluar dari akun ini?')"
            class="w-full py-3.5 rounded-2xl bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 font-semibold text-xs flex items-center justify-center gap-2 shadow-sm transition-all active:scale-[0.99]">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            <span>Keluar</span>
        </a>
    </div>

</div>

<script>
function dismissFlashAlert(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px)';
        setTimeout(() => el.remove(), 400);
    }
}
</script>

<!-- Bottom Navigation -->
<?php require_once __DIR__ . '/includes/bottom_nav.php'; ?>

<!-- Script -->
<script src="assets/js/app.js"></script>
</body>
</html>
