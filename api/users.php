<?php
/**
 * Users API
 */

function handleUsers($method, $id, $input) {
    global $db;
    $db = getDB();
    
    // ตรวจสอบว่าเป็น admin เท่านั้น (ยกเว้น GET me สำหรับดูข้อมูลตัวเอง)
    if ($id !== 'me') {
        checkAdmin();
    }
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getUser($id);
            } else {
                getUsers($input);
            }
            break;
            
        case 'POST':
            createUser($input);
            break;
            
        case 'PUT':
            if ($id) {
                updateUser($id, $input);
            } else {
                sendError('User ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteUser($id);
            } else {
                sendError('User ID required', 400);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function getUsers($input) {
    global $db;
    
    $pagination = getPaginationParams($input);
    $search = isset($input['search']) ? sanitizeInput($input['search']) : '';
    $status = isset($input['status']) ? sanitizeInput($input['status']) : '';
    $role = isset($input['role']) ? sanitizeInput($input['role']) : '';
    
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(username ILIKE :search OR email ILIKE :search OR full_name ILIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if (!empty($status)) {
        $where[] = "status = :status";
        $params['status'] = $status;
    }
    
    if (!empty($role)) {
        $where[] = "role = :role";
        $params['role'] = $role;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM users $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get users (without password)
    $sql = "SELECT id, username, email, full_name, role, status, created_at, updated_at 
            FROM users 
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    
    $users = $stmt->fetchAll();
    
    sendResponse([
        'data' => $users,
        'pagination' => [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => (int)$total,
            'pages' => ceil($total / $pagination['limit'])
        ]
    ]);
}

function getUser($id) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT id, username, email, full_name, role, status, created_at, updated_at 
        FROM users 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    sendResponse(['data' => $user]);
}

function createUser($input) {
    global $db;
    
    validateRequired($input, ['username', 'email', 'password', 'full_name']);
    
    $username = sanitizeInput($input['username']);
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $full_name = sanitizeInput($input['full_name']);
    $role = sanitizeInput($input['role'] ?? 'user');
    $status = sanitizeInput($input['status'] ?? 'active');
    
    if (strlen($password) < 6) {
        sendError('Password must be at least 6 characters', 400);
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role, status) 
            VALUES (:username, :email, :password_hash, :full_name, :role, :status)
            RETURNING id, username, email, full_name, role, status
        ");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash,
            'full_name' => $full_name,
            'role' => $role,
            'status' => $status
        ]);
        
        $user = $stmt->fetch();
        
        sendResponse(['data' => $user, 'message' => 'User created successfully'], 201);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'unique') !== false) {
            sendError('Username or email already exists', 409);
        }
        sendError('Failed to create user: ' . $e->getMessage(), 500);
    }
}

function updateUser($id, $input) {
    global $db;
    
    // Check if user exists
    $checkStmt = $db->prepare("SELECT id FROM users WHERE id = :id");
    $checkStmt->execute(['id' => $id]);
    if (!$checkStmt->fetch()) {
        sendError('User not found', 404);
    }
    
    $allowedFields = ['username', 'email', 'full_name', 'role', 'status'];
    $updates = [];
    $params = ['id' => $id];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = :$field";
            $params[$field] = sanitizeInput($input[$field]);
        }
    }
    
    // Handle password update separately
    if (isset($input['password'])) {
        if (strlen($input['password']) < 6) {
            sendError('Password must be at least 6 characters', 400);
        }
        $updates[] = "password_hash = :password_hash";
        $params['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
    }
    
    if (empty($updates)) {
        sendError('No fields to update', 400);
    }
    
    try {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id RETURNING id, username, email, full_name, role, status";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $user = $stmt->fetch();
        
        sendResponse(['data' => $user, 'message' => 'User updated successfully']);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'unique') !== false) {
            sendError('Username or email already exists', 409);
        }
        sendError('Failed to update user: ' . $e->getMessage(), 500);
    }
}

function deleteUser($id) {
    global $db;
    
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id RETURNING id");
    $stmt->execute(['id' => $id]);
    
    if (!$stmt->fetch()) {
        sendError('User not found', 404);
    }
    
    sendResponse(['message' => 'User deleted successfully']);
}
?>

