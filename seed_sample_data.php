<?php
/**
 * สคริปต์เพิ่มข้อมูลตัวอย่างสำหรับ CRM System
 * รันครั้งเดียวเพื่อเพิ่มข้อมูลตัวอย่างในทุกเมนู
 */

require_once 'config/database.php';
require_once 'config/functions.php';

$db = getDB();

try {
    $db->beginTransaction();
    
    echo "<h2>กำลังเพิ่มข้อมูลตัวอย่าง...</h2>";
    
    // 1. เพิ่มลูกค้าตัวอย่าง
    echo "<p>1. เพิ่มลูกค้าตัวอย่าง...</p>";
    $customers = [
        ['สมชาย', 'ใจดี', 'บริษัท เทคโนโลยี จำกัด', 'somchai@tech.com', '02-123-4567', '081-234-5678', 'company', 'กรุงเทพมหานคร', 'active'],
        ['สมหญิง', 'รักงาน', 'บริษัท การตลาด จำกัด', 'somying@marketing.com', '02-234-5678', '082-345-6789', 'company', 'เชียงใหม่', 'active'],
        ['วิชัย', 'เก่งมาก', null, 'wichai@email.com', '02-345-6789', '083-456-7890', 'individual', 'ภูเก็ต', 'active'],
        ['มาลี', 'สวยงาม', 'บริษัท ผลิตภัณฑ์ จำกัด', 'malee@product.com', '02-456-7890', '084-567-8901', 'company', 'ขอนแก่น', 'active'],
        ['ประเสริฐ', 'ดีมาก', null, 'prasert@email.com', '02-567-8901', '085-678-9012', 'individual', 'นครราชสีมา', 'active'],
        ['สุดา', 'ขยัน', 'บริษัท บริการ จำกัด', 'suda@service.com', '02-678-9012', '086-789-0123', 'company', 'สงขลา', 'active'],
        ['ธีรพงษ์', 'มุ่งมั่น', null, 'teerapong@email.com', '02-789-0123', '087-890-1234', 'individual', 'ระยอง', 'active'],
        ['กาญจนา', 'ประสบการณ์', 'บริษัท อุตสาหกรรม จำกัด', 'kanjana@industry.com', '02-890-1234', '088-901-2345', 'company', 'ชลบุรี', 'active']
    ];
    
    foreach ($customers as $idx => $customer) {
        $code = 'CUS' . str_pad($idx + 1, 6, '0', STR_PAD_LEFT);
        // ตรวจสอบว่ามี customer_code อยู่แล้วหรือไม่
        $checkStmt = $db->prepare("SELECT id FROM customers WHERE customer_code = :code");
        $checkStmt->execute(['code' => $code]);
        if ($checkStmt->fetch()) {
            continue; // ข้ามถ้ามีอยู่แล้ว
        }
        
        $stmt = $db->prepare("
            INSERT INTO customers (customer_code, first_name, last_name, company_name, email, phone, mobile, customer_type, province, status, created_at)
            VALUES (:code, :first, :last, :company, :email, :phone, :mobile, :type, :province, :status, NOW())
        ");
        $stmt->execute([
            'code' => $code,
            'first' => $customer[0],
            'last' => $customer[1],
            'company' => $customer[2],
            'email' => $customer[3],
            'phone' => $customer[4],
            'mobile' => $customer[5],
            'type' => $customer[6],
            'province' => $customer[7],
            'status' => $customer[8]
        ]);
    }
    echo "<p>✓ เพิ่มลูกค้า " . count($customers) . " รายการ</p>";
    
    // 2. เพิ่มสินค้าตัวอย่าง
    echo "<p>2. เพิ่มสินค้าตัวอย่าง...</p>";
    $products = [
        ['PROD001', 'โปรแกรม CRM Professional', 'ซอฟต์แวร์ CRM สำหรับธุรกิจ', 50000, 100, 'active'],
        ['PROD002', 'ระบบ POS', 'ระบบขายหน้าร้าน', 30000, 50, 'active'],
        ['PROD003', 'เว็บไซต์ E-commerce', 'เว็บไซต์ขายของออนไลน์', 80000, 30, 'active'],
        ['PROD004', 'แอปพลิเคชันมือถือ', 'แอป iOS และ Android', 120000, 20, 'active'],
        ['PROD005', 'ระบบ ERP', 'ระบบจัดการองค์กร', 200000, 10, 'active'],
        ['PROD006', 'บริการ Cloud Hosting', 'โฮสติ้งรายเดือน', 2000, 500, 'active'],
        ['PROD007', 'การอบรมใช้งานระบบ', 'คอร์สอบรม 1 วัน', 5000, 100, 'active'],
        ['PROD008', 'บริการ Support รายปี', 'Support และ Maintenance', 30000, 200, 'active']
    ];
    
    foreach ($products as $product) {
        // ตรวจสอบว่ามี product_code อยู่แล้วหรือไม่
        $checkStmt = $db->prepare("SELECT id FROM products WHERE product_code = :code");
        $checkStmt->execute(['code' => $product[0]]);
        if ($checkStmt->fetch()) {
            continue; // ข้ามถ้ามีอยู่แล้ว
        }
        
        $stmt = $db->prepare("
            INSERT INTO products (product_code, product_name, description, unit_price, stock_quantity, status, created_at)
            VALUES (:code, :name, :desc, :price, :stock, :status, NOW())
        ");
        $stmt->execute([
            'code' => $product[0],
            'name' => $product[1],
            'desc' => $product[2],
            'price' => $product[3],
            'stock' => $product[4],
            'status' => $product[5]
        ]);
    }
    echo "<p>✓ เพิ่มสินค้า " . count($products) . " รายการ</p>";
    
    // 3. เพิ่มดีลตัวอย่าง
    echo "<p>3. เพิ่มดีลตัวอย่าง...</p>";
    $stages = ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed'];
    $dealNames = [
        'โครงการ CRM สำหรับบริษัทเทคโนโลยี',
        'ระบบ POS สำหรับร้านค้า',
        'เว็บไซต์ E-commerce ใหม่',
        'แอปพลิเคชันมือถือ',
        'ระบบ ERP Enterprise',
        'บริการ Cloud Hosting',
        'โครงการอบรมทีมงาน',
        'สัญญา Support 1 ปี'
    ];
    
    // ดึง customer IDs
    $stmt = $db->query("SELECT id FROM customers ORDER BY id LIMIT 8");
    $customerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ดึง product IDs
    $stmt = $db->query("SELECT id FROM products ORDER BY id LIMIT 8");
    $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($dealNames as $idx => $dealName) {
        $stage = $stages[array_rand($stages)];
        $amount = rand(50000, 500000);
        $probability = match($stage) {
            'prospecting' => 10,
            'qualification' => 25,
            'proposal' => 50,
            'negotiation' => 75,
            'closed' => 100,
            default => 50
        };
        $status = $stage === 'closed' ? 'closed' : 'open';
        
        $days = rand(7, 90);
        $stmt = $db->prepare("
            INSERT INTO deals (deal_name, customer_id, amount, stage, probability, status, expected_close_date, created_at)
            VALUES (:name, :customer_id, :amount, :stage, :prob, :status, 
                    CURRENT_DATE + INTERVAL '{$days} days', NOW())
        ");
        $stmt->execute([
            'name' => $dealName,
            'customer_id' => $customerIds[$idx % count($customerIds)],
            'amount' => $amount,
            'stage' => $stage,
            'prob' => $probability,
            'status' => $status
        ]);
    }
    echo "<p>✓ เพิ่มดีล " . count($dealNames) . " รายการ</p>";
    
    // 4. เพิ่มกิจกรรมตัวอย่าง
    echo "<p>4. เพิ่มกิจกรรมตัวอย่าง...</p>";
    $activityTypes = ['call', 'meeting', 'email', 'task'];
    $subjects = [
        'ติดตามผลการเสนอราคา',
        'นัดประชุมเสนอโครงการ',
        'ส่งเอกสารเสนอราคา',
        'ติดตามลูกค้า',
        'สรุปผลการประชุม',
        'เตรียมเอกสารเสนอ',
        'ติดต่อลูกค้าใหม่',
        'Follow up ดีล'
    ];
    
    $stmt = $db->query("SELECT id FROM customers ORDER BY id LIMIT 8");
    $customerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($subjects as $idx => $subject) {
        $type = $activityTypes[array_rand($activityTypes)];
        $priority = ['low', 'medium', 'high'][array_rand([0, 1, 2])];
        $status = ['pending', 'completed'][array_rand([0, 1])];
        $dueDate = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
        
        $stmt = $db->prepare("
            INSERT INTO activities (subject, customer_id, activity_type, due_date, priority, status, created_at)
            VALUES (:subject, :customer_id, :type, :due_date, :priority, :status, NOW())
        ");
        $stmt->execute([
            'subject' => $subject,
            'customer_id' => $customerIds[$idx % count($customerIds)],
            'type' => $type,
            'due_date' => $dueDate,
            'priority' => $priority,
            'status' => $status
        ]);
    }
    echo "<p>✓ เพิ่มกิจกรรม " . count($subjects) . " รายการ</p>";
    
    // 5. เพิ่มคำสั่งซื้อตัวอย่าง
    echo "<p>5. เพิ่มคำสั่งซื้อตัวอย่าง...</p>";
    
    $stmt = $db->query("SELECT id FROM customers ORDER BY id LIMIT 5");
    $customerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $db->query("SELECT id, unit_price FROM products ORDER BY id LIMIT 5");
    $products = $stmt->fetchAll();
    
    for ($i = 0; $i < 5; $i++) {
        $customerId = $customerIds[$i % count($customerIds)];
        $product = $products[$i % count($products)];
        $quantity = rand(1, 5);
        $subtotal = $product['unit_price'] * $quantity;
        $tax = $subtotal * 0.07; // VAT 7%
        $discount = 0;
        $totalAmount = $subtotal + $tax - $discount;
        $paymentStatus = ['unpaid', 'paid', 'paid'][array_rand([0, 1, 2])];
        $orderDate = date('Y-m-d', strtotime('-' . rand(0, 90) . ' days'));
        $orderNumber = 'ORD' . date('Ymd') . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("
            INSERT INTO orders (order_number, customer_id, order_date, subtotal, tax, discount, total_amount, payment_status, status, created_at)
            VALUES (:order_number, :customer_id, :order_date, :subtotal, :tax, :discount, :total, :payment_status, 'confirmed', :created_at)
            RETURNING id
        ");
        $stmt->execute([
            'order_number' => $orderNumber,
            'customer_id' => $customerId,
            'order_date' => $orderDate,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $totalAmount,
            'payment_status' => $paymentStatus,
            'created_at' => $orderDate
        ]);
        
        $orderId = $stmt->fetch()['id'];
        
        // เพิ่ม order items
        $stmt = $db->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
            VALUES (:order_id, :product_id, :quantity, :unit_price, :total_price)
        ");
        $stmt->execute([
            'order_id' => $orderId,
            'product_id' => $product['id'],
            'quantity' => $quantity,
            'unit_price' => $product['unit_price'],
            'total_price' => $totalAmount
        ]);
    }
    echo "<p>✓ เพิ่มคำสั่งซื้อ 5 รายการ</p>";
    
    $db->commit();
    echo "<h2 style='color: green;'>✓ เพิ่มข้อมูลตัวอย่างสำเร็จ!</h2>";
    echo "<p><a href='dashboard.php'>ไปที่แดชบอร์ด</a></p>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<h2 style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

