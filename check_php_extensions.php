<?php
/**
 * ตรวจสอบ PHP Extensions ที่จำเป็น
 */
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบ PHP Extensions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .icon {
            font-size: 24px;
            margin-right: 15px;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .info h3 {
            margin-top: 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 ตรวจสอบ PHP Extensions</h1>
        
        <?php
        $checks = [];
        
        // ตรวจสอบ PHP Version
        $phpVersion = phpversion();
        $checks[] = [
            'name' => 'PHP Version',
            'status' => version_compare($phpVersion, '7.4.0', '>='),
            'current' => $phpVersion,
            'required' => '7.4.0 หรือสูงกว่า',
            'message' => version_compare($phpVersion, '7.4.0', '>=') 
                ? 'PHP version ถูกต้อง' 
                : 'ต้องอัปเกรด PHP เป็น 7.4.0 หรือสูงกว่า'
        ];
        
        // ตรวจสอบ PDO
        $checks[] = [
            'name' => 'PDO Extension',
            'status' => extension_loaded('pdo'),
            'current' => extension_loaded('pdo') ? 'ติดตั้งแล้ว' : 'ไม่พบ',
            'required' => 'จำเป็น',
            'message' => extension_loaded('pdo') 
                ? 'PDO extension พร้อมใช้งาน' 
                : 'ต้องติดตั้ง PDO extension'
        ];
        
        // ตรวจสอบ PDO_PGSQL (PostgreSQL)
        $pdoPgsqlLoaded = extension_loaded('pdo_pgsql');
        $checks[] = [
            'name' => 'PDO PostgreSQL Driver',
            'status' => $pdoPgsqlLoaded,
            'current' => $pdoPgsqlLoaded ? 'ติดตั้งแล้ว' : 'ไม่พบ',
            'required' => 'จำเป็น',
            'message' => $pdoPgsqlLoaded 
                ? 'PostgreSQL driver พร้อมใช้งาน' 
                : '⚠️ ต้องติดตั้ง pdo_pgsql extension'
        ];
        
        // ตรวจสอบ PDO drivers ที่มี
        $availableDrivers = PDO::getAvailableDrivers();
        $checks[] = [
            'name' => 'PDO Drivers ที่มี',
            'status' => in_array('pgsql', $availableDrivers),
            'current' => implode(', ', $availableDrivers),
            'required' => 'pgsql',
            'message' => in_array('pgsql', $availableDrivers)
                ? 'พบ PostgreSQL driver'
                : 'ไม่พบ PostgreSQL driver ในรายการ'
        ];
        
        // แสดงผลการตรวจสอบ
        foreach ($checks as $check) {
            $class = $check['status'] ? 'success' : 'error';
            $icon = $check['status'] ? '✅' : '❌';
            echo "<div class='check-item $class'>";
            echo "<span class='icon'>$icon</span>";
            echo "<div style='flex: 1;'>";
            echo "<strong>{$check['name']}</strong><br>";
            echo "<small>ปัจจุบัน: {$check['current']} | ต้องการ: {$check['required']}</small><br>";
            echo "<span>{$check['message']}</span>";
            echo "</div>";
            echo "</div>";
        }
        
        // ถ้าไม่มี pdo_pgsql แสดงคำแนะนำ
        if (!$pdoPgsqlLoaded || !in_array('pgsql', $availableDrivers)) {
            echo "<div class='info'>";
            echo "<h3>📋 วิธีติดตั้ง PostgreSQL Driver ใน XAMPP</h3>";
            echo "<p><strong>ขั้นตอนที่ 1:</strong> เปิดไฟล์ <code>php.ini</code></p>";
            echo "<p>ไฟล์อยู่ที่: <code>C:\\xampp\\php\\php.ini</code></p>";
            echo "<p><strong>ขั้นตอนที่ 2:</strong> ค้นหาและเอา comment (;) ออกที่บรรทัด:</p>";
            echo "<pre>;extension=pdo_pgsql</pre>";
            echo "<p>เปลี่ยนเป็น:</p>";
            echo "<pre>extension=pdo_pgsql</pre>";
            echo "<p><strong>ขั้นตอนที่ 3:</strong> ตรวจสอบว่ามีไฟล์ DLL อยู่หรือไม่</p>";
            echo "<p>ตรวจสอบที่: <code>C:\\xampp\\php\\ext\\php_pdo_pgsql.dll</code></p>";
            echo "<p><strong>ขั้นตอนที่ 4:</strong> ถ้าไม่มีไฟล์ DLL</p>";
            echo "<ul>";
            echo "<li>ดาวน์โหลด PHP Thread Safe (TS) version ที่ตรงกับ PHP version ของคุณ</li>";
            echo "<li>หรือติดตั้ง PostgreSQL client libraries</li>";
            echo "<li>หรือใช้ XAMPP version ที่มี PostgreSQL support</li>";
            echo "</ul>";
            echo "<p><strong>ขั้นตอนที่ 5:</strong> Restart Apache ใน XAMPP Control Panel</p>";
            echo "<p><strong>ขั้นตอนที่ 6:</strong> รีเฟรชหน้านี้เพื่อตรวจสอบอีกครั้ง</p>";
            echo "</div>";
        }
        
        // แสดงข้อมูล PHP
        echo "<div class='info'>";
        echo "<h3>📊 ข้อมูล PHP</h3>";
        echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
        echo "<p><strong>PHP ini file:</strong> " . php_ini_loaded_file() . "</p>";
        echo "<p><strong>Loaded extensions:</strong> " . implode(', ', get_loaded_extensions()) . "</p>";
        echo "</div>";
        ?>
    </div>
</body>
</html>



