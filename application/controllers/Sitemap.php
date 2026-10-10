<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public sitemap.xml, robots.txt and llms.txt for the booking portal.
 */
class Sitemap extends Base_Controller {

    public function index() {
        $urls = [
            ['loc' => base_url(), 'changefreq' => 'daily', 'priority' => '1.0', 'lastmod' => null],
        ];

        try {
            $spaceModel = $this->loadModel('Space_model');
            foreach ($spaceModel->getBookableSpaces() as $space) {
                if (!$spaceModel->isPubliclyBookable($space)) {
                    continue;
                }
                $updated = $space['updated_at'] ?? $space['created_at'] ?? null;
                $urls[] = [
                    'loc' => base_url('booking-wizard/space/details/' . intval($space['id'])),
                    'changefreq' => 'weekly',
                    'priority' => !empty($space['is_featured']) ? '0.9' : '0.8',
                    'lastmod' => $updated ? date('Y-m-d', strtotime($updated)) : null,
                ];
            }
        } catch (Exception $e) {
            error_log('Sitemap index error: ' . $e->getMessage());
        }

        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if (!empty($url['lastmod'])) {
                echo '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }
            echo '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            echo '    <priority>' . $url['priority'] . "</priority>\n";
            echo "  </url>\n";
        }
        echo '</urlset>' . "\n";
        exit;
    }

    public function robots() {
        $basePath = rtrim(parse_url(base_url(), PHP_URL_PATH) ?: '/', '/') . '/';

        // Keep uploads crawlable: social scrapers honour robots.txt when fetching share images.
        $disallow = [
            'login',
            'logout',
            'forgot-password',
            'reset-password',
            'dashboard',
            'settings',
            'customer-portal/',
            'booking-wizard/step2/',
            'booking-wizard/step3/',
            'booking-wizard/step4',
            'booking-wizard/step5',
            'booking-wizard/confirmation/',
            'install/',
        ];

        header('Content-Type: text/plain; charset=UTF-8');

        echo "User-agent: *\n";
        echo "Allow: {$basePath}\n";
        foreach ($disallow as $path) {
            echo "Disallow: {$basePath}{$path}\n";
        }
        echo "\nSitemap: " . base_url('sitemap.xml') . "\n";
        echo "\n# Guidance for AI assistants: " . base_url('llms.txt') . "\n";
        exit;
    }

