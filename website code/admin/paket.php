<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require 'koneksi.php';

$success_message = '';
$error_message = '';

if (isset($_POST['action'])) {
    $admin_id = $_SESSION['admin_id'] ?? 1;

    switch ($_POST['action']) {
        case 'add_package':
            $nama_paket = mysqli_real_escape_string($conn, $_POST['nama_paket']);
            $harga = (int)$_POST['harga'];
            $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
            $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
            $query = "INSERT INTO paket (nama_paket, harga, kategori, keterangan, is_active) VALUES (?, ?, ?, ?, 1)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "siss", $nama_paket, $harga, $kategori, $keterangan);
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Paket berhasil ditambahkan!";
                logAdminActivity($conn, $admin_id, 'add_package', "Added $nama_paket");
            } else {
                $error_message = "Gagal menambahkan paket: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
            break;
    }
}

$packages = mysqli_query($conn, "SELECT * FROM paket WHERE is_active = 1 ORDER BY kategori, nama_paket");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Kelola Paket - Depictworks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #FAF9F8; margin: 0; }
        header { background: #3E2723; color: #fff; padding: 10px; text-align: center; }
        .sidebar { position: fixed; left: 0; top: 50px; width: 200px; background: #fff; padding: 10px 0; }
        .sidebar a { display: block; padding: 10px; color: #333; text-decoration: none; }
        .sidebar a:hover { background: #FAF9F8; color: #3E2723; }
        main { margin-left: 200px; padding: 20px; }
        .section { background: #fff; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .section h2 { font-family: 'Playfair Display', serif; color: #3E2723; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #3E2723; color: #fff; }
        .btn { padding: 5px 10px; background: #3E2723; color: #fff; border: none; border-radius: 3px; text-decoration: none; }
        .btn:hover { background: #5D4037; }
    </style>
</head>
<body>
<header>
    <h1>Depictworks Admin</h1>
</header>
<nav class="sidebar">
    <a href="admin.php">Dashboard</a>
    <a href="bookings.php">Bookings</a>
    <a href="paket.php" class="active">Paket</a>
    <a href="gallery.php">Gallery</a>
</nav>
<main>
    <?php if (isset($success_message)): ?><div class="alert"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if (isset($error_message)): ?><div class="alert error"><?php echo $error_message; ?></div><?php endif; ?>
    <div class="section">
        <h2>Kelola Paket</h2>
        <form method="post" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="add_package">
            <input type="text" name="nama_paket" placeholder="Nama Paket" required>
            <input type="number" name="harga" placeholder="Harga" required>
            <select name="kategori" required>
                <option value="outdoor">Outdoor</option>
                <option value="indoor">Indoor</option>
            </select>
            <textarea name="keterangan" placeholder="Keterangan" required></textarea>
            <button type="submit" class="btn">Tambah Paket</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Paket</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($packages)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo e($row['nama_paket']); ?></td>
                    <td><?php echo e($row['kategori']); ?></td>
                    <td><?php echo rupiah($row['harga']); ?></td>
                    <td><?php echo e($row['keterangan']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>