# PowerShell script สำหรับ Push ไป GitHub ด้วย Token
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Push SalesFlow to GitHub" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# ขอ Token จากผู้ใช้
$token = Read-Host "กรอก Personal Access Token ที่ได้จาก GitHub"

if ([string]::IsNullOrWhiteSpace($token)) {
    Write-Host "Error: ต้องกรอก Token!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "กำลังลบ credential cache..." -ForegroundColor Yellow

# ลบ cached credentials
git credential reject <<< "protocol=https`nhost=github.com"

Write-Host "กำลัง push ไปยัง GitHub..." -ForegroundColor Green

# ใช้ token ใน URL โดยตรง
$remoteUrl = "https://$token@github.com/jakkapant32/sale-flow.git"

try {
    # ตั้งค่า remote URL ใหม่พร้อม token
    git remote set-url origin $remoteUrl
    
    # Push
    git push -u origin main
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  สำเร็จ! Code ถูก push ไป GitHub แล้ว" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Repository: https://github.com/jakkapant32/sale-flow" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "หมายเหตุ: Token ถูกเก็บไว้ใน git remote URL" -ForegroundColor Yellow
    Write-Host "หากต้องการลบ ให้รัน: git remote set-url origin https://github.com/jakkapant32/sale-flow.git" -ForegroundColor Yellow
    Write-Host ""
} catch {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "  Error: Push ไม่สำเร็จ" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "ตรวจสอบว่า:" -ForegroundColor Yellow
    Write-Host "- Token ถูกต้องหรือไม่"
    Write-Host "- Token มี scope 'repo' หรือไม่"
    Write-Host "- มีสิทธิ์เข้าถึง repository หรือไม่"
    Write-Host ""
    Write-Host "Error details: $_" -ForegroundColor Red
}

Read-Host "กด Enter เพื่อปิด"

