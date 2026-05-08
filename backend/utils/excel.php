<?php
/**
 * Excel XML Generator Utility
 * Generate .xls files using XML format (Excel 97-2003)
 */

function generateExcelXML($headers, $data, $sheetName = 'Sheet1') {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
    $xml .= 'xmlns:o="urn:schemas-microsoft-com:office:office" ';
    $xml .= 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
    $xml .= 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    
    // Styles
    $xml .= '<Styles>' . "\n";
    $xml .= '<Style ss:ID="header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#4e73df" ss:Pattern="Solid"/></Style>' . "\n";
    $xml .= '<Style ss:ID="date"><NumberFormat ss:Format="yyyy-mm-dd"/></Style>' . "\n";
    $xml .= '</Styles>' . "\n";
    
    // Worksheet
    $xml .= '<Worksheet ss:Name="' . htmlspecialchars($sheetName) . '">' . "\n";
    $xml .= '<Table>' . "\n";
    
    // Headers
    $xml .= '<Row>' . "\n";
    foreach ($headers as $header) {
        $xml .= '<Cell ss:StyleID="header"><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
    }
    $xml .= '</Row>' . "\n";
    
    // Data rows
    foreach ($data as $row) {
        $xml .= '<Row>' . "\n";
        foreach ($headers as $header) {
            $value = $row[$header] ?? '';
            $type = 'String';
            
            // Detect numeric values
            if (is_numeric($value) && $value != '') {
                $type = 'Number';
            }
            // Detect dates
            elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $type = 'String';
                // Could use date style if needed
            }
            
            $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($value) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
    }
    
    $xml .= '</Table>' . "\n";
    $xml .= '</Worksheet>' . "\n";
    $xml .= '</Workbook>';
    
    return $xml;
}

function parseExcelFile($filePath) {
    // Simple CSV parser for now (can be extended to support .xls parsing)
    $data = [];
    $headers = [];
    
    if (($handle = fopen($filePath, 'r')) !== FALSE) {
        $row = 0;
        while (($line = fgets($handle)) !== FALSE) {
            // Remove BOM if present
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            $line = trim($line);
            
            if ($line === '') continue;
            
            // Parse CSV (simple comma-separated, no quoted commas support)
            $values = str_getcsv($line);
            
            if ($row === 0) {
                $headers = $values;
            } else {
                $rowData = [];
                foreach ($headers as $i => $header) {
                    $rowData[$header] = $values[$i] ?? '';
                }
                $data[] = $rowData;
            }
            $row++;
        }
        fclose($handle);
    }
    
    return ['headers' => $headers, 'data' => $data];
}

function downloadExcelFile($xml, $filename) {
    header('Content-Type: application/vnd.ms-excel;charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    echo $xml;
    exit;
}
?>
