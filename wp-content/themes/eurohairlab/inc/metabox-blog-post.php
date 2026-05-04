<?php

declare(strict_types=1);

add_filter('rwmb_meta_boxes', 'eurohairlab_blog_post_register_meta_boxes');

/**
 * @param array<int, array<string, mixed>> $meta_boxes
 * @return array<int, array<string, mixed>>
 */
function eurohairlab_blog_post_register_meta_boxes(array $meta_boxes): array
{
    $meta_boxes[] = [
        'title' => esc_html__('Blog (front-end)', 'eurohairlab'),
        'id' => 'eh_blog_post_section',
        'post_types' => ['post'],
        'context' => 'normal',
        'priority' => 'high',
        'fields' => [
            [
                'type' => 'single_image',
                'name' => esc_html__('Image thumbnail', 'eurohairlab'),
                'id' => 'eh_blog_image_thumbnail',
                'max_file_uploads' => 1,
                'image_size' => 'large',
            ],
            [
                'type' => 'single_image',
                'name' => esc_html__('Image cover', 'eurohairlab'),
                'id' => 'eh_blog_image_cover',
                'max_file_uploads' => 1,
                'image_size' => 'large',
            ],
            [
                'type' => 'date',
                'name' => esc_html__('Blog date', 'eurohairlab'),
                'id' => 'eh_blog_date',
                'timestamp' => true,
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_blog_title',
                'desc' => esc_html__('Shown on the blog list and article layout. Leave empty to hide the title on the front-end.', 'eurohairlab'),
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Description', 'eurohairlab'),
                'id' => 'eh_blog_description',
                'rows' => 6,
                'desc' => esc_html__('Summary for blog cards and optional intro on the article page. Main article body uses the editor below.', 'eurohairlab'),
            ],
        ],
    ];

    return $meta_boxes;
}

/**
 * Display title from Blog metabox (empty string if unset).
 */
function eurohairlab_get_blog_post_display_title(int $post_id): string
{
    if (!function_exists('rwmb_meta')) {
        return '';
    }

    $t = function_exists('eurohairlab_rwmb_page_meta')
        ? eurohairlab_rwmb_page_meta($post_id, 'eh_blog_title', [])
        : rwmb_meta('eh_blog_title', [], $post_id);

    return is_string($t) ? trim($t) : '';
}

/**
 * Description / summary from Blog metabox.
 */
function eurohairlab_get_blog_post_description(int $post_id): string
{
    if (!function_exists('rwmb_meta')) {
        return '';
    }

    $t = function_exists('eurohairlab_rwmb_page_meta')
        ? eurohairlab_rwmb_page_meta($post_id, 'eh_blog_description', [])
        : rwmb_meta('eh_blog_description', [], $post_id);

    return is_string($t) ? trim($t) : '';
}

/**
 * Formatted blog date (F j, Y) from metabox timestamp, or empty.
 */
function eurohairlab_get_blog_post_display_date(int $post_id): string
{
    if (!function_exists('rwmb_meta')) {
        return '';
    }

    $raw = rwmb_meta('eh_blog_date', [], $post_id);
    if ($raw === '' || $raw === null || $raw === false) {
        return '';
    }

    $ts = null;
    if (is_numeric($raw)) {
        $ts = (int) $raw;
    } elseif (is_string($raw) && $raw !== '') {
        $parsed = strtotime($raw);
        $ts = $parsed !== false ? $parsed : null;
    }

    if ($ts === null || $ts <= 0) {
        return '';
    }

    return date_i18n('F j, Y', $ts);
}

/**
 * Image URL for metabox single image; no fallback (empty if unset).
 */
function eurohairlab_get_blog_post_image_url(int $post_id, string $field_id): string
{
    if (!function_exists('rwmb_meta')) {
        return '';
    }

    $val = rwmb_meta($field_id, [], $post_id);

    return eurohairlab_mb_image_url($val, '');
}
