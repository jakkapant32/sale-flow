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
        
        $discount = isset($input['discount']) ? floatval($input['discount']) : 0;
        
        // คำนวณภาษี (ถ้ามี tax_rate หรือใช้ค่าที่กรอกมา)
        $taxRate = isset($input['tax_rate']) ? floatval($input['tax_rate']) : 7.00;
        $tax = isset($input['tax']) ? floatval($input['tax']) : 0;
        if ($tax == 0 && $taxRate > 0) {
            // ถ้าไม่ได้กรอก tax ให้คำนวณจาก subtotal - discount
            $tax = ($subtotal - $discount) * ($taxRate / 100);
        }
        
        $total = $subtotal + $tax - $discount;
        
        // คำนวณค่าคอมมิชชั่น (รับ commission_rate จาก input หรือใช้ default 7%)
        $commissionRate = isset($input['commission_rate']) ? floatval($input['commission_rate']) : 7.00;
        if ($commissionRate < 0 || $commissionRate > 100) {
            throw new Exception('Commission rate must be between 0 and 100');
        }
        $commissionAmount = $total * ($commissionRate / 100);
        
        // คำนวณรายได้สุทธิ = ยอดรวม - ค่าคอมมิชชั่น - ภาษี
        $netIncome = $total - $commissionAmount - $tax;
        
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
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'net_income' => $netIncome,
            'tax_rate' => $taxRate,
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
                     'tax', 'discount', 'tax_rate', 'commission_rate', 'currency', 
                     'payment_status', 'payment_method', 'notes'];
    
    $updates = [];
    $params = ['id' => $id];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if (in_array($field, ['customer_id', 'deal_id'])) {
                $params[$field] = !empty($input[$field]) ? $input[$field] : null;
            } elseif (in_array($field, ['tax', 'discount', 'tax_rate', 'commission_rate'])) {
                $params[$field] = floatval($input[$field]);
            } elseif (in_array($field, ['order_date', 'delivery_date'])) {
                $params[$field] = !empty($input[$field]) ? $input[$field] : null;
            } else {
                $params[$field] = sanitizeInput($input[$field]);
            }
            $updates[] = "$field = :$field";
        }
    }
    
    // คำนวณ total_amount, commission_amount และ net_income ใหม่ถ้ามีการเปลี่ยนแปลง
    $needRecalc = isset($input['tax']) || isset($input['discount']) || 
                  isset($input['commission_rate']) || isset($input['tax_rate']);
    
    if ($needRecalc) {
        $orderStmt = $db->prepare("SELECT subtotal, tax, discount, total_amount, commission_rate, tax_rate FROM orders WHERE id = :id");
        $orderStmt->execute(['id' => $id]);
        $order = $orderStmt->fetch();
        
        $subtotal = $order['subtotal'];
        $discount = isset($input['discount']) ? floatval($input['discount']) : $order['discount'];
        
        // คำนวณภาษี
        $taxRate = isset($input['tax_rate']) ? floatval($input['tax_rate']) : ($order['tax_rate'] ?? 7.00);
        $tax = isset($input['tax']) ? floatval($input['tax']) : $order['tax'];
        if (isset($input['tax_rate']) && $tax == 0) {
            $tax = ($subtotal - $discount) * ($taxRate / 100);
            $updates[] = "tax = :tax";
            $params['tax'] = $tax;
        }
        
        $total = $subtotal + $tax - $discount;
        
        // คำนวณ commission
        $commissionRate = isset($input['commission_rate']) ? floatval($input['commission_rate']) : ($order['commission_rate'] ?? 7.00);
        if ($commissionRate < 0 || $commissionRate > 100) {
            sendError('Commission rate must be between 0 and 100', 400);
        }
        $commissionAmount = $total * ($commissionRate / 100);
        
        // คำนวณรายได้สุทธิ
        $netIncome = $total - $commissionAmount - $tax;
        
        $updates[] = "total_amount = :total_amount";
        $updates[] = "commission_amount = :commission_amount";
        $updates[] = "net_income = :net_income";
        $params['total_amount'] = $total;
        $params['commission_amount'] = $commissionAmount;
        $params['net_income'] = $netIncome;
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

