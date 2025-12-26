-- SQL Script สำหรับเพิ่มข้อมูลตัวอย่างสำหรับ user "tum" (Safe Version)
-- คัดลอกและรันใน pgAdmin Query Tool หรือ psql
-- User tum มีอยู่แล้ว: id = 9de14816-0863-468d-aefe-18c77fd389ac
-- Version ที่ปลอดภัยกว่า - ไม่ใช้ transaction wrapper

-- ============================================
-- ⚠️ คำแนะนำสำคัญก่อนรัน Script:
-- ============================================
-- 1. ถ้าเกิด error "current transaction is aborted"
--    ให้รันคำสั่งนี้ก่อน: ROLLBACK;
--
-- 2. ใน pgAdmin Query Tool:
--    - ไปที่ Query → Execute → Auto-commit ON
--    - หรือกด F5 เพื่อรันด้วย Auto-commit
--
-- 3. ใน psql:
--    - รันคำสั่งนี้ก่อน: \set AUTOCOMMIT on
--    - หรือใช้: BEGIN; ... script ... COMMIT;

-- ============================================
-- ขั้นตอนที่ 1: ทำความสะอาด transaction (รันคำสั่งนี้ก่อนเสมอ)
-- ============================================
-- คอมเมนต์ออกถ้าไม่ต้องการรัน ROLLBACK อัตโนมัติ
DO $$
BEGIN
    -- ถ้ามี transaction ที่ abort อยู่ PostgreSQL จะ reject คำสั่งนี้
    -- แต่ไม่เป็นไร ถ้าไม่มี transaction ก็จะไม่มีผลกระทบ
    NULL;
EXCEPTION
    WHEN OTHERS THEN
        -- ถ้าเกิด error ก็ไม่เป็นไร แสดงว่าไม่มี transaction ที่ abort อยู่
        NULL;
END $$;

-- ============================================
-- ขั้นตอนที่ 2: เพิ่มข้อมูลตัวอย่าง
-- ============================================

-- ใช้ user_id ของ tum ที่มีอยู่แล้ว
DO $$
DECLARE
    tum_user_id UUID := '9de14816-0863-468d-aefe-18c77fd389ac';  -- User ID ของ tum
    max_cust_num INTEGER;
    max_deal_num INTEGER;
    cust_count INTEGER;
