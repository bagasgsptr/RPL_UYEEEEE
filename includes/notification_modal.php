<?php
// includes/notification_modal.php
$notif_user_id = (int)($_SESSION['user_id'] ?? 1);
$is_admin_mode = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Setiap pengguna (admin maupun karyawan) menerima notifikasi sesuai user_id masing-masing
$stmt_notif = $pdo->prepare("
    SELECT * FROM notifikasi 
    WHERE user_id = :uid 
    ORDER BY id DESC 
    LIMIT 15
");
$stmt_notif->execute([':uid' => $notif_user_id]);
$all_notifs = $stmt_notif->fetchAll();

$unread_count = 0;
foreach ($all_notifs as $n) {
    if ((int)$n['is_read'] === 0) {
        $unread_count++;
    }
}
?>

<!-- Notification Dropdown / Modal Container -->
<div id="notificationDropdown" class="hidden absolute right-4 top-16 w-[340px] max-w-[90vw] bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden transform transition-all duration-200 origin-top-right">
    
    <!-- Header -->
    <div class="px-4 py-3 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h3 class="font-bold text-slate-800 text-sm">Notifikasi</h3>
            <?php if ($unread_count > 0): ?>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full"><?= $unread_count ?> baru</span>
            <?php endif; ?>
        </div>
        <?php if ($unread_count > 0): ?>
            <button onclick="markAllNotificationsRead()" class="text-xs text-slate-500 hover:text-blue-600 transition-colors">
                Tandai dibaca
            </button>
        <?php endif; ?>
    </div>

    <!-- Notification List -->
    <div class="max-h-[380px] overflow-y-auto divide-y divide-slate-100">
        <?php if (empty($all_notifs)): ?>
            <div class="p-6 text-center text-slate-400">
                <i data-lucide="bell-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                <p class="text-xs">Belum ada notifikasi saat ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($all_notifs as $nt): ?>
                <?php
                $target_url = $is_admin_mode ? 'persetujuan.php' : 'pengembalian.php';
                if ($nt['tipe'] === 'menunggu' && $is_admin_mode) {
                    $target_url = 'persetujuan.php?tab=menunggu';
                } elseif ($nt['tipe'] === 'disetujui' && $is_admin_mode) {
                    $target_url = 'persetujuan.php?tab=riwayat';
                }
                ?>
                <a href="<?= $target_url ?>" class="p-3.5 hover:bg-slate-50/80 transition-colors flex items-start gap-3 relative block <?= (int)$nt['is_read'] === 0 ? 'bg-blue-50/20' : '' ?>">
                    
                    <!-- Icon Box -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5
                        <?php 
                            if ($nt['tipe'] === 'disetujui') echo 'bg-emerald-50 text-emerald-600';
                            elseif ($nt['tipe'] === 'menunggu') echo 'bg-blue-50 text-blue-600';
                            elseif ($nt['tipe'] === 'ditolak') echo 'bg-rose-50 text-rose-600';
                            else echo 'bg-amber-50 text-amber-600';
                        ?>">
                        <?php if ($nt['tipe'] === 'disetujui'): ?>
                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        <?php elseif ($nt['tipe'] === 'menunggu'): ?>
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        <?php elseif ($nt['tipe'] === 'ditolak'): ?>
                            <i data-lucide="x-circle" class="w-5 h-5"></i>
                        <?php else: ?>
                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-0.5">
                            <h4 class="text-xs font-bold text-slate-800 truncate"><?= htmlspecialchars($nt['judul']) ?></h4>
                            <?php if ((int)$nt['is_read'] === 0): ?>
                                <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0 ml-1"></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-snug line-clamp-2 mb-1.5"><?= htmlspecialchars($nt['pesan']) ?></p>
                        
                        <!-- Status Badge -->
                        <?php if ($nt['tipe'] === 'disetujui'): ?>
                            <span class="inline-block text-[10px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-md">
                                Disetujui
                            </span>
                        <?php elseif ($nt['tipe'] === 'menunggu'): ?>
                            <span class="inline-block text-[10px] font-medium text-amber-700 bg-amber-50 border border-amber-200/60 px-2 py-0.5 rounded-md">
                                Menunggu Persetujuan
                            </span>
                        <?php elseif ($nt['tipe'] === 'ditolak'): ?>
                            <span class="inline-block text-[10px] font-medium text-rose-700 bg-rose-50 border border-rose-200/60 px-2 py-0.5 rounded-md">
                                Ditolak
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Chevron -->
                    <div class="text-slate-300 shrink-0 self-center">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </div>

                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Backdrop for closing dropdown when clicking outside -->
<div id="notificationBackdrop" class="hidden fixed inset-0 z-40 bg-black/10 backdrop-blur-[1px]"></div>
