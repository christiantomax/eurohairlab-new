<?php

function eurohairlab_get_current_admin_post_id(): int
{
    $post_id = 0;

    if (isset($_GET['post'])) {
        $post_id = absint(wp_unslash($_GET['post']));
    } elseif (isset($_POST['post_ID'])) {
        $post_id = absint(wp_unslash($_POST['post_ID']));
    }

    return $post_id;
}

function eurohairlab_is_home_meta_box_page(): bool
{
    if (!is_admin()) {
        return false;
    }

    $post_id = eurohairlab_get_current_admin_post_id();
    if (!$post_id) {
        return false;
    }

    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id && $post_id === $front_page_id) {
        return true;
    }

    $post = get_post($post_id);

    return $post instanceof WP_Post
        && $post->post_type === 'page'
        && $post->post_name === 'home';
}

/**
 * Default hero slide descriptions, one textarea per slide.
 *
 * @return list<string>
 */
function eurohairlab_home_default_hero_slide_copy_meta(): array
{
    return [
        "ScalpFirstâ„¢ is our signature approach that puts the scalp at the center of every hair treatment. It starts with a detailed scalp assessment to uncover underlying imbalances that cause thinning, shedding, or weak hair.\n\nBy diagnosing the root cause first, ScalpFirstâ„¢ allows us to deliver targeted, effective treatments that restore follicle function and support long-term hair health.",
        "With our diagnose-based approach, we address hair issues at their origin. Thinning, shedding, and fragility all begin at the scalp, reflecting underlying physiological or biochemical imbalances.",
        "Visible recovery is built on consistent scalp care and evidence-led protocols tailored to your stage of hair change.",
        "Measured outcomes help you understand progress with clarityâ€”density, comfort, and scalp balance tracked over time.",
    ];
}

function eurohairlab_home_normalize_hero_slide_copy_meta_value($value): array
{
    if (is_string($value)) {
        $value = trim($value);

        return $value === '' ? [] : [$value];
    }

    if (!is_array($value)) {
        return [];
    }

    $normalized = [];

    foreach ($value as $item) {
        if (is_string($item)) {
            $item = trim($item);
            if ($item !== '') {
                $normalized[] = $item;
            }

            continue;
        }

        if (!is_array($item)) {
            continue;
        }

        $raw = $item['eh_home_hero_copy_paragraph'] ?? null;
        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw !== '') {
                $normalized[] = $raw;
            }

            continue;
        }

        if (is_array($raw)) {
            $paragraphs = array_values(array_filter(
                array_map(static fn ($line): string => trim((string) $line), $raw),
                static fn (string $line): bool => $line !== ''
            ));

            if ($paragraphs !== []) {
                $normalized[] = implode("\n\n", $paragraphs);
            }
        }
    }

    return $normalized;
}

function eurohairlab_home_migrate_hero_slide_copy_meta_value($post_id): void
{
    if (!$post_id || !function_exists('rwmb_set_meta')) {
        return;
    }

    $existing = get_post_meta((int) $post_id, 'eh_home_hero_slide_copy', true);
    $normalized = eurohairlab_home_normalize_hero_slide_copy_meta_value($existing);

    if ($normalized === [] || $normalized === $existing) {
        return;
    }

    rwmb_set_meta((int) $post_id, 'eh_home_hero_slide_copy', $normalized);
}

add_filter('rwmb_meta_boxes', 'eurohairlab_home_hero_register_meta_boxes');

