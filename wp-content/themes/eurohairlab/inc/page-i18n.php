<?php

declare(strict_types=1);

/**
 * Page EN/ID Meta Box bridge: duplicates textual RWMB fields with `_id` suffix (Bahasa Indonesia),
 * resolves values on the frontend from cookie / ?lang=, and prefills empty ID fields from EN once.
 *
 * Applies to `page`, marketing CPTs (`eh_treatment_program`, `eh_result`, `eh_promo`), and blog `post`.
 */

/** @var array<string, true> */
$GLOBALS['eurohairlab_i18n_base_field_ids'] = [];

/**
 * @return list<string>
 */
function eurohairlab_rwmb_i18n_post_types(): array
{
    return ['page', 'eh_treatment_program', 'eh_result', 'eh_promo', 'post'];
}

/**
 * Public language for marketing pages: `en` (default) or `id`.
 */
function eurohairlab_get_public_lang(): string
{
    if (isset($_GET['lang'])) {
        $g = sanitize_key((string) wp_unslash($_GET['lang']));
        if (in_array($g, ['en', 'id'], true)) {
            return $g;
        }
    }

    if (!empty($_COOKIE['eurohairlab_lang'])) {
        $c = sanitize_key((string) wp_unslash($_COOKIE['eurohairlab_lang']));
        if (in_array($c, ['en', 'id'], true)) {
            return $c;
        }
    }

    return 'en';
}

function eurohairlab_meta_value_nonempty(mixed $value): bool
{
    if ($value === null || $value === false) {
        return false;
    }
    if (is_string($value)) {
        return trim($value) !== '';
    }
    if (is_array($value)) {
        return $value !== [];
    }

    return true;
}

/**
 * Read RWMB meta for page content with Indonesian fallback: uses `{key}_id` when lang is `id` and that value is non-empty.
 */
function eurohairlab_rwmb_page_meta(int|string $post_id, string $field_id, array $args = []): mixed
{
    if (!function_exists('rwmb_meta')) {
        return null;
    }

    $base_ids = $GLOBALS['eurohairlab_i18n_base_field_ids'] ?? [];
    $has_id_variant = isset($base_ids[$field_id]);

    if (!$has_id_variant || str_ends_with($field_id, '_id') || eurohairlab_get_public_lang() !== 'id') {
        return rwmb_meta($field_id, $args, $post_id);
    }

    $id_field = $field_id . '_id';
    $id_val = rwmb_meta($id_field, $args, $post_id);
    if (eurohairlab_meta_value_nonempty($id_val)) {
        return $id_val;
    }

    return rwmb_meta($field_id, $args, $post_id);
}

/**
 * Category label for blog UI: Indonesian term meta when lang is `id`, else core name.
 */
function eurohairlab_get_category_display_name(WP_Term $term): string
{
    $raw_name = $term->name ?? '';
    $name = is_string($raw_name) ? $raw_name : (is_scalar($raw_name) ? (string) $raw_name : '');
    if (eurohairlab_get_public_lang() !== 'id' || !function_exists('rwmb_meta')) {
        return $name;
    }

    $id = rwmb_meta('eh_category_display_name_id', ['object_type' => 'term'], $term->term_id);
    if (is_string($id) && trim($id) !== '') {
        return trim($id);
    }

    return $name;
}

/**
 * URL to set public language via `?lang=` (cookie is set on next response).
 */
function eurohairlab_get_public_lang_switch_url(string $lang): string
{
    $lang = $lang === 'id' ? 'id' : 'en';
    $relative = remove_query_arg('lang');
    if (!is_string($relative) || $relative === '') {
        $relative = '/';
    }
    if ($relative[0] !== '/') {
        $relative = '/' . $relative;
    }

    return esc_url(add_query_arg('lang', $lang, home_url($relative)));
}

