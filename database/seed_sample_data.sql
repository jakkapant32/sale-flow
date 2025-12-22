-- สคริปต์เพิ่มข้อมูลตัวอย่างสำหรับ CRM System
-- รันใน pgAdmin Query Tool

BEGIN;

-- 1. เพิ่มลูกค้าตัวอย่าง
INSERT INTO customers (customer_code, first_name, last_name, company_name, email, phone, mobile, customer_type, province, status, created_at)
VALUES 
    ('CUS000001', 'สมชาย', 'ใจดี', 'บริษัท เทคโนโลยี จำกัด', 'somchai@tech.com', '02-123-4567', '081-234-5678', 'company', 'กรุงเทพมหานคร', 'active', NOW()),
    ('CUS000002', 'สมหญิง', 'รักงาน', 'บริษัท การตลาด จำกัด', 'somying@marketing.com', '02-234-5678', '082-345-6789', 'company', 'เชียงใหม่', 'active', NOW()),
    ('CUS000003', 'วิชัย', 'เก่งมาก', NULL, 'wichai@email.com', '02-345-6789', '083-456-7890', 'individual', 'ภูเก็ต', 'active', NOW()),
    ('CUS000004', 'มาลี', 'สวยงาม', 'บริษัท ผลิตภัณฑ์ จำกัด', 'malee@product.com', '02-456-7890', '084-567-8901', 'company', 'ขอนแก่น', 'active', NOW()),
    ('CUS000005', 'ประเสริฐ', 'ดีมาก', NULL, 'prasert@email.com', '02-567-8901', '085-678-9012', 'individual', 'นครราชสีมา', 'active', NOW()),
    ('CUS000006', 'สุดา', 'ขยัน', 'บริษัท บริการ จำกัด', 'suda@service.com', '02-678-9012', '086-789-0123', 'company', 'สงขลา', 'active', NOW()),
    ('CUS000007', 'ธีรพงษ์', 'มุ่งมั่น', NULL, 'teerapong@email.com', '02-789-0123', '087-890-1234', 'individual', 'ระยอง', 'active', NOW()),
    ('CUS000008', 'กาญจนา', 'ประสบการณ์', 'บริษัท อุตสาหกรรม จำกัด', 'kanjana@industry.com', '02-890-1234', '088-901-2345', 'company', 'ชลบุรี', 'active', NOW())
ON CONFLICT (customer_code) DO NOTHING;

-- 2. เพิ่มสินค้าตัวอย่าง
INSERT INTO products (product_code, product_name, description, category, unit_price, cost_price, stock_quantity, status, created_at)
VALUES 
    ('PROD001', 'โปรแกรม CRM Professional', 'ซอฟต์แวร์ CRM สำหรับธุรกิจ', 'Software', 50000.00, 30000.00, 100, 'active', NOW()),
    ('PROD002', 'ระบบ POS', 'ระบบขายหน้าร้าน', 'Software', 30000.00, 20000.00, 50, 'active', NOW()),
    ('PROD003', 'เว็บไซต์ E-commerce', 'เว็บไซต์ขายของออนไลน์', 'Web Development', 80000.00, 50000.00, 30, 'active', NOW()),
    ('PROD004', 'แอปพลิเคชันมือถือ', 'แอป iOS และ Android', 'Mobile App', 120000.00, 80000.00, 20, 'active', NOW()),
    ('PROD005', 'ระบบ ERP', 'ระบบจัดการองค์กร', 'Enterprise Software', 200000.00, 150000.00, 10, 'active', NOW()),
    ('PROD006', 'บริการ Cloud Hosting', 'โฮสติ้งรายเดือน', 'Hosting', 2000.00, 1000.00, 500, 'active', NOW()),
    ('PROD007', 'การอบรมใช้งานระบบ', 'คอร์สอบรม 1 วัน', 'Training', 5000.00, 2000.00, 100, 'active', NOW()),
    ('PROD008', 'บริการ Support รายปี', 'Support และ Maintenance', 'Service', 30000.00, 15000.00, 200, 'active', NOW())
ON CONFLICT (product_code) DO NOTHING;

-- 3. เพิ่มดีลตัวอย่าง (ต้องมี customer_id ก่อน)
INSERT INTO deals (deal_name, customer_id, amount, stage, probability, status, expected_close_date, created_at)
SELECT 
    deal_data.deal_name,
    c.id as customer_id,
    deal_data.amount,
    deal_data.stage,
    deal_data.probability,
    deal_data.status,
    CURRENT_DATE + (deal_data.days || ' days')::INTERVAL as expected_close_date,
    NOW() as created_at