function eurohairlab_home_hero_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Home Hero Section', 'eurohairlab'),
        'id'         => 'eh_home_hero_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => false,
        'fields'     => [
            [
                'type'             => 'image_advanced',
                'name'             => esc_html__('Hero Images', 'eurohairlab'),
                'id'               => 'eh_home_hero_images',
                'max_file_uploads' => 10,
                'std'              => [],
            ],
            [
                'type'  => 'textarea',
                'name'  => esc_html__('Hero Titles', 'eurohairlab'),
                'id'    => 'eh_home_hero_titles',
                'clone' => true,
                'std'   => '',
            ],
            [
                'type'  => 'textarea',
                'name'  => esc_html__('Hero Descriptions', 'eurohairlab'),
                'id'    => 'eh_home_hero_slide_copy',
                'clone' => true,
                'std'   => '',
            ],
            [
                'type'  => 'text',
                'name'  => esc_html__('Hero CTA label (per slide)', 'eurohairlab'),
                'id'    => 'eh_home_hero_button_texts',
                'desc'  => esc_html__('One row per slide, same order as Hero Images / Titles. Leave a row empty to use the default label for that slide.', 'eurohairlab'),
                'clone' => true,
                'std'   => '',
            ],
            [
                'type'  => 'text',
                'name'  => esc_html__('Hero CTA link (per slide)', 'eurohairlab'),
                'id'    => 'eh_home_hero_button_hrefs',
                'desc'  => esc_html__('Full URL, or a path on this site (e.g. /assessment). One row per slide, same order as the hero slides. Leave a row empty to use the default link.', 'eurohairlab'),
                'clone' => true,
                'std'   => '',
            ],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_home_foundation_register_meta_boxes');

function eurohairlab_home_foundation_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Home Foundation Section', 'eurohairlab'),
        'id'         => 'eh_home_foundation_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => false,
        'fields'     => [
            [
                'type' => 'text',
                'name' => esc_html__('Kicker', 'eurohairlab'),
                'id'   => 'eh_home_foundation_kicker',
                'std'  => '',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id'   => 'eh_home_foundation_title',
                'std'  => '',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Body Text', 'eurohairlab'),
                'id'   => 'eh_home_foundation_body_text',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button Text', 'eurohairlab'),
                'id'   => 'eh_home_foundation_button_text',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button Href', 'eurohairlab'),
                'id'   => 'eh_home_foundation_button_href',
                'std'  => '',
            ],
            [
                'type' => 'single_image',
                'name' => esc_html__('Image', 'eurohairlab'),
                'id'   => 'eh_home_foundation_image',
                'std'  => 0,
            ],
            [
                'type' => 'oembed',
                'name' => esc_html__('Video URL', 'eurohairlab'),
                'id'   => 'eh_home_foundation_video_url',
                'std'  => '',
            ],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_home_difference_register_meta_boxes');

function eurohairlab_home_difference_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Home See The Difference Section', 'eurohairlab'),
        'id'         => 'eh_home_difference_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => false,
        'fields'     => [
            [
                'type' => 'text',
                'name' => esc_html__('Kicker', 'eurohairlab'),
                'id'   => 'eh_home_difference_kicker',
                'std'  => '',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id'   => 'eh_home_difference_title',
                'std'  => '',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Body Text', 'eurohairlab'),
                'id'   => 'eh_home_difference_body_text',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button Text', 'eurohairlab'),
                'id'   => 'eh_home_difference_button_text',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button Href', 'eurohairlab'),
                'id'   => 'eh_home_difference_button_href',
                'std'  => '',
            ],
            [
                'type' => 'single_image',
                'name' => esc_html__('Before Image', 'eurohairlab'),
                'id'   => 'eh_home_difference_before_image',
                'std'  => 0,
            ],
            [
                'type' => 'single_image',
                'name' => esc_html__('After Image', 'eurohairlab'),
                'id'   => 'eh_home_difference_after_image',
                'std'  => 0,
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Before Label', 'eurohairlab'),
                'id'   => 'eh_home_difference_before_label',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('After Label', 'eurohairlab'),
                'id'   => 'eh_home_difference_after_label',
                'std'  => '',
            ],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_home_technology_register_meta_boxes');

function eurohairlab_home_technology_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Home Technology Section', 'eurohairlab'),
        'id'         => 'eh_home_technology_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => false,
        'fields'     => [
            [
                'type' => 'text',
                'name' => esc_html__('Kicker', 'eurohairlab'),
                'id'   => 'eh_home_technology_kicker',
                'std'  => '',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id'   => 'eh_home_technology_title',
                'std'  => '',
            ],
            [
                'type'  => 'text',
                'name'  => esc_html__('Card Titles', 'eurohairlab'),
                'id'    => 'eh_home_technology_card_titles',
                'clone' => true,
                'std'   => '',
            ],
            [
                'type'  => 'textarea',
                'name'  => esc_html__('Card Descriptions', 'eurohairlab'),
                'id'    => 'eh_home_technology_card_descriptions',
                'clone' => true,
                'std'   => '',
            ],
            [
                'type'  => 'single_image',
                'name'  => esc_html__('Card Images', 'eurohairlab'),
                'id'    => 'eh_home_technology_card_images',
                'clone' => true,
                'std'   => 0,
            ],
            [
                'type'  => 'text',
                'name'  => esc_html__('Card Lightbox Titles', 'eurohairlab'),
                'id'    => 'eh_home_technology_card_lightbox_titles',
                'clone' => true,
                'std'   => '',
            ],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_home_programs_register_meta_boxes');

function eurohairlab_home_programs_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Home Treatment Programs Section', 'eurohairlab'),
        'id'         => 'eh_home_programs_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => false,
        'fields'     => [
            [
                'type' => 'text',
                'name' => esc_html__('Kicker', 'eurohairlab'),
                'id'   => 'eh_home_programs_kicker',
                'std'  => '',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id'   => 'eh_home_programs_title',
                'std'  => '',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Body Text', 'eurohairlab'),
                'id'   => 'eh_home_programs_body_text',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button Text', 'eurohairlab'),
                'id'   => 'eh_home_programs_button_text',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button Href', 'eurohairlab'),
                'id'   => 'eh_home_programs_button_href',
                'std'  => '',
            ],
            [
                'type'  => 'single_image',
                'name'  => esc_html__('Card Images', 'eurohairlab'),
                'id'    => 'eh_home_programs_card_images',
                'clone' => true,
                'std'   => 0,
            ],
            [
                'type'  => 'text',
                'name'  => esc_html__('Card Titles', 'eurohairlab'),
                'id'    => 'eh_home_programs_card_titles',
                'clone' => true,
                'std'   => '',
            ],
            [
                'type'  => 'textarea',
                'name'  => esc_html__('Card Descriptions', 'eurohairlab'),
                'id'    => 'eh_home_programs_card_descriptions',
                'clone' => true,
                'std'   => '',
            ],
            [
                'type'  => 'text',
                'name'  => esc_html__('Card Duration', 'eurohairlab'),
                'id'    => 'eh_home_programs_card_durations',
                'clone' => true,
                'std'   => '',
            ],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_home_testimonials_register_meta_boxes');

function eurohairlab_home_testimonials_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Home Testimonials Section', 'eurohairlab'),
        'id'         => 'eh_home_testimonials_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => false,
        'fields'     => [
            [
                'type'  => 'textarea',
                'name'  => esc_html__('Testimonial Texts', 'eurohairlab'),
                'id'    => 'eh_home_testimonial_texts',
                'clone' => true,
                'std'   => '',
            ],
            [
                'type'  => 'text',
                'name'  => esc_html__('Testimonial Names', 'eurohairlab'),
                'id'    => 'eh_home_testimonial_names',
                'clone' => true,
                'std'   => '',
            ],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_home_consultation_register_meta_boxes');

function eurohairlab_home_consultation_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Home Consultation CTA Section', 'eurohairlab'),
        'id'         => 'eh_home_consultation_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => false,
        'fields'     => [
            [
                'type' => 'textarea',
                'name' => esc_html__('Title', 'eurohairlab'),
                'id'   => 'eh_home_consultation_title',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button Text', 'eurohairlab'),
                'id'   => 'eh_home_consultation_button_text',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Button Href', 'eurohairlab'),
                'id'   => 'eh_home_consultation_button_href',
                'std'  => '',
            ],
            [
                'type' => 'single_image',
                'name' => esc_html__('Background Image', 'eurohairlab'),
                'id'   => 'eh_home_consultation_background_image',
                'std'  => 0,
            ],
        ],
    ];

    return $meta_boxes;
}

function eurohairlab_import_theme_asset_to_media_library($relative_path, $parent_post_id = 0)
{
    $relative_path = ltrim((string) $relative_path, '/');

    if ($relative_path === '') {
        return 0;
    }

    $existing = get_posts([
        'post_type'      => 'attachment',
        'posts_per_page' => 1,
        'post_status'    => 'inherit',
        'meta_key'       => '_eh_theme_asset_source',
        'meta_value'     => $relative_path,
        'fields'         => 'ids',
    ]);

    if (!empty($existing)) {
        return (int) $existing[0];
    }

    $source = trailingslashit(get_template_directory()) . $relative_path;
    if (!file_exists($source)) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';

    $upload_dir = wp_upload_dir();
    if (!empty($upload_dir['error'])) {
        return 0;
    }

    $filename = wp_unique_filename($upload_dir['path'], basename($source));
    $target   = trailingslashit($upload_dir['path']) . $filename;

    if (!wp_mkdir_p($upload_dir['path']) || !copy($source, $target)) {
        return 0;
    }

    $attachment_id = wp_insert_attachment([
        'post_mime_type' => wp_check_filetype($filename)['type'] ?? 'image/webp',
        'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_parent'    => (int) $parent_post_id,
    ], $target, $parent_post_id);

    if (is_wp_error($attachment_id) || !$attachment_id) {
        return 0;
    }

    $metadata = wp_generate_attachment_metadata($attachment_id, $target);
    if (!empty($metadata)) {
        wp_update_attachment_metadata($attachment_id, $metadata);
    }

    update_post_meta($attachment_id, '_eh_theme_asset_source', $relative_path);

    return (int) $attachment_id;
}

function eurohairlab_seed_home_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $home_page_id = (int) get_option('page_on_front');
    if (!$home_page_id) {
        $home_page = get_page_by_path('home', OBJECT, 'page');
        $home_page_id = $home_page instanceof WP_Post ? (int) $home_page->ID : 0;
    }

    if (!$home_page_id || get_post_meta($home_page_id, '_eh_home_meta_seeded', true)) {
        return;
    }

    $hero_image_ids = array_values(array_filter([
        eurohairlab_import_theme_asset_to_media_library('assets/images/hero-bg.webp', $home_page_id),
        eurohairlab_import_theme_asset_to_media_library('assets/images/scalp-analysis.webp', $home_page_id),
        eurohairlab_import_theme_asset_to_media_library('assets/images/journey-after.webp', $home_page_id),
        eurohairlab_import_theme_asset_to_media_library('assets/images/real-transformations.webp', $home_page_id),
    ]));

    $foundation_image_id   = eurohairlab_import_theme_asset_to_media_library('assets/images/scalp-analysis.webp', $home_page_id);
    $difference_before_id  = eurohairlab_import_theme_asset_to_media_library('assets/images/journey-before.webp', $home_page_id);
    $difference_after_id   = eurohairlab_import_theme_asset_to_media_library('assets/images/journey-after.webp', $home_page_id);
    $technology_image_ids  = array_values(array_filter([
        eurohairlab_import_theme_asset_to_media_library('assets/images/figma/treatment-program-1.webp', $home_page_id),
        eurohairlab_import_theme_asset_to_media_library('assets/images/figma/treatment-program-2.webp', $home_page_id),
        eurohairlab_import_theme_asset_to_media_library('assets/images/figma/treatment-technology.webp', $home_page_id),
        eurohairlab_import_theme_asset_to_media_library('assets/images/figma/treatment-program-3.webp', $home_page_id),
    ]));
    $program_image_ids     = array_values(array_filter([
        eurohairlab_import_theme_asset_to_media_library('assets/images/figma/treatment-program-1.webp', $home_page_id),
        eurohairlab_import_theme_asset_to_media_library('assets/images/figma/treatment-program-2.webp', $home_page_id),
        eurohairlab_import_theme_asset_to_media_library('assets/images/figma/treatment-technology.webp', $home_page_id),
        eurohairlab_import_theme_asset_to_media_library('assets/images/figma/treatment-program-3.webp', $home_page_id),
    ]));
    $consultation_image_id = eurohairlab_import_theme_asset_to_media_library('assets/images/journey-before.webp', $home_page_id);

    rwmb_set_meta($home_page_id, 'eh_home_hero_images', $hero_image_ids);
    rwmb_set_meta($home_page_id, 'eh_home_hero_titles', [
        'ScalpFirstÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢',
        "Clinical Scalp\nDiagnosis",
        "Visible Hair\nRecovery",
        "Measured\nHair Results",
    ]);
    rwmb_set_meta($home_page_id, 'eh_home_hero_slide_copy', eurohairlab_home_default_hero_slide_copy_meta());
    rwmb_set_meta($home_page_id, 'eh_home_hero_button_texts', [
        'Start Your Free Scalp Analysis',
        'Explore Diagnosis',
        'See Real Results',
        'View Real Results',
    ]);
    rwmb_set_meta($home_page_id, 'eh_home_hero_button_hrefs', [
        '/assessment',
        '/diagnosis',
        '/results',
        '/results',
    ]);

    rwmb_set_meta($home_page_id, 'eh_home_foundation_kicker', 'The Foundation');
    rwmb_set_meta($home_page_id, 'eh_home_foundation_title', "Start From\na Healthy Scalp");
    rwmb_set_meta($home_page_id, 'eh_home_foundation_body_text', "The scalp is the foundation of every treatment and clinical solution.\n\nMost hair concerns don't begin with the hair. They begin with the scalp that go undiagnosed for years.\n\nAt EUROHAIRLAB, we focus on understand your condition, diagnosing the root before recommending anything");
    rwmb_set_meta($home_page_id, 'eh_home_foundation_button_text', 'Start Your Free Scalp Analysis');
    rwmb_set_meta($home_page_id, 'eh_home_foundation_button_href', '/assessment');
    rwmb_set_meta($home_page_id, 'eh_home_foundation_image', $foundation_image_id);
    rwmb_set_meta($home_page_id, 'eh_home_foundation_video_url', '');

    rwmb_set_meta($home_page_id, 'eh_home_difference_kicker', 'See the Difference');
    rwmb_set_meta($home_page_id, 'eh_home_difference_title', "Real\nTransformations");
    rwmb_set_meta($home_page_id, 'eh_home_difference_body_text', 'Thousands of clients have experienced improved hair density and scalp health through our treatment programs. We deliver more than treatments; we provide clinical clarity, a high-precision diagnosis, and a personalised regenerative roadmap.');
    rwmb_set_meta($home_page_id, 'eh_home_difference_button_text', 'View Real Results');
    rwmb_set_meta($home_page_id, 'eh_home_difference_button_href', '/results');
    rwmb_set_meta($home_page_id, 'eh_home_difference_before_image', $difference_before_id);
    rwmb_set_meta($home_page_id, 'eh_home_difference_after_image', $difference_after_id);
    rwmb_set_meta($home_page_id, 'eh_home_difference_before_label', 'Before');
    rwmb_set_meta($home_page_id, 'eh_home_difference_after_label', 'After');

    $technology_desc_seed = [
        "A scalp detox treatment using DR. SCALP's special technique with an 8-step Korean method. It deeply cleanses dirt and dead skin cells, improves circulation, leaving the scalp feeling fresh and clean, and the hair lighter and ready for further treatments.\n\nSuitable for:\nOily scalp\nHair exposed to chemical processes (coloring/perming)\nClogged scalp\nPreparation for advanced hair treatments",
        "An intensive treatment designed to nourish weak and thinning hair, restore scalp health, and provide full relaxation. It leaves the scalp feeling healthy and relaxed, while the hair appears thicker, stronger, and more radiant.\n\nSuitable for:\nNormal to oily scalp\nDamaged hair\nScalp with buildup of dirt or residue\nA preparatory step before further treatments",
        "An advanced scalp and hair loss treatment using the SCALPFIRSTÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ System with a structured 19-step clinical protocol. It targets hair loss at the root by reactivating follicles, balancing the scalp environment, and improving microcirculationÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Âsupporting stronger, healthier, and long-term hair growth.\n\nSuitable for:\nEarly-stage hair loss\nThinning hair / reduced hair density\nImbalanced or inflamed scalp\nPrevention of Androgenetic Alopecia (AGA)",
        "An advanced treatment designed to control hormonally driven hair loss using the SCALPFIRSTÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ System with a precise 20-step clinical protocol. It works by regulating DHT activity, protecting hair follicles from miniaturization, and stabilizing the scalpÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Âhelping to slow hair loss progression and maintain stronger, healthier hair over time.\n\nSuitable for:\nHormonal hair loss (DHT-related)\nReceding hairline or thinning crown\nEarly to moderate Androgenetic Alopecia (AGA)\nMen experiencing progressive hair thinning",
    ];

    rwmb_set_meta($home_page_id, 'eh_home_technology_kicker', 'EUROHAIRLAB Technology');
    rwmb_set_meta($home_page_id, 'eh_home_technology_title', 'The Science Behind Your Results');
    rwmb_set_meta($home_page_id, 'eh_home_technology_card_titles', [
        'Scalp Detox',
        'Scalp Revival',
        'Regen ActivÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ Hair Loss Treatment',
        'Regen BoostÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ Hormonal Hair Loss Control',
    ]);
    rwmb_set_meta($home_page_id, 'eh_home_technology_card_descriptions', $technology_desc_seed);
    rwmb_set_meta($home_page_id, 'eh_home_technology_card_images', $technology_image_ids);
    rwmb_set_meta($home_page_id, 'eh_home_technology_card_lightbox_titles', [
        'Scalp Detox',
        'Scalp Revival',
        'Regen ActivÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢',
        'Regen BoostÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢',
    ]);

    rwmb_set_meta($home_page_id, 'eh_home_programs_kicker', 'Treatment Programs');
    rwmb_set_meta($home_page_id, 'eh_home_programs_title', 'Personalized Hair Recovery');
    rwmb_set_meta($home_page_id, 'eh_home_programs_body_text', 'At EUROHAIRLAB, every program is structured around the findings of your personal assessment. Ensuring that what you receive is clinically designed around your diagnosis, personalised to your condition, and adjusted as your scalp responds.');
    rwmb_set_meta($home_page_id, 'eh_home_programs_button_text', 'Learn More Treatment Programs');
    rwmb_set_meta($home_page_id, 'eh_home_programs_button_href', '/treatment-programs');
    rwmb_set_meta($home_page_id, 'eh_home_programs_card_titles', [
        'Scalp Detox',
        'Scalp Revival',
        'Regen ActivÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ Hair Loss Treatment',
        'Regen BoostÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ Hormonal Hair Loss Control',
    ]);
    rwmb_set_meta($home_page_id, 'eh_home_programs_card_descriptions', $technology_desc_seed);
    rwmb_set_meta($home_page_id, 'eh_home_programs_card_images', $program_image_ids);
    rwmb_set_meta($home_page_id, 'eh_home_programs_card_durations', [
        '65 minutes',
        '60 minutes',
        '75ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“90 minutes',
        '75ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“90 minutes',
    ]);

    rwmb_set_meta($home_page_id, 'eh_home_testimonial_texts', [
        "I don't go anywhere without the Clean Hand Sanitizer. It's my peace of mind.",
        'I had severe hair thinning after stress and hormonal imbalance. After the 90-day program, my hair density improved significantly.',
        "I don't go anywhere without the Clean Hand Sanitizer. It's my peace of mind.",
        'I had severe hair thinning after stress and hormonal imbalance. After the 90-day program, my hair density improved significantly.',
    ]);
    rwmb_set_meta($home_page_id, 'eh_home_testimonial_names', ['Natasha', 'Angel', 'Natasha', 'Angel']);

    rwmb_set_meta($home_page_id, 'eh_home_consultation_title', 'Start Your Great Hair With Us');
    rwmb_set_meta($home_page_id, 'eh_home_consultation_button_text', 'Start Consultation');
    rwmb_set_meta($home_page_id, 'eh_home_consultation_button_href', 'mailto:hello@eurohairlab.com');
    rwmb_set_meta($home_page_id, 'eh_home_consultation_background_image', $consultation_image_id);

    update_post_meta($home_page_id, '_eh_home_meta_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_home_meta_box_defaults');

/**
 * One-time backfill for home hero slide copy when meta was added after initial seed.
 */
function eurohairlab_migrate_home_hero_slide_copy_meta(): void
{
    if (!is_admin() || !function_exists('rwmb_meta') || !function_exists('rwmb_set_meta')) {
        return;
    }

    if (get_option('eh_home_hero_slide_copy_migrated', '')) {
        return;
    }

    $home_page_id = (int) get_option('page_on_front');
    if (!$home_page_id) {
        $home_page = get_page_by_path('home', OBJECT, 'page');
        $home_page_id = $home_page instanceof WP_Post ? (int) $home_page->ID : 0;
    }

    if (!$home_page_id) {
        return;
    }

    $existing = rwmb_meta('eh_home_hero_slide_copy', [], $home_page_id);
    $normalized_existing = eurohairlab_home_normalize_hero_slide_copy_meta_value($existing);
    if ($normalized_existing !== []) {
        if ($normalized_existing !== $existing) {
            rwmb_set_meta($home_page_id, 'eh_home_hero_slide_copy', $normalized_existing);
        }

        update_option('eh_home_hero_slide_copy_migrated', '1', false);

        return;
    }

    $titles = rwmb_meta('eh_home_hero_titles', [], $home_page_id);
    if (!is_array($titles) || $titles === []) {
        return;
    }

    rwmb_set_meta($home_page_id, 'eh_home_hero_slide_copy', eurohairlab_home_default_hero_slide_copy_meta());
    update_option('eh_home_hero_slide_copy_migrated', '1', false);
}
add_action('admin_init', 'eurohairlab_migrate_home_hero_slide_copy_meta', 25);

/**
 * One-time backfill for home treatment program durations.
 */
function eurohairlab_migrate_home_program_card_durations_meta(): void
{
    if (!is_admin() || !function_exists('rwmb_meta') || !function_exists('rwmb_set_meta')) {
        return;
    }

    if (get_option('eh_home_program_card_durations_migrated', '')) {
        return;
    }

    $home_page_id = (int) get_option('page_on_front');
    if (!$home_page_id) {
        $home_page = get_page_by_path('home', OBJECT, 'page');
        $home_page_id = $home_page instanceof WP_Post ? (int) $home_page->ID : 0;
    }

    if (!$home_page_id) {
        return;
    }

    $existing = rwmb_meta('eh_home_programs_card_durations', [], $home_page_id);
    if (is_array($existing) && $existing !== []) {
        update_option('eh_home_program_card_durations_migrated', '1', false);

        return;
    }

    $fallback = rwmb_meta('eh_home_technology_card_lightbox_titles', [], $home_page_id);
    $technology_durations = ['65 minutes', '60 minutes', '75ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“90 minutes', '75ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“90 minutes'];

    if (is_array($fallback) && $fallback !== []) {
        rwmb_set_meta($home_page_id, 'eh_home_programs_card_durations', $technology_durations);
        update_option('eh_home_program_card_durations_migrated', '1', false);
    }
}
add_action('admin_init', 'eurohairlab_migrate_home_program_card_durations_meta', 26);

function eurohairlab_normalize_internal_meta_href($value)
{
    $value = is_string($value) ? trim($value) : '';

    if ($value === '') {
        return '';
    }

    if (
        str_starts_with($value, '#')
        || str_starts_with($value, 'mailto:')
        || str_starts_with($value, 'tel:')
    ) {
        return $value;
    }

    $parsed = wp_parse_url($value);
    $home   = wp_parse_url(home_url('/'));

    if (!empty($parsed['host']) && !empty($home['host']) && strtolower((string) $parsed['host']) === strtolower((string) $home['host'])) {
        $path = isset($parsed['path']) ? untrailingslashit((string) $parsed['path']) : '';
        if ($path === '') {
            $path = '/';
        }

        if (!empty($parsed['query'])) {
            $path .= '?' . $parsed['query'];
        }

        if (!empty($parsed['fragment'])) {
            $path .= '#' . $parsed['fragment'];
        }

        return $path;
    }

    return $value;
}

function eurohairlab_migrate_home_meta_box_hrefs(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $home_page_id = (int) get_option('page_on_front');
    if (!$home_page_id) {
        $home_page = get_page_by_path('home', OBJECT, 'page');
        $home_page_id = $home_page instanceof WP_Post ? (int) $home_page->ID : 0;
    }

    if (!$home_page_id || get_post_meta($home_page_id, '_eh_home_href_migrated', true)) {
        return;
    }

    $single_fields = [
        'eh_home_foundation_button_href',
        'eh_home_difference_button_href',
        'eh_home_programs_button_href',
        'eh_home_consultation_button_href',
    ];

    foreach ($single_fields as $field_id) {
        $current = rwmb_meta($field_id, [], $home_page_id);
        if (!is_string($current) || $current === '') {
            continue;
        }

        rwmb_set_meta($home_page_id, $field_id, eurohairlab_normalize_internal_meta_href($current));
    }

    $hero_hrefs = rwmb_meta('eh_home_hero_button_hrefs', [], $home_page_id);
    if (is_array($hero_hrefs) && !empty($hero_hrefs)) {
        $hero_hrefs = array_map('eurohairlab_normalize_internal_meta_href', $hero_hrefs);
        rwmb_set_meta($home_page_id, 'eh_home_hero_button_hrefs', $hero_hrefs);
    }

    update_post_meta($home_page_id, '_eh_home_href_migrated', '1');
}
add_action('admin_init', 'eurohairlab_migrate_home_meta_box_hrefs');

function eurohairlab_sanitize_home_clone_text_values(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $home_page_id = (int) get_option('page_on_front');
    if (!$home_page_id) {
        $home_page = get_page_by_path('home', OBJECT, 'page');
        $home_page_id = $home_page instanceof WP_Post ? (int) $home_page->ID : 0;
    }

    if (!$home_page_id || get_post_meta($home_page_id, '_eh_home_clone_text_sanitized', true)) {
        return;
    }

    $clone_text_fields = [
        'eh_home_hero_titles',
        'eh_home_hero_button_texts',
        'eh_home_hero_button_hrefs',
        'eh_home_technology_card_titles',
        'eh_home_technology_card_descriptions',
        'eh_home_technology_card_lightbox_titles',
        'eh_home_programs_card_titles',
        'eh_home_programs_card_descriptions',
        'eh_home_programs_card_durations',
        'eh_home_testimonial_texts',
        'eh_home_testimonial_names',
    ];

    foreach ($clone_text_fields as $field_id) {
        $value = rwmb_meta($field_id, [], $home_page_id);

        if (!is_array($value)) {
            continue;
        }

        $sanitized = array_map(
            static fn($item): string => is_string($item) ? $item : '',
            $value
        );

        rwmb_set_meta($home_page_id, $field_id, $sanitized);
    }

    update_post_meta($home_page_id, '_eh_home_clone_text_sanitized', '1');
}
add_action('admin_init', 'eurohairlab_sanitize_home_clone_text_values');
