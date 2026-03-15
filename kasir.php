<?php
$title = 'Kasir';
require_once 'includes/header.php';

// Ambil semua kategori dengan produk
$kategori_list = $pdo->query("
    SELECT k.*, COUNT(p.id) as jumlah_produk
    FROM kategori k 
    LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 1 AND p.stok > 0
    GROUP BY k.id
    ORDER BY k.nama_kategori
")->fetchAll();

// Ambil semua produk aktif dengan stok > 0
$produk_all = $pdo->query("
    SELECT p.*, k.nama_kategori, k.id as kategori_id 
    FROM produk p 
    LEFT JOIN kategori k ON p.kategori_id = k.id 
    WHERE p.status = 1 AND p.stok > 0
    ORDER BY k.nama_kategori, p.nama_produk
")->fetchAll();

// Proses transaksi
$error_msg = '';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_transaksi'])) {
    try {
        $pdo->beginTransaction();
        
        // Validasi
        if(empty($_POST['produk_id']) || !is_array($_POST['produk_id'])) {
            throw new Exception("Keranjang kosong!");
        }
        
        $kode_transaksi = 'TRX' . date('YmdHis') . rand(10, 99);
        $total_harga = floatval($_POST['total_harga']);
        $diskon = floatval($_POST['diskon'] ?? 0);
        $pajak = floatval($_POST['pajak'] ?? 0);
        $total_bayar = floatval($_POST['total_bayar']);
        $tunai = floatval($_POST['tunai']);
        $kembalian = floatval($_POST['kembalian']);
        $metode = $_POST['metode_pembayaran'] ?? 'tunai';
        $catatan = $_POST['catatan'] ?? '';
        
        // Insert transaksi
        $stmt = $pdo->prepare("INSERT INTO transaksi 
            (kode_transaksi, user_id, total_harga, diskon, pajak, total_bayar, tunai, kembalian, metode_pembayaran, catatan) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $kode_transaksi, $_SESSION['user_id'], $total_harga, $diskon, 
            $pajak, $total_bayar, $tunai, $kembalian, $metode, $catatan
        ]);
        $transaksi_id = $pdo->lastInsertId();
        
        // Insert detail dan update stok
        foreach($_POST['produk_id'] as $index => $produk_id) {
            $qty = intval($_POST['qty'][$index]);
            $harga = floatval($_POST['harga_satuan'][$index]);
            $subtotal = $qty * $harga;
            
            $stmt = $pdo->prepare("INSERT INTO detail_transaksi 
                (transaksi_id, produk_id, qty, harga_satuan, subtotal) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$transaksi_id, $produk_id, $qty, $harga, $subtotal]);
            
            // Update stok
            $stmt = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
            $stmt->execute([$qty, $produk_id]);
        }
        
        $pdo->commit();
        header('Location: kasir.php?success=1&kode=' . $kode_transaksi);
        exit;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
}
?>

<?php include 'includes/sidebar.php'; ?>

