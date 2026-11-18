<?php
// pengembalian.php
require_once __DIR__ . '/includes/auth_check.php';
check_auth();

$user = get_current_user_data();
$page_title = 'Pengembalian';
$active_tab = 'pengembalian';

$current_tab = $_GET['tab'] ?? 'dipinjam';

// Active loans (dipinjam / disetujui and not returned)
$stmt_dipinjam = $pdo->prepare("
    SELECT p.*, b.nama_barang, b.kode_barang, b.ikon 
    FROM peminjaman p
    JOIN barang b ON p.barang_id = b.id
    WHERE p.user_id = :uid AND p.status = 'disetujui' AND p.tgl_kembali_aktual IS NULL
    ORDER BY p.tgl_kembali_rencana ASC
");
$stmt_dipinjam->execute([':uid' => $user['id']]);
$loans_dipinjam = $stmt_dipinjam->fetchAll();

// Pending approval loans (diajukan / menunggu)
$stmt_diajukan = $pdo->prepare("
    SELECT p.*, b.nama_barang, b.kode_barang, b.ikon 
    FROM peminjaman p
    JOIN barang b ON p.barang_id = b.id
    WHERE p.user_id = :uid AND p.status = 'menunggu'
    ORDER BY p.id DESC
");
$stmt_diajukan->execute([':uid' => $user['id']]);
$loans_diajukan = $stmt_diajukan->fetchAll();

$count_dipinjam = count($loans_dipinjam);
$count_diajukan = count($loans_diajukan);

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="px-5 pt-8 pb-6 flex-1 flex flex-col">
    
    <!-- Top Header -->
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pengembalian</h1>
            <p class="text-xs text-slate-400 mt-1 font-medium">Kelola barang yang sedang Anda pinjam.</p>
        </div>
        <button type="button" onclick="window.location.href='dashboard.php'" class="w-10 h-10 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-center text-slate-600 hover:text-blue-600 transition-colors" title="Riwayat">
            <i data-lucide="history" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- Alert Messages with Auto-Dismiss -->
    <?php if ($flash_success): ?>
        <div id="flashSuccess" class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl flex items-center justify-between shadow-sm transition-all duration-500">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 shrink-0 text-emerald-600"></i>
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

    <!-- Segmented Tabs (Dipinjam / Diajukan) -->
    <div class="bg-[#f0f4f9] p-1 rounded-2xl flex items-center mb-5">
        <a href="pengembalian.php?tab=dipinjam" class="flex-1 py-2 text-center text-xs font-bold rounded-xl transition-all <?= $current_tab === 'dipinjam' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">
            Dipinjam (<?= $count_dipinjam ?>)
        </a>
        <a href="pengembalian.php?tab=diajukan" class="flex-1 py-2 text-center text-xs font-bold rounded-xl transition-all <?= $current_tab === 'diajukan' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">
            Diajukan (<?= $count_diajukan ?>)
        </a>
    </div>

    <?php if ($current_tab === 'dipinjam'): ?>

        <!-- Dynamic Status Info Banner -->
        <?php
        $has_overdue = false;
        $has_urgent = false;
        foreach ($loans_dipinjam as $loan_check) {
            $chk = get_sisa_hari_info($loan_check['tgl_kembali_rencana']);
            if ($chk['status'] === 'overdue') {
                $has_overdue = true;
            } elseif ($chk['status'] === 'today' || $chk['status'] === 'tomorrow') {
                $has_urgent = true;
            }
        }
        ?>

        <?php if ($has_overdue): ?>
            <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                </div>
                <p class="text-xs text-rose-900 leading-relaxed font-medium">
                    Ada barang pinjaman yang telah melewati batas waktu pengembalian. Mohon segera ajukan pengembalian ke sarpras.
                </p>
            </div>
        <?php elseif ($has_urgent): ?>
            <div class="mb-5 p-4 rounded-2xl bg-[#ebf3ff] border border-blue-100 flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                </div>
                <p class="text-xs text-blue-900 leading-relaxed font-medium">
                    Ada barang yang mendekati jatuh tempo. Mohon pastikan kondisi barang utuh dan kembalikan tepat waktu, ya.
                </p>
            </div>
        <?php elseif ($count_dipinjam > 0): ?>
            <div class="mb-5 p-4 rounded-2xl bg-[#f0f7ff] border border-blue-100/80 flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center shrink-0 mt-0.5">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                </div>
                <p class="text-xs text-blue-900 leading-relaxed font-medium">
                    Pinjaman Anda sedang aktif berjalan. Pastikan menjaga kondisi barang dan kembalikan sesuai jadwal rencana.
                </p>
            </div>
        <?php endif; ?>

        <!-- List Barang Dipinjam -->
        <div class="space-y-4 flex-1">
            <?php if (empty($loans_dipinjam)): ?>
                <div class="bg-white rounded-3xl p-8 text-center text-slate-400 border border-slate-100 shadow-sm">
                    <i data-lucide="package-check" class="w-10 h-10 mx-auto mb-2 opacity-50 text-blue-500"></i>
                    <p class="text-xs font-semibold text-slate-700">Tidak ada tanggungan pinjaman</p>
                    <p class="text-[11px] text-slate-400 mt-1">Anda sedang tidak meminjam sarana & prasarana saat ini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($loans_dipinjam as $idx => $loan): ?>
                    <?php $due_info = get_sisa_hari_info($loan['tgl_kembali_rencana']); ?>
                    <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                        
                        <!-- Top details -->
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
                                    <?= htmlspecialchars($loan['kode_peminjaman']) ?> · Dipinjam <?= format_tgl_pendek($loan['tgl_pinjam']) ?>
                                </p>
                            </div>
                        </div>

                        <!-- Date & Urgency Badge (Dihitung Real Time) -->
                        <div class="flex items-center justify-between text-xs pt-1">
                            <div class="flex items-center gap-1.5 text-slate-600 font-medium text-xs">
                                <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                                <span><?= format_tgl_pendek($loan['tgl_kembali_rencana']) ?></span>
                            </div>

                            <span class="<?= $due_info['badge_class'] ?> text-[11px] px-2.5 py-1 rounded-lg">
                                <?= $due_info['badge_text'] ?>
                            </span>
                        </div>

                        <!-- Action Button -->
                        <div>
                            <button 
                                type="button" 
                                onclick="openReturnModal(<?= $loan['id'] ?>, '<?= htmlspecialchars(addslashes($loan['nama_barang'])) ?>')"
                                class="w-full py-3 rounded-2xl bg-[#0084ff] hover:bg-blue-600 active:scale-[0.99] text-white font-semibold text-xs flex items-center justify-center gap-2 shadow-md shadow-blue-500/25 transition-all">
                                <span>Ajukan pengembalian</span>
                                <i data-lucide="package-check" class="w-4 h-4"></i>
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <!-- Tab Diajukan (Menunggu persetujuan) -->
        <div class="space-y-3 flex-1">
            <?php if (empty($loans_diajukan)): ?>
                <div class="bg-white rounded-3xl p-8 text-center text-slate-400 border border-slate-100 shadow-sm">
                    <i data-lucide="clock" class="w-10 h-10 mx-auto mb-2 opacity-50 text-amber-500"></i>
                    <p class="text-xs font-semibold text-slate-700">Tidak ada pengajuan aktif</p>
                    <p class="text-[11px] text-slate-400 mt-1">Semua permohonan pinjam Anda telah diproses.</p>
                </div>
            <?php else: ?>
                <?php foreach ($loans_diajukan as $item): ?>
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                    <i data-lucide="clock" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800"><?= htmlspecialchars($item['nama_barang']) ?></h4>
                                    <p class="text-[10px] text-slate-400 font-medium"><?= htmlspecialchars($item['kode_peminjaman']) ?> · <?= $item['jumlah'] ?> Unit</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md">
                                Menunggu Persetujuan
                            </span>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-xl text-[11px] text-slate-600">
                            <p><span class="font-semibold">Keperluan:</span> <?= htmlspecialchars($item['alasan_pinjam'] ?: '-') ?></p>
                            <p class="mt-1 text-slate-400 text-[10px]">Tanggal: <?= format_tgl_pendek($item['tgl_pinjam']) ?> s/d <?= format_tgl_pendek($item['tgl_kembali_rencana']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<!-- Modal Pengembalian Barang -->
<div id="returnModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-[430px] bg-white rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl animate-slide-down">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Konfirmasi Pengembalian</h3>
                <p id="returnItemName" class="text-xs text-blue-600 font-semibold mt-0.5"></p>
            </div>
            <button type="button" onclick="closeReturnModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form action="api/kembalikan.php" method="POST" class="space-y-4">
            <input type="hidden" name="peminjaman_id" id="returnPeminjamanId">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kondisi Barang Saat Dikembalikan</label>
                <select name="kondisi" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                    <option value="baik">Kondisi Baik & Lengkap (Normal)</option>
                    <option value="rusak_ringan">Ada Kerusakan Ringan / Baret</option>
                    <option value="kendala">Ada Kendala Fungsional</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                <textarea 
                    name="catatan" 
                    rows="2" 
                    placeholder="Tuliskan catatan jika ada komponen/kelengkapan tambahan..." 
                    class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:border-blue-500 placeholder-slate-400 resize-none"></textarea>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button 
                    type="button" 
                    onclick="closeReturnModal()" 
                    class="w-1/3 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="w-2/3 py-3 rounded-xl bg-[#0084ff] hover:bg-blue-600 text-xs font-semibold text-white shadow-md shadow-blue-500/20 transition-all">
                    Selesaikan Pengembalian
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function openReturnModal(loanId, itemName) {
    document.getElementById('returnPeminjamanId').value = loanId;
    document.getElementById('returnItemName').innerText = itemName;
    document.getElementById('returnModal').classList.remove('hidden');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.add('hidden');
}
</script>

<!-- Bottom Navigation -->
<?php require_once __DIR__ . '/includes/bottom_nav.php'; ?>

<!-- Script -->
<script src="assets/js/app.js"></script>
</body>
</html>
