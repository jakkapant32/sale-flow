-- ============================================
-- CRM Database Reset and Installation Script
-- ============================================
-- สคริปต์นี้จะลบตารางเก่าทั้งหมด (ถ้ามี) และสร้างใหม่ทั้งหมด
-- ⚠️ คำเตือน: สคริปต์นี้จะลบข้อมูลทั้งหมดในตาราง!
-- ============================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- DROP ตารางเก่าทั้งหมด (ถ้ามี) พร้อม CASCADE
-- ============================================
-- ลบในลำดับที่ถูกต้องเพื่อหลีกเลี่ยง foreign key constraints

DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS activities CASCADE;
DROP TABLE IF EXISTS deals CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS customers CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- ลบ triggers และ functions ที่เกี่ยวข้อง
DROP FUNCTION IF EXISTS update_updated_at_column() CASCADE;

-- ============================================
-- สร้างตารางใหม่ทั้งหมด
-- ============================================

-- Users table (for authentication)
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customers table
CREATE TABLE customers (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    customer_code VARCHAR(50) UNIQUE,
    company_name VARCHAR(255),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    mobile VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Thailand',
    customer_type VARCHAR(50) DEFAULT 'individual',
    industry VARCHAR(100),
    website VARCHAR(255),
    status VARCHAR(50) DEFAULT 'active',
    source VARCHAR(100),
    assigned_to UUID REFERENCES users(id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    product_code VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    unit_price DECIMAL(15, 2) NOT NULL,
    cost_price DECIMAL(15, 2),
    stock_quantity INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Deals/Opportunities table
CREATE TABLE deals (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    deal_code VARCHAR(50) UNIQUE,
    deal_name VARCHAR(255) NOT NULL,
    customer_id UUID REFERENCES customers(id) ON DELETE CASCADE,
    product_id UUID REFERENCES products(id) ON DELETE SET NULL,
    amount DECIMAL(15, 2) NOT NULL,
    commission_rate DECIMAL(5, 2) DEFAULT 7.00,
    commission_amount DECIMAL(15, 2) DEFAULT 0,
    net_income DECIMAL(15, 2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'THB',
    stage VARCHAR(50) DEFAULT 'prospecting',
    probability INTEGER DEFAULT 0 CHECK (probability >= 0 AND probability <= 100),
    expected_close_date DATE,
    actual_close_date DATE,
    status VARCHAR(50) DEFAULT 'open',
    assigned_to UUID REFERENCES users(id) ON DELETE SET NULL,
    description TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activities/Tasks table
CREATE TABLE activities (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    activity_type VARCHAR(50) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT,
    related_to VARCHAR(50),
    related_id UUID,
    customer_id UUID REFERENCES customers(id) ON DELETE CASCADE,
    deal_id UUID REFERENCES deals(id) ON DELETE CASCADE,
    assigned_to UUID REFERENCES users(id) ON DELETE SET NULL,
    due_date DATE,
    completion_date TIMESTAMP,
    status VARCHAR(50) DEFAULT 'pending',
    priority VARCHAR(20) DEFAULT 'medium',
    location VARCHAR(255),
    duration_minutes INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders/Sales table
CREATE TABLE orders (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id UUID REFERENCES customers(id) ON DELETE CASCADE,
    deal_id UUID REFERENCES deals(id) ON DELETE SET NULL,
    order_date DATE NOT NULL,
    delivery_date DATE,
    status VARCHAR(50) DEFAULT 'pending',
    subtotal DECIMAL(15, 2) NOT NULL,
    tax DECIMAL(15, 2) DEFAULT 0,
    tax_rate DECIMAL(5, 2) DEFAULT 7.00,
    discount DECIMAL(15, 2) DEFAULT 0,
    total_amount DECIMAL(15, 2) NOT NULL,
    commission_rate DECIMAL(5, 2) DEFAULT 7.00,
    commission_amount DECIMAL(15, 2) DEFAULT 0,
    net_income DECIMAL(15, 2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'THB',
    payment_status VARCHAR(50) DEFAULT 'unpaid',
    payment_method VARCHAR(50),
    notes TEXT,
    created_by UUID REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order Items table
CREATE TABLE order_items (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    order_id UUID REFERENCES orders(id) ON DELETE CASCADE,
    product_id UUID REFERENCES products(id) ON DELETE RESTRICT,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(15, 2) NOT NULL,
    discount DECIMAL(15, 2) DEFAULT 0,
    total_price DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- สร้าง Indexes สำหรับประสิทธิภาพที่ดีขึ้น
-- ============================================

CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email);
CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone);
CREATE INDEX IF NOT EXISTS idx_customers_assigned_to ON customers(assigned_to);
CREATE INDEX IF NOT EXISTS idx_customers_status ON customers(status);

CREATE INDEX IF NOT EXISTS idx_deals_customer_id ON deals(customer_id);
CREATE INDEX IF NOT EXISTS idx_deals_stage ON deals(stage);
CREATE INDEX IF NOT EXISTS idx_deals_status ON deals(status);
CREATE INDEX IF NOT EXISTS idx_deals_assigned_to ON deals(assigned_to);

CREATE INDEX IF NOT EXISTS idx_activities_customer_id ON activities(customer_id);
CREATE INDEX IF NOT EXISTS idx_activities_deal_id ON activities(deal_id);
CREATE INDEX IF NOT EXISTS idx_activities_due_date ON activities(due_date);
CREATE INDEX IF NOT EXISTS idx_activities_status ON activities(status);

CREATE INDEX IF NOT EXISTS idx_orders_customer_id ON orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_orders_order_date ON orders(order_date);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status);
CREATE INDEX IF NOT EXISTS idx_orders_commission_rate ON orders(commission_rate);

CREATE INDEX IF NOT EXISTS idx_deals_commission_rate ON deals(commission_rate);

CREATE INDEX IF NOT EXISTS idx_products_code ON products(product_code);
CREATE INDEX IF NOT EXISTS idx_products_status ON products(status);

-- ============================================
-- สร้าง Function และ Triggers สำหรับ updated_at
-- ============================================

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers for updated_at
CREATE TRIGGER update_users_updated_at 
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_customers_updated_at 
    BEFORE UPDATE ON customers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_products_updated_at 
    BEFORE UPDATE ON products
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_deals_updated_at 
    BEFORE UPDATE ON deals
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_activities_updated_at 
    BEFORE UPDATE ON activities
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_orders_updated_at 
    BEFORE UPDATE ON orders
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================
-- ใส่ข้อมูลเริ่มต้น (Initial Data)
-- ============================================

-- Insert default admin user (password: admin123 - ควรเปลี่ยนทันที!)
-- Password hash สำหรับ "admin123"
INSERT INTO users (username, email, password_hash, full_name, role, status) 
VALUES (
    'admin', 
    'admin@crm.local', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Administrator', 
    'admin',
    'active'
)
ON CONFLICT (username) DO UPDATE 
SET 
    email = EXCLUDED.email,
    password_hash = EXCLUDED.password_hash,
    full_name = EXCLUDED.full_name,
    role = EXCLUDED.role,
    status = EXCLUDED.status;

-- Insert sample products (ตัวอย่างสินค้า)
INSERT INTO products (product_code, product_name, description, category, unit_price, cost_price, stock_quantity, status) VALUES
('PROD001', 'Product A', 'Description for Product A', 'Category 1', 1000.00, 500.00, 100, 'active'),
('PROD002', 'Product B', 'Description for Product B', 'Category 1', 2000.00, 1000.00, 50, 'active'),
('PROD003', 'Product C', 'Description for Product C', 'Category 2', 3000.00, 1500.00, 75, 'active')
ON CONFLICT (product_code) DO NOTHING;

-- ============================================
-- ตรวจสอบผลลัพธ์
-- ============================================

-- แสดงรายการตารางทั้งหมด
SELECT 
    table_name,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = t.table_name) as column_count
FROM information_schema.tables t
WHERE table_schema = 'public' 
    AND table_type = 'BASE TABLE'
ORDER BY table_name;

-- แสดงจำนวนข้อมูลในแต่ละตาราง
SELECT 'users' as table_name, COUNT(*) as record_count FROM users
UNION ALL
SELECT 'customers', COUNT(*) FROM customers
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

-- ============================================
-- เสร็จสิ้น!
-- ============================================
-- ตารางทั้งหมดถูกสร้างเรียบร้อยแล้ว
-- ข้อมูลเริ่มต้นถูกใส่เข้าไปแล้ว
-- 
-- ข้อมูลเข้าสู่ระบบเริ่มต้น:
-- Username: admin
-- Password: admin123
-- ⚠️ ควรเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก!
-- ============================================

