<?php
session_start();
include '../includes/db.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Ambil semua penyewa untuk dropdown
$penyewaResult = mysqli_query($conn, "SELECT * FROM penyewa");

// Tambah Aset
if (isset($_POST['tambah'])) {
    $nama     = $_POST['nama'];
    $kecamatan = $_POST['kecamatan'];
    $penyewa  = $_POST['penyewa'];
    $tanggal  = $_POST['tanggal_input'];

    $insert = mysqli_query($conn, "INSERT INTO aset (nama_aset, kecamatan, penyewa_id, tanggal_input)
                                   VALUES ('$nama', '$kecamatan', $penyewa, '$tanggal')");
}

// Update Aset
if (isset($_POST['update'])) {
    $id       = $_POST['id'];
    $nama     = $_POST['nama'];
    $kecamatan = $_POST['kecamatan'];
    $penyewa  = $_POST['penyewa'];
    $tanggal  = $_POST['tanggal_input'];
    $update = mysqli_query($conn, "UPDATE aset SET nama_aset='$nama', kecamatan='$kecamatan', penyewa_id=$penyewa, tanggal_input='$tanggal' WHERE id=$id");
    header("Location: aset.php");
    exit;
}

// Hapus Aset
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM aset WHERE id=$id");
    header("Location: aset.php");
    exit;
}

// Ambil data aset + join penyewa
$data = mysqli_query($conn, "SELECT aset.*, penyewa.nama AS nama_penyewa 
                             FROM aset
                             LEFT JOIN penyewa ON aset.penyewa_id = penyewa.id");

// Ambil data aset untuk edit jika ada parameter edit
$editData = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $editRes = mysqli_query($conn, "SELECT * FROM aset WHERE id=$edit_id");
    if ($editRes && mysqli_num_rows($editRes) > 0) {
        $editData = mysqli_fetch_assoc($editRes);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Data Aset</title>
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
    table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 10px; text-align: left; }
    form { background: white; padding: 20px; margin-bottom: 30px; border-radius: 8px; }
    input, select { width: 100%; padding: 8px; margin-bottom: 10px; }
    button { padding: 10px 15px; background: #52796F; color: white; border: none; cursor: pointer; }
    button:hover { background: #52796F; }
    a { color: red; text-decoration: none; }
  </style>
</head>
<body>
  <div class="content">
    <?php if ($editData): ?>
      <h2>Edit Aset</h2>
      <form method="POST">
        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <input type="text" name="nama" placeholder="Nama Aset" value="<?= htmlspecialchars($editData['nama_aset']) ?>" required>
        <input type="text" name="kecamatan" placeholder="Kecamatan" value="<?= htmlspecialchars($editData['kecamatan']) ?>">
        <label for="penyewa">Pilih Penyewa:</label>
        <select name="penyewa" required>
          <option value="">-- Pilih Penyewa --</option>
          <?php
          $penyewaResultEdit = mysqli_query($conn, "SELECT * FROM penyewa");
          while($p = mysqli_fetch_assoc($penyewaResultEdit)):
          ?>
            <option value="<?= $p['id'] ?>" <?= $p['id'] == $editData['penyewa_id'] ? 'selected' : '' ?>><?= $p['nama'] ?> (<?= $p['kontak'] ?>)</option>
          <?php endwhile; ?>
        </select>
        <label for="tanggal_input">Tanggal Input:</label>
        <input type="date" name="tanggal_input" value="<?= $editData['tanggal_input'] ?>" required>
        <button type="submit" name="update">Update</button>
        <a href="aset.php" style="margin-left:10px;color:#333;">Batal</a>
      </form>
    <?php else: ?>
      <h2>Tambah Aset</h2>
      <form method="POST">
        <input type="text" name="nama" placeholder="Nama Aset" required>
        <input type="text" name="kecamatan" placeholder="Kecamatan">
        <label for="penyewa">Pilih Penyewa:</label>
        <select name="penyewa" required>
          <option value="">-- Pilih Penyewa --</option>
          <?php while($p = mysqli_fetch_assoc($penyewaResult)): ?>
            <option value="<?= $p['id'] ?>"><?= $p['nama'] ?> (<?= $p['kontak'] ?>)</option>
          <?php endwhile; ?>
        </select>
        <label for="tanggal_input">Tanggal Input:</label>
        <input type="date" name="tanggal_input" required>
        <button type="submit" name="tambah">Simpan</button>
      </form>
    <?php endif; ?>

    <h2>Daftar Aset</h2>
    <table>
      <tr>
        <th>No</th>
        <th>Nama Aset</th>
        <th>Kecamatan</th>
        <th>Penyewa</th>
        <th>Tanggal Input</th>
        <th>Aksi</th>
      </tr>
      <?php $no=1; while ($row = mysqli_fetch_assoc($data)) : ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
        <td><?= htmlspecialchars($row['kecamatan']) ?></td>
        <td><?= htmlspecialchars($row['nama_penyewa'] ?? '-') ?></td>
        <td><?= htmlspecialchars($row['tanggal_input']) ?></td>
        <td>
          <a href="aset.php?edit=<?= $row['id'] ?>" title="Edit" style="color:#1976d2;vertical-align:middle;display:inline-block;margin-right:10px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;">
              <path d="M4 21h17M14.7 4.29a1 1 0 0 1 1.42 0l3.59 3.59a1 1 0 0 1 0 1.42l-9.17 9.17-4.24 1.06 1.06-4.24 9.17-9.17z" stroke="#1976d2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </a>
          <a href="aset.php?hapus=<?= $row['id'] ?>" title="Hapus" onclick="return confirm('Yakin hapus?')" style="color:#c62828;vertical-align:middle;display:inline-block;">
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
  </div>
</body>
</html>
