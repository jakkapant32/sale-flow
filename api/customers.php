<?php
/**
 * Customers API
 */

function handleCustomers($method, $id, $input) {
    global $db;
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getCustomer($id);
            } else {
                getCustomers($input);
            }
            break;
            
        case 'POST':
            createCustomer($input);
            break;
            
        case 'PUT':
            if ($id) {
                updateCustomer($id, $input);
            } else {
                sendError('Customer ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteCustomer($id);
            } else {
                sendError('Customer ID required', 400);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function getCustomers($input) {
    global $db;
    if (session_status() === PHP_SESSION_NONE) {
        if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    }
    $userId = $_SESSION['user_id'] ?? null;
    
    $pagination = getPaginationParams($input);
    // Don't sanitize search to preserve special characters and Thai text
    $search = isset($input['search']) ? trim($input['search']) : '';
    $status = isset($input['status']) ? sanitizeInput($input['status']) : '';
    
    $where = [];
    $params = [];
    
    // Filter by user: แสดงเฉพาะลูกค้าที่ assigned_to เป็น user นี้ หรือ assigned_to เป็น NULL (ลูกค้าทั่วไป)
    if ($userId) {
        $where[] = "(c.assigned_to = :user_id OR c.assigned_to IS NULL)";
        $params['user_id'] = $userId;
    }
    
    if (!empty($search)) {
        // Search in multiple fields including customer_code and concatenated full name
        $where[] = "(c.customer_code ILIKE :search OR c.first_name ILIKE :search OR c.last_name ILIKE :search OR (c.first_name || ' ' || c.last_name) ILIKE :search OR c.company_name ILIKE :search OR c.email ILIKE :search OR c.phone ILIKE :search OR c.mobile ILIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if (!empty($status)) {
        $where[] = "c.status = :status";
        $params['status'] = $status;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM customers c $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get customers
    $sql = "SELECT c.*, u.full_name as assigned_to_name 
            FROM customers c 
            LEFT JOIN users u ON c.assigned_to = u.id 
            $whereClause 
            ORDER BY c.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    
    $customers = $stmt->fetchAll();
    
    sendResponse([
        'data' => $customers,
        'pagination' => [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => (int)$total,
            'pages' => ceil($total / $pagination['limit'])
        ]
    ]);
}

function getCustomer($id) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT c.*, u.full_name as assigned_to_name 
        FROM customers c 
        LEFT JOIN users u ON c.assigned_to = u.id 
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        sendError('Customer not found', 404);
    }
    
    // Get related deals
    $dealsStmt = $db->prepare("SELECT * FROM deals WHERE customer_id = :id ORDER BY created_at DESC");
    $dealsStmt->execute(['id' => $id]);
    $customer['deals'] = $dealsStmt->fetchAll();
    
    // Get recent activities
    $activitiesStmt = $db->prepare("SELECT * FROM activities WHERE customer_id = :id ORDER BY created_at DESC LIMIT 10");
    $activitiesStmt->execute(['id' => $id]);
    $customer['activities'] = $activitiesStmt->fetchAll();
    
    sendResponse(['data' => $customer]);
}

function createCustomer($input) {
    global $db;
    if (session_status() === PHP_SESSION_NONE) {
        if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    }
    $userId = $_SESSION['user_id'] ?? null;
    
    validateRequired($input, ['first_name', 'last_name']);
    
    $data = [
        'customer_code' => generateCode('CUST', $db, 'customers', 'customer_code'),
        'company_name' => sanitizeInput($input['company_name'] ?? ''),
        'first_name' => sanitizeInput($input['first_name']),
        'last_name' => sanitizeInput($input['last_name']),
        'email' => sanitizeInput($input['email'] ?? ''),
        'phone' => sanitizeInput($input['phone'] ?? ''),
        'mobile' => sanitizeInput($input['mobile'] ?? ''),
        'address' => sanitizeInput($input['address'] ?? ''),
        'city' => sanitizeInput($input['city'] ?? ''),
        'province' => sanitizeInput($input['province'] ?? ''),
        'postal_code' => sanitizeInput($input['postal_code'] ?? ''),
        'country' => sanitizeInput($input['country'] ?? 'Thailand'),
        'customer_type' => sanitizeInput($input['customer_type'] ?? 'individual'),
        'industry' => sanitizeInput($input['industry'] ?? ''),
        'website' => sanitizeInput($input['website'] ?? ''),
        'status' => sanitizeInput($input['status'] ?? 'active'),
        'source' => sanitizeInput($input['source'] ?? ''),
        'assigned_to' => !empty($input['assigned_to']) ? $input['assigned_to'] : $userId, // กำหนดให้ user ปัจจุบันเป็น default
        'notes' => sanitizeInput($input['notes'] ?? '')
    ];
    
    $fields = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO customers ($fields) VALUES ($placeholders) RETURNING *";
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    
    $customer = $stmt->fetch();
    
    sendResponse(['data' => $customer, 'message' => 'Customer created successfully'], 201);
}

function updateCustomer($id, $input) {
    global $db;
    
    // Check if customer exists
    $checkStmt = $db->prepare("SELECT id FROM customers WHERE id = :id");
    $checkStmt->execute(['id' => $id]);
    if (!$checkStmt->fetch()) {
        sendError('Customer not found', 404);
    }
    
    $allowedFields = ['company_name', 'first_name', 'last_name', 'email', 'phone', 'mobile', 
                     'address', 'city', 'province', 'postal_code', 'country', 'customer_type',
                     'industry', 'website', 'status', 'source', 'assigned_to', 'notes'];
    
    $updates = [];
    $params = ['id' => $id];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = :$field";
            $params[$field] = sanitizeInput($input[$field]);
        }
    }
    
    if (empty($updates)) {
        sendError('No fields to update', 400);
    }
    
    $sql = "UPDATE customers SET " . implode(', ', $updates) . " WHERE id = :id RETURNING *";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $customer = $stmt->fetch();
    
    sendResponse(['data' => $customer, 'message' => 'Customer updated successfully']);
}

function deleteCustomer($id) {
    global $db;
    
    $stmt = $db->prepare("DELETE FROM customers WHERE id = :id RETURNING id");
    $stmt->execute(['id' => $id]);
    
    if (!$stmt->fetch()) {
        sendError('Customer not found', 404);
    }
    
    sendResponse(['message' => 'Customer deleted successfully']);
}
?>

