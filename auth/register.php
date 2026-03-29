<?php
session_start();
require_once('../config/config.php');
if (isset($_SESSION['id_user'])) {
  if ($_SESSION['id_level'] == 1 || $_SESSION['id_level'] == 2)
    header('Location: ../admin/dashboard.php');
  else
    header('Location: ../masyarakat/dashboard.php');
  exit;
}
$error = $success = '';
if (isset($_POST['register'])) {
  $nama = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
  $username = mysqli_real_escape_string($conn, trim($_POST['username']));
  $telepon = mysqli_real_escape_string($conn, trim($_POST['telp']));
  $password = $_POST['password'];
  $confirm = $_POST['confirm_password'];
  if (empty($nama) || empty($username) || empty($password)) {
    $error = 'Semua kolom wajib diisi.';
  } elseif (strlen($password) < 6) {
    $error = 'Password minimal 6 karakter.';
  } elseif ($password !== $confirm) {
    $error = 'Konfirmasi password tidak cocok.';
  } else {
    $cek = mysqli_query($conn, "SELECT id_user FROM tb_user WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
      $error = 'Username sudah digunakan.';
    } else {
      $hash = md5($password);
      $ins = mysqli_query($conn, "INSERT INTO tb_user (nama_lengkap,username,password,telp,id_level) VALUES ('$nama','$username','$hash','$telepon',3)");
      if ($ins)
        $success = 'Pendaftaran berhasil! Silakan login.';
      else
        $error = 'Terjadi kesalahan: ' . mysqli_error($conn);
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Daftar — LelangOnline</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *,
    *::before,
    *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --p50: #eef2f8;
      --p100: #d9e2f0;
      --p200: #b3c5e1;
      --p300: #8da8d2;
      --p400: #678bc3;
      --p500: #416eb4;
      --p600: #2a4f8c;
      --p700: #1e3a66;
      --p800: #132a4a;
      --p900: #0a1a30;
      --s50: #f8fafc;
      --s100: #f1f5f9;
      --s200: #e2e8f0;
      --s300: #cbd5e1;
      --s400: #94a3b8;
      --s500: #64748b;
      --s700: #334155;
      --s900: #0f172a;
      --white: #ffffff;
      --red: #ef4444;
      --green: #22c55e;
    }

    html {
      height: 100%;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      background: linear-gradient(145deg, var(--p900) 0%, var(--p800) 30%, var(--p700) 60%, var(--p600) 85%, var(--p500) 100%);
      position: relative;
      overflow: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.025'%3E%3Ccircle cx='40' cy='40' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
      pointer-events: none;
    }

    .orb {
      position: fixed;
      border-radius: 50%;
      filter: blur(90px);
      pointer-events: none;
      animation: orbF 14s ease-in-out infinite;
    }

    .orb-1 {
      width: 600px;
      height: 600px;
      background: rgba(103, 139, 195, 0.18);
      top: -250px;
      right: -150px;
    }

    .orb-2 {
      width: 450px;
      height: 450px;
      background: rgba(30, 58, 102, 0.25);
      bottom: -180px;
      left: -120px;
      animation-delay: -7s;
    }

    .orb-3 {
      width: 280px;
      height: 280px;
      background: rgba(141, 168, 210, 0.15);
      top: 40%;
      left: 20%;
      animation-delay: -3.5s;
    }

    @keyframes orbF {

      0%,
      100% {
        transform: translateY(0) scale(1);
      }

      50% {
        transform: translateY(-25px) scale(1.04);
      }
    }

    .wrap {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 560px;
      animation: slideUp .6s cubic-bezier(.16, 1, .3, 1) both;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(24px);
      }

      to {
        opacity: 1;
        transform: none;
      }
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      justify-content: center;
      margin-bottom: 2rem;
    }

    .brand-icon {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }

    .brand-icon i {
      color: #fff;
      font-size: 1.4rem;
    }

    .brand-text .name {
      font-size: 1.4rem;
      font-weight: 800;
      color: #fff;
      letter-spacing: -.025em;
    }

    .brand-text .name span {
      color: #93c5fd;
    }

    .brand-text .tag {
      font-size: .6rem;
      color: rgba(255, 255, 255, 0.4);
      font-weight: 500;
      letter-spacing: .12em;
      text-transform: uppercase;
    }

    .card {
      background: rgba(255, 255, 255, 0.97);
      border-radius: 28px;
      padding: 36px 34px 30px;
      box-shadow: 0 32px 80px -12px rgba(10, 26, 48, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1);
    }

    .card-title {
      font-size: 1.55rem;
      font-weight: 800;
      color: var(--p900);
      letter-spacing: -.03em;
      margin-bottom: 4px;
    }

    .card-sub {
      font-size: .83rem;
      color: var(--s500);
      margin-bottom: 24px;
    }

    .card-sub a {
      color: var(--p600);
      font-weight: 700;
      text-decoration: none;
      border-bottom: 1.5px solid var(--p200);
    }

    .card-sub a:hover {
      color: var(--p700);
    }

    .alert {
      display: flex;
      align-items: center;
      gap: 10px;
      border-radius: 14px;
      padding: 11px 14px;
      margin-bottom: 18px;
    }

    .alert-err {
      background: #fef2f2;
      border: 1.5px solid #fca5a5;
      animation: shake .35s ease;
    }

    .alert-ok {
      background: #f0fdf4;
      border: 1.5px solid #86efac;
    }

    .alert-err i {
      color: var(--red);
      font-size: .82rem;
      flex-shrink: 0;
    }

    .alert-ok i {
      color: var(--green);
      font-size: .82rem;
      flex-shrink: 0;
    }

    .alert p {
      font-size: .82rem;
      font-weight: 600;
    }

    .alert-err p {
      color: #b91c1c;
    }

    .alert-ok p {
      color: #15803d;
    }

    .alert-ok a {
      color: var(--p700);
      font-weight: 700;
      text-decoration: none;
    }

    @keyframes shake {

      0%,
      100% {
        transform: translateX(0);
      }

      25% {
        transform: translateX(-5px);
      }

      75% {
        transform: translateX(4px);
      }
    }

    .sec {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 22px 0 14px;
    }

    .sec-num {
      width: 24px;
      height: 24px;
      border-radius: 8px;
      background: linear-gradient(135deg, var(--p700), var(--p500));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .65rem;
      font-weight: 800;
      color: #fff;
      flex-shrink: 0;
    }

    .sec-lbl {
      font-size: .68rem;
      font-weight: 800;
      color: var(--p600);
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    .sec::after {
      content: '';
      flex: 1;
      height: 1.5px;
      background: var(--s200);
    }

    .grid2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    @media(max-width:480px) {
      .grid2 {
        grid-template-columns: 1fr;
      }
    }

    .field {
      margin-bottom: 14px;
    }

    .field-label {
      display: block;
      font-size: .68rem;
      font-weight: 700;
      color: var(--s700);
      text-transform: uppercase;
      letter-spacing: .08em;
      margin-bottom: 6px;
    }

    .field-label sup {
      color: var(--red);
    }

    .fw {
      position: relative;
    }

    .fi {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--s400);
      font-size: .8rem;
      pointer-events: none;
      transition: color .2s;
    }

    .fw:focus-within .fi {
      color: var(--p500);
    }

    .finput {
      width: 100%;
      padding: 12px 14px 12px 40px;
      border: 1.5px solid var(--s200);
      border-radius: 13px;
      font-family: inherit;
      font-size: .875rem;
      font-weight: 500;
      color: var(--s900);
      background: var(--s50);
      outline: none;
      transition: all .2s;
    }

    .finput::placeholder {
      color: var(--s300);
      font-weight: 400;
    }

    .finput:focus {
      border-color: var(--p400);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(65, 110, 180, 0.1);
    }

    .feye {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: var(--s400);
      font-size: .8rem;
      padding: 4px 6px;
      transition: color .2s;
      border-radius: 6px;
    }

    .feye:hover {
      color: var(--p500);
      background: var(--p50);
    }

    /* Password strength */
    .str-wrap {
      margin-top: 6px;
    }

    .str-bars {
      display: flex;
      gap: 4px;
    }

    .sb {
      flex: 1;
      height: 3px;
      border-radius: 2px;
      background: var(--s200);
      transition: background .3s;
    }

    .str-hint {
      font-size: .67rem;
      color: var(--s400);
      margin-top: 3px;
      font-weight: 500;
    }

    .cb-terms {
      display: flex;
      align-items: flex-start;
      gap: 9px;
      margin: 20px 0 22px;
    }

    .cb-terms input {
      width: 16px;
      height: 16px;
      accent-color: var(--p600);
      flex-shrink: 0;
      margin-top: 2px;
      cursor: pointer;
    }

    .cb-terms label {
      font-size: .8rem;
      color: var(--s500);
      line-height: 1.5;
      cursor: pointer;
    }

    .cb-terms a {
      color: var(--p600);
      font-weight: 600;
      text-decoration: none;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      width: 100%;
      padding: 13px 24px;
      border-radius: 14px;
      font-family: inherit;
      font-size: .9rem;
      font-weight: 700;
      cursor: pointer;
      border: none;
      text-decoration: none;
      transition: all .25s;
      position: relative;
      overflow: hidden;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--p700), var(--p500));
      color: #fff;
      box-shadow: 0 6px 20px -4px rgba(30, 58, 102, 0.45);
    }

    .btn-primary::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, var(--p600), var(--p400));
      opacity: 0;
      transition: opacity .25s;
    }

    .btn-primary:hover::before {
      opacity: 1;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px -5px rgba(30, 58, 102, 0.5);
    }

    .btn span,
    .btn i {
      position: relative;
      z-index: 1;
    }

    .flink {
      text-align: center;
      font-size: .83rem;
      color: var(--s400);
      margin-top: 14px;
    }

    .flink a {
      color: var(--p600);
      font-weight: 700;
      text-decoration: none;
      border-bottom: 1.5px solid var(--p200);
    }

    .flink a:hover {
      color: var(--p700);
    }

    .dots {
      display: flex;
      justify-content: center;
      gap: 6px;
      margin-top: 24px;
    }

    .dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--s200);
    }

    .dot.on {
      width: 20px;
      border-radius: 3px;
      background: var(--p500);
    }

    .page-footer {
      text-align: center;
      margin-top: 1.25rem;
      font-size: .7rem;
      color: rgba(255, 255, 255, 0.35);
      letter-spacing: .03em;
    }
  </style>
