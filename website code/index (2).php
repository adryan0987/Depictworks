<?php
// Include koneksi database
require_once __DIR__ . '/admin/koneksi.php';

// Handle form submission
$booking_success = false;
$booking_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'booking') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date = $_POST['date'] ?? '';
    $package = $_POST['package'] ?? '';
    $email = trim($_POST['email'] ?? '');

    // Validation
    if (empty($name) || empty($phone) || empty($date) || empty($package)) {
        $booking_message = 'Mohon lengkapi semua field yang diperlukan.';
    } elseif (strlen($phone) < 10) {
        $booking_message = 'Nomor WhatsApp minimal 10 digit.';
    } elseif (strtotime($date) < strtotime('today')) {
        $booking_message = 'Tanggal booking tidak boleh di masa lalu.';
    } else {
        // Save to database
        $stmt = mysqli_prepare($conn, "INSERT INTO bookings (name, phone, email, booking_date, package, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, "sssss", $name, $phone, $email, $date, $package);
        
        if (mysqli_stmt_execute($stmt)) {
            // Set session untuk notifikasi
            session_start();
            $_SESSION['booking_success'] = true;
            $_SESSION['booking_message'] = 'Booking berhasil! Kami akan menghubungi Anda segera.';
            
            // Redirect untuk mencegah resubmit saat refresh
            header('Location: ' . $_SERVER['PHP_SELF'] . '#booking');
            exit();
        } else {
            $booking_message = 'Terjadi kesalahan. Silakan coba lagi.';
        }
        mysqli_stmt_close($stmt);
    }
}

// Cek session untuk notifikasi
session_start();
if (isset($_SESSION['booking_success'])) {
    $booking_success = $_SESSION['booking_success'];
    $booking_message = $_SESSION['booking_message'];
    
    // Hapus session setelah ditampilkan
    unset($_SESSION['booking_success']);
    unset($_SESSION['booking_message']);
}

// Get packages from database
$paket_query = mysqli_query($conn, "SELECT * FROM paket WHERE is_active = 1 ORDER BY kategori, harga");
$paket_outdoor = [];
$paket_indoor = [];

