<?php
$searchInput = '<input type="search" name="search" class="form-control form-control-sm" style="min-width:200px" value="<?= htmlspecialchars(list_search_term()) ?>" placeholder="Search name, ID, code…">';

$dirs = [
    __DIR__ . '/../application/views',
];
$updated = 0;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../application/views'));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    if (strpos($path, 'partials') !== false) {
        continue;
    }
    $content = file_get_contents($path);
    if (strpos($content, 'render_pagination_controls') === false) {
        continue;
    }
    if (strpos($content, 'name="search"') !== false || strpos($content, 'list_search_field.php') !== false) {
        continue;
    }
    // Card header GET form without search
    if (preg_match('/<form\s+method="GET"[^>]*class="[^"]*d-flex[^"]*"[^>]*>\s*<input type="hidden" name="page"/', $content)) {
        $content = preg_replace(
            '/(<form\s+method="GET"[^>]*class="[^"]*d-flex[^"]*"[^>]*>)/',
            '$1' . "\n            " . $searchInput,
            $content,
            1
        );
    } elseif (preg_match('/<div class="card[^"]*">\s*<div class="card-body">\s*<div class="table-responsive">/s', $content)
        && strpos($content, 'method="GET"') === false) {
        // Insert filter bar above table
        $bar = '<div class="card-header d-flex justify-content-end py-2">' . "\n"
            . '        <form method="GET" action="" class="d-flex align-items-center gap-2 mb-0 flex-wrap">' . "\n"
            . '            ' . $searchInput . "\n"
            . '            <input type="hidden" name="page" value="1">' . "\n"
            . '            <label class="small text-muted mb-0">Records</label>' . "\n"
            . '            <?php render_pagination_per_page_select(intval($pagination[\'per_page\'] ?? 50), \'per_page\', \'form-select form-select-sm\'); ?>' . "\n"
            . '            <button type="submit" class="btn btn-sm btn-primary">Apply</button>' . "\n"
            . '        </form>' . "\n"
            . '    </div>' . "\n"
            . '    ';
        $content = preg_replace(
            '/(<div class="card[^"]*">\s*<div class="card-body">)/',
            '$1' . "\n" . $bar,
            $content,
            1
        );
    } else {
        continue;
    }

    file_put_contents($path, $content);
    $updated++;
    echo str_replace(__DIR__ . '/../', '', $path) . PHP_EOL;
}
echo "Updated {$updated} views\n";
