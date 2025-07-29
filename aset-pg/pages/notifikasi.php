<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Ambil kontrak yang akan habis dalam 30 hari
$data = mysqli_query($conn, "
    SELECT kontrak.*, aset.nama_aset, penyewa.nama AS nama_penyewa
    FROM kontrak
    JOIN aset ON kontrak.aset_id = aset.id
    JOIN penyewa ON kontrak.penyewa_id = penyewa.id
    WHERE kontrak.tanggal_akhir BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY kontrak.tanggal_akhir ASC
");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Notifikasi Jatuh Tempo</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #fefefe; }
    h2 { color: #c0392b; }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
    }
    table, th, td {
      border: 1px solid #ccc;
    }
    th, td {
      padding: 10px;
      text-align: left;
    }
    .warning {
      background-color: #f9e79f;
    }
  </style>
</head>
<body>

  <h2>🔔 Kontrak Hampir Habis</h2>

  <table>
    <tr>
      <th>No</th>
      <th>Nama Aset</th>
      <th>Penyewa</th>
      <th>Tanggal Mulai</th>
      <th>Tanggal Akhir</th>
      <th>Sisa Hari</th>
    </tr>
    <?php 
    $no = 1; 
    while ($row = mysqli_fetch_assoc($data)) : 
      $tanggal_akhir = new DateTime($row['tanggal_akhir']);
      $hari_ini = new DateTime();
      $sisa_hari = $hari_ini->diff($tanggal_akhir)->days;
    ?>
    <tr class="warning">
      <td><?= $no++ ?></td>
      <td><?= htmlspecialchars($row['nama_aset']) ?></td>
      <td><?= htmlspecialchars($row['nama_penyewa']) ?></td>
      <td><?= $row['tanggal_mulai'] ?></td>
      <td><?= $row['tanggal_akhir'] ?></td>
      <td><?= $sisa_hari ?> hari lagi</td>
    </tr>
    <?php endwhile; ?>
  </table>

</body>
</html>
