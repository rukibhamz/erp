<?php
$pattern = '/(<div class="card[^"]*">)\s*<div class="card-body">\s*(<div class="card-header d-flex justify-content-end py-2">.*?<\/div>)\s*/s';
$files = glob(__DIR__ . '/../application/views/**/*.php');
$files = array_merge($files, glob(__DIR__ . '/../application/views/**/**/*.php'));
$updated = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (!preg_match($pattern, $content)) {
        continue;
    }
    $fixed = preg_replace($pattern, '$1' . "\n    $2\n    <div class=\"card-body\">\n        ", $content, 1);
    if ($fixed !== $content) {
        file_put_contents($file, $fixed);
        $updated++;
        echo str_replace(__DIR__ . '/../', '', $file) . PHP_EOL;
    }
}
echo "Fixed {$updated} files\n";
