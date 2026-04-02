<?php
session_start();
require_once('../config/config.php');
checkLevel([1, 2]); // Admin dan Petugas

$is_admin = $_SESSION['id_level'] == 1;

// Get filter dengan validasi
$tanggal_dari   = isset($_GET['dari']) ? mysqli_real_escape_string($conn, $_GET['dari']) : date('Y-m-01');
$tanggal_sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($conn, $_GET['sampai']) : date('Y-m-d');
$status_filter  = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';
$export_type    = isset($_GET['export']) ? mysqli_real_escape_string($conn, $_GET['export']) : '';

// Validasi tanggal
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_dari)) $tanggal_dari = date('Y-m-01');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_sampai)) $tanggal_sampai = date('Y-m-d');

// Build query with proper escaping
$where = "WHERE l.tgl_lelang BETWEEN '$tanggal_dari' AND '$tanggal_sampai'";
if($status_filter !== 'all') {
    $status_filter = mysqli_real_escape_string($conn, $status_filter);
    $where .= " AND l.status = '$status_filter'";
}

$query = "SELECT l.*, b.nama_barang, b.harga_awal, u.nama_lengkap as pemenang
          FROM tb_lelang l
          JOIN tb_barang b ON l.id_barang = b.id_barang
          LEFT JOIN tb_user u ON l.id_user = u.id_user
          $where
          ORDER BY l.tgl_lelang DESC";

$laporan = mysqli_query($conn, $query);

if(!$laporan) {
    die("Query Error: " . mysqli_error($conn));
}

// Calculate statistics
$rows_data = [];
$total_lelang  = 0;
$total_nilai   = 0;
$lelang_selesai = 0;
$lelang_aktif  = 0;

while($r = mysqli_fetch_assoc($laporan)) {
    $rows_data[] = $r;
    $total_lelang++;
    if($r['status'] == 'ditutup') {
        $lelang_selesai++;
        $total_nilai += floatval($r['harga_akhir'] ?? 0);
    } else {
        $lelang_aktif++;
    }
}

$pembayaran_query = "SELECT p.*, l.id_lelang, l.tgl_lelang, l.harga_akhir, b.nama_barang, u.nama_lengkap,
                            COALESCE(p.created_at, CONCAT(l.tgl_lelang, ' 00:00:00')) as tanggal_pembayaran
                     FROM tb_pembayaran p
                     JOIN tb_lelang l ON p.id_lelang = l.id_lelang
                     JOIN tb_barang b ON l.id_barang = b.id_barang
                     JOIN tb_user u ON p.id_user = u.id_user
                     WHERE DATE(COALESCE(p.created_at, CONCAT(l.tgl_lelang, ' 00:00:00'))) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'
                     ORDER BY tanggal_pembayaran DESC";
$pembayaran_result = mysqli_query($conn, $pembayaran_query);

if(!$pembayaran_result) {
    die("Query Error: " . mysqli_error($conn));
}

$payment_rows = [];
$total_pembayaran = 0;
$total_pembayaran_selesai = 0;
$total_nilai_pembayaran = 0;

while($payment = mysqli_fetch_assoc($pembayaran_result)) {
    $payment_rows[] = $payment;
    $total_pembayaran++;
    if(($payment['status_pembayaran'] ?? '') === 'selesai') {
        $total_pembayaran_selesai++;
        $total_nilai_pembayaran += floatval($payment['jumlah'] ?? 0);
    }
}

// Helper functions
if(!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        if($angka === null || $angka === '') return 'Rp 0';
        return 'Rp ' . number_format(floatval($angka), 0, ',', '.');
    }
}
if(!function_exists('formatTanggal')) {
    function formatTanggal($tanggal) {
        if($tanggal === null || $tanggal === '' || $tanggal == '0000-00-00') return '-';
        return date('d/m/Y', strtotime($tanggal));
    }
}
if(!function_exists('formatTanggalWaktu')) {
    function formatTanggalWaktu($tanggal) {
        if($tanggal === null || $tanggal === '' || $tanggal == '0000-00-00 00:00:00') return '-';
        return date('d/m/Y H:i', strtotime($tanggal));
    }
}

