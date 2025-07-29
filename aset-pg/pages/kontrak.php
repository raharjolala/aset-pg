<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    echo "Akses ditolak. Halaman ini hanya untuk admin.";
    exit;
}

// Tambah kontrak
if (isset($_POST['tambah'])) {
    $aset_id       = $_POST['aset_id'];
    $penyewa_id    = $_POST['penyewa_id'];
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_akhir = $_POST['tanggal_akhir'];

    $insert = mysqli_query($conn, "INSERT INTO kontrak (aset_id, penyewa_id, tanggal_mulai, tanggal_akhir)
                                   VALUES ($aset_id, $penyewa_id, '$tanggal_mulai', '$tanggal_akhir')");
}

// Edit kontrak (update) dengan log history
if (isset($_POST['update'])) {
    $id            = $_POST['id'];
    $aset_id       = $_POST['aset_id'];
    $penyewa_id    = $_POST['penyewa_id'];
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_akhir = $_POST['tanggal_akhir'];
    // Ambil data lama
    $old = mysqli_query($conn, "SELECT * FROM kontrak WHERE id=$id");
    $oldData = mysqli_fetch_assoc($old);
    // Simpan ke history
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : '';
    mysqli_query($conn, "INSERT INTO kontrak_history (kontrak_id, aset_id, penyewa_id, tanggal_mulai, tanggal_akhir, aksi, user, waktu) VALUES (
        $id, {$oldData['aset_id']}, {$oldData['penyewa_id']}, '{$oldData['tanggal_mulai']}', '{$oldData['tanggal_akhir']}', 'update', '$user', NOW()
    )");
    // Update kontrak
    $update = mysqli_query($conn, "UPDATE kontrak SET aset_id=$aset_id, penyewa_id=$penyewa_id, tanggal_mulai='$tanggal_mulai', tanggal_akhir='$tanggal_akhir' WHERE id=$id");
    header("Location: kontrak.php");
    exit;
}

// Hapus kontrak dengan log history
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Ambil data lama
    $old = mysqli_query($conn, "SELECT * FROM kontrak WHERE id=$id");
    $oldData = mysqli_fetch_assoc($old);
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : '';
    mysqli_query($conn, "INSERT INTO kontrak_history (kontrak_id, aset_id, penyewa_id, tanggal_mulai, tanggal_akhir, aksi, user, waktu) VALUES (
        $id, {$oldData['aset_id']}, {$oldData['penyewa_id']}, '{$oldData['tanggal_mulai']}', '{$oldData['tanggal_akhir']}', 'delete', '$user', NOW()
    )");
    mysqli_query($conn, "DELETE FROM kontrak WHERE id=$id");
    header("Location: kontrak.php");
    exit;
}

// Ambil data untuk form
$asetList    = mysqli_query($conn, "SELECT id, nama_aset FROM aset");
$penyewaList = mysqli_query($conn, "SELECT id, nama FROM penyewa");

// Ambil data kontrak lengkap
$data = mysqli_query($conn, "SELECT kontrak.*, aset.nama_aset, penyewa.nama AS nama_penyewa
                             FROM kontrak
                             JOIN aset ON kontrak.aset_id = aset.id
                             JOIN penyewa ON kontrak.penyewa_id = penyewa.id");

// Ambil data kontrak untuk edit jika ada parameter edit
$editData = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $editRes = mysqli_query($conn, "SELECT * FROM kontrak WHERE id=$edit_id");
    if ($editRes && mysqli_num_rows($editRes) > 0) {
        $editData = mysqli_fetch_assoc($editRes);
    }
}

