<?php

declare(strict_types=1);

add_filter('rwmb_meta_boxes', 'eurohairlab_diagnosis_page_register_meta_boxes');

function eurohairlab_diagnosis_page_register_meta_boxes(array $meta_boxes): array
{
    $wysiwyg_opts = [
        'textarea_rows' => 8,
        'teeny' => false,
        'media_buttons' => true,
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Diagnosis — Hero', 'eurohairlab'),
        'id' => 'eh_diagnosis_hero_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'single_image',
                'name' => esc_html__('Background image', 'eurohairlab'),
                'id' => 'eh_diagnosis_hero_image',
            ],
            [
                'type' => 'select',
                'name' => esc_html__('Overlay', 'eurohairlab'),
                'id' => 'eh_diagnosis_hero_overlay',
                'options' => [
                    'dark_51' => esc_html__('Dark 51%', 'eurohairlab'),
                    'dark_42' => esc_html__('Dark 42%', 'eurohairlab'),
                    'none' => esc_html__('None', 'eurohairlab'),
                ],
                'std' => 'dark_51',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_diagnosis_hero_title',
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Paragraph', 'eurohairlab'),
                'id' => 'eh_diagnosis_hero_paragraph',
                'options' => $wysiwyg_opts,
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Why Diagnosis', 'eurohairlab'),
        'id' => 'eh_diagnosis_why_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'single_image',
                'name' => esc_html__('Image', 'eurohairlab'),
                'id' => 'eh_diagnosis_why_image',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Image alt text', 'eurohairlab'),
                'id' => 'eh_diagnosis_why_image_alt',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Kicker', 'eurohairlab'),
                'id' => 'eh_diagnosis_why_kicker',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_diagnosis_why_title',
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Paragraph', 'eurohairlab'),
                'id' => 'eh_diagnosis_why_body',
                'options' => $wysiwyg_opts,
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Symptoms', 'eurohairlab'),
        'id' => 'eh_diagnosis_symptoms_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Section title', 'eurohairlab'),
                'id' => 'eh_diagnosis_symptoms_heading',
                'options' => $wysiwyg_opts,
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Symptom text (one per row)', 'eurohairlab'),
                'id' => 'eh_diagnosis_symptom_lines',
                'clone' => true,
                'sort_clone' => true,
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Step precision diagnosis', 'eurohairlab'),
        'id' => 'eh_diagnosis_steps_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'text',
                'name' => esc_html__('Section title', 'eurohairlab'),
                'id' => 'eh_diagnosis_steps_heading',
            ],
            [
                'type' => 'image_advanced',
                'name' => esc_html__('Image', 'eurohairlab'),
                'id' => 'eh_diagnosis_steps_images',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_diagnosis_steps_titles',
                'clone' => true,
                'sort_clone' => true,
                'add_button' => esc_html__('Add title', 'eurohairlab'),
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Description', 'eurohairlab'),
                'id' => 'eh_diagnosis_steps_descriptions',
                'clone' => true,
                'sort_clone' => true,
                'add_button' => esc_html__('Add description', 'eurohairlab'),
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Analysis and management of hair loss causes', 'eurohairlab'),
        'id' => 'eh_diagnosis_analysis_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'text',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_diagnosis_analysis_title',
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Intro', 'eurohairlab'),
                'id' => 'eh_diagnosis_analysis_intro',
                'options' => $wysiwyg_opts,
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Cause label', 'eurohairlab'),
                'id' => 'eh_diagnosis_analysis_labels',
                'clone' => true,
                'sort_clone' => true,
                'add_button' => esc_html__('Add label', 'eurohairlab'),
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Delivers results', 'eurohairlab'),
        'id' => 'eh_diagnosis_delivers_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'text',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_diagnosis_delivers_title',
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Intro', 'eurohairlab'),
                'id' => 'eh_diagnosis_delivers_intro',
                'options' => $wysiwyg_opts,
            ],
            [
                'type' => 'image_advanced',
                'name' => esc_html__('Image', 'eurohairlab'),
                'id' => 'eh_diagnosis_delivers_images',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_diagnosis_delivers_titles',
                'clone' => true,
                'sort_clone' => true,
                'add_button' => esc_html__('Add title', 'eurohairlab'),
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Description', 'eurohairlab'),
                'id' => 'eh_diagnosis_delivers_descriptions',
                'clone' => true,
                'sort_clone' => true,
                'add_button' => esc_html__('Add description', 'eurohairlab'),
            ],
        ],
    ];

    $meta_boxes[] = [
        'title' => esc_html__('Free scalp diagnosis by experts', 'eurohairlab'),
        'id' => 'eh_diagnosis_free_scalp_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'single_image',
                'name' => esc_html__('Background image', 'eurohairlab'),
                'id' => 'eh_diagnosis_free_scalp_image',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id' => 'eh_diagnosis_free_scalp_title',
            ],
            [
                'type' => 'wysiwyg',
                'name' => esc_html__('Paragraph', 'eurohairlab'),
                'id' => 'eh_diagnosis_free_scalp_paragraph',
                'options' => $wysiwyg_opts,
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button title', 'eurohairlab'),
                'id' => 'eh_diagnosis_free_scalp_button_label',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button href', 'eurohairlab'),
                'id' => 'eh_diagnosis_free_scalp_button_href',
                'desc' => esc_html__('Leave empty to use the same default as “Bottom CTA” (free scalp analysis URL). Relative paths like /contact/ are allowed.', 'eurohairlab'),
            ],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_diagnosis_bottom_cta_register_meta_boxes');