if(!function_exists('escapeExcel')) {
    function escapeExcel($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if($export_type === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename=laporan-lelang-pembayaran-' . $tanggal_dari . '-sd-' . $tanggal_sampai . '.xls');
    echo "<table border='1'>";
    echo "<tr><th colspan='7'>Laporan Lelang</th></tr>";
    echo "<tr><th>No</th><th>Tanggal</th><th>Nama Barang</th><th>Harga Awal</th><th>Harga Akhir</th><th>Pemenang</th><th>Status</th></tr>";
    if(count($rows_data) > 0) {
        foreach($rows_data as $i => $row) {
            echo '<tr>';
            echo '<td>' . ($i + 1) . '</td>';
            echo '<td>' . escapeExcel(formatTanggal($row['tgl_lelang'])) . '</td>';
            echo '<td>' . escapeExcel($row['nama_barang']) . '</td>';
            echo '<td>' . escapeExcel(formatRupiah($row['harga_awal'])) . '</td>';
            echo '<td>' . escapeExcel(formatRupiah($row['harga_akhir'])) . '</td>';
            echo '<td>' . escapeExcel($row['pemenang'] ?: '-') . '</td>';
            echo '<td>' . escapeExcel($row['status']) . '</td>';
            echo '</tr>';
        }
    }
    echo "</table><br>";
    echo "<table border='1'>";
    echo "<tr><th colspan='8'>Laporan Pembayaran</th></tr>";
    echo "<tr><th>No</th><th>Tanggal Bayar</th><th>Nama User</th><th>Nama Barang</th><th>Metode</th><th>Jumlah</th><th>Status</th><th>Bukti</th></tr>";
    if(count($payment_rows) > 0) {
        foreach($payment_rows as $i => $row) {
            echo '<tr>';
            echo '<td>' . ($i + 1) . '</td>';
            echo '<td>' . escapeExcel(formatTanggalWaktu($row['tanggal_pembayaran'])) . '</td>';
            echo '<td>' . escapeExcel($row['nama_lengkap']) . '</td>';
            echo '<td>' . escapeExcel($row['nama_barang']) . '</td>';
            echo '<td>' . escapeExcel($row['metode_pembayaran'] ?: '-') . '</td>';
            echo '<td>' . escapeExcel(formatRupiah($row['jumlah'])) . '</td>';
            echo '<td>' . escapeExcel($row['status_pembayaran']) . '</td>';
            echo '<td>' . escapeExcel($row['bukti_pembayaran'] ?: '-') . '</td>';
            echo '</tr>';
        }
    }
    echo "</table>";
    exit;
}

if($export_type === 'pdf') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Export PDF Laporan</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 24px; color: #1f2937; }
            h1, h2 { margin: 0 0 12px; }
            p { margin: 0 0 16px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
            th, td { border: 1px solid #cbd5e1; padding: 8px; font-size: 12px; text-align: left; }
            th { background: #e2e8f0; }
            .meta { margin-bottom: 20px; font-size: 13px; }
        </style>
    </head>
    <body onload="window.print()">
        <h1>Laporan Lelang dan Pembayaran</h1>
        <p class="meta">Periode <?php echo formatTanggal($tanggal_dari); ?> s/d <?php echo formatTanggal($tanggal_sampai); ?></p>

        <h2>Data Lelang</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Barang</th>
                    <th>Harga Awal</th>
                    <th>Harga Akhir</th>
                    <th>Pemenang</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows_data as $i => $row): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo formatTanggal($row['tgl_lelang']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?php echo formatRupiah($row['harga_awal']); ?></td>
                    <td><?php echo formatRupiah($row['harga_akhir']); ?></td>
                    <td><?php echo htmlspecialchars($row['pemenang'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Data Pembayaran</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Bayar</th>
                    <th>Nama User</th>
                    <th>Nama Barang</th>
                    <th>Metode</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Bukti</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($payment_rows as $i => $row): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo formatTanggalWaktu($row['tanggal_pembayaran']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?php echo htmlspecialchars($row['metode_pembayaran'] ?: '-'); ?></td>
                    <td><?php echo formatRupiah($row['jumlah']); ?></td>
                    <td><?php echo htmlspecialchars($row['status_pembayaran']); ?></td>
                    <td><?php echo htmlspecialchars($row['bukti_pembayaran'] ?: '-'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Lelang - Sistem Lelang Online</title>
    
    <!-- Font Professional -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Elegant Blue Theme */
        :root {
            --primary-50: #eef2f8;
            --primary-100: #d9e2f0;
            --primary-200: #b3c5e1;
            --primary-300: #8da8d2;
            --primary-400: #678bc3;
            --primary-500: #416eb4;
            --primary-600: #2a4f8c;
            --primary-700: #1e3a66;
            --primary-800: #132a4a;
            --primary-900: #0a1a30;
            
            --secondary-50: #f8fafc;
            --secondary-100: #f1f5f9;
            --secondary-200: #e2e8f0;
            --secondary-300: #cbd5e1;
            --secondary-400: #94a3b8;
            --secondary-500: #64748b;
            --secondary-600: #475569;
            --secondary-700: #334155;
            --secondary-800: #1e293b;
            --secondary-900: #0f172a;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Custom Classes */
        .bg-primary { background-color: var(--primary-600); }
        .bg-primary-light { background-color: var(--primary-500); }
        .bg-primary-dark { background-color: var(--primary-700); }
        .text-primary { color: var(--primary-700); }
        .border-primary { border-color: var(--primary-200); }
        
        /* Modern Card with Hover Effects */
        .card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(30, 58, 102, 0.08);
            border: 1px solid var(--primary-100);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-400), var(--primary-600));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .card:hover::before {
            transform: scaleX(1);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 35px -8px rgba(30, 58, 102, 0.15);
        }
        
        /* Gradient Background */
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-700), var(--primary-600), var(--primary-500));
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(65, 110, 180, 0.15);
            transition: all 0.3s ease;
        }
        
        .glass:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(65, 110, 180, 0.3);
        }
        
        /* Table Styles */
        .modern-table {
            border-collapse: separate;
            border-spacing: 0 12px;
            width: 100%;
        }
        
        .modern-table tbody tr {
            background: white;
            border-radius: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px -2px rgba(30, 58, 102, 0.06);
            animation: slideInRow 0.5s ease-out;
            animation-fill-mode: both;
        }
        
        @keyframes slideInRow {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .modern-table tbody tr:hover {
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 20px 25px -8px rgba(30, 58, 102, 0.15);
            background: linear-gradient(90deg, white, var(--primary-50));
        }
        
        .modern-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            color: var(--primary-600);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .modern-table td {
            padding: 1rem 1.5rem;
            color: var(--secondary-700);
        }
        
        .modern-table td:first-child { border-radius: 18px 0 0 18px; }
        .modern-table td:last-child { border-radius: 0 18px 18px 0; }
        
        /* Badge Styles */
        .badge {
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .badge:hover {
            transform: scale(1.05);
            filter: brightness(1.1);
        }
        
        .badge-success {
            background: #e6f7e6;
            color: #0a5e0a;
            border: 1px solid #a3e0a3;
        }
        
        .badge-warning {
            background: #fff4e0;
            color: #9e5e0a;
            border: 1px solid #ffd79c;
        }
        
        .badge-danger {
            background: #ffe6e6;
            color: #a12323;
            border: 1px solid #ffb8b8;
        }
        
        .badge-info {
            background: var(--primary-100);
            color: var(--primary-800);
            border: 1px solid var(--primary-200);
        }
        
        /* Button Styles */
        .btn-primary {
            background: var(--primary-600);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-700);
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -8px rgba(30, 58, 102, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-700);
            border: 2px solid var(--primary-200);
            padding: 0.7rem 1.5rem;
            border-radius: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-outline:hover {
            background: var(--primary-50);
            border-color: var(--primary-500);
            color: var(--primary-800);
            transform: translateY(-2px);
        }
        
        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .spin-slow {
            animation: spin 8s linear infinite;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--primary-50);
            border-radius: 6px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-300);
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-400);
        }
        
        /* Loading Spinner */
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--primary-200);
            border-top-color: var(--primary-600);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .filter-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--primary-100);
            border-radius: 14px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .filter-input:focus {
            outline: none;
            border-color: var(--primary-400);
            box-shadow: 0 0 0 3px rgba(65, 110, 180, 0.1);
        }
        
        .page-banner {
            background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-600) 50%, var(--primary-500) 100%);
            border-radius: 28px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .page-banner::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        
        .page-banner::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: 30%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        
        .hover-glow {
            transition: all 0.3s ease;
        }
        
        .hover-glow:hover {
            box-shadow: 0 0 20px rgba(65, 110, 180, 0.3);
            transform: translateY(-2px);
        }
        
        .slide-in-left { animation: slideInLeft 0.5s ease-out; }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @media print {
            body { background: white; }
            aside, nav, .no-print, #particles { display: none !important; }
            main { padding: 0 !important; width: 100% !important; margin: 0 !important; }
        }
    </style>
