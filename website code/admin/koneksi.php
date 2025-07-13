<?php
// Konfigurasi database
$host = 'localhost';
$username = 'depictwo_ryan';
$password = 'qwertyuiop0987';
$database = 'depictwo_db';

// Buat koneksi
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Fungsi helper untuk format rupiah
function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi untuk escape output HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>