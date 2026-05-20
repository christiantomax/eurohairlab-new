<?php

declare(strict_types=1);

/**
 * Admin CPT for popup metabox editing (data synced to wp_eh_popups).
 */

add_action('init', static function (): void {
    register_post_type(
        'eh_popup',
        [
            'labels' => [
                'name' => esc_html__('Popups', 'eurohairlab'),
                'singular_name' => esc_html__('Popup', 'eurohairlab'),
                'add_new' => esc_html__('Add New', 'eurohairlab'),
                'add_new_item' => esc_html__('Add Popup', 'eurohairlab'),
                'edit_item' => esc_html__('Edit Popup', 'eurohairlab'),
                'menu_name' => esc_html__('Popups', 'eurohairlab'),
            ],
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-welcome-view-site',
            'menu_position' => 29,
            'supports' => ['title'],
            'has_archive' => false,
            'rewrite' => false,
            'capability_type' => 'post',
        ]
    );
}, 5);

add_action('init', static function (): void {
    if (!is_admin() || get_option('eh_popup_default_post_created') === '1') {
        return;
    }

    $post_id = eh_popup_ensure_default_cpt_post();
    if ($post_id > 0) {
        eh_popup_seed_default_meta($post_id);
    }
    update_option('eh_popup_default_post_created', '1');
}, 20);

add_filter('manage_eh_popup_posts_columns', static function (array $columns): array {
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['eh_popup_status'] = esc_html__('Status', 'eurohairlab');
            $new['eh_popup_views'] = esc_html__('Views', 'eurohairlab');
        }
    }

    return $new;
});

add_action('manage_eh_popup_posts_custom_column', static function (string $column, int $post_id): void {
    $row = eh_popup_get_row_by_post_id($post_id);
    if ($column === 'eh_popup_status') {
        $active = $row && !empty($row['is_active']);
        echo $active
            ? '<span style="color:#2271b1;">' . esc_html__('Active', 'eurohairlab') . '</span>'
            : esc_html__('Inactive', 'eurohairlab');
    }
    if ($column === 'eh_popup_views') {
        echo esc_html((string) ($row['views'] ?? 0));
    }
}, 10, 2);
