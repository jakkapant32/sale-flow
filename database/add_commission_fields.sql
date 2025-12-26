-- Migration Script: เพิ่มฟิลด์สำหรับค่าคอมมิชชั่นและรายได้สุทธิ
-- สำหรับตาราง orders และ deals
-- วันที่สร้าง: 2024

-- ============================================
-- เพิ่มฟิลด์ในตาราง orders
-- ============================================

-- เพิ่มฟิลด์ commission_rate (อัตราค่าคอมมิชชั่น %)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5, 2) DEFAULT 7.00;

-- เพิ่มฟิลด์ commission_amount (ยอดค่าคอมมิชชั่น)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS commission_amount DECIMAL(15, 2) DEFAULT 0;

-- เพิ่มฟิลด์ net_income (รายได้สุทธิ)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS net_income DECIMAL(15, 2) DEFAULT 0;

-- เพิ่มฟิลด์ tax_rate (อัตราภาษี %)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(5, 2) DEFAULT 7.00;

-- ============================================
-- เพิ่มฟิลด์ในตาราง deals
-- ============================================

-- เพิ่มฟิลด์ commission_rate (อัตราค่าคอมมิชชั่น %)
ALTER TABLE deals ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5, 2) DEFAULT 7.00;

-- เพิ่มฟิลด์ commission_amount (ยอดค่าคอมมิชชั่น)
ALTER TABLE deals ADD COLUMN IF NOT EXISTS commission_amount DECIMAL(15, 2) DEFAULT 0;

-- เพิ่มฟิลด์ net_income (รายได้สุทธิ)
ALTER TABLE deals ADD COLUMN IF NOT EXISTS net_income DECIMAL(15, 2) DEFAULT 0;

-- ============================================
-- สร้าง Indexes เพื่อประสิทธิภาพที่ดีขึ้น
-- ============================================

CREATE INDEX IF NOT EXISTS idx_orders_commission_rate ON orders(commission_rate);
CREATE INDEX IF NOT EXISTS idx_deals_commission_rate ON deals(commission_rate);

-- ============================================
-- อัพเดทข้อมูลเดิม (คำนวณค่าคอมมิชชั่นและรายได้สุทธิสำหรับข้อมูลที่มีอยู่)
-- ============================================

-- อัพเดท orders ที่มีข้อมูลอยู่แล้ว (คำนวณจาก total_amount)
UPDATE orders 
SET 
    commission_amount = COALESCE(total_amount * (commission_rate / 100), 0),
    net_income = COALESCE(total_amount - (total_amount * (commission_rate / 100)) - tax, 0)
WHERE commission_amount = 0 OR net_income = 0;

-- อัพเดท deals ที่มีข้อมูลอยู่แล้ว
UPDATE deals 
SET 
    commission_amount = COALESCE(amount * (commission_rate / 100), 0),
    net_income = COALESCE(amount - (amount * (commission_rate / 100)), 0)
WHERE commission_amount = 0 OR net_income = 0;

-- ============================================
-- Migration Complete
-- ============================================


