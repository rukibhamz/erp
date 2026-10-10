<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/settings_helper.php';

/**
 * Plain-text value for meta tags. Settings saved through sanitize_input() are stored
 * HTML-escaped, so decode first to avoid double-escaped output like "&amp;amp;".
 */
function seo_clean_text($value, int $maxLength = 0): string {
    $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));

    if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
        $cut = mb_substr($text, 0, $maxLength - 1);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.6) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }
        $text = rtrim($cut, " ,.;:-") . '…';
    }

    return $text;
}

function seo_absolute_url(string $pathOrUrl): string {
    if ($pathOrUrl === '' || preg_match('#^https?://#i', $pathOrUrl)) {
        return $pathOrUrl;
    }
    return base_url(ltrim($pathOrUrl, '/'));
}

/**
 * Absolute URL for a local asset, versioned by mtime so social scrapers pick up replacements.
 */
function seo_asset_url(string $relativePath): string {
    $relativePath = ltrim($relativePath, '/');
    $file = (defined('ROOTPATH') ? ROOTPATH : dirname(BASEPATH) . '/') . $relativePath;
    $url = base_url($relativePath);
    return is_file($file) ? $url . '?v=' . filemtime($file) : $url;
}

/**
 * Current page URL without query string, on the configured scheme/host.
 */
function seo_canonical_url(): string {
    $base = parse_url(base_url());
    $origin = ($base['scheme'] ?? 'http') . '://' . ($base['host'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    if (!empty($base['port'])) {
        $origin .= ':' . $base['port'];
    }
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return $origin . $path;
}

function seo_site_name(): string {
    $name = seo_clean_text(get_app_setting('seo_site_title', ''));
    if ($name === '') {
        $name = seo_clean_text(get_app_setting('company_name', ''));
    }
    return $name !== '' ? $name : 'Acropolis Park';
}

function seo_defaults(): array {
    $siteName = seo_site_name();

    $description = seo_clean_text(get_app_setting('seo_description', ''));
    if ($description === '') {
        $description = "Book event spaces, picnic spots, photo and video shoot locations, and workspaces at {$siteName}. "
            . 'Check live availability, get instant confirmation and pay securely online.';
    }

    $keywords = seo_clean_text(get_app_setting('seo_keywords', ''));
    if ($keywords === '') {
        $keywords = "{$siteName}, event space booking, venue hire, picnic booking, photoshoot location, "
            . 'video shoot location, workspace booking, book a space online';
    }

    $customImage = trim((string) get_app_setting('seo_og_image', ''));
    $imagePath = ($customImage !== '' && is_file(ROOTPATH . 'uploads/company/' . $customImage))
        ? 'uploads/company/' . $customImage
        : 'assets/img/og-image.png';

    $twitter = trim((string) get_app_setting('seo_twitter_handle', ''));

    $themeColor = trim((string) get_app_setting('brand_color', ''));
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) {
        $themeColor = '#000000';
    }

    return [
        'site_name' => $siteName,
        'description' => $description,
        'keywords' => $keywords,
        'image' => $imagePath,
        'image_alt' => "{$siteName} — book spaces online",
        'twitter_handle' => $twitter !== '' ? '@' . ltrim($twitter, '@') : '',
        'theme_color' => $themeColor,
        'locale' => 'en_NG',
    ];
}

/**
 * Organization / LocalBusiness structured data for indexable public pages.
 */
function seo_structured_data(array $defaults, string $imageUrl): array {
    $website = trim((string) get_app_setting('company_website', ''));
    if ($website === '') {
        $website = trim((string) get_app_setting('portal_return_link', ''));
    }

    $logo = trim((string) get_app_setting('company_logo', ''));
    $logoUrl = ($logo !== '' && is_file(ROOTPATH . 'uploads/company/' . $logo))
        ? base_url('uploads/company/' . $logo)
        : base_url('assets/img/icon-512.png');

    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $defaults['site_name'],
        'description' => $defaults['description'],
        'url' => $website !== '' ? $website : base_url(),
        'logo' => $logoUrl,
        'image' => $imageUrl,
        'potentialAction' => [
            '@type' => 'ReserveAction',
            'target' => base_url('booking-wizard'),
            'name' => 'Book a space',
        ],
    ];

    $phone = seo_clean_text(get_app_setting('company_phone', ''));
    $email = seo_clean_text(get_app_setting('company_email', ''));
    $address = seo_clean_text(get_app_setting('company_address', ''));
    if ($phone !== '') {
        $data['telephone'] = $phone;
    }
    if ($email !== '') {
        $data['email'] = $email;
    }
    if ($address !== '') {
        $data['address'] = $address;
    }

    return $data;
}

/**
 * Render <title>, meta description, robots, canonical, icons, Open Graph, Twitter and JSON-LD.
 *
 * Page options (all optional):
 *  - title          Page title; the site name is appended.
 *  - full_title     Exact <title> text, used as-is.
 *  - description    Page description (truncated to ~160 chars).
 *  - image          Absolute URL or site-relative path of the share image.
 *  - image_alt      Alt text for the share image.
 *  - canonical      Canonical URL (defaults to current URL without query string).
 *  - robots         e.g. "index, follow" (default) or "noindex, nofollow".
 *  - type           Open Graph type (default "website").
 *  - structured_data  Emit LocalBusiness JSON-LD (default: true when indexable).
 */
