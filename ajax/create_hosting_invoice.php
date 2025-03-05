<?php
require_once '../config/db.php';
require_once '../lib/InvoiceGenerator.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$hosting_id = $data['hosting_id'] ?? null;

if ($hosting_id) {
    try {
        // Получаем данные о хостинге и клиенте
        $stmt = $pdo->prepare("
            SELECT h.*, c.* 
            FROM hosting h 
            JOIN clients c ON h.client_id = c.id 
            WHERE h.id = ?
        ");
        $stmt->execute([$hosting_id]);
        $hosting_data = $stmt->fetch();
        
        if ($hosting_data) {
            // Генерируем номер счета
            $invoice_number = 'INV-' . date('Y') . sprintf('%04d', rand(1, 9999));
            
            // Подготавливаем данные для счета
            $invoice_data = [
                'invoice_number' => $invoice_number,
                'client_name' => $hosting_data['first_name'] . ' ' . $hosting_data['last_name'],
                'company' => $hosting_data['company'],
                'email' => $hosting_data['email'],
                'phone' => $hosting_data['phone'],
                'service_name' => 'Хостинг ' . $hosting_data['website_url'],
                'amount' => $hosting_data['price'],
                'issue_date' => date('Y-m-d')
            ];
            
            // Генерируем PDF
            $generator = new InvoiceGenerator($invoice_data);
            $file_name = $invoice_number . '.pdf';
            $file_path = '../uploads/invoices/' . $file_name;
            $generator->generateInvoice()->save($file_path);
            
            // Сохраняем счет в базе
            $stmt = $pdo->prepare("
                INSERT INTO invoices (client_id, invoice_number, amount, file_path, issue_date, status) 
                VALUES (?, ?, ?, ?, CURRENT_DATE, 'pending')
            ");
            $stmt->execute([
                $hosting_data['client_id'],
                $invoice_number,
                $hosting_data['price'],
                $file_name
            ]);
            
            echo json_encode([
                'success' => true,
                'client_id' => $hosting_data['client_id'],
                'invoice_number' => $invoice_number
            ]);
        } else {
            throw new Exception('Hosting not found');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid hosting ID']);
} 