<?php

add_filter('rwmb_meta_boxes', 'eurohairlab_global_seo_register_meta_boxes');

function eurohairlab_global_seo_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('SEO Settings', 'eurohairlab'),
        'id'         => 'eh_global_seo_settings',
        'post_types' => ['page', 'post'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'core',
        'closed'     => true,
        'fields'     => [
            [
                'type' => 'text',
                'name' => esc_html__('Meta Title', 'eurohairlab'),
                'id'   => 'eh_seo_meta_title',
                'std'  => '',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Meta Description', 'eurohairlab'),
                'id'   => 'eh_seo_meta_description',
                'std'  => '',
            ],
            [
                'type' => 'text',
                'name' => esc_html__('Open Graph Title', 'eurohairlab'),
                'id'   => 'eh_seo_og_title',
                'std'  => '',
            ],
            [
                'type' => 'textarea',
                'name' => esc_html__('Open Graph Description', 'eurohairlab'),
                'id'   => 'eh_seo_og_description',
                'std'  => '',
            ],
            [
                'type' => 'single_image',
                'name' => esc_html__('Open Graph Image', 'eurohairlab'),
                'id'   => 'eh_seo_og_image',
                'std'  => 0,
            ],
            [
                'type' => 'checkbox',
                'name' => esc_html__('Noindex', 'eurohairlab'),
                'id'   => 'eh_seo_noindex',
                'std'  => 0,
            ],
        ],
    ];

    return $meta_boxes;
}

function eurohairlab_seed_home_seo_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $home_page_id = (int) get_option('page_on_front');
    if (!$home_page_id) {
        $home_page = get_page_by_path('home', OBJECT, 'page');
        $home_page_id = $home_page instanceof WP_Post ? (int) $home_page->ID : 0;
    }

    if (!$home_page_id || get_post_meta($home_page_id, '_eh_home_seo_seeded', true)) {
        return;
    }

    $og_image_id = 0;
    if (function_exists('eurohairlab_import_theme_asset_to_media_library')) {
        $og_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/hero-bg.webp', $home_page_id);
    }

    rwmb_set_meta($home_page_id, 'eh_seo_meta_title', 'Eurohairlab | Advanced Hair & Scalp Science');
    rwmb_set_meta($home_page_id, 'eh_seo_meta_description', 'Eurohairlab provides scalp-first diagnosis, personalized treatment programs, and measurable hair recovery support through advanced clinical care.');
    rwmb_set_meta($home_page_id, 'eh_seo_og_title', 'Eurohairlab | Advanced Hair & Scalp Science');
    rwmb_set_meta($home_page_id, 'eh_seo_og_description', 'Start with scalp-first diagnosis, explore personalized recovery programs, and see measurable hair transformation at Eurohairlab.');
    rwmb_set_meta($home_page_id, 'eh_seo_noindex', 0);

    if ($og_image_id > 0) {
        rwmb_set_meta($home_page_id, 'eh_seo_og_image', $og_image_id);
    }

    update_post_meta($home_page_id, '_eh_home_seo_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_home_seo_meta_box_defaults');

function eurohairlab_seed_about_seo_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $about_page = get_page_by_path('about', OBJECT, 'page');
    $about_page_id = $about_page instanceof WP_Post ? (int) $about_page->ID : 0;

    if (!$about_page_id || get_post_meta($about_page_id, '_eh_about_seo_seeded', true)) {
        return;
    }

    $og_image_id = 0;
    if (function_exists('eurohairlab_import_theme_asset_to_media_library')) {
        $og_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/about-hero.webp', $about_page_id);
    }

    rwmb_set_meta($about_page_id, 'eh_seo_meta_title', 'About Eurohairlab | Korean Scalp Clinic In Jakarta');
    rwmb_set_meta($about_page_id, 'eh_seo_meta_description', 'Learn about Eurohairlab, the first Korean scalp clinic in Jakarta, bringing scalp-first methodology and personalised care through the DR.SCALP Korea partnership.');
    rwmb_set_meta($about_page_id, 'eh_seo_og_title', 'About Eurohairlab | Korean Scalp Clinic In Jakarta');
    rwmb_set_meta($about_page_id, 'eh_seo_og_description', 'Explore Eurohairlab’s foundation, Korean scalp science, DR.SCALP Korea partnership, and premium clinic experience.');
    rwmb_set_meta($about_page_id, 'eh_seo_noindex', 0);

    if ($og_image_id > 0) {
        rwmb_set_meta($about_page_id, 'eh_seo_og_image', $og_image_id);
    }

    update_post_meta($about_page_id, '_eh_about_seo_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_about_seo_meta_box_defaults');

