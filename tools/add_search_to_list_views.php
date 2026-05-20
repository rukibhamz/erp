<?php
$searchInclude = "<?php include(BASEPATH . 'views/partials/list_search_field.php'); ?>\n";

$patterns = [
    // After opening filter form row
    [
        'file' => 'application/views/receivables/invoices.php',
        'needle' => '<form method="GET" action="<?= base_url(\'receivables/invoices\') ?>" class="row g-3">',
        'insert' => '<form method="GET" action="<?= base_url(\'receivables/invoices\') ?>" class="row g-3">' . "\n            " . trim($searchInclude),
    ],
    [
        'file' => 'application/views/bookings/index.php',
        'needle' => '<form method="GET" action="" class="row g-3 align-items-end">',
        'insert' => '<form method="GET" action="" class="row g-3 align-items-end">' . "\n                " . trim($searchInclude),
    ],
];

$globFiles = array_merge(
    glob(__DIR__ . '/../application/views/receivables/*.php'),
    glob(__DIR__ . '/../application/views/payables/*.php'),
    glob(__DIR__ . '/../application/views/transactions/*.php'),
    glob(__DIR__ . '/../application/views/ledger/*.php'),
    glob(__DIR__ . '/../application/views/cash/*.php'),
    glob(__DIR__ . '/../application/views/bookings/index.php'),
    glob(__DIR__ . '/../application/views/users/*.php')
);

$updated = 0;
foreach ($globFiles as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'list_search_field.php') !== false) {
        continue;
    }
    if (strpos($content, 'name="search"') !== false && strpos($content, 'list_search_field') === false) {
        // accounts-style custom search — skip
        if (strpos($file, 'accounts/index.php') !== false) {
            continue;
        }
    }
    if (strpos($content, 'method="GET"') === false) {
        continue;
    }

    $orig = $content;
    // Insert search partial after first row g-3 form tag
    if (preg_match('/<form[^>]*method="GET"[^>]*class="[^"]*row g-3[^"]*"[^>]*>/i', $content, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1] + strlen($m[0][0]);
        $content = substr($content, 0, $pos) . "\n            " . trim($searchInclude) . substr($content, $pos);
    } elseif (preg_match('/<form[^>]*method="GET"[^>]*class="[^"]*d-flex[^"]*"[^>]*>/i', $content, $m, PREG_OFFSET_CAPTURE)) {
        // Header-inline forms: prepend search input before per_page
        $searchBlock = '<input type="search" name="search" class="form-control form-control-sm" style="min-width:200px" value="<?= htmlspecialchars(list_search_term()) ?>" placeholder="Search…">';
        if (strpos($content, 'name="search"') === false) {
            $content = preg_replace(
                '/(<form[^>]*method="GET"[^>]*class="[^"]*d-flex[^"]*"[^>]*>)/i',
                '$1' . "\n                " . $searchBlock,
                $content,
                1
            );
        }
    } else {
        continue;
    }

    if ($content !== $orig) {
        file_put_contents($file, $content);
        $updated++;
        echo str_replace(__DIR__ . '/../', '', $file) . PHP_EOL;
    }
}

echo "Updated {$updated} view files\n";
