<?php

declare(strict_types=1);

require_once get_template_directory() . '/inc/site-data.php';
require_once get_template_directory() . '/inc/page-i18n.php';
require_once get_template_directory() . '/inc/metabox-category-i18n.php';
require_once get_template_directory() . '/inc/domain-routing.php';
require_once get_template_directory() . '/inc/metabox-home.php';
require_once get_template_directory() . '/inc/metabox-about.php';
require_once get_template_directory() . '/inc/metabox-diagnosis.php';
require_once get_template_directory() . '/inc/metabox-contact.php';
require_once get_template_directory() . '/inc/metabox-promo.php';
require_once get_template_directory() . '/inc/metabox-blog-list.php';
require_once get_template_directory() . '/inc/metabox-blog-post.php';
require_once get_template_directory() . '/inc/blog-permalinks.php';
require_once get_template_directory() . '/inc/metabox-assessment.php';
require_once get_template_directory() . '/inc/metabox-seo.php';
require_once get_template_directory() . '/inc/cpt-eurohairlab-marketing.php';
require_once get_template_directory() . '/inc/metabox-marketing-pages.php';

function eurohairlab_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('editor-styles');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_post_type_support('page', 'excerpt');
}
add_action('after_setup_theme', 'eurohairlab_theme_setup');

/**
 * Browsers request /favicon.ico by default; without a file at the web root that yields 404 in DevTools.
 * Short-circuit so the request never reaches WP’s 404 template (no body; not an error).
 */
