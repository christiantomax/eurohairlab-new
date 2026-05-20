<?php

declare(strict_types=1);

/**
 * Meta Box fields for eh_popup CPT (EN + ID). Synced to wp_eh_popups on save.
 */

add_filter('rwmb_meta_boxes', 'eh_popup_register_meta_boxes');

function eh_popup_register_meta_boxes(array $meta_boxes): array
{
    $headline_wysiwyg_opts = [
        'textarea_rows' => 6,
        'teeny' => false,
        'media_buttons' => false,
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Popup — Content', 'eurohairlab'),
        'id' => 'eh_popup_content_section',
        'post_types' => ['eh_popup'],
        'context' => 'normal',
        'priority' => 'high',
        'fields' => [
            [
                'type' => 'switch',
                'name' => esc_html__('Active', 'eurohairlab'),
                'id' => 'eh_popup_active',
                'std' => 1,
                'style' => 'rounded',
            ],
            [
                'type' => 'single_image',
                'name' => esc_html__('Left panel image', 'eurohairlab'),
                'id' => 'eh_popup_image',
                'desc' => sprintf(
                    /* translators: 1: max width px, 2: max height px, 3: quality percent */
                    esc_html__(
                        'Shown on the left side of the modal (desktop). On save, images are resized to max %1$d×%2$d px, compressed to %3$d%%, and converted to WebP.',
                        'eurohairlab'
                    ),
                    EUROHAIRLAB_POPUP_IMAGE_MAX_WIDTH,
                    EUROHAIRLAB_POPUP_IMAGE_MAX_HEIGHT,
                    EUROHAIRLAB_IMAGE_WEBP_QUALITY
                ),
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Headline (English)', 'eurohairlab'),
                'id' => 'eh_popup_headline_en',
                'options' => $headline_wysiwyg_opts,
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Headline (Bahasa Indonesia)', 'eurohairlab'),
                'id' => 'eh_popup_headline_id',
                'options' => $headline_wysiwyg_opts,
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button text (English)', 'eurohairlab'),
                'id' => 'eh_popup_cta_text_en',
                'std' => 'Diagnose Now',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button text (Bahasa Indonesia)', 'eurohairlab'),
                'id' => 'eh_popup_cta_text_id',
            ],
            [
                'type' => 'url',
                'name' => esc_html__('Button URL', 'eurohairlab'),
                'id' => 'eh_popup_cta_url',
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Popup — Display', 'eurohairlab'),
        'id' => 'eh_popup_display_section',
        'post_types' => ['eh_popup'],
        'context' => 'normal',
        'priority' => 'default',
        'closed' => true,
        'fields' => [
            [
                'type' => 'number',
                'name' => esc_html__('Width (%)', 'eurohairlab'),
                'id' => 'eh_popup_width_percent',
                'min' => 20,
                'max' => 100,
                'std' => 50,
            ],
            [
                'type' => 'number',
                'name' => esc_html__('Height (px)', 'eurohairlab'),
                'id' => 'eh_popup_height_px',
                'min' => 200,
                'max' => 2000,
                'std' => 500,
            ],
            [
                'type' => 'number',
                'name' => esc_html__('Overlay opacity', 'eurohairlab'),
                'id' => 'eh_popup_overlay_opacity',
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'std' => 0.5,
            ],
            [
                'type' => 'number',
                'name' => esc_html__('Open delay (ms)', 'eurohairlab'),
                'id' => 'eh_popup_delay_ms',
                'min' => 0,
                'std' => 0,
            ],
            [
                'type' => 'select',
                'name' => esc_html__('Trigger', 'eurohairlab'),
                'id' => 'eh_popup_trigger',
                'options' => [
                    'pageLoaded' => esc_html__('When page loads', 'eurohairlab'),
                ],
                'std' => 'pageLoaded',
            ],
            [
                'type' => 'post',
                'name' => esc_html__('Show on pages', 'eurohairlab'),
                'id' => 'eh_popup_show_page_ids',
                'post_type' => 'page',
                'field_type' => 'select_advanced',
                'multiple' => true,
                'placeholder' => esc_html__('Select pages…', 'eurohairlab'),
                'desc' => esc_html__(
                    'Popup appears only on the selected pages. If none are selected, the Home page is used by default.',
                    'eurohairlab'
                ),
            ],
            [
                'type' => 'switch',
                'name' => esc_html__('Close on Escape key', 'eurohairlab'),
                'id' => 'eh_popup_close_on_esc',
                'std' => 1,
                'style' => 'rounded',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Custom CSS', 'eurohairlab'),
                'id' => 'eh_popup_custom_css',
                'rows' => 12,
            ],
        ],
    ];

    return $meta_boxes;
}

/**
 * Build row payload from RWMB post meta.
 *
 * @return array<string, mixed>
 */
function eh_popup_collect_meta_from_post(int $post_id): array
{
    $get = static function (string $key, mixed $default = '') use ($post_id): mixed {
        if (!function_exists('rwmb_meta')) {
            return $default;
        }

        return rwmb_meta($key, [], $post_id);
    };

    $post = get_post($post_id);
    $title = $post instanceof WP_Post ? $post->post_title : '';

    $show_on = $get('eh_popup_show_page_ids', []);
    if (!is_array($show_on)) {
        $show_on = $show_on ? [(int) $show_on] : [];
    }

    return [
        'admin_title' => $title,
        'is_active' => (int) $get('eh_popup_active', 0),
        'headline_en' => (string) $get('eh_popup_headline_en', ''),
        'headline_id' => (string) $get('eh_popup_headline_id', ''),
        'cta_text_en' => (string) $get('eh_popup_cta_text_en', ''),
        'cta_text_id' => (string) $get('eh_popup_cta_text_id', ''),
        'cta_url' => (string) $get('eh_popup_cta_url', ''),
        'image_attachment_id' => function_exists('eurohairlab_mb_attachment_id')
            ? eurohairlab_mb_attachment_id($get('eh_popup_image', 0))
            : (int) $get('eh_popup_image', 0),
        'overlay_opacity' => (float) $get('eh_popup_overlay_opacity', 0.5),
        'delay_ms' => (int) $get('eh_popup_delay_ms', 0),
        'trigger_event' => (string) $get('eh_popup_trigger', 'pageLoaded'),
        'show_page_ids' => $show_on,
        'close_on_esc' => (int) $get('eh_popup_close_on_esc', 1),
        'custom_css' => (string) $get('eh_popup_custom_css', ''),
        'popup_width_percent' => (int) $get('eh_popup_width_percent', 50),
        'popup_height_px' => (int) $get('eh_popup_height_px', 500),
    ];
}

function eh_popup_sync_post_to_table(int $post_id): void
{
    if (get_post_type($post_id) !== 'eh_popup') {
        return;
    }

    if (function_exists('rwmb_meta') && function_exists('eurohairlab_optimize_popup_panel_attachment')) {
        $image_id = function_exists('eurohairlab_mb_attachment_id')
            ? eurohairlab_mb_attachment_id(rwmb_meta('eh_popup_image', [], $post_id))
            : (int) rwmb_meta('eh_popup_image', [], $post_id);
        if ($image_id > 0) {
            eurohairlab_optimize_popup_panel_attachment($image_id);
        }
    }

    $data = eh_popup_collect_meta_from_post($post_id);
    eh_popup_upsert_from_post($post_id, $data);
}

add_action('save_post_eh_popup', static function (int $post_id): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    eh_popup_sync_post_to_table($post_id);
}, 20, 1);

add_action('rwmb_after_save_post', static function (int $post_id): void {
    if (get_post_type($post_id) === 'eh_popup') {
        eh_popup_sync_post_to_table($post_id);
    }
}, 20, 1);

/**
 * Prefill Show on pages with Home when the field has never been saved.
 *
 * @param mixed $value
 * @return mixed
 */
add_filter('rwmb_eh_popup_show_page_ids_value', static function ($value, array $field, array $args) {
    unset($field);

    if (!empty($value)) {
        return $value;
    }

    $post_id = isset($args['object_id']) ? (int) $args['object_id'] : 0;
    if ($post_id > 0 && metadata_exists('post', $post_id, 'eh_popup_show_page_ids')) {
        return $value;
    }

    $home_id = eh_popup_get_default_home_page_id();

    return $home_id > 0 ? [$home_id] : $value;
}, 10, 3);

/**
 * Migrate legacy exclude meta → show on pages (default Home).
 */
add_action('admin_init', static function (): void {
    if (get_option('eh_popup_exclude_to_show_meta_v1') === '1') {
        return;
    }

    $popup_posts = get_posts([
        'post_type' => 'eh_popup',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    $default_show = array_filter([eh_popup_get_default_home_page_id()]);

    foreach ($popup_posts as $popup_post_id) {
        $popup_post_id = (int) $popup_post_id;
        if (!metadata_exists('post', $popup_post_id, 'eh_popup_show_page_ids') && function_exists('rwmb_set_meta')) {
            $legacy_exclude = get_post_meta($popup_post_id, 'eh_popup_exclude_page_ids', true);
            if ($legacy_exclude === '' || $legacy_exclude === false || $legacy_exclude === []) {
                if ($default_show !== []) {
                    rwmb_set_meta($popup_post_id, 'eh_popup_show_page_ids', $default_show);
                }
            }
        }

        delete_post_meta($popup_post_id, 'eh_popup_exclude_page_ids');
        eh_popup_sync_post_to_table($popup_post_id);
    }

    update_option('eh_popup_exclude_to_show_meta_v1', '1');
}, 98);

/**
 * One-time: re-sync popup rows so image_attachment_id is stored correctly (RWMB array → int).
 */
add_action('admin_init', static function (): void {
    if (!current_user_can('edit_posts') || get_option('eh_popup_image_id_resync_v1') === '1') {
        return;
    }

    $popup_posts = get_posts([
        'post_type' => 'eh_popup',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    foreach ($popup_posts as $popup_post_id) {
        eh_popup_sync_post_to_table((int) $popup_post_id);
    }

    update_option('eh_popup_image_id_resync_v1', '1');
}, 99);
