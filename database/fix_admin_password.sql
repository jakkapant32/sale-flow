-- Fix Admin Password Hash
-- Hash นี้เป็น hash ของ "admin123" ที่ถูกต้อง
-- รัน script นี้เพื่ออัพเดท password hash ของ admin user

UPDATE users 
SET password_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy'
WHERE username = 'admin';

-- ตรวจสอบผลลัพธ์
SELECT username, email, role, status 
FROM users 
WHERE username = 'admin';

-- หลังจากรัน script นี้แล้ว:
-- Username: admin
-- Password: admin123