function eurohairlab_seed_diagnosis_seo_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $diagnosis_page = get_page_by_path('diagnosis', OBJECT, 'page');
    $diagnosis_page_id = $diagnosis_page instanceof WP_Post ? (int) $diagnosis_page->ID : 0;

    if (!$diagnosis_page_id || get_post_meta($diagnosis_page_id, '_eh_diagnosis_seo_seeded', true)) {
        return;
    }

    $og_image_id = 0;
    if (function_exists('eurohairlab_import_theme_asset_to_media_library')) {
        $og_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/diagnosis-hero.webp', $diagnosis_page_id);
    }

    rwmb_set_meta($diagnosis_page_id, 'eh_seo_meta_title', 'Diagnosis | Eurohairlab Scalp Analysis & Hair Loss Assessment');
    rwmb_set_meta($diagnosis_page_id, 'eh_seo_meta_description', 'Understand your scalp first with Eurohairlab diagnosis. Explore scalp imaging, hair loss causes, and density mapping analysis before treatment.');
    rwmb_set_meta($diagnosis_page_id, 'eh_seo_og_title', 'Diagnosis | Eurohairlab Scalp Analysis & Hair Loss Assessment');
    rwmb_set_meta($diagnosis_page_id, 'eh_seo_og_description', 'Discover how Eurohairlab evaluates scalp health through advanced scalp imaging, hair loss cause analysis, and density mapping.');
    rwmb_set_meta($diagnosis_page_id, 'eh_seo_noindex', 0);

    if ($og_image_id > 0) {
        rwmb_set_meta($diagnosis_page_id, 'eh_seo_og_image', $og_image_id);
    }

    update_post_meta($diagnosis_page_id, '_eh_diagnosis_seo_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_diagnosis_seo_meta_box_defaults');

function eurohairlab_seed_contact_seo_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $contact_page = get_page_by_path('contact', OBJECT, 'page');
    $contact_page_id = $contact_page instanceof WP_Post ? (int) $contact_page->ID : 0;

    if (!$contact_page_id || get_post_meta($contact_page_id, '_eh_contact_seo_seeded', true)) {
        return;
    }

    rwmb_set_meta($contact_page_id, 'eh_seo_meta_title', 'Contact Eurohairlab | Clinic Location & Appointment');
    rwmb_set_meta($contact_page_id, 'eh_seo_meta_description', 'Find Eurohairlab clinic location, operating hours, WhatsApp consultation access, and appointment booking information.');
    rwmb_set_meta($contact_page_id, 'eh_seo_og_title', 'Contact Eurohairlab | Clinic Location & Appointment');
    rwmb_set_meta($contact_page_id, 'eh_seo_og_description', 'Visit Eurohairlab clinic, check operating hours, and contact the team for WhatsApp consultation or booking.');
    rwmb_set_meta($contact_page_id, 'eh_seo_noindex', 0);

    update_post_meta($contact_page_id, '_eh_contact_seo_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_contact_seo_meta_box_defaults');

