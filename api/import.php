<?php
/**
 * Import API - CSV and XLSX for Customers and Products
 */

function handleImport($method, $input, $files = []) {
    global $db;
    
    if ($method !== 'POST') {
        sendError('Method not allowed', 405);
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        sendError('Unauthorized', 401);
    }
    
    $db = getDB();
    $type = isset($input['type']) ? trim($input['type']) : '';
    if (!in_array($type, ['customers', 'products'])) {
        sendError('Invalid type. Use customers or products.', 400);
    }
    
    $file = $files['file'] ?? null;
    if (!$file || ($file['error'] !== UPLOAD_ERR_OK)) {
        $msg = 'No file uploaded or upload error.';
        if ($file && $file['error'] !== UPLOAD_ERR_OK) {
            $msg = 'Upload error: ' . $file['error'];
        }
        sendError($msg, 400);
    }
    
    $path = $file['tmp_name'];
    $name = $file['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    
    if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
        sendError('Only CSV, XLSX, or XLS files are allowed.', 400);
    }
    
    $rows = [];
    if ($ext === 'csv') {
        $rows = readCsv($path);
    } else {
        $rows = readExcel($path, $ext);
    }
    
    if (empty($rows)) {
        sendError('No data rows found in file.', 400);
    }
    
    $headers = array_shift($rows);
    $headers = array_map(function ($h) {
        return trim(preg_replace('/^\*+\s*/', '', $h));
    }, $headers);
    
    if ($type === 'customers') {
        $result = importCustomers($db, $headers, $rows);
    } else {
        $result = importProducts($db, $headers, $rows);
    }
    
    sendResponse([
        'message' => 'Import completed',
        'imported' => $result['imported'],
        'skipped' => $result['skipped'],
        'errors' => $result['errors']
    ]);
}

function readCsv($path) {
    $rows = [];
    $fp = fopen($path, 'r');
    if (!$fp) {
        return $rows;
    }
    $bom = fread($fp, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($fp);
    }
    while (($row = fgetcsv($fp)) !== false) {
        $rows[] = $row;
    }
    fclose($fp);
    return $rows;
}

function readExcel($path, $ext) {
    if (!is_file(__DIR__ . '/../vendor/autoload.php')) {
        sendError('Excel support requires Composer. Run: composer install', 503);
    }
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
        $ext === 'xlsx' ? 'Xlsx' : 'Xls'
    );
    $spreadsheet = $reader->load($path);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    return $rows;
}

function normalizeKey($label) {
    $s = mb_strtolower(trim($label));
    $s = preg_replace('/[\s\-]+/', '_', $s);
    $s = preg_replace('/^\*+/', '', $s);
    return trim($s, '_');
}

function findColumn($headers, $candidates) {
    $norm = array_map('normalizeKey', $headers);
    foreach ($candidates as $c) {
        $k = normalizeKey($c);
        $idx = array_search($k, $norm);
        if ($idx !== false) {
            return $idx;
        }
    }
    return null;
}

function getRowVal($row, $idx) {
    if ($idx === null || !isset($row[$idx])) {
        return '';
    }
    $v = trim((string) $row[$idx]);
    return $v;
}

