<?php
session_start();
require_once 'config/db.php';

// Включаем отображение ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Получаем ID хостинга
$hosting_id = $_GET['id'] ?? null;

if ($hosting_id) {
    // Получаем информацию о хостинге
    $stmt = $conn->prepare("SELECT * FROM hosting WHERE id = ?");
    $stmt->execute([$hosting_id]);
    $hosting = $stmt->fetch();

    if ($hosting) {
        // Подключаем FPDF
        require_once 'fpdf/fpdf.php'; // Убедитесь, что путь правильный

        // Создаем новый PDF документ
        $pdf = new FPDF();
        $pdf->AddPage();

        // Устанавливаем шрифт
        $pdf->SetFont('Arial', 'B', 16);

        // Добавляем текст
        $pdf->Cell(40, 10, 'Счет для клиента: ' . htmlspecialchars($hosting['client_id']));
        $pdf->Ln();
        $pdf->Cell(40, 10, 'URL сайта: ' . htmlspecialchars($hosting['website_url']));
        $pdf->Ln();
        $pdf->Cell(40, 10, 'Цена: 14.52 Euro');
        $pdf->Ln();
        $pdf->Cell(40, 10, 'Дата начала: ' . htmlspecialchars($hosting['hosting_start']));
        $pdf->Ln();
        $pdf->Cell(40, 10, 'Дата окончания: ' . htmlspecialchars($hosting['hosting_end']));

        // Закрываем и выводим PDF документ
        $pdf->Output('invoice_' . $hosting_id . '.pdf', 'D'); // 'D' для скачивания
    } else {
        echo "Хостинг не найден.";
    }
} else {
    echo "ID хостинга не указан.";
}
?> 