<?php
require_once '../includes/auth.php';
require_once '../includes/i18n.php';
requireAuth();

header('Content-Type: application/json');

// Путь к файлу с шаблоном
$template_file = __DIR__ . '/../config/sms_template.txt';
$default_template = t('sms_default_template');

// Загружаем шаблон
$template = file_exists($template_file) ? file_get_contents($template_file) : $default_template;

// Заменяем переменные если переданы параметры
if (isset($_GET['name'])) {
    $template = str_replace('{NAME}', $_GET['name'], $template);
}
if (isset($_GET['phone'])) {
    $template = str_replace('{PHONE}', $_GET['phone'], $template);
}
if (isset($_GET['days'])) {
    $template = str_replace('{DAYS}', $_GET['days'], $template);
}

echo json_encode([
    'success' => true,
    'template' => $template
]);
