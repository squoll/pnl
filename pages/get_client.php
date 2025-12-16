<?php
// get_client.php - получение данных клиента по ID
include_once '../includes/auth.php';
require_once '../includes/i18n.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => t('api_error_not_authorized')]);
    exit();
}

if (isset($_GET['id'])) {
    $client_id = intval($_GET['id']);
    
    try {
        $stmt = $conn->prepare("
            SELECT c.*, p.operator 
            FROM tv_clients c 
            LEFT JOIN tv_providers p ON c.provider_id = p.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch();
        
        if ($client) {
            // Calculate expiration
            $subscription_date = new DateTime($client['subscription_date']);
            $expiration_date = clone $subscription_date;
            $expiration_date->modify("+{$client['months']} months");
            
            $now = new DateTime();
            $days_left = $now->diff($expiration_date)->days;
            if ($now > $expiration_date) {
                $days_left = -$days_left;
            }
            
            $client['expiration_date'] = $expiration_date->format('Y-m-d');
            $client['days_left'] = $days_left;
            
            echo json_encode(['success' => true, 'client' => $client]);
        } else {
            echo json_encode(['success' => false, 'error' => t('api_error_client_not_found')]);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => t('api_error_no_id')]);
}
?>