function eurohairlab_short_circuit_favicon_ico_request(): void
{
    if (PHP_SAPI === 'cli' || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    $request_path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (!is_string($request_path) || $request_path === '') {
        return;
    }

    $home_path = parse_url(home_url('/'), PHP_URL_PATH);
    $home_path = is_string($home_path) ? '/' . trim($home_path, '/') : '';
    $home_prefix = ($home_path === '/' || $home_path === '') ? '' : $home_path;

    $allowed = ['/favicon.ico'];
    if ($home_prefix !== '') {
        $allowed[] = $home_prefix . '/favicon.ico';
    }

    if (!in_array($request_path, $allowed, true)) {
        return;
    }

    nocache_headers();
    status_header(204);
    exit;
}
add_action('init', 'eurohairlab_short_circuit_favicon_ico_request', 0);

function eurohairlab_remove_page_editor(): void
{
    remove_post_type_support('page', 'editor');
}
add_action('admin_init', 'eurohairlab_remove_page_editor');

add_filter('use_block_editor_for_post_type', function ($use_block_editor, $post_type) {
    if (in_array($post_type, ['page', 'post', 'eh_treatment_program', 'eh_result', 'eh_promo'], true)) {
        return false;
    }

    return $use_block_editor;
}, 10, 2);

/**
 * Local only: force Tailwind Play CDN (same config as marketing surfaces below).
 */
function eurohairlab_use_tailwind_play_cdn(): bool
{
    if (!defined('WP_ENV')) {
        return false;
    }

    return strtolower(trim((string) WP_ENV)) === 'local';
}

/**
 * Main marketing surfaces: home, about, blog, treatments, etc. Use Tailwind CDN for now (JIT) instead of compiled {@see tailwind-built.css}.
 */
function eurohairlab_is_tailwind_cdn_marketing_surface(): bool
{
    if (is_front_page()) {
        return true;
    }

    if (is_page([
        'home',
        'about',
        'contact',
        'blog-list',
        'diagnosis',
        'treatments',
        'treatment-programs',
        'results',
        'promo',
        'assessment',
    ])) {
        return true;
    }

    if (is_singular('post')) {
        return true;
    }

    if (is_home() && !is_front_page()) {
        return true;
    }

    return false;
}

/**
 * Production hosts where the whole public theme should load Tailwind from the CDN (JIT), not {@see tailwind-built.css}.
 * Requires {@see WP_ENV} === production so staging/dev hostnames are unaffected.
 */
function eurohairlab_should_use_tailwind_cdn_on_production_hosts(): bool
{
    if (!defined('WP_ENV') || strtolower(trim((string) WP_ENV)) !== 'production') {
        return false;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = (string) (preg_replace('/:\d+$/', '', $host) ?? $host);

    $production_hosts = [
        'eurohairlab.com',
        'www.eurohairlab.com',
        'assessment.eurohairlab.com',
    ];

    return in_array($host, $production_hosts, true);
}

function eurohairlab_should_enqueue_tailwind_via_cdn(): bool
{
    return eurohairlab_use_tailwind_play_cdn()
        || eurohairlab_is_tailwind_cdn_marketing_surface()
        || eurohairlab_should_use_tailwind_cdn_on_production_hosts();
}

function eurohairlab_enqueue_assets(): void
{
    $script_dependencies = [];

    wp_enqueue_style(
        'eurohairlab-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@100;400;500;600;700&family=Montserrat:wght@500;600;700;800&display=swap',
        [],
        null
    );

    $theme_style_deps = [];

    if (eurohairlab_should_enqueue_tailwind_via_cdn()) {
        wp_enqueue_script('tailwind-cdn', 'https://cdn.tailwindcss.com', [], null, false);

        $tailwind_config = [
            'theme' => [
                'extend' => [
                    'fontFamily' => [
                        'sans' => ['Inter', 'system-ui', 'sans-serif'],
                        'display' => ['Montserrat', 'system-ui', 'sans-serif'],
                        'futuraHv' => ['Futura Hv BT', 'Futura BT', 'sans-serif'],
                        'futuraBk' => ['Futura Bk BT', 'Futura BT', 'sans-serif'],
                    ],
                    'colors' => [
                        'ink' => '#121012',
                        'sand' => '#f6f5f1',
                        'blush' => '#dea093',
                        'mist' => '#999999',
                        'cocoa' => '#8d5f56',
                        'eh-ink' => '#231F20',
                        'eh-coral' => '#DEA093',
                        'eh-muted' => '#686869',
                        'eh-sand-num' => '#D5BBA0',
                        'eh-panel' => '#D9D9D9',
                    ],
                    'boxShadow' => [
                        'soft' => '0 24px 80px rgba(18, 16, 18, 0.16)',
                    ],
                ],
            ],
        ];

        wp_add_inline_script(
            'tailwind-cdn',
            'tailwind.config = ' . wp_json_encode($tailwind_config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . ';',
            'after'
        );
    } else {
        $tailwind_built_path = get_template_directory() . '/assets/css/tailwind-built.css';
        if (is_readable($tailwind_built_path)) {
            wp_enqueue_style(
                'eurohairlab-tailwind',
                get_template_directory_uri() . '/assets/css/tailwind-built.css',
                [],
                filemtime($tailwind_built_path)
            );
            $theme_style_deps[] = 'eurohairlab-tailwind';
        }
    }

    wp_enqueue_style(
        'eurohairlab-theme',
        get_template_directory_uri() . '/assets/css/app.css',
        $theme_style_deps,
        filemtime(get_template_directory() . '/assets/css/app.css')
    );

    if (is_front_page() || is_page(['about', 'blog-list', 'diagnosis', 'treatments', 'treatment-programs', 'results'])) {
        wp_enqueue_style(
            'slick-carousel',
            'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',
            [],
            '1.8.1'
        );

        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'slick-carousel',
            'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
            ['jquery'],
            '1.8.1',
            true
        );

        $script_dependencies = ['jquery', 'slick-carousel'];
    }

    wp_enqueue_script(
        'eurohairlab-theme',
        get_template_directory_uri() . '/assets/js/app.js',
        $script_dependencies,
        filemtime(get_template_directory() . '/assets/js/app.js'),
        true
    );

    if (function_exists('eh_assessment_get_frontend_config') && is_page('assessment')) {
        wp_localize_script(
            'eurohairlab-theme',
            'eurohairlabAssessment',
            eh_assessment_get_frontend_config()
        );
    }
}
add_action('wp_enqueue_scripts', 'eurohairlab_enqueue_assets');

function eurohairlab_ensure_core_pages(): void
{
    if (!function_exists('wp_insert_post')) {
        return;
    }

    $pages = [
        'assessment' => [
            'post_title' => 'Assessment',
            'post_content' => '',
            'post_excerpt' => 'Online hair assessment wizard for Eurohairlab.',
        ],
    ];

    foreach ($pages as $slug => $page) {
        $existing_page = get_page_by_path($slug, OBJECT, 'page');

        if ($existing_page instanceof WP_Post) {
            continue;
        }

        wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $page['post_title'],
            'post_name' => $slug,
            'post_content' => $page['post_content'],
            'post_excerpt' => $page['post_excerpt'],
        ]);
    }
}
add_action('init', 'eurohairlab_ensure_core_pages');

