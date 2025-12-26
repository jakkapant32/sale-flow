-- SQL Script สำหรับเพิ่มข้อมูลตัวอย่างสำหรับ user "tum"
-- คัดลอกและรันใน pgAdmin Query Tool หรือ psql
-- User tum มีอยู่แล้ว: id = 9de14816-0863-468d-aefe-18c77fd389ac

-- ถ้ามี transaction ที่ abort อยู่ ให้ rollback ก่อน
ROLLBACK;

BEGIN;

-- ใช้ user_id ของ tum ที่มีอยู่แล้ว
DO $$
DECLARE
    tum_user_id UUID := '9de14816-0863-468d-aefe-18c77fd389ac';  -- User ID ของ tum
BEGIN
    -- ตรวจสอบว่า user tum มีอยู่จริง
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = tum_user_id AND username = 'tum') THEN
        RAISE EXCEPTION 'User tum not found with ID: %', tum_user_id;
    END IF;
    
    RAISE NOTICE 'กำลังเพิ่มข้อมูลตัวอย่างสำหรับ user tum (ID: %)', tum_user_id;

    -- 2. เพิ่มลูกค้าตัวอย่างที่ assigned_to เป็น tum
    WITH max_cust_num AS (
        SELECT COALESCE(
            MAX(CAST(SUBSTRING(customer_code FROM 4) AS INTEGER)), 
            0
        ) as max_num
        FROM customers 
        WHERE customer_code LIKE 'CUS%' AND LENGTH(customer_code) >= 9
    ),
    customer_data AS (
        SELECT * FROM (
            VALUES 
                (1, 'สมชาย', 'ใจดี', 'บริษัท เทคโนโลยี จำกัด', 'somchai@tech.com', '02-123-4567', '081-234-5678', 'company', 'กรุงเทพมหานคร'),
                (2, 'สมหญิง', 'รักงาน', 'บริษัท การตลาด จำกัด', 'somying@marketing.com', '02-234-5678', '082-345-6789', 'company', 'เชียงใหม่'),
                (3, 'วิชัย', 'เก่งมาก', NULL, 'wichai@email.com', '02-345-6789', '083-456-7890', 'individual', 'ภูเก็ต'),
                (4, 'มาลี', 'สวยงาม', 'บริษัท ผลิตภัณฑ์ จำกัด', 'malee@product.com', '02-456-7890', '084-567-8901', 'company', 'ขอนแก่น'),
                (5, 'ประเสริฐ', 'ดีมาก', NULL, 'prasert@email.com', '02-567-8901', '085-678-9012', 'individual', 'นครราชสีมา')
        ) AS t(seq, first_name, last_name, company_name, email, phone, mobile, customer_type, province)
    )
    INSERT INTO customers (customer_code, first_name, last_name, company_name, email, phone, mobile, customer_type, province, status, assigned_to, created_at)
    SELECT 
        'CUS' || LPAD(((SELECT max_num FROM max_cust_num) + cd.seq)::TEXT, 6, '0') as customer_code,
        cd.first_name,
        cd.last_name,
        cd.company_name,
        cd.email,
        cd.phone,
        cd.mobile,
        cd.customer_type,
        cd.province,
        'active' as status,
        tum_user_id as assigned_to,
        NOW() as created_at
    FROM customer_data cd
    WHERE NOT EXISTS (
        SELECT 1 FROM customers 
        WHERE first_name = cd.first_name 
        AND last_name = cd.last_name 
        AND assigned_to = tum_user_id
    )
    ON CONFLICT (customer_code) DO UPDATE
    SET assigned_to = tum_user_id;

    -- 3. เพิ่มดีลตัวอย่างที่ assigned_to เป็น tum
    WITH customer_ids AS (
        SELECT id FROM customers WHERE assigned_to = tum_user_id LIMIT 5
    ),
    max_deal_num AS (
        SELECT COALESCE(
            MAX(CAST(SUBSTRING(deal_code FROM 5) AS INTEGER)), 
            0
        ) as max_num
        FROM deals 
        WHERE deal_code LIKE 'DEAL%' AND LENGTH(deal_code) >= 10
    ),
    deal_data AS (
        SELECT * FROM (
            VALUES 
                (1, 'โครงการ CRM สำหรับบริษัท', 500000.00, 'prospecting', 10, 'open', 30),
                (2, 'ระบบ POS สำหรับร้านค้า', 300000.00, 'qualification', 25, 'open', 45),
                (3, 'เว็บไซต์ E-commerce', 800000.00, 'proposal', 50, 'open', 60),
                (4, 'แอปพลิเคชันมือถือ', 1200000.00, 'negotiation', 75, 'open', 20),
                (5, 'ระบบ ERP Enterprise', 2000000.00, 'closed', 100, 'closed', 0)
        ) AS t(seq, deal_name, amount, stage, probability, status, days)
    )
    INSERT INTO deals (deal_code, deal_name, customer_id, amount, stage, probability, status, expected_close_date, assigned_to, created_at)
    SELECT 
        'DEAL' || LPAD(((SELECT max_num FROM max_deal_num) + dd.seq)::TEXT, 6, '0') as deal_code,
        dd.deal_name,
        c.id as customer_id,
        dd.amount,
        dd.stage,
        dd.probability,
        dd.status,
        CURRENT_DATE + (dd.days || ' days')::INTERVAL as expected_close_date,
        tum_user_id as assigned_to,
        NOW() as created_at
    FROM deal_data dd
    CROSS JOIN LATERAL (
        SELECT id FROM customer_ids ORDER BY RANDOM() LIMIT 1
    ) c
    WHERE NOT EXISTS (
        SELECT 1 FROM deals WHERE deal_name = dd.deal_name AND assigned_to = tum_user_id
    )
    ON CONFLICT (deal_code) DO NOTHING;

    -- 4. เพิ่มกิจกรรมตัวอย่างที่ assigned_to เป็น tum
    WITH customer_ids AS (
        SELECT id FROM customers WHERE assigned_to = tum_user_id LIMIT 5
    )
    INSERT INTO activities (subject, customer_id, activity_type, due_date, priority, status, assigned_to, created_at)
    SELECT 
        activity_data.subject,
        c.id as customer_id,
        activity_data.activity_type,
        CURRENT_DATE + (activity_data.days || ' days')::INTERVAL as due_date,
        activity_data.priority,
        activity_data.status,
        tum_user_id as assigned_to,
        NOW() as created_at
    FROM (
        VALUES 
            ('ติดตามผลการเสนอราคา', 'call', 7, 'high', 'pending'),
            ('นัดประชุมเสนอโครงการ', 'meeting', 14, 'high', 'pending'),
            ('ส่งเอกสารเสนอราคา', 'email', 3, 'medium', 'pending'),
            ('ติดตามลูกค้า', 'call', 10, 'medium', 'pending'),
            ('สรุปผลการประชุม', 'task', 5, 'low', 'completed'),
            ('เตรียมเอกสารเสนอ', 'task', 2, 'high', 'pending'),
            ('ติดต่อลูกค้าใหม่', 'call', 1, 'high', 'pending'),
            ('Follow up ดีล', 'email', 5, 'medium', 'pending')
    ) AS activity_data(subject, activity_type, days, priority, status)
    CROSS JOIN LATERAL (
        SELECT id FROM customer_ids ORDER BY RANDOM() LIMIT 1
    ) c
    WHERE NOT EXISTS (
        SELECT 1 FROM activities WHERE subject = activity_data.subject AND assigned_to = tum_user_id
    );

    -- 5. เพิ่มคำสั่งซื้อตัวอย่าง (created_by เป็น tum)
    WITH customer_ids AS (
        SELECT id FROM customers WHERE assigned_to = tum_user_id LIMIT 3
    )
    INSERT INTO orders (order_number, customer_id, order_date, subtotal, tax, discount, total_amount, payment_status, status, created_by, created_at)
    SELECT 
        'ORD' || TO_CHAR(CURRENT_DATE - (order_data.days || ' days')::INTERVAL, 'YYYYMMDD') || LPAD(ROW_NUMBER() OVER()::TEXT, 4, '0') as order_number,
        c.id as customer_id,
        CURRENT_DATE - (order_data.days || ' days')::INTERVAL as order_date,
        order_data.subtotal,
        order_data.tax,
        order_data.discount,
        order_data.total_amount,
        order_data.payment_status,
        'confirmed' as status,
        tum_user_id as created_by,
        CURRENT_DATE - (order_data.days || ' days')::INTERVAL as created_at
    FROM (
        VALUES 
            (0, 50000.00, 3500.00, 0.00, 53500.00, 'paid'),
            (15, 30000.00, 2100.00, 0.00, 32100.00, 'paid'),
            (45, 80000.00, 5600.00, 0.00, 85600.00, 'paid')
    ) AS order_data(days, subtotal, tax, discount, total_amount, payment_status)
    CROSS JOIN LATERAL (
        SELECT id FROM customer_ids ORDER BY RANDOM() LIMIT 1
    ) c
    WHERE NOT EXISTS (
        SELECT 1 FROM orders o 
        WHERE o.created_by = tum_user_id 
        AND o.order_date = CURRENT_DATE - (order_data.days || ' days')::INTERVAL
    );

    RAISE NOTICE 'เพิ่มข้อมูลตัวอย่างสำหรับ user tum สำเร็จ!';
    RAISE NOTICE 'User ID: %', tum_user_id;
    RAISE NOTICE 'Username: tum';
    RAISE NOTICE 'Full Name: จักรพันธ์  ชินโสภา';
    
END $$;

COMMIT;

-- แสดงสรุปข้อมูลที่เพิ่ม
SELECT 
    'User tum' as info,
    username,
    email,
    full_name,
    role,
    status
FROM users 
WHERE username = 'tum';

SELECT 
    'Customers assigned to tum' as info,
    COUNT(*) as count
FROM customers 
WHERE assigned_to = (SELECT id FROM users WHERE username = 'tum');

SELECT 
    'Deals assigned to tum' as info,
    COUNT(*) as count
FROM deals 
WHERE assigned_to = (SELECT id FROM users WHERE username = 'tum');

SELECT 
    'Activities assigned to tum' as info,
    COUNT(*) as count
FROM activities 
WHERE assigned_to = (SELECT id FROM users WHERE username = 'tum');

SELECT 
    'Orders created by tum' as info,
    COUNT(*) as count
FROM orders 
WHERE created_by = (SELECT id FROM users WHERE username = 'tum');

