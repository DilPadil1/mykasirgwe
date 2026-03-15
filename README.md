# 🛒 MyKasirGWE — Aplikasi POS Kasir Berbasis Web

![PHP](https://img.shields.io/badge/PHP-8.x-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-Database-orange?logo=mysql)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-Styling-38bdf8?logo=tailwindcss)
![License](https://img.shields.io/badge/License-MIT-green)

Aplikasi Point of Sale (POS) / Kasir berbasis web yang dibangun menggunakan PHP Native dan MySQL. Cocok digunakan untuk toko kecil hingga menengah.

---

## 📸 Screenshot

<table>
  <tr>
    <td align="center"><b>Dashboard</b></td>
    <td align="center"><b>Kasir / POS</b></td>
  </tr>
  <tr>
    <td><img src="https://c.top4top.io/p_3726g71f11.png" alt="Dashboard" width="100%"/></td>
    <td><img src="https://d.top4top.io/p_3726gfflz2.png" alt="Kasir" width="100%"/></td>
  </tr>
  <tr>
    <td align="center"><b>Produk</b></td>
    <td align="center"><b>Transaksi</b></td>
  </tr>
  <tr>
    <td><img src="https://e.top4top.io/p_37266y10g3.png" alt="Produk" width="100%"/></td>
    <td><img src="https://f.top4top.io/p_3726efqdi4.png" alt="Transaksi" width="100%"/></td>
  </tr>
  <tr>
    <td align="center" colspan="2"><b>Laporan</b></td>
  </tr>
  <tr>
    <td colspan="2" align="center"><img src="https://g.top4top.io/p_3726ldjpe5.png" alt="Laporan" width="50%"/></td>
  </tr>
</table>

---

## ✨ Fitur Utama

- 🛍️ **Kasir / POS** — Tambah produk ke keranjang, proses transaksi dengan mudah
- 🔍 **Pencarian & Filter Produk** — Cari produk berdasarkan nama atau kode, filter per kategori
- 💳 **Multi Metode Pembayaran** — Tunai, Debit, dan QRIS
- 🧾 **Cetak Struk** — Struk otomatis setelah transaksi berhasil
- 📦 **Manajemen Stok** — Stok otomatis berkurang saat transaksi
- 🏷️ **Diskon & Pajak** — Support diskon nominal dan pajak 10%
- 📊 **Laporan Transaksi** — Riwayat transaksi lengkap
- 🗂️ **Manajemen Kategori & Produk** — CRUD produk dan kategori
- 👤 **Autentikasi** — Login & session management

---

## 🖥️ Teknologi yang Digunakan

| Teknologi | Keterangan |
|---|---|
| PHP 8.x | Backend / Logic |
| MySQL | Database |
| Tailwind CSS | Styling / UI |
| Font Awesome | Icon |
| Vanilla JavaScript | Interaktivitas |
| XAMPP | Local Development Server |

---

## ⚙️ Cara Instalasi

### Prasyarat
- XAMPP / Laragon (PHP 8.x + MySQL)
- Browser modern (Chrome, Firefox, Edge)

### Langkah Instalasi

**1. Clone repository ini**
```bash
git clone https://github.com/DilPadil1/mykasirgwe.git
```

**2. Pindahkan ke folder htdocs**
```bash
# Pindahkan folder ke:
C:\xampp\htdocs\mykasirgwe
```

**3. Import database**
- Buka `phpMyAdmin` → http://localhost/phpmyadmin
- Buat database baru: `mykasirgwe`
- Import file `mykasirgwe.sql` yang ada di folder project

**4. Konfigurasi koneksi database**

Edit file `config/database.php`:
```php
$host = 'localhost';
$dbname = 'mykasirgwe';
$username = 'root';
$password = '';
```

**5. Jalankan aplikasi**

Buka browser dan akses:
```
http://localhost/mykasirgwe
```

---

## 📁 Struktur Folder

```
mykasirgwe/
├── assets/
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── sidebar.php
├── dashboard.php
├── kasir.php
├── produk.php
├── kategori.php
├── transaksi.php
├── laporan.php
├── struk.php
├── users.php
├── login.php
├── logout.php
├── .gitignore
├── LICENSE
└── README.md
```

---

## 🤝 Kontribusi

Pull request sangat terbuka! Untuk perubahan besar, harap buka issue terlebih dahulu.

1. Fork repository ini
2. Buat branch baru (`git checkout -b fitur-baru`)
3. Commit perubahan (`git commit -m 'Tambah fitur baru'`)
4. Push ke branch (`git push origin fitur-baru`)
5. Buat Pull Request

---

## 📄 Lisensi

Didistribusikan di bawah [MIT License](LICENSE). Bebas digunakan dan dimodifikasi dengan tetap mencantumkan kredit.

---

## 👨‍💻 Developer

**DilPadil1**  
- GitHub: [@DilPadil1](https://github.com/DilPadil1)

---

> ⭐ Jangan lupa kasih **star** kalau project ini membantu kamu!