/**
 * Use the Treatments layout for slug `treatments` or legacy `treatment-programs` until the page is renamed in WP.
 */
function eurohairlab_treatments_page_template(string $template): string
{
    if (!is_singular('page')) {
        return $template;
    }

    $slug = (string) get_post_field('post_name', get_queried_object_id(), 'raw');
    if ($slug !== 'treatments' && $slug !== 'treatment-programs') {
        return $template;
    }

    $custom = get_template_directory() . '/page-treatments.php';
    if (!is_readable($custom)) {
        return $template;
    }

    return $custom;
}
add_filter('template_include', 'eurohairlab_treatments_page_template', 99);

function eurohairlab_resource_hints(array $urls, string $relation_type): array
{
    if ($relation_type === 'preconnect') {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = [
            'href' => 'https://fonts.gstatic.com',
            'crossorigin',
        ];
        if (eurohairlab_should_enqueue_tailwind_via_cdn()) {
            $urls[] = 'https://cdn.tailwindcss.com';
        }
    }

    return $urls;
}
add_filter('wp_resource_hints', 'eurohairlab_resource_hints', 10, 2);

function eurohairlab_cleanup_frontend_assets(): void
{
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
    wp_dequeue_style('global-styles');
}
add_action('wp_enqueue_scripts', 'eurohairlab_cleanup_frontend_assets', 100);

function eurohairlab_disable_emojis(): void
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'eurohairlab_disable_emojis');

