# คำแนะนำการติดตั้งฐานข้อมูล

## วิธีที่ 1: ใช้ psql Command Line (แนะนำ)

### ขั้นตอนที่ 1: ตรวจสอบตารางเก่า (Optional)
```bash
PGPASSWORD=p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4 psql -h dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com -U smartsales_user -d smartsales_db_tp3c -f database/check_tables.sql
```

### ขั้นตอนที่ 2: ลบตารางเก่าและสร้างใหม่ทั้งหมด
⚠️ **คำเตือน: คำสั่งนี้จะลบข้อมูลทั้งหมดในตาราง!**

```bash
PGPASSWORD=p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4 psql -h dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com -U smartsales_user -d smartsales_db_tp3c -f database/reset_and_install.sql
```

## วิธีที่ 2: ใช้ pgAdmin หรือ DBeaver

1. เปิด pgAdmin หรือ DBeaver
2. เชื่อมต่อกับฐานข้อมูล:
   - Host: `dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com`
   - Port: `5432`
   - Database: `smartsales_db_tp3c`
   - Username: `smartsales_user`
   - Password: `p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4`

3. เปิดไฟล์ `database/reset_and_install.sql`
4. รันสคริปต์ทั้งหมด (F5 หรือ Execute)

## วิธีที่ 3: ใช้ psql แบบ Interactive

```bash
# เชื่อมต่อฐานข้อมูล
PGPASSWORD=p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4 psql -h dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com -U smartsales_user -d smartsales_db_tp3c

# ใน psql prompt รัน:
\i database/reset_and_install.sql

# หรือ copy-paste เนื้อหาไฟล์เข้าไป
```

## ตรวจสอบผลลัพธ์

หลังจากรันสคริปต์แล้ว ควรมีตารางดังนี้:
- ✅ users
- ✅ customers
- ✅ products
- ✅ deals
- ✅ activities
- ✅ orders
- ✅ order_items

และควรมีข้อมูลเริ่มต้น:
- ✅ 1 user (admin)
- ✅ 3 products (ตัวอย่าง)

## ข้อมูลเข้าสู่ระบบเริ่มต้น

- **Username:** `admin`
- **Password:** `admin123`

⚠️ **ควรเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก!**

