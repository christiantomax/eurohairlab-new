<?php

declare(strict_types=1);

/**
 * Resolve popup left-panel image URL from custom table row (with RWMB fallback).
 *
 * @param array<string, mixed> $row
 */
function eh_popup_resolve_panel_image_url(array $row): string
{
    $attachment_id = (int) ($row['image_attachment_id'] ?? 0);

    if ($attachment_id <= 0) {
        $post_id = (int) ($row['post_id'] ?? 0);
        if ($post_id > 0 && function_exists('rwmb_meta') && function_exists('eurohairlab_mb_attachment_id')) {
            $attachment_id = eurohairlab_mb_attachment_id(rwmb_meta('eh_popup_image', [], $post_id));
        }
    }

    if ($attachment_id <= 0) {
        return '';
    }

    if (function_exists('eurohairlab_mb_image_url')) {
        return eurohairlab_mb_image_url($attachment_id, '');
    }

    $url = wp_get_attachment_image_url($attachment_id, 'full');

    return is_string($url) && $url !== '' ? $url : (string) wp_get_attachment_url($attachment_id);
}