</head>
<body class="antialiased text-gray-800">
    <!-- Particle Background -->
    <div id="particles" class="fixed inset-0 pointer-events-none"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 flex flex-col items-center animate__animated animate__zoomIn">
            <div class="spinner mb-4"></div>
            <p class="text-primary-600 font-medium">Memuat data...</p>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

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
                                <?php echo $is_admin ? 'Administrator' : 'Petugas'; ?>
                            </p>
                        </div>
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center text-white font-bold shadow-md ring-2 ring-primary-100 hover:scale-110 transition-transform">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?>
                        </div>
                    </div>
                    
                    <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="relative overflow-hidden group btn-outline text-sm py-2 px-4 flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline">Keluar</span>
                        <span class="absolute inset-0 bg-primary-100 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex relative">
        <!-- Sidebar -->
        <aside class="w-72 bg-white border-r border-primary-100 min-h-screen shadow-xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 gradient-bg"></div>
            <div class="p-6 relative z-10">
                <!-- Profile Card -->
                <div class="gradient-bg rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden group" data-aos="fade-right" data-aos-delay="50">
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
                    <p class="text-xs uppercase tracking-wider text-primary-300 mb-4 px-4 font-semibold" data-aos="fade-right">Menu Utama</p>
                    
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 group relative overflow-hidden">
                        <i class="fas fa-home w-6 text-primary-400 group-hover:text-primary-600 group-hover:scale-110 transition-transform"></i>
                        <span class="ml-3 group-hover:translate-x-1 transition-transform">Beranda</span>
                    </a>
                    <a href="laporan.php" class="flex items-center px-4 py-3 bg-primary-50 text-primary-700 rounded-xl font-bold group relative overflow-hidden">
                        <i class="fas fa-chart-bar w-6 text-primary-600"></i>
                        <span class="ml-3">Laporan</span>
                        <i class="fas fa-chevron-right ml-auto text-sm text-primary-600"></i>
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
                    <div class="mt-3 pt-3 border-t border-primary-100">
                        <p class="text-xs text-primary-400 text-center">
                            <i class="fas fa-circle text-green-500 text-[6px] mr-1 animate-pulse"></i>
                            Dukungan 24/7
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 bg-secondary-50">
            <!-- Breadcrumb -->
            <div class="flex items-center text-sm mb-4 slide-in-left no-print" style="color: var(--primary-500)">
                <a href="dashboard.php" class="transition hover:text-primary-700" style="color: var(--primary-500)">
                    <i class="fas fa-home mr-1"></i>Beranda
                </a>
                <i class="fas fa-chevron-right mx-3 text-xs text-primary-300"></i>
                <span class="font-medium text-primary-800">Laporan Lelang</span>
            </div>

            <!-- Page Banner -->
            <div class="page-banner no-print" data-aos="fade-down">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-chart-bar text-white text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Laporan Lelang</h1>
                            <p class="text-white/70 text-sm mt-1">Laporan data lelang dan pembayaran periode tertentu</p>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex gap-3">
                        <a href="?dari=<?php echo urlencode($tanggal_dari); ?>&sampai=<?php echo urlencode($tanggal_sampai); ?>&status=<?php echo urlencode($status_filter); ?>&export=pdf" 
                           class="flex items-center px-5 py-3 rounded-2xl font-semibold text-sm transition-all bg-white/15 text-white border border-white/30 hover:bg-white/25">
                            <i class="fas fa-file-pdf mr-2"></i>Export PDF
                        </a>
                        <a href="?dari=<?php echo urlencode($tanggal_dari); ?>&sampai=<?php echo urlencode($tanggal_sampai); ?>&status=<?php echo urlencode($status_filter); ?>&export=excel" 
                           class="flex items-center px-5 py-3 rounded-2xl font-semibold text-sm transition-all bg-white/15 text-white border border-white/30 hover:bg-white/25">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="bg-white rounded-2xl p-6 border border-primary-100 mb-6 no-print hover-glow" data-aos="fade-up">
                <form method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-2 text-primary-700">Tanggal Dari</label>
                        <input type="date" name="dari" value="<?php echo $tanggal_dari; ?>" class="filter-input">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-2 text-primary-700">Tanggal Sampai</label>
                        <input type="date" name="sampai" value="<?php echo $tanggal_sampai; ?>" class="filter-input">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-2 text-primary-700">Keterangan</label>
                        <select name="status" class="filter-input">
                            <option value="all" <?php echo $status_filter=='all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="dibuka" <?php echo $status_filter=='dibuka' ? 'selected' : ''; ?>>Dibuka</option>
                            <option value="ditutup" <?php echo $status_filter=='ditutup' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <a href="laporan.php" class="btn-outline">
                            <i class="fas fa-sync-alt mr-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table Lelang -->
            <div class="bg-white rounded-2xl border border-primary-100 overflow-hidden hover-glow" data-aos="fade-up" data-aos-delay="100">
                <div class="p-6 border-b border-primary-100 bg-gradient-to-r from-primary-50 to-white">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-primary-800">
                                <i class="fas fa-calendar-alt mr-3 text-primary-500"></i>Data Lelang
                            </h2>
                            <p class="text-primary-500 mt-1">Periode <?php echo formatTanggal($tanggal_dari); ?> – <?php echo formatTanggal($tanggal_sampai); ?></p>
                        </div>
                        <div class="mt-2 md:mt-0">
                            <span class="bg-primary-100 text-primary-700 px-4 py-2 rounded-full text-sm font-semibold">
                                <i class="fas fa-database mr-2"></i><?php echo count($rows_data); ?> data ditemukan
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full modern-table">
                        <thead>
                            <tr class="bg-transparent">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">No</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Nama Barang</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Harga Awal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Harga Akhir</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Pemenang</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($rows_data) > 0): ?>
                                <?php foreach($rows_data as $i => $row): ?>
                                <tr class="bg-white" style="animation-delay: <?php echo $i * 0.05; ?>s">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-primary-100 text-primary-700 rounded-xl font-bold">
                                            <?php echo $i + 1; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo formatTanggal($row['tgl_lelang']); ?></td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-primary-800"><?php echo htmlspecialchars($row['nama_barang']); ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-gray-600"><?php echo formatRupiah($row['harga_awal']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-primary-600 bg-primary-50 px-3 py-1 rounded-full"><?php echo formatRupiah($row['harga_akhir']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if(!empty($row['pemenang'])): ?>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-8 h-8 rounded-lg gradient-bg flex items-center justify-center text-white text-xs font-bold">
                                                    <?php echo strtoupper(substr($row['pemenang'], 0, 1)); ?>
                                                </div>
                                                <span class="text-sm text-gray-700"><?php echo htmlspecialchars($row['pemenang']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if($row['status'] == 'dibuka'): ?>
                                            <span class="badge badge-info">
                                                <i class="fas fa-circle text-[6px] mr-1 animate-pulse"></i>Dibuka
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle mr-1"></i>Selesai
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mb-4 animate-float">
                                                <i class="fas fa-file-alt text-primary-400 text-3xl"></i>
                                            </div>
                                            <p class="text-primary-600 font-medium text-lg">Tidak ada data laporan</p>
                                            <p class="text-sm text-primary-400 mt-2">Tidak ada lelang pada periode ini</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table Pembayaran -->
            <div class="bg-white rounded-2xl border border-primary-100 overflow-hidden hover-glow mt-6" data-aos="fade-up" data-aos-delay="150">
                <div class="p-6 border-b border-primary-100 bg-gradient-to-r from-primary-50 to-white">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-primary-800">
                                <i class="fas fa-credit-card mr-3 text-primary-500"></i>Data Pembayaran
                            </h2>
                            <p class="text-primary-500 mt-1">Periode <?php echo formatTanggal($tanggal_dari); ?> – <?php echo formatTanggal($tanggal_sampai); ?></p>
                        </div>
                        <div class="mt-2 md:mt-0">
                            <span class="bg-primary-100 text-primary-700 px-4 py-2 rounded-full text-sm font-semibold">
                                <i class="fas fa-database mr-2"></i><?php echo count($payment_rows); ?> data ditemukan
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full modern-table">
                        <thead>
                            <tr class="bg-transparent">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">No</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Tanggal Bayar</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Nama User</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Nama Barang</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Metode</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Jumlah</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-primary-600 uppercase tracking-wider">Bukti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($payment_rows) > 0): ?>
                                <?php foreach($payment_rows as $i => $row): ?>
                                <tr class="bg-white" style="animation-delay: <?php echo $i * 0.05; ?>s">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-primary-100 text-primary-700 rounded-xl font-bold">
                                            <?php echo $i + 1; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo formatTanggalWaktu($row['tanggal_pembayaran']); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center text-primary-600 text-xs font-bold">
                                                <?php echo strtoupper(substr($row['nama_lengkap'], 0, 1)); ?>
                                            </div>
                                            <span class="font-medium text-gray-700"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-700"><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($row['metode_pembayaran'] ?: '-'); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-primary-600 bg-primary-50 px-3 py-1 rounded-full"><?php echo formatRupiah($row['jumlah']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if(($row['status_pembayaran'] ?? '') === 'selesai'): ?>
                                            <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Selesai</span>
                                        <?php elseif(($row['status_pembayaran'] ?? '') === 'dibayar'): ?>
                                            <span class="badge badge-info"><i class="fas fa-hourglass-half mr-1"></i>Dibayar</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars(ucfirst($row['status_pembayaran'] ?? 'Tunggu')); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if(!empty($row['bukti_pembayaran'])): ?>
                                            <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Ada</span>
                                        <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mb-4 animate-float">
                                                <i class="fas fa-receipt text-primary-400 text-3xl"></i>
                                            </div>
                                            <p class="text-primary-600 font-medium text-lg">Tidak ada data pembayaran</p>
                                            <p class="text-sm text-primary-400 mt-2">Tidak ada pembayaran pada periode ini</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Footer Note -->
            <div class="mt-6 text-center text-sm text-primary-400 no-print">
                <i class="fas fa-print mr-1"></i>
                Gunakan tombol Export untuk mencetak laporan • Dihasilkan: <?php echo date('d/m/Y H:i:s'); ?> WIB
            </div>
        </main>
    </div>

    <!-- AOS Animation Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 600,
            once: false,
            mirror: true,
            offset: 20,
            easing: 'ease-out-cubic'
        });

        // Particle Background
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            if(!particlesContainer) return;
            
            for (let i = 0; i < 15; i++) {
                const particle = document.createElement('div');
                particle.className = 'absolute bg-primary-200 rounded-full pointer-events-none';
                particle.style.width = (Math.random() * 6 + 2) + 'px';
                particle.style.height = particle.style.width;
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animation = `float ${Math.random() * 10 + 10}s linear infinite`;
                particle.style.opacity = '0.2';
                particlesContainer.appendChild(particle);
            }
        }
        createParticles();

        // Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `flex items-center p-4 rounded-xl shadow-lg text-sm transform transition-all duration-500 animate__animated animate__fadeInRight ${
                type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' :
                type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' :
                'bg-blue-50 text-blue-800 border border-blue-200'
            }`;
            
            const icon = type === 'success' ? 'fa-check-circle' : 
                        type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
            
            toast.innerHTML = `
                <i class="fas ${icon} mr-3 text-lg"></i>
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-3 text-gray-500 hover:text-gray-700 hover:scale-110 transition-transform">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.getElementById('toastContainer').appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('animate__fadeOutRight');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        // Show Loading
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
            document.getElementById('loadingOverlay').classList.add('flex');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
            document.getElementById('loadingOverlay').classList.remove('flex');
        }

        // Print optimization
        window.onbeforeprint = () => {
            document.querySelectorAll('.particle').forEach(el => el.style.display = 'none');
        };
        window.onafterprint = () => {
            document.querySelectorAll('.particle').forEach(el => el.style.display = '');
        };

        <?php if(isset($_SESSION['success'])): ?>
        showToast('<?php echo addslashes($_SESSION['success']); ?>', 'success');
        <?php unset($_SESSION['success']); endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
        showToast('<?php echo addslashes($_SESSION['error']); ?>', 'error');
        <?php unset($_SESSION['error']); endif; ?>
    </script>
</body>
</html>