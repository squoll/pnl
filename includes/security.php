<?php
/**
 * Security functions for IPTV Dashboard
 * Защита от bruteforce, rate limiting, IP blocking
 */

class Security {
    private $conn;
    private $max_attempts = 5; // Максимум попыток входа
    private $lockout_time = 900; // 15 минут блокировки
    private $attempt_window = 300; // 5 минут окно для подсчета попыток
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->createSecurityTables();
    }
    
    /**
     * Создание таблиц безопасности
     */
    private function createSecurityTables() {
        try {
            // Таблица для отслеживания попыток входа
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    username VARCHAR(100),
                    attempt_time DATETIME NOT NULL,
                    success TINYINT(1) DEFAULT 0,
                    user_agent TEXT,
                    INDEX(ip_address),
                    INDEX(attempt_time)
                )
            ");
            
            // Таблица заблокированных IP
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS blocked_ips (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL UNIQUE,
                    blocked_at DATETIME NOT NULL,
                    blocked_until DATETIME NOT NULL,
                    reason VARCHAR(255),
                    attempts_count INT DEFAULT 0,
                    INDEX(ip_address),
                    INDEX(blocked_until)
                )
            ");
            
            // Проверяем, существует ли старая структура таблицы security_logs
            $result = $this->conn->query("SHOW TABLES LIKE 'security_logs'");
            if ($result->rowCount() > 0) {
                // Проверяем структуру таблицы
                $columns = $this->conn->query("SHOW COLUMNS FROM security_logs")->fetchAll(PDO::FETCH_ASSOC);
                $has_event_type = false;
                
                foreach ($columns as $column) {
                    if ($column['Field'] === 'event_type') {
                        $has_event_type = true;
                        break;
                    }
                }
                
                // Если нет поля event_type, пересоздаём таблицу
                if (!$has_event_type) {
                    // Сохраняем старые данные во временную таблицу
                    $this->conn->exec("RENAME TABLE security_logs TO security_logs_old");
                    
                    // Создаём новую таблицу с правильной структурой
                    $this->conn->exec("
                        CREATE TABLE security_logs (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            event_type VARCHAR(50) NOT NULL,
                            ip_address VARCHAR(45),
                            username VARCHAR(100),
                            description TEXT,
                            created_at DATETIME NOT NULL,
                            severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
                            INDEX(event_type),
                            INDEX(created_at),
                            INDEX(severity)
                        )
                    ");
                    
                    // Удаляем старую таблицу (т.к. в ней были битые данные)
                    $this->conn->exec("DROP TABLE IF EXISTS security_logs_old");
                }
            } else {
                // Таблица для логов безопасности
                $this->conn->exec("
                    CREATE TABLE IF NOT EXISTS security_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        event_type VARCHAR(50) NOT NULL,
                        ip_address VARCHAR(45),
                        username VARCHAR(100),
                        description TEXT,
                        created_at DATETIME NOT NULL,
                        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
                        INDEX(event_type),
                        INDEX(created_at),
                        INDEX(severity)
                    )
                ");
            }
        } catch(PDOException $e) {
            error_log("Security tables creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Проверка IP на блокировку
     */
    public function isIpBlocked($ip) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM blocked_ips 
                WHERE ip_address = ? AND blocked_until > NOW()
            ");
            $stmt->execute([$ip]);
            $blocked = $stmt->fetch();
            
            if ($blocked) {
                $this->logSecurityEvent('ip_blocked_attempt', $ip, null, 
                    "Попытка доступа с заблокированного IP. Блокировка до: " . $blocked['blocked_until'], 
                    'medium');
                return true;
            }
            
            // Очистка истекших блокировок
            $this->conn->exec("DELETE FROM blocked_ips WHERE blocked_until < NOW()");
            
            return false;
        } catch(PDOException $e) {
            error_log("IP block check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка количества попыток входа
     */
    public function checkLoginAttempts($ip, $username = null) {
        try {
            // Подсчет неудачных попыток за последние N минут
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE ip_address = ? 
                AND success = 0 
                AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$ip, $this->attempt_window]);
            $result = $stmt->fetch();
            
            if ($result['attempts'] >= $this->max_attempts) {
                $this->blockIp($ip, "Превышен лимит попыток входа: {$result['attempts']}");
                return false;
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Login attempts check error: " . $e->getMessage());
            return true; // В случае ошибки не блокируем
        }
    }
    
    /**
     * Регистрация попытки входа
     */
    public function logLoginAttempt($ip, $username, $success) {
        try {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt = $this->conn->prepare("
                INSERT INTO login_attempts (ip_address, username, attempt_time, success, user_agent)
                VALUES (?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([$ip, $username, $success ? 1 : 0, $user_agent]);
            
            // Очистка старых записей (старше 24 часов)
            $this->conn->exec("
                DELETE FROM login_attempts 
                WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            if (!$success) {
                $this->logSecurityEvent('login_failed', $ip, $username, 
                    "Неудачная попытка входа", 'low');
            }
        } catch(PDOException $e) {
            error_log("Login attempt logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Блокировка IP адреса
     */
    private function blockIp($ip, $reason) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO blocked_ips (ip_address, blocked_at, blocked_until, reason, attempts_count)
                VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND), ?, 1)
                ON DUPLICATE KEY UPDATE 
                    blocked_at = NOW(),
                    blocked_until = DATE_ADD(NOW(), INTERVAL ? SECOND),
                    attempts_count = attempts_count + 1,
                    reason = ?
            ");
            $stmt->execute([$ip, $this->lockout_time, $reason, $this->lockout_time, $reason]);
            
            $this->logSecurityEvent('ip_blocked', $ip, null, 
                "IP заблокирован: $reason", 'high');
        } catch(PDOException $e) {
            error_log("IP blocking error: " . $e->getMessage());
        }
    }
    
    /**
     * Логирование событий безопасности
     */
    public function logSecurityEvent($event_type, $ip, $username, $description, $severity = 'low') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO security_logs (event_type, ip_address, username, description, created_at, severity)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([$event_type, $ip, $username, $description, $severity]);
            
            // Очистка старых логов (старше 30 дней)
            $this->conn->exec("
                DELETE FROM security_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
        } catch(PDOException $e) {
            error_log("Security event logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Получение информации о клиенте
     */
    public function getClientIp() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Проверка на подозрительную активность
     */
    public function detectSuspiciousActivity() {
        $suspicious = false;
        $ip = $this->getClientIp();
        
        // Проверка на SQL injection паттерны в URL
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $sql_patterns = [
            '/union.*select/i',
            '/select.*from/i',
            '/<script/i',
            '/javascript:/i',
            '/onclick=/i',
            '/onerror=/i'
        ];
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $request_uri)) {
                $this->logSecurityEvent('suspicious_request', $ip, null, 
                    "Подозрительный запрос обнаружен: $request_uri", 'critical');
                $suspicious = true;
                break;
            }
        }
        
        // Проверка User-Agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $bot_patterns = ['/bot/i', '/crawler/i', '/spider/i', '/scraper/i'];
        
        foreach ($bot_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                $this->logSecurityEvent('bot_detected', $ip, null, 
                    "Обнаружен бот: $user_agent", 'low');
                break;
            }
        }
        
        return $suspicious;
    }
    
    /**
     * Генерация CSRF токена
     */
    public function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Проверка CSRF токена
     */
    public function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Защита сессии от перехвата
     */
    public function secureSession() {
        // Регенерация ID сессии
        if (!isset($_SESSION['session_created'])) {
            $_SESSION['session_created'] = time();
        } elseif (time() - $_SESSION['session_created'] > 1800) {
            // Регенерация каждые 30 минут
            session_regenerate_id(true);
            $_SESSION['session_created'] = time();
        }
        
        // Проверка IP и User-Agent для предотвращения перехвата
        $current_ip = $this->getClientIp();
        $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (isset($_SESSION['session_ip']) && $_SESSION['session_ip'] !== $current_ip) {
            $this->logSecurityEvent('session_hijack_attempt', $current_ip, 
                $_SESSION['username'] ?? null, 
                "Попытка перехвата сессии - смена IP", 'critical');
            session_destroy();
            return false;
        }
        
        if (isset($_SESSION['session_ua']) && $_SESSION['session_ua'] !== $current_ua) {
            $this->logSecurityEvent('session_hijack_attempt', $current_ip, 
                $_SESSION['username'] ?? null, 
                "Попытка перехвата сессии - смена User-Agent", 'critical');
            session_destroy();
            return false;
        }
        
        $_SESSION['session_ip'] = $current_ip;
        $_SESSION['session_ua'] = $current_ua;
        
        return true;
    }
}
