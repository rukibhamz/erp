<?php
// Simple diagnostic script for space photos
define('BASEPATH', 'dummy'); 
require_once 'application/config/database.php';
$db_config = $db['default'];

try {
    $dsn = "mysql:host={$db_config['hostname']};dbname={$db_config['database']};charset={$db_config['char_set']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3>Database check (erp_space_photos):</h3>";
    $stmt = $pdo->query("SELECT * FROM erp_space_photos ORDER BY id DESC LIMIT 10");
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($photos)) {
        echo "<p style='color:red;'>No photos found in database table 'erp_space_photos'.</p>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Space ID</th><th>URL</th><th>Primary</th></tr>";
        foreach ($photos as $p) {
            echo "<tr><td>{$p['id']}</td><td>{$p['space_id']}</td><td>{$p['photo_url']}</td><td>{$p['is_primary']}</td></tr>";
        }
        echo "</table>";
    }

    echo "<h3>Directory check (uploads/spaces):</h3>";
    $dir = 'uploads/spaces';
    if (!is_dir($dir)) {
        echo "<p style='color:red;'>Directory '$dir' DOES NOT EXIST.</p>";
    } else {
        $files = scandir($dir);
        $files = array_diff($files, array('.', '..'));
        if (empty($files)) {
            echo "<p style='color:red;'>Directory '$dir' is EMPTY.</p>";
        } else {
            echo "<ul>";
            foreach ($files as $f) {
                echo "<li>$f</li>";
            }
            echo "</ul>";
        }
    }

} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>