<main class="ml-64 p-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Kasir</h1>
        <p class="text-gray-500 mt-1">Proses penjualan produk</p>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg flex justify-between items-center">
        <div>
            <p class="text-green-700 font-medium"><i class="fas fa-check-circle mr-2"></i>Transaksi berhasil!</p>
            <p class="text-green-600 text-sm">Kode: <?php echo $_GET['kode']; ?></p>
        </div>
        <a href="struk.php?kode=<?php echo $_GET['kode']; ?>" target="_blank" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
            <i class="fas fa-print mr-2"></i>Cetak Struk
        </a>
    </div>
    <?php endif; ?>

    <?php if($error_msg): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
        <p class="text-red-700"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_msg; ?></p>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Area Produk -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Kategori Tabs -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <div class="flex gap-2 overflow-x-auto pb-2">
                    <button onclick="filterKategori('all')" class="kategori-tab active whitespace-nowrap px-4 py-2 rounded-lg font-medium transition-all bg-blue-500 text-white" data-kategori="all">
                        Semua
                    </button>
                    <?php foreach($kategori_list as $k): ?>
                    <button onclick="filterKategori(<?php echo $k['id']; ?>)" class="kategori-tab whitespace-nowrap px-4 py-2 rounded-lg font-medium transition-all bg-gray-100 text-gray-600 hover:bg-gray-200" data-kategori="<?php echo $k['id']; ?>">
                        <?php echo $k['nama_kategori']; ?> (<?php echo $k['jumlah_produk']; ?>)
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pencarian -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="searchProduk" placeholder="Cari produk..." 
                        class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"
                        onkeyup="cariProduk(this.value)">
                </div>
                <div id="hasilPencarian" class="hidden mt-2 max-h-48 overflow-y-auto border border-gray-200 rounded-xl divide-y divide-gray-100 bg-white"></div>
            </div>

            <!-- Grid Produk -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                <div id="produkGrid" class="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                    <!-- Diisi JavaScript -->
                </div>
            </div>

            <!-- Keranjang -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Keranjang</h3>
                    <span id="badgeKeranjang" class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">0 item</span>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Produk</th>
                            <th class="px-4 py-2 text-center text-sm font-semibold text-gray-600">Qty</th>
                            <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Subtotal</th>
                            <th class="px-4 py-2 text-center text-sm font-semibold text-gray-600"></th>
                        </tr>
                    </thead>
                    <tbody id="keranjangBody" class="divide-y divide-gray-100">
                        <tr id="emptyRow">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                                <p>Keranjang masih kosong</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Area Pembayaran -->
        <div class="lg:col-span-1">
            <form method="POST" id="formTransaksi" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4 sticky top-6">
                <h3 class="font-bold text-gray-800 text-lg mb-4">Pembayaran</h3>
                
                <!-- Hidden inputs container - PENTING! -->
                <div id="hiddenInputsContainer"></div>
                
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium" id="displaySubtotal">Rp 0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Diskon</span>
                        <input type="number" name="diskon" id="diskon" value="0" min="0" 
                            class="w-24 px-2 py-1 border border-gray-200 rounded text-right"
                            onchange="hitungTotal()">
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Pajak (10%)</span>
                        <input type="checkbox" id="cbPajak" onchange="hitungTotal()" class="w-4 h-4 text-blue-500 rounded">
                    </div>
                    <div class="border-t pt-2">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-800">Total</span>
                            <span class="font-bold text-xl text-blue-600" id="displayTotal">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Metode</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="setMetode('tunai')" class="metode-btn active bg-blue-50 border-2 border-blue-500 text-blue-700 py-2 rounded-lg text-sm font-medium" data-metode="tunai">
                            Tunai
                        </button>
                        <button type="button" onclick="setMetode('debit')" class="metode-btn bg-gray-50 border-2 border-gray-200 text-gray-600 py-2 rounded-lg text-sm font-medium" data-metode="debit">
                            Debit
                        </button>
                        <button type="button" onclick="setMetode('qris')" class="metode-btn bg-gray-50 border-2 border-gray-200 text-gray-600 py-2 rounded-lg text-sm font-medium" data-metode="qris">
                            QRIS
                        </button>
                    </div>
                    <input type="hidden" name="metode_pembayaran" id="metodePembayaran" value="tunai">
                </div>

                <div id="areaTunai">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tunai</label>
                    <input type="number" name="tunai" id="tunai" class="w-full px-4 py-2 border border-gray-200 rounded-xl text-lg font-bold text-right" 
    placeholder="0" onkeyup="hitungKembalian(); updateTombolBayar();">
                    <div class="flex justify-between mt-1 text-sm">
                        <span class="text-gray-600">Kembalian</span>
                        <span class="font-bold text-green-600" id="displayKembalian">Rp 0</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <textarea name="catatan" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm"></textarea>
                </div>

                <input type="hidden" name="total_harga" id="inputSubtotal" value="0">
                <input type="hidden" name="pajak" id="inputPajak" value="0">
                <input type="hidden" name="total_bayar" id="inputTotal" value="0">
                <input type="hidden" name="kembalian" id="inputKembalian" value="0">

                <button type="submit" name="simpan_transaksi" value="1" id="btnBayar" disabled
                    class="w-full bg-gray-300 text-gray-500 py-3 rounded-xl font-bold transition-all cursor-not-allowed">
                    <i class="fas fa-cash-register mr-2"></i>Bayar
                </button>
            </form>
        </div>
    </div>
</main>

<script>
let keranjang = [];
let produkData = <?php echo json_encode($produk_all); ?>;

// Render produk saat load
document.addEventListener('DOMContentLoaded', function() {
    renderProduk('all');
});