function eurohairlab_filter_page_specific_meta_boxes(WP_Post $post): void
{
    if ($post->post_type !== 'page') {
        return;
    }

    $home_meta_box_ids = [
        'eh_home_hero_section',
        'eh_home_foundation_section',
        'eh_home_difference_section',
        'eh_home_technology_section',
        'eh_home_programs_section',
        'eh_home_testimonials_section',
        'eh_home_consultation_section',
    ];
    $about_meta_box_ids = [
        'eh_about_hero_section',
        'eh_about_foundation_section',
        'eh_about_science_section',
        'eh_about_partnership_section',
        'eh_about_clinical_technology_section',
        'eh_about_premium_section',
    ];
    $diagnosis_meta_box_ids = [
        'eh_diagnosis_hero_section',
        'eh_diagnosis_why_section',
        'eh_diagnosis_symptoms_section',
        'eh_diagnosis_steps_section',
        'eh_diagnosis_analysis_section',
        'eh_diagnosis_delivers_section',
        'eh_diagnosis_free_scalp_section',
        'eh_diagnosis_bottom_cta_section',
    ];
    $treatments_meta_box_ids = [
        'eh_treatments_hero_section',
    ];
    $results_meta_box_ids = [
        'eh_results_hero_section',
    ];
    $contact_meta_box_ids = [
        'eh_contact_map_section',
    ];
    $promo_meta_box_ids = [
        'eh_promo_hero_section',
    ];
    $blog_list_meta_box_ids = [
        'eh_blog_list_section',
    ];
    $assessment_meta_box_ids = [
        'eh_assessment_landing_section',
        'eh_assessment_q1_section',
        'eh_assessment_q2_section',
        'eh_assessment_q3_section',
        'eh_assessment_q4_section',
        'eh_assessment_q5_section',
        'eh_assessment_q6_section',
        'eh_assessment_q7_section',
        'eh_assessment_q8_section',
        'eh_assessment_q9_section',
        'eh_assessment_q10_section',
        'eh_assessment_complete_section',
    ];

    $front_page_id = (int) get_option('page_on_front');
    $is_home_page  = ($front_page_id && (int) $post->ID === $front_page_id) || $post->post_name === 'home';
    $is_about_page = $post->post_name === 'about';
    $is_diagnosis_page = $post->post_name === 'diagnosis';
    $is_treatments_page = $post->post_name === 'treatments' || $post->post_name === 'treatment-programs';
    $is_results_page = $post->post_name === 'results';
    $is_contact_page = $post->post_name === 'contact';
    $is_promo_page = $post->post_name === 'promo';
    $is_blog_list_page = $post->post_name === 'blog-list';
    $is_assessment_page = $post->post_name === 'assessment';

    $other_marketing_page_boxes = array_merge($treatments_meta_box_ids, $results_meta_box_ids);

    if ($is_home_page) {
        foreach (array_merge($about_meta_box_ids, $diagnosis_meta_box_ids, $contact_meta_box_ids, $promo_meta_box_ids, $blog_list_meta_box_ids, $assessment_meta_box_ids, $other_marketing_page_boxes) as $meta_box_id) {
            remove_meta_box($meta_box_id, 'page', 'normal');
        }

        return;
    }

    if ($is_about_page) {
        foreach (array_merge($home_meta_box_ids, $diagnosis_meta_box_ids, $contact_meta_box_ids, $promo_meta_box_ids, $blog_list_meta_box_ids, $assessment_meta_box_ids, $other_marketing_page_boxes) as $meta_box_id) {
            remove_meta_box($meta_box_id, 'page', 'normal');
        }

        return;
    }

    if ($is_diagnosis_page) {
        foreach (array_merge($home_meta_box_ids, $about_meta_box_ids, $contact_meta_box_ids, $promo_meta_box_ids, $blog_list_meta_box_ids, $assessment_meta_box_ids, $other_marketing_page_boxes) as $meta_box_id) {
            remove_meta_box($meta_box_id, 'page', 'normal');
        }

        return;
    }

    if ($is_treatments_page) {
        foreach (array_merge($home_meta_box_ids, $about_meta_box_ids, $diagnosis_meta_box_ids, $contact_meta_box_ids, $promo_meta_box_ids, $blog_list_meta_box_ids, $assessment_meta_box_ids, $results_meta_box_ids) as $meta_box_id) {
            remove_meta_box($meta_box_id, 'page', 'normal');
        }

        return;
    }

    if ($is_results_page) {
        foreach (array_merge($home_meta_box_ids, $about_meta_box_ids, $diagnosis_meta_box_ids, $contact_meta_box_ids, $promo_meta_box_ids, $blog_list_meta_box_ids, $assessment_meta_box_ids, $treatments_meta_box_ids) as $meta_box_id) {
            remove_meta_box($meta_box_id, 'page', 'normal');
        }

        return;
    }

    if ($is_contact_page) {
        foreach (array_merge($home_meta_box_ids, $about_meta_box_ids, $diagnosis_meta_box_ids, $promo_meta_box_ids, $blog_list_meta_box_ids, $assessment_meta_box_ids, $other_marketing_page_boxes) as $meta_box_id) {
            remove_meta_box($meta_box_id, 'page', 'normal');
        }

        return;
    }

    if ($is_promo_page) {
        foreach (array_merge($home_meta_box_ids, $about_meta_box_ids, $diagnosis_meta_box_ids, $contact_meta_box_ids, $blog_list_meta_box_ids, $assessment_meta_box_ids, $other_marketing_page_boxes) as $meta_box_id) {
            remove_meta_box($meta_box_id, 'page', 'normal');
        }

        return;
    }

    if ($is_blog_list_page) {
        foreach (array_merge($home_meta_box_ids, $about_meta_box_ids, $diagnosis_meta_box_ids, $contact_meta_box_ids, $promo_meta_box_ids, $assessment_meta_box_ids, $other_marketing_page_boxes) as $meta_box_id) {
            remove_meta_box($meta_box_id, 'page', 'normal');
        }

        return;
    }

    if ($is_assessment_page) {
        foreach (array_merge($home_meta_box_ids, $about_meta_box_ids, $diagnosis_meta_box_ids, $contact_meta_box_ids, $promo_meta_box_ids, $blog_list_meta_box_ids, $other_marketing_page_boxes) as $meta_box_id) {
            remove_meta_box($meta_box_id, 'page', 'normal');
        }

        return;
    }

    foreach (array_merge($home_meta_box_ids, $about_meta_box_ids, $diagnosis_meta_box_ids, $contact_meta_box_ids, $promo_meta_box_ids, $blog_list_meta_box_ids, $assessment_meta_box_ids, $other_marketing_page_boxes) as $meta_box_id) {
        remove_meta_box($meta_box_id, 'page', 'normal');
    }
}
add_action('add_meta_boxes_page', 'eurohairlab_filter_page_specific_meta_boxes', 99);