BEGIN
    -- ตรวจสอบว่า user tum มีอยู่จริง
    IF NOT EXISTS (SELECT 1 FROM users WHERE id = tum_user_id AND username = 'tum') THEN
        RAISE EXCEPTION 'User tum not found with ID: %', tum_user_id;
    END IF;
    
    RAISE NOTICE 'กำลังเพิ่มข้อมูลตัวอย่างสำหรับ user tum (ID: %)', tum_user_id;

    -- หา max customer_code
    SELECT COALESCE(
        MAX(CAST(SUBSTRING(customer_code FROM 4) AS INTEGER)), 
        0
    ) INTO max_cust_num
    FROM customers 
    WHERE customer_code LIKE 'CUS%' AND LENGTH(customer_code) >= 9;
    
    RAISE NOTICE 'Max customer code number: %', max_cust_num;

    -- 2. เพิ่มลูกค้าตัวอย่างที่ assigned_to เป็น tum (ถ้ายังไม่มี)
    INSERT INTO customers (customer_code, first_name, last_name, company_name, email, phone, mobile, customer_type, province, status, assigned_to, created_at)
    VALUES 
        ('CUS' || LPAD((max_cust_num + 1)::TEXT, 6, '0'), 'สมชาย', 'ใจดี', 'บริษัท เทคโนโลยี จำกัด', 'somchai_tum@tech.com', '02-123-4567', '081-234-5678', 'company', 'กรุงเทพมหานคร', 'active', tum_user_id, NOW()),
        ('CUS' || LPAD((max_cust_num + 2)::TEXT, 6, '0'), 'สมหญิง', 'รักงาน', 'บริษัท การตลาด จำกัด', 'somying_tum@marketing.com', '02-234-5678', '082-345-6789', 'company', 'เชียงใหม่', 'active', tum_user_id, NOW()),
        ('CUS' || LPAD((max_cust_num + 3)::TEXT, 6, '0'), 'วิชัย', 'เก่งมาก', NULL, 'wichai_tum@email.com', '02-345-6789', '083-456-7890', 'individual', 'ภูเก็ต', 'active', tum_user_id, NOW()),
        ('CUS' || LPAD((max_cust_num + 4)::TEXT, 6, '0'), 'มาลี', 'สวยงาม', 'บริษัท ผลิตภัณฑ์ จำกัด', 'malee_tum@product.com', '02-456-7890', '084-567-8901', 'company', 'ขอนแก่น', 'active', tum_user_id, NOW()),
        ('CUS' || LPAD((max_cust_num + 5)::TEXT, 6, '0'), 'ประเสริฐ', 'ดีมาก', NULL, 'prasert_tum@email.com', '02-567-8901', '085-678-9012', 'individual', 'นครราชสีมา', 'active', tum_user_id, NOW())
    ON CONFLICT (customer_code) DO NOTHING;

    -- ตรวจสอบจำนวนลูกค้าที่เพิ่มได้
    SELECT COUNT(*) INTO cust_count FROM customers WHERE assigned_to = tum_user_id;
    RAISE NOTICE 'จำนวนลูกค้าที่ assigned ให้ tum: %', cust_count;

    -- หา max deal_code
    SELECT COALESCE(
        MAX(CAST(SUBSTRING(deal_code FROM 5) AS INTEGER)), 
        0
    ) INTO max_deal_num
    FROM deals 
    WHERE deal_code LIKE 'DEAL%' AND LENGTH(deal_code) >= 10;
    
    RAISE NOTICE 'Max deal code number: %', max_deal_num;

    -- 3. เพิ่มดีลตัวอย่างที่ assigned_to เป็น tum (ถ้ายังไม่มี)
    WITH customer_ids AS (
        SELECT id, ROW_NUMBER() OVER (ORDER BY created_at DESC) as rn 
        FROM customers 
        WHERE assigned_to = tum_user_id 
        LIMIT 5
    ),
    deal_data AS (
        SELECT * FROM (
            VALUES 
                (1, 'โครงการ CRM สำหรับบริษัท (Tum)', 500000.00, 'prospecting', 10, 'open', 30),
                (2, 'ระบบ POS สำหรับร้านค้า (Tum)', 300000.00, 'qualification', 25, 'open', 45),
                (3, 'เว็บไซต์ E-commerce (Tum)', 800000.00, 'proposal', 50, 'open', 60),
                (4, 'แอปพลิเคชันมือถือ (Tum)', 1200000.00, 'negotiation', 75, 'open', 20),
                (5, 'ระบบ ERP Enterprise (Tum)', 2000000.00, 'closed', 100, 'closed', 0)
        ) AS t(seq, deal_name, amount, stage, probability, status, days)
    )
    INSERT INTO deals (deal_code, deal_name, customer_id, amount, stage, probability, status, expected_close_date, assigned_to, created_at)
    SELECT 
        'DEAL' || LPAD((max_deal_num + dd.seq)::TEXT, 6, '0') as deal_code,
        dd.deal_name,
        COALESCE((SELECT id FROM customer_ids WHERE rn = ((dd.seq - 1) % (SELECT COUNT(*) FROM customer_ids)) + 1), (SELECT id FROM customer_ids LIMIT 1)) as customer_id,
        dd.amount,
        dd.stage,
        dd.probability,
        dd.status,
        CURRENT_DATE + (dd.days || ' days')::INTERVAL as expected_close_date,
        tum_user_id as assigned_to,
        NOW() as created_at
    FROM deal_data dd
    WHERE NOT EXISTS (
        SELECT 1 FROM deals WHERE deal_name = dd.deal_name AND assigned_to = tum_user_id
    )
    ON CONFLICT (deal_code) DO NOTHING;

    -- 4. เพิ่มกิจกรรมตัวอย่างที่ assigned_to เป็น tum
    WITH customer_ids AS (
        SELECT id FROM customers WHERE assigned_to = tum_user_id ORDER BY created_at DESC LIMIT 5
    )
    INSERT INTO activities (subject, customer_id, activity_type, due_date, priority, status, assigned_to, created_at)
    SELECT 
        activity_data.subject,
        (SELECT id FROM customer_ids ORDER BY RANDOM() LIMIT 1) as customer_id,
        activity_data.activity_type,
        CURRENT_DATE + (activity_data.days || ' days')::INTERVAL as due_date,
        activity_data.priority,
        activity_data.status,
        tum_user_id as assigned_to,
        NOW() as created_at
    FROM (
        VALUES 
            ('ติดตามผลการเสนอราคา (Tum)', 'call', 7, 'high', 'pending'),
            ('นัดประชุมเสนอโครงการ (Tum)', 'meeting', 14, 'high', 'pending'),
            ('ส่งเอกสารเสนอราคา (Tum)', 'email', 3, 'medium', 'pending'),
            ('ติดตามลูกค้า (Tum)', 'call', 10, 'medium', 'pending'),
            ('สรุปผลการประชุม (Tum)', 'task', 5, 'low', 'completed'),
            ('เตรียมเอกสารเสนอ (Tum)', 'task', 2, 'high', 'pending'),
            ('ติดต่อลูกค้าใหม่ (Tum)', 'call', 1, 'high', 'pending'),
            ('Follow up ดีล (Tum)', 'email', 5, 'medium', 'pending')
    ) AS activity_data(subject, activity_type, days, priority, status)
    WHERE NOT EXISTS (
        SELECT 1 FROM activities WHERE subject = activity_data.subject AND assigned_to = tum_user_id
    );

    -- 5. เพิ่มคำสั่งซื้อตัวอย่าง (created_by เป็น tum)
    WITH customer_ids AS (
        SELECT id FROM customers WHERE assigned_to = tum_user_id ORDER BY created_at DESC LIMIT 3
    )
    INSERT INTO orders (order_number, customer_id, order_date, subtotal, tax, discount, total_amount, payment_status, status, created_by, created_at)
    SELECT 
        'ORD' || TO_CHAR(CURRENT_DATE - (order_data.days || ' days')::INTERVAL, 'YYYYMMDD') || LPAD(ROW_NUMBER() OVER()::TEXT, 4, '0') as order_number,
        (SELECT id FROM customer_ids ORDER BY RANDOM() LIMIT 1) as customer_id,
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
    WHERE NOT EXISTS (
        SELECT 1 FROM orders o 
        WHERE o.created_by = tum_user_id 
        AND o.order_date = CURRENT_DATE - (order_data.days || ' days')::INTERVAL
        AND o.subtotal = order_data.subtotal
    )
    ON CONFLICT (order_number) DO NOTHING;

    RAISE NOTICE 'เพิ่มข้อมูลตัวอย่างสำหรับ user tum สำเร็จ!';
    RAISE NOTICE 'User ID: %', tum_user_id;
    RAISE NOTICE 'Username: tum';
    RAISE NOTICE 'Full Name: จักรพันธ์  ชินโสภา';
    
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'Error: %', SQLERRM;
        RAISE;
END $$;

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
WHERE assigned_to = '9de14816-0863-468d-aefe-18c77fd389ac';

SELECT 
    'Deals assigned to tum' as info,
    COUNT(*) as count
FROM deals 
WHERE assigned_to = '9de14816-0863-468d-aefe-18c77fd389ac';

SELECT 
    'Activities assigned to tum' as info,
    COUNT(*) as count
FROM activities 
WHERE assigned_to = '9de14816-0863-468d-aefe-18c77fd389ac';

SELECT 
    'Orders created by tum' as info,
    COUNT(*) as count
FROM orders 
WHERE created_by = '9de14816-0863-468d-aefe-18c77fd389ac';

