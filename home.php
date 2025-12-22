<?php
// Home page doesn't require authentication
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SalesFlow - ระบบ CRM สำหรับธุรกิจ</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #0f172a;
            --primary-dark: #0a0f1f;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #0f172a;
            --light-color: #f0f4f8;
            --border-color: #cbd5e1;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.1), 0 1px 2px 0 rgba(15, 23, 42, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(15, 23, 42, 0.1), 0 4px 6px -2px rgba(15, 23, 42, 0.05);
            --gradient-primary: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);
            --gradient-secondary: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--light-color);
        }

        /* Header */
        header {
            background: white;
            box-shadow: var(--shadow);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--border-color);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--accent-color);
        }

        .btn-primary {
            background: var(--gradient-secondary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            background: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            padding: 4rem 5%;
            background: var(--gradient-primary);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: fadeInUp 0.8s ease-out;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .btn-secondary {
            background: white;
            color: var(--primary-color);
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: var(--shadow);
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
            background: var(--light-color);
        }

        /* Features Section */
        .features {
            padding: 4rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: var(--primary-color);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            text-align: center;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-secondary);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-color);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.75rem;
            color: white;
            box-shadow: var(--shadow);
        }

        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .feature-card p {
            color: var(--text-secondary);
        }

        /* Stats Section */
        .stats {
            background: var(--gradient-primary);
            color: white;
            padding: 4rem 5%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Footer */
        footer {
            background: var(--primary-color);
            color: white;
            padding: 3rem 5%;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--accent-color);
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

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <a href="home.php" class="logo"><i class="fas fa-chart-line"></i> SalesFlow</a>
            <ul class="nav-links">
                <li><a href="#features">ฟีเจอร์</a></li>
                <li><a href="#about">เกี่ยวกับเรา</a></li>
                <li><a href="index.php" class="btn-primary">เข้าสู่ระบบ</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>ระบบ CRM ที่ธุรกิจของคุณต้องการ</h1>
            <p>จัดการลูกค้า เพิ่มยอดขาย และพัฒนาธุรกิจของคุณด้วยเครื่องมือที่ทรงพลังและใช้งานง่าย</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn-secondary">สมัครสมาชิกฟรี</a>
                <a href="index.php" class="btn-primary">เข้าสู่ระบบ</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <h2 class="section-title">ฟีเจอร์ที่ครบครัน</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <h3>แดชบอร์ดแบบเรียลไทม์</h3>
                <p>ดูภาพรวมธุรกิจของคุณแบบเรียลไทม์ พร้อมรายงานที่เข้าใจง่าย</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h3>จัดการลูกค้า</h3>
                <p>เก็บข้อมูลลูกค้าแบบครบถ้วน พร้อมประวัติการติดต่อทั้งหมด</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-handshake"></i></div>
                <h3>จัดการดีล</h3>
                <p>ติดตามโอกาสขายและสถานะดีลของคุณ</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                <h3>จัดการกิจกรรม</h3>
                <p>บันึกและติดตามกิจกรรมทั้งหมดของทีม</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-box"></i></div>
                <h3>จัดการสินค้า</h3>
                <p>จัดการสินค้าและคำสั่งซื้อได้อย่างง่ายดาย</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>ความปลอดภัยสูง</h3>
                <p>ระบบรักษาความปลอดภัยระดับสูง ปกป้องข้อมูลของคุณ</p>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>100%</h3>
                <p>ฟรีตลอดชีพ</p>
            </div>
            <div class="stat-item">
                <h3>24/7</h3>
                <p>ใช้งานได้ทุกเวลา</p>
            </div>
            <div class="stat-item">
                <h3>ง่าย</h3>
                <p>ใช้งานง่าย</p>
            </div>
            <div class="stat-item">
                <h3>เร็ว</h3>
                <p>ประสิทธิภาพสูง</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="about">
        <div class="footer-content">
            <div class="footer-links">
                <a href="#features">ฟีเจอร์</a>
                <a href="index.php">เข้าสู่ระบบ</a>
                <a href="register.php">สมัครสมาชิก</a>
            </div>
            <p>&copy; 2024 SalesFlow. สงวนลิขสิทธิ์ทุกประการ</p>
        </div>
    </footer>
</body>
</html>

