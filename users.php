<?php
$title = 'Manajemen Pengguna';
require_once 'includes/header.php';

// Cek admin
if($user['role'] != 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Proses tambah/edit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    // Upload foto
    $foto = '';
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = 'assets/uploads/users/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_name = time() . '_' . $_FILES['foto']['name'];
        $foto = $upload_dir . $file_name;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto);
    }
    
    if($id) {
        // Update
        if($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if($foto) {
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, nama_lengkap=?, role=?, foto=? WHERE id=?");
                $stmt->execute([$username, $hash, $nama_lengkap, $role, $foto, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, nama_lengkap=?, role=? WHERE id=?");
                $stmt->execute([$username, $hash, $nama_lengkap, $role, $id]);
            }
        } else {
            if($foto) {
                $stmt = $pdo->prepare("UPDATE users SET username=?, nama_lengkap=?, role=?, foto=? WHERE id=?");
                $stmt->execute([$username, $nama_lengkap, $role, $foto, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, nama_lengkap=?, role=? WHERE id=?");
                $stmt->execute([$username, $nama_lengkap, $role, $id]);
            }
        }
    } else {
        // Insert
        $hash = password_hash($password ?: 'password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, foto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $nama_lengkap, $role, $foto]);
    }
    header('Location: users.php');
    exit;
}

// Toggle status
if(isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE users SET status = NOT status WHERE id = ?");
    $stmt->execute([$_GET['toggle']]);
    header('Location: users.php');
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<?php include 'includes/sidebar.php'; ?>

<main class="ml-64 p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manajemen Pengguna</h1>
            <p class="text-gray-500 mt-1">Kelola akun kasir dan admin</p>
        </div>
        <button onclick="openModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-xl font-medium transition-colors flex items-center gap-2">
            <i class="fas fa-plus"></i>Tambah Pengguna
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Pengguna</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">Username</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">Role</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">Status</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach($users as $u): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="<?php echo $u['foto'] ?? 'https://ui-avatars.com/api/?name='.urlencode($u['nama_lengkap']).'&background=3B82F6&color=fff'; ?>" class="w-10 h-10 rounded-full object-cover">
                            <span class="font-medium text-gray-800"><?php echo $u['nama_lengkap']; ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo $u['username']; ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-medium capitalize <?php echo $u['role'] == 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                            <?php echo $u['role']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="?toggle=<?php echo $u['id']; ?>" class="w-12 h-6 rounded-full transition-colors relative inline-block <?php echo $u['status'] ? 'bg-green-500' : 'bg-gray-300'; ?>">
                            <span class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all <?php echo $u['status'] ? 'left-7' : 'left-1'; ?>"></span>
                        </a>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)" class="w-8 h-8 bg-blue-50 text-blue-500 rounded-lg hover:bg-blue-100 transition-colors">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modal -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl w-full max-w-md m-4 shadow-2xl">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Tambah Pengguna</h3>
            <button onclick="closeModal()" class="w-8 h-8 bg-gray-100 rounded-full hover:bg-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="id" id="userId">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="namaLengkap" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input type="text" name="username" id="username" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password <span id="passHint" class="text-xs text-gray-400">(Kosongkan jika tidak diubah)</span></label>
                <input type="password" name="password" id="password" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select name="role" id="role" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    <option value="kasir">Kasir</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto</label>
                <input type="file" name="foto" accept="image/*" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
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
    document.getElementById('modalTitle').textContent = 'Tambah Pengguna';
    document.getElementById('userId').value = '';
    document.getElementById('passHint').textContent = '';
    document.getElementById('password').required = true;
    document.querySelector('form').reset();
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
}

function editUser(data) {
    openModal();
    document.getElementById('modalTitle').textContent = 'Edit Pengguna';
    document.getElementById('userId').value = data.id;
    document.getElementById('namaLengkap').value = data.nama_lengkap;
    document.getElementById('username').value = data.username;
    document.getElementById('role').value = data.role;
    document.getElementById('password').required = false;
    document.getElementById('passHint').textContent = '(Kosongkan jika tidak diubah)';
}
</script>

<?php include 'includes/footer.php'; ?>