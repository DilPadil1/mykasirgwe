<?php
$title = 'Riwayat Transaksi';
require_once 'includes/header.php';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter
$search = $_GET['search'] ?? '';
$tanggal_dari = $_GET['dari'] ?? date('Y-m-d');
$tanggal_sampai = $_GET['sampai'] ?? date('Y-m-d');

$sql = "SELECT t.*, u.nama_lengkap FROM transaksi t JOIN users u ON t.user_id = u.id WHERE DATE(t.created_at) BETWEEN ? AND ?";
$params = [$tanggal_dari, $tanggal_sampai];

if($search) {
    $sql .= " AND (t.kode_transaksi LIKE ? OR u.nama_lengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY t.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksi = $stmt->fetchAll();

// Total untuk pagination
$total = $pdo->prepare("SELECT COUNT(*) FROM transaksi t JOIN users u ON t.user_id = u.id WHERE DATE(t.created_at) BETWEEN ? AND ?" . ($search ? " AND (t.kode_transaksi LIKE ? OR u.nama_lengkap LIKE ?)" : ""));
$total->execute($params);
$total_pages = ceil($total->fetchColumn() / $limit);
?>

<?php include 'includes/sidebar.php'; ?>

<main class="ml-64 p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Riwayat Transaksi</h1>
        <p class="text-gray-500 mt-1">Lihat semua transaksi penjualan</p>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 mb-6 shadow-sm border border-gray-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Kode transaksi/kasir..." 
                        class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Dari Tanggal</label>
                <input type="date" name="dari" value="<?php echo $tanggal_dari; ?>" class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sampai</label>
                <input type="date" name="sampai" value="<?php echo $tanggal_sampai; ?>" class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded-xl hover:bg-blue-600 transition-colors">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>
    </div>

    <!-- Tabel -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Kode Transaksi</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Tanggal</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Kasir</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">Total</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">Metode</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach($transaksi as $t): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-gray-800"><?php echo $t['kode_transaksi']; ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo tanggal_indo($t['created_at']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo $t['nama_lengkap']; ?></td>
                    <td class="px-6 py-4 text-right font-bold text-gray-800"><?php echo rupiah($t['total_bayar']); ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-medium capitalize
                            <?php echo $t['metode_pembayaran'] == 'tunai' ? 'bg-green-100 text-green-700' : 
                                ($t['metode_pembayaran'] == 'debit' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'); ?>">
                            <?php echo $t['metode_pembayaran']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="struk.php?kode=<?php echo $t['kode_transaksi']; ?>" target="_blank" 
                            class="w-8 h-8 bg-blue-50 text-blue-500 rounded-lg hover:bg-blue-100 inline-flex items-center justify-center transition-colors">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="struk.php?kode=<?php echo $t['kode_transaksi']; ?>&print=1" target="_blank"
                            class="w-8 h-8 bg-green-50 text-green-500 rounded-lg hover:bg-green-100 inline-flex items-center justify-center transition-colors ml-2">
                            <i class="fas fa-print"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="flex justify-center gap-2 mt-6">
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&dari=<?php echo $tanggal_dari; ?>&sampai=<?php echo $tanggal_sampai; ?>" 
            class="w-10 h-10 rounded-xl flex items-center justify-center font-medium transition-colors <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>