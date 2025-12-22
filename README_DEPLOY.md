# 🚀 คู่มือการ Deploy SalesFlow บน Render.com

## ขั้นตอนการ Deploy

### 1. เตรียม Database บน Render.com

1. เข้าไปที่ [Render Dashboard](https://dashboard.render.com/)
2. คลิก "New +" → เลือก "PostgreSQL"
3. ตั้งค่า:
   - **Name**: `salesflow-db` (หรือชื่อที่ต้องการ)
   - **Database**: `salesflow_production`
   - **User**: `salesflow_user`
   - **Region**: เลือก region ที่ใกล้ที่สุด
   - **PostgreSQL Version**: 14 หรือใหม่กว่า
4. คลิก "Create Database"
5. **บันทึกข้อมูล**:
   - Internal Database URL (สำหรับใช้ใน Render)
   - External Connection String (สำหรับใช้จากภายนอก)
   - Username และ Password

### 2. ติดตั้ง Database Schema

#### วิธีที่ 1: ใช้ pgAdmin หรือ Database Tool

1. ใช้ External Connection String ที่ได้จาก Render
2. เชื่อมต่อกับ database
3. Run SQL จากไฟล์ `database/schema.sql`

#### วิธีที่ 2: ใช้ Command Line (psql)

```bash
# ติดตั้ง psql (ถ้ายังไม่มี)
# Windows: ติดตั้ง PostgreSQL client
# Mac: brew install postgresql
# Linux: sudo apt-get install postgresql-client

# เชื่อมต่อและ run schema
psql "your-external-connection-string" < database/schema.sql

# หรือ seed ข้อมูลตัวอย่าง
psql "your-external-connection-string" < database/seed_sample_data_simple.sql
```

### 3. Deploy Web Service

1. ใน Render Dashboard คลิก "New +" → เลือก "Web Service"
2. เชื่อมต่อ Repository:
   - เชื่อมต่อ GitHub/GitLab repository ที่มีโค้ด
   - หรือ Deploy จาก Public Git repository
3. ตั้งค่าการ Deploy:

   **Basic Settings:**
   - **Name**: `salesflow` (หรือชื่อที่ต้องการ)
   - **Environment**: `PHP`
   - **Build Command**: (ว่างไว้ - PHP ไม่ต้อง build)
   - **Start Command**: `php -S 0.0.0.0:$PORT -t .`

   **Environment Variables:**
   - เพิ่ม Environment Variables ต่อไปนี้:

   ```
   DATABASE_URL = postgresql://username:password@host:port/database
   ```

   หรือแยกเป็น:

   ```
   DB_HOST = your-database-host
   DB_PORT = 5432
   DB_NAME = your-database-name
   DB_USER = your-database-user
   DB_PASSWORD = your-database-password
   ```

   **หมายเหตุ**: สำหรับ Internal Database URL จาก Render ใช้ format:
   ```
   postgresql://user:password@hostname:5432/database
   ```

4. คลิก "Create Web Service"
5. รอให้ Deploy เสร็จสิ้น (ประมาณ 2-3 นาที)

### 4. ตั้งค่า Environment Variables

ใน Web Service Dashboard:

1. ไปที่แท็บ "Environment"
2. เพิ่มตัวแปรต่อไปนี้:

```
DATABASE_URL=postgresql://username:password@host:port/database
```

หรือ:

```
DB_HOST=your-host
DB_PORT=5432
DB_NAME=your-db-name
DB_USER=your-user
DB_PASSWORD=your-password
```

3. **สำคัญ**: ใช้ Internal Database URL จาก Render Database (เริ่มต้นด้วย `postgresql://`) เพื่อให้เชื่อมต่อภายใน network ของ Render ได้เร็วกว่า

### 5. Verify Deployment

1. รอให้ Deploy เสร็จสิ้น
2. เปิด URL ที่ Render ให้มา (เช่น `https://salesflow.onrender.com`)
3. ตรวจสอบว่า:
   - หน้า Login แสดงขึ้นมา
   - สามารถ Register และ Login ได้
   - ข้อมูลใน Dashboard แสดงถูกต้อง

### 6. Troubleshooting

#### ปัญหา: Cannot connect to database

**แก้ไข**:
- ตรวจสอบว่าใช้ Internal Database URL (ไม่ใช่ External)
- ตรวจสอบว่า Database Service กำลังทำงานอยู่
- ตรวจสอบ Environment Variables ว่าใส่ถูกต้อง

#### ปัญหา: 404 Not Found

**แก้ไข**:
- ตรวจสอบ Start Command ว่าเป็น `php -S 0.0.0.0:$PORT -t .`
- ตรวจสอบว่าไฟล์ `index.php` อยู่ใน root directory

#### ปัญหา: PHP Extensions ไม่มี

**แก้ไข**:
- Render.com รองรับ PHP extensions มาตรฐาน รวมถึง `pdo_pgsql`
- ถ้ายังมีปัญหา ตรวจสอบใน `check_php_extensions.php`

### 7. Production Checklist

- [ ] Database schema ติดตั้งแล้ว
- [ ] Environment Variables ตั้งค่าถูกต้อง
- [ ] สามารถ Login/Register ได้
- [ ] Dashboard แสดงข้อมูลถูกต้อง
- [ ] ทุกหน้าใช้งานได้ปกติ
- [ ] SSL/HTTPS เปิดใช้งานอัตโนมัติ (Render มีให้)

### 8. Custom Domain (Optional)

1. ไปที่ Web Service Settings
2. คลิก "Custom Domains"
3. เพิ่ม domain ที่ต้องการ
4. ตั้งค่า DNS records ตามที่ Render บอก

### 9. Auto-Deploy from Git

Render จะ auto-deploy เมื่อ:
- Push code ไปที่ main/master branch
- ตั้งค่าไว้ใน Settings → Auto-Deploy

---

## 📝 หมายเหตุสำคัญ

1. **Database Connection**: ใช้ Internal Database URL สำหรับประสิทธิภาพที่ดีกว่า
2. **Environment Variables**: ไม่ควร hardcode credentials ใน code
3. **Free Tier**: Render Free tier มี limitations:
   - Web service จะ sleep หลังจาก 15 นาทีไม่มีการใช้งาน
   - Database ใช้ได้ 90 วัน (Free tier)
4. **Performance**: สำหรับ production ควรใช้ Paid plan

---

## 🔗 Links ที่เกี่ยวข้อง

- [Render.com Documentation](https://render.com/docs)
- [Render PHP Guide](https://render.com/docs/php)
- [Render PostgreSQL Guide](https://render.com/docs/databases/postgresql)

