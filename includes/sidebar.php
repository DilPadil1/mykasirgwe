<aside class="fixed left-0 top-0 h-full w-64 bg-white shadow-xl z-50 overflow-y-auto">
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white font-bold text-xl">
                <i class="fas fa-cash-register"></i>
            </div>
            <div>
                <h1 class="font-bold text-gray-800 text-lg">POS Kasir</h1>
                <p class="text-xs text-gray-500">Sistem Manajemen</p>
            </div>
        </div>
    </div>

    <nav class="p-4 space-y-1">
        <a href="dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-gray-600 rounded-xl transition-all hover:text-primary <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 text-primary font-medium' : ''; ?>">
            <i class="fas fa-home w-5"></i>
            <span>Dashboard</span>
        </a>

        <a href="kasir.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-gray-600 rounded-xl transition-all hover:text-primary <?php echo basename($_SERVER['PHP_SELF']) == 'kasir.php' ? 'bg-blue-50 text-primary font-medium' : ''; ?>">
            <i class="fas fa-shopping-cart w-5"></i>
            <span>Kasir</span>
        </a>

        <div class="pt-4 pb-2 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Master Data</div>

        <a href="produk.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-gray-600 rounded-xl transition-all hover:text-primary <?php echo basename($_SERVER['PHP_SELF']) == 'produk.php' ? 'bg-blue-50 text-primary font-medium' : ''; ?>">
            <i class="fas fa-box w-5"></i>
            <span>Produk</span>
        </a>

        <a href="kategori.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-gray-600 rounded-xl transition-all hover:text-primary <?php echo basename($_SERVER['PHP_SELF']) == 'kategori.php' ? 'bg-blue-50 text-primary font-medium' : ''; ?>">
            <i class="fas fa-tags w-5"></i>
            <span>Kategori</span>
        </a>

        <div class="pt-4 pb-2 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Transaksi</div>

        <a href="transaksi.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-gray-600 rounded-xl transition-all hover:text-primary <?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'bg-blue-50 text-primary font-medium' : ''; ?>">
            <i class="fas fa-receipt w-5"></i>
            <span>Riwayat Transaksi</span>
        </a>

        <a href="laporan.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-gray-600 rounded-xl transition-all hover:text-primary <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'bg-blue-50 text-primary font-medium' : ''; ?>">
            <i class="fas fa-chart-bar w-5"></i>
            <span>Laporan</span>
        </a>

        <?php if($user['role'] == 'admin'): ?>
        <div class="pt-4 pb-2 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Sistem</div>

        <a href="users.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-gray-600 rounded-xl transition-all hover:text-primary <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-blue-50 text-primary font-medium' : ''; ?>">
            <i class="fas fa-users w-5"></i>
            <span>Pengguna</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-100 bg-white">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gray-50">
            <img src="<?php echo $user['foto'] ?? 'https://ui-avatars.com/api/?name='.urlencode($user['nama_lengkap']).'&background=3B82F6&color=fff'; ?>" class="w-10 h-10 rounded-full object-cover">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate"><?php echo $user['nama_lengkap']; ?></p>
                <p class="text-xs text-gray-500 capitalize"><?php echo $user['role']; ?></p>
            </div>
            <a href="logout.php" class="text-gray-400 hover:text-danger transition-colors">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>