function seo_meta_tags(array $page = []): string {
    $d = seo_defaults();
    $siteName = $d['site_name'];

    $pageTitle = seo_clean_text($page['title'] ?? '');
    if (!empty($page['full_title'])) {
        $title = seo_clean_text($page['full_title']);
    } elseif ($pageTitle !== '' && strcasecmp($pageTitle, $siteName) !== 0) {
        $title = "{$pageTitle} | {$siteName}";
    } else {
        $title = "{$siteName} | Book Spaces Online";
    }

    $description = seo_clean_text($page['description'] ?? '', 160);
    if ($description === '') {
        $description = seo_clean_text($d['description'], 160);
    }

    $robots = $page['robots'] ?? 'index, follow';
    $indexable = stripos($robots, 'noindex') === false;
    $canonical = $page['canonical'] ?? seo_canonical_url();

    $imageWidth = null;
    $imageHeight = null;
    if (!empty($page['image'])) {
        $image = seo_absolute_url((string) $page['image']);
    } else {
        $image = seo_asset_url($d['image']);
        $size = @getimagesize(ROOTPATH . $d['image']);
        if ($size) {
            [$imageWidth, $imageHeight] = $size;
        }
    }
    $imageAlt = seo_clean_text($page['image_alt'] ?? $d['image_alt']);
    $type = $page['type'] ?? 'website';

    $e = function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $tags = [];
    $tags[] = '<title>' . $e($title) . '</title>';
    $tags[] = '<meta name="description" content="' . $e($description) . '">';
    if ($indexable) {
        $tags[] = '<meta name="keywords" content="' . $e($d['keywords']) . '">';
    }
    $tags[] = '<meta name="robots" content="' . $e($robots) . '">';
    if ($indexable) {
        $tags[] = '<link rel="canonical" href="' . $e($canonical) . '">';
    }
    $tags[] = '<meta name="application-name" content="' . $e($siteName) . '">';
    $tags[] = '<meta name="theme-color" content="' . $e($d['theme_color']) . '">';

    $tags[] = '<link rel="icon" href="' . $e(seo_asset_url('favicon.ico')) . '" sizes="any">';
    $tags[] = '<link rel="icon" type="image/svg+xml" href="' . $e(seo_asset_url('assets/img/favicon.svg')) . '">';
    $tags[] = '<link rel="icon" type="image/png" sizes="32x32" href="' . $e(seo_asset_url('assets/img/favicon-32.png')) . '">';
    $tags[] = '<link rel="apple-touch-icon" sizes="180x180" href="' . $e(seo_asset_url('assets/img/apple-touch-icon.png')) . '">';
    $tags[] = '<link rel="manifest" href="' . $e(base_url('pwa_manifest.php')) . '">';

    $tags[] = '<meta property="og:type" content="' . $e($type) . '">';
    $tags[] = '<meta property="og:site_name" content="' . $e($siteName) . '">';
    $tags[] = '<meta property="og:title" content="' . $e($title) . '">';
    $tags[] = '<meta property="og:description" content="' . $e($description) . '">';
    $tags[] = '<meta property="og:url" content="' . $e($canonical) . '">';
    $tags[] = '<meta property="og:locale" content="' . $e($d['locale']) . '">';
    $tags[] = '<meta property="og:image" content="' . $e($image) . '">';
    if (stripos($image, 'https://') === 0) {
        $tags[] = '<meta property="og:image:secure_url" content="' . $e($image) . '">';
    }
    if ($imageWidth && $imageHeight) {
        $tags[] = '<meta property="og:image:width" content="' . (int) $imageWidth . '">';
        $tags[] = '<meta property="og:image:height" content="' . (int) $imageHeight . '">';
    }
    $tags[] = '<meta property="og:image:alt" content="' . $e($imageAlt) . '">';

    $tags[] = '<meta name="twitter:card" content="summary_large_image">';
    if ($d['twitter_handle'] !== '') {
        $tags[] = '<meta name="twitter:site" content="' . $e($d['twitter_handle']) . '">';
    }
    $tags[] = '<meta name="twitter:title" content="' . $e($title) . '">';
    $tags[] = '<meta name="twitter:description" content="' . $e($description) . '">';
    $tags[] = '<meta name="twitter:image" content="' . $e($image) . '">';
    $tags[] = '<meta name="twitter:image:alt" content="' . $e($imageAlt) . '">';

    if ($page['structured_data'] ?? $indexable) {
        $json = json_encode(
            seo_structured_data($d, $image),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );
        $nonce = function_exists('csp_nonce') ? ' nonce="' . $e(csp_nonce()) . '"' : '';
        $tags[] = '<script type="application/ld+json"' . $nonce . '>' . $json . '</script>';
    }

    return '    ' . implode("\n    ", $tags) . "\n";
}
