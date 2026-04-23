<?php

declare(strict_types=1);

/**
 * CPTs: Treatment Programs, Results, Promo — admin listing + Meta Box fields.
 */

add_action('init', 'eurohairlab_register_marketing_cpts', 5);

function eurohairlab_register_marketing_cpts(): void
{
    register_post_type(
        'eh_treatment_program',
        [
            'labels' => [
                'name' => esc_html__('Treatment Programs', 'eurohairlab'),
                'singular_name' => esc_html__('Treatment Program', 'eurohairlab'),
                'add_new' => esc_html__('Add New', 'eurohairlab'),
                'add_new_item' => esc_html__('Add Treatment Program', 'eurohairlab'),
                'edit_item' => esc_html__('Edit Treatment Program', 'eurohairlab'),
                'view_item' => esc_html__('View Treatment Program', 'eurohairlab'),
                'search_items' => esc_html__('Search Treatment Programs', 'eurohairlab'),
                'not_found' => esc_html__('No programs found', 'eurohairlab'),
                'menu_name' => esc_html__('Treatment Programs', 'eurohairlab'),
            ],
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-grid-view',
            'menu_position' => 26,
            'supports' => ['title', 'thumbnail', 'page-attributes'],
            'has_archive' => false,
            'rewrite' => false,
            'capability_type' => 'post',
        ]
    );

    register_post_type(
        'eh_result',
        [
            'labels' => [
                'name' => esc_html__('Results', 'eurohairlab'),
                'singular_name' => esc_html__('Result', 'eurohairlab'),
                'add_new' => esc_html__('Add New', 'eurohairlab'),
                'add_new_item' => esc_html__('Add Result', 'eurohairlab'),
                'edit_item' => esc_html__('Edit Result', 'eurohairlab'),
                'menu_name' => esc_html__('Results', 'eurohairlab'),
            ],
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-format-gallery',
            'menu_position' => 27,
            'supports' => ['title', 'thumbnail', 'page-attributes'],
            'has_archive' => false,
            'rewrite' => false,
        ]
    );

    register_post_type(
        'eh_promo',
        [
            'labels' => [
                'name' => esc_html__('Promo', 'eurohairlab'),
                'singular_name' => esc_html__('Promo', 'eurohairlab'),
                'add_new' => esc_html__('Add New', 'eurohairlab'),
                'add_new_item' => esc_html__('Add Promo', 'eurohairlab'),
                'edit_item' => esc_html__('Edit Promo', 'eurohairlab'),
                'menu_name' => esc_html__('Promo', 'eurohairlab'),
            ],
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-megaphone',
            'menu_position' => 28,
            'supports' => ['title', 'thumbnail', 'page-attributes'],
            'has_archive' => false,
            'rewrite' => false,
        ]
    );
}

add_filter('rwmb_meta_boxes', 'eurohairlab_marketing_cpts_register_meta_boxes');

