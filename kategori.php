<?php
$title = 'Manajemen Kategori';
require_once 'includes/header.php';

// Proses tambah/edit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $nama = $_POST['nama_kategori'];
    $deskripsi = $_POST['deskripsi'];
    
    if($id) {
        $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori=?, deskripsi=? WHERE id=?");
        $stmt->execute([$nama, $deskripsi, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, deskripsi) VALUES (?, ?)");
        $stmt->execute([$nama, $deskripsi]);
    }
    header('Location: kategori.php');
    exit;
}

// Hapus
if(isset($_GET['delete'])) {
    // Cek apakah kategori dipakai produk
    $cek = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kategori_id = ?");
    $cek->execute([$_GET['delete']]);
    if($cek->fetchColumn() > 0) {
        $error = "Kategori tidak bisa dihapus karena masih digunakan oleh produk!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header('Location: kategori.php');
        exit;
    }
}

$kategori_list = $pdo->query("SELECT k.*, COUNT(p.id) as jumlah_produk FROM kategori k LEFT JOIN produk p ON k.id = p.kategori_id GROUP BY k.id ORDER BY k.nama_kategori")->fetchAll();
?>

<?php include 'includes/sidebar.php'; ?>

<main class="ml-64 p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Kategori Produk</h1>
            <p class="text-gray-500 mt-1">Kelompokkan produk berdasarkan kategori</p>
        </div>
        <button onclick="openModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i>Tambah Kategori
        </button>
    </div>

    <?php if(isset($error)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
        <p class="text-red-700"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></p>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($kategori_list as $k): ?>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-purple-500 rounded-xl flex items-center justify-center text-white">
                    <i class="fas fa-folder text-xl"></i>
                </div>
                <div class="flex gap-2">
                    <button onclick="editKategori(<?php echo htmlspecialchars(json_encode($k)); ?>)" class="w-8 h-8 bg-blue-50 text-blue-500 rounded-lg hover:bg-blue-100">
                        <i class="fas fa-edit"></i>
                    </button>
                    <a href="?delete=<?php echo $k['id']; ?>" onclick="return confirm('Yakin hapus kategori ini?')" class="w-8 h-8 bg-red-50 text-red-500 rounded-lg hover:bg-red-100 flex items-center justify-center">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
            <h3 class="font-bold text-gray-800 text-lg mb-1"><?php echo $k['nama_kategori']; ?></h3>
            <p class="text-gray-500 text-sm mb-4 line-clamp-2"><?php echo $k['deskripsi'] ?: 'Tidak ada deskripsi'; ?></p>
            <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 px-3 py-2 rounded-lg">
                <i class="fas fa-box"></i>
                <span><?php echo $k['jumlah_produk']; ?> produk</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl w-full max-w-md m-4 shadow-2xl">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Tambah Kategori</h3>
            <button onclick="closeModal()" class="w-8 h-8 bg-gray-100 rounded-full hover:bg-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id" id="kategoriId">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kategori</label>
                <input type="text" name="nama_kategori" id="namaKategori" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50">Batal</button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-500 text-white rounded-xl hover:bg-blue-600 font-medium">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('modalTitle').textContent = 'Tambah Kategori';
    document.getElementById('kategoriId').value = '';
    document.getElementById('namaKategori').value = '';
    document.getElementById('deskripsi').value = '';
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
}

function editKategori(data) {
    openModal();
    document.getElementById('modalTitle').textContent = 'Edit Kategori';
    document.getElementById('kategoriId').value = data.id;
    document.getElementById('namaKategori').value = data.nama_kategori;
    document.getElementById('deskripsi').value = data.deskripsi;
}
</script>

<?php include 'includes/footer.php'; ?>