<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=erp', 'root', '');
$res = $pdo->query('SELECT * FROM erp_accounts WHERE id = 60')->fetch(PDO::FETCH_ASSOC);
echo 'Account 60: ' . print_r($res, true) . PHP_EOL;

$res = $pdo->query('SELECT * FROM erp_transactions LIMIT 1')->fetch(PDO::FETCH_ASSOC);
echo 'First Transaction: ' . print_r($res, true) . PHP_EOL;

$count = $pdo->query('SELECT COUNT(*) FROM erp_bookings')->fetchColumn();
echo 'Booking Count: ' . $count . PHP_EOL;

$tCount = $pdo->query('SELECT COUNT(*) FROM erp_transactions')->fetchColumn();
echo 'Transaction Count: ' . $tCount . PHP_EOL;

$codes = ['1000', '1010', '1200', '4000', '4100', '2100'];
foreach($codes as $code) {
    $stmt = $pdo->prepare('SELECT id, account_name FROM erp_accounts WHERE account_code = ?');
    $stmt->execute([$code]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Code $code: " . ($res ? $res['id'] . ' (' . $res['account_name'] . ')' : 'MISSING') . PHP_EOL;
}
