<?php
require_once 'includes/auth.php';
requireAuth();

// Только для администратора
if ($_SESSION['username'] !== 'admin') {
    die('Access denied');
}

// Функция геокодирования (та же, что и в map.php)
function geocodeAddress($address) {
    if (empty($address)) {
        return null;
    }
    
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
                'User-Agent: IPTV Dashboard Geocoding Script',
                'Accept: application/json'
            ]
        ]
    ];
    
    $context = stream_context_create($options);
    $query_string = http_build_query($params);
    $response = @file_get_contents($url . '?' . $query_string, false, $context);
    
    if ($response === FALSE) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (empty($data)) {
        return null;
    }
    
    return [
        'lat' => $data[0]['lat'],
        'lon' => $data[0]['lon'],
        'display_name' => $data[0]['display_name']
    ];
}

// Получаем всех клиентов без координат
$query = "SELECT id, first_name, address FROM tv_clients WHERE (latitude IS NULL OR longitude IS NULL) AND address IS NOT NULL AND address != ''";
$clients = $conn->query($query)->fetchAll();

$total = count($clients);
$successful = 0;
$failed = 0;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Массовое геокодирование</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css'>
    <style>
        body { padding: 20px; }
        .progress { height: 30px; margin: 10px 0; }
        .log { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Массовое геокодирование адресов</h1>
        <p>Всего клиентов без координат: <strong>$total</strong></p>
        
        <div class='progress'>
            <div id='progress-bar' class='progress-bar progress-bar-striped progress-bar-animated' 
                 style='width: 0%'></div>
        </div>
        
        <div id='status' class='alert alert-info'>
            Начинаем геокодирование...
        </div>
        
        <div class='card'>
            <div class='card-header'>
                <h5 class='mb-0'>Лог геокодирования</h5>
            </div>
            <div class='card-body log' id='log'></div>
        </div>
    </div>
    
    <script>
        const clients = " . json_encode($clients) . ";
        let current = 0;
        const total = $total;
        
        function updateProgress() {
            const percent = Math.round((current / total) * 100);
            document.getElementById('progress-bar').style.width = percent + '%';
            document.getElementById('status').innerHTML = `Обработано: \${current}/\${total} (\${percent}%)`;
        }
        
        function addLog(message, type = 'info') {
            const log = document.getElementById('log');
            const entry = document.createElement('div');
            entry.className = 'alert alert-' + type + ' alert-dismissible fade show';
            entry.innerHTML = message + '<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>';
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }
        
        async function geocodeNext() {
            if (current >= total) {
                addLog('<strong>Геокодирование завершено!</strong>', 'success');
                document.getElementById('progress-bar').classList.remove('progress-bar-animated');
                document.getElementById('progress-bar').classList.add('bg-success');
                return;
            }
            
            const client = clients[current];
            current++;
            
            try {
                const response = await fetch('api/geocode_single.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        client_id: client.id,
                        address: client.address
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    addLog(`✓ ${client.first_name}: ${client.address} → ${result.display_name}`, 'success');
                } else {
                    addLog(`✗ ${client.first_name}: ${client.address} - ${result.error}`, 'danger');
                }
            } catch (error) {
                addLog(`✗ ${client.first_name}: ${client.address} - Ошибка сети`, 'danger');
            }
            
            updateProgress();
            
            // Задержка 1.5 секунды между запросами (соблюдение лимитов API)
            setTimeout(geocodeNext, 1500);
        }
        
        // Начинаем геокодирование
        geocodeNext();
    </script>
</body>
</html>";
?>