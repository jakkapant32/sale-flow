# PowerShell script สำหรับ Push ไป GitHub

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Push SalesFlow to GitHub" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "ขั้นตอน:"
Write-Host "1. สร้าง Personal Access Token ที่: https://github.com/settings/tokens" -ForegroundColor Yellow
Write-Host "2. เลือก scope: repo"
Write-Host "3. Copy token ที่ได้"
Write-Host ""

$token = Read-Host "กรอก Token ที่ได้"

if ([string]::IsNullOrWhiteSpace($token)) {
    Write-Host "Error: ต้องกรอก Token!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "กำลัง push ไปยัง GitHub..." -ForegroundColor Green

$remoteUrl = "https://$token@github.com/jakkapant32/sale-flow.git"

try {
    git push -u $remoteUrl main
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  สำเร็จ! Code ถูก push ไป GitHub แล้ว" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Repository: https://github.com/jakkapant32/sale-flow" -ForegroundColor Cyan
    Write-Host ""
} catch {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "  Error: Push ไม่สำเร็จ" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "ตรวจสอบว่า:" -ForegroundColor Yellow
    Write-Host "- Token ถูกต้องหรือไม่"
    Write-Host "- มีสิทธิ์เข้าถึง repository หรือไม่"
    Write-Host ""
}

Read-Host "กด Enter เพื่อปิด"

