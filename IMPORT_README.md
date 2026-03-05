# การนำเข้าข้อมูลจากไฟล์ (CSV / Excel)

## รองรับไฟล์
- **CSV** – ใช้ได้ทันที
- **XLSX, XLS** – ต้องติดตั้ง Composer และรัน `composer install` ก่อน (เพื่อใช้ไลบรารี PhpSpreadsheet)

## วิธีใช้
1. หน้า **ลูกค้า** หรือ **สินค้า** → กดปุ่ม **นำเข้าจากไฟล์ (CSV/Excel)**
2. เลือกไฟล์ (แถวแรกต้องเป็นหัวคอลัมน์)
3. กด **นำเข้า**

## รูปแบบคอลัมน์ที่รองรับ

### ลูกค้า (Customer)
| คอลัมน์ในไฟล์ | ใช้กับฟิลด์ |
|---------------|-------------|
| *Customer ID / Customer ID | รหัสลูกค้า (ว่างได้ ระบบสร้างให้) |
| *Customer Name / Customer Name | ชื่อองค์กร / ลูกค้า |
| *Customer Category | ประเภท/อุตสาหกรรม |
| *Contact Person | ชื่อผู้ติดต่อ |
| *Phone | โทรศัพท์ |
| *Email | อีเมล |
| *Address | ที่อยู่ |
| Decision Maker | หมายเหตุ |

### สินค้า (Product)
| คอลัมน์ในไฟล์ | ใช้กับฟิลด์ |
|---------------|-------------|
| *Product ID / Product ID | รหัสสินค้า (ว่างได้ ระบบสร้างให้) |
| *Product Name / Product Name | ชื่อสินค้า |
| *Category / Category | หมวดหมู่ |
| Description | รายละเอียด |
| Quantity | จำนวนคงเหลือ |
| Unit price | ราคาขาย |
| Status (Active/Inactive) | สถานะ |

## ติดตั้งรองรับ Excel (XLSX/XLS)
```bash
composer install
```
ถ้าไม่มี Composer: ดาวน์โหลดที่ https://getcomposer.org/
