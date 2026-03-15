<?php
$title = 'Manajemen Produk';
require_once 'includes/header.php';

// Proses tambah/edit produk
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $kode_produk = $_POST['kode_produk'];
    $nama_produk = $_POST['nama_produk'];
    $kategori_id = $_POST['kategori_id'];
    $harga_beli = $_POST['harga_beli'];
    $harga_jual = $_POST['harga_jual'];
    $stok = $_POST['stok'];
    $min_stok = $_POST['min_stok'];
    $barcode = $_POST['barcode'];
    
    // Upload foto
    $foto = '';
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = 'assets/uploads/products/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_name = time() . '_' . $_FILES['foto']['name'];
        $foto = $upload_dir . $file_name;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto);
    }
    
    if($id) {
        // Update
        if($foto) {
            $stmt = $pdo->prepare("UPDATE produk SET kode_produk=?, nama_produk=?, kategori_id=?, harga_beli=?, harga_jual=?, stok=?, min_stok=?, foto=?, barcode=? WHERE id=?");
            $stmt->execute([$kode_produk, $nama_produk, $kategori_id, $harga_beli, $harga_jual, $stok, $min_stok, $foto, $barcode, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE produk SET kode_produk=?, nama_produk=?, kategori_id=?, harga_beli=?, harga_jual=?, stok=?, min_stok=?, barcode=? WHERE id=?");
            $stmt->execute([$kode_produk, $nama_produk, $kategori_id, $harga_beli, $harga_jual, $stok, $min_stok, $barcode, $id]);
        }
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO produk (kode_produk, nama_produk, kategori_id, harga_beli, harga_jual, stok, min_stok, foto, barcode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kode_produk, $nama_produk, $kategori_id, $harga_beli, $harga_jual, $stok, $min_stok, $foto, $barcode]);
    }
    
    header('Location: produk.php?success=1');
    exit;
}

// Hapus produk
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("UPDATE produk SET status = 0 WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: produk.php');
    exit;
}

// Ambil data
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';

$sql = "SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id WHERE p.status = 1";
$params = [];

if($search) {
    $sql .= " AND (p.nama_produk LIKE ? OR p.kode_produk LIKE ? OR p.barcode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($kategori_filter) {
    $sql .= " AND p.kategori_id = ?";
    $params[] = $kategori_filter;
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produk_list = $stmt->fetchAll();

$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
?>

<?php include 'includes/sidebar.php'; ?>

<main class="ml-64 p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manajemen Produk</h1>
            <p class="text-gray-500 mt-1">Kelola data produk dan stok</p>
        </div>
        <button onclick="openModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i>
            Tambah Produk
        </button>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg">
        <p class="text-green-700"><i class="fas fa-check-circle mr-2"></i>Data berhasil disimpan!</p>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 mb-6 shadow-sm border border-gray-100">
        <form method="GET" class="flex gap-4">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Cari produk..." 
                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <select name="kategori" class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                <option value="">Semua Kategori</option>
                <?php foreach($kategori_list as $k): ?>
                <option value="<?php echo $k['id']; ?>" <?php echo $kategori_filter == $k['id'] ? 'selected' : ''; ?>>
                    <?php echo $k['nama_kategori']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-xl transition-colors">
                <i class="fas fa-filter"></i>
            </button>
        </form>
    </div>

    <!-- Tabel Produk -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Produk</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Kode/Barcode</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Kategori</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">Harga Beli</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">Harga Jual</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">Stok</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach($produk_list as $p): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="<?php echo $p['foto'] ?? 'https://via.placeholder.com/50'; ?>" class="w-12 h-12 rounded-lg object-cover">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo $p['nama_produk']; ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <div><?php echo $p['kode_produk']; ?></div>
                        <?php if($p['barcode']): ?>
                        <div class="text-xs text-gray-400"><?php echo $p['barcode']; ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo $p['nama_kategori']; ?></td>
                    <td class="px-6 py-4 text-right text-sm text-gray-600"><?php echo rupiah($p['harga_beli']); ?></td>
                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-800"><?php echo rupiah($p['harga_jual']); ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $p['stok'] <= $p['min_stok'] ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                            <?php echo $p['stok']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center gap-2">
                            <button onclick="editProduk(<?php echo htmlspecialchars(json_encode($p)); ?>)" class="w-8 h-8 bg-blue-50 text-blue-500 rounded-lg hover:bg-blue-100 transition-colors">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $p['id']; ?>" onclick="return confirm('Yakin hapus produk ini?')" class="w-8 h-8 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition-colors flex items-center justify-center">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modal Form -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4 shadow-2xl transform transition-all">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Tambah Produk</h3>
            <button onclick="closeModal()" class="w-8 h-8 bg-gray-100 rounded-full hover:bg-gray-200 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="id" id="produkId">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kode Produk</label>
                    <input type="text" name="kode_produk" id="kodeProduk" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Barcode</label>
                    <input type="text" name="barcode" id="barcode" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Produk</label>
                <input type="text" name="nama_produk" id="namaProduk" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                    <select name="kategori_id" id="kategoriId" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                        <option value="">Pilih Kategori</option>
                        <?php foreach($kategori_list as $k): ?>
                        <option value="<?php echo $k['id']; ?>"><?php echo $k['nama_kategori']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stok Minimum</label>
                    <input type="number" name="min_stok" id="minStok" value="5" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Harga Beli</label>
                    <input type="number" name="harga_beli" id="hargaBeli" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Harga Jual</label>
                    <input type="number" name="harga_jual" id="hargaJual" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stok Awal</label>
                    <input type="number" name="stok" id="stok" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto Produk</label>
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors cursor-pointer" onclick="document.getElementById('fotoInput').click()">
                    <img id="previewFoto" class="mx-auto mb-2 h-32 object-cover rounded-lg hidden">
                    <div id="uploadPlaceholder">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-500">Klik untuk upload foto</p>
                    </div>
                    <input type="file" name="foto" id="fotoInput" accept="image/*" class="hidden" onchange="previewImage(this)">
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">Batal</button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-colors font-medium">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('modalTitle').textContent = 'Tambah Produk';
    document.getElementById('produkId').value = '';
    document.getElementById('kodeProduk').value = 'PRD' + Date.now().toString().slice(-6);
    document.querySelector('form').reset();
    document.getElementById('previewFoto').classList.add('hidden');
    document.getElementById('uploadPlaceholder').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
}

function editProduk(data) {
    openModal();
    document.getElementById('modalTitle').textContent = 'Edit Produk';
    document.getElementById('produkId').value = data.id;
    document.getElementById('kodeProduk').value = data.kode_produk;
    document.getElementById('namaProduk').value = data.nama_produk;
    document.getElementById('kategoriId').value = data.kategori_id;
    document.getElementById('hargaBeli').value = data.harga_beli;
    document.getElementById('hargaJual').value = data.harga_jual;
    document.getElementById('stok').value = data.stok;
    document.getElementById('minStok').value = data.min_stok;
    document.getElementById('barcode').value = data.barcode;
    
    if(data.foto) {
        document.getElementById('previewFoto').src = data.foto;
        document.getElementById('previewFoto').classList.remove('hidden');
        document.getElementById('uploadPlaceholder').classList.add('hidden');
    }
}

function previewImage(input) {
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewFoto').src = e.target.result;
            document.getElementById('previewFoto').classList.remove('hidden');
            document.getElementById('uploadPlaceholder').classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>