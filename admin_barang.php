<?php
// admin_barang.php
require_once __DIR__ . '/includes/auth_check.php';
check_auth();

$user = get_current_user_data();

// Only admin
if (($user['role'] ?? '') !== 'admin' && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$page_title = 'Kelola Barang';
$active_tab = 'barang';

// Handle Add/Edit Stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_stock') {
        $barang_id = (int)($_POST['barang_id'] ?? 0);
        $stok_total = (int)($_POST['stok_total'] ?? 0);
        $stok_tersedia = (int)($_POST['stok_tersedia'] ?? 0);

        if ($stok_tersedia > $stok_total) {
            $stok_tersedia = $stok_total;
        }

        $stmt = $pdo->prepare("UPDATE barang SET stok_total = :st, stok_tersedia = :sa WHERE id = :id");
        $stmt->execute([':st' => $stok_total, ':sa' => $stok_tersedia, ':id' => $barang_id]);
        $_SESSION['flash_success'] = 'Stok barang berhasil diperbarui!';
        header("Location: admin_barang.php");
        exit;
    }

    if ($action === 'add_item') {
        $kode = strtoupper(trim($_POST['kode_barang'] ?? ''));
        $nama = trim($_POST['nama_barang'] ?? '');
        $kategori_id = (int)($_POST['kategori_id'] ?? 1);
        $stok = (int)($_POST['stok'] ?? 1);
        $ikon = trim($_POST['ikon'] ?? 'laptop');

        if (!empty($kode) && !empty($nama)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO barang (kode_barang, nama_barang, kategori_id, stok_total, stok_tersedia, ikon) VALUES (:k, :n, :kat, :st, :st, :ik)");
                $stmt->execute([':k' => $kode, ':n' => $nama, ':kat' => $kategori_id, ':st' => $stok, ':ik' => $ikon]);
                $_SESSION['flash_success'] = "Barang {$nama} berhasil ditambahkan!";
            } catch (Exception $e) {
                $_SESSION['flash_error'] = "Gagal menambahkan: Kode barang mungkin sudah ada.";
            }
        }
        header("Location: admin_barang.php");
        exit;
    }
}

$stmt_items = $pdo->query("SELECT b.*, k.nama_kategori FROM barang b JOIN kategori k ON b.kategori_id = k.id ORDER BY b.id ASC");
$all_items = $stmt_items->fetchAll();

$categories = $pdo->query("SELECT * FROM kategori")->fetchAll();

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
            <button type="button" onclick="openAddItemModal()" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1 bg-blue-50 px-2.5 py-1.5 rounded-xl">
                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
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
        <h1 class="text-3xl font-extrabold text-[#0f172a] tracking-tight">Data Barang</h1>
        <p class="text-xs text-slate-400 font-medium mt-1">Kelola stok dan inventaris sarana prasarana.</p>
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

    <!-- Search Input -->
    <div class="relative flex items-center rounded-2xl bg-white border border-slate-200 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 transition-all mb-4">
        <span class="pl-4 text-slate-400">
            <i data-lucide="search" class="w-4 h-4"></i>
        </span>
        <input 
            type="text" 
            id="searchInput" 
            onkeyup="searchBarang(this.value)"
            placeholder="Cari kode atau nama barang..." 
            class="w-full bg-transparent px-3 py-3 text-xs text-slate-800 placeholder-slate-400 focus:outline-none">
    </div>

    <!-- Barang List -->
    <div class="space-y-3.5 flex-1">
        <?php foreach ($all_items as $item): ?>
            <div class="barang-item bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm space-y-3" data-name="<?= htmlspecialchars($item['nama_barang']) ?>" data-code="<?= htmlspecialchars($item['kode_barang']) ?>">
                
                <div class="flex items-start gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <?php if ($item['ikon'] === 'proyektor'): ?>
                            <i data-lucide="projector" class="w-6 h-6"></i>
                        <?php elseif ($item['ikon'] === 'mouse'): ?>
                            <i data-lucide="mouse" class="w-6 h-6"></i>
                        <?php else: ?>
                            <i data-lucide="laptop" class="w-6 h-6"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-800 text-sm tracking-tight truncate"><?= htmlspecialchars($item['nama_barang']) ?></h3>
                            <span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg"><?= htmlspecialchars($item['kode_barang']) ?></span>
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5"><?= htmlspecialchars($item['nama_kategori']) ?></p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                    <div class="space-x-2">
                        <span class="font-semibold text-slate-600">Tersedia: <strong class="text-emerald-600"><?= $item['stok_tersedia'] ?></strong></span>
                        <span class="text-slate-300">|</span>
                        <span class="text-slate-400">Total: <?= $item['stok_total'] ?></span>
                    </div>

                    <button 
                        type="button" 
                        onclick="openEditStockModal(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nama_barang'])) ?>', <?= $item['stok_total'] ?>, <?= $item['stok_tersedia'] ?>)"
                        class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors flex items-center gap-1">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        <span>Ubah Stok</span>
                    </button>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- Modal Ubah Stok -->
