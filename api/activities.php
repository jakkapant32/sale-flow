<?php
/**
 * Activities/Tasks API
 */

function handleActivities($method, $id, $input) {
    global $db;
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getActivity($id);
            } else {
                getActivities($input);
            }
            break;
            
        case 'POST':
            createActivity($input);
            break;
            
        case 'PUT':
            if ($id) {
                updateActivity($id, $input);
            } else {
                sendError('Activity ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteActivity($id);
            } else {
                sendError('Activity ID required', 400);
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function getActivities($input) {
    global $db;
    session_start();
    $userId = $_SESSION['user_id'] ?? null;
    
    $pagination = getPaginationParams($input);
    $search = isset($input['search']) ? sanitizeInput($input['search']) : '';
    $status = isset($input['status']) ? sanitizeInput($input['status']) : '';
    $activity_type = isset($input['activity_type']) ? sanitizeInput($input['activity_type']) : '';
    $customer_id = isset($input['customer_id']) ? $input['customer_id'] : '';
    $deal_id = isset($input['deal_id']) ? $input['deal_id'] : '';
    
    $where = [];
    $params = [];
    
    // Filter by user: แสดงเฉพาะกิจกรรมที่ assigned_to เป็น user นี้ หรือ assigned_to เป็น NULL
    if ($userId) {
        $where[] = "(a.assigned_to = :user_id OR a.assigned_to IS NULL)";
        $params['user_id'] = $userId;
    }
    
    if (!empty($search)) {
        $where[] = "(a.subject ILIKE :search OR a.description ILIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if (!empty($status)) {
        $where[] = "a.status = :status";
        $params['status'] = $status;
    }
    
    if (!empty($activity_type)) {
        $where[] = "a.activity_type = :activity_type";
        $params['activity_type'] = $activity_type;
    }
    
    if (!empty($customer_id)) {
        $where[] = "a.customer_id = :customer_id";
        $params['customer_id'] = $customer_id;
    }
    
    if (!empty($deal_id)) {
        $where[] = "a.deal_id = :deal_id";
        $params['deal_id'] = $deal_id;
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM activities a $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    // Get activities
    $sql = "SELECT a.*,
                   c.first_name || ' ' || c.last_name as customer_name,
                   d.deal_name,
                   u.full_name as assigned_to_name
            FROM activities a
            LEFT JOIN customers c ON a.customer_id = c.id
            LEFT JOIN deals d ON a.deal_id = d.id
            LEFT JOIN users u ON a.assigned_to = u.id
            $whereClause 
            ORDER BY a.due_date ASC, a.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    
    $activities = $stmt->fetchAll();
    
    sendResponse([
        'data' => $activities,
        'pagination' => [
            'page' => $pagination['page'],
            'limit' => $pagination['limit'],
            'total' => (int)$total,
            'pages' => ceil($total / $pagination['limit'])
        ]
    ]);
}

function getActivity($id) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT a.*,
               c.first_name || ' ' || c.last_name as customer_name,
               d.deal_name,
               u.full_name as assigned_to_name
        FROM activities a
        LEFT JOIN customers c ON a.customer_id = c.id
        LEFT JOIN deals d ON a.deal_id = d.id
        LEFT JOIN users u ON a.assigned_to = u.id
        WHERE a.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $activity = $stmt->fetch();
    
    if (!$activity) {
        sendError('Activity not found', 404);
    }
    
    sendResponse(['data' => $activity]);
}

function createActivity($input) {
    global $db;
    session_start();
    $userId = $_SESSION['user_id'] ?? null;
    
    validateRequired($input, ['activity_type', 'subject']);
    
    $data = [
        'activity_type' => sanitizeInput($input['activity_type']),
        'subject' => sanitizeInput($input['subject']),
        'description' => sanitizeInput($input['description'] ?? ''),
        'related_to' => sanitizeInput($input['related_to'] ?? ''),
        'related_id' => !empty($input['related_id']) ? $input['related_id'] : null,
        'customer_id' => !empty($input['customer_id']) ? $input['customer_id'] : null,
        'deal_id' => !empty($input['deal_id']) ? $input['deal_id'] : null,
        'assigned_to' => !empty($input['assigned_to']) ? $input['assigned_to'] : $userId, // กำหนดให้ user ปัจจุบันเป็น default
        'due_date' => !empty($input['due_date']) ? $input['due_date'] : null,
        'completion_date' => !empty($input['completion_date']) ? $input['completion_date'] : null,
        'status' => sanitizeInput($input['status'] ?? 'pending'),
        'priority' => sanitizeInput($input['priority'] ?? 'medium'),
        'location' => sanitizeInput($input['location'] ?? ''),
        'duration_minutes' => isset($input['duration_minutes']) ? intval($input['duration_minutes']) : null
    ];
    
    $fields = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO activities ($fields) VALUES ($placeholders) RETURNING *";
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    
    $activity = $stmt->fetch();
    
    sendResponse(['data' => $activity, 'message' => 'Activity created successfully'], 201);
}

function updateActivity($id, $input) {
    global $db;
    
    // Check if activity exists
    $checkStmt = $db->prepare("SELECT id FROM activities WHERE id = :id");
    $checkStmt->execute(['id' => $id]);
    if (!$checkStmt->fetch()) {
        sendError('Activity not found', 404);
    }
    
    $allowedFields = ['activity_type', 'subject', 'description', 'related_to', 'related_id',
                     'customer_id', 'deal_id', 'assigned_to', 'due_date', 'completion_date',
                     'status', 'priority', 'location', 'duration_minutes'];
    
    $updates = [];
    $params = ['id' => $id];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if (in_array($field, ['related_id', 'customer_id', 'deal_id', 'assigned_to'])) {
                $params[$field] = !empty($input[$field]) ? $input[$field] : null;
            } elseif (in_array($field, ['due_date', 'completion_date'])) {
                $params[$field] = !empty($input[$field]) ? $input[$field] : null;
            } elseif ($field === 'duration_minutes') {
                $params[$field] = !empty($input[$field]) ? intval($input[$field]) : null;
            } else {
                $params[$field] = sanitizeInput($input[$field]);
            }
            $updates[] = "$field = :$field";
        }
    }
    
    if (empty($updates)) {
        sendError('No fields to update', 400);
    }
    
    $sql = "UPDATE activities SET " . implode(', ', $updates) . " WHERE id = :id RETURNING *";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $activity = $stmt->fetch();
    
    sendResponse(['data' => $activity, 'message' => 'Activity updated successfully']);
}

function deleteActivity($id) {
    global $db;
    
    $stmt = $db->prepare("DELETE FROM activities WHERE id = :id RETURNING id");
    $stmt->execute(['id' => $id]);
    
    if (!$stmt->fetch()) {
        sendError('Activity not found', 404);
    }
    
    sendResponse(['message' => 'Activity deleted successfully']);
}
?>

