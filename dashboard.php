<?php
// dashboard.php
require_once __DIR__ . '/includes/auth_check.php';
check_auth();

$user = get_current_user_data();
$page_title = 'Dashboard';
$active_tab = 'dashboard';

// Fetch quick categories / items count
$stmt_laptop = $pdo->prepare("SELECT stok_tersedia FROM barang WHERE kode_barang = 'LTP' LIMIT 1");
$stmt_laptop->execute();
$laptop_stock = $stmt_laptop->fetchColumn() ?: 8;

$stmt_proyektor = $pdo->prepare("SELECT stok_tersedia FROM barang WHERE kode_barang = 'PRY' LIMIT 1");
$stmt_proyektor->execute();
$proyektor_stock = $stmt_proyektor->fetchColumn() ?: 3;

// Active borrowed items count
$stmt_active = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id = :uid AND status = 'disetujui' AND tgl_kembali_aktual IS NULL");
$stmt_active->execute([':uid' => $user['id']]);
$active_loans_count = $stmt_active->fetchColumn();

// Fetch loan history for this user
$stmt_history = $pdo->prepare("
    SELECT p.*, b.nama_barang, b.ikon, b.kode_barang 
    FROM peminjaman p
    JOIN barang b ON p.barang_id = b.id
    WHERE p.user_id = :uid
    ORDER BY p.id DESC
    LIMIT 5
");
$stmt_history->execute([':uid' => $user['id']]);
$history_items = $stmt_history->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="px-5 pt-8 pb-6 flex-1 flex flex-col">
    
    <!-- Admin Badge Bar if logged in as Admin -->
    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <div class="flex items-center justify-between mb-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-[#0a192f] text-white text-xs font-semibold shadow-sm">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-400"></i>
                <span>Mode admin</span>
            </span>
            <a href="admin_karyawan.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1.5 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-xl transition-colors">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                <span>Akun Karyawan</span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Top Header: Date, Dynamic User Greeting, and Notification Bell -->
    <div class="relative flex items-start justify-between mb-6">
        <div>
            <!-- Date -->
            <p class="text-xs text-slate-400 font-medium mb-1">
                <?= format_tgl_indo(date('Y-m-d')) ?>
            </p>
            <!-- User Name Greeting (as requested: nama yang login tertera di atas dashboard) -->
            <?php
            $nama_panggilan = 'Pengguna';
            if (!empty($user['nama_lengkap'])) {
                $nama_panggilan = explode(' ', trim($user['nama_lengkap']))[0];
            } elseif (!empty($user['name'])) {
                $nama_panggilan = explode(' ', trim($user['name']))[0];
            } elseif (!empty($user['username'])) {
                $nama_panggilan = ucfirst($user['username']);
            }
            ?>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight flex items-center gap-1.5">
                <span>Halo, <?= htmlspecialchars($nama_panggilan) ?></span>
                <span class="inline-block animate-wave text-xl">👋</span>
            </h1>
        </div>

        <!-- Notification Bell Button -->
        <div class="relative">
            <button 
                type="button" 
                id="notificationBtn" 
                class="w-11 h-11 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-center text-slate-700 hover:text-blue-600 hover:border-blue-200 transition-all active:scale-95"
                title="Notifikasi">
                <i data-lucide="bell" class="w-5 h-5"></i>
                
                <!-- Unread Red Indicator Dot -->
                <?php
                // Check unread count
                $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = :uid AND is_read = 0");
                $stmt_unread->execute([':uid' => $user['id']]);
                $unread_cnt = $stmt_unread->fetchColumn();
                ?>
                <?php if ($unread_cnt > 0): ?>
                    <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-blue-600 border-2 border-white rounded-full"></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Notification Dropdown Component -->
        <?php require_once __DIR__ . '/includes/notification_modal.php'; ?>
    </div>

    <!-- Loan Banner Card -->
    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <?php
        $stmt_pnd = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'menunggu'");
        $cnt_pnd = (int)$stmt_pnd->fetchColumn();
        ?>
        <div class="w-full bg-[#0a1b37] rounded-3xl p-5 text-white shadow-xl shadow-slate-900/10 mb-6 relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 rounded-full bg-blue-600/20 blur-xl pointer-events-none"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-xs text-blue-200/80 font-medium uppercase tracking-wider mb-1">Persetujuan Peminjaman</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-white tracking-tight">
                            <?= str_pad($cnt_pnd, 2, '0', STR_PAD_LEFT) ?>
                        </span>
                        <span class="text-xs text-blue-200 font-medium">Menunggu persetujuan</span>
                    </div>
               </div>
                <a href="persetujuan.php" class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-xs font-semibold text-white transition-all shadow-md flex items-center gap-1.5">
                    <span>Tinjau</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="w-full bg-[#0a1b37] rounded-3xl p-5 text-white shadow-xl shadow-slate-900/10 mb-6 relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 rounded-full bg-blue-600/20 blur-xl pointer-events-none"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-xs text-blue-200/80 font-medium uppercase tracking-wider mb-1">Aktivitas Anda</p>
                    <div class="flex items-baseline gap-2">
                        <?php if ($active_loans_count > 0): ?>
                            <span class="text-3xl font-extrabold text-white tracking-tight">
                                <?= str_pad($active_loans_count, 2, '0', STR_PAD_LEFT) ?>
                            </span>
                            <span class="text-xs text-blue-200 font-medium">Sedang dipinjam</span>
                        <?php else: ?>
                            <span class="text-sm font-semibold text-blue-100">Tidak ada pinjaman aktif</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="pengembalian.php" class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-semibold text-white transition-all backdrop-blur-sm flex items-center gap-1.5 border border-white/10">
                    <span>Kelola</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Section: Quick Actions / Categories -->
    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <!-- Section: Manajemen Admin -->
        <div class="mb-6">
            <h2 class="text-base font-bold text-slate-800 tracking-tight mb-3">Manajemen Sarpras</h2>
            
            <div class="grid grid-cols-2 gap-3.5">
                <!-- Kelola Barang -->
                <a href="admin_barang.php" class="bg-white p-4 rounded-2xl border border-slate-200/70 shadow-sm hover:shadow-md hover:border-blue-200 transition-all flex flex-col justify-between group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i data-lucide="boxes" class="w-5 h-5"></i>
                        </div>
                        <div class="text-slate-400 group-hover:text-blue-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform">
                            <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Data Barang</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Stok & inventaris</p>
                    </div>
                </a>

                <!-- Kelola Karyawan -->
                <a href="admin_karyawan.php" class="bg-white p-4 rounded-2xl border border-slate-200/70 shadow-sm hover:shadow-md hover:border-blue-200 transition-all flex flex-col justify-between group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="user-plus" class="w-5 h-5"></i>
                        </div>
                        <div class="text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform">
                            <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Akun Karyawan</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Tambah & atur akun</p>
                    </div>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="mb-6">
            <h2 class="text-base font-bold text-slate-800 tracking-tight mb-3">Butuh cepat?</h2>
            
            <div class="grid grid-cols-2 gap-3.5">
                <!-- Laptop Card -->
                <a href="peminjaman.php?kategori=elektronik" class="bg-white p-4 rounded-2xl border border-slate-200/70 shadow-sm hover:shadow-md hover:border-blue-200 transition-all flex flex-col justify-between group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i data-lucide="laptop" class="w-5 h-5"></i>
                        </div>
                        <div class="text-slate-400 group-hover:text-blue-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform">
                            <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Laptop</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium"><?= $laptop_stock ?> unit tersedia</p>
                    </div>
                </a>

                <!-- Proyektor Card -->
                <a href="peminjaman.php?kategori=elektronik" class="bg-white p-4 rounded-2xl border border-slate-200/70 shadow-sm hover:shadow-md hover:border-blue-200 transition-all flex flex-col justify-between group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i data-lucide="projector" class="w-5 h-5"></i>
                        </div>
                        <div class="text-slate-400 group-hover:text-blue-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform">
                            <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Proyektor</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5 font-medium"><?= $proyektor_stock ?> unit tersedia</p>
                    </div>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Section: Histori Peminjaman -->
    <div class="flex-1">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-bold text-slate-800 tracking-tight">Histori peminjaman</h2>
            <a href="peminjaman.php" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Lihat semua</a>
        </div>

        <div class="space-y-2.5">
            <?php if (empty($history_items)): ?>
                <div class="bg-white rounded-2xl p-6 text-center text-slate-400 border border-slate-100">
                    <p class="text-xs">Belum ada riwayat peminjaman.</p>
                </div>
            <?php else: ?>
                <?php foreach ($history_items as $item): ?>
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200/70 shadow-sm flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Icon Box -->
                            <div class="w-11 h-11 rounded-xl bg-blue-50/70 text-blue-600 flex items-center justify-center shrink-0">
                                <?php if ($item['ikon'] === 'proyektor'): ?>
                                    <i data-lucide="projector" class="w-5 h-5"></i>
                                <?php elseif ($item['ikon'] === 'mouse'): ?>
                                    <i data-lucide="mouse" class="w-5 h-5"></i>
                                <?php else: ?>
                                    <i data-lucide="laptop" class="w-5 h-5"></i>
                                <?php endif; ?>
                            </div>

                            <!-- Title & Dates -->
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-800 truncate"><?= htmlspecialchars($item['nama_barang']) ?></h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    <?php if ($item['status'] === 'dikembalikan' && $item['tgl_kembali_aktual']): ?>
                                        Dikembalikan <?= format_tgl_pendek($item['tgl_kembali_aktual']) ?>
                                    <?php else: ?>
                                        <?= format_tgl_pendek($item['tgl_pinjam']) ?> – <?= format_tgl_pendek($item['tgl_kembali_rencana']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Status Badge matching mockup -->
                        <div class="shrink-0">
                            <?php if ($item['status'] === 'disetujui'): ?>
                                <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
                                    Disetujui
                                </span>
                            <?php elseif ($item['status'] === 'ditolak'): ?>
                                <span class="text-[11px] font-semibold text-rose-700 bg-rose-50 px-2.5 py-1 rounded-lg">
                                    Ditolak
                                </span>
                            <?php elseif ($item['status'] === 'dikembalikan'): ?>
                                <span class="text-[11px] font-semibold text-emerald-800 bg-[#e6f4ea] px-2.5 py-1 rounded-lg">
                                    Dikembalikan
                                </span>
                            <?php else: ?>
                                <span class="text-[11px] font-semibold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg">
                                    Menunggu
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Bottom Navigation -->
<?php require_once __DIR__ . '/includes/bottom_nav.php'; ?>

<!-- Script -->
<script src="assets/js/app.js"></script>
</body>
</html>
