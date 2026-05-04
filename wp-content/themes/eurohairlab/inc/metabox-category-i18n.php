<?php

declare(strict_types=1);

/**
 * Optional Indonesian display name for blog categories (term meta via Meta Box).
 */

add_filter('rwmb_meta_boxes', static function (array $meta_boxes): array {
    $meta_boxes[] = [
        'title' => esc_html__('Blog — Indonesian', 'eurohairlab'),
        'id' => 'eh_category_blog_id_section',
        'taxonomies' => ['category'],
        'fields' => [
            [
                'type' => 'text',
                'name' => esc_html__('Category display name (Bahasa Indonesia)', 'eurohairlab'),
                'id' => 'eh_category_display_name_id',
                'desc' => esc_html__('Shown when the site language is Indonesian. Leave empty to use the default category name.', 'eurohairlab'),
            ],
        ],
    ];

    return $meta_boxes;
}, 25);
