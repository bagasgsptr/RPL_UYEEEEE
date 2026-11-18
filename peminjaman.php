<?php
// peminjaman.php
require_once __DIR__ . '/includes/auth_check.php';
check_auth();

$user = get_current_user_data();
$page_title = 'Peminjaman';
$active_tab = 'peminjaman';

$filter_cat = $_GET['kategori'] ?? 'semua';

// Fetch all categories
$stmt_cats = $pdo->query("SELECT * FROM kategori ORDER BY id ASC");
$categories = $stmt_cats->fetchAll();

// Fetch all barang
$stmt_items = $pdo->query("
    SELECT b.*, k.nama_kategori, k.slug as kategori_slug 
    FROM barang b 
    JOIN kategori k ON b.kategori_id = k.id 
    ORDER BY b.id ASC
");
$all_items = $stmt_items->fetchAll();

$total_units = 0;
$total_types = count($all_items);
foreach ($all_items as $itm) {
    $total_units += (int)$itm['stok_tersedia'];
}

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="px-5 pt-8 pb-6 flex-1 flex flex-col">
    
    <!-- Top Header -->
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Peminjaman</h1>
            <p class="text-xs text-slate-400 mt-1 font-medium">Temukan barang untuk kerja Anda.</p>
        </div>
        <button type="button" onclick="alert('Fitur filter lanjutan aktif pada kategori di bawah.')" class="w-10 h-10 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center justify-center text-slate-600 hover:text-blue-600 transition-colors">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
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

    <!-- Search Input Bar -->
    <div class="relative flex items-center rounded-2xl bg-white border border-slate-200 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 transition-all mb-4">
        <span class="pl-4 text-slate-400">
            <i data-lucide="search" class="w-4 h-4"></i>
        </span>
        <input 
            type="text" 
            id="searchInput" 
            onkeyup="searchBarang(this.value)"
            placeholder="Cari laptop, proyektor, alat kantor..." 
            class="w-full bg-transparent px-3 py-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none">
    </div>

    <!-- Category Chips Filter -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-5 no-scrollbar">
        <button 
            type="button" 
            onclick="filterCategory('semua', this)"
            class="category-chip px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?= $filter_cat === 'semua' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200' ?>">
            Semua
        </button>
        <?php foreach ($categories as $cat): ?>
            <button 
                type="button" 
                onclick="filterCategory('<?= htmlspecialchars($cat['slug']) ?>', this)"
                class="category-chip px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?= $filter_cat === $cat['slug'] ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200' ?>">
                <?= htmlspecialchars($cat['nama_kategori']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Barang Tersedia Header -->
    <div class="flex items-center justify-between mb-3.5">
        <h2 class="text-sm font-bold text-slate-800">Barang tersedia</h2>
        <span class="text-xs text-slate-400 font-medium"><?= $total_units ?> unit · <?= $total_types ?> jenis</span>
    </div>

    <!-- Barang List -->
    <div id="barangContainer" class="space-y-3 flex-1">
        <?php foreach ($all_items as $item): ?>
            <div 
                class="barang-item bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between gap-3"
                data-name="<?= htmlspecialchars($item['nama_barang']) ?>"
                data-code="<?= htmlspecialchars($item['kode_barang']) ?>"
                data-category="<?= htmlspecialchars($item['kategori_slug']) ?>"
                style="<?= ($filter_cat !== 'semua' && $filter_cat !== $item['kategori_slug']) ? 'display: none;' : '' ?>">
                
                <div class="flex items-start gap-3.5">
                    <!-- Icon Box -->
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <?php if ($item['ikon'] === 'proyektor'): ?>
                            <i data-lucide="projector" class="w-6 h-6"></i>
                        <?php elseif ($item['ikon'] === 'mouse'): ?>
                            <i data-lucide="mouse" class="w-6 h-6"></i>
                        <?php else: ?>
                            <i data-lucide="laptop" class="w-6 h-6"></i>
                        <?php endif; ?>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-slate-800 text-xs tracking-tight line-clamp-1"><?= htmlspecialchars($item['nama_barang']) ?></h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5"><?= htmlspecialchars($item['kode_barang']) ?> · <?= htmlspecialchars($item['nama_kategori']) ?></p>
                    </div>
                </div>

                <!-- Footer Card: Availability badge + Pinjam button -->
                <div class="flex items-center justify-between pt-1">
                    <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
                        <?= $item['stok_tersedia'] ?> unit tersedia
                    </span>

                    <button 
                        type="button" 
                        onclick="openBorrowModal(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nama_barang'])) ?>', <?= $item['stok_tersedia'] ?>)"
                        class="bg-[#0084ff] hover:bg-blue-600 active:scale-95 text-white font-semibold text-xs px-5 py-2 rounded-xl transition-all shadow-sm shadow-blue-500/20">
                        Pinjam
                    </button>
                </div>

            </div>
        <?php endforeach; ?>

        <!-- Empty search state -->
        <div id="emptySearchState" class="hidden p-8 text-center text-slate-400">
            <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
            <p class="text-xs">Barang yang Anda cari tidak ditemukan.</p>
        </div>
    </div>

</div>

<!-- Modal Pinjam Barang -->
<div id="borrowModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-[430px] bg-white rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl animate-slide-down">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Ajukan Peminjaman</h3>
                <p id="modalItemName" class="text-xs text-blue-600 font-semibold mt-0.5"></p>
            </div>
            <button type="button" onclick="closeBorrowModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form action="api/pinjam.php" method="POST" class="space-y-4">
            <input type="hidden" name="barang_id" id="modalBarangId">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Unit</label>
                <input 
                    type="number" 
                    name="jumlah" 
                    id="modalJumlah" 
                    value="1" 
                    min="1" 
                    max="10" 
                    required 
                    class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                <p class="text-[10px] text-slate-400 mt-1" id="modalMaxStockText">Maksimal stok tersedia: 8</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pinjam</label>
                    <input 
                        type="date" 
                        name="tgl_pinjam" 
                        value="<?= date('Y-m-d') ?>" 
                        required 
                        class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Rencana Kembali</label>
                    <input 
                        type="date" 
                        name="tgl_kembali_rencana" 
                        value="<?= date('Y-m-d', strtotime('+3 days')) ?>" 
                        required 
                        class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Keperluan / Alasan Pinjam</label>
                <textarea 
                    name="alasan_pinjam" 
                    rows="2" 
                    placeholder="Contoh: Kebutuhan dinas / presentasi proyek..." 
                    required
                    class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:border-blue-500 placeholder-slate-400 resize-none"></textarea>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button 
                    type="button" 
                    onclick="closeBorrowModal()" 
                    class="w-1/3 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="w-2/3 py-3 rounded-xl bg-[#0084ff] hover:bg-blue-600 text-xs font-semibold text-white shadow-md shadow-blue-500/20 transition-all">
                    Kirim Pengajuan
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function openBorrowModal(id, name, maxStock) {
    document.getElementById('modalBarangId').value = id;
    document.getElementById('modalItemName').innerText = name;
    const qtyInput = document.getElementById('modalJumlah');
    qtyInput.max = maxStock;
    qtyInput.value = 1;
    document.getElementById('modalMaxStockText').innerText = `Maksimal stok tersedia: ${maxStock} unit`;
    document.getElementById('borrowModal').classList.remove('hidden');
}

function closeBorrowModal() {
    document.getElementById('borrowModal').classList.add('hidden');
}
</script>

<!-- Bottom Navigation -->
<?php require_once __DIR__ . '/includes/bottom_nav.php'; ?>

<!-- Script -->
<script src="assets/js/app.js"></script>
</body>
</html>