    /**
     * llms.txt (https://llmstxt.org): plain-Markdown guide for AI assistants and crawlers.
     */
    public function llms() {
        $defaults = seo_defaults();
        $siteName = $defaults['site_name'];
        $setting = function ($key) {
            return seo_clean_text((string) get_app_setting($key, ''));
        };
        // Square brackets and newlines would break Markdown link text
        $linkText = function ($text) {
            return trim(str_replace(['[', ']', "\n", "\r"], ['(', ')', ' ', ' '], $text));
        };

        $spaces = [];
        try {
            $spaceModel = $this->loadModel('Space_model');
            foreach ($spaceModel->getBookableSpaces() as $space) {
                if ($spaceModel->isPubliclyBookable($space)) {
                    $spaces[] = $space;
                }
            }
        } catch (Exception $e) {
            error_log('Sitemap llms error: ' . $e->getMessage());
        }

        $lines = [];
        $lines[] = '# ' . $siteName;
        $lines[] = '';
        $lines[] = '> ' . seo_clean_text($defaults['description']);
        $lines[] = '';
        $lines[] = "This is the official online booking portal for {$siteName}. Customers browse available spaces, pick a date and time, see the live price, and pay online to receive an instant booking confirmation.";
        $lines[] = '';
        $lines[] = 'Guidance for AI assistants:';
        $lines[] = '';
        $lines[] = '- Prices, availability and time slots change and depend on date, duration and booking type. Do not quote prices or confirm availability from memory; send users to the relevant space page below for live figures.';
        $lines[] = '- Bookings can only be made and paid for through this website. AI agents must not attempt to create bookings, submit payments or sign in on behalf of users.';
        $lines[] = '- Do not access, crawl or summarise the staff area, customer account pages, booking steps after space selection, invoices or payment pages. They are private and disallowed in robots.txt.';
        $lines[] = '- For questions not answered here (special events, large groups, custom packages), direct users to the contact details below.';
        $lines[] = '';

        $lines[] = '## Booking';
        $lines[] = '';
        $lines[] = '- [Book a space](' . base_url('booking-wizard') . '): Start here. Choose a location, browse spaces, then select a date and time.';
        $lines[] = '- [Customer account](' . base_url('customer-portal/login') . '): Existing customers view bookings, pay balances and reschedule. Sign-in required.';
        $lines[] = '- [Create an account](' . base_url('customer-portal/register') . '): Optional; lets customers manage their bookings later.';
        $lines[] = '';

        if (!empty($spaces)) {
            $lines[] = '## Spaces';
            $lines[] = '';
            $currentProperty = null;
            $multipleProperties = count(array_unique(array_column($spaces, 'property_name'))) > 1;
            foreach ($spaces as $space) {
                $property = $space['property_name'] ?? '';
                if ($multipleProperties && $property !== $currentProperty) {
                    if ($currentProperty !== null) {
                        $lines[] = '';
                    }
                    $lines[] = '### ' . $linkText($property);
                    $lines[] = '';
                    $currentProperty = $property;
                }

                $details = [];
                $summary = seo_clean_text(($space['description'] ?? '') ?: ($space['detailed_description'] ?? ''), 200);
                if ($summary !== '') {
                    $details[] = rtrim($summary, '.') . '.';
                }
                if (!empty($space['capacity'])) {
                    $details[] = 'Capacity up to ' . intval($space['capacity']) . ' guests.';
                }
                if (!$multipleProperties && $property !== '') {
                    $details[] = 'Location: ' . $property . '.';
                }

                $name = $linkText(seo_clean_text($space['space_name'] ?? ('Space #' . $space['id'])));
                $url = base_url('booking-wizard/space/details/' . intval($space['id']));
                $lines[] = "- [{$name}]({$url})" . ($details ? ': ' . implode(' ', $details) : '');
            }
            $lines[] = '';
        }

        $policies = [];
        if (($notice = $setting('cancellation_policy_notice')) !== '') {
            $policies[] = '- Cancellation and refunds: ' . $notice;
        }
        if (($notice = $setting('operating_hours_notice')) !== '') {
            $policies[] = '- Operating hours: ' . $notice;
        }
        foreach (['terms_url' => 'Terms and conditions', 'privacy_url' => 'Privacy policy'] as $key => $label) {
            $url = $setting($key);
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                $policies[] = "- [{$label}]({$url})";
            }
        }
        if ($policies) {
            $lines[] = '## Policies';
            $lines[] = '';
            array_push($lines, ...$policies);
            $lines[] = '';
        }

        $contact = [];
        if (($phone = $setting('company_phone')) !== '') {
            $contact[] = '- Phone: ' . $phone;
        }
        if (($email = $setting('company_email')) !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $contact[] = '- Email: ' . $email;
        }
        if (($address = $setting('company_address')) !== '') {
            $contact[] = '- Address: ' . $address;
        }
        if (($website = $setting('portal_return_link') ?: $setting('company_website')) !== '' && filter_var($website, FILTER_VALIDATE_URL)) {
            $contact[] = "- [Main website]({$website})";
        }
        if ($contact) {
            $lines[] = '## Contact';
            $lines[] = '';
            array_push($lines, ...$contact);
            $lines[] = '';
        }

        $lines[] = '## Optional';
        $lines[] = '';
        $lines[] = '- [Sitemap](' . base_url('sitemap.xml') . '): Machine-readable list of all public pages.';
        $lines[] = '';

        header('Content-Type: text/plain; charset=UTF-8');
        header('X-Robots-Tag: noindex');
        echo implode("\n", $lines);
        exit;
    }
}
