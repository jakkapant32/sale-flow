<?php
/**
 * Database Installation Script
 * รันสคริปต์ติดตั้งฐานข้อมูลผ่านเบราว์เซอร์
 * 
 * ⚠️ คำเตือน: ไฟล์นี้จะลบตารางเก่าทั้งหมดและสร้างใหม่
 * หลังจากติดตั้งเสร็จแล้ว ควรลบไฟล์นี้เพื่อความปลอดภัย
 */

require_once 'config/database.php';

// ตั้งค่า
$sqlFile = __DIR__ . '/database/reset_and_install.sql';
$isInstalled = false;
$messages = [];
$errors = [];

// ตรวจสอบว่าไฟล์ SQL มีอยู่หรือไม่
if (!file_exists($sqlFile)) {
    $errors[] = "ไม่พบไฟล์: $sqlFile";
}

// ถ้ากดปุ่ม Install
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $db = getDB();
        
        // อ่านไฟล์ SQL
        $sql = file_get_contents($sqlFile);
        
        // ลบ comments (แต่เก็บไว้สำหรับ function definitions)
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // แยก SQL statements โดยใช้ regex ที่ดีขึ้น
        // ต้องระวัง semicolon ใน $$ delimiters
        $statements = [];
        $current = '';
        $inDollarQuote = false;
        $dollarTag = '';
        
        $lines = explode("\n", $sql);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // ตรวจสอบ dollar quote delimiters ($$ หรือ $tag$)
            if (preg_match('/\$([^$]*)\$/', $line, $matches)) {
                if (!$inDollarQuote) {
                    $inDollarQuote = true;
                    $dollarTag = $matches[0];
                } elseif ($line === $dollarTag || strpos($line, $dollarTag) !== false) {
                    $inDollarQuote = false;
                    $dollarTag = '';
                }
                $current .= $line . "\n";
                continue;
            }
            
            // ถ้าอยู่ใน dollar quote ให้เพิ่มต่อ
            if ($inDollarQuote) {
                $current .= $line . "\n";
                continue;
            }
            
            // ถ้าเจอ semicolon และไม่อยู่ใน dollar quote = จบ statement
            if (strpos($line, ';') !== false) {
                $current .= $line;
                $stmt = trim($current);
                if (!empty($stmt) && strlen($stmt) > 5) {
                    $statements[] = $stmt;
                }
                $current = '';
            } else {
                $current .= $line . "\n";
            }
        }
        
        // เพิ่ม statement สุดท้าย (ถ้ามี)
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        // รันคำสั่ง SQL
        $db->beginTransaction();
        $executed = 0;
        
        foreach ($statements as $index => $statement) {
            $statement = trim($statement);
            if (empty($statement) || strlen($statement) < 5) continue;
            
            try {
                $db->exec($statement);
                $executed++;
                // แสดงเฉพาะ statements สำคัญ
                if (stripos($statement, 'CREATE TABLE') !== false || 
                    stripos($statement, 'CREATE FUNCTION') !== false ||
                    stripos($statement, 'INSERT INTO') !== false) {
                    preg_match('/CREATE TABLE\s+(\w+)|CREATE FUNCTION\s+(\w+)|INSERT INTO\s+(\w+)/i', $statement, $matches);
                    $name = $matches[1] ?? $matches[2] ?? $matches[3] ?? 'statement';
                    $messages[] = "✓ " . $name;
                }
            } catch (PDOException $e) {
                // บางคำสั่งอาจจะ error ถ้ามีอยู่แล้ว - ข้ามไป
                $errorMsg = $e->getMessage();
                if (stripos($errorMsg, 'already exists') === false && 
                    stripos($errorMsg, 'duplicate') === false &&
                    stripos($errorMsg, 'does not exist') === false) {
                    $errors[] = "Error: " . substr($errorMsg, 0, 100);
                }
            }
        }
        
        $db->commit();
        $isInstalled = true;
        $messages[] = "✓ ติดตั้งฐานข้อมูลสำเร็จ! (รัน " . $executed . " คำสั่ง)";
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $errors[] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ตรวจสอบตารางที่มีอยู่
$existingTables = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $errors[] = "ไม่สามารถเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตั้งฐานข้อมูล CRM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 100%;
            padding: 2rem;
        }
        h1 {
            color: #0f172a;
            margin-bottom: 1rem;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 0.5rem;
        }
        .warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #92400e;
        }
        .warning strong {
            display: block;
            margin-bottom: 0.5rem;
        }
        .info {
            background: #dbeafe;
            border: 2px solid #3b82f6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #1e40af;
        }
        .error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #991b1b;
        }
        .success {
            background: #d1fae5;
            border: 2px solid #10b981;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #065f46;
        }
        .message {
            background: #f3f4f6;
            border-left: 4px solid #3b82f6;
            padding: 0.5rem 1rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
        }
        .btn {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
            margin-top: 1rem;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
        }
        form {
            margin-top: 1.5rem;
        }
        .credentials {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        .credentials strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 ติดตั้งฐานข้อมูล CRM</h1>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($isInstalled): ?>
            <div class="success">
                <strong>✅ ติดตั้งสำเร็จ!</strong><br>
                ตารางทั้งหมดถูกสร้างเรียบร้อยแล้ว
            </div>
            
            <div class="credentials">
                <strong>ข้อมูลเข้าสู่ระบบ:</strong>
                <div>Username: <strong>admin</strong></div>
                <div>Password: <strong>admin123</strong></div>
                <div style="margin-top: 0.5rem; color: #dc2626;">
                    ⚠️ ควรเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก!
                </div>
            </div>
            
            <a href="index.php" class="btn">ไปที่หน้าล็อกอิน</a>
            
            <div class="warning" style="margin-top: 1rem;">
                <strong>⚠️ ความปลอดภัย:</strong>
                กรุณาลบไฟล์ <code>install.php</code> หลังจากติดตั้งเสร็จแล้ว
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ คำเตือน</strong>
                สคริปต์นี้จะลบตารางเก่าทั้งหมด (ถ้ามี) และสร้างใหม่ทั้งหมด<br>
                ข้อมูลเดิมจะถูกลบทั้งหมด!
            </div>
            
            <?php if (!empty($existingTables)): ?>
                <div class="info">
                    <strong>📋 ตารางที่มีอยู่ปัจจุบัน:</strong>
                    <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                        <?php foreach ($existingTables as $table): ?>
                            <li><?= htmlspecialchars($table) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="info">
                    ไม่พบตารางในฐานข้อมูล (ฐานข้อมูลว่างเปล่า)
                </div>
            <?php endif; ?>
            
            <?php if (!empty($messages)): ?>
                <div style="max-height: 300px; overflow-y: auto; margin: 1rem 0;">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message"><?= htmlspecialchars($msg) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <button type="submit" name="install" class="btn btn-danger" 
                        onclick="return confirm('คุณแน่ใจว่าต้องการลบตารางเก่าทั้งหมดและสร้างใหม่?\\nข้อมูลเดิมจะถูกลบทั้งหมด!');">
                    🔄 ติดตั้งฐานข้อมูล (ลบตารางเก่าและสร้างใหม่)
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

