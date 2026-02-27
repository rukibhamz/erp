<?php
$lines = file('c:\xampp\htdocs\newerp\logs\error.log');
$last20 = array_slice($lines, -20);
foreach ($last20 as $line) {
    echo $line;
}
?>
