<?php
// Konfigurasi database
$host = 'localhost';
$username = 'depictwo_ryan';
$password = 'qwertyuiop0987';
$database = 'depictwo_db';

// Error reporting untuk development (matikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Pertama, coba koneksi tanpa database untuk membuat database jika belum ada
    $conn_temp = mysqli_connect($host, $username, $password);
    
    if (!$conn_temp) {
        throw new Exception("Koneksi gagal: " . mysqli_connect_error());
    }
    
    // Cek apakah database sudah ada, jika tidak buat database
    $db_check = mysqli_query($conn_temp, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");
    if (mysqli_num_rows($db_check) == 0) {
        // Buat database jika belum ada
        if (!mysqli_query($conn_temp, "CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            throw new Exception("Error creating database: " . mysqli_error($conn_temp));
        }
    }
    
    // Tutup koneksi sementara
    mysqli_close($conn_temp);
    
    // Membuat koneksi ke database
    $conn = mysqli_connect($host, $username, $password, $database);
    
    if (!$conn) {
        throw new Exception("Koneksi database gagal: " . mysqli_connect_error());
    }
    
    // Set charset UTF-8
    mysqli_set_charset($conn, "utf8mb4");
    
    // Fungsi untuk mengecek dan membuat tabel
    function createTablesIfNotExist($conn) {
        // Buat tabel admin
        $sql_admin = "CREATE TABLE IF NOT EXISTS `admin` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `nama_lengkap` varchar(100) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!mysqli_query($conn, $sql_admin)) {
            throw new Exception("Error creating admin table: " . mysqli_error($conn));
        }
        
        // Cek apakah sudah ada admin
        $check_admin = mysqli_query($conn, "SELECT * FROM admin LIMIT 1");
        if (mysqli_num_rows($check_admin) == 0) {
            // Insert admin default dengan password ter-hash
            $default_password = password_hash('admin123', PASSWORD_DEFAULT);
            $sql_insert_admin = "INSERT INTO `admin` (`username`, `password`, `nama_lengkap`, `email`) 
                               VALUES ('admin', '$default_password', 'Administrator', 'admin@depictworks.com')";
            mysqli_query($conn, $sql_insert_admin);
        }
        
        // Buat tabel paket
        $sql_paket = "CREATE TABLE IF NOT EXISTS `paket` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `kategori` enum('outdoor','indoor') NOT NULL,
            `nama_paket` varchar(100) NOT NULL,
            `keterangan` text NOT NULL,
            `harga` int(11) NOT NULL,
            `foto_preview` varchar(255) DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_kategori` (`kategori`),
            KEY `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!mysqli_query($conn, $sql_paket)) {
            throw new Exception("Error creating paket table: " . mysqli_error($conn));
        }
        
        // Buat tabel bookings (sesuai dengan index.php)
        $sql_bookings = "CREATE TABLE IF NOT EXISTS `bookings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `phone` varchar(20) NOT NULL,
            `email` varchar(100) DEFAULT NULL,
            `booking_date` date NOT NULL,
            `package` varchar(100) NOT NULL,
            `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
            `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
            `payment_amount` int(11) DEFAULT 0,
            `payment_method` varchar(50) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `admin_notes` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_status` (`status`),
            KEY `idx_booking_date` (`booking_date`),
            KEY `idx_phone` (`phone`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!mysqli_query($conn, $sql_bookings)) {
            throw new Exception("Error creating bookings table: " . mysqli_error($conn));
        }
        
        // Insert paket default jika belum ada
        $check_paket = mysqli_query($conn, "SELECT * FROM paket LIMIT 1");
        if (mysqli_num_rows($check_paket) == 0) {
            $default_pakets = [
                ['outdoor', 'Outdoor Hemat', '2 jam sesi foto + unlimited softfile + 36 edited file', 350000, 'gambar/our gallery/IMG_2273.jpg'],
                ['outdoor', 'Outdoor Lengkap', '4 jam sesi foto + unlimited softfile + 72 edited file', 750000, 'gambar/our gallery/IMG_2769.jpg'],
                ['outdoor', 'Outdoor Premium', 'Fullday sampai acara selesai + unlimited softfile + 120 edited file', 1990000, 'gambar/our gallery/IMG_2605.jpg'],
                ['indoor', 'Indoor Classic', '10 menit 1-2 orang (lebih dari 2 orang tambahan Rp 15.000/orang, tambahan waktu Rp 20.000/5 menit, jasa fotografer Rp 50.000/10 menit)', 50000, 'gambar/our gallery/20250707_130246_0000.png']
            ];
            
            foreach ($default_pakets as $paket) {
                $sql_insert_paket = "INSERT INTO `paket` (`kategori`, `nama_paket`, `keterangan`, `harga`, `foto_preview`) 
                                   VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql_insert_paket);
                mysqli_stmt_bind_param($stmt, "sssis", $paket[0], $paket[1], $paket[2], $paket[3], $paket[4]);
                mysqli_stmt_execute($stmt);
            }
        }
        
        // Buat tabel untuk log aktivitas admin (optional)
        $sql_activity_log = "CREATE TABLE IF NOT EXISTS `admin_activity_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `admin_id` int(11) NOT NULL,
            `action` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `admin_id` (`admin_id`),
            CONSTRAINT `log_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!mysqli_query($conn, $sql_activity_log)) {
            throw new Exception("Error creating activity log table: " . mysqli_error($conn));
        }
    }
    
    // Jalankan pembuatan tabel
    createTablesIfNotExist($conn);
    
} catch (Exception $e) {
    // Log error (dalam production, log ke file)
    error_log("Database Error: " . $e->getMessage());
    
    // Tampilkan pesan error yang user-friendly
    die("Terjadi kesalahan koneksi database. Silakan hubungi administrator.");
}

// Fungsi helper untuk format rupiah
function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi untuk mencatat aktivitas admin
function logAdminActivity($conn, $admin_id, $action, $description = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $sql = "INSERT INTO admin_activity_log (admin_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issss", $admin_id, $action, $description, $ip, $user_agent);
    mysqli_stmt_execute($stmt);
}

// Fungsi untuk escape output HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>