function eurohairlab_diagnosis_bottom_cta_register_meta_boxes(array $meta_boxes): array
{
    $meta_boxes[] = [
        'title' => esc_html__('Bottom CTA (Free scalp analysis)', 'eurohairlab'),
        'id' => 'eh_diagnosis_bottom_cta_section',
        'post_types' => ['page'],
        'context' => 'normal',
        'autosave' => true,
        'priority' => 'high',
        'closed' => true,
        'fields' => [
            [
                'type' => 'text',
                'name' => esc_html__('Button href override (optional)', 'eurohairlab'),
                'id' => 'eh_diagnosis_bottom_cta_button_href',
                'desc' => esc_html__('Used when “Free scalp diagnosis” button href is empty. Leave empty to use the theme assessment URL in a new tab.', 'eurohairlab'),
            ],
        ],
    ];

    return $meta_boxes;
}

add_action('admin_init', 'eurohairlab_seed_diagnosis_sections_meta');

function eurohairlab_seed_diagnosis_sections_meta(): void
{
    if (!is_admin() || !current_user_can('manage_options') || !function_exists('rwmb_set_meta') || !function_exists('eurohairlab_import_theme_asset_to_media_library')) {
        return;
    }

    $diagnosis_page = get_page_by_path('diagnosis', OBJECT, 'page');
    $diagnosis_page_id = $diagnosis_page instanceof WP_Post ? (int) $diagnosis_page->ID : 0;

    if (!$diagnosis_page_id || get_post_meta($diagnosis_page_id, '_eh_diagnosis_sections_seeded', true)) {
        return;
    }

    $import = static function (string $relative_path) use ($diagnosis_page_id): int {
        return (int) eurohairlab_import_theme_asset_to_media_library($relative_path, $diagnosis_page_id);
    };

    $hero_id = $import('assets/images/figma/diagnosis-mcp/hero-bg.png');
    if (!$hero_id) {
        $hero_id = $import('assets/images/figma/diagnosis-hero.webp');
    }

    $why_id = $import('assets/images/figma/diagnosis-intro.png');
    if (!$why_id) {
        $why_id = $import('assets/images/figma/diagnosis-intro.webp');
    }
    if (!$why_id) {
        $why_id = $hero_id;
    }

    $cta_id = $import('assets/images/figma/diagnosis-hero.webp');
    if (!$cta_id) {
        $cta_id = $hero_id;
    }

    $scalp_ids = [];
    for ($i = 1; $i <= 5; $i++) {
        $scalp_ids[] = $import('assets/images/figma/scalp-diagnosis-' . $i . '.png');
    }

    $delivery_ids = [];
    foreach ([1, 2, 3] as $n) {
        $delivery_ids[] = $import('assets/images/figma/delivery-results-' . $n . '.png');
    }

    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_hero_image', $hero_id);
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_hero_overlay', 'dark_51');
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_hero_title', "Understand\nYour Scalp First");
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_hero_paragraph', '<p>' . esc_html__('Effective treatment begins with an accurate diagnosis. At EUROHAIRLAB, every patient begins with a complete clinical evaluation before any treatment is considered so that what we recommend is built around what we actually find.', 'eurohairlab') . '</p>');

    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_why_image', $why_id);
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_why_image_alt', esc_attr__('Clinical scalp imaging review during a precision diagnosis consultation at EUROHAIRLAB.', 'eurohairlab'));
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_why_kicker', esc_html__('The First Step in Scalp Care: Precision Diagnosis', 'eurohairlab'));
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_why_title', esc_html__('Why Scalp', 'eurohairlab') . "\n" . esc_html__(' Diagnosis ?', 'eurohairlab'));
    $why_body = '';
    foreach (
        [
            esc_html__('To identify the causes of hair loss and scalp issues at EUROHAIRLAB, a thorough and accurate diagnosis is essential.', 'eurohairlab'),
            esc_html__('While some people are familiar with their scalp condition, such as whether it is dry or oily, many are not aware of their scalp\'s actual state.', 'eurohairlab'),
            esc_html__('At EUROHAIRLAB, we conduct an 11-step precision diagnosis to thoroughly examine the scalp and hair condition.', 'eurohairlab'),
            esc_html__('Through this process, we analyze hair loss causes, scalp keratin, hair density, hair thickness, heavy metals in hair, scalp oil and moisture balance, and scalp toxins, among other factors.', 'eurohairlab'),
            esc_html__('Based on these results, we design a personalized 1:1 care program. If you are currently concerned about hair loss or scalp issues, visit EUROHAIRLAB now.', 'eurohairlab'),
        ] as $p
    ) {
        $why_body .= '<p>' . $p . '</p>';
    }
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_why_body', $why_body);

    $sym_heading = '<p>' . esc_html__('If you experience any of these symptoms,', 'eurohairlab');
    $sym_heading .= ' <br class="hidden sm:inline" /> ';
    $sym_heading .= esc_html__(' it\'s recommended to get a ', 'eurohairlab');
    $sym_heading .= '<span class="underline decoration-2 underline-offset-4">' . esc_html__('scalp diagnosis at EUROHAIRLAB', 'eurohairlab') . '</span></p>';
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_symptoms_heading', $sym_heading);
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_symptom_lines', [
        'Sudden pimples or inflammation on the scalp',
        'Increased hair loss during shampooing.',
        'Scalp remains itchy and oily even after washing.',
        'Increased dandruff.',
        'Hair becomes thinner, and falls out easily when touched.',
        'Frequent perming or dyeing.',
        'Excessive use of hair products like wax or spray.',
        'Experiencing stress from social interactions, studies, or work.',
        'Excessive scalp sweating.',
        'Scalp pain when touched.',
        'Tingling scalp and stiffness in the back of the neck.',
    ]);

    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_steps_heading', esc_html__('11-step precision diagnosis', 'eurohairlab'));

    $steps_meta = [
        ['title' => 'Scalp Diagnosis', 'description' => 'We use various lenses to analyze scalp issues that are not visible to the naked eye in detail.'],
        ['title' => 'Scalp Type Diagnosis', 'description' => 'We check your scalp condition, compare it with photo data, and guide you on the current state.'],
        ['title' => 'Hair Density Check', 'description' => 'We check the number of hairs per pore, from the early stages of hair loss to the advanced stages, with precision.'],
        ['title' => 'Dead Skin Cell Condition', 'description' => 'We check the number of hairs per pore, from the early stages of hair loss to the advanced stages, with precision.'],
        ['title' => 'Scalp Trouble', 'description' => 'We check the number of hairs per pore, from the early stages of hair loss to the advanced stages, with precision.'],
        ['title' => 'Sebum Regulation', 'description' => 'We assess how much oil is being produced and how it affects the scalp surface condition.'],
        ['title' => 'Scalp Micro Inflammation', 'description' => 'We look for subtle inflammation that may be contributing to discomfort and shedding.'],
        ['title' => 'Keratin Build Up', 'description' => 'We check for excess keratin and debris that can affect scalp balance and follicle health.'],
        ['title' => 'Hair Density Check', 'description' => 'We compare density across the scalp to identify thinning patterns and progression.'],
        ['title' => 'Follicle Activity', 'description' => 'We evaluate active and dormant follicles to understand the current growth condition.'],
        ['title' => 'Diagnosis Summary', 'description' => 'We summarize all findings into a personalized care plan and next-step recommendation.'],
    ];

    $step_images = [];
    $step_titles = [];
    $step_descriptions = [];
    foreach ($steps_meta as $index => $meta) {
        $img_id = $scalp_ids[$index % max(count($scalp_ids), 1)] ?? 0;
        if ($img_id > 0) {
            $step_images[] = $img_id;
        }
        $step_titles[] = $meta['title'];
        $step_descriptions[] = $meta['description'];
    }
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_steps_images', $step_images);
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_steps_titles', $step_titles);
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_steps_descriptions', $step_descriptions);

    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_analysis_title', esc_html__('Analysis and Management of Hair Loss Causes', 'eurohairlab'));
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_analysis_intro', '<p>' . esc_html__('The causes of hair loss are quite diverse, and in many cases, hair loss occurs due to a combination of factors. It is important to accurately analyze the causes of hair loss, such as stress, genetics, dieting, postpartum conditions, fine dust, poor dietary habits, scalp issues, or chemical damage from perms or dyeing, and provide tailored care for each type.', 'eurohairlab') . '</p>');

    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_analysis_labels', [
        'Stress',
        'Genetics',
        'Dieting',
        'Postpartum',
        'Poor dietary habits',
        'Scalp problems',
        'Perming, Dyeing',
        'Fine dust',
    ]);

    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_title', esc_html__('EUROHAIRLAB Delivers Results', 'eurohairlab'));
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_intro', '<p>' . esc_html__('It\'s difficult to talk about your hair loss worries to anyone! We strive to offer services that will not only care for your scalp health but also bring comfort to your body and mind.', 'eurohairlab') . '</p>');

    $cards = [
        ['title' => 'Effective Care', 'description' => 'With over 17 years of clinical data from universities and field studies, we use the most effective care methods and products based on each customer\'s scalp type.'],
        ['title' => 'Safety', 'description' => 'We ensure safety and trust by using specialized products that are clinically proven to be harmless to the scalp in medical universities and research centers in the U.S., Germany, and Italy, along with our own developed products.'],
        ['title' => 'Personalized Care Service', 'description' => 'Based on 17 years of accumulated clinical data, we provide tailored care according to each customer\'s scalp condition, effectively addressing a variety of concerns.'],
    ];
    $card_rows = [];
    $card_rows_titles = [];
    $card_rows_descriptions = [];
    foreach ($cards as $i => $c) {
        $img_id = $delivery_ids[$i % max(count($delivery_ids), 1)] ?? 0;
        if ($img_id > 0) {
            $card_rows[] = $img_id;
        }
        $card_rows_titles[] = $c['title'];
        $card_rows_descriptions[] = $c['description'];
    }
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_images', $card_rows);
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_titles', $card_rows_titles);
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_descriptions', $card_rows_descriptions);

    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_free_scalp_image', $cta_id);
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_free_scalp_title', esc_html__('Free Scalp Diagnosis by Experts', 'eurohairlab'));
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_free_scalp_paragraph', '<p>' . esc_html__('Diagnosing scalp condition, sebum levels, keratin, pore status, hair loss type, hair density, damage level, and thickness.', 'eurohairlab') . '</p>');
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_free_scalp_button_label', esc_html__('Start Your Free Scalp Analysis', 'eurohairlab'));
    rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_free_scalp_button_href', '');

    update_post_meta($diagnosis_page_id, '_eh_diagnosis_sections_seeded', '1');
}

