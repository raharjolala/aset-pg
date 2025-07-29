<?php
session_start();
include '../includes/db.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    echo "Akses ditolak. Halaman ini hanya untuk admin.";
    exit;
}

// Tambah Sertifikat
if (isset($_POST['tambah'])) {
    $aset_id = $_POST['aset_id'];
    $nomor   = $_POST['nomor_sertifikat'];
    $tanggal = $_POST['tanggal_terbit'];

    // Handle upload
    $upload_dir = '../uploads/';
    $file_name  = basename($_FILES['file']['name']);
    $target_path = $upload_dir . $file_name;
    $upload_ok = move_uploaded_file($_FILES['file']['tmp_name'], $target_path);

    if ($upload_ok) {
        mysqli_query($conn, "INSERT INTO sertifikat (aset_id, nomor_sertifikat, tanggal_terbit, file_path)
                             VALUES ($aset_id, '$nomor', '$tanggal', '$file_name')");
    }
}

// Update Sertifikat
if (isset($_POST['update'])) {
    $id      = $_POST['id'];
    $aset_id = $_POST['aset_id'];
    $nomor   = $_POST['nomor_sertifikat'];
    $tanggal = $_POST['tanggal_terbit'];
    $file_name = $_POST['file_lama'];

    // Jika ada file baru diupload
    if (isset($_FILES['file']) && $_FILES['file']['name']) {
        $upload_dir = '../uploads/';
        $file_name_new  = basename($_FILES['file']['name']);
        $target_path = $upload_dir . $file_name_new;
        $upload_ok = move_uploaded_file($_FILES['file']['tmp_name'], $target_path);
        if ($upload_ok) {
            // Hapus file lama
            if ($file_name && file_exists($upload_dir . $file_name)) {
                unlink($upload_dir . $file_name);
            }
            $file_name = $file_name_new;
        }
    }
    mysqli_query($conn, "UPDATE sertifikat SET aset_id=$aset_id, nomor_sertifikat='$nomor', tanggal_terbit='$tanggal', file_path='$file_name' WHERE id=$id");
    header("Location: sertifikat.php");
    exit;
}

// Hapus Sertifikat
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    // Hapus file dari folder
    $getFile = mysqli_query($conn, "SELECT file_path FROM sertifikat WHERE id=$id");
    $file = mysqli_fetch_assoc($getFile);
    unlink("../uploads/" . $file['file_path']);

    mysqli_query($conn, "DELETE FROM sertifikat WHERE id=$id");
    header("Location: sertifikat.php");
    exit;
}

// Ambil data aset dan sertifikat
$asetList = mysqli_query($conn, "SELECT id, nama_aset FROM aset");
$data = mysqli_query($conn, "SELECT sertifikat.*, aset.nama_aset 
                             FROM sertifikat 
                             JOIN aset ON sertifikat.aset_id = aset.id");

// Ambil data sertifikat untuk edit jika ada parameter edit
$editData = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $editRes = mysqli_query($conn, "SELECT * FROM sertifikat WHERE id=$edit_id");
    if ($editRes && mysqli_num_rows($editRes) > 0) {
        $editData = mysqli_fetch_assoc($editRes);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Data Sertifikat</title>
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
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 10px; text-align: left; }
    a { color: red; text-decoration: none; }
  </style>
</head>
<body>
  <div class="content">
    <?php if ($editData): ?>
      <h2>Edit Sertifikat</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <input type="hidden" name="file_lama" value="<?= htmlspecialchars($editData['file_path']) ?>">
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
        <input type="text" name="nomor_sertifikat" placeholder="Nomor Sertifikat" value="<?= htmlspecialchars($editData['nomor_sertifikat']) ?>" required>
        <input type="date" name="tanggal_terbit" value="<?= $editData['tanggal_terbit'] ?>" required>
        <label>File PDF (kosongkan jika tidak ingin ganti):</label>
        <input type="file" name="file" accept=".pdf">
        <div style="margin-bottom:10px;">File saat ini: <a href="../uploads/<?= $editData['file_path'] ?>" target="_blank">Lihat PDF</a></div>
        <button type="submit" name="update">Update Sertifikat</button>
        <a href="sertifikat.php" style="margin-left:10px;color:#333;">Batal</a>
      </form>
    <?php else: ?>
      <h2>Tambah Sertifikat</h2>
      <form method="POST" enctype="multipart/form-data">
        <label for="aset_id">Pilih Aset:</label>
        <select name="aset_id" required>
          <option value="">-- Pilih Aset --</option>
          <?php while ($a = mysqli_fetch_assoc($asetList)): ?>
            <option value="<?= $a['id'] ?>"><?= $a['nama_aset'] ?></option>
          <?php endwhile; ?>
        </select>
        <input type="text" name="nomor_sertifikat" placeholder="Nomor Sertifikat" required>
        <input type="date" name="tanggal_terbit" required>
        <input type="file" name="file" accept=".pdf" required>
        <button type="submit" name="tambah">Simpan Sertifikat</button>
      </form>
    <?php endif; ?>

    <h2>Daftar Sertifikat</h2>
    <table>
      <tr>
        <th>No</th>
        <th>Nama Aset</th>
        <th>Nomor Sertifikat</th>
        <th>Tanggal Terbit</th>
        <th>File</th>
        <th>Aksi</th>
      </tr>
      <?php $no = 1; while ($row = mysqli_fetch_assoc($data)) : ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
        <td><?= htmlspecialchars($row['nomor_sertifikat']) ?></td>
        <td><?= $row['tanggal_terbit'] ?></td>
        <td><a href="../uploads/<?= $row['file_path'] ?>" target="_blank">Lihat PDF</a></td>
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
