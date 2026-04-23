<?php

declare(strict_types=1);

add_filter('rwmb_meta_boxes', 'eurohairlab_marketing_pages_register_meta_boxes');

function eurohairlab_marketing_pages_register_meta_boxes(array $meta_boxes): array
{
    $wysiwyg_opts = [
        'textarea_rows' => 8,
        'teeny' => false,
        'media_buttons' => true,
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Treatment programs — Hero', 'eurohairlab'),
        'id' => 'eh_treatments_hero_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'single_image',
                'name' => esc_html__('Hero image', 'eurohairlab'),
                'id' => 'eh_treatments_hero_image',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_treatments_hero_title',
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Paragraph', 'eurohairlab'),
                'id' => 'eh_treatments_hero_paragraph',
                'options' => $wysiwyg_opts,
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Results — Hero', 'eurohairlab'),
        'id' => 'eh_results_hero_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'single_image',
                'name' => esc_html__('Hero image', 'eurohairlab'),
                'id' => 'eh_results_hero_image',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_results_hero_title',
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Paragraph', 'eurohairlab'),
                'id' => 'eh_results_hero_paragraph',
                'options' => $wysiwyg_opts,
            ],
        ],
    ];

    return $meta_boxes;
}

add_action('admin_init', 'eurohairlab_seed_marketing_pages_meta');

function eurohairlab_seed_marketing_pages_meta(): void
{
    if (!is_admin() || !current_user_can('manage_options') || !function_exists('rwmb_set_meta') || !function_exists('eurohairlab_import_theme_asset_to_media_library')) {
        return;
    }

    foreach (['treatments', 'treatment-programs'] as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if (!$page instanceof WP_Post) {
            continue;
        }

        $pid = (int) $page->ID;
        if (get_post_meta($pid, '_eh_treatments_meta_seeded', true)) {
            break;
        }

        $hero_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/figma-treatment-hero.webp', $pid);
        rwmb_set_meta($pid, 'eh_treatments_hero_image', $hero_id);
        rwmb_set_meta($pid, 'eh_treatments_hero_title', 'HAIR TREATMENT PROGRAM');
        rwmb_set_meta($pid, 'eh_treatments_hero_paragraph', '<p>A comprehensive, clinically structured hair and scalp care program built on a diagnose-first approach. Every treatment begins with a detailed scalp analysis to identify root causes before selecting the most precise solution. Combining advanced Korean techniques with the SCALPFIRST™ System, this program delivers targeted care from scalp correction to long-term hair regeneration and maintenance.</p>');
        update_post_meta($pid, '_eh_treatments_meta_seeded', '1');
        break;
    }

    $results_page = get_page_by_path('results', OBJECT, 'page');
    if ($results_page instanceof WP_Post && !get_post_meta($results_page->ID, '_eh_results_meta_seeded', true)) {
        $rid = (int) $results_page->ID;
        $img_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/figma-results-hero.webp', $rid);
        rwmb_set_meta($rid, 'eh_results_hero_image', $img_id);
        rwmb_set_meta($rid, 'eh_results_hero_title', '3 Million Cases Worldwide');
        rwmb_set_meta($rid, 'eh_results_hero_paragraph', '<p>EUROHAIRLAB by DR. SCALP has helped over 3 million people around the world take control of their hair health. With our ScalpFirst™ philosophy and diagnosis-first approach, every treatment is tailored to optimize your scalp environment and deliver results that last. Experience the care and expertise trusted by millions.</p>');
        update_post_meta($rid, '_eh_results_meta_seeded', '1');
    }
}
