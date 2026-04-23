<?php

declare(strict_types=1);

/**
 * Split-host routing: main marketing site vs assessment-only host.
 *
 * Expects WP_MAIN_DOMAIN and WP_ASSESSMENT_DOMAIN (full URLs, no trailing slash required)
 * to be defined in wp-config. When both resolve to different hosts, rules apply:
 *
 * - On the assessment host: only the Assessment page, REST, static assets, cron, and
 *   admin-ajax are allowed; wp-admin (except admin-ajax), wp-login, and other front pages
 *   redirect to WP_MAIN_DOMAIN.
 * - On the main host: the Assessment page redirects to the same path on WP_ASSESSMENT_DOMAIN;
 *   other pages are unchanged.
 *
 * When hosts match or constants are missing, all logic is skipped (e.g. local single-host).
 *
 * Split-domain rules run only when WP_ENV is `development` or `production` (not `local`).
 *
 * Asset image URLs: when the HTTP host matches {@see WP_ASSESSMENT_DOMAIN} (regardless of
 * WP_ENV), install media/theme/plugin URLs are rewritten to {@see WP_MAIN_DOMAIN} so images
 * load from the main host while the page is served from the assessment host.
 */

function eurohairlab_wp_env(): string
{
    if (defined('WP_ENV')) {
        return (string) WP_ENV;
    }

    return 'local';
}

function eurohairlab_domain_host_from_url(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST);

    return $host ? strtolower((string) $host) : '';
}

function eurohairlab_current_request_host(): string
{
    $raw = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
    if ($raw === '') {
        return '';
    }
    $colon = strpos($raw, ':');
    if ($colon !== false) {
        return substr($raw, 0, $colon);
    }

    return $raw;
}

/**
 * When not running locally, rewrite absolute (or root-relative) asset URLs that belong to this
 * WordPress install so they load from {@see WP_MAIN_DOMAIN} (main marketing host). Used on the
 * assessment page so theme and media URLs stay on the main domain when the site is served from
 * WP_ASSESSMENT_DOMAIN or another host.
 */
function eurohairlab_should_rewrite_urls_to_wp_main_domain(): bool
{
    if (strtolower(trim(eurohairlab_wp_env())) === 'local') {
        return false;
    }

    if (!defined('WP_MAIN_DOMAIN')) {
        return false;
    }

    return trim((string) WP_MAIN_DOMAIN) !== '';
}

/**
 * @return list<string>
 */
function eurohairlab_wp_install_url_hosts_for_rewrite(): array
{
    $hosts = [];
    foreach ([home_url('/'), site_url('/')] as $base) {
        $h = eurohairlab_domain_host_from_url($base);
        if ($h !== '') {
            $hosts[$h] = true;
        }
    }
    if (defined('WP_ASSESSMENT_DOMAIN')) {
        $h = eurohairlab_domain_host_from_url((string) WP_ASSESSMENT_DOMAIN);
        if ($h !== '') {
            $hosts[$h] = true;
        }
    }
    $req = eurohairlab_current_request_host();
    if ($req !== '') {
        $hosts[$req] = true;
    }

    $main = defined('WP_MAIN_DOMAIN') ? eurohairlab_domain_host_from_url((string) WP_MAIN_DOMAIN) : '';
    if ($main !== '') {
        unset($hosts[$main]);
    }

    return array_keys($hosts);
}

/**
 * Shared rewrite: URLs for this WordPress install (or root-relative paths) → {@see WP_MAIN_DOMAIN}.
 *
 * @param string $url Already trimmed.
 */
