<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Export Helper
 * Provides functions for exporting data to PDF and Excel formats
 */

/**
 * Export HTML content to PDF
 * 
 * @param string $html HTML content to convert
 * @param string $filename Output filename
 * @param string $orientation Page orientation (P=Portrait, L=Landscape)
 * @return void
 */
function exportToPDF($html, $filename, $orientation = 'P') {
    // Use Dompdf
    $autoloadPath = BASEPATH . '../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        show_error('Dependencies are missing. Please run "composer install" in the application root directory to install DomPDF and other required libraries.', 500, 'Composer Dependencies Missing');
        return;
    }
    require_once $autoloadPath;
    
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', strtolower($orientation));
    $dompdf->render();
    
    // Output PDF
    $dompdf->stream($filename, ['Attachment' => true]);
}

/**
 * Export data to Excel (CSV format)
 * 
 * @param array $data 2D array of data
 * @param string $filename Output filename
 * @param string $sheetName Sheet name (not used in CSV)
 * @return void
 */
function exportToExcel($data, $filename, $sheetName = 'Report') {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Format currency for display
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency symbol
 * @return string Formatted currency
 */
function formatCurrency($amount, $currency = '₦') {
    return $currency . number_format($amount, 2);
}

/**
 * Generate PDF-friendly HTML wrapper
 * 
 * @param string $title Document title
 * @param string $content HTML content
 * @return string Complete HTML document
 */
function wrapPdfHtml($title, $content) {
    return '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }
        h2 {
            font-size: 14px;
            margin: 15px 0 10px 0;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .subtitle {
            color: #666;
            font-size: 11px;
            margin-bottom: 15px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    ' . $content . '
    <div class="footer">
        Generated on ' . date('Y-m-d H:i:s') . '
    </div>
</body>
</html>';
}
