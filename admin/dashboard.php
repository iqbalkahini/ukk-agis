<?php
session_start();
require_once('../config/config.php');
checkLevel([1]); // Hanya admin yang bisa akses halaman ini

// Handle delete barang
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Cek apakah barang ada di lelang
    $check = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE id_barang = $id");
    $cek = mysqli_fetch_assoc($check);
    
    if($cek['total'] > 0) {
        echo "<script>alert('Barang tidak dapat dihapus karena sudah masuk dalam data lelang!'); window.location='dashboard.php';</script>";
        exit;
    }
    
    // Hapus gambar jika ada
    $barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM tb_barang WHERE id_barang=$id"));
    if($barang['gambar']) {
        $paths = ['../barang/', '../uploads/barang/', '../uploads/'];
        foreach($paths as $p) {
            if(file_exists($p . $barang['gambar'])) {
                unlink($p . $barang['gambar']);
            }
        }
    }
    
    mysqli_query($conn, "DELETE FROM tb_barang WHERE id_barang = $id");
    header('Location: dashboard.php'); exit;
}

// Handle tambah barang
if(isset($_POST['tambah_barang'])) {
    if(empty($_POST['harga_awal']) || $_POST['harga_awal'] <= 0) {
        echo "<script>alert('Harga barang tidak boleh kosong!'); window.location='dashboard.php';</script>";
        exit;
    }
    
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $tgl = date('Y-m-d');
    $harga = (int)$_POST['harga_awal'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi_barang']);
    $status = 'tunggu';
    
    $gambar = '';
    if($_FILES['gambar']['name']) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = time() . '.' . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], '../uploads/barang/' . $gambar);
    }
    
    mysqli_query($conn, "INSERT INTO tb_barang (nama_barang, tgl, harga_awal, deskripsi_barang, gambar, status_barang) VALUES ('$nama','$tgl',$harga,'$deskripsi','$gambar','$status')");
    header('Location: dashboard.php'); exit;
}

// Handle perbarui barang
if(isset($_POST['perbarui_barang'])) {
    if(empty($_POST['harga_awal']) || $_POST['harga_awal'] <= 0) {
        echo "<script>alert('Harga barang tidak boleh kosong!'); window.location='dashboard.php';</script>";
        exit;
    }
    
    $id = (int)$_POST['id_barang'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $harga = (int)$_POST['harga_awal'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi_barang']);
    $status = mysqli_real_escape_string($conn, $_POST['status_barang']);
    
    $img_query = "";
    if($_FILES['gambar']['name']) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = time() . '.' . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], '../uploads/barang/' . $gambar);
        $img_query = ", gambar='$gambar'";
    }
    
    mysqli_query($conn, "UPDATE tb_barang SET nama_barang='$nama', harga_awal=$harga, deskripsi_barang='$deskripsi', status_barang='$status' $img_query WHERE id_barang=$id");
    header('Location: dashboard.php'); exit;
}

// Search & Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = "WHERE 1=1";
if($search) $where .= " AND (nama_barang LIKE '%$search%' OR deskripsi_barang LIKE '%$search%')";
if($filter_status) $where .= " AND status_barang = '$filter_status'";

$barang_list = mysqli_query($conn, "SELECT * FROM tb_barang $where ORDER BY id_barang DESC");

