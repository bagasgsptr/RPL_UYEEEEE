<?php
// persetujuan.php
require_once __DIR__ . '/includes/auth_check.php';
check_auth();

$user = get_current_user_data();

// Only admin can access this page
if (($user['role'] ?? '') !== 'admin' && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$page_title = 'Persetujuan';
$active_tab = 'persetujuan';

$current_tab = $_GET['tab'] ?? 'menunggu';

// Fetch pending loans (Menunggu) - ensure p.id is not overwritten by u.id!
$stmt_pending = $pdo->prepare("
    SELECT p.id as peminjaman_id, p.*,
           b.nama_barang, b.kode_barang, b.ikon, b.stok_tersedia,
           k.nama_kategori,
           u.*,
           p.id as id
    FROM peminjaman p
    JOIN barang b ON p.barang_id = b.id
    JOIN kategori k ON b.kategori_id = k.id
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'menunggu'
    ORDER BY p.id DESC
");
$stmt_pending->execute();
$pending_loans = $stmt_pending->fetchAll();

// Fetch history loans (Riwayat) - ensure p.id is not overwritten by u.id!
$stmt_history = $pdo->prepare("
    SELECT p.id as peminjaman_id, p.*,
           b.nama_barang, b.kode_barang, b.ikon,
           k.nama_kategori,
           u.*,
           p.id as id
    FROM peminjaman p
    JOIN barang b ON p.barang_id = b.id
    JOIN kategori k ON b.kategori_id = k.id
    JOIN users u ON p.user_id = u.id
    WHERE p.status != 'menunggu'
    ORDER BY p.id DESC
    LIMIT 20
");
$stmt_history->execute();
$history_loans = $stmt_history->fetchAll();

$pending_count = count($pending_loans);

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['last_action']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="px-5 pt-8 pb-6 flex-1 flex flex-col">
    
    <!-- Top Mode Admin Header with Notification Bell -->
    <div class="relative flex items-center justify-between mb-4">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-[#0a192f] text-white text-xs font-semibold shadow-sm">
            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-400"></i>
            <span>Mode admin</span>
        </span>
        
        <div class="flex items-center gap-2.5">
            <span class="text-xs text-slate-400 font-medium">Administrasi barang</span>

            <!-- Notification Bell Button -->
            <button 
                type="button" 
                id="notificationBtn" 
                class="relative w-9 h-9 rounded-xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-center text-slate-700 hover:text-blue-600 hover:border-blue-200 transition-all active:scale-95"
                title="Lonceng Notifikasi">
                <i data-lucide="bell" class="w-4 h-4"></i>
                
                <?php
                // Check unread count for current admin
                $stmt_unr = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = :uid AND is_read = 0");
                $stmt_unr->execute([':uid' => $user['id']]);
                $unread_admin_cnt = (int)$stmt_unr->fetchColumn();
                ?>
                <?php if ($unread_admin_cnt > 0): ?>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-blue-600 border-2 border-white rounded-full"></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Notification Dropdown Component -->
        <?php require_once __DIR__ . '/includes/notification_modal.php'; ?>
    </div>

    <!-- Title & Subtitle -->
    <div class="mb-5">
        <h1 class="text-3xl font-extrabold text-[#0f172a] tracking-tight">Persetujuan</h1>
        <p class="text-xs text-slate-400 font-medium mt-1">Tinjau pengajuan peminjaman karyawan.</p>
    </div>

    <!-- Flash Alert with Auto-Dismiss -->
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
            setTimeout(() => { dismissFlashAlert('flashSuccess'); }, 4000);
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

    <!-- Segmented Control Tabs (Menunggu & Riwayat persetujuan) -->
    <div class="bg-[#f0f4f9] p-1 rounded-2xl flex items-center mb-5">
        <a href="persetujuan.php?tab=menunggu" class="flex-1 py-2.5 text-center text-xs font-bold rounded-xl transition-all <?= $current_tab === 'menunggu' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">
            Menunggu (<?= $pending_count ?>)
        </a>
        <a href="persetujuan.php?tab=riwayat" class="flex-1 py-2.5 text-center text-xs font-bold rounded-xl transition-all <?= $current_tab === 'riwayat' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">
            Riwayat persetujuan
        </a>
    </div>

    <?php if ($current_tab === 'menunggu'): ?>

        <!-- List Pengajuan Menunggu Persetujuan -->
        <div class="space-y-4 flex-1">
            <?php if (empty($pending_loans)): ?>
                <div class="bg-white rounded-3xl p-8 text-center text-slate-400 border border-slate-100 shadow-sm">
                    <i data-lucide="check-circle" class="w-10 h-10 mx-auto mb-2 opacity-50 text-emerald-500"></i>
                    <p class="text-xs font-semibold text-slate-700">Semua pengajuan telah ditinjau</p>
                    <p class="text-[11px] text-slate-400 mt-1">Tidak ada permohonan peminjaman yang menunggu saat ini.</p>
                    <div class="mt-4">
                        <a href="persetujuan.php?tab=riwayat" class="text-xs font-semibold text-blue-600 hover:underline inline-flex items-center gap-1">
                            <span>Lihat Riwayat Persetujuan</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($pending_loans as $idx => $loan): ?>
                    <?php
                    $peminjam_nama = $loan['nama_lengkap'] ?? $loan['name'] ?? $loan['username'] ?? 'Karyawan';
                    $peminjam_id = $loan['id_karyawan'] ?? $loan['employee_id'] ?? 'IT-001';
                    $real_peminjaman_id = (int)$loan['peminjaman_id'];
                    ?>
                    <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm space-y-3.5 relative">
                        
                        <!-- Top Row: Kode & Date + Menunggu Badge -->
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono text-slate-500 font-semibold bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200/60">
                                <?= htmlspecialchars($loan['kode_peminjaman']) ?> · <?= format_tgl_pendek($loan['created_at']) ?>
                            </span>
                            <span class="bg-[#fef9c3] text-[#a16207] font-bold text-[11px] px-2.5 py-0.5 rounded-lg">
                                Menunggu
                            </span>
                        </div>

                        <!-- Content Row: Icon Box + Item Name + Subtitle -->
                        <div class="flex items-start gap-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <?php if ($loan['ikon'] === 'proyektor'): ?>
                                    <i data-lucide="projector" class="w-6 h-6"></i>
                                <?php elseif ($loan['ikon'] === 'mouse'): ?>
                                    <i data-lucide="mouse" class="w-6 h-6"></i>
                                <?php else: ?>
                                    <i data-lucide="laptop" class="w-6 h-6"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-slate-800 text-sm tracking-tight truncate"><?= htmlspecialchars($loan['nama_barang']) ?></h3>
                                <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                                    <?= htmlspecialchars($loan['kode_barang']) ?> · <?= htmlspecialchars($loan['nama_kategori']) ?> · <?= $loan['jumlah'] ?> unit
                                </p>
                            </div>
                        </div>

                        <!-- Requester Info Row -->
                        <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-100 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400 text-[11px]">Pemohon:</span>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($peminjam_nama) ?></span>
                            </div>
                            <span class="text-[11px] font-mono font-medium text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200/60"><?= htmlspecialchars($peminjam_id) ?></span>
                        </div>

                        <!-- Date Range Row -->
                        <div class="flex items-center gap-2 text-xs text-slate-600">
                            <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                            <span class="font-medium"><?= format_tgl_pendek($loan['tgl_pinjam']) ?> – <?= format_tgl_pendek($loan['tgl_kembali_rencana']) ?></span>
                        </div>

                        <?php if (!empty($loan['alasan_pinjam'])): ?>
                            <div class="text-[11px] bg-slate-50 p-2.5 rounded-xl text-slate-500">
                                <span class="font-semibold text-slate-700">Keperluan:</span> <?= htmlspecialchars($loan['alasan_pinjam']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons: Tolak (Left) & Setujui (Right) -->
                        <div class="flex items-center gap-3 pt-1">
                            
                            <!-- Tombol Tolak -->
                            <button 
                                type="button" 
                                onclick="openRejectModal(<?= $real_peminjaman_id ?>, '<?= htmlspecialchars(addslashes($loan['nama_barang'])) ?>', '<?= htmlspecialchars(addslashes($peminjam_nama)) ?>')"
                                class="flex-1 py-3 rounded-2xl bg-[#fee2e2]/60 hover:bg-[#fee2e2] active:scale-[0.99] text-[#ef4444] font-bold text-xs flex items-center justify-center gap-1.5 transition-all">
                                <i data-lucide="x" class="w-4 h-4 stroke-[2.5]"></i>
                                <span>Tolak</span>
                            </button>

                            <!-- Tombol Setujui -->
                            <form action="api/persetujuan.php" method="POST" class="flex-1">
                                <input type="hidden" name="action" value="setujui">
                                <input type="hidden" name="peminjaman_id" value="<?= $real_peminjaman_id ?>">
                                <button 
                                    type="submit" 
                                    class="w-full py-3 rounded-2xl bg-[#0084ff] hover:bg-blue-600 active:scale-[0.99] text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-md shadow-blue-500/25 transition-all">
                                    <i data-lucide="check" class="w-4 h-4 stroke-[2.5]"></i>
                                    <span>Setujui</span>
                                </button>
                            </form>

                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <!-- Tab Riwayat Persetujuan (Status Tetap, Tanpa Tombol) -->
        <div class="space-y-3.5 flex-1">
            <?php if (empty($history_loans)): ?>
                <div class="bg-white rounded-3xl p-8 text-center text-slate-400 border border-slate-100 shadow-sm">
                    <p class="text-xs">Belum ada riwayat persetujuan peminjaman.</p>
                </div>
            <?php else: ?>
                <?php foreach ($history_loans as $h): ?>
                    <?php
                    $peminjam_nama = $h['nama_lengkap'] ?? $h['name'] ?? $h['username'] ?? 'Karyawan';
                    ?>
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800 font-mono"><?= htmlspecialchars($h['kode_peminjaman']) ?></span>
                            <?php if ($h['status'] === 'disetujui'): ?>
                                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md flex items-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3 stroke-[2.5]"></i>
                                    <span>Disetujui</span>
                                </span>
                            <?php elseif ($h['status'] === 'ditolak'): ?>
                                <span class="text-[11px] font-bold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-md flex items-center gap-1">
                                    <i data-lucide="x" class="w-3 h-3 stroke-[2.5]"></i>
                                    <span>Ditolak</span>
                                </span>
                            <?php elseif ($h['status'] === 'dikembalikan'): ?>
                                <span class="text-[11px] font-bold text-teal-700 bg-teal-50 px-2.5 py-0.5 rounded-md">
                                    Dikembalikan
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-700 font-semibold"><?= htmlspecialchars($h['nama_barang']) ?> · <span class="text-slate-400 font-normal"><?= $h['jumlah'] ?> unit</span></p>
                        <p class="text-[11px] text-slate-500">Pemohon: <span class="text-slate-700 font-bold"><?= htmlspecialchars($peminjam_nama) ?></span> · <?= format_tgl_pendek($h['tgl_pinjam']) ?> s/d <?= format_tgl_pendek($h['tgl_kembali_rencana']) ?></p>
                        <?php if (!empty($h['alasan_tolak'])): ?>
                            <p class="text-[11px] text-rose-600 bg-rose-50/50 p-2 rounded-lg">Catatan Tolak: <?= htmlspecialchars($h['alasan_tolak']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<!-- Modal Tolak Pengajuan -->
<div id="rejectModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-[430px] bg-white rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl animate-slide-down">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-rose-600 text-base flex items-center gap-1.5">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                    <span>Tolak Pengajuan</span>
                </h3>
                <p id="rejectItemInfo" class="text-xs text-slate-500 mt-0.5"></p>
            </div>
            <button type="button" onclick="closeRejectModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form action="api/persetujuan.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="tolak">
            <input type="hidden" name="peminjaman_id" id="rejectPeminjamanId">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan</label>
                <textarea 
                    name="alasan_tolak" 
                    rows="3" 
                    placeholder="Contoh: Stok sedang dalam masa pemeliharaan / jadwal pemakaian bentrok..." 
                    required
                    class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:border-rose-500 placeholder-slate-400 resize-none"></textarea>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button 
                    type="button" 
                    onclick="closeRejectModal()" 
                    class="w-1/3 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="w-2/3 py-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-xs font-semibold text-white shadow-md shadow-rose-500/20 transition-all">
                    Kirim Penolakan
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function openRejectModal(loanId, itemName, requester) {
    document.getElementById('rejectPeminjamanId').value = loanId;
    document.getElementById('rejectItemInfo').innerText = `${itemName} (${requester})`;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>

<!-- Bottom Navigation -->
<?php require_once __DIR__ . '/includes/bottom_nav.php'; ?>

<!-- Script -->
<script src="assets/js/app.js"></script>
</body>
</html>
