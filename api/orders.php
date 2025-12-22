<?php
/**
 * Orders/Sales API
 */

function handleOrders($method, $id, $input) {
    global $db;
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getOrder($id);
            } else {
                getOrders($input);
            }
            break;
            
        case 'POST':
            createOrder($input);
            break;
            
        case 'PUT':
            if ($id) {
                updateOrder($id, $input);
            } else {
                sendError('Order ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteOrder($id);
            } else {
                sendError('Order ID required', 400);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function getOrders($input) {
    global $db;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userId = $_SESSION['user_id'] ?? null;
    
    $pagination = getPaginationParams($input);
    $search = isset($input['search']) ? sanitizeInput($input['search']) : '';
    $status = isset($input['status']) ? sanitizeInput($input['status']) : '';
    $customer_id = isset($input['customer_id']) ? $input['customer_id'] : '';
    
    $where = [];
    $params = [];
    
    // Filter by user: แสดงเฉพาะคำสั่งซื้อที่ created_by เป็น user นี้ หรือ created_by เป็น NULL
    if ($userId) {
        $where[] = "(o.created_by = :user_id OR o.created_by IS NULL)";
        $params['user_id'] = $userId;
    }
    
    if (!empty($search)) {
        $where[] = "(o.order_number ILIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if (!empty($status)) {
        $where[] = "o.status = :status";
        $params['status'] = $status;
    }
    
    if (!empty($customer_id)) {
        $where[] = "o.customer_id = :customer_id";
        $params['customer_id'] = $customer_id;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM orders o $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get orders
    $sql = "SELECT o.*,
                   c.first_name || ' ' || c.last_name as customer_name,
                   c.company_name,
                   u.full_name as created_by_name
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN users u ON o.created_by = u.id
            $whereClause 
            ORDER BY o.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    
    $orders = $stmt->fetchAll();
    
    sendResponse([
        'data' => $orders,
        'pagination' => [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => (int)$total,
            'pages' => ceil($total / $pagination['limit'])
        ]
    ]);
}

function getOrder($id) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT o.*,
               c.first_name || ' ' || c.last_name as customer_name,
               c.company_name,
               u.full_name as created_by_name
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN users u ON o.created_by = u.id
        WHERE o.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        sendError('Order not found', 404);
    }
    
    // Get order items
    $itemsStmt = $db->prepare("
        SELECT oi.*, p.product_name, p.product_code 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = :id
    ");
    $itemsStmt->execute(['id' => $id]);
    $order['items'] = $itemsStmt->fetchAll();
    
    sendResponse(['data' => $order]);
}

function createOrder($input) {
    global $db;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userId = $_SESSION['user_id'] ?? null;
    
    validateRequired($input, ['customer_id', 'order_date', 'items']);
    
    if (empty($input['items']) || !is_array($input['items'])) {
        sendError('Order items are required', 400);
    }
    
    try {
        $db->beginTransaction();
        
        // Calculate totals
        $subtotal = 0;
        foreach ($input['items'] as $item) {
            if (empty($item['product_id']) || empty($item['quantity']) || empty($item['unit_price'])) {
                throw new Exception('Invalid order item data');
            }
            $itemTotal = floatval($item['unit_price']) * intval($item['quantity']);
            $discount = isset($item['discount']) ? floatval($item['discount']) : 0;
            $subtotal += ($itemTotal - $discount);
        }
        
        $tax = isset($input['tax']) ? floatval($input['tax']) : 0;
        $discount = isset($input['discount']) ? floatval($input['discount']) : 0;
        $total = $subtotal + $tax - $discount;
        
        // Create order
        $orderData = [
            'order_number' => generateCode('ORD', $db, 'orders', 'order_number'),
            'customer_id' => $input['customer_id'],
            'deal_id' => !empty($input['deal_id']) ? $input['deal_id'] : null,
            'order_date' => $input['order_date'],
            'delivery_date' => !empty($input['delivery_date']) ? $input['delivery_date'] : null,
            'status' => sanitizeInput($input['status'] ?? 'pending'),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total_amount' => $total,
            'currency' => sanitizeInput($input['currency'] ?? 'THB'),
            'payment_status' => sanitizeInput($input['payment_status'] ?? 'unpaid'),
            'payment_method' => sanitizeInput($input['payment_method'] ?? ''),
            'notes' => sanitizeInput($input['notes'] ?? ''),
            'created_by' => $userId // กำหนดให้ user ปัจจุบันเป็น default
        ];
        
        $fields = implode(', ', array_keys($orderData));
        $placeholders = ':' . implode(', :', array_keys($orderData));
        
        $sql = "INSERT INTO orders ($fields) VALUES ($placeholders) RETURNING id";
        $stmt = $db->prepare($sql);
        $stmt->execute($orderData);
        $orderId = $stmt->fetch()['id'];
        
        // Create order items
        foreach ($input['items'] as $item) {
            $itemTotal = floatval($item['unit_price']) * intval($item['quantity']);
            $itemDiscount = isset($item['discount']) ? floatval($item['discount']) : 0;
            
            $itemData = [
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'quantity' => intval($item['quantity']),
                'unit_price' => floatval($item['unit_price']),
                'discount' => $itemDiscount,
                'total_price' => $itemTotal - $itemDiscount
            ];
            
            $itemFields = implode(', ', array_keys($itemData));
            $itemPlaceholders = ':' . implode(', :', array_keys($itemData));
            
            $itemSql = "INSERT INTO order_items ($itemFields) VALUES ($itemPlaceholders)";
            $itemStmt = $db->prepare($itemSql);
            $itemStmt->execute($itemData);
        }
        
        $db->commit();
        
        // Get created order with items
        getOrder($orderId);
        
    } catch (Exception $e) {
        $db->rollBack();
        sendError('Failed to create order: ' . $e->getMessage(), 500);
    }
}

function updateOrder($id, $input) {
    global $db;
    
    // Check if order exists
    $checkStmt = $db->prepare("SELECT id FROM orders WHERE id = :id");
    $checkStmt->execute(['id' => $id]);
    if (!$checkStmt->fetch()) {
        sendError('Order not found', 404);
    }
    
    $allowedFields = ['customer_id', 'deal_id', 'order_date', 'delivery_date', 'status',
                     'tax', 'discount', 'currency', 'payment_status', 'payment_method', 'notes'];
    
    $updates = [];
    $params = ['id' => $id];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if (in_array($field, ['customer_id', 'deal_id'])) {
                $params[$field] = !empty($input[$field]) ? $input[$field] : null;
            } elseif (in_array($field, ['tax', 'discount'])) {
                $params[$field] = floatval($input[$field]);
            } elseif (in_array($field, ['order_date', 'delivery_date'])) {
                $params[$field] = !empty($input[$field]) ? $input[$field] : null;
            } else {
                $params[$field] = sanitizeInput($input[$field]);
            }
            $updates[] = "$field = :$field";
        }
    }
    
    // Recalculate total if subtotal, tax, or discount changed
    if (isset($input['tax']) || isset($input['discount'])) {
        $orderStmt = $db->prepare("SELECT subtotal FROM orders WHERE id = :id");
        $orderStmt->execute(['id' => $id]);
        $order = $orderStmt->fetch();
        
        $tax = isset($input['tax']) ? floatval($input['tax']) : $order['subtotal'] * 0.07;
        $discount = isset($input['discount']) ? floatval($input['discount']) : 0;
        $total = $order['subtotal'] + $tax - $discount;
        
        $updates[] = "total_amount = :total_amount";
        $params['total_amount'] = $total;
    }
    
    if (empty($updates)) {
        sendError('No fields to update', 400);
    }
    
    $sql = "UPDATE orders SET " . implode(', ', $updates) . " WHERE id = :id RETURNING *";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $order = $stmt->fetch();
    
    sendResponse(['data' => $order, 'message' => 'Order updated successfully']);
}

function deleteOrder($id) {
    global $db;
    
    $stmt = $db->prepare("DELETE FROM orders WHERE id = :id RETURNING id");
    $stmt->execute(['id' => $id]);
    
    if (!$stmt->fetch()) {
        sendError('Order not found', 404);
    }
    
    sendResponse(['message' => 'Order deleted successfully']);
}
?>

