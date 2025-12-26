<?php
/**
 * Helper Functions
 */

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Send error response
 */
function sendError($message, $statusCode = 400) {
    sendResponse(['error' => $message], $statusCode);
}

/**
 * Validate required fields
 */
function validateRequired($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing), 400);
    }
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate unique code
 */
function generateCode($prefix, $db, $table, $column = 'code') {
    $stmt = $db->query("SELECT COUNT(*) as count FROM $table WHERE $column LIKE '$prefix%'");
    $result = $stmt->fetch();
    $nextNum = ($result['count'] ?? 0) + 1;
    return $prefix . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
}

/**
 * Check authentication (simple session-based for now)
 */
function checkAuth() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        sendError('Unauthorized', 401);
    }
    return $_SESSION['user_id'];
}

/**
 * Get pagination parameters
 */
function getPaginationParams($input) {
    $page = isset($input['page']) ? max(1, intval($input['page'])) : 1;
    $limit = isset($input['limit']) ? min(100, max(1, intval($input['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

/**
 * Check if user is admin
 */
function checkAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        sendError('Unauthorized', 401);
    }
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        sendError('Access denied. Admin privileges required.', 403);
    }
    return $_SESSION['user_id'];
}

/**
 * Check if user has required role
 */
function requireRole($requiredRole) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        sendError('Unauthorized', 401);
    }
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        sendError('Access denied. Insufficient privileges.', 403);
    }
    return $_SESSION['user_id'];
}

/**
 * Check if user is admin or owner of resource
 */
function checkAdminOrOwner($resourceUserId) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        sendError('Unauthorized', 401);
    }
    if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] !== $resourceUserId)) {
        sendError('Access denied', 403);
    }
    return $_SESSION['user_id'];
}

/**
 * Check if user is admin (for non-API usage, returns boolean)
 */
function isAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
