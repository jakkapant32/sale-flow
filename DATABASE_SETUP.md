# การตั้งค่าฐานข้อมูลใหม่บน Render.com

## ข้อมูลฐานข้อมูลใหม่

- **Name:** salesflow-postgres
- **Hostname:** dpg-d5mvt9mr433s739e5itg-a
- **Port:** 5432
- **Database:** salesflow_jj90
- **Username:** salesflow_user
- **Password:** NChIixpe8r33jC0rDjlp7WrgVc7Tdusv

## Internal Database URL (ใช้สำหรับ Web Service ใน Render)
```
postgresql://salesflow_user:NChIixpe8r33jC0rDjlp7WrgVc7Tdusv@dpg-d5mvt9mr433s739e5itg-a/salesflow_jj90
```

## External Database URL (ใช้สำหรับการเชื่อมต่อจากภายนอก)
```
postgresql://salesflow_user:NChIixpe8r33jC0rDjlp7WrgVc7Tdusv@dpg-d5mvt9mr433s739e5itg-a.oregon-postgres.render.com/salesflow_jj90
```

## ขั้นตอนการตั้งค่า Environment Variables บน Render.com

### 1. ไปที่ Web Service ของ SalesFlow
- เข้าสู่ Render.com Dashboard
- เลือก Web Service ที่ชื่อ "salesflow" หรือชื่อที่คุณตั้งไว้

### 2. ไปที่แท็บ "Environment"
- คลิกที่แท็บ "Environment" ในเมนูด้านซ้าย

### 3. อัปเดต Environment Variables

#### วิธีที่ 1: ใช้ DATABASE_URL (แนะนำ)
เพิ่มหรือแก้ไข environment variable:
- **Key:** `DATABASE_URL`
- **Value:** `postgresql://salesflow_user:NChIixpe8r33jC0rDjlp7WrgVc7Tdusv@dpg-d5mvt9mr433s739e5itg-a/salesflow_jj90`

#### วิธีที่ 2: ใช้ค่าทีละตัว (ถ้าไม่ใช้ DATABASE_URL)
- **Key:** `DB_HOST`  
  **Value:** `dpg-d5mvt9mr433s739e5itg-a.oregon-postgres.render.com`

- **Key:** `DB_PORT`  
  **Value:** `5432`

- **Key:** `DB_NAME`  
  **Value:** `salesflow_jj90`

- **Key:** `DB_USER`  
  **Value:** `salesflow_user`

- **Key:** `DB_PASSWORD`  
  **Value:** `NChIixpe8r33jC0rDjlp7WrgVc7Tdusv`

### 4. บันทึกและ Deploy ใหม่
- คลิก "Save Changes"
- Render จะ deploy service ใหม่โดยอัตโนมัติ

## การสร้าง Schema และข้อมูลเริ่มต้น

หลังจากตั้งค่า environment variables แล้ว ให้รัน SQL scripts ต่อไปนี้ในฐานข้อมูลใหม่:

### 1. สร้าง Schema
```bash
# ใช้ PSQL Command จาก Render.com
PGPASSWORD=NChIixpe8r33jC0rDjlp7WrgVc7Tdusv psql -h dpg-d5mvt9mr433s739e5itg-a.oregon-postgres.render.com -U salesflow_user salesflow_jj90 < database/schema.sql
```

หรือใช้ pgAdmin หรือ DBeaver เพื่อรันไฟล์ `database/schema.sql`

### 2. เพิ่มข้อมูลตัวอย่าง (ถ้าต้องการ)
```bash
# รันไฟล์เพิ่มข้อมูลตัวอย่างสำหรับ user "tum"
psql -h dpg-d5mvt9mr433s739e5itg-a.oregon-postgres.render.com -U salesflow_user -d salesflow_jj90 -f database/add_user_tum_sample_data_step_by_step.sql
```

## ตรวจสอบการเชื่อมต่อ

หลังจาก deploy แล้ว ให้ตรวจสอบว่า Web Service เชื่อมต่อฐานข้อมูลได้:
1. เปิด Web Service URL
2. ลอง login หรือเข้าหน้า dashboard
3. ตรวจสอบ logs ใน Render.com ว่ามี error เกี่ยวกับ database หรือไม่

## หมายเหตุ

- **Internal Database URL** ใช้สำหรับ Web Service ที่อยู่ใน Render.com เดียวกัน (เร็วกว่า)
- **External Database URL** ใช้สำหรับการเชื่อมต่อจากเครื่อง local หรือเครื่องอื่น
- Password ควรเก็บเป็นความลับและไม่ควร commit ลง Git