<div id="editStockModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-[430px] bg-white rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl animate-slide-down">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Ubah Stok Barang</h3>
                <p id="editStockItemName" class="text-xs text-blue-600 font-semibold mt-0.5"></p>
            </div>
            <button type="button" onclick="closeEditStockModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form action="admin_barang.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="barang_id" id="editBarangId">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Stok Total</label>
                    <input type="number" name="stok_total" id="editStokTotal" min="0" required class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Stok Tersedia</label>
                    <input type="number" name="stok_tersedia" id="editStokTersedia" min="0" required class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button" onclick="closeEditStockModal()" class="w-1/3 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Batal</button>
                <button type="submit" class="w-2/3 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white shadow-md transition-all">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Barang Baru -->
<div id="addItemModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-[430px] bg-white rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl animate-slide-down">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-base">Tambah Barang Baru</h3>
                <p class="text-xs text-slate-400">Registrasi inventaris sarpras</p>
            </div>
            <button type="button" onclick="closeAddItemModal()" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form action="admin_barang.php" method="POST" class="space-y-3.5">
            <input type="hidden" name="action" value="add_item">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Barang</label>
                    <input type="text" name="kode_barang" placeholder="MISAL: PC, CAM" required class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500 uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori</label>
                    <select name="kategori_id" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Barang</label>
                <input type="text" name="nama_barang" placeholder="Nama lengkap sarana / barang" required class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Unit</label>
                    <input type="number" name="stok" value="1" min="1" required class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ikon Tampilan</label>
                    <select name="ikon" class="w-full bg-[#f4f7fb] border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-blue-500">
                        <option value="laptop">Laptop</option>
                        <option value="proyektor">Proyektor</option>
                        <option value="mouse">Mouse / Aksesori</option>
                    </select>
                </div>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button" onclick="closeAddItemModal()" class="w-1/3 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Batal</button>
                <button type="submit" class="w-2/3 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white shadow-md transition-all">Tambahkan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditStockModal(id, name, total, avail) {
    document.getElementById('editBarangId').value = id;
    document.getElementById('editStockItemName').innerText = name;
    document.getElementById('editStokTotal').value = total;
    document.getElementById('editStokTersedia').value = avail;
    document.getElementById('editStockModal').classList.remove('hidden');
}

function closeEditStockModal() {
    document.getElementById('editStockModal').classList.add('hidden');
}

function openAddItemModal() {
    document.getElementById('addItemModal').classList.remove('hidden');
}

function closeAddItemModal() {
    document.getElementById('addItemModal').classList.add('hidden');
}
</script>

<!-- Bottom Navigation -->
<?php require_once __DIR__ . '/includes/bottom_nav.php'; ?>

<!-- Script -->
<script src="assets/js/app.js"></script>
</body>
</html>