function renderProduk(kategoriId, search = '') {
    const grid = document.getElementById('produkGrid');
    let filtered = produkData;
    
    if(kategoriId !== 'all') {
        filtered = filtered.filter(p => p.kategori_id == kategoriId);
    }
    
    if(search) {
        filtered = filtered.filter(p => 
            p.nama_produk.toLowerCase().includes(search.toLowerCase()) ||
            p.kode_produk.toLowerCase().includes(search.toLowerCase())
        );
    }
    
    grid.innerHTML = filtered.map(p => `
        <div onclick="tambahKeKeranjang(${p.id})" class="bg-white border-2 border-gray-100 rounded-xl p-3 cursor-pointer hover:border-blue-400 hover:shadow-lg transition-all">
            <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-gray-100">
                <img src="${p.foto || 'https://ui-avatars.com/api/?name='+encodeURIComponent(p.nama_produk)+'&background=random&size=200'}" class="w-full h-full object-cover">
            </div>
            <h4 class="font-medium text-gray-800 text-sm mb-1 line-clamp-2">${p.nama_produk}</h4>
            <div class="flex justify-between items-center">
                <span class="font-bold text-blue-600 text-sm">Rp ${parseInt(p.harga_jual).toLocaleString()}</span>
                <span class="text-xs text-gray-400">Stok: ${p.stok}</span>
            </div>
        </div>
    `).join('');
}

function filterKategori(kategoriId) {
    document.querySelectorAll('.kategori-tab').forEach(tab => {
        if(tab.dataset.kategori == kategoriId) {
            tab.classList.add('bg-blue-500', 'text-white');
            tab.classList.remove('bg-gray-100', 'text-gray-600');
        } else {
            tab.classList.remove('bg-blue-500', 'text-white');
            tab.classList.add('bg-gray-100', 'text-gray-600');
        }
    });
    renderProduk(kategoriId, document.getElementById('searchProduk').value);
}

function cariProduk(keyword) {
    const hasilDiv = document.getElementById('hasilPencarian');
    if(keyword.length < 2) {
        hasilDiv.classList.add('hidden');
        renderProduk('all');
        return;
    }
    
    const hasil = produkData.filter(p => 
        p.nama_produk.toLowerCase().includes(keyword.toLowerCase()) ||
        p.kode_produk.toLowerCase().includes(keyword.toLowerCase())
    );
    
    hasilDiv.innerHTML = hasil.map(p => `
        <div class="p-3 hover:bg-gray-50 cursor-pointer flex justify-between items-center" onclick="tambahKeKeranjang(${p.id}); document.getElementById('hasilPencarian').classList.add('hidden');">
            <div>
                <p class="font-medium text-gray-800">${p.nama_produk}</p>
                <p class="text-xs text-gray-500">Stok: ${p.stok}</p>
            </div>
            <span class="font-bold text-blue-600">Rp ${parseInt(p.harga_jual).toLocaleString()}</span>
        </div>
    `).join('');
    hasilDiv.classList.remove('hidden');
}

function tambahKeKeranjang(produkId) {
    const produk = produkData.find(p => p.id == produkId);
    const existing = keranjang.find(item => item.id == produkId);
    
    if(existing) {
        if(existing.qty < produk.stok) {
            existing.qty++;
        } else {
            alert('Stok tidak mencukupi!');
            return;
        }
    } else {
        keranjang.push({
            id: produk.id,
            nama: produk.nama_produk,
            harga: parseInt(produk.harga_jual),
            qty: 1,
            stok: produk.stok
        });
    }
    
    renderKeranjang();
    hitungTotal();
}