// Statistics
$total_barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang"))['total'];
$tunggu_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang WHERE status_barang = 'tunggu'"))['total'];
$dibuka_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang WHERE status_barang = 'dibuka'"))['total'];
$ditutup_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_barang WHERE status_barang = 'ditutup'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Barang - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Plus Jakarta Sans', sans-serif; margin:0; padding:0; box-sizing:border-box; }
        :root { --primary-50:#eef2f8; --primary-100:#d9e2f0; --primary-200:#b3c5e1; --primary-300:#8da8d2; --primary-400:#678bc3; --primary-500:#416eb4; --primary-600:#2a4f8c; --primary-700:#1e3a66; --primary-800:#132a4a; }
        body { background: linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%); min-height:100vh; overflow-x:hidden; }
        .gradient-bg { background:linear-gradient(135deg,var(--primary-700),var(--primary-600),var(--primary-500)); background-size:200% 200%; animation:gradientShift 8s ease infinite; }
        @keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
        .glass { background:rgba(255,255,255,0.85); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); border:1px solid rgba(65,110,180,0.15); }
        .stat-card { background:white; border-radius:24px; padding:1.5rem; box-shadow:0 10px 25px -5px rgba(30,58,102,0.08); border:1px solid var(--primary-100); transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275); position:relative; overflow:hidden; }
        .stat-card:hover { transform:translateY(-8px) scale(1.02); box-shadow:0 25px 35px -8px rgba(30,58,102,0.2); }
        .animate-float { animation:float 3s ease-in-out infinite; }
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
        .badge { padding:5px 12px; border-radius:100px; font-size:0.72rem; font-weight:600; display:inline-flex; align-items:center; }
        .badge-success { background:#e6f7e6; color:#0a5e0a; border:1px solid #a3e0a3; }
        .badge-danger { background:#ffe6e6; color:#a12323; border:1px solid #ffb8b8; }
        .badge-warning { background:#fff8e1; color:#7a5500; border:1px solid #ffe082; }
        .btn-primary { background:var(--primary-600); color:white; padding:0.65rem 1.4rem; border-radius:12px; font-weight:600; transition:all 0.3s ease; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 12px 20px -8px rgba(30,58,102,0.4); }
        .form-input { width:100%; border:1.5px solid var(--primary-200); border-radius:12px; padding:0.6rem 1rem; font-size:0.9rem; transition:all 0.2s; outline:none; }
        .form-input:focus { border-color:var(--primary-500); box-shadow:0 0 0 3px rgba(65,110,180,0.15); }
        .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:100; display:none; align-items:center; justify-content:center; }
        .modal-backdrop.active { display:flex; }
        .modal-box { background:white; border-radius:24px; padding:2rem; width:100%; max-width:520px; max-height:90vh; overflow-y:auto; box-shadow:0 30px 60px -15px rgba(30,58,102,0.4); animation:modalIn 0.3s ease; }
        @keyframes modalIn{from{opacity:0;transform:scale(0.92) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
    </style>
</head>
<body class="antialiased text-gray-800">
    <!-- Navbar -->
    <nav class="glass sticky top-0 z-30 border-b border-primary-100 shadow-sm">
        <div class="w-full px-0 flex">
            <div class="flex justify-between h-16 w-full">
                <div class="flex items-center" style="width:288px;min-width:288px;padding-left:1.5rem">
                    <div class="gradient-bg p-2.5 rounded-xl shadow-lg animate-float flex items-center justify-center" style="min-width:40px;min-height:40px">
                        <i class="fas fa-gavel text-white text-lg"></i>
                    </div>
                    <div class="flex flex-col leading-tight ml-3">
                        <span class="font-extrabold text-lg tracking-tight" style="background: linear-gradient(135deg, var(--primary-700), var(--primary-500)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height:1.2">Sistem Lelang</span>
                        <span class="text-xs font-semibold tracking-widest uppercase" style="color: var(--primary-400); letter-spacing: 0.18em; line-height:1.2">Online<span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400 ml-1.5 mb-0.5 animate-pulse align-middle"></span></span>
                    </div>
                </div>
                <div class="flex items-center space-x-4 pr-6">
                    <!-- User Menu -->
                    <div class="flex items-center space-x-3 ml-2 pl-2 border-l-2 border-primary-100">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-primary-800"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?></p>
                            <p class="text-xs text-primary-500 flex items-center justify-end">
                                <i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>
                                Administrator
                            </p>
                        </div>
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white font-bold shadow-md ring-2 ring-primary-100 hover:scale-110 transition-transform">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?>
                        </div>
                    </div>
                    
                    <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="text-sm py-2 px-4 rounded-xl border border-gray-200 hover:bg-gray-50 flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex relative z-10">
        <!-- Sidebar -->
        <aside class="w-72 bg-white border-r border-primary-100 min-h-screen shadow-xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 gradient-bg"></div>
            <div class="p-6 relative z-10">
                <!-- Profile Card -->
                <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-8 -mb-8 group-hover:scale-150 transition-transform duration-700"></div>
                    
                    <div class="flex items-center space-x-4 relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl font-bold border-2 border-white/30 group-hover:scale-110 transition-transform">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div>
                            <p class="text-white/80 text-sm font-light">Selamat datang,</p>
                            <p class="text-xl font-bold group-hover:translate-x-1 transition-transform"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?></p>
                            <p class="text-white/80 text-xs mt-1 flex items-center">
                                <i class="fas fa-circle text-green-300 text-[6px] mr-2 animate-pulse"></i>
                                Sedang Aktif
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation -->
                <nav class="space-y-2">
                    <p class="text-xs uppercase tracking-wider text-primary-300 mb-4 px-4 font-semibold uppercase">Menu Utama</p>
                    
                    <a href="dashboard.php" class="flex items-center px-4 py-3 bg-primary-50 text-primary-700 rounded-xl font-bold group relative overflow-hidden">
                        <i class="fas fa-home w-6 text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Beranda</span>
                        <i class="fas fa-chevron-right ml-auto text-sm text-primary-600"></i>
                    </a>
                    <a href="pengguna.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group relative overflow-hidden">
                        <i class="fas fa-users w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Pengguna</span>
                    </a>
                    <a href="laporan.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group relative overflow-hidden">
                        <i class="fas fa-chart-bar w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Laporan</span>
                    </a>
                </nav>
            </div>
            
            <!-- Sidebar Footer -->
            <div class="absolute bottom-6 left-6 right-6">
                <div class="bg-primary-50 rounded-xl p-4 border border-primary-100 hover:shadow-lg transition-all hover:scale-105 group">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm group-hover:rotate-12 transition-transform">
                            <i class="fas fa-headset text-primary-500 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-primary-800">Butuh Bantuan?</p>
                            <p class="text-xs text-primary-500 flex items-center">
                                <i class="fas fa-envelope mr-1"></i>
                                admin@lelang.com
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8" style="background: var(--secondary-50)">
            <!-- Header -->
            <div class="mb-8 gradient-bg rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <h1 class="text-3xl font-bold mb-2">Manajemen Barang</h1>
                    <p class="text-blue-100 opacity-80">Kelola daftar barang lelang melalui satu panel kontrol terpusat.</p>
                </div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-10 -mt-10 animate-float"></div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card">
                    <p class="text-xs uppercase text-gray-400 font-bold mb-1">Total Barang</p>
                    <p class="text-3xl font-extrabold text-blue-900"><?php echo $total_barang; ?></p>
                </div>
                <div class="stat-card">
                    <p class="text-xs uppercase text-gray-400 font-bold mb-1">Tunggu</p>
                    <p class="text-3xl font-extrabold text-orange-500"><?php echo $tunggu_count; ?></p>
                </div>
                <div class="stat-card">
                    <p class="text-xs uppercase text-gray-400 font-bold mb-1">Dibuka</p>
                    <p class="text-3xl font-extrabold text-green-600"><?php echo $dibuka_count; ?></p>
                </div>
                <div class="stat-card">
                    <p class="text-xs uppercase text-gray-400 font-bold mb-1">Ditutup</p>
                    <p class="text-3xl font-extrabold text-red-600"><?php echo $ditutup_count; ?></p>
                </div>
            </div>

            <!-- Actions & Filters -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-wrap gap-4 items-center mb-8">
                <form method="GET" class="flex flex-1 gap-3">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari barang..." class="form-input flex-1">
                    <select name="status" class="form-input w-40">
                        <option value="">Semua Status</option>
                        <option value="tunggu" <?php echo $filter_status=='tunggu'?'selected':''; ?>>Tunggu</option>
                        <option value="dibuka" <?php echo $filter_status=='dibuka'?'selected':''; ?>>Dibuka</option>
                        <option value="ditutup" <?php echo $filter_status=='ditutup'?'selected':''; ?>>Ditutup</option>
                    </select>
                    <button type="submit" class="btn-primary">Filter</button>
                </form>
                <button onclick="openModal('modalTambah')" class="btn-primary bg-green-600 hover:bg-green-700">
                    <i class="fas fa-plus mr-2"></i>Tambah Barang
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Item</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Harga Awal</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php while($row = mysqli_fetch_assoc($barang_list)): ?>
                        <tr class="hover:bg-gray-50 transition-all">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <?php 
                                    $img = resolveBarangImageUrl($row['gambar'] ?? '');
                                    if($img): ?>
                                        <img src="<?php echo $img; ?>" class="w-12 h-12 rounded-xl object-cover shadow-sm">
                                    <?php else: ?>
                                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-500">
                                            <i class="fas fa-box"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-bold text-gray-900"><?php echo htmlspecialchars($row['nama_barang']); ?></p>
                                        <p class="text-xs text-gray-400"><?php echo date('d/m/Y', strtotime($row['tgl'])); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-blue-700"><?php echo formatRupiah($row['harga_awal']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if($row['status_barang']=='dibuka'): ?>
                                    <span class="badge badge-success">Dibuka</span>
                                <?php elseif($row['status_barang']=='ditutup'): ?>
                                    <span class="badge badge-danger">Ditutup</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Tunggu</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center space-x-2">
                                    <button onclick='openEditModal(<?php echo json_encode($row); ?>)' class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $row['id_barang']; ?>" onclick="return confirm('Hapus barang ini?')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal Modal -->
    <div id="modalTambah" class="modal-backdrop">
        <div class="modal-box">
            <h3 class="text-xl font-bold mb-4">Tambah Barang Baru</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return validateForm('modalTambah')" novalidate>
                <input type="hidden" name="tambah_barang" value="1">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Nama Barang</label>
                    <input type="text" name="nama_barang" id="namaTambah" class="form-input" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Harga Awal</label>
                    <input type="number" name="harga_awal" id="hargaTambah" class="form-input" min="1" required>
                    <p id="errorHargaTambah" class="text-red-500 text-[10px] mt-1 hidden font-semibold uppercase tracking-wider italic">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Harga harus diisi dan lebih dari 0!
                    </p>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Deskripsi</label>
                    <textarea name="deskripsi_barang" class="form-input h-24" required></textarea>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Gambar Barang</label>
                    <input type="file" name="gambar" class="form-input">
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal('modalTambah')" class="flex-1 py-3 bg-gray-100 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="flex-1 btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEdit" class="modal-backdrop">
        <div class="modal-box">
            <h3 class="text-xl font-bold mb-4">Edit Barang</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return validateForm('modalEdit')" novalidate>
                <input type="hidden" name="perbarui_barang" value="1">
                <input type="hidden" name="id_barang" id="editId">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Nama Barang</label>
                    <input type="text" name="nama_barang" id="editNama" class="form-input" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Harga Awal</label>
                    <input type="number" name="harga_awal" id="editHarga" class="form-input" min="1" required>
                    <p id="errorHargaEdit" class="text-red-500 text-[10px] mt-1 hidden font-semibold uppercase tracking-wider italic">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Harga harus diisi dan lebih dari 0!
                    </p>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Status</label>
                    <select name="status_barang" id="editStatus" class="form-input">
                        <option value="tunggu">Tunggu</option>
                        <option value="dibuka">Dibuka</option>
                        <option value="ditutup">Ditutup</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Deskripsi</label>
                    <textarea name="deskripsi_barang" id="editDeskripsi" class="form-input h-24" required></textarea>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Ganti Gambar <small>(opsional)</small></label>
                    <input type="file" name="gambar" class="form-input">
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal('modalEdit')" class="flex-1 py-3 bg-gray-100 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="flex-1 btn-primary">Perbarui</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id){ 
            document.getElementById(id).classList.add('active'); 
        }
        function closeModal(id){ 
            document.getElementById(id).classList.remove('active');
            // Reset error states
            const errorIds = ['errorHargaTambah', 'errorHargaEdit'];
            errorIds.forEach(eid => {
                const el = document.getElementById(eid);
                if(el) el.classList.add('hidden');
            });
            const inputIds = ['hargaTambah', 'editHarga'];
            inputIds.forEach(iid => {
                const el = document.getElementById(iid);
                if(el) el.classList.remove('border-red-500', 'bg-red-50');
            });
        }
        function openEditModal(data){
            document.getElementById('editId').value = data.id_barang;
            document.getElementById('editNama').value = data.nama_barang;
            document.getElementById('editHarga').value = data.harga_awal;
            document.getElementById('editStatus').value = data.status_barang;
            document.getElementById('editDeskripsi').value = data.deskripsi_barang;
            openModal('modalEdit');
        }

        function validateForm(modalId) {
            let isValid = true;
            const isEdit = modalId === 'modalEdit';
            const priceInput = document.getElementById(isEdit ? 'editHarga' : 'hargaTambah');
            const errorMsg = document.getElementById(isEdit ? 'errorHargaEdit' : 'errorHargaTambah');
            
            if (!priceInput.value || parseInt(priceInput.value) <= 0) {
                errorMsg.classList.remove('hidden');
                priceInput.classList.add('border-red-500', 'bg-red-50');
                priceInput.focus();
                isValid = false;
            } else {
                errorMsg.classList.add('hidden');
                priceInput.classList.remove('border-red-500', 'bg-red-50');
            }
            
            return isValid;
        }

        // Add real-time validation
        ['hargaTambah', 'editHarga'].forEach(id => {
            const input = document.getElementById(id);
            if(input) {
                input.addEventListener('input', function() {
                    const errorId = id === 'hargaTambah' ? 'errorHargaTambah' : 'errorHargaEdit';
                    const errorMsg = document.getElementById(errorId);
                    if (this.value && parseInt(this.value) > 0) {
                        errorMsg.classList.add('hidden');
                        this.classList.remove('border-red-500', 'bg-red-50');
                    }
                });
            }
        });
    </script>
</body>
</html>