<?php
/**
 * Deals/Opportunities API
 */

function handleDeals($method, $id, $input) {
    global $db;
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getDeal($id);
            } else {
                getDeals($input);
            }
            break;
            
        case 'POST':
            createDeal($input);
            break;
            
        case 'PUT':
            if ($id) {
                updateDeal($id, $input);
            } else {
                sendError('Deal ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteDeal($id);
            } else {
                sendError('Deal ID required', 400);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function getDeals($input) {
    global $db;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userId = $_SESSION['user_id'] ?? null;
    
    $pagination = getPaginationParams($input);
    $search = isset($input['search']) ? sanitizeInput($input['search']) : '';
    $stage = isset($input['stage']) ? sanitizeInput($input['stage']) : '';
    $status = isset($input['status']) ? sanitizeInput($input['status']) : '';
    $customer_id = isset($input['customer_id']) ? $input['customer_id'] : '';
    
    $where = [];
    $params = [];
    
    // Filter by user: แสดงเฉพาะดีลที่ assigned_to เป็น user นี้ หรือ assigned_to เป็น NULL
    if ($userId) {
        $where[] = "(d.assigned_to = :user_id OR d.assigned_to IS NULL)";
        $params['user_id'] = $userId;
    }
    
    if (!empty($search)) {
        $where[] = "(d.deal_name ILIKE :search OR d.deal_code ILIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if (!empty($stage)) {
        $where[] = "d.stage = :stage";
        $params['stage'] = $stage;
    }
    
    if (!empty($status)) {
        $where[] = "d.status = :status";
        $params['status'] = $status;
    }
    
    if (!empty($customer_id)) {
        $where[] = "d.customer_id = :customer_id";
        $params['customer_id'] = $customer_id;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM deals d $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get deals
    $sql = "SELECT d.*, 
                   c.first_name || ' ' || c.last_name as customer_name,
                   c.company_name,
                   p.product_name,
                   u.full_name as assigned_to_name
            FROM deals d
            LEFT JOIN customers c ON d.customer_id = c.id
            LEFT JOIN products p ON d.product_id = p.id
            LEFT JOIN users u ON d.assigned_to = u.id
            $whereClause 
            ORDER BY d.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    
    $deals = $stmt->fetchAll();
    
    sendResponse([
        'data' => $deals,
        'pagination' => [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => (int)$total,
            'pages' => ceil($total / $pagination['limit'])
        ]
    ]);
}

function getDeal($id) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT d.*, 
               c.first_name || ' ' || c.last_name as customer_name,
               c.company_name,
               p.product_name,
               u.full_name as assigned_to_name
        FROM deals d
        LEFT JOIN customers c ON d.customer_id = c.id
        LEFT JOIN products p ON d.product_id = p.id
        LEFT JOIN users u ON d.assigned_to = u.id
        WHERE d.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $deal = $stmt->fetch();
    
    if (!$deal) {
        sendError('Deal not found', 404);
    }
    
    // Get related activities
    $activitiesStmt = $db->prepare("SELECT * FROM activities WHERE deal_id = :id ORDER BY created_at DESC");
    $activitiesStmt->execute(['id' => $id]);
    $deal['activities'] = $activitiesStmt->fetchAll();
    
    sendResponse(['data' => $deal]);
}

function createDeal($input) {
    global $db;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userId = $_SESSION['user_id'] ?? null;
    
    validateRequired($input, ['deal_name', 'customer_id', 'amount']);
    
    $data = [
        'deal_code' => generateCode('DEAL', $db, 'deals', 'deal_code'),
        'deal_name' => sanitizeInput($input['deal_name']),
        'customer_id' => $input['customer_id'],
        'product_id' => !empty($input['product_id']) ? $input['product_id'] : null,
        'amount' => floatval($input['amount']),
        'currency' => sanitizeInput($input['currency'] ?? 'THB'),
        'stage' => sanitizeInput($input['stage'] ?? 'prospecting'),
        'probability' => isset($input['probability']) ? intval($input['probability']) : 0,
        'expected_close_date' => !empty($input['expected_close_date']) ? $input['expected_close_date'] : null,
        'actual_close_date' => !empty($input['actual_close_date']) ? $input['actual_close_date'] : null,
        'status' => sanitizeInput($input['status'] ?? 'open'),
        'assigned_to' => !empty($input['assigned_to']) ? $input['assigned_to'] : $userId, // กำหนดให้ user ปัจจุบันเป็น default
        'description' => sanitizeInput($input['description'] ?? ''),
        'notes' => sanitizeInput($input['notes'] ?? '')
    ];
    
    // Validate probability range
    if ($data['probability'] < 0 || $data['probability'] > 100) {
        sendError('Probability must be between 0 and 100', 400);
    }
    
    $fields = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO deals ($fields) VALUES ($placeholders) RETURNING *";
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    
    $deal = $stmt->fetch();
    
    sendResponse(['data' => $deal, 'message' => 'Deal created successfully'], 201);
}

function updateDeal($id, $input) {
    global $db;
    
    // Check if deal exists
    $checkStmt = $db->prepare("SELECT id FROM deals WHERE id = :id");
    $checkStmt->execute(['id' => $id]);
    if (!$checkStmt->fetch()) {
        sendError('Deal not found', 404);
    }
    
    $allowedFields = ['deal_name', 'customer_id', 'product_id', 'amount', 'currency', 'stage',
                     'probability', 'expected_close_date', 'actual_close_date', 'status',
                     'assigned_to', 'description', 'notes'];
    
    $updates = [];
    $params = ['id' => $id];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if ($field === 'amount') {
                $params[$field] = floatval($input[$field]);
            } elseif ($field === 'probability') {
                $val = intval($input[$field]);
                if ($val < 0 || $val > 100) {
                    sendError('Probability must be between 0 and 100', 400);
                }
                $params[$field] = $val;
            } elseif (in_array($field, ['customer_id', 'product_id', 'assigned_to'])) {
                $params[$field] = !empty($input[$field]) ? $input[$field] : null;
            } elseif (in_array($field, ['expected_close_date', 'actual_close_date'])) {
                $params[$field] = !empty($input[$field]) ? $input[$field] : null;
            } else {
                $params[$field] = sanitizeInput($input[$field]);
            }
            $updates[] = "$field = :$field";
        }
    }
    
    if (empty($updates)) {
        sendError('No fields to update', 400);
    }
    
    $sql = "UPDATE deals SET " . implode(', ', $updates) . " WHERE id = :id RETURNING *";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $deal = $stmt->fetch();
    
    sendResponse(['data' => $deal, 'message' => 'Deal updated successfully']);
}

function deleteDeal($id) {
    global $db;
    
    $stmt = $db->prepare("DELETE FROM deals WHERE id = :id RETURNING id");
    $stmt->execute(['id' => $id]);
    
    if (!$stmt->fetch()) {
        sendError('Deal not found', 404);
    }
    
    sendResponse(['message' => 'Deal deleted successfully']);
}
?>

