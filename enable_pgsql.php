<?php
/**
 * สคริปต์ช่วยเปิดใช้งาน pdo_pgsql extension
 * ⚠️ ต้องรันด้วยสิทธิ์ Administrator
 */
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปิดใช้งาน PostgreSQL Driver</title>
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
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
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
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 เปิดใช้งาน PostgreSQL Driver</h1>
        
        <?php
        $phpIniPath = 'C:\\xampp\\php\\php.ini';
        $dllPath = 'C:\\xampp\\php\\ext\\php_pdo_pgsql.dll';
        
        // ตรวจสอบไฟล์ php.ini
        if (!file_exists($phpIniPath)) {
            echo '<div class="error">❌ ไม่พบไฟล์ php.ini ที่: ' . $phpIniPath . '</div>';
            echo '<div class="info">กรุณาตรวจสอบว่า XAMPP ติดตั้งที่ C:\\xampp หรือไม่</div>';
            exit;
        }
        
        // ตรวจสอบไฟล์ DLL
        if (!file_exists($dllPath)) {
            echo '<div class="error">❌ ไม่พบไฟล์ php_pdo_pgsql.dll</div>';
            echo '<div class="info">ไฟล์ควรอยู่ที่: ' . $dllPath . '</div>';
            echo '<div class="warning">⚠️ แต่จากที่ตรวจสอบ ไฟล์มีอยู่แล้ว กรุณาตรวจสอบอีกครั้ง</div>';
        } else {
            echo '<div class="success">✅ พบไฟล์ php_pdo_pgsql.dll</div>';
        }
        
        // อ่านไฟล์ php.ini
        $phpIniContent = file_get_contents($phpIniPath);
        
        // ตรวจสอบสถานะปัจจุบัน
        $isCommented = strpos($phpIniContent, ';extension=pdo_pgsql') !== false;
        $isEnabled = strpos($phpIniContent, "\nextension=pdo_pgsql") !== false || 
                     strpos($phpIniContent, "\r\nextension=pdo_pgsql") !== false;
        
        if ($isEnabled) {
            echo '<div class="success">✅ pdo_pgsql extension เปิดใช้งานอยู่แล้ว!</div>';
            echo '<div class="info">กรุณา <strong>Restart Apache</strong> ใน XAMPP Control Panel</div>';
            echo '<p><a href="check_php_extensions.php" class="btn">ตรวจสอบอีกครั้ง</a></p>';
        } else if ($isCommented) {
            echo '<div class="warning">⚠️ pdo_pgsql extension ยังถูก comment อยู่</div>';
            
            // แสดงวิธีแก้ไข
            echo '<div class="info">';
            echo '<h3>📋 วิธีแก้ไข (ทำด้วยตนเอง):</h3>';
            echo '<ol>';
            echo '<li>เปิดไฟล์: <code>' . $phpIniPath . '</code></li>';
            echo '<li>กด <strong>Ctrl + F</strong> เพื่อค้นหา</li>';
            echo '<li>พิมพ์: <code>pdo_pgsql</code></li>';
            echo '<li>หาบรรทัด: <code>;extension=pdo_pgsql</code></li>';
            echo '<li><strong>เอา semicolon (;) ออก</strong> → เปลี่ยนเป็น: <code>extension=pdo_pgsql</code></li>';
            echo '<li>บันทึกไฟล์ (Ctrl + S)</li>';
            echo '<li><strong>Restart Apache</strong> ใน XAMPP Control Panel</li>';
            echo '</ol>';
            echo '</div>';
            
            // พยายามแก้ไขอัตโนมัติ (ถ้าไฟล์สามารถเขียนได้)
            if (isset($_GET['auto']) && $_GET['auto'] === '1') {
                echo '<div class="info">';
                echo '<h3>🔄 พยายามแก้ไขอัตโนมัติ...</h3>';
                
                // แทนที่ ;extension=pdo_pgsql ด้วย extension=pdo_pgsql
                $newContent = preg_replace(
                    '/;extension=pdo_pgsql/',
                    'extension=pdo_pgsql',
                    $phpIniContent
                );
                
                if ($newContent !== $phpIniContent) {
                    // พยายามบันทึก
                    if (is_writable($phpIniPath)) {
                        if (file_put_contents($phpIniPath, $newContent)) {
                            echo '<div class="success">✅ แก้ไขไฟล์ php.ini สำเร็จ!</div>';
                            echo '<div class="warning">⚠️ <strong>กรุณา Restart Apache ใน XAMPP Control Panel</strong></div>';
                            echo '<p><a href="check_php_extensions.php" class="btn">ตรวจสอบอีกครั้ง</a></p>';
                        } else {
                            echo '<div class="error">❌ ไม่สามารถบันทึกไฟล์ได้ (อาจต้องใช้สิทธิ์ Administrator)</div>';
                        }
                    } else {
                        echo '<div class="error">❌ ไฟล์ php.ini ไม่สามารถเขียนได้ (อาจต้องใช้สิทธิ์ Administrator)</div>';
                        echo '<div class="info">กรุณาแก้ไขด้วยตนเองตามขั้นตอนด้านบน</div>';
                    }
                } else {
                    echo '<div class="error">❌ ไม่พบ ;extension=pdo_pgsql ในไฟล์</div>';
                }
                echo '</div>';
            } else {
                echo '<p><a href="?auto=1" class="btn">ลองแก้ไขอัตโนมัติ</a></p>';
                echo '<div class="warning">⚠️ ถ้าแก้ไขอัตโนมัติไม่ได้ กรุณาแก้ไขด้วยตนเอง</div>';
            }
        } else {
            echo '<div class="warning">⚠️ ไม่พบ extension=pdo_pgsql ในไฟล์ php.ini</div>';
            echo '<div class="info">กรุณาเพิ่มบรรทัดนี้ในไฟล์ php.ini:</div>';
            echo '<pre>extension=pdo_pgsql</pre>';
        }
        ?>
        
        <div class="info">
            <h3>📝 หมายเหตุ:</h3>
            <ul>
                <li>หลังจากแก้ไข php.ini <strong>ต้อง Restart Apache ทุกครั้ง</strong></li>
                <li>ถ้าแก้ไขอัตโนมัติไม่ได้ อาจต้องใช้สิทธิ์ Administrator</li>
                <li>ตรวจสอบผลลัพธ์ที่: <a href="check_php_extensions.php">check_php_extensions.php</a></li>
            </ul>
        </div>
    </div>
</body>
</html>