// FIX UTAMA: Render keranjang + generate hidden inputs di dalam form
function renderKeranjang() {
    const tbody = document.getElementById('keranjangBody');
    const hiddenContainer = document.getElementById('hiddenInputsContainer');
    const badge = document.getElementById('badgeKeranjang');
    
    if(keranjang.length === 0) {
        tbody.innerHTML = `
            <tr id="emptyRow">
                <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                    <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                    <p>Keranjang masih kosong</p>
                </td>
            </tr>`;
        hiddenContainer.innerHTML = ''; // Kosongkan hidden inputs
        badge.textContent = '0 item';
        updateTombolBayar();
        return;
    }
    
    // Render tabel keranjang
    tbody.innerHTML = keranjang.map((item, index) => `
        <tr>
            <td class="px-4 py-3">
                <p class="font-medium text-gray-800 text-sm">${item.nama}</p>
            </td>
            <td class="px-4 py-3 text-center">
                <div class="flex items-center justify-center gap-1">
                    <button type="button" onclick="ubahQty(${index}, -1)" class="w-6 h-6 bg-gray-100 rounded hover:bg-gray-200 text-xs">-</button>
                    <span class="w-8 text-center font-medium text-sm">${item.qty}</span>
                    <button type="button" onclick="ubahQty(${index}, 1)" class="w-6 h-6 bg-gray-100 rounded hover:bg-gray-200 text-xs">+</button>
                </div>
            </td>
            <td class="px-4 py-3 text-right font-medium text-sm">Rp ${(item.harga * item.qty).toLocaleString()}</td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="hapusItem(${index})" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash text-sm"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    // FIX: Generate hidden inputs untuk form submission
    let hiddenHtml = '';
    keranjang.forEach((item, index) => {
        hiddenHtml += `<input type="hidden" name="produk_id[]" value="${item.id}">`;
        hiddenHtml += `<input type="hidden" name="qty[]" value="${item.qty}">`;
        hiddenHtml += `<input type="hidden" name="harga_satuan[]" value="${item.harga}">`;
    });
    hiddenContainer.innerHTML = hiddenHtml;
    
    // Update badge
    const totalQty = keranjang.reduce((sum, item) => sum + item.qty, 0);
    badge.textContent = totalQty + ' item';
}

function ubahQty(index, delta) {
    const item = keranjang[index];
    const newQty = item.qty + delta;
    
    if(newQty > 0 && newQty <= item.stok) {
        item.qty = newQty;
        renderKeranjang();
        hitungTotal();
    } else if(newQty > item.stok) {
        alert('Stok tidak mencukupi! Maksimal: ' + item.stok);
    }
}

function hapusItem(index) {
    keranjang.splice(index, 1);
    renderKeranjang();
    hitungTotal();
}

function hitungTotal() {
    let subtotal = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    let diskon = parseInt(document.getElementById('diskon').value) || 0;
    let pajak = 0;
    
    if(document.getElementById('cbPajak').checked) {
        pajak = Math.round((subtotal - diskon) * 0.1);
    }
    
    let total = subtotal - diskon + pajak;
    
    document.getElementById('displaySubtotal').textContent = 'Rp ' + subtotal.toLocaleString();
    document.getElementById('displayTotal').textContent = 'Rp ' + total.toLocaleString();
    document.getElementById('inputSubtotal').value = subtotal;
    document.getElementById('inputPajak').value = pajak;
    document.getElementById('inputTotal').value = total;
    
    hitungKembalian();
    updateTombolBayar();
}

function hitungKembalian() {
    const total = parseInt(document.getElementById('inputTotal').value) || 0;
    const tunai = parseInt(document.getElementById('tunai').value) || 0;
    const kembalian = tunai - total;
    
    document.getElementById('displayKembalian').textContent = 'Rp ' + (kembalian > 0 ? kembalian : 0).toLocaleString();
    document.getElementById('inputKembalian').value = kembalian > 0 ? kembalian : 0;
}

function updateTombolBayar() {
    const btn = document.getElementById('btnBayar');
    const total = parseInt(document.getElementById('inputTotal').value) || 0;
    const tunai = parseInt(document.getElementById('tunai').value) || 0;
    const metode = document.getElementById('metodePembayaran').value;
    
    let isValid = keranjang.length > 0 && total > 0;
    if(metode === 'tunai') {
        isValid = isValid && tunai >= total;
    }
    
    if(isValid) {
        btn.disabled = false;
        btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
        btn.classList.add('bg-blue-500', 'text-white', 'hover:bg-blue-600');
    } else {
        btn.disabled = true;
        btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
        btn.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600');
    }
}

function setMetode(metode) {
    document.getElementById('metodePembayaran').value = metode;
    document.querySelectorAll('.metode-btn').forEach(btn => {
        if(btn.dataset.metode === metode) {
            btn.classList.add('bg-blue-50', 'border-blue-500', 'text-blue-700');
            btn.classList.remove('bg-gray-50', 'border-gray-200', 'text-gray-600');
        } else {
            btn.classList.remove('bg-blue-50', 'border-blue-500', 'text-blue-700');
            btn.classList.add('bg-gray-50', 'border-gray-200', 'text-gray-600');
        }
    });
    
    if(metode === 'tunai') {
        document.getElementById('areaTunai').classList.remove('hidden');
    } else {
        document.getElementById('areaTunai').classList.add('hidden');
        document.getElementById('tunai').value = document.getElementById('inputTotal').value;
        hitungKembalian();
    }
    updateTombolBayar();
}

// Validasi sebelum submit
document.getElementById('formTransaksi').addEventListener('submit', function(e) {
    if(keranjang.length === 0) {
        e.preventDefault();
        alert('Keranjang masih kosong!');
        return false;
    }
    
    // Cek hidden inputs
    const hiddenContainer = document.getElementById('hiddenInputsContainer');
    if(hiddenContainer.children.length === 0) {
        e.preventDefault();
        alert('Data keranjang tidak valid!');
        return false;
    }
    
    console.log('Submitting form with', keranjang.length, 'items');
});
</script>

<?php include 'includes/footer.php'; ?>