@echo off
echo ========================================
echo   Push SalesFlow to GitHub
echo ========================================
echo.
echo ขั้นตอน:
echo 1. สร้าง Personal Access Token ที่: https://github.com/settings/tokens
echo 2. เลือก scope: repo
echo 3. Copy token ที่ได้
echo.
set /p TOKEN="กรอก Token ที่ได้: "

if "%TOKEN%"=="" (
    echo Error: ต้องกรอก Token!
    pause
    exit /b 1
)

echo.
echo กำลังลบ credential cache เก่า...
git credential-manager-core erase <<< protocol=https`nhost=github.com || git credential reject <<< protocol=https`nhost=github.com || echo "Clearing credentials..."

echo.
echo กำลังตั้งค่า remote URL...
git remote set-url origin https://%TOKEN%@github.com/jakkapant32/sale-flow.git

echo.
echo กำลัง push ไปยัง GitHub...
git push -u origin main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo   สำเร็จ! Code ถูก push ไป GitHub แล้ว
    echo ========================================
    echo.
    echo Repository: https://github.com/jakkapant32/sale-flow
    echo.
) else (
    echo.
    echo ========================================
    echo   Error: Push ไม่สำเร็จ
    echo ========================================
    echo.
    echo ตรวจสอบว่า:
    echo - Token ถูกต้องหรือไม่
    echo - มีสิทธิ์เข้าถึง repository หรือไม่
    echo.
)

pause

