<?php
// A simple script to validate the "Dual Database Table" theory
try {
    $pdo = new PDO('mysql:host=localhost;dbname=erp;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Query the table used by Booking_wizard (Tax_model)
    $stmt1 = $pdo->query("SELECT * FROM erp_taxes WHERE tax_code = 'VAT' OR tax_name LIKE '%VAT%' OR tax_name LIKE '%Value Added%'");
    $taxesTable = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // 2. Query the table used by Tax Configuration UI (Tax_type_model)
    $stmt2 = $pdo->query("SELECT * FROM erp_tax_types WHERE code = 'VAT' OR name LIKE '%VAT%' OR name LIKE '%Value Added%'");
    $taxTypesTable = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $diagnostics = [
        'theory' => 'The system has two separate database tables for taxes that are out of sync.',
        'evidence_table_1' => [
            'name' => "erp_taxes",
            'used_by' => 'Booking_wizard.php (via Tax_model)',
            'records' => $taxesTable
        ],
        'evidence_table_2' => [
            'name' => "erp_tax_types",
            'used_by' => 'Tax_config.php UI (via Tax_type_model)',
            'records' => $taxTypesTable
        ]
    ];

    echo json_encode($diagnostics, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
