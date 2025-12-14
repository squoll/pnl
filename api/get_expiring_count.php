<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tv_clients WHERE DATE_ADD(subscription_date, INTERVAL months MONTH) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 MONTH)");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo json_encode(['count' => $result['count']]);
} catch(PDOException $e) {
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
}
?>