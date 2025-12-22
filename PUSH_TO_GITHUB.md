# 🚀 วิธี Push Code ไป GitHub

## ขั้นตอนที่ 1: สร้าง Personal Access Token

1. ไปที่: https://github.com/settings/tokens
2. คลิก "Generate new token" → "Generate new token (classic)"
3. ตั้งค่า:
   - **Note**: `SalesFlow Deployment`
   - **Expiration**: เลือก 90 days หรือ No expiration
   - **Select scopes**: ✅ เลือก `repo` (Full control of private repositories)
4. คลิก "Generate token"
5. **COPY TOKEN ทันที** (จะแสดงแค่ครั้งเดียว)

## ขั้นตอนที่ 2: Push Code

เปิด Command Prompt หรือ PowerShell ในโฟลเดอร์ `C:\xampp\htdocs\SalesFlow` แล้วรัน:

```bash
# แทนที่ YOUR_TOKEN ด้วย token ที่ copy มา
git push -u https://YOUR_TOKEN@github.com/jakkapant32/sale-flow.git main
```

**หรือ** ถ้าใช้ GitHub Desktop:
1. ติดตั้ง: https://desktop.github.com/
2. Sign in
3. File → Add Local Repository → เลือก `C:\xampp\htdocs\SalesFlow`
4. คลิก "Publish repository"

---

**หมายเหตุ**: Commit พร้อมแล้ว (52 files) กำลังรอ push ไป GitHub

