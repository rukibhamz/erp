<?php
// Diagnostics/check_account_types.php
// Load CodeIgniter framework
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('ENVIRONMENT', 'development');

require_once BASEPATH . 'core/CodeIgniter.php';

// Mock the CI instance to load the model
$CI =& get_instance();
$CI->load->database();
$CI->load->model('Account_model');

echo "=== DISTINCT ACCOUNT TYPES IN DB ===\n";
$query = $CI->db->query("SELECT DISTINCT account_type FROM " . $CI->db->dbprefix('accounts'));
$types = $query->result_array();
foreach ($types as $row) {
    echo "- '" . $row['account_type'] . "'\n";
}

echo "\n=== TESTING accountModel->getByType() ===\n";

$tests = ['asset', 'assets', 'revenue', 'income', 'liability', 'liabilities', 'expense', 'expenses'];

foreach ($tests as $type) {
    $results = $CI->Account_model->getByType($type);
    echo "getByType('$type'): Found " . count($results) . " accounts.\n";
    if (count($results) > 0) {
        echo "  First match: " . $results[0]['account_name'] . " (" . $results[0]['account_type'] . ")\n";
    }
}