function eurohairlab_marketing_cpts_register_meta_boxes(array $meta_boxes): array
{
    $wysiwyg_opts = [
        'textarea_rows' => 8,
        'teeny' => false,
        'media_buttons' => true,
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Program content', 'eurohairlab'),
        'id' => 'eh_tp_content_section',
        'post_types' => ['eh_treatment_program'],
        'context' => 'normal',
        'priority' => 'high',
        'fields' => [
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Paragraph', 'eurohairlab'),
                'id' => 'eh_tp_paragraph',
                'options' => $wysiwyg_opts,
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Detail includes program', 'eurohairlab'),
                'id' => 'eh_tp_detail_includes',
                'options' => $wysiwyg_opts,
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Result content', 'eurohairlab'),
        'id' => 'eh_result_content_section',
        'post_types' => ['eh_result'],
        'context' => 'normal',
        'priority' => 'high',
        'fields' => [
            [
                'type' => 'text',
                'name' => esc_html__('Card title', 'eurohairlab'),
                'id' => 'eh_result_card_title',
                'desc' => esc_html__('Primary heading shown on the result card and in the modal. If empty, the post title is used.', 'eurohairlab'),
            ],
            [
                'type' => 'image_advanced',
                'name' => esc_html__('Image collection', 'eurohairlab'),
                'id' => 'eh_result_gallery',
                'max_status' => false,
                'desc' => esc_html__('Order matters: the first image is Before, the second is After. Extra images are available in the modal viewer.', 'eurohairlab'),
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Short description', 'eurohairlab'),
                'id' => 'eh_result_short_description',
                'rows' => 4,
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Testimoni', 'eurohairlab'),
                'id' => 'eh_result_testimonial',
                'rows' => 5,
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Sub title', 'eurohairlab'),
                'id' => 'eh_result_subtitle',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Sub description', 'eurohairlab'),
                'id' => 'eh_result_sub_description',
                'rows' => 6,
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Promo content', 'eurohairlab'),
        'id' => 'eh_promo_item_section',
        'post_types' => ['eh_promo'],
        'context' => 'normal',
        'priority' => 'high',
        'fields' => [
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Description', 'eurohairlab'),
                'id' => 'eh_promo_item_description',
                'options' => $wysiwyg_opts,
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button label', 'eurohairlab'),
                'id' => 'eh_promo_item_button_label',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button href', 'eurohairlab'),
                'id' => 'eh_promo_item_button_href',
            ],
        ],
    ];

    return $meta_boxes;
}

add_action('admin_init', 'eurohairlab_seed_marketing_cpts_once');

function eurohairlab_seed_marketing_cpts_once(): void
{
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    if (get_option('eh_marketing_cpts_seeded')) {
        return;
    }

    if (!function_exists('rwmb_set_meta') || !function_exists('eurohairlab_import_theme_asset_to_media_library')) {
        return;
    }

    $theme_dir = get_template_directory();
    $figma = $theme_dir . '/assets/images/figma';

    $tp_defs = [
        ['slug' => 'program-korean', 'title' => 'Korean Scalp Ritual', 'img' => 'treatment-program-1.webp', 'summary' => '<p>A foundational treatment series focused on deep cleansing and scalp reset using advanced Korean techniques.</p>', 'detail' => '<p><strong>Includes:</strong></p><ul><li>Scalp Detox</li><li>Scalp Revival</li></ul>'],
        ['slug' => 'program-scalpfirst', 'title' => 'ScalpFirst™ Therapy', 'img' => 'treatment-program-2.webp', 'summary' => '<p>A targeted therapy range designed to restore and balance specific scalp conditions with clinical precision.</p>', 'detail' => '<p><strong>Includes:</strong></p><ul><li>Scalp Balance</li><li>Scalp Relief</li><li>Scalp Calm</li><li>Scalp Defense</li></ul>'],
        ['slug' => 'program-regan', 'title' => 'Hair Regan Protocol', 'img' => 'treatment-technology.webp', 'summary' => '<p>A clinical-grade protocol designed to treat hair loss at the root and support long-term regeneration based on diagnosis.</p>', 'detail' => '<p><strong>Includes:</strong></p><ul><li>ReGen Activ</li><li>ReGen Clear</li><li>ReGen Boost</li><li>ReGen Pure</li><li>ReGen Neo</li><li>ReGen Longevity</li><li>ReGen Shield</li></ul>'],
        ['slug' => 'program-booster', 'title' => 'Regan Booster', 'img' => 'treatment-program-3.webp', 'summary' => '<p>A targeted therapy range designed to restore and balance specific scalp conditions with clinical precision.</p>', 'detail' => '<p><strong>Includes:</strong></p><ul><li>Booster Exoscalp</li><li>Booster Secretome</li><li>Booster AQ Complex</li><li>Booster HairPlus</li></ul>'],
    ];

    foreach ($tp_defs as $i => $def) {
        $pid = wp_insert_post(
            [
                'post_type' => 'eh_treatment_program',
                'post_status' => 'publish',
                'post_title' => $def['title'],
                'post_name' => $def['slug'] ?? sanitize_title($def['title']),
                'menu_order' => $i,
            ],
            true
        );
        if (is_wp_error($pid) || !$pid) {
            continue;
        }
        $path = $figma . '/' . $def['img'];
        if (is_readable($path)) {
            $aid = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/' . $def['img'], (int) $pid);
            if ($aid) {
                set_post_thumbnail((int) $pid, $aid);
            }
        }
        rwmb_set_meta((int) $pid, 'eh_tp_paragraph', $def['summary']);
        rwmb_set_meta((int) $pid, 'eh_tp_detail_includes', $def['detail']);
    }

    $res_seed = [
        ['before' => 'results-1-before.webp', 'after' => 'results-1-after.webp', 'card_title' => 'Female, 40s', 'short_description' => '4 Months on Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'I had severe hair thinning after stress and hormonal imbalance. After the program, my hair density improved significantly.', 'sub_description' => 'This case study highlights the importance of diagnosis-first treatment and consistent scalp follow-up.'],
        ['before' => 'results-2-before.webp', 'after' => 'results-2-after.webp', 'card_title' => 'Female, 25', 'short_description' => '4 Months on Supplements & Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'The scalp-first diagnosis helped me understand the real cause of my thinning and the results followed.', 'sub_description' => 'A structured plan makes it easier to track progress and maintain momentum.'],
        ['before' => 'results-3-before.webp', 'after' => 'results-3-after.webp', 'card_title' => 'Female, 40s', 'short_description' => '4 Months on Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'My shedding reduced gradually and the density along my hairline started to look fuller.', 'sub_description' => 'Visible changes are supported by ongoing scalp correction and program adjustment.'],
        ['before' => 'results-2-before.webp', 'after' => 'results-2-after.webp', 'card_title' => 'Female, 25', 'short_description' => '4 Months on Supplements & Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'The treatment plan felt more precise because everything started from diagnosis.', 'sub_description' => 'Progress becomes measurable when the plan is built around the scalp condition.'],
        ['before' => 'results-3-before.webp', 'after' => 'results-3-after.webp', 'card_title' => 'Female, 40s', 'short_description' => '4 Months on Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'I could see clearer progress when the clinic tracked the changes consistently.', 'sub_description' => 'Regular review helps the treatment remain responsive to scalp changes.'],
        ['before' => 'results-1-before.webp', 'after' => 'results-1-after.webp', 'card_title' => 'Female, 40s', 'short_description' => '4 Months on Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'My scalp became healthier first, and then the visible results followed.', 'sub_description' => 'Healthier scalp conditions often lead to more reliable recovery outcomes.'],
        ['before' => 'results-3-before.webp', 'after' => 'results-3-after.webp', 'card_title' => 'Female, 25', 'short_description' => '4 Months on Supplements & Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'The improvement felt gradual but much more reliable than trying random products.', 'sub_description' => 'Measured care removes guesswork and gives the process more clarity.'],
        ['before' => 'results-1-before.webp', 'after' => 'results-1-after.webp', 'card_title' => 'Female, 40s', 'short_description' => '4 Months on Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'I finally understood what my scalp needed and stopped guessing.', 'sub_description' => 'A consistent program can simplify the next steps and reduce uncertainty.'],
        ['before' => 'results-2-before.webp', 'after' => 'results-2-after.webp', 'card_title' => 'Female, 25', 'short_description' => '4 Months on Supplements & Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'The clinic mapped my progress in a way that made every step feel measurable.', 'sub_description' => 'Tracking makes it easier to see which adjustments support recovery.'],
        ['before' => 'results-2-before.webp', 'after' => 'results-2-after.webp', 'card_title' => 'Female, 40s', 'short_description' => '4 Months on Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'I noticed less shedding first, then more visible fullness over time.', 'sub_description' => 'Subtle changes become more meaningful when they are documented clearly.'],
        ['before' => 'results-3-before.webp', 'after' => 'results-3-after.webp', 'card_title' => 'Female, 40s', 'short_description' => '4 Months on Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'Having a structured plan made the process feel much more reassuring.', 'sub_description' => 'The right structure helps recovery feel more manageable over time.'],
        ['before' => 'results-1-before.webp', 'after' => 'results-1-after.webp', 'card_title' => 'Female, 25', 'short_description' => '4 Months on Supplements & Extract', 'subtitle' => 'Case Studies', 'testimonial' => 'The results were not instant, but they were clearly trackable and real.', 'sub_description' => 'Consistency and review are what make long-term changes visible.'],
    ];

    foreach ($res_seed as $i => $row) {
        $pid = wp_insert_post(
            [
                'post_type' => 'eh_result',
                'post_status' => 'publish',
                'post_title' => $row['meta'],
                'menu_order' => $i,
            ],
            true
        );
        if (is_wp_error($pid) || !$pid) {
            continue;
        }
        $ids = [];
        foreach (['before' => $row['before'], 'after' => $row['after']] as $file) {
            $path = $figma . '/' . $file;
            if (is_readable($path)) {
                $aid = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/' . $file, (int) $pid);
                if ($aid) {
                    $ids[] = $aid;
                }
            }
        }
        if ($ids !== []) {
            rwmb_set_meta((int) $pid, 'eh_result_gallery', $ids);
            set_post_thumbnail((int) $pid, (int) $ids[0]);
        }
        rwmb_set_meta((int) $pid, 'eh_result_card_title', $row['card_title']);
        rwmb_set_meta((int) $pid, 'eh_result_short_description', $row['short_description']);
        rwmb_set_meta((int) $pid, 'eh_result_testimonial', $row['testimonial']);
        rwmb_set_meta((int) $pid, 'eh_result_subtitle', $row['subtitle']);
        rwmb_set_meta((int) $pid, 'eh_result_sub_description', $row['sub_description']);
    }

    update_option('eh_marketing_cpts_seeded', '1', false);
}