</head>

<body>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>

  <div class="wrap">
    <div class="brand">
      <div class="brand-icon"><i class="fas fa-gavel"></i></div>
      <div class="brand-text">
        <div class="name">Lelang<span>Online</span></div>
        <div class="tag">Platform Lelang Terpercaya</div>
      </div>
    </div>

    <div class="card">
      <h2 class="card-title">Buat Akun Baru</h2>
      <p class="card-sub">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>

      <?php if ($error): ?>
        <div class="alert alert-err"><i class="fas fa-exclamation-circle"></i>
          <p><?php echo htmlspecialchars($error); ?></p>
        </div><?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-ok"><i class="fas fa-check-circle"></i>
          <p><?php echo htmlspecialchars($success); ?> <a href="login.php">Login &rarr;</a></p>
        </div><?php endif; ?>

      <form method="POST" id="rf">
        <div class="sec">
          <div class="sec-num">1</div><span class="sec-lbl">Identitas Diri</span>
        </div>
        <div class="grid2">
          <div class="field">
            <label class="field-label">Nama Lengkap <sup>*</sup></label>
            <div class="fw"><i class="fi fas fa-id-card"></i>
              <input type="text" name="nama_lengkap" class="finput" placeholder="Nama lengkap" required
                value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
            </div>
          </div>
          <div class="field">
            <label class="field-label">Nama Pengguna <sup>*</sup></label>
            <div class="fw"><i class="fi fas fa-at"></i>
              <input type="text" name="username" class="finput" placeholder="Nama pengguna unik" required
                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
          </div>
        </div>

        <div class="sec">
          <div class="sec-num">2</div><span class="sec-lbl">Kontak</span>
        </div>
        <div class="field">
          <label class="field-label">No. Telepon</label>
          <div class="fw"><i class="fi fas fa-phone"></i>
            <input type="tel" name="telp" class="finput" placeholder="08xx-xxxx-xxxx"
              value="<?php echo htmlspecialchars($_POST['telp'] ?? ''); ?>">
          </div>
        </div>

        <div class="sec">
          <div class="sec-num">3</div><span class="sec-lbl">Keamanan</span>
        </div>
        <div class="grid2">
          <div class="field">
            <label class="field-label">Kata sandi <sup>*</sup></label>
            <div class="fw"><i class="fi fas fa-lock"></i>
              <input type="password" name="password" id="p1" class="finput" placeholder="Min. 6 karakter" required
                oninput="chkStr(this.value)">
              <button type="button" class="feye" onclick="tog('p1','e1')"><i class="fas fa-eye" id="e1"></i></button>
            </div>
            <div class="str-wrap">
              <div class="str-bars">
                <div class="sb" id="s1"></div>
                <div class="sb" id="s2"></div>
                <div class="sb" id="s3"></div>
                <div class="sb" id="s4"></div>
              </div>
              <div class="str-hint" id="sh">Masukkan kata sandi</div>
            </div>
          </div>
          <div class="field">
            <label class="field-label">Konfirmasi <sup>*</sup></label>
            <div class="fw"><i class="fi fas fa-lock-open"></i>
              <input type="password" name="confirm_password" id="p2" class="finput" placeholder="Ulangi kata sandi"
                required>
              <button type="button" class="feye" onclick="tog('p2','e2')"><i class="fas fa-eye" id="e2"></i></button>
            </div>
          </div>
        </div>

        <div class="cb-terms">
          <input type="checkbox" id="tc" required>
          <label for="tc">Saya menyetujui <a href="#">Syarat &amp; Ketentuan</a> dan <a href="#">Kebijakan Privasi</a>
            LelangOnline</label>
        </div>

        <button type="submit" name="register" class="btn btn-primary">
          <i class="fas fa-user-plus"></i><span>Buat Akun Sekarang</span>
        </button>
        <p class="flink">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
      </form>

      <div class="dots">
        <div class="dot"></div>
        <div class="dot on"></div>
      </div>
    </div>


    <script>
      function tog(id, ei) { var p = document.getElementById(id), i = document.getElementById(ei); p.type = p.type === 'password' ? 'text' : 'password'; i.className = p.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye'; }
      function chkStr(v) {
        var sc = 0;
        if (v.length >= 6) sc++; if (v.length >= 10) sc++;
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) sc++;
        if (/[0-9]/.test(v) || /[^a-zA-Z0-9]/.test(v)) sc++;
        var c = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
        var lb = ['Terlalu pendek', 'Lemah', 'Cukup kuat', 'Kuat'];
        for (var i = 1; i <= 4; i++) document.getElementById('s' + i).style.background = i <= sc ? c[Math.max(sc - 1, 0)] : '#e2e8f0';
        var el = document.getElementById('sh');
        el.textContent = v.length ? (lb[sc - 1] || 'Terlalu pendek') : 'Masukkan kata sandi';
        el.style.color = v.length ? (c[Math.max(sc - 1, 0)]) : '#94a3b8';
      }
    </script>
</body>

</html>