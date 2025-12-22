<?php
/**
 * Products API
 */

function handleProducts($method, $id, $input) {
    global $db;
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getProduct($id);
            } else {
                getProducts($input);
            }
            break;
            
        case 'POST':
            createProduct($input);
            break;
            
        case 'PUT':
            if ($id) {
                updateProduct($id, $input);
            } else {
                sendError('Product ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteProduct($id);
            } else {
                sendError('Product ID required', 400);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function getProducts($input) {
    global $db;
    
    $pagination = getPaginationParams($input);
    $search = isset($input['search']) ? sanitizeInput($input['search']) : '';
    $category = isset($input['category']) ? sanitizeInput($input['category']) : '';
    $status = isset($input['status']) ? sanitizeInput($input['status']) : '';
    
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(product_name ILIKE :search OR product_code ILIKE :search OR description ILIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if (!empty($category)) {
        $where[] = "category = :category";
        $params['category'] = $category;
    }
    
    if (!empty($status)) {
        $where[] = "status = :status";
        $params['status'] = $status;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM products $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get products
    $sql = "SELECT * FROM products $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll();
    
    sendResponse([
        'data' => $products,
        'pagination' => [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => (int)$total,
            'pages' => ceil($total / $pagination['limit'])
        ]
    ]);
}

function getProduct($id) {
    global $db;
    
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        sendError('Product not found', 404);
    }
    
    sendResponse(['data' => $product]);
}

function createProduct($input) {
    global $db;
    
    validateRequired($input, ['product_code', 'product_name', 'unit_price']);
    
    $data = [
        'product_code' => sanitizeInput($input['product_code']),
        'product_name' => sanitizeInput($input['product_name']),
        'description' => sanitizeInput($input['description'] ?? ''),
        'category' => sanitizeInput($input['category'] ?? ''),
        'unit_price' => floatval($input['unit_price']),
        'cost_price' => isset($input['cost_price']) ? floatval($input['cost_price']) : null,
        'stock_quantity' => isset($input['stock_quantity']) ? intval($input['stock_quantity']) : 0,
        'status' => sanitizeInput($input['status'] ?? 'active')
    ];
    
    $fields = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO products ($fields) VALUES ($placeholders) RETURNING *";
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    
    $product = $stmt->fetch();
    
    sendResponse(['data' => $product, 'message' => 'Product created successfully'], 201);
}

function updateProduct($id, $input) {
    global $db;
    
    // Check if product exists
    $checkStmt = $db->prepare("SELECT id FROM products WHERE id = :id");
    $checkStmt->execute(['id' => $id]);
    if (!$checkStmt->fetch()) {
        sendError('Product not found', 404);
    }
    
    $allowedFields = ['product_code', 'product_name', 'description', 'category', 'unit_price',
                     'cost_price', 'stock_quantity', 'status'];
    
    $updates = [];
    $params = ['id' => $id];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if (in_array($field, ['unit_price', 'cost_price'])) {
                $params[$field] = floatval($input[$field]);
            } elseif ($field === 'stock_quantity') {
                $params[$field] = intval($input[$field]);
            } else {
                $params[$field] = sanitizeInput($input[$field]);
            }
            $updates[] = "$field = :$field";
        }
    }
    
    if (empty($updates)) {
        sendError('No fields to update', 400);
    }
    
    $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = :id RETURNING *";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $product = $stmt->fetch();
    
    sendResponse(['data' => $product, 'message' => 'Product updated successfully']);
}

function deleteProduct($id) {
    global $db;
    
    $stmt = $db->prepare("DELETE FROM products WHERE id = :id RETURNING id");
    $stmt->execute(['id' => $id]);
    
    if (!$stmt->fetch()) {
        sendError('Product not found', 404);
    }
    
    sendResponse(['message' => 'Product deleted successfully']);
}
?>

