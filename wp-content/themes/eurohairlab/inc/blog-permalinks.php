<?php

declare(strict_types=1);

/**
 * Public blog posts use /blog/{post-name}/ permalinks.
 */
function eurohairlab_blog_post_add_rewrite_rules(): void
{
    add_rewrite_rule(
        '^blog/([^/]+)/?$',
        'index.php?name=$matches[1]',
        'top'
    );
}
add_action('init', 'eurohairlab_blog_post_add_rewrite_rules', 5);

/**
 * @param string  $permalink
 * @param WP_Post $post
 * @param bool    $leavename When true, leave placeholder alone (sample permalink in editor).
 */
function eurohairlab_blog_post_permalink(string $permalink, $post, bool $leavename = false): string
{
    if (!$post instanceof WP_Post || $post->post_type !== 'post') {
        return $permalink;
    }

    if ($leavename) {
        return $permalink;
    }

    if (in_array($post->post_status, ['draft', 'pending', 'auto-draft'], true)) {
        return $permalink;
    }

    if ($post->post_name === '') {
        return $permalink;
    }

    return trailingslashit(home_url('blog/' . $post->post_name));
}
add_filter('post_link', 'eurohairlab_blog_post_permalink', 10, 3);

function eurohairlab_blog_post_flush_rewrite_rules(): void
{
    eurohairlab_blog_post_add_rewrite_rules();
    flush_rewrite_rules(false);
}
add_action('after_switch_theme', 'eurohairlab_blog_post_flush_rewrite_rules');

/**
 * One-time flush so existing installs pick up /blog/{slug}/ without re-saving permalinks manually.
 */
function eurohairlab_blog_post_maybe_flush_rewrite_rules(): void
{
    if (get_option('eurohairlab_blog_rewrite_v1')) {
        return;
    }

    eurohairlab_blog_post_add_rewrite_rules();
    flush_rewrite_rules(false);
    update_option('eurohairlab_blog_rewrite_v1', '1');
}
add_action('init', 'eurohairlab_blog_post_maybe_flush_rewrite_rules', 99);
