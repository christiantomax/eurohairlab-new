<?php

add_filter('rwmb_meta_boxes', 'eurohairlab_promo_hero_register_meta_boxes');

function eurohairlab_promo_hero_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Promo Hero', 'eurohairlab'),
        'id'         => 'eh_promo_hero_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'single_image', 'name' => esc_html__('Hero Image', 'eurohairlab'), 'id' => 'eh_promo_hero_image', 'std' => 0],
            ['type' => 'textarea', 'name' => esc_html__('Title', 'eurohairlab'), 'id' => 'eh_promo_hero_title', 'std' => ''],
        ],
    ];

    return $meta_boxes;
}
