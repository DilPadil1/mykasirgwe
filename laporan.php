<?php
$title = 'Laporan Penjualan';
require_once 'includes/header.php';

// Default periode
$periode = $_GET['periode'] ?? 'hari_ini';
$tanggal_dari = $_GET['dari'] ?? date('Y-m-d');
$tanggal_sampai = $_GET['sampai'] ?? date('Y-m-d');

switch($periode) {
    case 'hari_ini':
        $tanggal_dari = $tanggal_sampai = date('Y-m-d');
        break;
    case 'kemarin':
        $tanggal_dari = $tanggal_sampai = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'minggu_ini':
        $tanggal_dari = date('Y-m-d', strtotime('monday this week'));
        $tanggal_sampai = date('Y-m-d');
        break;
    case 'bulan_ini':
        $tanggal_dari = date('Y-m-01');
        $tanggal_sampai = date('Y-m-d');
        break;
    case 'custom':
        // Gunakan dari form
        break;
}

// Statistik
$stat = $pdo->prepare("SELECT 
    COUNT(*) as total_transaksi,
    SUM(total_bayar) as total_pendapatan,
    SUM(total_harga) as total_penjualan_kotor,
    SUM(diskon) as total_diskon,
    AVG(total_bayar) as rata_rata_transaksi
FROM transaksi WHERE DATE(created_at) BETWEEN ? AND ?");
$stat->execute([$tanggal_dari, $tanggal_sampai]);
$statistik = $stat->fetch();

// Penjualan per hari (untuk grafik)
$grafik = $pdo->prepare("SELECT DATE(created_at) as tanggal, COUNT(*) as jumlah, SUM(total_bayar) as total 
FROM transaksi WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY tanggal");
$grafik->execute([$tanggal_dari, $tanggal_sampai]);
$data_grafik = $grafik->fetchAll();

// Produk terlaris periode ini
$terlaris = $pdo->prepare("SELECT p.nama_produk, SUM(dt.qty) as total_qty, SUM(dt.subtotal) as total_pendapatan
FROM detail_transaksi dt 
JOIN produk p ON dt.produk_id = p.id 
JOIN transaksi t ON dt.transaksi_id = t.id
WHERE DATE(t.created_at) BETWEEN ? AND ?
GROUP BY dt.produk_id ORDER BY total_qty DESC LIMIT 10");
$terlaris->execute([$tanggal_dari, $tanggal_sampai]);
$produk_terlaris = $terlaris->fetchAll();

// Penjualan per kategori
$perKategori = $pdo->prepare("SELECT k.nama_kategori, COUNT(DISTINCT t.id) as jumlah_transaksi, SUM(dt.subtotal) as total
FROM detail_transaksi dt 
JOIN produk p ON dt.produk_id = p.id 
JOIN kategori k ON p.kategori_id = k.id
JOIN transaksi t ON dt.transaksi_id = t.id
WHERE DATE(t.created_at) BETWEEN ? AND ?
GROUP BY p.kategori_id");
$perKategori->execute([$tanggal_dari, $tanggal_sampai]);
$kategori_stats = $perKategori->fetchAll();
?>

<?php include 'includes/sidebar.php'; ?>

<main class="ml-64 p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Laporan Penjualan</h1>
            <p class="text-gray-500 mt-1">Analisis penjualan dan performa toko</p>
        </div>
        <button onclick="window.print()" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center gap-2">
            <i class="fas fa-print"></i>Cetak Laporan
        </button>
    </div>

    <!-- Filter Periode -->
    <div class="bg-white rounded-2xl p-4 mb-6 shadow-sm border border-gray-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Periode</label>
                <select name="periode" onchange="this.form.submit()" class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    <option value="hari_ini" <?php echo $periode == 'hari_ini' ? 'selected' : ''; ?>>Hari Ini</option>
                    <option value="kemarin" <?php echo $periode == 'kemarin' ? 'selected' : ''; ?>>Kemarin</option>
                    <option value="minggu_ini" <?php echo $periode == 'minggu_ini' ? 'selected' : ''; ?>>Minggu Ini</option>
                    <option value="bulan_ini" <?php echo $periode == 'bulan_ini' ? 'selected' : ''; ?>>Bulan Ini</option>
                    <option value="custom" <?php echo $periode == 'custom' ? 'selected' : ''; ?>>Custom</option>
                </select>
            </div>
            <?php if($periode == 'custom'): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Dari</label>
                <input type="date" name="dari" value="<?php echo $tanggal_dari; ?>" class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sampai</label>
                <input type="date" name="sampai" value="<?php echo $tanggal_sampai; ?>" class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded-xl hover:bg-blue-600">Terapkan</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
            <p class="text-blue-100 text-sm mb-1">Total Transaksi</p>
            <h3 class="text-3xl font-bold"><?php echo number_format($statistik['total_transaksi']); ?></h3>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white">
            <p class="text-green-100 text-sm mb-1">Total Pendapatan</p>
            <h3 class="text-3xl font-bold"><?php echo rupiah($statistik['total_pendapatan']); ?></h3>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white">
            <p class="text-purple-100 text-sm mb-1">Rata-rata Transaksi</p>
            <h3 class="text-3xl font-bold"><?php echo rupiah($statistik['rata_rata_transaksi']); ?></h3>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white">
            <p class="text-orange-100 text-sm mb-1">Total Diskon</p>
            <h3 class="text-3xl font-bold"><?php echo rupiah($statistik['total_diskon']); ?></h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Grafik Penjualan -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Tren Penjualan</h3>
            <canvas id="salesChart" height="250"></canvas>
        </div>

        <!-- Penjualan per Kategori -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Penjualan per Kategori</h3>
            <canvas id="categoryChart" height="250"></canvas>
        </div>
    </div>

    <!-- Produk Terlaris -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Top 10 Produk Terlaris</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Ranking</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Nama Produk</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600">Qty Terjual</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($produk_terlaris as $index => $p): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div class="w-8 h-8 rounded-full <?php echo $index < 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'; ?> flex items-center justify-center font-bold">
                                <?php echo $index + 1; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo $p['nama_produk']; ?></td>
                        <td class="px-6 py-4 text-right font-bold text-blue-600"><?php echo number_format($p['total_qty']); ?></td>
                        <td class="px-6 py-4 text-right font-bold text-gray-800"><?php echo rupiah($p['total_pendapatan']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Grafik Penjualan
const ctx1 = document.getElementById('salesChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($d) { return date('d/m', strtotime($d['tanggal'])); }, $data_grafik)); ?>,
        datasets: [{
            label: 'Pendapatan',
            data: <?php echo json_encode(array_map(function($d) { return $d['total']; }, $data_grafik)); ?>,
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Jumlah Transaksi',
            data: <?php echo json_encode(array_map(function($d) { return $d['jumlah']; }, $data_grafik)); ?>,
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: { intersect: false, mode: 'index' },
        scales: {
            y: { beginAtZero: true, ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString(); } } },
            y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
        }
    }
});

// Grafik Kategori
const ctx2 = document.getElementById('categoryChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(function($k) { return $k['nama_kategori']; }, $kategori_stats)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_map(function($k) { return $k['total']; }, $kategori_stats)); ?>,
            backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>