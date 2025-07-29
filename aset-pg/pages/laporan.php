<?php
session_start();
include '../includes/db.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Ambil data aset lengkap dengan join ke penyewa, kontrak, sertifikat
$query = "
    SELECT 
        aset.nama_aset,
        aset.kecamatan,
        aset.tanggal_input,
        penyewa.nama AS nama_penyewa,
        penyewa.kontak,
        kontrak.tanggal_mulai,
        kontrak.tanggal_akhir,
        sertifikat.nomor_sertifikat,
        sertifikat.tanggal_terbit
    FROM aset
    LEFT JOIN penyewa ON aset.penyewa_id = penyewa.id
    LEFT JOIN kontrak ON kontrak.aset_id = aset.id
    LEFT JOIN sertifikat ON sertifikat.aset_id = aset.id
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Laporan Aset PG</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f0f0ff;
      color: black;
      margin: 0;
      padding: 0;
      display: flex;
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
    h2 { text-align: center; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table, th, td {
      border: 1px solid #333;
    }
    th, td {
      padding: 8px;
      text-align: left;
    }
    .btn-print {
      display: inline-block;
      margin-bottom: 15px;
      padding: 10px 15px;
      background: #3498db;
      color: white;
      text-decoration: none;
      border-radius: 4px;
    }
    .btn-print:hover {
      background: #2c80b4;
    }

    /* Tambahan untuk header dan footer cetak */
    .print-header, .print-footer {
      display: none;
    }

    @media print {
      .btn-print, .screen-title, .sidebar {
        display: none !important;
      }
      .content {
        margin: 0 !important;
        width: 100% !important;
        padding: 0 !important;
      }
      .print-header {
        display: block !important;
        margin-bottom: 10px;
      }
      .print-title {
        font-size: 2.1em;
        font-weight: bold;
        text-align: center;
        margin-bottom: 2px;
        letter-spacing: 2px;
      }
      .print-date {
        text-align: right;
        font-size: 1em;
        margin-bottom: 8px;
      }
      .print-line {
        border: none;
        border-top: 2px solid #333;
        margin-bottom: 18px;
      }
      .print-footer {
        display: block !important;
        margin-top: 40px;
        font-size: 1em;
      }
    }
    .print-header, .print-footer {
      display: none;
    }
    }
  </style>
</head>
<body>
  <div class="content">
    <a href="#" class="btn-print" onclick="window.print()">🖨️ Cetak Laporan</a>

    <!-- Header khusus cetak -->
    <div class="print-header">
      <div class="print-title">LAPORAN ASET PG</div>
      <div class="print-date">Tanggal Cetak: <?php echo date('d-m-Y'); ?></div>
      <hr class="print-line">
    </div>

    <h2 class="screen-title">Laporan Aset PG</h2>

    <table>
      <tr>
        <th>No</th>
        <th>Nama Aset</th>
        <th>Kecamatan</th>
        <th>Penyewa</th>
        <th>Kontak</th>
        <th>Tanggal Input</th>
        <th>Kontrak Mulai</th>
        <th>Kontrak Akhir</th>
        <th>No. Sertifikat</th>
        <th>Tgl. Terbit</th>
      </tr>
      <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) : ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
        <td><?= htmlspecialchars($row['kecamatan']) ?></td>
        <td><?= htmlspecialchars($row['nama_penyewa'] ?? '-') ?></td>
        <td><?= htmlspecialchars($row['kontak'] ?? '-') ?></td>
        <td><?= $row['tanggal_input'] ?></td>
        <td><?= $row['tanggal_mulai'] ?? '-' ?></td>
        <td><?= $row['tanggal_akhir'] ?? '-' ?></td>
        <td><?= $row['nomor_sertifikat'] ?? '-' ?></td>
        <td><?= $row['tanggal_terbit'] ?? '-' ?></td>
      </tr>
      <?php endwhile; ?>
    </table>

    <!-- Footer khusus cetak -->
    <div class="print-footer">
      <div style="margin-top:60px; width:100%; display:flex; justify-content:space-between;">
        <div style="text-align:center; width:40%;">
          Mengetahui,<br>
          Kepala Bagian<br><br><br><br>
          <span style="text-decoration:underline;">(___________________)</span>
        </div>
        <div style="text-align:center; width:40%;">
          Dicetak oleh,<br>
          Admin<br><br><br><br>
          <span style="text-decoration:underline;">(___________________)</span>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
