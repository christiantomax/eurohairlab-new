<?php

add_filter('rwmb_meta_boxes', 'eurohairlab_blog_list_register_meta_boxes');

function eurohairlab_blog_list_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Blog Listing Page', 'eurohairlab'),
        'id'         => 'eh_blog_list_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'text', 'name' => esc_html__('Page Title', 'eurohairlab'), 'id' => 'eh_blog_list_page_title', 'std' => ''],
        ],
    ];

    return $meta_boxes;
}
