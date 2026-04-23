<?php

function eurohairlab_is_about_meta_box_page(): bool
{
    if (!is_admin()) {
        return false;
    }

    $post_id = function_exists('eurohairlab_get_current_admin_post_id')
        ? eurohairlab_get_current_admin_post_id()
        : 0;

    if (!$post_id) {
        return false;
    }

    $post = get_post($post_id);

    return $post instanceof WP_Post
        && $post->post_type === 'page'
        && $post->post_name === 'about';
}

add_filter('rwmb_meta_boxes', 'eurohairlab_about_hero_register_meta_boxes');

function eurohairlab_about_hero_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('About Hero Section', 'eurohairlab'),
        'id'         => 'eh_about_hero_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'single_image', 'name' => esc_html__('Hero Image', 'eurohairlab'), 'id' => 'eh_about_hero_image', 'std' => 0],
            ['type' => 'textarea', 'name' => esc_html__('Title', 'eurohairlab'), 'id' => 'eh_about_hero_title', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Body Text', 'eurohairlab'), 'id' => 'eh_about_hero_body_text', 'std' => ''],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_about_foundation_register_meta_boxes');

function eurohairlab_about_foundation_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('About Our Foundation Section', 'eurohairlab'),
        'id'         => 'eh_about_foundation_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'text', 'name' => esc_html__('Kicker', 'eurohairlab'), 'id' => 'eh_about_foundation_kicker', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Title', 'eurohairlab'), 'id' => 'eh_about_foundation_title', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Body Text', 'eurohairlab'), 'id' => 'eh_about_foundation_body_text', 'std' => ''],
            ['type' => 'single_image', 'name' => esc_html__('Image Left', 'eurohairlab'), 'id' => 'eh_about_foundation_image_left', 'std' => 0],
            ['type' => 'single_image', 'name' => esc_html__('Image Right', 'eurohairlab'), 'id' => 'eh_about_foundation_image_right', 'std' => 0],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_about_science_register_meta_boxes');

function eurohairlab_about_science_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('About Korean Scalp Science Section', 'eurohairlab'),
        'id'         => 'eh_about_science_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'text', 'name' => esc_html__('Kicker', 'eurohairlab'), 'id' => 'eh_about_science_kicker', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Title', 'eurohairlab'), 'id' => 'eh_about_science_title', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Body Text', 'eurohairlab'), 'id' => 'eh_about_science_body_text', 'std' => ''],
            ['type' => 'single_image', 'name' => esc_html__('Image', 'eurohairlab'), 'id' => 'eh_about_science_image', 'std' => 0],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_about_partnership_register_meta_boxes');

function eurohairlab_about_partnership_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('About DR.SCALP Korea Partnership Section', 'eurohairlab'),
        'id'         => 'eh_about_partnership_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'text', 'name' => esc_html__('Kicker', 'eurohairlab'), 'id' => 'eh_about_partnership_kicker', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Title', 'eurohairlab'), 'id' => 'eh_about_partnership_title', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Doctor Title', 'eurohairlab'), 'id' => 'eh_about_partnership_member_names', 'clone' => true, 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Doctor Name', 'eurohairlab'), 'id' => 'eh_about_partnership_member_titles', 'clone' => true, 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Doctor Bio', 'eurohairlab'), 'id' => 'eh_about_partnership_member_bios', 'clone' => true, 'std' => ''],
            ['type' => 'single_image', 'name' => esc_html__('Doctor Image', 'eurohairlab'), 'id' => 'eh_about_partnership_member_images', 'clone' => true, 'std' => 0],
            ['type' => 'single_image', 'name' => esc_html__('Doctor Image on Hover', 'eurohairlab'), 'id' => 'eh_about_partnership_member_hover_images', 'clone' => true, 'std' => 0],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_about_clinical_technology_register_meta_boxes');