add_action('admin_init', 'eurohairlab_migrate_diagnosis_steps_metabox_structure');
add_action('admin_init', 'eurohairlab_migrate_diagnosis_delivers_metabox_structure');

function eurohairlab_migrate_diagnosis_steps_metabox_structure(): void
{
    if (!is_admin() || !function_exists('rwmb_meta') || !function_exists('rwmb_set_meta')) {
        return;
    }

    $diagnosis_page = get_page_by_path('diagnosis', OBJECT, 'page');
    $diagnosis_page_id = $diagnosis_page instanceof WP_Post ? (int) $diagnosis_page->ID : 0;
    if (!$diagnosis_page_id || get_post_meta($diagnosis_page_id, '_eh_diagnosis_steps_structure_migrated', true)) {
        return;
    }

    $existing_images = rwmb_meta('eh_diagnosis_steps_images', [], $diagnosis_page_id);
    $existing_titles = rwmb_meta('eh_diagnosis_steps_titles', [], $diagnosis_page_id);
    $existing_descriptions = rwmb_meta('eh_diagnosis_steps_descriptions', [], $diagnosis_page_id);
    $existing_labels = rwmb_meta('eh_diagnosis_analysis_labels', [], $diagnosis_page_id);
    if (is_array($existing_images) && $existing_images !== [] && is_array($existing_titles) && $existing_titles !== [] && is_array($existing_descriptions) && $existing_descriptions !== [] && is_array($existing_labels) && $existing_labels !== []) {
        update_post_meta($diagnosis_page_id, '_eh_diagnosis_steps_structure_migrated', '1');
        return;
    }

    $legacy_rows = rwmb_meta('eh_diagnosis_steps', [], $diagnosis_page_id);
    if (!is_array($legacy_rows) || $legacy_rows === []) {
        update_post_meta($diagnosis_page_id, '_eh_diagnosis_steps_structure_migrated', '1');
        return;
    }

    $step_images = [];
    $step_titles = [];
    $step_descriptions = [];
    foreach ($legacy_rows as $row) {
        if (is_string($row) && $row !== '') {
            $decoded = json_decode($row, true);
            if (is_array($decoded)) {
                $row = $decoded;
            }
        }
        if (!is_array($row)) {
            continue;
        }

        $image = $row['step_image'] ?? null;
        if (is_array($image)) {
            $first = reset($image);
            if (is_numeric($first)) {
                $image = (int) $first;
            } elseif (is_array($first) && isset($first['ID']) && is_numeric($first['ID'])) {
                $image = (int) $first['ID'];
            } elseif (isset($image['ID']) && is_numeric($image['ID'])) {
                $image = (int) $image['ID'];
            }
        }

        if (is_numeric($image) && (int) $image > 0) {
            $step_images[] = (int) $image;
        }
        $step_titles[] = (string) ($row['step_title'] ?? '');
        $step_descriptions[] = (string) ($row['step_description'] ?? '');
    }

    if ($step_images !== []) {
        rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_steps_images', $step_images);
    }
    if ($step_titles !== []) {
        rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_steps_titles', $step_titles);
    }
    if ($step_descriptions !== []) {
        rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_steps_descriptions', $step_descriptions);
    }

    $legacy_pairs = rwmb_meta('eh_diagnosis_analysis_pairs', [], $diagnosis_page_id);
    if (is_array($legacy_pairs) && $legacy_pairs !== []) {
        $labels = [];
        foreach ($legacy_pairs as $row) {
            if (is_string($row) && $row !== '') {
                $decoded = json_decode($row, true);
                if (is_array($decoded)) {
                    $row = $decoded;
                }
            }
            if (!is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['pair_label'] ?? ''));
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        if ($labels !== []) {
            rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_analysis_labels', $labels);
        }
    }

    $legacy_cards = rwmb_meta('eh_diagnosis_delivers_cards', [], $diagnosis_page_id);
    if (is_array($legacy_cards) && $legacy_cards !== []) {
        $deliver_images = [];
        $deliver_titles = [];
        $deliver_descriptions = [];
        foreach ($legacy_cards as $row) {
            if (is_string($row) && $row !== '') {
                $decoded = json_decode($row, true);
                if (is_array($decoded)) {
                    $row = $decoded;
                }
            }
            if (!is_array($row)) {
                continue;
            }

            $image = $row['card_image'] ?? null;
            if (is_array($image)) {
                $first = reset($image);
                if (is_numeric($first)) {
                    $image = (int) $first;
                } elseif (is_array($first) && isset($first['ID']) && is_numeric($first['ID'])) {
                    $image = (int) $first['ID'];
                } elseif (isset($image['ID']) && is_numeric($image['ID'])) {
                    $image = (int) $image['ID'];
                }
            }

            if (is_numeric($image) && (int) $image > 0) {
                $deliver_images[] = (int) $image;
            }
            $deliver_titles[] = (string) ($row['card_title'] ?? '');
            $deliver_descriptions[] = (string) ($row['card_description'] ?? '');
        }

        if ($deliver_images !== []) {
            rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_images', $deliver_images);
        }
        if ($deliver_titles !== []) {
            rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_titles', $deliver_titles);
        }
        if ($deliver_descriptions !== []) {
            rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_descriptions', $deliver_descriptions);
        }
    }

    update_post_meta($diagnosis_page_id, '_eh_diagnosis_steps_structure_migrated', '1');
}

