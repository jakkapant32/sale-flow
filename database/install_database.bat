@echo off
REM ============================================
REM สคริปต์ติดตั้งฐานข้อมูล CRM
REM ============================================

echo ============================================
echo ติดตั้งฐานข้อมูล CRM
echo ============================================
echo.
echo กำลังเชื่อมต่อกับฐานข้อมูล...
echo.

REM ตรวจสอบว่า psql มีอยู่ใน PATH หรือไม่
where psql >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] ไม่พบ psql command
    echo.
    echo กรุณาเลือกวิธีใดวิธีหนึ่งต่อไปนี้:
    echo.
    echo 1. ติดตั้ง PostgreSQL Client:
    echo    - ดาวน์โหลดจาก: https://www.postgresql.org/download/windows/
    echo    - ติดตั้ง PostgreSQL และเลือก "Command Line Tools"
    echo    - เพิ่ม C:\Program Files\PostgreSQL\XX\bin ลงใน PATH
    echo.
    echo 2. ใช้ pgAdmin หรือ DBeaver:
    echo    - เปิดไฟล์ database/reset_and_install.sql
    echo    - รันสคริปต์ทั้งหมด
    echo.
    echo 3. ใช้ Render.com Dashboard:
    echo    - เข้าไปที่ Render.com Dashboard
    echo    - เปิด PostgreSQL database
    echo    - ใช้ SQL Editor เพื่อรันสคริปต์
    echo.
    pause
    exit /b 1
)

echo กำลังรันสคริปต์ติดตั้ง...
echo.

set PGPASSWORD=p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4
psql -h dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com -U smartsales_user -d smartsales_db_tp3c -f database\reset_and_install.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo ติดตั้งสำเร็จ!
    echo ============================================
    echo.
    echo ตารางทั้งหมดถูกสร้างเรียบร้อยแล้ว
    echo ข้อมูลเริ่มต้นถูกใส่เข้าไปแล้ว
    echo.
    echo ข้อมูลเข้าสู่ระบบ:
    echo   Username: admin
    echo   Password: admin123
    echo.
    echo ควรเปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก!
    echo.
) else (
    echo.
    echo ============================================
    echo เกิดข้อผิดพลาด!
    echo ============================================
    echo.
    echo กรุณาตรวจสอบ:
    echo - การเชื่อมต่ออินเทอร์เน็ต
    echo - ข้อมูลการเชื่อมต่อฐานข้อมูล
    echo - สิทธิ์ในการเข้าถึงฐานข้อมูล
    echo.
)

pause

