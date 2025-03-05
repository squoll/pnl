<?php
require('fpdf/fpdf.php');

// Создаем новый PDF документ
$pdf = new FPDF();
$pdf->AddPage();

// Устанавливаем шрифт
$pdf->SetFont('Arial', 'B', 16);

// Добавляем текст
$pdf->Cell(40, 10, 'Привет, мир! Это пример PDF-документа.');

// Закрываем и выводим PDF документ
$pdf->Output('example.pdf', 'I'); // 'I' для отображения в браузере
?> 