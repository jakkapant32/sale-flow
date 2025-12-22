# วิธีติดตั้งฐานข้อมูล CRM

## ⚠️ คำเตือน
สคริปต์ติดตั้งจะ**ลบตารางเก่าทั้งหมด**และสร้างใหม่ทั้งหมด  
กรุณาสำรองข้อมูลก่อนหากต้องการเก็บข้อมูลเดิม

---

## วิธีที่ 1: ใช้ไฟล์ .bat (Windows)

### ถ้ามี PostgreSQL Client ติดตั้งแล้ว:

1. **ดับเบิลคลิกที่ไฟล์:**
   - `database/install_database.bat` - สำหรับติดตั้งฐานข้อมูล
   - `database/check_database.bat` - สำหรับตรวจสอบตาราง

2. ไฟล์จะรันสคริปต์ให้อัตโนมัติ

---

## วิธีที่ 2: ใช้ pgAdmin (แนะนำสำหรับ Windows)

1. **ดาวน์โหลดและติดตั้ง pgAdmin:**
   - https://www.pgadmin.org/download/pgadmin-4-windows/

2. **เชื่อมต่อฐานข้อมูล:**
   - เปิด pgAdmin
   - คลิกขวาที่ "Servers" → "Create" → "Server"
   - ใส่ข้อมูลดังนี้:
     - **General Tab → Name:** `Render CRM`
     - **Connection Tab:**
       - **Host:** `dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com`
       - **Port:** `5432`
       - **Database:** `smartsales_db_tp3c`
       - **Username:** `smartsales_user`
       - **Password:** `p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4`
     - **Save Password:** ✓

3. **รันสคริปต์:**
   - เปิด Tools → Query Tool
   - เปิดไฟล์ `database/reset_and_install.sql`
   - กด F5 หรือคลิก Execute (▶)

---

## วิธีที่ 3: ใช้ DBeaver (ฟรี)

1. **ดาวน์โหลด DBeaver:**
   - https://dbeaver.io/download/

2. **เชื่อมต่อฐานข้อมูล:**
   - File → New → Database Connection
   - เลือก PostgreSQL
   - ใส่ข้อมูลการเชื่อมต่อ:
     - **Host:** `dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com`
     - **Port:** `5432`
     - **Database:** `smartsales_db_tp3c`
     - **Username:** `smartsales_user`
     - **Password:** `p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4`

3. **รันสคริปต์:**
   - เปิดไฟล์ `database/reset_and_install.sql`
   - กด Ctrl+Enter หรือคลิก Execute

---

## วิธีที่ 4: ใช้ Render.com Dashboard

1. **เข้า Render.com Dashboard:**
   - เข้าสู่ระบบ https://dashboard.render.com

2. **เปิด PostgreSQL Database:**
   - คลิกที่ database `smartsales-postgres`

3. **ใช้ SQL Editor:**
   - คลิก "Connect" → "External Connection"
   - หรือใช้ "PSQL Command" ที่ให้มา
   - Copy เนื้อหาจากไฟล์ `database/reset_and_install.sql`
   - Paste และ Execute

---

## วิธีที่ 5: ใช้ Command Line (ถ้าติดตั้ง PostgreSQL แล้ว)

### สำหรับ Windows (PowerShell หรือ Command Prompt):

```powershell
$env:PGPASSWORD="p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4"
psql -h dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com -U smartsales_user -d smartsales_db_tp3c -f database\reset_and_install.sql
```

### สำหรับ Linux/Mac:

```bash
PGPASSWORD=p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4 psql -h dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com -U smartsales_user -d smartsales_db_tp3c -f database/reset_and_install.sql
```

---

## ตรวจสอบผลลัพธ์

หลังจากรันสคริปต์แล้ว ควรมี:

### ตาราง (7 ตาราง):
- ✅ `users`
- ✅ `customers`
- ✅ `products`
- ✅ `deals`
- ✅ `activities`
- ✅ `orders`
- ✅ `order_items`

### ข้อมูลเริ่มต้น:
- ✅ 1 user (admin)
- ✅ 3 products (ตัวอย่าง)

---

## ข้อมูลเข้าสู่ระบบ

หลังจากติดตั้งเสร็จแล้ว สามารถเข้าสู่ระบบด้วย:

- **Username:** `admin`
- **Password:** `admin123`

⚠️ **ควรเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก!**

---

## แก้ไขปัญหา

### ไม่สามารถเชื่อมต่อฐานข้อมูลได้
- ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต
- ตรวจสอบว่า Render.com database กำลังทำงานอยู่
- ตรวจสอบ firewall/antivirus ที่อาจบล็อกการเชื่อมต่อ

### psql: command not found
- ติดตั้ง PostgreSQL Client
- หรือใช้ pgAdmin/DBeaver แทน

### Error: permission denied
- ตรวจสอบ username และ password
- ตรวจสอบว่า database name ถูกต้อง

---

## ไฟล์ที่เกี่ยวข้อง

- `database/reset_and_install.sql` - สคริปต์ติดตั้งหลัก (ลบตารางเก่าและสร้างใหม่)
- `database/check_tables.sql` - สคริปต์ตรวจสอบตาราง
- `database/schema.sql` - Schema เดิม (ใช้ CREATE IF NOT EXISTS)
- `database/install_database.bat` - สคริปต์ Windows สำหรับติดตั้ง
- `database/check_database.bat` - สคริปต์ Windows สำหรับตรวจสอบ

