<?php
/**
 * Database Configuration
 * PostgreSQL Connection for CRM System
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Read from environment variables (for Render.com) or use defaults (for local development)
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    
    private function __construct() {
        // Check if DATABASE_URL is set (Render.com format: postgresql://user:pass@host:port/dbname)
        if (getenv('DATABASE_URL')) {
            $url = parse_url(getenv('DATABASE_URL'));
            $this->host = $url['host'] ?? '';
            // On Render: use Internal URL as-is (dpg-xxx-a resolves on private network)
            // Off Render: append domain so short hostname resolves
            $onRender = getenv('RENDER') === 'true' || getenv('RENDER_SERVICE_NAME');
            if (!$onRender && preg_match('/^dpg-[a-z0-9]+-a$/', $this->host)) {
                $this->host .= '.oregon-postgres.render.com';
            }
            $this->port = $url['port'] ?? '5432';
            $this->dbname = ltrim($url['path'] ?? '', '/');
            $this->username = $url['user'] ?? '';
            $this->password = $url['pass'] ?? '';
        } else {
            // Fallback to individual environment variables (new DB: salesflow-db)
            $host = getenv('DB_HOST') ?: 'dpg-d6ai51i48b3s73bb4q5g-a.oregon-postgres.render.com';
            $onRender = getenv('RENDER') === 'true' || getenv('RENDER_SERVICE_NAME');
            if (!$onRender && preg_match('/^dpg-[a-z0-9]+-a$/', $host)) {
                $host .= '.oregon-postgres.render.com';
            }
            $this->host = $host;
            $this->port = getenv('DB_PORT') ?: '5432';
            $this->dbname = getenv('DB_NAME') ?: 'salesflow_0s9k';
            $this->username = getenv('DB_USER') ?: 'salesflow_user';
            $this->password = getenv('DB_PASSWORD') ?: 'fGlCkhwQLud9M7rPo3BglnwyRQaKtiYm';
        }
        
        $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname};sslmode=require;connect_timeout=30";
        $lastException = null;
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $this->connection = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $lastException = null;
                break;
            } catch (PDOException $e) {
                $lastException = $e;
                if ($attempt < $maxAttempts) {
                    sleep(2); // Wait for DB cold start (Render free tier spins down)
                }
            }
        }

        if ($lastException !== null) {
            $e = $lastException;
            error_log("Database Connection Error (after {$maxAttempts} attempts): " . $e->getMessage());
            
            // ตรวจสอบว่าเป็นปัญหา driver หรือไม่
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'could not find driver') !== false || 
                strpos($errorMessage, 'driver not found') !== false) {
                $helpMessage = "\n\n⚠️ PostgreSQL Driver ไม่พบ!\n";
                $helpMessage .= "กรุณาติดตั้ง pdo_pgsql extension\n";
                $helpMessage .= "ดูคำแนะนำที่: INSTALL_PGSQL_DRIVER.md\n";
                $helpMessage .= "หรือตรวจสอบที่: check_php_extensions.php\n";
                die("Connection failed: " . $errorMessage . $helpMessage);
            }
            
            die("Connection failed: " . $errorMessage);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Get database connection helper function
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
?>

