<?php
session_start();

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require 'koneksi.php';

// Handle status update
if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "UPDATE bookings SET status = '$new_status' WHERE id = $booking_id";
    mysqli_query($conn, $query);
    
    header('Location: admin.php');
    exit();
}

// Handle full edit
if (isset($_POST['update_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $package = mysqli_real_escape_string($conn, $_POST['package']);
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "UPDATE bookings SET 
              name = '$name', 
              phone = '$phone', 
              package = '$package',
              booking_date = '$booking_date',
              status = '$status' 
              WHERE id = $booking_id";
    
    mysqli_query($conn, $query);
    header('Location: admin.php');
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $booking_id = (int)$_GET['delete'];
    $query = "DELETE FROM bookings WHERE id = $booking_id";
    mysqli_query($conn, $query);
    header('Location: admin.php');
    exit;
}

// Handle bulk delete
if (isset($_POST['bulk_delete'])) {
    if (isset($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
        $selected_ids = array_map('intval', $_POST['selected_ids']);
        $ids_string = implode(',', $selected_ids);
        $query = "DELETE FROM bookings WHERE id IN ($ids_string)";
        mysqli_query($conn, $query);
    }
    header('Location: admin.php');
    exit;
}

// Get statistics
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings"))['total'];
$pending_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'"))['total'];
$confirmed_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE status = 'confirmed'"))['total'];

// Get bookings with filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$where = $filter ? "WHERE status = '" . mysqli_real_escape_string($conn, $filter) . "'" : "";
$bookings = mysqli_query($conn, "SELECT * FROM bookings $where ORDER BY created_at DESC");

// Get all packages for dropdown
$packages = mysqli_query($conn, "SELECT DISTINCT nama_paket FROM paket WHERE is_active = 1 ORDER BY nama_paket");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Depictworks</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }
        
        /* Header */
        .header {
            background: #3E2723;
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            background: #5D4037;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #4A332E;
        }
        
        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2.5rem;
            color: #3E2723;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #666;
            font-size: 1rem;
        }
        
        /* Filter */
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .filter-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .filter-btn {
            padding: 0.5rem 1rem;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: #3E2723;
            color: white;
        }
        
        .bulk-delete-btn {
            padding: 0.5rem 1rem;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }
        
        .bulk-delete-btn:hover {
            background: #c82333;
        }
        
        .bulk-delete-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        /* Table */
        .table-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #3E2723;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        /* Checkbox */
        .select-checkbox {
            margin-right: 0.5rem;
        }
        
        /* Status Badge */
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Actions */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: #007bff;
            color: white;
        }
        
        .btn-edit:hover {
            background: #0056b3;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: full-width;
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            font-size: 1.5rem;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        .btn-submit {
            background: #3E2723;
            color: white;
        }
        
        .btn-submit:hover {
            background: #2E1A17;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
                gap: 1rem;
            }
            
            .table-section {
                overflow-x: auto;
            }
            
            table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard Admin</h1>
        <div class="header-right">
            <span>Halo, <?php echo e($_SESSION['admin_name']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_bookings; ?></h3>
                <p>Total Booking</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $pending_bookings; ?></h3>
                <p>Menunggu Konfirmasi</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $confirmed_bookings; ?></h3>
                <p>Terkonfirmasi</p>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="filter-section">
            <h2>Data Booking</h2>
            <div class="filter-buttons">
                <a href="admin.php" class="filter-btn <?php echo !$filter ? 'active' : ''; ?>">Semua</a>
                <a href="?filter=pending" class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?filter=confirmed" class="filter-btn <?php echo $filter == 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                <a href="?filter=completed" class="filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">Completed</a>
                <form method="post" style="display: inline;">
                    <button type="submit" name="bulk_delete" class="bulk-delete-btn" id="bulkDeleteBtn" disabled>Hapus Terpilih</button>
                    <input type="hidden" name="selected_ids" id="selected_ids" value="">
                </form>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-section">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>WhatsApp</th>
                        <th>Paket</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($bookings)): ?>
                    <tr>
                        <td><input type="checkbox" class="select-checkbox" name="select_id[]" value="<?php echo $row['id']; ?>" onchange="updateBulkDeleteButton()"></td>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo e($row['name']); ?></td>
                        <td><?php echo e($row['phone']); ?></td>
                        <td><?php echo e($row['package']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['booking_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-edit" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', '<?php echo $row['phone']; ?>', '<?php echo addslashes($row['package']); ?>', '<?php echo $row['booking_date']; ?>', '<?php echo $row['status']; ?>')">Edit</button>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Yakin hapus?')">Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal Update Status -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Status Booking</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="booking_id" id="booking_id">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status_select">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" name="update_status" class="btn btn-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Full Data -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Data Booking</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="booking_id" id="edit_booking_id">
                
                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="name" id="edit_name" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="text" name="phone" id="edit_phone" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group">
                    <label>Paket</label>
                    <select name="package" id="edit_package" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <?php 
                        mysqli_data_seek($packages, 0);
                        while ($pkg = mysqli_fetch_assoc($packages)): 
                        ?>
                            <option value="<?php echo e($pkg['nama_paket']); ?>"><?php echo e($pkg['nama_paket']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Booking</label>
                    <input type="date" name="booking_date" id="edit_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" name="update_booking" class="btn btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(id, status) {
            document.getElementById('booking_id').value = id;
            document.getElementById('status_select').value = status;
            document.getElementById('statusModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('statusModal').classList.remove('active');
        }
        
        function openEditModal(id, name, phone, package, date, status) {
            document.getElementById('edit_booking_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_package').value = package;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_status').value = status;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Select All Checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.select-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
            updateBulkDeleteButton();
        });
        
        // Update Bulk Delete Button State
        function updateBulkDeleteButton() {
            const checkboxes = document.querySelectorAll('.select-checkbox:checked');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            const selectedIdsInput = document.getElementById('selected_ids');
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);
            
            bulkDeleteBtn.disabled = checkboxes.length === 0;
            selectedIdsInput.value = selectedIds.join(',');
        }
        
        // Update button state on page load and checkbox change
        document.querySelectorAll('.select-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkDeleteButton);
        });
        updateBulkDeleteButton();
    </script>
</body>
</html>