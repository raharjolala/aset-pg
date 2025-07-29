<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$isAdmin = $_SESSION['role'] === 'admin';
$isUser  = $_SESSION['role'] === 'pengguna';

// Ambil notifikasi kontrak hampir habis (khusus admin)
$notifikasi = [];
if ($isAdmin) {
    $notifikasi = mysqli_query($conn, "
        SELECT aset.nama_aset, penyewa.nama AS nama_penyewa, kontrak.tanggal_akhir
        FROM kontrak
        JOIN aset ON kontrak.aset_id = aset.id
        JOIN penyewa ON kontrak.penyewa_id = penyewa.id
        WHERE kontrak.tanggal_akhir BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY kontrak.tanggal_akhir ASC
    ");
}

// Query jumlah data untuk card
$totalAset = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM aset"))[0];
$totalPenyewa = $isAdmin ? mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM penyewa"))[0] : null;
$totalKontrak = $isAdmin ? mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM kontrak"))[0] : null;
$totalSertifikat = $isAdmin ? mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM sertifikat"))[0] : null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Aset PG</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      display: flex;
      background-color: #CAD2C5;
    }

    .sidebar {
      width: 230px;
      background-color: #2F3E46;
      color: white;
      height: 100vh;
      padding-top: 20px;
      position: fixed;
      transition: width 0.4s cubic-bezier(.4,2,.6,1), box-shadow 0.3s;
      overflow-x: hidden;
      z-index: 10;
    }
    .sidebar.shrink {
      width: 60px;
      box-shadow: 2px 0 8px rgba(44,62,80,0.08);
    }
    .sidebar h2 {
      text-align: center;
      margin-bottom: 30px;
      transition: opacity 0.3s;
    }
    .sidebar.shrink h2 {
      opacity: 0;
      height: 0;
      margin: 0;
      overflow: hidden;
    }
    .sidebar a {
      display: flex;
      align-items: center;
      color: white;
      padding: 12px 20px;
      text-decoration: none;
      transition: background 0.2s, padding 0.4s, font-size 0.4s;
      white-space: nowrap;
      font-size: 16px;
    }
    .sidebar.shrink a {
      padding: 12px 10px;
      font-size: 0;
    }
    .sidebar a:hover {
      background-color: #34495e;
    }
    .sidebar.shrink a:hover {
      background-color: #34495e;
    }
    .sidebar .icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 28px;
      min-width: 28px;
      height: 28px;
      margin-right: 12px;
      border-radius: 6px;
      background: rgba(255,255,255,0.08);
      transition: margin 0.4s, font-size 0.4s, background 0.3s;
      font-size: 0;
    }
    .sidebar .icon svg {
      width: 18px;
      height: 18px;
      display: block;
    }
    .sidebar .icon.home { background: #2F3E46; }
    .sidebar .icon.aset { background: #43a047; }
    .sidebar .icon.penyewa { background: #fbc02d; }
    .sidebar .icon.kontrak { background: #8e24aa; }
    .sidebar .icon.sertifikat { background: #e64a19; }
    .sidebar .icon.laporan { background: #00838f; }
    .sidebar .icon.logout { background: #c62828; }
    .sidebar.shrink .icon {
      margin-right: 0;
    }
    .sidebar .label {
      display: inline-block;
      transition: opacity 0.3s, width 0.4s;
      opacity: 1;
      width: auto;
      font-size: 16px;
    }
    .sidebar.shrink .label {
      opacity: 0;
      width: 0;
      font-size: 0;
      overflow: hidden;
    }

    .content {
      margin-left: 230px;
      padding: 30px;
      width: calc(100% - 230px);
      transition: margin-left 0.4s cubic-bezier(.4,2,.6,1), width 0.4s cubic-bezier(.4,2,.6,1);
    }
    .sidebar.shrink ~ .content {
      margin-left: 60px;
      width: calc(100% - 60px);
    }

    h1 {
      margin-bottom: 10px;
    }

    .notif-box {
      background-color: #fff3cd;
      border: 1px solid #ffeeba;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 6px;
      color: #856404;
    }

    .notif-box strong {
      display: block;
      margin-bottom: 8px;
    }

    .cards {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
    }

    .card {
      background-color: white;
      border-radius: 12px;
      padding: 20px;
      flex: 1;
      min-width: 200px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .card h3 {
      font-size: 16px;
      color: #555;
    }

    .card p {
      font-size: 28px;
      font-weight: bold;
      color: #2c3e50;
      margin-top: 10px;
    }

    .footer {
      margin-top: 40px;
      font-size: 14px;
      color: #777;
    }
  </style>
  <script>
    let sidebarTimer;
    let sidebar = null;
    function shrinkSidebar() {
      if (!sidebar) sidebar = document.querySelector('.sidebar');
      if (sidebar && !sidebar.classList.contains('shrink')) {
        sidebar.classList.add('shrink');
      }
    }
    function expandSidebar() {
      if (!sidebar) sidebar = document.querySelector('.sidebar');
      if (sidebar && sidebar.classList.contains('shrink')) {
        sidebar.classList.remove('shrink');
      }
      resetSidebarTimer();
    }
    function resetSidebarTimer() {
      clearTimeout(sidebarTimer);
      sidebarTimer = setTimeout(shrinkSidebar, 7000); // 7 detik idle
    }
    document.addEventListener('DOMContentLoaded', function() {
      sidebar = document.querySelector('.sidebar');
      resetSidebarTimer();
      document.addEventListener('mousemove', function(e) {
        // Jika mouse di area sidebar, expand
        if (e.clientX <= (sidebar ? sidebar.offsetWidth : 230)) {
          expandSidebar();
        } else {
          resetSidebarTimer();
        }
      });
      // Expand saat mouse masuk ke sidebar
      sidebar.addEventListener('mouseenter', expandSidebar);
      // Shrink saat mouse keluar, tapi tetap tunggu timer
      sidebar.addEventListener('mouseleave', resetSidebarTimer);
    });
  </script>
</head>
<body>

<div class="sidebar">
  <h2>Aset PG</h2>
  <a href="dashboard.php"><span class="icon home"><svg fill="#fff" viewBox="0 0 24 24"><path d="M10.19 2.62a2.25 2.25 0 0 1 3.62 0l7.5 10.5A2.25 2.25 0 0 1 19.5 16.5h-15a2.25 2.25 0 0 1-1.81-3.38l7.5-10.5zM12 4.5L4.5 15h15L12 4.5zm-1.5 9.75v3.75h3v-3.75h-3z"/></svg></span><span class="label">Dashboard</span></a>
  <a href="pages/aset.php"><span class="icon aset"><svg fill="#fff" viewBox="0 0 24 24"><path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7zm2 0v10h14V7H5zm2 2h10v2H7V9zm0 4h6v2H7v-2z"/></svg></span><span class="label">Data Aset</span></a>

  <?php if ($isAdmin): ?>
    <a href="pages/penyewa.php"><span class="icon penyewa"><svg fill="#fff" viewBox="0 0 24 24"><path d="M12 12c2.7 0 8 1.34 8 4v2H4v-2c0-2.66 5.3-4 8-4zm0-2a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/></svg></span><span class="label">Data Penyewa</span></a>
    <a href="pages/kontrak.php"><span class="icon kontrak"><svg fill="#fff" viewBox="0 0 24 24"><path d="M17 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h10zm0 2H7v14h10V5zm-2 2v2H9V7h6zm0 4v2H9v-2h6zm0 4v2H9v-2h6z"/></svg></span><span class="label">Kontrak</span></a>
    <a href="pages/sertifikat.php"><span class="icon sertifikat"><svg fill="#fff" viewBox="0 0 24 24"><path d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H6zm0 2h12v16H6V4zm2 2v2h8V6H8zm0 4v2h8v-2H8zm0 4v2h5v-2H8z"/></svg></span><span class="label">Sertifikat</span></a>
  <?php endif; ?>

  <a href="pages/laporan.php"><span class="icon laporan"><svg fill="#fff" viewBox="0 0 24 24"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 2v2H5V5h14zm0 4v10H5V9h14zm-2 2H7v2h10v-2zm0 4H7v2h10v-2z"/></svg></span><span class="label">Cetak</span></a>
  <a href="logout.php"><span class="icon logout"><svg fill="#fff" viewBox="0 0 24 24"><path d="M16 13v-2H7V8l-5 4 5 4v-3h9zm3-10H5a2 2 0 0 0-2 2v6h2V5h14v14H5v-6H3v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/></svg></span><span class="label">Logout</span></a>
</div>

<div class="content">
  <h1>Selamat datang, <?= htmlspecialchars($_SESSION['user']) ?>!</h1>
  <p>
    <?php if ($isAdmin): ?>
      Anda login sebagai <b>Admin</b>. Anda memiliki akses penuh ke sistem.
    <?php else: ?>
      Anda login sebagai <b>Pengguna</b>. Anda hanya dapat melihat data aset dan laporan.
    <?php endif; ?>
  </p>

  <?php if ($isAdmin && mysqli_num_rows($notifikasi) > 0): ?>
    <div class="notif-box">
      <strong>🔔 Peringatan Kontrak Hampir Habis:</strong>
      <ul>
        <?php while ($row = mysqli_fetch_assoc($notifikasi)) :
            $tgl = new DateTime($row['tanggal_akhir']);
            $sisa = (new DateTime())->diff($tgl)->days;
        ?>
          <li>
            Aset <b><?= htmlspecialchars($row['nama_aset']) ?></b> (oleh <b><?= htmlspecialchars($row['nama_penyewa']) ?></b>) 
            akan berakhir dalam <b><?= $sisa ?> hari</b> (<?= $tgl->format('d M Y') ?>)
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="cards">
    <div class="card">
      <h3>Total Aset</h3>
      <p><?= $totalAset ?></p>
    </div>

    <?php if ($isAdmin): ?>
      <div class="card">
        <h3>Penyewa Aktif</h3>
        <p><?= $totalPenyewa ?></p>
      </div>
      <div class="card">
        <h3>Kontrak Aktif</h3>
        <p><?= $totalKontrak ?></p>
      </div>
      <div class="card">
        <h3>Sertifikat</h3>
        <p><?= $totalSertifikat ?></p>
      </div>
    <?php endif; ?>
  </div>

  <div class="footer">
    &copy; <?= date('Y') ?> Aset PG. All rights reserved.
  </div>
</div>

</body>
</html>