add_action('init', static function (): void {
    if (is_admin()) {
        return;
    }
    if (!isset($_GET['lang'])) {
        return;
    }
    $l = sanitize_key((string) wp_unslash($_GET['lang']));
    if (!in_array($l, ['en', 'id'], true)) {
        return;
    }
    if (headers_sent()) {
        return;
    }
    $path = (defined('COOKIEPATH') && is_string(COOKIEPATH) && COOKIEPATH !== '') ? COOKIEPATH : '/';
    setcookie('eurohairlab_lang', $l, [
        'expires' => time() + YEAR_IN_SECONDS,
        'path' => $path,
        'secure' => is_ssl(),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['eurohairlab_lang'] = $l;
}, 0);

/**
 * @param array<string, mixed> $field
 * @return array<string, mixed>|null
 */
function eurohairlab_mb_duplicate_textual_field_for_id_lang(array $field): ?array
{
    $type = isset($field['type']) ? (string) $field['type'] : '';
    $id = isset($field['id']) ? (string) $field['id'] : '';
    if ($id === '' || str_ends_with($id, '_id')) {
        return null;
    }
    if (str_starts_with($id, 'eh_seo_')) {
        return null;
    }

    $textual = ['text', 'textarea', 'wysiwyg'];
    if (!in_array($type, $textual, true)) {
        return null;
    }

    $json = wp_json_encode($field, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return null;
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return null;
    }
    /** @var array<string, mixed> $dup */
    $dup = $decoded;

    $dup['id'] = $id . '_id';
    $name = isset($field['name']) ? (string) $field['name'] : $id;
    $dup['name'] = $name . ' (Bahasa Indonesia)';
    unset($dup['std'], $dup['std_callback'], $dup['placeholder']);

    $GLOBALS['eurohairlab_i18n_base_field_ids'][$id] = true;

    return $dup;
}

/**
 * @param list<array<string, mixed>> $fields
 * @return list<array<string, mixed>>
 */
function eurohairlab_mb_inject_id_lang_fields_after_each(array $fields): array
{
    $out = [];
    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $out[] = $field;
        $dup = eurohairlab_mb_duplicate_textual_field_for_id_lang($field);
        if ($dup !== null) {
            $out[] = $dup;
        }
    }

    return $out;
}

add_filter('rwmb_meta_boxes', static function (array $meta_boxes): array {
    $GLOBALS['eurohairlab_i18n_base_field_ids'] = [];

    $i18n_types = eurohairlab_rwmb_i18n_post_types();

    foreach ($meta_boxes as $i => $box) {
        if (!is_array($box)) {
            continue;
        }
        $types = $box['post_types'] ?? [];
        if (!is_array($types) || array_intersect($types, $i18n_types) === []) {
            continue;
        }
        if (empty($box['fields']) || !is_array($box['fields'])) {
            continue;
        }
        $meta_boxes[$i]['fields'] = eurohairlab_mb_inject_id_lang_fields_after_each($box['fields']);
    }

    return $meta_boxes;
}, 200);

add_action('admin_init', static function (): void {
    if (!is_admin()) {
        return;
    }
    global $pagenow;
    if ($pagenow !== 'post.php' || empty($_GET['post'])) {
        return;
    }
    $post_id = (int) wp_unslash($_GET['post']);
    $allowed = eurohairlab_rwmb_i18n_post_types();
    if ($post_id <= 0 || !in_array(get_post_type($post_id), $allowed, true) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    eurohairlab_prefill_page_id_meta_from_en_once($post_id);
}, 999);

add_action('admin_footer', static function (): void {
    if (!is_admin()) {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $allowed = eurohairlab_rwmb_i18n_post_types();
    if (!$screen instanceof WP_Screen || $screen->base !== 'post' || !in_array($screen->post_type, $allowed, true)) {
        return;
    }
    $post_id = isset($_GET['post']) ? (int) wp_unslash($_GET['post']) : 0;
    if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
        return;
    }
    eurohairlab_prefill_page_id_meta_from_en_once($post_id);
}, 1);

add_action('admin_init', static function (): void {
    if (!is_admin()) {
        return;
    }
    global $pagenow;
    if ($pagenow !== 'term.php' || empty($_GET['tag_ID']) || empty($_GET['taxonomy'])) {
        return;
    }
    $tax = sanitize_key((string) wp_unslash($_GET['taxonomy']));
    if ($tax !== 'category') {
        return;
    }
    $term_id = (int) wp_unslash($_GET['tag_ID']);
    if ($term_id <= 0 || !current_user_can('edit_term', $term_id)) {
        return;
    }
    eurohairlab_prefill_category_display_name_id_once($term_id);
}, 998);

add_action('admin_footer', static function (): void {
    if (!is_admin()) {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen instanceof WP_Screen || $screen->taxonomy !== 'category' || $screen->base !== 'term') {
        return;
    }
    $term_id = isset($_GET['tag_ID']) ? (int) wp_unslash($_GET['tag_ID']) : 0;
    if ($term_id <= 0 || !current_user_can('edit_term', $term_id)) {
        return;
    }
    eurohairlab_prefill_category_display_name_id_once($term_id);
}, 1);

function eurohairlab_prefill_page_id_meta_from_en_once(int $post_id): void
{
    if (!function_exists('rwmb_meta') || !function_exists('rwmb_set_meta')) {
        return;
    }

    $base_ids = $GLOBALS['eurohairlab_i18n_base_field_ids'] ?? [];
    if ($base_ids === []) {
        return;
    }

    foreach (array_keys($base_ids) as $base) {
        $id_key = $base . '_id';
        if (metadata_exists('post', $post_id, $id_key)) {
            continue;
        }
        $en_val = rwmb_meta($base, [], $post_id);
        if (!eurohairlab_meta_value_nonempty($en_val)) {
            continue;
        }
        $pref = apply_filters('eurohairlab_prefill_id_from_en', $en_val, $base, $post_id);
        rwmb_set_meta($post_id, $id_key, $pref);
    }
}

function eurohairlab_prefill_category_display_name_id_once(int $term_id): void
{
    if (!function_exists('rwmb_meta') || !function_exists('rwmb_set_meta')) {
        return;
    }

    if (metadata_exists('term', $term_id, 'eh_category_display_name_id')) {
        return;
    }

    $term = get_term($term_id, 'category');
    if (!$term instanceof WP_Term) {
        return;
    }

    $name = isset($term->name) && is_string($term->name) ? $term->name : '';
    if ($name === '') {
        return;
    }

    rwmb_set_meta($term_id, 'eh_category_display_name_id', $name, ['object_type' => 'term']);
}

/**
 * Default EN→ID prefill: copy English (editors refine). Override via `eurohairlab_prefill_id_from_en` for machine translation.
 */
add_filter('eurohairlab_prefill_id_from_en', static function (mixed $en_value, string $base_key = '', int $post_id = 0): mixed {
    unset($base_key, $post_id);

    return $en_value;
}, 10, 3);
