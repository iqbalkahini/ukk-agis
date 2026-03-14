<?php
session_start();
require_once('../config/config.php');
checkLevel([2]);

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        if ($angka === null || $angka === '') return 'Rp 0';
        return 'Rp ' . number_format((float) $angka, 0, ',', '.');
    }
}

if (!function_exists('formatTanggal')) {
    function formatTanggal($tanggal) {
        if (!$tanggal || $tanggal === '0000-00-00') return '-';
        return date('d/m/Y', strtotime($tanggal));
    }
}

if (!function_exists('resolveBarangImageUrl')) {
    function resolveBarangImageUrl($filename) {
        if (!$filename) return null;
        $paths = [
            ['file' => '../barang/' . $filename, 'url' => '../barang/' . $filename],
            ['file' => '../uploads/barang/' . $filename, 'url' => '../uploads/barang/' . $filename],
            ['file' => '../uploads/' . $filename, 'url' => '../uploads/' . $filename],
        ];
        foreach ($paths as $path) {
            if (file_exists($path['file'])) return $path['url'];
        }
        return null;
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $check = mysqli_query($conn, "SELECT * FROM tb_lelang WHERE id_barang = $id AND status = 'dibuka'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Barang sedang dalam lelang aktif!'); window.location='data_barang.php';</script>";
        exit;
    }

    $barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM tb_barang WHERE id_barang = $id"));
    if (!empty($barang['gambar'])) {
        $candidate_paths = [
            '../barang/' . $barang['gambar'],
            '../uploads/barang/' . $barang['gambar'],
            '../uploads/' . $barang['gambar'],
        ];
        foreach ($candidate_paths as $path) {
            if (file_exists($path)) {
                unlink($path);
                break;
            }
        }
    }

    mysqli_query($conn, "DELETE FROM tb_barang WHERE id_barang = $id");
    $_SESSION['success'] = 'Data barang berhasil dihapus';
    header('Location: data_barang.php');
    exit;
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = [];
if ($search !== '') {
    $where[] = "(nama_barang LIKE '%$search%' OR deskripsi_barang LIKE '%$search%')";
}
if ($status !== '') {
    $where[] = "status_barang = '$status'";
}
$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$barang = mysqli_query($conn, "SELECT * FROM tb_barang $where_clause ORDER BY id_barang DESC");
$total_barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang"))['total'];
$lelang_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE status='dibuka'"))['total'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang WHERE status_barang='pending'"))['total'];
$filtered_total = $barang ? mysqli_num_rows($barang) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Petugas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
        :root { --primary-50:#eef2f8; --primary-100:#d9e2f0; --primary-200:#b3c5e1; --primary-300:#8da8d2; --primary-400:#678bc3; --primary-500:#416eb4; --primary-600:#2a4f8c; --primary-700:#1e3a66; --primary-800:#132a4a; --secondary-50:#f8fafc; }
        body { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: 100vh; overflow-x: hidden; }
        .gradient-bg { background: linear-gradient(135deg, var(--primary-700), var(--primary-600), var(--primary-500)); }
        .glass { background: rgba(255,255,255,0.88); backdrop-filter: blur(12px); border: 1px solid rgba(65,110,180,0.15); }
        .card { background: white; border-radius: 24px; border: 1px solid var(--primary-100); box-shadow: 0 10px 25px -5px rgba(30,58,102,0.08); }
        .stat-card { background: white; border-radius: 24px; padding: 1.5rem; border: 1px solid var(--primary-100); box-shadow: 0 10px 25px -5px rgba(30,58,102,0.08); transition: .3s ease; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 25px 35px -8px rgba(30,58,102,0.15); }
        .modern-table { border-collapse: separate; border-spacing: 0 12px; }
        .modern-table tbody tr { background: white; box-shadow: 0 4px 12px -2px rgba(30,58,102,0.06); transition: .3s ease; }
        .modern-table tbody tr:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -8px rgba(30,58,102,0.15); }
        .badge { padding: 6px 14px; border-radius: 999px; font-size: .75rem; font-weight: 600; display: inline-flex; align-items: center; }
        .badge-success { background:#e6f7e6; color:#0a5e0a; border:1px solid #a3e0a3; }
        .badge-danger { background:#ffe6e6; color:#a12323; border:1px solid #ffb8b8; }
        .badge-warning { background:#fff8e1; color:#7a5500; border:1px solid #ffe082; }
        .btn-primary { background: var(--primary-600); color: white; padding: .8rem 1.4rem; border-radius: 14px; font-weight: 600; display: inline-flex; align-items: center; transition: .3s ease; }
        .btn-primary:hover { background: var(--primary-700); transform: translateY(-2px); }
        .btn-outline { background: white; color: var(--primary-700); border: 1px solid var(--primary-200); padding: .8rem 1.2rem; border-radius: 14px; font-weight: 600; display: inline-flex; align-items: center; transition: .3s ease; }
        .btn-outline:hover { background: var(--primary-50); }
        .form-input { width: 100%; padding: .85rem 1rem; border: 1px solid var(--primary-200); border-radius: 14px; background: white; }
        .form-input:focus { outline: none; border-color: var(--primary-400); box-shadow: 0 0 0 3px rgba(65,110,180,.12); }
        .particle { position: fixed; width: 4px; height: 4px; background: var(--primary-300); border-radius: 50%; opacity: .25; animation: particleFloat 14s linear infinite; }
        @keyframes particleFloat { from { transform: translateY(100vh) rotate(0deg); } to { transform: translateY(-100vh) rotate(360deg); } }
    </style>
</head>
<body class="antialiased text-gray-800">
<div id="particles"></div>
<div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

<nav class="glass sticky top-0 z-30 border-b shadow-sm" style="border-color:var(--primary-100)">
    <div class="w-full px-0 flex">
        <div class="flex justify-between h-16 w-full">
            <div class="flex items-center" style="width:288px;min-width:288px;padding-left:1.5rem">
                <div class="gradient-bg p-2.5 rounded-xl shadow-lg flex items-center justify-center" style="min-width:40px;min-height:40px">
                    <i class="fas fa-gavel text-white text-lg"></i>
                </div>
                <div class="flex flex-col leading-tight ml-3">
                    <span class="font-extrabold text-lg tracking-tight" style="background:linear-gradient(135deg,var(--primary-700),var(--primary-500));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1.2">Sistem Lelang</span>
                    <span class="text-xs font-semibold tracking-widest uppercase" style="color:var(--primary-400);letter-spacing:0.18em;line-height:1.2">Online<span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400 ml-1.5 mb-0.5 animate-pulse align-middle"></span></span>
                </div>
            </div>
            <div class="flex items-center space-x-4 pr-6">
                <div class="flex items-center space-x-3 ml-2 pl-2 border-l-2" style="border-color:var(--primary-100)">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold" style="color:var(--primary-800)"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
                        <p class="text-xs flex items-center justify-end" style="color:var(--primary-500)">
                            <i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>Petugas
                        </p>
                    </div>
                    <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white font-bold shadow-md ring-2 hover:scale-110 transition-transform" style="ring-color:var(--primary-100)">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                    </div>
                </div>
                <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="relative overflow-hidden group text-sm py-2 px-4 flex items-center space-x-2 rounded-xl border transition-all hover:scale-105" style="color:var(--primary-600);border-color:var(--primary-200)">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="hidden sm:inline">Keluar</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="flex relative">
    <aside class="w-72 bg-white border-r min-h-screen shadow-xl relative overflow-hidden" style="border-color:var(--primary-100)">
        <div class="absolute top-0 left-0 w-full h-1 gradient-bg"></div>
        <div class="p-6 relative z-10">
            <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group" data-aos="fade-right">
                <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-8 -mb-8 group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex items-center space-x-4 relative z-10">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl font-bold border-2 border-white/30 group-hover:scale-110 transition-transform">
                        <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-white/80 text-sm font-light">Selamat datang,</p>
                        <p class="text-xl font-bold group-hover:translate-x-1 transition-transform"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
                        <p class="text-white/80 text-xs mt-1 flex items-center">
                            <i class="fas fa-circle text-green-300 text-[6px] mr-2 animate-pulse"></i>Sedang Aktif
                        </p>
                    </div>
                </div>
            </div>
            <nav class="space-y-1">
                <p class="text-xs uppercase tracking-wider mb-3 px-4 font-semibold" style="color:var(--primary-300)">Menu Utama</p>
                <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)"><i class="fas fa-home w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i><span class="ml-3 group-hover:translate-x-1 transition-transform">Beranda</span></a>
                <a href="data_barang.php" class="flex items-center px-4 py-3.5 gradient-bg text-white rounded-xl shadow-lg transition-all duration-200 group relative overflow-hidden"><i class="fas fa-box w-6 text-white"></i><span class="ml-3 font-medium">Data Barang</span><i class="fas fa-chevron-right ml-auto text-sm opacity-0 group-hover:opacity-100 transition-all"></i><span class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity"></span></a>
                <a href="kelola_lelang.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)"><i class="fas fa-gavel w-6 group-hover:rotate-12 transition-transform" style="color:var(--primary-400)"></i><span class="ml-3 group-hover:translate-x-1 transition-transform">Kelola Lelang</span></a>
                <a href="pembayaran.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)"><i class="fas fa-credit-card w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i><span class="ml-3 group-hover:translate-x-1 transition-transform">Pembayaran</span></a>
                <a href="laporan.php" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group hover:bg-blue-50" style="color:var(--primary-700)"><i class="fas fa-chart-bar w-6 group-hover:scale-110 transition-transform" style="color:var(--primary-400)"></i><span class="ml-3 group-hover:translate-x-1 transition-transform">Laporan</span></a>
            </nav>
        </div>
        <div class="absolute bottom-6 left-6 right-6">
            <div class="rounded-xl p-4 border hover:shadow-lg transition-all hover:scale-105 group" style="background:var(--primary-50);border-color:var(--primary-100)">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm group-hover:rotate-12 transition-transform">
                        <i class="fas fa-headset text-lg" style="color:var(--primary-500)"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold" style="color:var(--primary-800)">Butuh Bantuan?</p>
                        <p class="text-xs flex items-center" style="color:var(--primary-500)"><i class="fas fa-envelope mr-1"></i>admin@lelang.com</p>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t" style="border-color:var(--primary-100)">
                    <p class="text-xs text-center" style="color:var(--primary-400)"><i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>Dukungan 24/7</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 p-8" style="background:var(--secondary-50)">
        <div class="gradient-bg rounded-3xl p-8 text-white shadow-xl mb-8" data-aos="fade-down">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold mb-2"><i class="fas fa-box mr-3"></i>Data Barang</h1>
                    <p class="text-blue-100">Halaman ini menampilkan daftar barang yang siap dikelola oleh petugas.</p>
                </div>
                <a href="tambah_barang.php" class="bg-white/15 hover:bg-white/25 text-white px-6 py-3 rounded-2xl font-semibold border border-white/25 inline-flex items-center transition-all">
                    <i class="fas fa-plus mr-2"></i>Tambah Barang
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card" data-aos="fade-up">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm" style="color:var(--primary-500)">Total Barang</p>
                        <p class="text-3xl font-bold" style="color:var(--primary-800)"><?php echo $total_barang; ?></p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:var(--primary-50)"><i class="fas fa-box text-2xl" style="color:var(--primary-600)"></i></div>
                </div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm" style="color:var(--primary-500)">Sedang Dilelang</p>
                        <p class="text-3xl font-bold text-green-700"><?php echo $lelang_aktif; ?></p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#dcfce7"><i class="fas fa-gavel text-2xl text-green-600"></i></div>
                </div>
            </div>
            <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm" style="color:var(--primary-500)">Pending</p>
                        <p class="text-3xl font-bold text-yellow-700"><?php echo $pending; ?></p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:#fff8e1"><i class="fas fa-clock text-2xl text-yellow-600"></i></div>
                </div>
            </div>
        </div>

        <div class="card p-6 mb-6" data-aos="fade-up">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Cari Barang</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-input" placeholder="Cari nama barang atau deskripsi">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Status</label>
                    <select name="status" class="form-input">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="dibuka" <?php echo $status === 'dibuka' ? 'selected' : ''; ?>>Dibuka</option>
                        <option value="ditutup" <?php echo $status === 'ditutup' ? 'selected' : ''; ?>>Ditutup</option>
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit" class="btn-primary"><i class="fas fa-filter mr-2"></i>Filter</button>
                    <a href="data_barang.php" class="btn-outline"><i class="fas fa-rotate-right mr-2"></i>Reset</a>
                </div>
            </form>
        </div>

        <div class="card overflow-hidden" data-aos="zoom-in">
            <div class="p-6 border-b flex flex-col md:flex-row md:items-center md:justify-between gap-3" style="border-color:var(--primary-100); background:linear-gradient(to right,var(--primary-50),white)">
                <div>
                    <h2 class="text-xl font-bold" style="color:var(--primary-800)"><i class="fas fa-list mr-2" style="color:var(--primary-500)"></i>Daftar Barang</h2>
                    <p class="text-sm mt-1" style="color:var(--primary-500)"><?php echo $filtered_total; ?> data ditampilkan</p>
                </div>
                <a href="tambah_barang.php" class="btn-primary"><i class="fas fa-plus mr-2"></i>Tambah Barang</a>
            </div>

            <div class="overflow-x-auto p-4">
                <table class="w-full modern-table">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">No</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Gambar</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Nama Barang</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Harga Awal</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider" style="color:var(--primary-600)">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($barang && mysqli_num_rows($barang) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($barang)): ?>
                            <?php $image_url = resolveBarangImageUrl($row['gambar'] ?? ''); ?>
                            <tr>
                                <td class="px-6 py-4 font-medium" style="color:var(--primary-700)"><?php echo $no++; ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($image_url): ?>
                                        <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($row['nama_barang']); ?>" class="w-14 h-14 object-cover rounded-xl border" style="border-color:var(--primary-200)">
                                    <?php else: ?>
                                        <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background:var(--primary-50)"><i class="fas fa-image" style="color:var(--primary-400)"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold" style="color:var(--primary-800)"><?php echo htmlspecialchars($row['nama_barang']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(mb_strimwidth($row['deskripsi_barang'] ?? '-', 0, 60, '...')); ?></p>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?php echo formatTanggal($row['tgl']); ?></td>
                                <td class="px-6 py-4 font-semibold" style="color:var(--primary-700)"><?php echo formatRupiah($row['harga_awal']); ?></td>
                                <td class="px-6 py-4">
                                    <?php if (($row['status_barang'] ?? '') === 'dibuka'): ?>
                                        <span class="badge badge-success"><span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>Dibuka</span>
                                    <?php elseif (($row['status_barang'] ?? '') === 'ditutup'): ?>
                                        <span class="badge badge-danger"><i class="fas fa-lock mr-1"></i>Ditutup</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="tambah_barang.php?edit=<?php echo $row['id_barang']; ?>" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:scale-110" style="background:var(--primary-100);color:var(--primary-700)" title="Edit">
                                            <i class="fas fa-edit text-sm"></i>
                                        </a>
                                        <a href="?delete=<?php echo $row['id_barang']; ?>" onclick="return confirm('Yakin ingin menghapus barang ini?')" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:scale-110" style="background:#fee2e2;color:#dc2626" title="Hapus">
                                            <i class="fas fa-trash text-sm"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 rounded-full flex items-center justify-center mb-4" style="background:var(--primary-50)"><i class="fas fa-box-open text-3xl" style="color:var(--primary-300)"></i></div>
                                        <p class="font-semibold text-lg" style="color:var(--primary-600)">Belum ada data barang</p>
                                        <p class="text-sm text-gray-500 mt-1">Silakan tambahkan barang baru dari halaman form.</p>
                                        <a href="tambah_barang.php" class="btn-primary mt-4"><i class="fas fa-plus mr-2"></i>Tambah Barang</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 700, once: false, mirror: true, offset: 40, easing: 'ease-out-cubic' });
    (function() {
        const c = document.getElementById('particles');
        if (!c) return;
        for (let i = 0; i < 18; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.left = Math.random() * 100 + '%';
            p.style.animationDuration = (Math.random() * 10 + 10) + 's';
            p.style.animationDelay = Math.random() * 5 + 's';
            p.style.width = p.style.height = (Math.random() * 6 + 2) + 'px';
            c.appendChild(p);
        }
    })();

    function showToast(message, type) {
        const toast = document.createElement('div');
        const colors = type === 'error' ? 'bg-red-50 text-red-800 border-red-200' : 'bg-green-50 text-green-800 border-green-200';
        const icon = type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check';
        toast.className = `flex items-center p-4 rounded-xl shadow-lg text-sm border ${colors}`;
        toast.innerHTML = `<i class="fas ${icon} mr-3"></i><span>${message}</span>`;
        document.getElementById('toastContainer').appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
</script>
<?php if (isset($_SESSION['success'])): ?>
<script>showToast('<?php echo addslashes($_SESSION['success']); ?>', 'success');</script>
<?php unset($_SESSION['success']); endif; ?>
</body>
</html>
