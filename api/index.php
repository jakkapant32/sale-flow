<?php
/**
 * Main API Router
 * Handles all API requests
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Start session once at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];

// Handle rewritten URLs (from .htaccess) or direct URLs
if (isset($_GET['path'])) {
    $path = $_GET['path'];
} else {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // Remove /api/index.php or /api/ from path
    $path = preg_replace('#^/api(/index\.php)?/?#', '', $path);
}

$path = trim($path, '/');
$segments = explode('/', $path);

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = [];
}
$input = array_merge($input, $_GET, $_POST);

// Route handling
try {
    $resource = $segments[0] ?? 'dashboard';
    $id = $segments[1] ?? null;
    $action = $segments[2] ?? null;
    
    switch ($resource) {
        case 'auth':
            require_once __DIR__ . '/auth.php';
            handleAuth($method, $id, $input);
            break;
            
        case 'customers':
            require_once __DIR__ . '/customers.php';
            handleCustomers($method, $id, $input);
            break;
            
        case 'deals':
            require_once __DIR__ . '/deals.php';
            handleDeals($method, $id, $input);
            break;
            
        case 'activities':
            require_once __DIR__ . '/activities.php';
            handleActivities($method, $id, $input);
            break;
            
        case 'products':
            require_once __DIR__ . '/products.php';
            handleProducts($method, $id, $input);
            break;
            
        case 'orders':
            require_once __DIR__ . '/orders.php';
            handleOrders($method, $id, $input);
            break;
            
        case 'dashboard':
            require_once __DIR__ . '/dashboard.php';
            handleDashboard($method, $input);
            break;
            
        case 'users':
            require_once __DIR__ . '/users.php';
            handleUsers($method, $id, $input);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Resource not found']);
            break;
    }
} catch (PDOException $e) {
    // จัดการ database connection errors
    $errorMessage = $e->getMessage();
    $statusCode = 500;
    
    if (strpos($errorMessage, 'could not find driver') !== false || 
        strpos($errorMessage, 'driver not found') !== false) {
        $errorMessage = 'PostgreSQL Driver ไม่พบ กรุณาติดตั้ง pdo_pgsql extension';
        $statusCode = 503; // Service Unavailable
    }
    
    http_response_code($statusCode);
    echo json_encode([
        'error' => $errorMessage,
        'type' => 'database_error',
        'help' => 'ดูคำแนะนำที่: INSTALL_PGSQL_DRIVER.md หรือตรวจสอบที่: check_php_extensions.php'
    ], JSON_UNESCAPED_UNICODE);
    error_log("API Database Error: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("API Error: " . $e->getMessage());
}
?>

