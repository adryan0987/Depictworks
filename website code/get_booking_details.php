<?php
session_start();

// Cek apakah sudah login
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    exit('Unauthorized');
}

require 'koneksi.php';

// Get booking ID
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id <= 0) {
    http_response_code(400);
    exit('Invalid booking ID');
}

// Get booking details
$query = "SELECT b.*, 
          (SELECT harga FROM paket WHERE nama_paket = b.package LIMIT 1) as package_price
          FROM bookings b 
          WHERE b.id = $booking_id";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    http_response_code(404);
    exit('Booking not found');
}

$booking = mysqli_fetch_assoc($result);

// Check output format
if (isset($_GET['format']) && $_GET['format'] == 'json') {
    // Return JSON for AJAX
    header('Content-Type: application/json');
    echo json_encode($booking);
} else {
    // Return HTML for modal
    ?>
    <div class="booking-details">
        <div class="detail-grid">
            <div class="detail-section">
                <h4>Informasi Customer</h4>
                <table class="detail-table">
                    <tr>
                        <td><strong>Nama:</strong></td>
                        <td><?php echo e($booking['name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>No. WhatsApp:</strong></td>
                        <td><?php echo e($booking['phone']); ?></td>
                    </tr>
                    <?php if ($booking['email']): ?>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo e($booking['email']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="detail-section">
                <h4>Detail Booking</h4>
                <table class="detail-table">
                    <tr>
                        <td><strong>ID Booking:</strong></td>
                        <td>#<?php echo $booking['id']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Paket:</strong></td>
                        <td><?php echo e($booking['package']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Harga Paket:</strong></td>
                        <td><?php echo rupiah($booking['package_price'] ?? 0); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Booking:</strong></td>
                        <td><?php echo date('d F Y', strtotime($booking['booking_date'])); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="detail-section">
                <h4>Status & Pembayaran</h4>
                <table class="detail-table">
                    <tr>
                        <td><strong>Status Booking:</strong></td>
                        <td>
                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status Pembayaran:</strong></td>
                        <td>
                            <span class="status-badge payment-<?php echo $booking['payment_status'] ?? 'unpaid'; ?>">
                                <?php echo ucfirst($booking['payment_status'] ?? 'unpaid'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php if ($booking['payment_amount'] > 0): ?>
                    <tr>
                        <td><strong>Jumlah Dibayar:</strong></td>
                        <td><?php echo rupiah($booking['payment_amount']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($booking['payment_method']): ?>
                    <tr>
                        <td><strong>Metode Pembayaran:</strong></td>
                        <td><?php echo ucfirst($booking['payment_method']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="detail-section">
                <h4>Informasi Lainnya</h4>
                <table class="detail-table">
                    <tr>
                        <td><strong>Dibuat:</strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Terakhir Update:</strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($booking['updated_at'])); ?></td>
                    </tr>
                </table>
                
                <?php if ($booking['notes']): ?>
                <div class="notes-section">
                    <strong>Catatan Customer:</strong>
                    <p><?php echo nl2br(e($booking['notes'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($booking['admin_notes']): ?>
                <div class="notes-section">
                    <strong>Catatan Admin:</strong>
                    <p><?php echo nl2br(e($booking['admin_notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="detail-actions">
            <button class="btn btn-warning" onclick="updateStatus(<?php echo $booking['id']; ?>)">
                <i class="fas fa-edit"></i> Update Status
            </button>
            <button class="btn btn-success" onclick="sendWhatsApp(<?php echo $booking['id']; ?>)">
                <i class="fab fa-whatsapp"></i> Kirim WhatsApp
            </button>
            <a href="print_booking.php?id=<?php echo $booking['id']; ?>" target="_blank" class="btn btn-info">
                <i class="fas fa-print"></i> Print
            </a>
        </div>
    </div>
    
    <style>
    .booking-details {
        padding: 10px;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .detail-section {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 6px;
    }
    
    .detail-section h4 {
        margin: 0 0 15px 0;
        color: #3E2723;
        font-size: 1.1em;
    }
    
    .detail-table {
        width: 100%;
        border: none;
    }
    
    .detail-table tr {
        background: none;
    }
    
    .detail-table td {
        padding: 5px 0;
        border: none;
        font-size: 0.9em;
    }
    
    .detail-table td:first-child {
        width: 40%;
        color: #666;
    }
    
    .notes-section {
        margin-top: 15px;
        padding: 10px;
        background: #fff;
        border-left: 3px solid #5D4037;
        border-radius: 3px;
    }
    
    .notes-section p {
        margin: 5px 0 0 0;
        color: #555;
    }
    
    .detail-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }
    </style>
    <?php
}
?>