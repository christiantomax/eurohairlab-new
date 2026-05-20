<?php

declare(strict_types=1);

/**
 * Resize, compress, and convert uploaded images to WebP for theme-managed media.
 */

const EUROHAIRLAB_POPUP_IMAGE_MAX_WIDTH = 960;
const EUROHAIRLAB_POPUP_IMAGE_MAX_HEIGHT = 1000;
const EUROHAIRLAB_IMAGE_WEBP_QUALITY = 70;

/**
 * @return array{max_width: int, max_height: int, quality: int}
 */
function eurohairlab_popup_image_optimize_options(): array
{
    return [
        'max_width' => EUROHAIRLAB_POPUP_IMAGE_MAX_WIDTH,
        'max_height' => EUROHAIRLAB_POPUP_IMAGE_MAX_HEIGHT,
        'quality' => EUROHAIRLAB_IMAGE_WEBP_QUALITY,
    ];
}

function eurohairlab_is_eh_popup_admin_media_context(): bool
{
    if (!is_admin()) {
        return false;
    }

    $post_id = 0;
    if (!empty($_POST['post_id'])) {
        $post_id = (int) wp_unslash($_POST['post_id']);
    } elseif (!empty($_REQUEST['post_id'])) {
        $post_id = (int) wp_unslash($_REQUEST['post_id']);
    } elseif (!empty($_POST['post_ID'])) {
        $post_id = (int) wp_unslash($_POST['post_ID']);
    }

    if ($post_id > 0 && get_post_type($post_id) === 'eh_popup') {
        return true;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    return $screen instanceof WP_Screen
        && ($screen->post_type === 'eh_popup' || $screen->id === 'eh_popup');
}

/**
 * Resize (fit within bounds), encode WebP at given quality, replace attachment file.
 */
function eurohairlab_optimize_attachment_to_webp(int $attachment_id, array $options = []): bool
{
    if ($attachment_id <= 0) {
        return false;
    }

    $max_width = max(1, (int) ($options['max_width'] ?? EUROHAIRLAB_POPUP_IMAGE_MAX_WIDTH));
    $max_height = max(1, (int) ($options['max_height'] ?? EUROHAIRLAB_POPUP_IMAGE_MAX_HEIGHT));
    $quality = min(100, max(1, (int) ($options['quality'] ?? EUROHAIRLAB_IMAGE_WEBP_QUALITY)));
    $profile = isset($options['profile']) ? (string) $options['profile'] : 'generic';

    $path = get_attached_file($attachment_id);
    if (!is_string($path) || $path === '' || !is_readable($path)) {
        return false;
    }

    $mime = get_post_mime_type($attachment_id);
    if (!is_string($mime) || !wp_match_mime_types('image', $mime)) {
        return false;
    }

    $stored_profile = get_post_meta($attachment_id, '_eh_image_optimize_profile', true);
    $stored_quality = (int) get_post_meta($attachment_id, '_eh_webp_quality', true);
    if (
        get_post_meta($attachment_id, '_eh_webp_optimized', true) === '1'
        && $stored_profile === $profile
        && $stored_quality === $quality
        && str_ends_with(strtolower($path), '.webp')
    ) {
        return true;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';

    $editor = wp_get_image_editor($path);
    if (is_wp_error($editor)) {
        return false;
    }

    if (!$editor->supports_mime_type('image/webp')) {
        return false;
    }

    $size = $editor->get_size();
    if (!empty($size['width']) && !empty($size['height'])) {
        $needs_resize = $size['width'] > $max_width || $size['height'] > $max_height;
        if ($needs_resize) {
            $resized = $editor->resize($max_width, $max_height, false);
            if (is_wp_error($resized)) {
                return false;
            }
        }
    }

    $editor->set_quality($quality);

    $upload_dir = wp_upload_dir();
    if (!empty($upload_dir['error'])) {
        return false;
    }

    $file_info = pathinfo($path);
    $base_name = $file_info['filename'] ?? ('image-' . $attachment_id);
    $target_dir = $file_info['dirname'] ?? $upload_dir['path'];
    $target_path = trailingslashit($target_dir) . wp_unique_filename($target_dir, $base_name . '.webp');

    $saved = $editor->save($target_path, 'image/webp');
    if (is_wp_error($saved) || empty($saved['path'])) {
        return false;
    }

    $new_path = (string) $saved['path'];
    $previous_path = $path;

    update_attached_file($attachment_id, $new_path);
    wp_update_post([
        'ID' => $attachment_id,
        'post_mime_type' => 'image/webp',
    ]);

    $metadata = wp_generate_attachment_metadata($attachment_id, $new_path);
    if (is_array($metadata) && $metadata !== []) {
        wp_update_attachment_metadata($attachment_id, $metadata);
    }

    if ($previous_path !== $new_path && is_file($previous_path)) {
        wp_delete_file($previous_path);
    }

    update_post_meta($attachment_id, '_eh_webp_optimized', '1');
    update_post_meta($attachment_id, '_eh_webp_quality', (string) $quality);
    update_post_meta($attachment_id, '_eh_image_optimize_profile', $profile);

    return true;
}

function eurohairlab_optimize_popup_panel_attachment(int $attachment_id): bool
{
    return eurohairlab_optimize_attachment_to_webp(
        $attachment_id,
        array_merge(eurohairlab_popup_image_optimize_options(), ['profile' => 'popup_panel'])
    );
}

add_action('add_attachment', static function (int $attachment_id): void {
    if (!is_admin() || !eurohairlab_is_eh_popup_admin_media_context()) {
        return;
    }

    eurohairlab_optimize_popup_panel_attachment($attachment_id);
}, 25);
