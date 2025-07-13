<?php
session_start();

// Cek apakah sudah login
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require 'koneksi.php';

// Handle actions
$success_message = '';
$error_message = '';

if (isset($_POST['action'])) {
    $admin_id = $_SESSION['admin_id'] ?? 1;

    switch ($_POST['action']) {
        case 'edit_booking':
            $id = (int)$_POST['id'];
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $phone = mysqli_real_escape_string($conn, $_POST['phone']);
            $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
            $package = mysqli_real_escape_string($conn, $_POST['package']);
            $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
            $payment_amount = (int)$_POST['payment_amount'];
            $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? '');
            $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes'] ?? '');

            $query = "UPDATE bookings SET name = ?, phone = ?, email = ?, package = ?, booking_date = ?, 
                     status = ?, payment_status = ?, payment_amount = ?, payment_method = ?, admin_notes = ?, updated_at = NOW() 
                     WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssssiisi", $name, $phone, $email, $package, $booking_date, $status, $payment_status, $payment_amount, $payment_method, $admin_notes, $id);
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Data booking berhasil diperbarui!";
                logAdminActivity($conn, $admin_id, 'edit_booking', "Edited booking ID: $id");
            } else {
                $error_message = "Gagal memperbarui data booking: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
            break;
    }
}

// Fetch bookings
$bookings = mysqli_query($conn, "SELECT * FROM bookings ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Kelola Bookings - Depictworks</title>
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
    <a href="bookings.php" class="active">Bookings</a>
    <a href="paket.php">Paket</a>
    <a href="gallery.php">Gallery</a>
</nav>
<main>
    <?php if (isset($success_message)): ?><div class="alert"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if (isset($error_message)): ?><div class="alert error"><?php echo $error_message; ?></div><?php endif; ?>
    <div class="section">
        <h2>Kelola Bookings</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Phone</th>
                    <th>Paket</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($bookings)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo e($row['name']); ?></td>
                    <td><?php echo e($row['phone']); ?></td>
                    <td><?php echo e($row['package']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['booking_date'])); ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><a href="javascript:void(0)" class="btn" onclick="editBooking(<?php echo $row['id']; ?>)">Edit</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
function editBooking(id) {
    // Simulasi edit (bisa diintegrasikan dengan modal seperti admin.php)
    alert('Edit booking ID: ' + id);
    // Tambahkan logika AJAX atau modal di sini
}
</script>
</body>
</html>