FROM (
    VALUES 
        ('โครงการ CRM สำหรับบริษัทเทคโนโลยี', 'CUS000001', 500000.00, 'prospecting', 10, 'open', 30),
        ('ระบบ POS สำหรับร้านค้า', 'CUS000002', 300000.00, 'qualification', 25, 'open', 45),
        ('เว็บไซต์ E-commerce ใหม่', 'CUS000003', 800000.00, 'proposal', 50, 'open', 60),
        ('แอปพลิเคชันมือถือ', 'CUS000004', 1200000.00, 'negotiation', 75, 'open', 20),
        ('ระบบ ERP Enterprise', 'CUS000005', 2000000.00, 'closed', 100, 'closed', 0),
        ('บริการ Cloud Hosting', 'CUS000006', 24000.00, 'prospecting', 10, 'open', 90),
        ('โครงการอบรมทีมงาน', 'CUS000007', 40000.00, 'qualification', 25, 'open', 15),
        ('สัญญา Support 1 ปี', 'CUS000008', 240000.00, 'proposal', 50, 'open', 30)
) AS deal_data(deal_name, customer_code, amount, stage, probability, status, days)
INNER JOIN customers c ON c.customer_code = deal_data.customer_code
WHERE NOT EXISTS (
    SELECT 1 FROM deals d WHERE d.deal_name = deal_data.deal_name
);

-- 4. เพิ่มกิจกรรมตัวอย่าง
INSERT INTO activities (subject, customer_id, activity_type, due_date, priority, status, created_at)
SELECT 
    activity_data.subject,
    c.id as customer_id,
    activity_data.activity_type,
    CURRENT_DATE + (activity_data.days || ' days')::INTERVAL as due_date,
    activity_data.priority,
    activity_data.status,
    NOW() as created_at
FROM (
    VALUES 
        ('ติดตามผลการเสนอราคา', 'CUS000001', 'call', 7, 'high', 'pending'),
        ('นัดประชุมเสนอโครงการ', 'CUS000002', 'meeting', 14, 'high', 'pending'),
        ('ส่งเอกสารเสนอราคา', 'CUS000003', 'email', 3, 'medium', 'pending'),
        ('ติดตามลูกค้า', 'CUS000004', 'call', 10, 'medium', 'pending'),
        ('สรุปผลการประชุม', 'CUS000005', 'task', 5, 'low', 'completed'),
        ('เตรียมเอกสารเสนอ', 'CUS000006', 'task', 2, 'high', 'pending'),
        ('ติดต่อลูกค้าใหม่', 'CUS000007', 'call', 1, 'high', 'pending'),
        ('Follow up ดีล', 'CUS000008', 'email', 5, 'medium', 'pending')
) AS activity_data(subject, customer_code, activity_type, days, priority, status)
INNER JOIN customers c ON c.customer_code = activity_data.customer_code;

-- 5. เพิ่มคำสั่งซื้อตัวอย่าง
INSERT INTO orders (order_number, customer_id, order_date, subtotal, tax, discount, total_amount, payment_status, status, created_at)
SELECT 
    'ORD' || TO_CHAR(CURRENT_DATE - (order_data.days || ' days')::INTERVAL, 'YYYYMMDD') || LPAD(order_data.seq::TEXT, 4, '0') as order_number,
    c.id as customer_id,
    CURRENT_DATE - (order_data.days || ' days')::INTERVAL as order_date,
    order_data.subtotal,
    order_data.tax,
    order_data.discount,
    order_data.total_amount,
    order_data.payment_status,
    'confirmed' as status,
    CURRENT_DATE - (order_data.days || ' days')::INTERVAL as created_at
FROM (
    VALUES 
        ('CUS000001', 0, 1, 50000.00, 3500.00, 0.00, 53500.00, 'paid'),
        ('CUS000002', 15, 2, 30000.00, 2100.00, 0.00, 32100.00, 'paid'),
        ('CUS000003', 30, 3, 80000.00, 5600.00, 0.00, 85600.00, 'paid'),
        ('CUS000004', 45, 4, 120000.00, 8400.00, 0.00, 128400.00, 'paid'),
        ('CUS000005', 60, 5, 200000.00, 14000.00, 0.00, 214000.00, 'unpaid')
) AS order_data(customer_code, days, seq, subtotal, tax, discount, total_amount, payment_status)
INNER JOIN customers c ON c.customer_code = order_data.customer_code
ON CONFLICT (order_number) DO NOTHING;

-- 6. เพิ่มรายการสินค้าในคำสั่งซื้อ
INSERT INTO order_items (order_id, product_id, quantity, unit_price, discount, total_price)
SELECT 
    o.id as order_id,
    p.id as product_id,
    1 as quantity,
    p.unit_price,
    0.00 as discount,
    p.unit_price as total_price
FROM orders o
INNER JOIN customers c ON o.customer_id = c.id
INNER JOIN products p ON (
    (c.customer_code = 'CUS000001' AND p.product_code = 'PROD001') OR
    (c.customer_code = 'CUS000002' AND p.product_code = 'PROD002') OR
    (c.customer_code = 'CUS000003' AND p.product_code = 'PROD003') OR
    (c.customer_code = 'CUS000004' AND p.product_code = 'PROD004') OR
    (c.customer_code = 'CUS000005' AND p.product_code = 'PROD005')
)
WHERE o.order_number LIKE 'ORD%'
AND NOT EXISTS (
    SELECT 1 FROM order_items oi WHERE oi.order_id = o.id
);

COMMIT;

-- ตรวจสอบจำนวนข้อมูลที่เพิ่ม
SELECT 
    'customers' as table_name, COUNT(*) as record_count FROM customers
UNION ALL
SELECT 'products', COUNT(*) FROM products
UNION ALL
SELECT 'deals', COUNT(*) FROM deals
UNION ALL
SELECT 'activities', COUNT(*) FROM activities
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'order_items', COUNT(*) FROM order_items;

