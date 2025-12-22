<?php
/**
 * Authentication API
 */

function handleAuth($method, $id, $input) {
    global $db;
    $db = getDB();
    session_start();
    
    switch ($method) {
        case 'POST':
            if ($id === 'login') {
                login($input);
            } elseif ($id === 'logout') {
                logout();
            } elseif ($id === 'register') {
                register($input);
            } else {
                sendError('Invalid action');
            }
            break;
            
        case 'GET':
            if ($id === 'me') {
                getCurrentUser();
            } else {
                sendError('Invalid action');
            }
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function login($input) {
    global $db;
    
    validateRequired($input, ['username', 'password']);
    
    $username = sanitizeInput($input['username']);
    $password = $input['password'];
    
    $stmt = $db->prepare("SELECT id, username, email, password_hash, full_name, role, status FROM users WHERE username = :username OR email = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        sendError('Invalid username or password', 401);
    }
    
    if ($user['status'] !== 'active') {
        sendError('Account is not active', 403);
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    
    unset($user['password_hash']);
    
    sendResponse([
        'success' => true,
        'user' => $user,
        'message' => 'Login successful'
    ]);
}

function logout() {
    session_destroy();
    sendResponse(['success' => true, 'message' => 'Logged out successfully']);
}

function register($input) {
    global $db;
    
    validateRequired($input, ['username', 'email', 'password', 'full_name']);
    
    $username = sanitizeInput($input['username']);
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $full_name = sanitizeInput($input['full_name']);
    
    if (strlen($password) < 6) {
        sendError('Password must be at least 6 characters', 400);
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role) 
            VALUES (:username, :email, :password_hash, :full_name, 'user')
            RETURNING id, username, email, full_name, role
        ");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $password_hash,
            'full_name' => $full_name
        ]);
        
        $user = $stmt->fetch();
        
        sendResponse([
            'success' => true,
            'user' => $user,
            'message' => 'Registration successful'
        ], 201);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'unique') !== false) {
            sendError('Username or email already exists', 409);
        }
        sendError('Registration failed: ' . $e->getMessage(), 500);
    }
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        sendError('Not authenticated', 401);
    }
    
    global $db;
    $stmt = $db->prepare("SELECT id, username, email, full_name, role, status, created_at FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    sendResponse(['user' => $user]);
}
?>

