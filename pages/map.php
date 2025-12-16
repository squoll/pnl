<?php
// map.php - Карта клиентов
include_once '../includes/auth.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

include '../includes/header.php';

// Получаем клиентов с адресами
$query = "SELECT id, first_name, phone, address, subscription_date, months FROM tv_clients WHERE address IS NOT NULL AND address != ''";
$clients = $conn->query($query)->fetchAll();

// Функция для геокодирования адреса
function geocodeAddress($address) {
    global $conn;
    
    if (empty($address)) {
        return null;
    }
    
    // Кэширование геокодирования в сессии
    $cache_key = 'geocode_' . md5($address);
    
    if (isset($_SESSION[$cache_key])) {
        return $_SESSION[$cache_key];
    }
    
    // Используем Nominatim OpenStreetMap
    $url = "https://nominatim.openstreetmap.org/search";
    $params = [
        'q' => $address . ', Latvia', // Добавляем Латвию по умолчанию
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1,
        'accept-language' => isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ru'
    ];
    
    $options = [
        'http' => [
            'header' => [
                'User-Agent: IPTV Dashboard (contact@standigital.lv)',
                'Accept: application/json'
            ]
        ]
    ];
    
    $context = stream_context_create($options);
    $query_string = http_build_query($params);
    $response = @file_get_contents($url . '?' . $query_string, false, $context);
    
    if ($response === FALSE) {
        error_log("Geocoding failed for address: $address");
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (empty($data)) {
        return null;
    }
    
    $result = [
        'lat' => $data[0]['lat'],
        'lon' => $data[0]['lon'],
        'display_name' => $data[0]['display_name']
    ];
    
    // Кэшируем результат на 24 часа
    $_SESSION[$cache_key] = $result;
    
    return $result;
}

// Обработка AJAX запроса для геокодирования
if (isset($_GET['ajax']) && $_GET['ajax'] == 'geocode') {
    header('Content-Type: application/json');
    
    $address = $_GET['address'] ?? '';
    $client_id = $_GET['client_id'] ?? 0;
    
    if (empty($address)) {
        echo json_encode(['error' => 'Address is required']);
        exit;
    }
    
    $result = geocodeAddress($address);
    
    if ($result) {
        // Сохраняем координаты в базу данных
        try {
            $stmt = $conn->prepare("UPDATE tv_clients SET latitude = ?, longitude = ? WHERE id = ?");
            $stmt->execute([$result['lat'], $result['lon'], $client_id]);
        } catch (PDOException $e) {
            error_log("Failed to save coordinates: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'lat' => $result['lat'],
            'lon' => $result['lon'],
            'display_name' => $result['display_name']
        ]);
    } else {
        echo json_encode(['error' => 'Geocoding failed']);
    }
    exit;
}

// Обработка AJAX запроса для обновления адреса и координат
if (isset($_POST['ajax']) && $_POST['ajax'] == 'update_address') {
    global $conn;
    header('Content-Type: application/json');
    
    $client_id = $_POST['client_id'] ?? 0;
    $address = $_POST['address'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    
    if (empty($client_id) || empty($address) || empty($latitude) || empty($longitude)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE tv_clients SET address = ?, latitude = ?, longitude = ? WHERE id = ?");
        $stmt->execute([$address, $latitude, $longitude, $client_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Получаем клиентов с координатами
$query_with_coords = "SELECT c.*, p.operator,
                     (SELECT COUNT(*) FROM tv_clients WHERE provider_id = p.id) as total_clients,
                     (SELECT SUM(paid) FROM tv_clients WHERE provider_id = p.id) as total_paid
                     FROM tv_clients c 
                     LEFT JOIN tv_providers p ON c.provider_id = p.id
                     WHERE c.address IS NOT NULL AND c.address != ''
                     ORDER BY c.first_name";
$clients_with_data = $conn->query($query_with_coords)->fetchAll();

// Получаем ID клиента из параметра URL для центрирования карты
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
$selected_client = null;

// Массив для хранения клиентов с координатами
$clients_for_map = [];

foreach ($clients_with_data as $client) {
    // Если координаты уже есть в базе, используем их
    if ($client['latitude'] && $client['longitude']) {
        $client_data = [
            'id' => $client['id'],
            'name' => $client['first_name'],
            'phone' => $client['phone'],
            'address' => $client['address'],
            'subscription_date' => $client['subscription_date'],
            'months' => $client['months'],
            'lat' => $client['latitude'],
            'lon' => $client['longitude'],
            'provider' => $client['operator'] ?? 'Без провайдера',
            'paid' => $client['paid'] ?? 0,
            'total_clients' => $client['total_clients'] ?? 0,
            'total_paid' => $client['total_paid'] ?? 0
        ];
        $clients_for_map[] = $client_data;
        
        // Сохраняем выбранного клиента для центрирования карты
        if ($selected_client_id && $client['id'] == $selected_client_id) {
            $selected_client = $client_data;
        }
    }
}

// Получаем клиентов с адресами но без координат (failed geocoding)
$failed_clients = [];
foreach ($clients_with_data as $client) {
    if (!$client['latitude'] || !$client['longitude']) {
        $failed_clients[] = [
            'id' => $client['id'],
            'name' => $client['first_name'],
            'phone' => $client['phone'],
            'address' => $client['address']
        ];
    }
}

// Подсчет статистики
$clients_with_address = count($clients);
$clients_geocoded = count($clients_for_map);
$failed_count = count($failed_clients);
$geocoding_percentage = $clients_with_address > 0 ? round(($clients_geocoded / $clients_with_address) * 100) : 0;
?>

<!-- Дополнительные стили для карты -->
<style>
    .map-container {
        height: 600px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        position: relative;
    }
    
    #map {
        height: 100%;
        width: 100%;
    }
    
    .stats-card, .legend {
        background: var(--dk-dark-bg);
        color: var(--dk-gray-300);
        border: 1px solid var(--dk-gray-700);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .stats-value {
        font-size: 2rem;
        font-weight: bold;
        color: #007bff;
    }
    
    .geocode-progress {
        height: 10px;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        margin-right: 10px;
        border: 2px solid var(--dk-dark-bg);
        box-shadow: 0 0 5px rgba(0,0,0,0.2);
        opacity: 1 !important;
    }
    
    .marker-popup {
        min-width: 250px;
    }
    
    .marker-popup h6 {
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
        margin-bottom: 10px;
    }
    
    .marker-popup p {
        margin-bottom: 5px;
        font-size: 0.9rem;
    }
    
    .marker-popup .badge {
        font-size: 0.8rem;
    }
</style>
<style>
    /* ... существующие стили ... */
    
    .custom-marker div {
        opacity: 1 !important;
    }
    
    .cluster-marker div {
        opacity: 1 !important;
        background-color: #ffc107 !important;
    }
    .marker-cluster,
    .marker-cluster div {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    .marker-cluster-small,
    .marker-cluster-medium,
    .marker-cluster-large {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    .marker-cluster, .marker-cluster div {
        opacity: 1 !important;
    }
    
    /* Убедимся, что цвета видны на карте */
    .leaflet-marker-icon {
        opacity: 1 !important;
    }
</style>

<!-- Подключаем библиотеки Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />

<!-- Разметка HTML с переводами -->
<div class="p-4">
    <div class="welcome mb-4">
        <div class="content rounded-3 p-3">
            <h1 class="fs-3"><?= htmlspecialchars(t('map_title')) ?></h1>
            <p class="mb-0"><?= htmlspecialchars(t('map_subtitle')) ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <!-- Статистика -->
            <div class="stats-card">
                <h5><i class="uil uil-map-marker"></i> <?= htmlspecialchars(t('map_stats')) ?></h5>
                <div class="stats-value"><?= $clients_with_address ?></div>
                <p><?= htmlspecialchars(t('map_clients_with_address')) ?></p>
                
                <div class="stats-value"><?= $clients_geocoded ?></div>
                <p><?= htmlspecialchars(t('map_clients_geocoded')) ?></p>
                
                <div class="progress geocode-progress">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?= $geocoding_percentage ?>%">
                        <?= $geocoding_percentage ?>%
                    </div>
                </div>
            </div>

            <!-- Легенда -->
            <div class="legend">
                <h5><i class="uil uil-palette"></i> <?= htmlspecialchars(t('map_legend')) ?></h5>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #007bff;"></div>
                    <span><?= htmlspecialchars(t('map_active_clients')) ?></span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #28a745;"></div>
                    <span><?= htmlspecialchars(t('map_new_clients')) ?></span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #dc3545;"></div>
                    <span><?= htmlspecialchars(t('map_expiring_soon')) ?></span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #6c757d;"></div>
                    <span><?= htmlspecialchars(t('map_expired')) ?></span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: #ffc107; border-color: #333;"></div>
                    <span><?= htmlspecialchars(t('map_cluster')) ?></span>
                </div>
            </div>

            <!-- Управление -->
            <div class="card mt-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="uil uil-cog"></i> <?= htmlspecialchars(t('map_controls')) ?></h5>
                </div>
                <div class="card-body">
                    <button id="geocode-all" class="btn btn-success w-100 mb-2">
                        <i class="uil uil-location-point"></i> <?= htmlspecialchars(t('map_geocode_all')) ?>
                    </button>
                    <button id="center-map" class="btn btn-info w-100 mb-2">
                        <i class="uil uil-crosshair"></i> <?= htmlspecialchars(t('map_center')) ?>
                    </button>
                    <button id="show-all" class="btn btn-warning w-100">
                        <i class="uil uil-expand-arrows"></i> <?= htmlspecialchars(t('map_show_all')) ?>
                    </button>
                </div>
            </div>

            <!-- Информация -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="uil uil-info-circle"></i> <?= htmlspecialchars(t('map_info_title')) ?></h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><small>• <?= htmlspecialchars(t('map_info_click')) ?></small></p>
                    <p class="mb-1"><small>• <?= htmlspecialchars(t('map_info_scroll')) ?></small></p>
                    <p class="mb-1"><small>• <?= htmlspecialchars(t('map_info_drag')) ?></small></p>
                    <p class="mb-0"><small>• <?= htmlspecialchars(t('map_info_source')) ?></small></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="map-container">
                <!-- Панель прогресса -->
                <div id="geocoding-progress" class="alert alert-info d-none">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars(t('map_js_geocoding')) ?></span>
                        <span id="progress-text">0/0</span>
                    </div>
                    <div class="progress mt-2">
                        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             style="width: 0%"></div>
                    </div>
                </div>

                <div id="map"></div>
            </div>
        </div>
    </div>
    
    <!-- Failed Geocoding Section -->
    <?php if ($failed_count > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="uil uil-exclamation-triangle"></i> 
                        <?= htmlspecialchars(t('failed_geocoding_title')) ?> (<?= $failed_count ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?= htmlspecialchars(t('failed_geocoding_subtitle')) ?></p>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= htmlspecialchars(t('name')) ?></th>
                                    <th><?= htmlspecialchars(t('phone')) ?></th>
                                    <th><?= htmlspecialchars(t('address')) ?></th>
                                    <th><?= htmlspecialchars(t('actions')) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($failed_clients as $fc): ?>
                                <tr id="failed-row-<?= $fc['id'] ?>">
                                    <td><?= htmlspecialchars($fc['name']) ?></td>
                                    <td><?= htmlspecialchars($fc['phone']) ?></td>
                                    <td>
                                        <div class="address-wrapper" style="position: relative;">
                                            <input type="text" 
                                                   class="form-control failed-address-input" 
                                                   data-client-id="<?= $fc['id'] ?>"
                                                   value="<?= htmlspecialchars($fc['address']) ?>"
                                                   autocomplete="off"
                                                   placeholder="Start typing...">
                                            <input type="hidden" class="failed-lat" data-client-id="<?= $fc['id'] ?>">
                                            <input type="hidden" class="failed-lon" data-client-id="<?= $fc['id'] ?>">
                                            <div class="address-suggestions failed-suggestions-<?= $fc['id'] ?>" 
                                                 style="position: absolute; background: var(--dk-dark-bg); border: 1px solid var(--dk-gray-700); border-radius: 0 0 5px 5px; width: 100%; z-index: 1000; max-height: 200px; overflow-y: auto; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.3);"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-success update-failed-btn" data-client-id="<?= $fc['id'] ?>">
                                            <i class="uil uil-check"></i> <?= htmlspecialchars(t('update_coordinates')) ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<!-- Скрипты -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script>
    // Инициализация карты (центр на Риге)
    const map = L.map('map').setView([56.9496, 24.1052], 10);
    
    // Добавляем слой карты
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Добавляем слой кластеризации
    const markers = L.markerClusterGroup({
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        spiderfyOnMaxZoom: true,
        removeOutsideVisibleBounds: false,
        animate: true,
        animateAddingMarkers: false,
        disableClusteringAtZoom: 17,
        maxClusterRadius: 80,
        iconCreateFunction: function(cluster) {
            const count = cluster.getChildCount();
            let size = 'medium';
            
            if (count < 10) {
                size = 'small';
            } else if (count > 100) {
                size = 'large';
            }
            
            // ФИКС: Убрана прозрачность, добавлен белый фон
            return L.divIcon({
                html: '<div style="background-color: #ffc107; color: #000; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 3px solid var(--dk-dark-bg); box-shadow: 0 0 10px rgba(0,0,0,0.3);">' + count + '</div>',
                className: 'cluster-marker',
                iconSize: L.point(40, 40)
            });
        }
    });
    
    // Данные клиентов из PHP
    const clients = <?= json_encode($clients_for_map, JSON_UNESCAPED_UNICODE) ?>;
    const allClients = <?= json_encode($clients, JSON_UNESCAPED_UNICODE) ?>;
    const selectedClient = <?= json_encode($selected_client, JSON_UNESCAPED_UNICODE) ?>;
    
    // Функция для определения цвета маркера
    function getMarkerColor(client) {
        const endDate = new Date(client.subscription_date);
        endDate.setMonth(endDate.getMonth() + parseInt(client.months));
        const now = new Date();
        const daysLeft = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));
        
        if (daysLeft < 0) {
            return '#6c757d'; // Серый - просроченные
        } else if (daysLeft <= 30) {
            return '#dc3545'; // Красный - скоро истекает
        }
        
        const startDate = new Date(client.subscription_date);
        const daysSinceStart = Math.ceil((now - startDate) / (1000 * 60 * 60 * 24));
        
        if (daysSinceStart <= 30) {
            return '#28a745'; // Зеленый - новые
        }
        
        return '#007bff'; // Синий - активные
    }
    
    // Функция для получения иконки маркера
    function getMarkerIcon(color) {
        // ФИКС: Убрана прозрачность, добавлен solid цвет
        return L.divIcon({
            html: `<div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid var(--dk-dark-bg); box-shadow: 0 0 10px rgba(0,0,0,0.3);"></div>`,
            className: 'custom-marker',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });
    }
    
    // Функция для форматирования даты
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU');
    }
    
    // Функция для расчета дней до окончания
    function getDaysLeft(subscriptionDate, months) {
        const endDate = new Date(subscriptionDate);
        endDate.setMonth(endDate.getMonth() + parseInt(months));
        const now = new Date();
        const daysLeft = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));
        return daysLeft;
    }
    
    // Добавляем маркеры для клиентов с координатами
    let selectedMarker = null;
    
    clients.forEach((client) => {
        const daysLeft = getDaysLeft(client.subscription_date, client.months);
        const color = getMarkerColor(client);
        
        const popupContent = `
            <div class="marker-popup">
                <h6>${client.name}</h6>
                <p><strong>📞 <?= t('map_popup_phone') ?>:</strong> ${client.phone}</p>
                <p><strong>📍 <?= t('map_popup_address') ?>:</strong> ${client.address}</p>
                <p><strong>📅 <?= t('map_popup_sub_start') ?>:</strong> ${formatDate(client.subscription_date)}</p>
                <p><strong>⏳ <?= t('map_popup_term') ?>:</strong> ${client.months} <?= t('months_suffix') ?></p>
                <p><strong>🔄 <?= t('map_popup_days_left') ?>:</strong> 
                    <span class="badge ${daysLeft > 30 ? 'bg-success' : (daysLeft > 0 ? 'bg-warning' : 'bg-danger')}">
                        ${daysLeft > 0 ? daysLeft + ' <?= t('days') ?>' : '<?= t('map_marker_expired') ?>'}
                    </span>
                </p>
                <p><strong>💰 <?= t('map_popup_paid') ?>:</strong> €${parseFloat(client.paid || 0).toFixed(2)}</p>
                <p><strong>📡 <?= t('map_popup_provider') ?>:</strong> ${client.provider}</p>
                <hr>
                <a href="tv_clients.php?search=${encodeURIComponent(client.name)}" class="btn btn-sm btn-primary w-100">
                    <i class="uil uil-user"></i> <?= t('map_popup_go_to_client') ?>
                </a>
            </div>
        `;
        
        const marker = L.marker([parseFloat(client.lat), parseFloat(client.lon)], {
            icon: getMarkerIcon(color),
            title: client.name
        }).bindPopup(popupContent);
        
        markers.addLayer(marker);
        
        // Сохраняем маркер выбранного клиента
        if (selectedClient && client.id === selectedClient.id) {
            selectedMarker = marker;
        }
    });
    
    // Добавляем кластеры на карту
    map.addLayer(markers);
    
    // Если выбран клиент, центрируем карту на нем и открываем popup
    if (selectedClient && selectedMarker) {
        // Ждем загрузки карты
        setTimeout(() => {
            map.setView([parseFloat(selectedClient.lat), parseFloat(selectedClient.lon)], 16);
            selectedMarker.openPopup();
        }, 500);
    }
    
    // Добавляем контроль масштаба
    L.control.scale().addTo(map);
    
    // Функция для геокодирования адреса
    async function geocodeAddress(clientId, address) {
        try {
            const response = await fetch(`map.php?ajax=geocode&address=${encodeURIComponent(address)}&client_id=${clientId}`);
            const data = await response.json();
            
            if (data.success) {
                return {
                    success: true,
                    lat: parseFloat(data.lat),
                    lon: parseFloat(data.lon),
                    display_name: data.display_name
                };
            } else {
                return {
                    success: false,
                    error: data.error || 'Unknown error'
                };
            }
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    // Геокодирование всех адресов
    document.getElementById('geocode-all').addEventListener('click', async function() {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> <?= t('map_js_processing') ?>';
        
        // Показываем прогресс
        const progressPanel = document.getElementById('geocoding-progress');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        
        progressPanel.classList.remove('d-none');
        
        // Фильтруем клиентов без координат
        const clientsToGeocode = allClients.filter(client => !client.latitude || !client.longitude);
        const total = clientsToGeocode.length;
        let completed = 0;
        let successful = 0;
        let failed = 0;
        
        progressText.textContent = `0/${total}`;
        progressBar.style.width = '0%';
        
        for (const client of clientsToGeocode) {
            const result = await geocodeAddress(client.id, client.address);
            
            completed++;
            
            if (result.success) {
                successful++;
                
                // Добавляем новый маркер на карту
                const daysLeft = getDaysLeft(client.subscription_date, client.months);
                const color = getMarkerColor(client);
                
                const popupContent = `
                    <div class="marker-popup">
                        <h6>${client.first_name}</h6>
                        <p><strong>📞 <?= t('map_popup_phone') ?>:</strong> ${client.phone}</p>
                        <p><strong>📍 <?= t('map_popup_address') ?>:</strong> ${client.address}</p>
                        <p><strong>🌍 <?= t('map_popup_geocoded') ?>:</strong> ${result.display_name}</p>
                        <p><strong>📅 <?= t('map_popup_sub_start') ?>:</strong> ${formatDate(client.subscription_date)}</p>
                        <p><strong>🔄 <?= t('map_popup_days_left') ?>:</strong> 
                            <span class="badge ${daysLeft > 30 ? 'bg-success' : (daysLeft > 0 ? 'bg-warning' : 'bg-danger')}">
                                ${daysLeft > 0 ? daysLeft + ' <?= t('days') ?>' : '<?= t('map_marker_expired') ?>'}
                            </span>
                        </p>
                        <hr>
                        <a href="tv_clients.php?search=${encodeURIComponent(client.first_name)}" class="btn btn-sm btn-primary w-100">
                            <i class="uil uil-user"></i> <?= t('map_popup_go_to_client') ?>
                        </a>
                    </div>
                `;
                
                const marker = L.marker([result.lat, result.lon], {
                    icon: getMarkerIcon(color),
                    title: client.first_name
                }).bindPopup(popupContent);
                
                markers.addLayer(marker);
            } else {
                failed++;
                console.error(`Failed to geocode ${client.address}: ${result.error}`);
            }
            
            // Обновляем прогресс
            const progressPercent = Math.round((completed / total) * 100);
            progressBar.style.width = `${progressPercent}%`;
            progressText.textContent = `${completed}/${total} (${successful} <?= t('success') ?>, ${failed} <?= t('error') ?>)`;
            
            // Небольшая задержка для соблюдения лимитов API
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
        
        // Обновляем карту
        map.fitBounds(markers.getBounds());
        
        // Скрываем прогресс
        setTimeout(() => {
            progressPanel.classList.add('d-none');
        }, 3000);
        
        // Восстанавливаем кнопку
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        // Показываем результат
        alert(`<?= t('map_js_completed') ?>\nSuccessful: ${successful}\nErrors: ${failed}\nTotal processed: ${completed}`);
    });
    
    // Центрирование карты
    document.getElementById('center-map').addEventListener('click', function() {
        if (markers.getLayers().length > 0) {
            map.fitBounds(markers.getBounds());
        } else {
            map.setView([56.9496, 24.1052], 10);
        }
    });
    
    // Показать всех клиентов
    document.getElementById('show-all').addEventListener('click', function() {
        if (markers.getLayers().length > 0) {
            map.fitBounds(markers.getBounds());
        }
    });
    
    // Сохранение позиции карты в localStorage
    map.on('moveend', function() {
        const center = map.getCenter();
        const zoom = map.getZoom();
        localStorage.setItem('mapCenter', JSON.stringify([center.lat, center.lng]));
        localStorage.setItem('mapZoom', zoom);
    });
    
    // Восстановление позиции карты
    const savedCenter = localStorage.getItem('mapCenter');
    const savedZoom = localStorage.getItem('mapZoom');
    
    if (savedCenter && savedZoom) {
        const center = JSON.parse(savedCenter);
        map.setView(center, parseInt(savedZoom));
    }
    
    // Автокомплит для failed addresses
    document.querySelectorAll('.failed-address-input').forEach(input => {
        const clientId = input.dataset.clientId;
        const suggestionsBox = document.querySelector(`.failed-suggestions-${clientId}`);
        const latInput = document.querySelector(`.failed-lat[data-client-id="${clientId}"]`);
        const lonInput = document.querySelector(`.failed-lon[data-client-id="${clientId}"]`);
        let debounceTimer;
        
        input.addEventListener('input', function() {
            const query = this.value;
            
            // Сбрасываем координаты
            if (latInput.value) {
                latInput.value = '';
                lonInput.value = '';
            }
            
            clearTimeout(debounceTimer);
            suggestionsBox.style.display = 'none';
            
            if (query.length < 3) return;
            
            debounceTimer = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Latvia')}&limit=5&addressdetails=1&accept-language=ru`, {
                    headers: { 'Accept-Language': 'ru' }
                })
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'address-suggestion-item';
                            div.style.padding = '10px';
                            div.style.cursor = 'pointer';
                            div.style.borderBottom = '1px solid var(--dk-gray-700)';
                            div.textContent = item.display_name;
                            div.addEventListener('click', function() {
                                input.value = item.display_name;
                                latInput.value = item.lat;
                                lonInput.value = item.lon;
                                suggestionsBox.style.display = 'none';
                            });
                            div.addEventListener('mouseenter', function() {
                                this.style.backgroundColor = 'var(--dk-gray-800)';
                            });
                            div.addEventListener('mouseleave', function() {
                                this.style.backgroundColor = '';
                            });
                            suggestionsBox.appendChild(div);
                        });
                        suggestionsBox.style.display = 'block';
                    }
                })
                .catch(err => console.error('Geocoding error:', err));
            }, 500);
        });
    });
    
    // Обработка кнопок обновления
    document.querySelectorAll('.update-failed-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const clientId = this.dataset.clientId;
            const addressInput = document.querySelector(`.failed-address-input[data-client-id="${clientId}"]`);
            const latInput = document.querySelector(`.failed-lat[data-client-id="${clientId}"]`);
            const lonInput = document.querySelector(`.failed-lon[data-client-id="${clientId}"]`);
            
            const address = addressInput.value;
            const latitude = latInput.value;
            const longitude = lonInput.value;
            
            if (!latitude || !longitude) {
                alert('<?= t('map_select_address_first') ?>');
                return;
            }
            
            // Отключаем кнопку
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> <?= t('map_js_processing') ?>';
            
            // Отправляем AJAX запрос
            const formData = new FormData();
            formData.append('ajax', 'update_address');
            formData.append('client_id', clientId);
            formData.append('address', address);
            formData.append('latitude', latitude);
            formData.append('longitude', longitude);
            
            fetch('map.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Удаляем строку из таблицы
                    document.getElementById(`failed-row-${clientId}`).remove();
                    
                    // Перезагружаем страницу для обновления карты
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                    this.disabled = false;
                    this.innerHTML = '<i class="uil uil-check"></i> <?= t('update_coordinates') ?>';
                }
            })
            .catch(err => {
                console.error('Update error:', err);
                alert('Network error');
                this.disabled = false;
                this.innerHTML = '<i class="uil uil-check"></i> <?= t('update_coordinates') ?>';
            });
        });
    });
    
    // Скрывать подсказки при клике вне
    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('failed-address-input')) {
            document.querySelectorAll('.address-suggestions').forEach(box => {
                box.style.display = 'none';
            });
        }
    });
</script>
<?php include '../includes/footer.php'; ?>
