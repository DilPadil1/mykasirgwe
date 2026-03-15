<?php
$title = 'Dashboard';
require_once 'includes/header.php';

// Statistik
$total_produk = $pdo->query("SELECT COUNT(*) FROM produk WHERE status = 1")->fetchColumn();
$total_transaksi_hari = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$total_pendapatan_hari = $pdo->query("SELECT COALESCE(SUM(total_bayar), 0) FROM transaksi WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$stok_menipis = $pdo->query("SELECT COUNT(*) FROM produk WHERE stok <= min_stok AND status = 1")->fetchColumn();

// Grafik penjualan 7 hari terakhir
$penjualan_minggu = $pdo->query("
    SELECT DATE(created_at) as tanggal, COUNT(*) as jumlah, SUM(total_bayar) as total 
    FROM transaksi 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY tanggal
")->fetchAll();

// FIX: Produk terlaris - hanya dari transaksi yang sudah sukses (ada di tabel transaksi)
$produk_terlaris = $pdo->query("
    SELECT p.nama_produk, p.foto, SUM(dt.qty) as total_terjual, SUM(dt.subtotal) as total_pendapatan 
    FROM detail_transaksi dt
    JOIN produk p ON dt.produk_id = p.id
    JOIN transaksi t ON dt.transaksi_id = t.id
    WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY dt.produk_id
    ORDER BY total_terjual DESC
    LIMIT 5
")->fetchAll();

// Transaksi terbaru
$transaksi_terbaru = $pdo->query("
    SELECT t.*, u.nama_lengkap 
    FROM transaksi t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 5
")->fetchAll();
?>

<?php include 'includes/sidebar.php'; ?>

<main class="ml-64 p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-500 mt-1">Selamat datang kembali, <?php echo $user['nama_lengkap']; ?>!</p>
    </div>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Produk</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_produk; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-500">
                    <i class="fas fa-box text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Transaksi Hari Ini</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_transaksi_hari; ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-green-500">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Pendapatan Hari Ini</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo rupiah($total_pendapatan_hari); ?></h3>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center text-purple-500">
                    <i class="fas fa-money-bill-wave text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Stok Menipis</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $stok_menipis; ?></h3>
                </div>
                <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center text-red-500">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Grafik Penjualan -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Penjualan 7 Hari Terakhir</h3>
            <canvas id="salesChart" height="250"></canvas>
        </div>

        <!-- Transaksi Terbaru -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Transaksi Terbaru</h3>
            <div class="space-y-3">
                <?php foreach($transaksi_terbaru as $t): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                    <div>
                        <p class="font-medium text-gray-800 text-sm"><?php echo $t['kode_transaksi']; ?></p>
                        <p class="text-xs text-gray-500"><?php echo $t['nama_lengkap']; ?></p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800"><?php echo rupiah($t['total_bayar']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo date('H:i', strtotime($t['created_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- FIX: Produk Terlaris dengan Foto -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Top 5 Produk Terlaris (30 Hari Terakhir)</h3>
            <a href="laporan.php" class="text-blue-500 text-sm hover:underline">Lihat Detail →</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <?php foreach($produk_terlaris as $index => $produk): ?>
            <div class="relative group">
                <div class="bg-gray-50 rounded-2xl p-4 text-center hover:shadow-md transition-all">
                    <?php if($index < 3): ?>
                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                        <i class="fas fa-crown text-xs"></i>
                    </div>
                    <?php endif; ?>
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full overflow-hidden bg-white shadow-sm">
                        <img src="<?php echo $produk['foto'] ?? 'https://ui-avatars.com/api/?name='.urlencode($produk['nama_produk']).'&background=random'; ?>" 
                            class="w-full h-full object-cover">
                    </div>
                    <h4 class="font-medium text-gray-800 text-sm mb-1 line-clamp-2"><?php echo $produk['nama_produk']; ?></h4>
                    <p class="text-2xl font-bold text-blue-600"><?php echo $produk['total_terjual']; ?></p>
                    <p class="text-xs text-gray-500">terjual</p>
                    <p class="text-xs text-green-600 mt-1"><?php echo rupiah($produk['total_pendapatan']); ?></p>
                </div>
                <div class="absolute top-2 left-2 w-6 h-6 bg-gray-800 text-white rounded-full flex items-center justify-center text-xs font-bold">
                    <?php echo $index + 1; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($d) { return date('d/m', strtotime($d['tanggal'])); }, $penjualan_minggu)); ?>,
        datasets: [{
            label: 'Penjualan (Rp)',
            data: <?php echo json_encode(array_map(function($d) { return $d['total']; }, $penjualan_minggu)); ?>,
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include 'includes/footer.php'; ?>