function eurohairlab_migrate_diagnosis_delivers_metabox_structure(): void
{
    if (!is_admin() || !function_exists('rwmb_meta') || !function_exists('rwmb_set_meta')) {
        return;
    }

    $diagnosis_page = get_page_by_path('diagnosis', OBJECT, 'page');
    $diagnosis_page_id = $diagnosis_page instanceof WP_Post ? (int) $diagnosis_page->ID : 0;
    if (!$diagnosis_page_id || get_post_meta($diagnosis_page_id, '_eh_diagnosis_delivers_structure_migrated', true)) {
        return;
    }

    $existing_images = rwmb_meta('eh_diagnosis_delivers_images', [], $diagnosis_page_id);
    $existing_titles = rwmb_meta('eh_diagnosis_delivers_titles', [], $diagnosis_page_id);
    $existing_descriptions = rwmb_meta('eh_diagnosis_delivers_descriptions', [], $diagnosis_page_id);
    if (is_array($existing_images) && $existing_images !== [] && is_array($existing_titles) && $existing_titles !== [] && is_array($existing_descriptions) && $existing_descriptions !== []) {
        update_post_meta($diagnosis_page_id, '_eh_diagnosis_delivers_structure_migrated', '1');
        return;
    }

    $legacy_cards = rwmb_meta('eh_diagnosis_delivers_cards', [], $diagnosis_page_id);
    if (!is_array($legacy_cards) || $legacy_cards === []) {
        update_post_meta($diagnosis_page_id, '_eh_diagnosis_delivers_structure_migrated', '1');
        return;
    }

    $deliver_images = [];
    $deliver_titles = [];
    $deliver_descriptions = [];
    foreach ($legacy_cards as $row) {
        if (is_string($row) && $row !== '') {
            $decoded = json_decode($row, true);
            if (is_array($decoded)) {
                $row = $decoded;
            }
        }
        if (!is_array($row)) {
            continue;
        }

        $image = $row['card_image'] ?? null;
        if (is_array($image)) {
            $first = reset($image);
            if (is_numeric($first)) {
                $image = (int) $first;
            } elseif (is_array($first) && isset($first['ID']) && is_numeric($first['ID'])) {
                $image = (int) $first['ID'];
            } elseif (isset($image['ID']) && is_numeric($image['ID'])) {
                $image = (int) $image['ID'];
            }
        }

        if (is_numeric($image) && (int) $image > 0) {
            $deliver_images[] = (int) $image;
        }
        $deliver_titles[] = (string) ($row['card_title'] ?? '');
        $deliver_descriptions[] = (string) ($row['card_description'] ?? '');
    }

    if ($deliver_images !== []) {
        rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_images', $deliver_images);
    }
    if ($deliver_titles !== []) {
        rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_titles', $deliver_titles);
    }
    if ($deliver_descriptions !== []) {
        rwmb_set_meta($diagnosis_page_id, 'eh_diagnosis_delivers_descriptions', $deliver_descriptions);
    }

    update_post_meta($diagnosis_page_id, '_eh_diagnosis_delivers_structure_migrated', '1');
}
