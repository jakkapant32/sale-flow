<?php
/**
 * Dashboard API - Statistics and Reports
 */

function handleDashboard($method, $input) {
    global $db;
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            getDashboardStats($input);
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
}

function getDashboardStats($input) {
    global $db;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userId = $_SESSION['user_id'] ?? null;
    
    $period = isset($input['period']) ? sanitizeInput($input['period']) : 'year';
    
    // Calculate date range based on period (for TO_CHAR, quarter handled separately)
    $dateFormat = match($period) {
        'day' => 'YYYY-MM-DD',
        'week' => 'YYYY-"W"IW',
        'month' => 'YYYY-MM',
        'year' => 'YYYY',
        default => 'YYYY'
    };
    
    // Set limit based on period
    $limit = match($period) {
        'day' => 30,
        'week' => 12,
        'month' => 12,
        'quarter' => 8,
        'year' => 10,
        default => 10
    };
    
    // Get summary statistics - Filter by user
    $stats = [];
    
    // Total customers
    if ($userId) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM customers WHERE status = 'active' AND (assigned_to = :user_id OR assigned_to IS NULL)");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    }
    $stats['total_customers'] = (int)$stmt->fetch()['total'];
    
    // Total deals
    if ($userId) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM deals WHERE status = 'open' AND (assigned_to = :user_id OR assigned_to IS NULL)");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as total FROM deals WHERE status = 'open'");
    }
    $stats['total_deals'] = (int)$stmt->fetch()['total'];
    
    // Total deal value
    if ($userId) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM deals WHERE status = 'open' AND (assigned_to = :user_id OR assigned_to IS NULL)");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM deals WHERE status = 'open'");
    }
    $stats['total_deal_value'] = floatval($stmt->fetch()['total']);
    
    // Won deals value
    if ($userId) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM deals WHERE status = 'closed' AND stage = 'won' AND (assigned_to = :user_id OR assigned_to IS NULL)");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM deals WHERE status = 'closed' AND stage = 'won'");
    }
    $stats['won_deals_value'] = floatval($stmt->fetch()['total']);
    
    // Total orders
    if ($userId) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE (created_by = :user_id OR created_by IS NULL)");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    }
    $stats['total_orders'] = (int)$stmt->fetch()['total'];
    
    // Total revenue
    if ($userId) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid' AND (created_by = :user_id OR created_by IS NULL)");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'");
    }
    $stats['total_revenue'] = floatval($stmt->fetch()['total']);
    
    // Pending activities
    if ($userId) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM activities WHERE status = 'pending' AND (assigned_to = :user_id OR assigned_to IS NULL)");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as total FROM activities WHERE status = 'pending'");
    }
    $stats['pending_activities'] = (int)$stmt->fetch()['total'];
    
    // Overdue activities
    if ($userId) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM activities WHERE status = 'pending' AND due_date < CURRENT_DATE AND (assigned_to = :user_id OR assigned_to IS NULL)");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as total FROM activities WHERE status = 'pending' AND due_date < CURRENT_DATE");
    }
    $stats['overdue_activities'] = (int)$stmt->fetch()['total'];
    
    // Deals by stage
    $stmt = $db->query("
        SELECT stage, COUNT(*) as count, COALESCE(SUM(amount), 0) as value 
        FROM deals 
        WHERE status = 'open' 
        GROUP BY stage 
        ORDER BY count DESC
    ");
    $stats['deals_by_stage'] = $stmt->fetchAll();
    
    // Recent deals
    if ($userId) {
        $stmt = $db->prepare("
            SELECT d.*, c.first_name || ' ' || c.last_name as customer_name 
            FROM deals d 
            LEFT JOIN customers c ON d.customer_id = c.id 
            WHERE (d.assigned_to = :user_id OR d.assigned_to IS NULL)
            ORDER BY d.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("
            SELECT d.*, c.first_name || ' ' || c.last_name as customer_name 
            FROM deals d 
            LEFT JOIN customers c ON d.customer_id = c.id 
            ORDER BY d.created_at DESC 
            LIMIT 10
        ");
    }
    $stats['recent_deals'] = $stmt->fetchAll();
    
    // Recent activities
    if ($userId) {
        $stmt = $db->prepare("
            SELECT a.*, c.first_name || ' ' || c.last_name as customer_name 
            FROM activities a 
            LEFT JOIN customers c ON a.customer_id = c.id 
            WHERE (a.assigned_to = :user_id OR a.assigned_to IS NULL)
            ORDER BY a.due_date ASC, a.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("
            SELECT a.*, c.first_name || ' ' || c.last_name as customer_name 
            FROM activities a 
            LEFT JOIN customers c ON a.customer_id = c.id 
            ORDER BY a.due_date ASC, a.created_at DESC 
            LIMIT 10
        ");
    }
    $stats['upcoming_activities'] = $stmt->fetchAll();
    
    // Revenue by period
    if ($period === 'quarter') {
        // Special handling for quarter
        $stmt = $db->prepare("
            SELECT 
                TO_CHAR(order_date, 'YYYY') || '-Q' || TO_CHAR(order_date, 'Q') as period,
                COALESCE(SUM(total_amount), 0) as revenue,
                COUNT(*) as orders
            FROM orders 
            WHERE payment_status = 'paid'
            GROUP BY TO_CHAR(order_date, 'YYYY'), TO_CHAR(order_date, 'Q')
            ORDER BY TO_CHAR(order_date, 'YYYY') DESC, TO_CHAR(order_date, 'Q') DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("
            SELECT TO_CHAR(order_date, :date_format) as period, 
                   COALESCE(SUM(total_amount), 0) as revenue,
                   COUNT(*) as orders
            FROM orders 
            WHERE payment_status = 'paid'
            GROUP BY TO_CHAR(order_date, :date_format)
            ORDER BY period DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':date_format', $dateFormat, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
    }
    $stats['revenue_trend'] = $stmt->fetchAll();
    
    // Top customers by revenue
    $stmt = $db->query("
        SELECT c.id, c.first_name || ' ' || c.last_name as customer_name, c.company_name,
               COALESCE(SUM(o.total_amount), 0) as total_revenue,
               COUNT(o.id) as order_count
        FROM customers c
        LEFT JOIN orders o ON c.id = o.customer_id AND o.payment_status = 'paid'
        GROUP BY c.id, c.first_name, c.last_name, c.company_name
        HAVING COALESCE(SUM(o.total_amount), 0) > 0
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    $stats['top_customers'] = $stmt->fetchAll();
    
    // Sales funnel
    if ($userId) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN stage = 'prospecting' THEN 1 END) as prospecting,
                COUNT(CASE WHEN stage = 'qualification' THEN 1 END) as qualification,
                COUNT(CASE WHEN stage = 'proposal' THEN 1 END) as proposal,
                COUNT(CASE WHEN stage = 'negotiation' THEN 1 END) as negotiation,
                COUNT(CASE WHEN stage = 'closed' THEN 1 END) as closed
            FROM deals
            WHERE status = 'open' AND (assigned_to = :user_id OR assigned_to IS NULL)
        ");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("
            SELECT 
                COUNT(CASE WHEN stage = 'prospecting' THEN 1 END) as prospecting,
                COUNT(CASE WHEN stage = 'qualification' THEN 1 END) as qualification,
                COUNT(CASE WHEN stage = 'proposal' THEN 1 END) as proposal,
                COUNT(CASE WHEN stage = 'negotiation' THEN 1 END) as negotiation,
                COUNT(CASE WHEN stage = 'closed' THEN 1 END) as closed
            FROM deals
            WHERE status = 'open'
        ");
    }
    $stats['sales_funnel'] = $stmt->fetch();
    
    sendResponse(['data' => $stats]);
}
?>