function eurohairlab_disable_admin_metabox_reordering(string $hook_suffix): void
{
    if ($hook_suffix !== 'post.php' && $hook_suffix !== 'post-new.php') {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'page') {
        return;
    }

    wp_add_inline_style(
        'common',
        <<<'CSS'
.postbox .hndle,
.postbox .handlediv {
    cursor: default !important;
}
CSS
    );

    wp_add_inline_script(
        'postbox',
        <<<'JS'
jQuery(function ($) {
    function disableMetaBoxSorting() {
        $('.meta-box-sortables').each(function () {
            const $container = $(this);

            if ($container.hasClass('ui-sortable')) {
                try {
                    $container.sortable('destroy');
                } catch (e) {
                    $container.sortable('disable');
                }
            }
        });

        $('.postbox .hndle, .postbox .handlediv').css('cursor', 'default');
    }

    disableMetaBoxSorting();
    $(window).on('load', disableMetaBoxSorting);
    setTimeout(disableMetaBoxSorting, 0);
    setTimeout(disableMetaBoxSorting, 300);
});
JS,
        'after'
    );
}
add_action('admin_enqueue_scripts', 'eurohairlab_disable_admin_metabox_reordering');

function eurohairlab_get_meta_title(?WP_Post $post = null): string
{
    $post = $post instanceof WP_Post ? $post : get_queried_object();

    if ($post instanceof WP_Post && function_exists('rwmb_meta')) {
        $custom_title = trim((string) rwmb_meta('eh_seo_meta_title', [], $post->ID));
        if ($custom_title !== '') {
            return $custom_title;
        }
    }

    if ($post instanceof WP_Post) {
        return get_the_title($post);
    }

    return wp_get_document_title();
}

function eurohairlab_get_seo_target_post(): ?WP_Post
{
    $queried_object = get_queried_object();

    if ($queried_object instanceof WP_Post) {
        return $queried_object;
    }

    if (is_home()) {
        $posts_page_id = (int) get_option('page_for_posts');
        if ($posts_page_id > 0) {
            $posts_page = get_post($posts_page_id);
            if ($posts_page instanceof WP_Post) {
                return $posts_page;
            }
        }
    }

    return null;
}

function eurohairlab_get_canonical_url(?WP_Post $post = null): string
{
    $post = $post instanceof WP_Post ? $post : eurohairlab_get_seo_target_post();

    if ($post instanceof WP_Post) {
        $permalink = get_permalink($post);
        return is_string($permalink) ? $permalink : '';
    }

    return '';
}

function eurohairlab_filter_document_title(string $title): string
{
    $seo_post = eurohairlab_get_seo_target_post();

    if ($seo_post instanceof WP_Post) {
        return eurohairlab_get_meta_title($seo_post);
    }

    return $title;
}
add_filter('pre_get_document_title', 'eurohairlab_filter_document_title', 20);

