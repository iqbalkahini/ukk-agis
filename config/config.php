<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_lelang_online');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Base URL
define('BASE_URL', 'http://localhost/sistem-lelang-online/');

// Function untuk format rupiah
function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Function untuk format tanggal Indonesia
function formatTanggal($tanggal)
{
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    $pecah = explode('-', $tanggal);
    return $pecah[2] . ' ' . $bulan[(int) $pecah[1]] . ' ' . $pecah[0];
}

// Function untuk mencari path gambar barang yang valid
function resolveBarangImageUrl($filename)
{
    if (!$filename)
        return null;
    $paths = [
        ['file' => __DIR__ . '/../barang/' . $filename, 'url' => '../barang/' . $filename],
        ['file' => __DIR__ . '/../uploads/barang/' . $filename, 'url' => '../uploads/barang/' . $filename],
        ['file' => __DIR__ . '/../uploads/' . $filename, 'url' => '../uploads/' . $filename],
    ];
    foreach ($paths as $path) {
        if (file_exists($path['file']))
            return $path['url'];
    }
    return null;
}

// Function untuk cek login
function checkLogin()
{
    if (!isset($_SESSION['id_user'])) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

// Function untuk cek level akses
function checkLevel($allowed_levels = [])
{
    if (!isset($_SESSION['id_level'])) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }

    if (!in_array($_SESSION['id_level'], $allowed_levels)) {
        header('Location: ' . BASE_URL);
        exit;
    }
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>