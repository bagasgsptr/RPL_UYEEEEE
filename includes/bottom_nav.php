<?php
// includes/bottom_nav.php
$active_tab = $active_tab ?? 'dashboard';
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// If admin, check pending approvals count for badge
$pending_count = 0;
if ($is_admin && isset($pdo)) {
    try {
        $stmt_p = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'menunggu'");
        $pending_count = (int)$stmt_p->fetchColumn();
    } catch (Exception $e) {}
}
?>

<!-- Bottom Navigation Bar (Fixed within mobile frame) -->
<nav class="fixed bottom-0 left-0 right-0 z-40 flex justify-center pointer-events-none">
    <div class="w-full max-w-[430px] bg-white/95 backdrop-blur-md border-t border-slate-100 shadow-[0_-4px_20px_rgba(0,0,0,0.04)] px-3 py-2 flex items-center justify-around pointer-events-auto">
        
        <?php if ($is_admin): ?>
            <!-- ADMIN NAVIGATION TABS: [Dashboard, Persetujuan, Barang, Profil] -->

            <!-- Tab: Dashboard -->
            <a href="dashboard.php" class="relative flex flex-col items-center justify-center flex-1 py-1 transition-all <?= $active_tab === 'dashboard' ? 'text-blue-600 font-semibold' : 'text-slate-400 hover:text-slate-600' ?>">
                <?php if ($active_tab === 'dashboard'): ?>
                    <span class="absolute -top-2 w-8 h-1 bg-blue-600 rounded-full"></span>
                <?php endif; ?>
                <i data-lucide="layout-grid" class="w-5 h-5 mb-1 <?= $active_tab === 'dashboard' ? 'stroke-[2.3]' : 'stroke-[1.8]' ?>"></i>
                <span class="text-[11px]">Dashboard</span>
            </a>

            <!-- Tab: Persetujuan (Admin) -->
            <a href="persetujuan.php" class="relative flex flex-col items-center justify-center flex-1 py-1 transition-all <?= $active_tab === 'persetujuan' ? 'text-blue-600 font-semibold' : 'text-slate-400 hover:text-slate-600' ?>">
                <?php if ($active_tab === 'persetujuan'): ?>
                    <span class="absolute -top-2 w-8 h-1 bg-blue-600 rounded-full"></span>
                <?php endif; ?>
                <div class="relative">
                    <i data-lucide="check-square" class="w-5 h-5 mb-1 <?= $active_tab === 'persetujuan' ? 'stroke-[2.3]' : 'stroke-[1.8]' ?>"></i>
                    <?php if ($pending_count > 0 && $active_tab !== 'persetujuan'): ?>
                        <span class="absolute -top-1 -right-2 bg-blue-600 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center"><?= $pending_count ?></span>
                    <?php endif; ?>
                </div>
                <span class="text-[11px]">Persetujuan</span>
            </a>

            <!-- Tab: Barang (Admin) -->
            <a href="admin_barang.php" class="relative flex flex-col items-center justify-center flex-1 py-1 transition-all <?= $active_tab === 'barang' ? 'text-blue-600 font-semibold' : 'text-slate-400 hover:text-slate-600' ?>">
                <?php if ($active_tab === 'barang'): ?>
                    <span class="absolute -top-2 w-8 h-1 bg-blue-600 rounded-full"></span>
                <?php endif; ?>
                <i data-lucide="boxes" class="w-5 h-5 mb-1 <?= $active_tab === 'barang' ? 'stroke-[2.3]' : 'stroke-[1.8]' ?>"></i>
                <span class="text-[11px]">Barang</span>
            </a>

            <!-- Tab: Profil -->
            <a href="profile.php" class="relative flex flex-col items-center justify-center flex-1 py-1 transition-all <?= $active_tab === 'profile' ? 'text-blue-600 font-semibold' : 'text-slate-400 hover:text-slate-600' ?>">
                <?php if ($active_tab === 'profile'): ?>
                    <span class="absolute -top-2 w-8 h-1 bg-blue-600 rounded-full"></span>
                <?php endif; ?>
                <i data-lucide="user" class="w-5 h-5 mb-1 <?= $active_tab === 'profile' ? 'stroke-[2.3]' : 'stroke-[1.8]' ?>"></i>
                <span class="text-[11px]">Profil</span>
            </a>

        <?php else: ?>
            <!-- USER NAVIGATION TABS: [Dashboard, Peminjaman, Pengembalian, Profil] -->

            <!-- Tab: Dashboard -->
            <a href="dashboard.php" class="relative flex flex-col items-center justify-center flex-1 py-1 transition-all <?= $active_tab === 'dashboard' ? 'text-blue-600 font-semibold' : 'text-slate-400 hover:text-slate-600' ?>">
                <?php if ($active_tab === 'dashboard'): ?>
                    <span class="absolute -top-2 w-8 h-1 bg-blue-600 rounded-full"></span>
                <?php endif; ?>
                <i data-lucide="layout-grid" class="w-5 h-5 mb-1 <?= $active_tab === 'dashboard' ? 'stroke-[2.3]' : 'stroke-[1.8]' ?>"></i>
                <span class="text-[11px]">Dashboard</span>
            </a>

            <!-- Tab: Peminjaman -->
            <a href="peminjaman.php" class="relative flex flex-col items-center justify-center flex-1 py-1 transition-all <?= $active_tab === 'peminjaman' ? 'text-blue-600 font-semibold' : 'text-slate-400 hover:text-slate-600' ?>">
                <?php if ($active_tab === 'peminjaman'): ?>
                    <span class="absolute -top-2 w-8 h-1 bg-blue-600 rounded-full"></span>
                <?php endif; ?>
                <i data-lucide="package-plus" class="w-5 h-5 mb-1 <?= $active_tab === 'peminjaman' ? 'stroke-[2.3]' : 'stroke-[1.8]' ?>"></i>
                <span class="text-[11px]">Peminjaman</span>
            </a>

            <!-- Tab: Pengembalian -->
            <a href="pengembalian.php" class="relative flex flex-col items-center justify-center flex-1 py-1 transition-all <?= $active_tab === 'pengembalian' ? 'text-blue-600 font-semibold' : 'text-slate-400 hover:text-slate-600' ?>">
                <?php if ($active_tab === 'pengembalian'): ?>
                    <span class="absolute -top-2 w-8 h-1 bg-blue-600 rounded-full"></span>
                <?php endif; ?>
                <i data-lucide="package-check" class="w-5 h-5 mb-1 <?= $active_tab === 'pengembalian' ? 'stroke-[2.3]' : 'stroke-[1.8]' ?>"></i>
                <span class="text-[11px]">Pengembalian</span>
            </a>

            <!-- Tab: Profil -->
            <a href="profile.php" class="relative flex flex-col items-center justify-center flex-1 py-1 transition-all <?= $active_tab === 'profile' ? 'text-blue-600 font-semibold' : 'text-slate-400 hover:text-slate-600' ?>">
                <?php if ($active_tab === 'profile'): ?>
                    <span class="absolute -top-2 w-8 h-1 bg-blue-600 rounded-full"></span>
                <?php endif; ?>
                <i data-lucide="user" class="w-5 h-5 mb-1 <?= $active_tab === 'profile' ? 'stroke-[2.3]' : 'stroke-[1.8]' ?>"></i>
                <span class="text-[11px]">Profil</span>
            </a>
        <?php endif; ?>

    </div>
</nav>

<!-- Spacer for fixed bottom nav -->
<div class="h-20 w-full shrink-0"></div>
