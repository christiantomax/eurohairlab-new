<?php

add_filter('rwmb_meta_boxes', 'eurohairlab_contact_map_register_meta_boxes');

function eurohairlab_contact_map_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Contact Map Section', 'eurohairlab'),
        'id'         => 'eh_contact_map_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'textarea', 'name' => esc_html__('Map Embed URL', 'eurohairlab'), 'id' => 'eh_contact_map_embed_url', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Hours Title', 'eurohairlab'), 'id' => 'eh_contact_hours_title', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Operating Hours', 'eurohairlab'), 'id' => 'eh_contact_operating_hours', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('WhatsApp Text', 'eurohairlab'), 'id' => 'eh_contact_whatsapp_text', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('WhatsApp Href', 'eurohairlab'), 'id' => 'eh_contact_whatsapp_href', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Appointment Text', 'eurohairlab'), 'id' => 'eh_contact_appointment_text', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Appointment Href', 'eurohairlab'), 'id' => 'eh_contact_appointment_href', 'std' => ''],
        ],
    ];

    return $meta_boxes;
}

function eurohairlab_seed_contact_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $contact_page = get_page_by_path('contact', OBJECT, 'page');
    $contact_page_id = $contact_page instanceof WP_Post ? (int) $contact_page->ID : 0;

    if (!$contact_page_id || get_post_meta($contact_page_id, '_eh_contact_meta_seeded', true)) {
        return;
    }

    rwmb_set_meta($contact_page_id, 'eh_contact_map_embed_url', 'https://www.google.com/maps/d/u/0/embed?mid=1J8SoXlVhDI_sC2xNRYTBhqiw3h5hWVU&ehbc=2E312F&noprof=1');
    rwmb_set_meta($contact_page_id, 'eh_contact_hours_title', 'Operating Hours');
    rwmb_set_meta($contact_page_id, 'eh_contact_operating_hours', '09.00 - 21.00');
    rwmb_set_meta($contact_page_id, 'eh_contact_whatsapp_text', 'WhatsApp Consultation');
    rwmb_set_meta($contact_page_id, 'eh_contact_whatsapp_href', 'https://wa.me/62215550188');
    rwmb_set_meta($contact_page_id, 'eh_contact_appointment_text', 'Book Appointment');
    rwmb_set_meta($contact_page_id, 'eh_contact_appointment_href', '/contact/#contact-form');

    update_post_meta($contact_page_id, '_eh_contact_meta_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_contact_meta_box_defaults');