function eurohairlab_about_clinical_technology_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('About Clinical Technology Section', 'eurohairlab'),
        'id'         => 'eh_about_clinical_technology_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'text', 'name' => esc_html__('Kicker', 'eurohairlab'), 'id' => 'eh_about_clinical_kicker', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Title', 'eurohairlab'), 'id' => 'eh_about_clinical_title', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Body Text', 'eurohairlab'), 'id' => 'eh_about_clinical_body_text', 'std' => ''],
            ['type' => 'single_image', 'name' => esc_html__('Slide Images', 'eurohairlab'), 'id' => 'eh_about_clinical_slide_images', 'clone' => true, 'std' => 0],
            ['type' => 'text', 'name' => esc_html__('Slide Titles', 'eurohairlab'), 'id' => 'eh_about_clinical_slide_titles', 'clone' => true, 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Slide Descriptions', 'eurohairlab'), 'id' => 'eh_about_clinical_slide_descriptions', 'clone' => true, 'std' => ''],
        ],
    ];

    return $meta_boxes;
}

add_filter('rwmb_meta_boxes', 'eurohairlab_about_premium_experience_register_meta_boxes');

function eurohairlab_about_premium_experience_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('About Premium Clinic Experience Section', 'eurohairlab'),
        'id'         => 'eh_about_premium_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'text', 'name' => esc_html__('Kicker', 'eurohairlab'), 'id' => 'eh_about_premium_kicker', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Title', 'eurohairlab'), 'id' => 'eh_about_premium_title', 'std' => ''],
            ['type' => 'single_image', 'name' => esc_html__('Slide Images', 'eurohairlab'), 'id' => 'eh_about_premium_slide_images', 'clone' => true, 'std' => 0],
            ['type' => 'text', 'name' => esc_html__('Slide Titles', 'eurohairlab'), 'id' => 'eh_about_premium_slide_titles', 'clone' => true, 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Slide Descriptions', 'eurohairlab'), 'id' => 'eh_about_premium_slide_descriptions', 'clone' => true, 'std' => ''],
        ],
    ];

    return $meta_boxes;
}