function eurohairlab_rewrite_url_to_wp_main_domain_impl(string $url): string
{
    if (!defined('WP_MAIN_DOMAIN')) {
        return $url;
    }

    $main_raw = trim((string) WP_MAIN_DOMAIN);
    $main = wp_parse_url($main_raw);
    if (!is_array($main) || empty($main['host'])) {
        return $url;
    }

    $main_scheme = isset($main['scheme']) && $main['scheme'] !== '' ? (string) $main['scheme'] : 'https';
    $main_host = strtolower((string) $main['host']);
    $main_port = isset($main['port']) ? (int) $main['port'] : 0;

    $rewrite_from_hosts = eurohairlab_wp_install_url_hosts_for_rewrite();
    if ($rewrite_from_hosts === []) {
        return $url;
    }

    $rewrite_from = array_fill_keys($rewrite_from_hosts, true);

    if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
        $rel = wp_parse_url($url);
        if (!is_array($rel) || empty($rel['path'])) {
            return $url;
        }

        $origin = $main_scheme . '://' . $main_host;
        if ($main_port > 0) {
            $origin .= ':' . $main_port;
        }

        $out = $origin . (string) $rel['path'];
        if (!empty($rel['query'])) {
            $out .= '?' . $rel['query'];
        }
        if (!empty($rel['fragment'])) {
            $out .= '#' . $rel['fragment'];
        }

        return $out;
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts)) {
        return $url;
    }

    $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    if ($scheme !== 'http' && $scheme !== 'https' && $scheme !== '') {
        return $url;
    }

    $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
    if ($host === '' || !isset($rewrite_from[$host])) {
        return $url;
    }

    $path = isset($parts['path']) ? (string) $parts['path'] : '';
    if ($path === '') {
        $path = '/';
    }

    $out = $main_scheme . '://' . $main_host;
    if ($main_port > 0) {
        $out .= ':' . $main_port;
    }
    $out .= $path;
    if (!empty($parts['query'])) {
        $out .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $out .= '#' . $parts['fragment'];
    }

    return $out;
}

/**
 * Rewrites http(s) or protocol-relative URLs whose host matches this install (or root-relative
 * paths) to use the scheme/host/port from WP_MAIN_DOMAIN.
 */
function eurohairlab_rewrite_url_to_wp_main_domain(string $url): string
{
    $url = trim($url);
    if ($url === '' || !eurohairlab_should_rewrite_urls_to_wp_main_domain()) {
        return $url;
    }

    return eurohairlab_rewrite_url_to_wp_main_domain_impl($url);
}

/**
 * Assessment page images and theme assets: map install URLs to {@see WP_MAIN_DOMAIN} when
 * the request is served on the {@see WP_ASSESSMENT_DOMAIN} host, or when the global rewriter
 * runs (non-local). On a normal local single-host install without the assessment host, URLs
 * are left unchanged so localhost media and theme files still load.
 */
function eurohairlab_rewrite_assessment_page_asset_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return $url;
    }

    if (!defined('WP_MAIN_DOMAIN') || trim((string) WP_MAIN_DOMAIN) === '') {
        return $url;
    }

    if (eurohairlab_request_host_matches_wp_assessment_domain()) {
        return eurohairlab_rewrite_url_to_wp_main_domain_impl($url);
    }

    return eurohairlab_rewrite_url_to_wp_main_domain($url);
}

function eurohairlab_split_domain_routing_active(): bool
{
    $env = strtolower(trim(eurohairlab_wp_env()));
    if ($env !== 'development' && $env !== 'production') {
        return false;
    }

    if (!defined('WP_MAIN_DOMAIN') || !defined('WP_ASSESSMENT_DOMAIN')) {
        return false;
    }

    $main = eurohairlab_domain_host_from_url((string) WP_MAIN_DOMAIN);
    $assessment = eurohairlab_domain_host_from_url((string) WP_ASSESSMENT_DOMAIN);
    if ($main === '' || $assessment === '') {
        return false;
    }

    return $main !== $assessment;
}

function eurohairlab_is_main_site_host(): bool
{
    if (!eurohairlab_split_domain_routing_active()) {
        return false;
    }

    $cur = eurohairlab_current_request_host();
    $main = eurohairlab_domain_host_from_url((string) WP_MAIN_DOMAIN);

    return $cur !== '' && $cur === $main;
}

