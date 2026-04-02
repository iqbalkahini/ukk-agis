<?php
session_start();
require_once('../config/config.php');
checkLevel([1]); // Hanya admin yang bisa akses halaman ini

// Handle delete pengguna
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Cek apakah user ada di penawaran lelang
    $check_history = mysqli_query($conn, "SELECT COUNT(*) as total FROM history_lelang WHERE id_user = $id");
    $cek_h = mysqli_fetch_assoc($check_history);
    
    $check_lelang = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_lelang WHERE id_user = $id");
    $cek_l = mysqli_fetch_assoc($check_lelang);
    
    if($cek_h['total'] > 0 || $cek_l['total'] > 0) {
        echo "<script>alert('Pengguna tidak dapat dihapus karena sudah memiliki riwayat penawaran atau memenangkan lelang!'); window.location='pengguna.php';</script>";
        exit;
    }
    
    mysqli_query($conn, "DELETE FROM tb_user WHERE id_user = $id AND id_level = 3");
    header('Location: pengguna.php'); exit;
}

// Handle tambah pengguna
if(isset($_POST['tambah_pengguna'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $telp = mysqli_real_escape_string($conn, $_POST['telp']);
    $id_level = 3; // Masyarakat
    
    // Cek username unik
    $check_user = mysqli_query($conn, "SELECT * FROM tb_user WHERE username = '$username'");
    if(mysqli_num_rows($check_user) > 0) {
        echo "<script>alert('Username sudah digunakan!'); window.location='pengguna.php';</script>";
        exit;
    }
    
    mysqli_query($conn, "INSERT INTO tb_user (nama_lengkap, username, password, telp, id_level) VALUES ('$nama','$username','$password','$telp',$id_level)");
    header('Location: pengguna.php'); exit;
}

// Handle perbarui pengguna
if(isset($_POST['perbarui_pengguna'])) {
    $id = (int)$_POST['id_user'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $telp = mysqli_real_escape_string($conn, $_POST['telp']);
    
    // Cek username unik (kecuali milik sendiri)
    $check_user = mysqli_query($conn, "SELECT * FROM tb_user WHERE username = '$username' AND id_user != $id");
    if(mysqli_num_rows($check_user) > 0) {
        echo "<script>alert('Username sudah digunakan!'); window.location='pengguna.php';</script>";
        exit;
    }
    
    $password_query = "";
    if(!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $password_query = ", password='$password'";
    }
    
    mysqli_query($conn, "UPDATE tb_user SET nama_lengkap='$nama', username='$username', telp='$telp' $password_query WHERE id_user=$id AND id_level = 3");
    header('Location: pengguna.php'); exit;
}

// Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "WHERE id_level = 3";
if($search) $where .= " AND (nama_lengkap LIKE '%$search%' OR username LIKE '%$search%' OR telp LIKE '%$search%')";

$user_list = mysqli_query($conn, "SELECT * FROM tb_user $where ORDER BY id_user DESC");

// Statistics
$total_masyarakat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_user WHERE id_level = 3"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Panel</title>
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
                <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="flex items-center space-x-4 relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl font-bold border-2 border-white/30">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div>
                            <p class="text-white/80 text-sm font-light">Selamat datang,</p>
                            <p class="text-xl font-bold"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?></p>
                        </div>
                    </div>
                </div>
                
                <nav class="space-y-2">
                    <p class="text-xs uppercase tracking-wider text-primary-300 mb-4 px-4 font-semibold">Menu Utama</p>
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group">
                        <i class="fas fa-home w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Beranda</span>
                    </a>
                    <a href="pengguna.php" class="flex items-center px-4 py-3 bg-primary-50 text-primary-700 rounded-xl font-bold group">
                        <i class="fas fa-users w-6 text-primary-600"></i>
                        <span class="ml-3">Pengguna</span>
                        <i class="fas fa-chevron-right ml-auto text-sm text-primary-600"></i>
                    </a>
                    <a href="laporan.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group">
                        <i class="fas fa-chart-bar w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Laporan</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="mb-8 gradient-bg rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <h1 class="text-3xl font-bold mb-2">Manajemen Pengguna</h1>
                    <p class="text-blue-100 opacity-80">Kelola data masyarakat yang terdaftar di sistem lelang.</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card">
                    <p class="text-xs uppercase text-gray-400 font-bold mb-1">Total Masyarakat</p>
                    <p class="text-3xl font-extrabold text-blue-900"><?php echo $total_masyarakat; ?></p>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-wrap gap-4 items-center mb-8">
                <form method="GET" class="flex flex-1 gap-3">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama, username, atau telepon..." class="form-input flex-1">
                    <button type="submit" class="btn-primary">Cari</button>
                </form>
                <button onclick="openModal('modalTambah')" class="btn-primary bg-green-600 hover:bg-green-700">
                    <i class="fas fa-plus mr-2"></i>Tambah Pengguna
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Nama Lengkap</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Username</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Telepon</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php while($row = mysqli_fetch_assoc($user_list)): ?>
                        <tr class="hover:bg-gray-50 transition-all">
                            <td class="px-6 py-4 font-bold text-gray-900"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td class="px-6 py-4 text-gray-600">@<?php echo htmlspecialchars($row['username']); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($row['telp']); ?></td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center space-x-2">
                                    <button onclick='openEditModal(<?php echo json_encode($row); ?>)' class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $row['id_user']; ?>" onclick="return confirm('Hapus pengguna ini?')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all">
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

    <!-- Modals -->
    <div id="modalTambah" class="modal-backdrop">
        <div class="modal-box">
            <h3 class="text-xl font-bold mb-4">Tambah Pengguna Baru</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="tambah_pengguna" value="1">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-input" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Username</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Telepon</label>
                    <input type="text" name="telp" class="form-input" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Password</label>
                    <input type="password" name="password" class="form-input" required>
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
            <h3 class="text-xl font-bold mb-4">Edit Pengguna</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="perbarui_pengguna" value="1">
                <input type="hidden" name="id_user" id="editId">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="editNama" class="form-input" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Username</label>
                    <input type="text" name="username" id="editUsername" class="form-input" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Telepon</label>
                    <input type="text" name="telp" id="editTelp" class="form-input" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Password <small>(kosongkan jika tidak diubah)</small></label>
                    <input type="password" name="password" class="form-input">
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal('modalEdit')" class="flex-1 py-3 bg-gray-100 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="flex-1 btn-primary">Perbarui</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id){ document.getElementById(id).classList.add('active'); }
        function closeModal(id){ document.getElementById(id).classList.remove('active'); }
        function openEditModal(data){
            document.getElementById('editId').value = data.id_user;
            document.getElementById('editNama').value = data.nama_lengkap;
            document.getElementById('editUsername').value = data.username;
            document.getElementById('editTelp').value = data.telp;
            openModal('modalEdit');
        }
    </script>
</body>
</html>
