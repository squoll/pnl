<?php
require_once 'vendor/autoload.php'; // Требуется установить tcpdf через composer
use TCPDF;

class InvoiceGenerator extends TCPDF {
    private $invoice_data;
    
    public function __construct($invoice_data) {
        parent::__construct();
        $this->invoice_data = $invoice_data;
    }
    
    public function generateInvoice() {
        // Настройка документа
        $this->SetCreator('Admin Panel');
        $this->SetAuthor('Admin Panel');
        $this->SetTitle('Счет №' . $this->invoice_data['invoice_number']);
        
        // Добавление страницы
        $this->AddPage();
        
        // Шапка счета
        $this->SetFont('dejavusans', 'B', 16);
        $this->Cell(0, 10, 'СЧЕТ №' . $this->invoice_data['invoice_number'], 0, 1, 'C');
        $this->SetFont('dejavusans', '', 10);
        $this->Cell(0, 10, 'Дата: ' . $this->invoice_data['issue_date'], 0, 1, 'R');
        
        // Информация о клиенте
        $this->SetFont('dejavusans', 'B', 12);
        $this->Cell(0, 10, 'Клиент:', 0, 1);
        $this->SetFont('dejavusans', '', 10);
        $this->Cell(0, 10, $this->invoice_data['client_name'], 0, 1);
        $this->Cell(0, 10, $this->invoice_data['company'], 0, 1);
        $this->Cell(0, 10, 'Email: ' . $this->invoice_data['email'], 0, 1);
        $this->Cell(0, 10, 'Телефон: ' . $this->invoice_data['phone'], 0, 1);
        
        // Таблица услуг
        $this->Ln(10);
        $this->SetFont('dejavusans', 'B', 10);
        
        // Заголовки таблицы
        $this->SetFillColor(240, 240, 240);
        $this->Cell(90, 7, 'Наименование', 1, 0, 'C', true);
        $this->Cell(30, 7, 'Кол-во', 1, 0, 'C', true);
        $this->Cell(30, 7, 'Цена', 1, 0, 'C', true);
        $this->Cell(40, 7, 'Сумма', 1, 1, 'C', true);
        
        // Данные таблицы
        $this->SetFont('dejavusans', '', 10);
        $this->Cell(90, 7, $this->invoice_data['service_name'], 1);
        $this->Cell(30, 7, '1', 1, 0, 'C');
        $this->Cell(30, 7, $this->invoice_data['amount'] . ' руб.', 1, 0, 'R');
        $this->Cell(40, 7, $this->invoice_data['amount'] . ' руб.', 1, 1, 'R');
        
        // Итого
        $this->SetFont('dejavusans', 'B', 10);
        $this->Cell(150, 7, 'Итого:', 1, 0, 'R');
        $this->Cell(40, 7, $this->invoice_data['amount'] . ' руб.', 1, 1, 'R');
        
        // Подпись
        $this->Ln(20);
        $this->Cell(0, 10, 'Подпись ________________', 0, 1, 'R');
        
        return $this;
    }
    
    public function save($path) {
        return $this->Output($path, 'F');
    }
} 