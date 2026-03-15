<?php
require_once 'config/database.php';

$kode = $_GET['kode'] ?? '';
$print = isset($_GET['print']);

$stmt = $pdo->prepare("SELECT t.*, u.nama_lengkap FROM transaksi t JOIN users u ON t.user_id = u.id WHERE t.kode_transaksi = ?");
$stmt->execute([$kode]);
$transaksi = $stmt->fetch();

if(!$transaksi) {
    die('Transaksi tidak ditemukan');
}

$detail = $pdo->prepare("SELECT dt.*, p.nama_produk FROM detail_transaksi dt JOIN produk p ON dt.produk_id = p.id WHERE dt.transaksi_id = ?");
$detail->execute([$transaksi['id']]);
$items = $detail->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk <?php echo $kode; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { width: 80mm; margin: 0; }
            .no-print { display: none; }
        }
        body { font-family: 'Courier New', monospace; }
    </style>
</head>
<body class="bg-white p-4 max-w-[80mm] mx-auto">
    <div class="text-center mb-4">
        <h2 class="font-bold text-lg">TOKO ANDA</h2>
        <p class="text-xs">Jl. Contoh No. 123</p>
        <p class="text-xs">Telp: 0812-3456-7890</p>
    </div>
    
    <div class="text-xs mb-4">
        <p>No: <?php echo $transaksi['kode_transaksi']; ?></p>
        <p>Tgl: <?php echo tanggal_indo($transaksi['created_at']); ?></p>
        <p>Kasir: <?php echo $transaksi['nama_lengkap']; ?></p>
    </div>
    
    <div class="border-t border-b border-black py-2 mb-2 text-xs">
        <?php foreach($items as $item): ?>
        <div class="flex justify-between mb-1">
            <span><?php echo $item['nama_produk']; ?></span>
        </div>
        <div class="flex justify-between pl-2">
            <span><?php echo $item['qty']; ?> x <?php echo rupiah($item['harga_satuan']); ?></span>
            <span><?php echo rupiah($item['subtotal']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-xs space-y-1 mb-4">
        <div class="flex justify-between">
            <span>Subtotal</span>
            <span><?php echo rupiah($transaksi['total_harga']); ?></span>
        </div>
        <?php if($transaksi['diskon'] > 0): ?>
        <div class="flex justify-between">
            <span>Diskon</span>
            <span>-<?php echo rupiah($transaksi['diskon']); ?></span>
        </div>
        <?php endif; ?>
        <?php if($transaksi['pajak'] > 0): ?>
        <div class="flex justify-between">
            <span>Pajak</span>
            <span><?php echo rupiah($transaksi['pajak']); ?></span>
        </div>
        <?php endif; ?>
        <div class="flex justify-between font-bold border-t border-black pt-1">
            <span>TOTAL</span>
            <span><?php echo rupiah($transaksi['total_bayar']); ?></span>
        </div>
        <div class="flex justify-between">
            <span>Tunai</span>
            <span><?php echo rupiah($transaksi['tunai']); ?></span>
        </div>
        <div class="flex justify-between">
            <span>Kembali</span>
            <span><?php echo rupiah($transaksi['kembalian']); ?></span>
        </div>
    </div>
    
    <div class="text-center text-xs">
        <p>Terima Kasih</p>
        <p>Selamat Belanja Kembali</p>
    </div>
    
    <?php if(!$print): ?>
    <div class="no-print mt-8 flex gap-2">
        <button onclick="window.print()" class="flex-1 bg-blue-500 text-white py-2 rounded">Cetak</button>
        <button onclick="window.close()" class="flex-1 bg-gray-500 text-white py-2 rounded">Tutup</button>
    </div>
    <?php else: ?>
    <script>window.onload = function() { window.print(); }</script>
    <?php endif; ?>
</body>
</html>