function eurohairlab_is_assessment_site_host(): bool
{
    if (!eurohairlab_split_domain_routing_active()) {
        return false;
    }

    return eurohairlab_request_host_matches_wp_assessment_domain();
}

/**
 * True when the incoming request host equals the host of {@see WP_ASSESSMENT_DOMAIN}.
 * Used for rewriting asset/image URLs to {@see WP_MAIN_DOMAIN} without requiring split-domain
 * routing (WP_ENV) to be active.
 */
function eurohairlab_request_host_matches_wp_assessment_domain(): bool
{
    if (!defined('WP_ASSESSMENT_DOMAIN')) {
        return false;
    }

    $assessment = eurohairlab_domain_host_from_url(trim((string) WP_ASSESSMENT_DOMAIN));
    if ($assessment === '') {
        return false;
    }

    $cur = eurohairlab_current_request_host();

    return $cur !== '' && $cur === $assessment;
}

function eurohairlab_redirect_to_main_domain_home(): void
{
    $url = trailingslashit((string) WP_MAIN_DOMAIN);
    wp_redirect(esc_url_raw($url), 302);
    exit;
}

/**
 * Paths that must keep working on the assessment host before WordPress resolves the query.
 */
function eurohairlab_split_domain_assessment_host_public_path_allowed(string $path): bool
{
    if ($path === '' || $path === '/') {
        return false;
    }

    return (bool) preg_match('#^/(wp-json|wp-content|wp-includes)(/|$)#', $path);
}

function eurohairlab_split_domain_template_redirect(): void
{
    if (!eurohairlab_split_domain_routing_active()) {
        return;
    }

    $request_uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = strtok($request_uri, '?') ?: '/';

    if (eurohairlab_split_domain_assessment_host_public_path_allowed($path)) {
        return;
    }

    if (eurohairlab_is_assessment_site_host()) {
        if (is_page('assessment')) {
            return;
        }

        if (function_exists('is_robots') && is_robots()) {
            return;
        }

        eurohairlab_redirect_to_main_domain_home();
    }

    if (eurohairlab_is_main_site_host()) {
        if (is_page('assessment')) {
            $target = rtrim((string) WP_ASSESSMENT_DOMAIN, '/') . $request_uri;
            wp_redirect(esc_url_raw($target), 302);
            exit;
        }
    }
}
add_action('template_redirect', 'eurohairlab_split_domain_template_redirect', 0);

function eurohairlab_split_domain_block_assessment_host_admin(): void
{
    if (!eurohairlab_is_assessment_site_host()) {
        return;
    }

    if (wp_doing_ajax()) {
        return;
    }

    if (wp_doing_cron()) {
        return;
    }

    // admin-post.php runs admin_init before admin_post_*; blocking here would break plugin POST/GET handlers (e.g. assessment PDF download).
    $admin_script = isset($_SERVER['PHP_SELF']) ? basename((string) $_SERVER['PHP_SELF']) : '';
    if ($admin_script === 'admin-post.php') {
        return;
    }

    eurohairlab_redirect_to_main_domain_home();
}
add_action('admin_init', 'eurohairlab_split_domain_block_assessment_host_admin', 0);

function eurohairlab_split_domain_block_assessment_host_login(): void
{
    if (!eurohairlab_is_assessment_site_host()) {
        return;
    }

    eurohairlab_redirect_to_main_domain_home();
}
add_action('login_init', 'eurohairlab_split_domain_block_assessment_host_login', 0);

/**
 * When the site is accessed on {@see WP_ASSESSMENT_DOMAIN}, point upload and theme/plugin base
 * URLs at {@see WP_MAIN_DOMAIN} so attachment and bundled image URLs resolve on the main host.
 */
