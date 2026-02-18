# คำสั่งที่ต้องรัน — ตั้งค่าฐานข้อมูล SalesFlow

โปรเจกต์ถูกตรวจสอบแล้ว Config ชี้ไปที่ DB ใหม่ (`salesflow_0s9k`) และไฟล์ `reset_and_install.sql` ถูกอัปเดตให้มีคอลัมน์ commission/tax_rate ครบแล้ว

---

## วิธีที่ 1: ผ่าน pgAdmin (แนะนำ)

1. เปิด pgAdmin → เชื่อมต่อ **salesflow-db** → เลือก database **salesflow_0s9k**
2. คลิกขวาที่ **salesflow_0s9k** → **Query Tool**
3. เปิดไฟล์ SQL แล้วรัน **ทีละไฟล์** ตามลำดับด้านล่าง

### ถ้าฐานข้อมูลยังว่าง (ไม่มีตารางเลย)

รันไฟล์เดียวนี้ก็พอ:

| ลำดับ | ไฟล์ | หมายเหตุ |
|------|------|----------|
| 1 | `database/schema.sql` | สร้าง extension, ตารางทั้งหมด, index, trigger, user admin, ผลิตภัณฑ์ตัวอย่าง |

**หรือ** ใช้วิธีผ่านเว็บ (วิธีที่ 3) โดยเปิด `install.php` แล้วกดติดตั้ง (จะรัน `reset_and_install.sql` ให้)

### ถ้ามีตารางเก่าแล้ว และอยากล้างแล้วสร้างใหม่

| ลำดับ | ไฟล์ | หมายเหตุ |
|------|------|----------|
| 1 | `database/reset_and_install.sql` | ลบตารางเก่า + สร้างใหม่ + ข้อมูลเริ่มต้น |

---

## วิธีที่ 2: ผ่าน Command Line (psql)

ถ้าติดตั้ง PostgreSQL client (psql) แล้ว ให้เปิด PowerShell แล้วรัน:

```powershell
cd c:\xampp\htdocs\SalesFlow

$env:PGPASSWORD = "fGlCkhwQLud9M7rPo3BglnwyRQaKtiYm"
psql -h dpg-d6ai51i48b3s73bb4q5g-a.oregon-postgres.render.com -p 5432 -U salesflow_user -d salesflow_0s9k -f database/schema.sql
```

**ถ้าฐานข้อมูลมีตารางอยู่แล้วและต้องการล้างแล้วสร้างใหม่:**

```powershell
$env:PGPASSWORD = "fGlCkhwQLud9M7rPo3BglnwyRQaKtiYm"
psql -h dpg-d6ai51i48b3s73bb4q5g-a.oregon-postgres.render.com -p 5432 -U salesflow_user -d salesflow_0s9k -f database/reset_and_install.sql
```

(รันจากโฟลเดอร์ `c:\xampp\htdocs\SalesFlow` เหมือนด้านบน)

---

## วิธีที่ 3: ผ่านเบราว์เซอร์ (install.php)

1. เปิด XAMPP → Start **Apache**
2. เปิดเบราว์เซอร์ไปที่:  
   **http://localhost/SalesFlow/install.php**
3. กดปุ่ม **「ติดตั้งฐานข้อมูล (ลบตารางเก่าและสร้างใหม่)」**  
   → ระบบจะรัน `database/reset_and_install.sql` ให้อัตโนมัติ
4. หลังติดตั้งสำเร็จ: ล็อกอินด้วย **admin** / **admin123** แล้วควรเปลี่ยนรหัสผ่าน
5. เพื่อความปลอดภัย: ลบหรือเปลี่ยนชื่อไฟล์ `install.php` หลังใช้เสร็จ

---

## หลังรันเสร็จ

- **ล็อกอิน:** Username = `admin` , Password = `admin123`  
  (เปลี่ยนรหัสผ่านหลังเข้าใช้ครั้งแรก)
- ตรวจสอบแอป: เปิด **http://localhost/SalesFlow/** แล้วลองใช้งาน Dashboard, ลูกค้า, Deals, Orders

---

## สรุปสิ่งที่ตรวจ/แก้ในโปรเจกต์

| รายการ | สถานะ |
|--------|--------|
| `config/database.php` | ชี้ไป host/db/user/password ใหม่แล้ว |
| `database/schema.sql` | มีตาราง + commission/tax_rate ครบ ใช้กับ PG 16 ได้ |
| `database/reset_and_install.sql` | เพิ่มคอลัมน์ commission_rate, commission_amount, net_income, tax_rate และ index ที่เกี่ยวข้องแล้ว |
| แอป (api/dashboard, orders, deals) | ใช้คอลัมน์ commission / net_income / tax_rate ตรงกับ schema แล้ว |

รันตามวิธีใดวิธีหนึ่งด้านบนเพียงครั้งเดียวก็เพียงพอสำหรับการตั้งค่าฐานข้อมูลใหม่