function eurohairlab_seed_about_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $about_page = get_page_by_path('about', OBJECT, 'page');
    $about_page_id = $about_page instanceof WP_Post ? (int) $about_page->ID : 0;

    if (!$about_page_id || get_post_meta($about_page_id, '_eh_about_meta_seeded', true)) {
        return;
    }

    $hero_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/about-hero.webp', $about_page_id);
    $foundation_left_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/about-story-main.webp', $about_page_id);
    $foundation_right_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/about-story-side.webp', $about_page_id);
    $science_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/about-korean-science.webp', $about_page_id);
    $partnership_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/about-partnership-team.webp', $about_page_id);
    $clinical_image_ids = array_values(array_filter([
        (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/about-technology.webp', $about_page_id),
        (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/diagnosis-density.webp', $about_page_id),
        (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/diagnosis-hero.webp', $about_page_id),
        (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/diagnosis-intro.webp', $about_page_id),
        (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/treatment-technology.webp', $about_page_id),
    ]));
    $premium_image_ids = array_values(array_filter([
        (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/about-privacy-1.webp', $about_page_id),
        (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/about-privacy-2.webp', $about_page_id),
        (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/about-privacy-3.webp', $about_page_id),
    ]));

    rwmb_set_meta($about_page_id, 'eh_about_hero_image', $hero_image_id);
    rwmb_set_meta($about_page_id, 'eh_about_hero_title', "The 1st Korean Scalp\nClinic In Jakarta");
    rwmb_set_meta($about_page_id, 'eh_about_hero_body_text', 'EUROHAIRLAB Brings Korean Scalp Methodology And Genuinely Personalised Care To The City.');

    rwmb_set_meta($about_page_id, 'eh_about_foundation_kicker', 'Our Foundation');
    rwmb_set_meta($about_page_id, 'eh_about_foundation_title', 'World-Proven Built for You');
    rwmb_set_meta($about_page_id, 'eh_about_foundation_body_text', "EUROHAIRLAB is the authorised Indonesian franchisee of DR.SCALP Korea, a global scalp care institution with over 17 years of clinical experience, more than 3 million patients treated, 360 clinics across 20+ countries.");
    rwmb_set_meta($about_page_id, 'eh_about_foundation_image_left', $foundation_left_image_id);
    rwmb_set_meta($about_page_id, 'eh_about_foundation_image_right', $foundation_right_image_id);

    rwmb_set_meta($about_page_id, 'eh_about_science_kicker', 'Korean Scalp Science');
    rwmb_set_meta($about_page_id, 'eh_about_science_title', 'ScalpFirst™');
    rwmb_set_meta($about_page_id, 'eh_about_science_body_text', "ScalpFirst™ is EUROHAIRLAB's structured approach that places the scalp at the centre of every treatment decision. Every step is guided by what your diagnostic assessment reveals.\n\nAt EUROHAIRLAB, every decision follows this principle. Assessment before recommendation. Diagnosis before treatment.");
    rwmb_set_meta($about_page_id, 'eh_about_science_image', $science_image_id);

    rwmb_set_meta($about_page_id, 'eh_about_partnership_kicker', 'The DR.SCALP Korea Partnership');
    rwmb_set_meta($about_page_id, 'eh_about_partnership_title', 'Guided By Experts');
    rwmb_set_meta($about_page_id, 'eh_about_partnership_member_names', ['Eliza Ennio Gunawan M']);
    rwmb_set_meta($about_page_id, 'eh_about_partnership_member_titles', ['Dokter Spesialis']);
    rwmb_set_meta($about_page_id, 'eh_about_partnership_member_bios', ["EUROHAIRLAB Is Administered By Licensed Medical Doctors With Dedicated Training In Scalp Medicine.\n\nThe Expertise Is Global.\n\nThe Care Is Personal."]);
    rwmb_set_meta($about_page_id, 'eh_about_partnership_member_images', [$partnership_image_id]);
    rwmb_set_meta($about_page_id, 'eh_about_partnership_member_hover_images', [$partnership_image_id]);

    rwmb_set_meta($about_page_id, 'eh_about_clinical_kicker', 'Clinical Technology');
    rwmb_set_meta($about_page_id, 'eh_about_clinical_title', 'Precision Technology for Your Condition');
    rwmb_set_meta($about_page_id, 'eh_about_clinical_body_text', 'Every technology at EUROHAIRLAB is sourced from DR.SCALP Korea\'s clinical platform. We combine modern diagnostic tools, scalp imaging technology, and regenerative treatment platforms to accurately identify the root cause of hair loss and deliver targeted solutions.');
    rwmb_set_meta($about_page_id, 'eh_about_clinical_slide_images', $clinical_image_ids);
    rwmb_set_meta($about_page_id, 'eh_about_clinical_slide_titles', [
        'Scalp Imaging System',
        'Density Mapping Review',
        'Follicle Condition Check',
        'Structured Diagnostic Support',
        'Targeted Treatment Planning',
    ]);
    rwmb_set_meta($about_page_id, 'eh_about_clinical_slide_descriptions', [
        'At Eurohairlab, every treatment begins with precise clinical analysis. We combine modern diagnostic tools, scalp imaging technology, and regenerative treatment platforms to accurately identify the root cause of hair loss and deliver targeted solutions.',
        'High-visibility imaging helps our team compare scalp zones, monitor density shifts, and define treatment priorities with less guesswork.',
        'Close scalp review supports a more accurate understanding of follicle behavior, scalp sensitivity, and the condition behind visible thinning.',
        'Each consultation combines observation, device-assisted review, and symptom history so treatment recommendations are based on evidence.',
        'Once the scalp condition is defined, we build a treatment roadmap that aligns in-clinic procedures with realistic recovery milestones.',
    ]);

    rwmb_set_meta($about_page_id, 'eh_about_premium_kicker', 'Premium Clinic Experience');
    rwmb_set_meta($about_page_id, 'eh_about_premium_title', 'Designed For Comfort And Privacy');
    rwmb_set_meta($about_page_id, 'eh_about_premium_slide_images', $premium_image_ids);
    rwmb_set_meta($about_page_id, 'eh_about_premium_slide_titles', ['', '', '']);
    rwmb_set_meta($about_page_id, 'eh_about_premium_slide_descriptions', ['', '', '']);

    update_post_meta($about_page_id, '_eh_about_meta_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_about_meta_box_defaults');
