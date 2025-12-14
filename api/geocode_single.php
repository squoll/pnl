<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$client_id = $input['client_id'] ?? 0;
$address = $input['address'] ?? '';

if (empty($address) || $client_id <= 0) {
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

function geocodeAddressOSM($address) {
    $url = "https://nominatim.openstreetmap.org/search";
    $params = [
        'q' => $address . ', Latvia',
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1,
        'accept-language' => 'ru'
    ];
    
    $options = [
        'http' => [
            'header' => [
                'User-Agent: IPTV Dashboard API',
                'Accept: application/json'
            ]
        ]
    ];
    
    $context = stream_context_create($options);
    $query_string = http_build_query($params);
    
    $response = @file_get_contents($url . '?' . $query_string, false, $context);
    
    if ($response === FALSE) {
        return ['error' => 'Geocoding service unavailable'];
    }
    
    $data = json_decode($response, true);
    
    if (empty($data)) {
        return ['error' => 'Address not found'];
    }
    
    return [
        'lat' => $data[0]['lat'],
        'lon' => $data[0]['lon'],
        'display_name' => $data[0]['display_name']
    ];
}

try {
    $result = geocodeAddressOSM($address);
    
    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => $result['error']]);
        exit();
    }
    
    // Сохраняем координаты в базу
    $stmt = $conn->prepare("UPDATE tv_clients SET latitude = ?, longitude = ? WHERE id = ?");
    $stmt->execute([$result['lat'], $result['lon'], $client_id]);
    
    echo json_encode([
        'success' => true,
        'lat' => $result['lat'],
        'lon' => $result['lon'],
        'display_name' => $result['display_name']
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>