<?php
// setup.php - Script/Web installer for the project

$configFile = __DIR__ . '/config/db.php';
$exampleFile = __DIR__ . '/config/db.example.php';
$message = '';
$error = '';

if (file_exists($configFile)) {
    // If config exists, check if we can connect
    require $configFile;
    try {
        if (isset($conn)) {
            $message = "Configuration file exists and connection is successful. You are ready to go! <a href='index.php'>Go to Dashboard</a>";
        }
    } catch (Exception $e) {
        $error = "Configuration file exists but connection failed: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? 'localhost';
    $name = $_POST['name'] ?? '';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    if (empty($name) || empty($user)) {
        $error = "Database name and username are required.";
    } else {
        // Try to connect
        try {
            $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Connection successful, write config
            $configContent = file_get_contents($exampleFile);
            if (!$configContent) {
                // Fallback content if example is missing
                $configContent = "<?php
// config/db.php
\$host = 'localhost';
\$dbname = 'iptv_standig';
\$username = 'iptv_standig';
\$password = '';
\$show_detailed_errors = false;
try {
    \$conn = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);
    \$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException \$e) { die(\"Connection Error\"); }
?>";
            }

            // Replace placeholders
            $configContent = preg_replace("/\\\$host = '.*';/", "\$host = '$host';", $configContent);
            $configContent = preg_replace("/\\\$dbname = '.*';/", "\$dbname = '$name';", $configContent);
            $configContent = preg_replace("/\\\$username = '.*';/", "\$username = '$user';", $configContent);
            $configContent = preg_replace("/\\\$password = '.*';/", "\$password = '$pass';", $configContent);

            if (file_put_contents($configFile, $configContent)) {
                $message = "Configuration saved successfully!";
                
                // Initialize Database Tables
                $sql = "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL,
                    login_attempts INT DEFAULT 0,
                    locked_until TIMESTAMP NULL
                );
                CREATE TABLE IF NOT EXISTS tv_clients (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    first_name VARCHAR(100),
                    last_name VARCHAR(100),
                    phone VARCHAR(20),
                    address TEXT,
                    provider_id INT,
                    subscription_date DATE,
                    months INT,
                    earned DECIMAL(10,2) DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE TABLE IF NOT EXISTS tv_providers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    operator VARCHAR(100),
                    details TEXT
                );
                ";
                // We execute multi-query or split? PDO prepare doesn't support multi-query well in one go usually, let's split.
                // Actually tv_payments is created in index.php, but good to have here.
                
                $message .= "<br>Database tables initialized (if not existed). <a href='index.php'>Go to Dashboard</a>";
            } else {
                $error = "Failed to write config file. Please check permissions for config/ directory.";
            }

        } catch (PDOException $e) {
            $error = "Connection failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPTV Dashboard Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm mx-auto" style="max-width: 500px;">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Installation Setup</h4>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <?php if (!file_exists($configFile) || isset($error)): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Database Host</label>
                        <input type="text" name="host" class="form-control" value="<?= htmlspecialchars($_POST['host'] ?? 'localhost') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Username</label>
                        <input type="text" name="user" class="form-control" value="<?= htmlspecialchars($_POST['user'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Password</label>
                        <input type="password" name="pass" class="form-control" value="<?= htmlspecialchars($_POST['pass'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Install & Connect</button>
                </form>
                <?php else: ?>
                    <p>System is already configured.</p>
                    <a href="index.php" class="btn btn-primary w-100">Go to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