function eurohairlab_output_meta_tags(): void
{
    $post = eurohairlab_get_seo_target_post();
    if (!$post instanceof WP_Post) {
        return;
    }

    $meta_description = eurohairlab_get_meta_description($post);
    $meta_title       = eurohairlab_get_meta_title($post);
    $og_title         = function_exists('rwmb_meta') ? trim((string) rwmb_meta('eh_seo_og_title', [], $post->ID)) : '';
    $og_description   = function_exists('rwmb_meta') ? trim((string) rwmb_meta('eh_seo_og_description', [], $post->ID)) : '';
    $noindex          = function_exists('rwmb_meta') ? (bool) rwmb_meta('eh_seo_noindex', [], $post->ID) : false;
    $og_image         = function_exists('rwmb_meta') ? rwmb_meta('eh_seo_og_image', [], $post->ID) : null;
    $og_image_url     = '';

    if (is_array($og_image)) {
        $og_image_url = $og_image['full_url'] ?? $og_image['url'] ?? '';
    } elseif (has_post_thumbnail($post)) {
        $og_image_url = (string) get_the_post_thumbnail_url($post, 'full');
    }

    if ($og_title === '') {
        $og_title = $meta_title;
    }

    if ($og_description === '') {
        $og_description = $meta_description;
    }

    $canonical = eurohairlab_get_canonical_url($post);

    echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
    if ($canonical !== '') {
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url(get_permalink($post)) . '">' . "\n";

    if ($og_image_url !== '') {
        echo '<meta property="og:image" content="' . esc_url($og_image_url) . '">' . "\n";
    }

    if ($noindex) {
        echo '<meta name="robots" content="noindex,nofollow">' . "\n";
    }
}
add_action('wp_head', 'eurohairlab_output_meta_tags', 5);

function eurohairlab_schema_markup(): void
{
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'MedicalBusiness',
        'name' => 'Eurohairlab',
        'url' => home_url('/'),
        'description' => eurohairlab_get_meta_description(),
        'image' => get_template_directory_uri() . '/assets/images/logo.webp',
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

    if (is_single()) {
        global $post;

        if (!$post instanceof WP_Post) {
            return;
        }

        $article = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title($post),
            'description' => eurohairlab_get_meta_description($post),
            'datePublished' => get_the_date(DATE_W3C, $post),
            'dateModified' => get_the_modified_date(DATE_W3C, $post),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', (int) $post->post_author),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Eurohairlab',
            ],
            'mainEntityOfPage' => get_permalink($post),
        ];

        if (has_post_thumbnail($post)) {
            $article['image'] = get_the_post_thumbnail_url($post, 'full');
        }

        echo '<script type="application/ld+json">' . wp_json_encode($article, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
add_action('wp_head', 'eurohairlab_schema_markup', 30);

function eurohairlab_output_google_tag_manager(): void
{
    if (!defined('WP_ENV') || (string) WP_ENV !== 'production') {
        return;
    }
    ?>
    <!-- Google Tag Manager -->
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-58VTQQSZ');
    </script>
    <!-- End Google Tag Manager -->
    <?php
}
add_action('wp_head', 'eurohairlab_output_google_tag_manager', 1);


// GA TAG FOR STAGING
function eurohairlab_output_google_analytics_tag(): void
{
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-M6HS4QQKB5"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-M6HS4QQKB5');
    </script>
    <?php
}
add_action('wp_head', 'eurohairlab_output_google_analytics_tag', 2);



// GA TAG FOR PROD
function eurohairlab_output_google_analytics_tag_prod(): void
{
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-G3SNWYEHTB"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-G3SNWYEHTB');
    </script>
    <?php
}
add_action('wp_head', 'eurohairlab_output_google_analytics_tag_prod', 4);




/**
 * Google Ads (gtag.js). Loaded only on production — local/dev should set WP_ENV accordingly.
 */
function eurohairlab_output_google_ads_gtag(): void
{
    if (!defined('WP_ENV') || (string) WP_ENV !== 'production') {
        return;
    }
    ?>
    <!-- Google tag (gtag.js) — Google Ads -->
    <script async src="<?php echo esc_url('https://www.googletagmanager.com/gtag/js?id=AW-18085629726'); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'AW-18085629726');
    </script>
    <?php
}
add_action('wp_head', 'eurohairlab_output_google_ads_gtag', 3);

function eurohairlab_output_google_tag_manager_noscript(): void
{
    if (!defined('WP_ENV') || (string) WP_ENV !== 'production') {
        return;
    }
    ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-58VTQQSZ"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
}
add_action('wp_body_open', 'eurohairlab_output_google_tag_manager_noscript');