function importCustomers($db, $headers, $rows) {
    $userId = $_SESSION['user_id'] ?? null;
    $imported = 0;
    $skipped = 0;
    $errors = [];
    
    $idxCode = findColumn($headers, ['*Customer ID', 'Customer ID', 'customer_id']);
    $idxName = findColumn($headers, ['*Customer Name', 'Customer Name', 'customer_name']);
    $idxCategory = findColumn($headers, ['*Customer Category', 'Customer Category', 'customer_category']);
    $idxContact = findColumn($headers, ['*Contact Person', 'Contact Person', 'contact_person']);
    $idxPhone = findColumn($headers, ['*Phone', 'Phone', 'phone']);
    $idxEmail = findColumn($headers, ['*Email', 'Email', 'email']);
    $idxAddress = findColumn($headers, ['*Address', 'Address', 'address']);
    $idxDecision = findColumn($headers, ['Decision Maker', 'decision_maker']);
    
    $stmt = $db->prepare("
        INSERT INTO customers (
            customer_code, company_name, first_name, last_name, email, phone, mobile,
            address, province, customer_type, industry, status, assigned_to, notes
        ) VALUES (
            :customer_code, :company_name, :first_name, :last_name, :email, :phone, :mobile,
            :address, :province, :customer_type, :industry, 'active', :assigned_to, :notes
        )
    ");
    
    foreach ($rows as $i => $row) {
        $rowNum = $i + 2;
        $companyName = getRowVal($row, $idxName);
        $contactPerson = getRowVal($row, $idxContact);
        $phone = getRowVal($row, $idxPhone);
        $email = getRowVal($row, $idxEmail);
        $address = getRowVal($row, $idxAddress);
        $category = getRowVal($row, $idxCategory);
        $decisionMaker = getRowVal($row, $idxDecision);
        
        if ($companyName === '' && $contactPerson === '') {
            $skipped++;
            continue;
        }
        
        $first_name = $contactPerson !== '' ? $contactPerson : (mb_substr($companyName, 0, 50) ?: 'นำเข้า');
        $last_name = $companyName !== '' ? $companyName : 'จากไฟล์';
        if (mb_strlen($first_name) > 100) {
            $first_name = mb_substr($first_name, 0, 100);
        }
        if (mb_strlen($last_name) > 255) {
            $last_name = mb_substr($last_name, 0, 255);
        }
        
        $customerCode = getRowVal($row, $idxCode);
        if ($customerCode === '') {
            $customerCode = generateCode('CUST', $db, 'customers', 'customer_code');
        }
        
        $customer_type = 'individual';
        if ($category !== '' && preg_match('/(สหกรณ์|บริษัท|สมาคม|องค์กร)/u', $category)) {
            $customer_type = 'company';
        }
        
        $params = [
            'customer_code' => $customerCode,
            'company_name' => $companyName,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'mobile' => $phone,
            'address' => $address,
            'province' => '',
            'customer_type' => $customer_type,
            'industry' => $category,
            'assigned_to' => $userId,
            'notes' => $decisionMaker
        ];
        
        try {
            $stmt->execute($params);
            $imported++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique') !== false) {
                $skipped++;
                $errors[] = "แถว $rowNum: รหัสลูกค้าซ้ำ ($customerCode)";
            } else {
                $errors[] = "แถว $rowNum: " . $e->getMessage();
            }
        }
    }
    
    return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
}

function importProducts($db, $headers, $rows) {
    $imported = 0;
    $skipped = 0;
    $errors = [];
    
    $idxCode = findColumn($headers, ['*Product ID', 'Product ID', 'product_id']);
    $idxName = findColumn($headers, ['*Product Name', 'Product Name', 'product_name']);
    $idxCategory = findColumn($headers, ['*Category', 'Category', 'category']);
    $idxDesc = findColumn($headers, ['Description', 'description']);
    $idxQty = findColumn($headers, ['Quantity', 'quantity']);
    $idxUnitPrice = findColumn($headers, ['Unit price', 'Unit price', 'unit_price', 'Unit price']);
    $idxStatus = findColumn($headers, ['Status (Active/Inactive)', 'Status', 'status']);
    
    $stmt = $db->prepare("
        INSERT INTO products (product_code, product_name, description, category, unit_price, cost_price, stock_quantity, status)
        VALUES (:product_code, :product_name, :description, :category, :unit_price, :cost_price, :stock_quantity, :status)
    ");
    
    foreach ($rows as $i => $row) {
        $rowNum = $i + 2;
        $name = getRowVal($row, $idxName);
        if ($name === '') {
            $skipped++;
            continue;
        }
        
        $code = getRowVal($row, $idxCode);
        if ($code === '') {
            $code = generateCode('PROD', $db, 'products', 'product_code');
        }
        
        $category = getRowVal($row, $idxCategory);
        $description = getRowVal($row, $idxDesc);
        $qty = (int) str_replace(',', '', getRowVal($row, $idxQty));
        $unitPrice = getRowVal($row, $idxUnitPrice);
        $unitPrice = (float) str_replace([',', ' ', '฿'], '', $unitPrice);
        if ($unitPrice < 0) {
            $unitPrice = 0;
        }
        $statusRaw = getRowVal($row, $idxStatus);
        $status = 'active';
        if (stripos($statusRaw, 'inactive') !== false || $statusRaw === '0') {
            $status = 'inactive';
        }
        
        $params = [
            'product_code' => $code,
            'product_name' => mb_substr($name, 0, 255),
            'description' => $description,
            'category' => $category,
            'unit_price' => $unitPrice,
            'cost_price' => null,
            'stock_quantity' => $qty,
            'status' => $status
        ];
        
        try {
            $stmt->execute($params);
            $imported++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique') !== false) {
                $skipped++;
                $errors[] = "แถว $rowNum: รหัสสินค้าซ้ำ ($code)";
            } else {
                $errors[] = "แถว $rowNum: " . $e->getMessage();
            }
        }
    }
    
    return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
}