function eurohairlab_filter_upload_dir_for_assessment_host(array $uploads): array
{
    if (!eurohairlab_request_host_matches_wp_assessment_domain()) {
        return $uploads;
    }

    if (!defined('WP_MAIN_DOMAIN') || trim((string) WP_MAIN_DOMAIN) === '') {
        return $uploads;
    }

    foreach (['url', 'baseurl'] as $key) {
        if (!empty($uploads[$key]) && is_string($uploads[$key])) {
            $uploads[$key] = eurohairlab_rewrite_url_to_wp_main_domain_impl($uploads[$key]);
        }
    }

    return $uploads;
}
add_filter('upload_dir', 'eurohairlab_filter_upload_dir_for_assessment_host');

/**
 * @param string|false $url
 * @return string|false
 */
function eurohairlab_filter_wp_get_attachment_url_for_assessment_host($url)
{
    if (!is_string($url) || $url === '') {
        return $url;
    }

    if (!eurohairlab_request_host_matches_wp_assessment_domain()) {
        return $url;
    }

    return eurohairlab_rewrite_assessment_page_asset_url($url);
}
add_filter('wp_get_attachment_url', 'eurohairlab_filter_wp_get_attachment_url_for_assessment_host', 10, 1);

/**
 * @param array|false $image
 * @return array|false
 */
function eurohairlab_filter_wp_get_attachment_image_src_for_assessment_host($image)
{
    if (!is_array($image) || empty($image[0]) || !is_string($image[0])) {
        return $image;
    }

    if (!eurohairlab_request_host_matches_wp_assessment_domain()) {
        return $image;
    }

    $image[0] = eurohairlab_rewrite_assessment_page_asset_url($image[0]);

    return $image;
}
add_filter('wp_get_attachment_image_src', 'eurohairlab_filter_wp_get_attachment_image_src_for_assessment_host', 10, 4);

/**
 * @param array<int, array{url: string, descriptor: string, value: string}> $sources
 * @return array<int, array{url: string, descriptor: string, value: string}>
 */
function eurohairlab_filter_wp_calculate_image_srcset_for_assessment_host(array $sources): array
{
    if (!eurohairlab_request_host_matches_wp_assessment_domain()) {
        return $sources;
    }

    foreach ($sources as $w => $row) {
        if (isset($row['url']) && is_string($row['url']) && $row['url'] !== '') {
            $sources[$w]['url'] = eurohairlab_rewrite_assessment_page_asset_url($row['url']);
        }
    }

    return $sources;
}
add_filter('wp_calculate_image_srcset', 'eurohairlab_filter_wp_calculate_image_srcset_for_assessment_host', 10, 5);

/**
 * @param string $template_dir_uri Full URI to the active theme's stylesheet directory.
 */
function eurohairlab_filter_stylesheet_directory_uri_for_assessment_host(string $template_dir_uri): string
{
    if (!eurohairlab_request_host_matches_wp_assessment_domain()) {
        return $template_dir_uri;
    }

    return eurohairlab_rewrite_assessment_page_asset_url($template_dir_uri);
}
add_filter('stylesheet_directory_uri', 'eurohairlab_filter_stylesheet_directory_uri_for_assessment_host', 10, 1);

/**
 * @param string $template_dir_uri Full URI to the active theme's root directory.
 */
function eurohairlab_filter_template_directory_uri_for_assessment_host(string $template_dir_uri): string
{
    if (!eurohairlab_request_host_matches_wp_assessment_domain()) {
        return $template_dir_uri;
    }

    return eurohairlab_rewrite_assessment_page_asset_url($template_dir_uri);
}
add_filter('template_directory_uri', 'eurohairlab_filter_template_directory_uri_for_assessment_host', 10, 1);

/**
 * Plugin (and other) assets under wp-content/plugins.
 */
function eurohairlab_filter_plugins_url_for_assessment_host(string $url, string $path = '', string $plugin = ''): string
{
    unset($path, $plugin);
    if (!eurohairlab_request_host_matches_wp_assessment_domain()) {
        return $url;
    }

    return eurohairlab_rewrite_assessment_page_asset_url($url);
}
add_filter('plugins_url', 'eurohairlab_filter_plugins_url_for_assessment_host', 10, 3);