function eurohairlab_seed_promo_seo_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $promo_page = get_page_by_path('promo', OBJECT, 'page');
    $promo_page_id = $promo_page instanceof WP_Post ? (int) $promo_page->ID : 0;

    if (!$promo_page_id || get_post_meta($promo_page_id, '_eh_promo_seo_seeded', true)) {
        return;
    }

    $og_image_id = 0;
    if (function_exists('eurohairlab_import_theme_asset_to_media_library')) {
        $og_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/promo-hero.webp', $promo_page_id);
    }

    rwmb_set_meta($promo_page_id, 'eh_seo_meta_title', 'Promo Eurohairlab | Special Offers & Consultation Deals');
    rwmb_set_meta($promo_page_id, 'eh_seo_meta_description', 'Explore Eurohairlab special offers, consultation promos, and limited-time treatment deals designed for first visits and ongoing care.');
    rwmb_set_meta($promo_page_id, 'eh_seo_og_title', 'Promo Eurohairlab | Special Offers & Consultation Deals');
    rwmb_set_meta($promo_page_id, 'eh_seo_og_description', 'See the latest Eurohairlab special offers and consultation promos available for your scalp and hair care journey.');
    rwmb_set_meta($promo_page_id, 'eh_seo_noindex', 0);

    if ($og_image_id > 0) {
        rwmb_set_meta($promo_page_id, 'eh_seo_og_image', $og_image_id);
    }

    update_post_meta($promo_page_id, '_eh_promo_seo_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_promo_seo_meta_box_defaults');

function eurohairlab_seed_blog_list_seo_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $blog_list_page = get_page_by_path('blog-list', OBJECT, 'page');
    $blog_list_page_id = $blog_list_page instanceof WP_Post ? (int) $blog_list_page->ID : 0;

    if (!$blog_list_page_id || get_post_meta($blog_list_page_id, '_eh_blog_list_seo_seeded', true)) {
        return;
    }

    rwmb_set_meta($blog_list_page_id, 'eh_seo_meta_title', 'Blog Eurohairlab | Scalp Science & Hair Loss Journal');
    rwmb_set_meta($blog_list_page_id, 'eh_seo_meta_description', 'Read the Eurohairlab blog for scalp science insights, hair loss education, Korean hair technology, and practical hair health guidance.');
    rwmb_set_meta($blog_list_page_id, 'eh_seo_og_title', 'Blog Eurohairlab | Scalp Science & Hair Loss Journal');
    rwmb_set_meta($blog_list_page_id, 'eh_seo_og_description', 'Explore the Eurohairlab journal covering scalp-first diagnosis, hair recovery knowledge, and clinical hair care education.');
    rwmb_set_meta($blog_list_page_id, 'eh_seo_noindex', 0);

    update_post_meta($blog_list_page_id, '_eh_blog_list_seo_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_blog_list_seo_meta_box_defaults');

function eurohairlab_seed_assessment_seo_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $assessment_page = get_page_by_path('assessment', OBJECT, 'page');
    $assessment_page_id = $assessment_page instanceof WP_Post ? (int) $assessment_page->ID : 0;

    if (!$assessment_page_id || get_post_meta($assessment_page_id, '_eh_assessment_seo_seeded', true)) {
        return;
    }

    $og_image_id = 0;
    if (function_exists('eurohairlab_import_theme_asset_to_media_library')) {
        $og_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-home.webp', $assessment_page_id);
    }

    rwmb_set_meta($assessment_page_id, 'eh_seo_meta_title', 'Assessment | Eurohairlab Online Hair Assessment');
    rwmb_set_meta($assessment_page_id, 'eh_seo_meta_description', 'Start your Eurohairlab online hair assessment to share your concerns, understand your scalp condition, and take the first step toward a personalized treatment plan.');
    rwmb_set_meta($assessment_page_id, 'eh_seo_og_title', 'Assessment | Eurohairlab Online Hair Assessment');
    rwmb_set_meta($assessment_page_id, 'eh_seo_og_description', 'Complete the Eurohairlab online hair assessment and begin your scalp-first diagnosis journey.');
    rwmb_set_meta($assessment_page_id, 'eh_seo_noindex', 0);

    if ($og_image_id > 0) {
        rwmb_set_meta($assessment_page_id, 'eh_seo_og_image', $og_image_id);
    }

    update_post_meta($assessment_page_id, '_eh_assessment_seo_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_assessment_seo_meta_box_defaults');