include '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html>
<head>
  <title>Data Kontrak</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #CAD2C5;
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
    h2 { margin-bottom: 10px; }
    form {
      background: white;
      padding: 20px;
      margin-bottom: 30px;
      border-radius: 8px;
    }
    input, select {
      width: 100%;
      padding: 8px;
      margin-bottom: 10px;
    }
    button {
      padding: 10px 15px;
      background: #52796F;
      color: white;
      border: none;
      cursor: pointer;
    }
    button:hover { background: #52796F; }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
    }
    table, th, td {
      border: 1px solid #ccc;
    }
    th, td {
      padding: 10px;
      text-align: left;
    }
    a {
      color: red;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="content">
    <?php if ($editData): ?>
      <h2>Edit Kontrak</h2>
      <form method="POST">
        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <label for="aset_id">Pilih Aset:</label>
        <select name="aset_id" required>
          <option value="">-- Pilih Aset --</option>
          <?php
          $asetListEdit = mysqli_query($conn, "SELECT id, nama_aset FROM aset");
          while ($a = mysqli_fetch_assoc($asetListEdit)):
          ?>
            <option value="<?= $a['id'] ?>" <?= $a['id'] == $editData['aset_id'] ? 'selected' : '' ?>><?= $a['nama_aset'] ?></option>
          <?php endwhile; ?>
        </select>

        <label for="penyewa_id">Pilih Penyewa:</label>
        <select name="penyewa_id" required>
          <option value="">-- Pilih Penyewa --</option>
          <?php
          $penyewaListEdit = mysqli_query($conn, "SELECT id, nama FROM penyewa");
          while ($p = mysqli_fetch_assoc($penyewaListEdit)):
          ?>
            <option value="<?= $p['id'] ?>" <?= $p['id'] == $editData['penyewa_id'] ? 'selected' : '' ?>><?= $p['nama'] ?></option>
          <?php endwhile; ?>
        </select>

        <label for="tanggal_mulai">Tanggal Mulai:</label>
        <input type="date" name="tanggal_mulai" value="<?= $editData['tanggal_mulai'] ?>" required>

        <label for="tanggal_akhir">Tanggal Akhir:</label>
        <input type="date" name="tanggal_akhir" value="<?= $editData['tanggal_akhir'] ?>" required>

        <button type="submit" name="update">Update Kontrak</button>
        <a href="kontrak.php" style="margin-left:10px;color:#333;">Batal</a>
      </form>
    <?php else: ?>
      <h2>Tambah Kontrak</h2>
      <form method="POST">
        <label for="aset_id">Pilih Aset:</label>
        <select name="aset_id" required>
          <option value="">-- Pilih Aset --</option>
          <?php
          $asetList2 = mysqli_query($conn, "SELECT id, nama_aset FROM aset");
          while ($a = mysqli_fetch_assoc($asetList2)):
          ?>
            <option value="<?= $a['id'] ?>"><?= $a['nama_aset'] ?></option>
          <?php endwhile; ?>
        </select>

        <label for="penyewa_id">Pilih Penyewa:</label>
        <select name="penyewa_id" required>
          <option value="">-- Pilih Penyewa --</option>
          <?php
          $penyewaList2 = mysqli_query($conn, "SELECT id, nama FROM penyewa");
          while ($p = mysqli_fetch_assoc($penyewaList2)):
          ?>
            <option value="<?= $p['id'] ?>"><?= $p['nama'] ?></option>
          <?php endwhile; ?>
        </select>

        <label for="tanggal_mulai">Tanggal Mulai:</label>
        <input type="date" name="tanggal_mulai" required>

        <label for="tanggal_akhir">Tanggal Akhir:</label>
        <input type="date" name="tanggal_akhir" required>

        <button type="submit" name="tambah">Simpan Kontrak</button>
      </form>
    <?php endif; ?>

    <h2>Daftar Kontrak</h2>
    <table>
      <tr>
        <th>No</th>
        <th>Nama Aset</th>
        <th>Penyewa</th>
        <th>Tanggal Mulai</th>
        <th>Tanggal Akhir</th>
        <th>Aksi</th>
      </tr>
      <?php $no=1; while ($row = mysqli_fetch_assoc($data)) : ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
        <td><?= htmlspecialchars($row['nama_penyewa']) ?></td>
        <td><?= $row['tanggal_mulai'] ?></td>
        <td><?= $row['tanggal_akhir'] ?></td>
        <td>
          <a href="kontrak.php?edit=<?= $row['id'] ?>" title="Edit" style="color:#1976d2;vertical-align:middle;display:inline-block;margin-right:10px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;">
              <path d="M4 21h17M14.7 4.29a1 1 0 0 1 1.42 0l3.59 3.59a1 1 0 0 1 0 1.42l-9.17 9.17-4.24 1.06 1.06-4.24 9.17-9.17z" stroke="#1976d2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </a>
          <a href="kontrak.php?hapus=<?= $row['id'] ?>" title="Hapus" onclick="return confirm('Yakin hapus?')" style="color:#c62828;vertical-align:middle;display:inline-block;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;">
              <rect x="3" y="6" width="18" height="14" rx="2" stroke="#c62828" stroke-width="2"/>
              <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="#c62828" stroke-width="2"/>
              <path d="M10 11v6M14 11v6" stroke="#c62828" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>

    <h3 style="margin-top:40px;">Riwayat Perubahan Kontrak</h3>
    <table style="margin-bottom:40px;">
      <tr>
        <th>No</th>
        <th>Nama Aset</th>
        <th>Penyewa</th>
        <th>Tanggal Mulai</th>
        <th>Tanggal Akhir</th>
        <th>Aksi</th>
        <th>User</th>
        <th>Waktu</th>
      </tr>
      <?php
      $history = mysqli_query($conn, "SELECT h.*, a.nama_aset, p.nama AS nama_penyewa FROM kontrak_history h
        LEFT JOIN aset a ON h.aset_id = a.id
        LEFT JOIN penyewa p ON h.penyewa_id = p.id
        ORDER BY h.waktu DESC LIMIT 30");
      $no=1;
      while ($h = mysqli_fetch_assoc($history)) : ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($h['nama_aset']) ?></td>
          <td><?= htmlspecialchars($h['nama_penyewa']) ?></td>
          <td><?= $h['tanggal_mulai'] ?></td>
          <td><?= $h['tanggal_akhir'] ?></td>
          <td><?= ucfirst($h['aksi']) ?></td>
          <td><?= htmlspecialchars($h['user']) ?></td>
          <td><?= $h['waktu'] ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</body>
</html>
