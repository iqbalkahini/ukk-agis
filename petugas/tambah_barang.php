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

$barang_upload_dir = '../barang/';
$barang_image_url = '../barang/';

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

if (isset($_POST['submit'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $tgl = mysqli_real_escape_string($conn, $_POST['tgl']);
    $harga = str_replace('.', '', $_POST['harga_awal']);
    $desk = mysqli_real_escape_string($conn, $_POST['deskripsi_barang']);
    $gambar_query = '';
    $gambar_value = '';

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            if (!is_dir($barang_upload_dir)) {
                mkdir($barang_upload_dir, 0777, true);
            }

            $gambar = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $_FILES['gambar']['name']);
            $gambar_value = $gambar;

            if (!empty($_POST['id_barang'])) {
                $id = (int) $_POST['id_barang'];
                $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM tb_barang WHERE id_barang = $id"));
                $candidate_paths = [
                    $barang_upload_dir . ($old['gambar'] ?? ''),
                    '../uploads/barang/' . ($old['gambar'] ?? ''),
                    '../uploads/' . ($old['gambar'] ?? ''),
                ];
                foreach ($candidate_paths as $path) {
                    if ($old && !empty($old['gambar']) && file_exists($path)) {
                        unlink($path);
                        break;
                    }
                }
            }

            move_uploaded_file($_FILES['gambar']['tmp_name'], $barang_upload_dir . $gambar);
            $gambar_query = ", gambar = '$gambar'";
        } else {
            $_SESSION['error'] = 'Format file tidak diizinkan';
            header('Location: tambah_barang.php' . (!empty($_POST['id_barang']) ? '?edit=' . (int) $_POST['id_barang'] : ''));
            exit;
        }
    }

    if (!empty($_POST['id_barang'])) {
        $id = (int) $_POST['id_barang'];
        mysqli_query($conn, "UPDATE tb_barang SET nama_barang='$nama', tgl='$tgl', harga_awal='$harga', deskripsi_barang='$desk' $gambar_query WHERE id_barang=$id");
        $_SESSION['success'] = 'Barang berhasil diperbarui';
    } else {
        if ($gambar_value) {
            mysqli_query($conn, "INSERT INTO tb_barang (nama_barang, tgl, harga_awal, deskripsi_barang, gambar, status_barang) VALUES ('$nama', '$tgl', '$harga', '$desk', '$gambar_value', 'pending')");
        } else {
            mysqli_query($conn, "INSERT INTO tb_barang (nama_barang, tgl, harga_awal, deskripsi_barang, status_barang) VALUES ('$nama', '$tgl', '$harga', '$desk', 'pending')");
        }
        $_SESSION['success'] = 'Barang berhasil ditambahkan';
    }

    header('Location: data_barang.php');
    exit;
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_barang WHERE id_barang = $id"));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_data ? 'Edit Barang' : 'Tambah Barang'; ?> - Petugas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
        :root { --primary-50:#eef2f8; --primary-100:#d9e2f0; --primary-200:#b3c5e1; --primary-400:#678bc3; --primary-600:#2a4f8c; --primary-700:#1e3a66; --primary-800:#132a4a; }
        body { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: 100vh; }
        .gradient-bg { background: linear-gradient(135deg, var(--primary-700), var(--primary-600), #416eb4); }
        .card { background:white; border:1px solid var(--primary-100); border-radius: 24px; box-shadow: 0 10px 25px -5px rgba(30,58,102,0.08); }
        .form-input { width:100%; padding:.9rem 1rem; border:1px solid var(--primary-200); border-radius:14px; background:white; }
        .form-input:focus { outline:none; border-color:var(--primary-400); box-shadow:0 0 0 3px rgba(65,110,180,.12); }
        .btn-primary { background:var(--primary-600); color:white; padding:.9rem 1.4rem; border-radius:14px; font-weight:600; display:inline-flex; align-items:center; transition:.3s ease; }
        .btn-primary:hover { background:var(--primary-700); transform:translateY(-2px); }
        .btn-outline { background:white; color:var(--primary-700); border:1px solid var(--primary-200); padding:.9rem 1.2rem; border-radius:14px; font-weight:600; display:inline-flex; align-items:center; }
    </style>
</head>
<body class="text-gray-800">
    <div class="max-w-5xl mx-auto px-4 py-10">
        <div class="gradient-bg rounded-3xl p-8 text-white shadow-xl mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold"><?php echo $edit_data ? 'Edit Barang' : 'Tambah Barang Baru'; ?></h1>
                    <p class="text-blue-100 mt-2">Petugas dapat menambah barang baru atau memperbarui detail barang.</p>
                </div>
                <a href="data_barang.php" class="bg-white/15 hover:bg-white/25 text-white px-6 py-3 rounded-2xl font-semibold border border-white/25 inline-flex items-center transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar
                </a>
            </div>
        </div>

        <div class="card p-6 md:p-8">
            <h2 class="text-2xl font-bold mb-6" style="color:var(--primary-800)">
                <i class="fas <?php echo $edit_data ? 'fa-pen-to-square' : 'fa-plus-circle'; ?> mr-2" style="color:var(--primary-600)"></i>
                <?php echo $edit_data ? 'Form Edit Barang' : 'Form Tambah Barang'; ?>
            </h2>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <input type="hidden" name="id_barang" value="<?php echo $edit_data['id_barang'] ?? ''; ?>">

                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Nama Barang</label>
                    <input type="text" name="nama_barang" required value="<?php echo htmlspecialchars($edit_data['nama_barang'] ?? ''); ?>" class="form-input" placeholder="Masukkan nama barang">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Tanggal</label>
                    <input type="date" name="tgl" required value="<?php echo htmlspecialchars($edit_data['tgl'] ?? date('Y-m-d')); ?>" class="form-input">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Harga Awal</label>
                    <input type="text" name="harga_awal" required value="<?php echo isset($edit_data['harga_awal']) ? number_format($edit_data['harga_awal'], 0, ',', '.') : ''; ?>" class="form-input" placeholder="0" oninput="formatRupiah(this)">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Gambar Barang</label>
                    <input type="file" name="gambar" accept="image/*" class="form-input">
                    <?php $preview_url = $edit_data ? resolveBarangImageUrl($edit_data['gambar'] ?? '') : null; ?>
                    <?php if ($preview_url): ?>
                        <div class="mt-3 flex items-center gap-3">
                            <img src="<?php echo $preview_url; ?>" alt="Preview" class="w-16 h-16 object-cover rounded-xl border" style="border-color:var(--primary-200)">
                            <p class="text-xs" style="color:var(--primary-600)">Gambar saat ini</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2" style="color:var(--primary-700)">Deskripsi Barang</label>
                    <textarea name="deskripsi_barang" rows="5" required class="form-input" placeholder="Masukkan deskripsi barang"><?php echo htmlspecialchars($edit_data['deskripsi_barang'] ?? ''); ?></textarea>
                </div>

                <div class="md:col-span-2 flex flex-col sm:flex-row gap-3 pt-2">
                    <button type="submit" name="submit" class="btn-primary">
                        <i class="fas fa-save mr-2"></i><?php echo $edit_data ? 'Perbarui Barang' : 'Simpan Barang'; ?>
                    </button>
                    <a href="data_barang.php" class="btn-outline">
                        <i class="fas fa-xmark mr-2"></i>Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function formatRupiah(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            if (value) input.value = parseInt(value, 10).toLocaleString('id-ID');
        }
    </script>
</body>
</html>
