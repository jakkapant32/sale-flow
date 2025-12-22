@echo off
REM ============================================
REM สคริปต์ตรวจสอบตารางในฐานข้อมูล
REM ============================================

echo ============================================
echo ตรวจสอบตารางในฐานข้อมูล
echo ============================================
echo.

REM ตรวจสอบว่า psql มีอยู่ใน PATH หรือไม่
where psql >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] ไม่พบ psql command
    echo กรุณาติดตั้ง PostgreSQL Client ก่อน
    pause
    exit /b 1
)

set PGPASSWORD=p0vTgAP02R8i8hKXjPF5uWpwDsE1nZr4
psql -h dpg-d51mhfggjchc73enlnfg-a.oregon-postgres.render.com -U smartsales_user -d smartsales_db_tp3c -f database\check_tables.sql

pause