while ($row = mysqli_fetch_assoc($paket_query)) {
    if ($row['kategori'] == 'outdoor') {
        $paket_outdoor[] = $row;
    } else {
        $paket_indoor[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depictworks Photography</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #EFEEE5; /* Krem/Krim */
        }
        
        /* Header */
        header {
            background: #424A54; /* Charcoal */
            color: #EFEEE5; /* Krem/Krim */
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(217,191,172,0.05), transparent); /* Desert Sand */
            animation: shimmer 3s infinite;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            z-index: 1;
        }
        
        header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            color: #EFEEE5; /* Krem/Krim */
        }
        
        header img {
            height: 45px;
            vertical-align: middle;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3));
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        /* Navigation */
        nav {
            position: relative;
            z-index: 1;
        }
        
        nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }
        
        nav li {
            margin: 0 1rem;
            position: relative;
        }
        
        nav a {
            color: #EFEEE5; /* Krem/Krim */
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
        }
        
        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: #D9BFAC; /* Desert Sand */
            transition: all 0.3s;
            transform: translateX(-50%);
        }
        
        nav a:hover::after {
            width: 80%;
        }
        
        nav a:hover {
            transform: translateY(-2px);
            color: #BAA390; /* Khaki */
        }
        
        /* Floating Book Now Button */
        .floating-book-now {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #BAA390, #E2D2C3); /* Khaki to Almond */
            color: #424A54; /* Charcoal */
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 5px 20px rgba(66,74,84,0.4);
            z-index: 999;
            transition: all 0.3s;
            animation: bounceIn 0.6s ease-out;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3) translateY(100px);
            }
            50% {
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        .floating-book-now:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 10px 30px rgba(66,74,84,0.5);
        }
        
        .floating-book-now::before {
            content: '📸';
            font-size: 1.2rem;
        }
        
        .floating-book-now.hide {
            transform: translateY(150px);
            opacity: 0;
        }
        
        /* Hero Section */
        #home {
            background: linear-gradient(rgba(66,74,84,0.4), rgba(66,74,84,0.4)), url('gambar/home/Desain tanpa judul (29).png') center/cover; /* Charcoal overlay */
            color: #EFEEE5; /* Krem/Krim */
            text-align: center;
            padding: 8rem 2rem;
            position: relative;
            overflow: hidden;
        }
        
        #home::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.4) 100%);
            pointer-events: none;
        }
        
        #home h2 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #EFEEE5; /* Krem/Krim */
            animation: fadeInUp 1s ease-out;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.5);
        }
        
        #home p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            color: #EFEEE5; /* Krem/Krim */
            animation: fadeInUp 1s ease-out 0.3s both;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Sections */
        section {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeIn 1s ease-out;
            background: #EFEEE5; /* Krem/Krim */
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #424A54; /* Charcoal */
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        section h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, #BAA390, #D9BFAC); /* Khaki to Desert Sand */
            border-radius: 2px;
        }
        
        /* About */
        #about {
            background: linear-gradient(135deg, #EFEEE5 0%, #E2D2C3 100%); /* Krem/Krim to Almond */
            text-align: left;
            position: relative;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        #about::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, #D9BFAC, transparent); /* Desert Sand */
        }
        
        #about .content {
            flex: 1;
            max-width: 50%;
        }
        
        #about .image {
            flex: 1;
            max-width: 50%;
        }
        
        #about p {
            max-width: 800px;
            margin: 0;
            font-size: 1.1rem;
            color: #424A54; /* Charcoal */
            line-height: 1.8;
        }
        
        #about .image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Gallery */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .gallery-grid img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            border: 2px solid #BAA390; /* Khaki */
        }
        
        .gallery-grid img:hover {
            transform: scale(1.05) rotate(1deg);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        }
        
        /* Packages */
        #booking {
            background: linear-gradient(135deg, #EFEEE5 0%, #D9BFAC 100%); /* Krem/Krim to Desert Sand */
        }
        
        .package-category {
            margin-bottom: 3rem;
        }
        
        .package-category h3 {
            font-size: 1.8rem;
            color: #424A54; /* Charcoal */
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
        }
        
        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .package-card {
            background: #F3F3F3; /* New package box color */
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(66,74,84,0.08); /* Charcoal shadow */
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
            height: 450px; /* Uniform height for all package cards */
        }
        
        .package-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(186,163,144,0.9), rgba(217,191,172,0.9)); /* Khaki to Desert Sand */
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1;
        }
        
        .package-card:hover::before {
            opacity: 0.1;
        }
        
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(66,74,84,0.15); /* Charcoal shadow */
        }
        
        .package-card.selected {
            border: 3px solid #BAA390; /* Khaki */
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(186,163,144,0.3);
        }
        
        .package-card.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            background: #BAA390; /* Khaki */
            color: #EFEEE5; /* Krem/Krim */
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 2;
            animation: checkmark 0.3s ease-out;
        }
        
        @keyframes checkmark {
            0% { transform: scale(0) rotate(-180deg); }
            50% { transform: scale(1.2) rotate(10deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
        
        .package-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .package-card:hover img {
            transform: scale(1.1);
        }
        
        .package-content {
            padding: 1.5rem;
            position: relative;
            z-index: 2;
            background: #F3F3F3; /* New package box color */
            height: 250px; /* Adjusted height to fit content */
        }
        
        .package-content h4 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: #424A54; /* Charcoal */
            margin-bottom: 0.5rem;
        }
        
        .package-content p {
            color: #424A54; /* Charcoal */
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .package-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #000000; /* Hitam */
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .package-price::before {
            content: '💰';
            font-size: 1rem;
        }
        
        .package-additional {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #424A54; /* Charcoal */
        }
        
        /* Form */
        .booking-container {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }
        
        .booking-form {
            background: #FFFFFF; /* White */
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(66,74,84,0.1); /* Charcoal shadow */
            max-width: 600px;
            margin: 0;
            position: relative;
            overflow: hidden;
        }
        
        .booking-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #BAA390, #D9BFAC); /* Khaki to Desert Sand */
        }
        
        .booking-instructions {
            flex: 1;
            background: #FFFFFF; /* White */
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(66,74,84,0.1); /* Charcoal shadow */
            max-width: 400px;
        }
        
        .booking-instructions h3 {
            font-size: 1.5rem;
            color: #424A54; /* Charcoal */
            margin-bottom: 1rem;
        }
        
        .booking-instructions p {
            color: #424A54; /* Charcoal */
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #424A54; /* Charcoal */
            transition: color 0.3s;
        }
        
        .form-group:focus-within label {
            color: #BAA390; /* Khaki */
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #D9BFAC; /* Desert Sand */
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #EFEEE5; /* Krem/Krim */
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #BAA390; /* Khaki */
            background: #EFEEE5; /* Krem/Krim */
            box-shadow: 0 0 0 4px rgba(186,163,144,0.1);
        }
        
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #BAA390, #D9BFAC); /* Khaki to Desert Sand */
            color: #424A54; /* Charcoal */
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(239,238,229,0.2); /* Krem/Krim */
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-submit:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(186,163,144,0.3);
        }
        
        /* Contact */
        #contact {
            text-align: center;
            background: #EFEEE5; /* Krem/Krim */
        }
        
        #contact p {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        #contact a {
            color: #D9BFAC; /* Desert Sand */
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
        
        #contact a:hover {
            background: rgba(217,191,172,0.1); /* Desert Sand */
            transform: translateX(5px);
        }
        
        /* Footer */
        footer {
            background: linear-gradient(135deg, #424A54, #D9BFAC); /* Charcoal to Desert Sand */
            color: #EFEEE5; /* Krem/Krim */
            text-align: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, #E2D2C3, transparent); /* Almond */
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            animation: slideDown 0.5s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: currentColor;
        }
        
        .alert-success {
            background: #E2D2C3; /* Almond */
            color: #424A54; /* Charcoal */
            border: 1px solid #D9BFAC; /* Desert Sand */
        }
        
        .alert-success::after {
            content: '✓';
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
        }
        
        .alert-error {
            background: #E2D2C3; /* Almond */
            color: #424A54; /* Charcoal */
            border: 1px solid #D9BFAC; /* Desert Sand */
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            
            .header-left {
                margin-bottom: 1rem;
            }
            
            header h1 {
                font-size: 1.5rem;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            nav li {
                margin: 0.3rem;
            }
            
            nav a {
                font-size: 0.9rem;
                padding: 0.3rem 0.8rem;
            }
            
            #home h2 {
                font-size: 2rem;
            }
            
            section {
                padding: 3rem 1rem;
            }
            
            section h2 {
                font-size: 2rem;
            }
            
            #about {
                flex-direction: column;
            }
            
            #about .content,
            #about .image {
                max-width: 100%;
            }
            
            #about .image img {
                margin-top: 1rem;
            }
            
            .booking-container {
                flex-direction: column;
            }
            
            .booking-form, .booking-instructions {
                max-width: 100%;
            }
            
            .floating-book-now {
                bottom: 20px;
                right: 20px;
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-left">
            <img src="gambar/logo/ds logo WHITE .png" alt="Depictworks Logo">
            <h1>Depictworks Photography</h1>
        </div>
        <nav>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#gallery">Gallery</a></li>
                <li><a href="#booking">Booking</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>
    
    <!-- Floating Book Now Button -->
    <a href="#booking" class="floating-book-now">Book Now</a>
    
    <section id="home">
        <h2>Abadikan Momen Berharga Anda</h2>
        <p>Kami hadir untuk mengabadikan setiap momen spesial dalam hidup Anda dengan sentuhan profesional dan penuh dedikasi.</p>
    </section>
    
    <section id="about">
        <div class="content">
            <h2>Tentang Depictworks</h2>
            <p>Depictworks adalah sebuah brand fotografi profesional yang berpengalaman yang menawarkan cerita, rasa dan makna dalam setiap jepretanya. mengabadikan berbagai momen spesial setiap orang  Dengan keahlian dan kreativitas yang tak terbatas, kami siap memberikan hasil terbaik untuk Anda.</p>
        </div>
        <div class="image">
            <!-- Tambahkan logo dengan background transparan di sini -->
            <img src="gambar/logo/Desain tanpa judul (28).png" alt="Depictworks Logo" onerror="this.style.display='none';">
        </div>
    </section>
    
    <section id="gallery">
        <h2>Galeri Kami</h2>
        <div class="gallery-grid">
            <img src="gambar/our gallery/5.png" alt="Gallery 1">
            <img src="gambar/our gallery/IMG_2299 (2).jpg" alt="Gallery 2">
            <img src="gambar/our gallery/download.jpeg" alt="Gallery 3">
            <img src="gambar/our gallery/IMG_2562 (1).jpg" alt="Gallery 4">
            <img src="gambar/our gallery/1 (1).png" alt="Gallery 5">
            <img src="gambar/paket preview/outdoor hemat/ADR04333.jpg" alt="Gallery 6">
            <img src="gambar/paket preview/outdoor hemat/wisuda2.webp" alt="Gallery 7">
            <img src="gambar/our gallery/3.png" alt="Gallery 8">
        </div>
    </section>
    
    <section id="booking">
        <h2>Booking Sekarang</h2>
        
        <?php if ($booking_message): ?>
            <div class="alert <?php echo $booking_success ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $booking_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($paket_outdoor) > 0): ?>
        <div class="package-category">
            <h3>Paket Outdoor</h3>
            <div class="package-grid">
                <?php foreach ($paket_outdoor as $paket): ?>
                <label class="package-card" data-package="<?php echo e($paket['nama_paket']); ?>">
                    <input type="radio" name="package" value="<?php echo e($paket['nama_paket']); ?>" hidden>
                    <?php
                    // CUSTOM GAMBAR UNTUK SETIAP PAKET - EDIT DI SINI
                    $custom_images = [
                        'Outdoor Hemat' => 'gambar/paket preview/outdoor hemat/ADR01070.jpg',
                        'Outdoor Lengkap' => 'gambar/paket preview/outdoor lengkap/ADR01607.jpg', 
                        'Outdoor Premium' => 'gambar/our gallery/IMG_2300.jpg'
                    ];
                    
                    // Gunakan custom image jika ada, kalau tidak pakai dari database
                    $image_src = isset($custom_images[$paket['nama_paket']]) 
                                ? $custom_images[$paket['nama_paket']] 
                                : $paket['foto_preview'];
                    ?>
                    <img src="<?php echo e($image_src); ?>" alt="<?php echo e($paket['nama_paket']); ?>">
                    <div class="package-content">
                        <h4><?php echo e($paket['nama_paket']); ?></h4>
                        <p><?php echo e($paket['keterangan']); ?></p>
                        <div class="package-price"><?php echo rupiah($paket['harga']); ?></div>
                        <div class="package-additional">
                            <?php
                            $additional_notes = [
                                'Outdoor Hemat' => 'Recommended for: graduation, birthday party & familly gathering',
                                'Outdoor Lengkap' => 'Recommended for: family gathering, engagement, small wedding & big events ',
                                'Outdoor Premium' => 'Recommended for: luxury events & large weddings'
                            ];
                            echo isset($additional_notes[$paket['nama_paket']]) ? $additional_notes[$paket['nama_paket']] : '';
                            ?>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (count($paket_indoor) > 0): ?>
        <div class="package-category">
            <h3>Paket Indoor</h3>
            <div class="package-grid">
                <?php foreach ($paket_indoor as $paket): ?>
                <label class="package-card" data-package="<?php echo e($paket['nama_paket']); ?>">
                    <input type="radio" name="package" value="<?php echo e($paket['nama_paket']); ?>" hidden>
                    <?php
                    // CUSTOM GAMBAR UNTUK PAKET INDOOR - EDIT DI SINI
                    $custom_images_indoor = [
                        'Indoor Classic' => 'gambar/paket preview/indoor classic/20250707_130246_0000.png'
                    ];
                    
                    // Gunakan custom image jika ada, kalau tidak pakai dari database
                    $image_src = isset($custom_images_indoor[$paket['nama_paket']]) 
                                ? $custom_images_indoor[$paket['nama_paket']] 
                                : $paket['foto_preview'];
                    ?>
                    <img src="<?php echo e($image_src); ?>" alt="<?php echo e($paket['nama_paket']); ?>">
                    <div class="package-content">
                        <h4><?php echo e($paket['nama_paket']); ?></h4>
                        <p><?php echo e($paket['keterangan']); ?></p>
                        <div class="package-price"><?php echo rupiah($paket['harga']); ?></div>
                        <div class="package-additional">
                            <?php
                            $additional_notes_indoor = [
                                'Indoor Classic' => 'Recommended for: studio portrait & small events'
                            ];
                            echo isset($additional_notes_indoor[$paket['nama_paket']]) ? $additional_notes_indoor[$paket['nama_paket']] : '';
                            ?>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="booking-container">
            <form class="booking-form" method="POST" action="#booking">
                <input type="hidden" name="action" value="booking">
                <input type="hidden" name="package" id="selectedPackage" required>
                
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Nomor WhatsApp *</label>
                    <input type="tel" name="phone" placeholder="08123456789" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Booking *</label>
                    <input type="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <button type="submit" class="btn-submit">Kirim Booking</button>
            </form>
            <div class="booking-instructions">
                <h3>Cara Mengisi Data Booking</h3>
                <p>1. Pilih paket yang diinginkan dengan mengklik kotak paket.<br>
                   2. Isi data diri Anda dengan benar di formulir booking.<br>
                   3. Klik "Kirim Booking" untuk mengirimkan permintaan Anda.<br>
                   4. Tunggu sampai kami kontak anda melalui whatsapp untuk tahap konfirmasi dan metode pembayaran</p>
            </div>
        </div>
    </section>
    
    <section id="contact">
        <h2>Hubungi Kami</h2>
        <p>📱 WhatsApp: <a href="https://wa.me/6283110241227">+62 812-3456-789</a></p>
        <p>📧 Email: <a href="mailto:@adryanprayoga690@gmail.com">info@depictworks.com</a></p>
        <p>📷 Instagram: <a href="https://www.instagram.com/depictworks/?igsh=cWtrYmNhZ3lza3J0#s">@depictworks</a></p>
    </section>
    
    <footer>
        <p>© 2025 Depictworks Photography. All rights reserved.</p>
    </footer>
    
    <script>
        // Package selection
        document.querySelectorAll('.package-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.package-card').forEach(c => c.classList.remove('selected'));
                
                // Add selection to clicked card
                this.classList.add('selected');
                
                // Update hidden input
                document.getElementById('selectedPackage').value = this.dataset.package;
            });
        });
        
        // Smooth scrolling
        document.querySelectorAll('nav a, .floating-book-now').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        
        // Hide floating button when in booking section
        window.addEventListener('scroll', function() {
            const bookingSection = document.getElementById('booking');
            const floatingBtn = document.querySelector('.floating-book-now');
            const bookingPosition = bookingSection.getBoundingClientRect();
            
            if (bookingPosition.top <= window.innerHeight && bookingPosition.bottom >= 0) {
                floatingBtn.classList.add('hide');
            } else {
                floatingBtn.classList.remove('hide');
            }
        });
        
        // Auto-hide notification after 10 seconds
        <?php if ($booking_message): ?>
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }
        }, 10000); // 10 detik
        <?php endif; ?>
    </script>
</body>
</html>