<?php

declare(strict_types=1);

/**
 * `edit_post()` in wp-admin/includes/post.php assumes every `$_POST['meta'][ $meta_row_id ]` entry is an array
 * with both `key` and `value` keys (classic “Custom Fields” UI). Missing `value` triggers PHP 8+ notices,
 * which emit output before redirects (“Cannot modify header information”).
 *
 */
add_action(
    'admin_init',
    static function (): void {
        if (empty($_POST['meta']) || !is_array($_POST['meta'])) {
            return;
        }

        foreach ($_POST['meta'] as $meta_id => $payload) {
            if (!is_array($payload)) {
                unset($_POST['meta'][$meta_id]);
                continue;
            }

            if (!array_key_exists('key', $payload)) {
                unset($_POST['meta'][$meta_id]);
                continue;
            }

            if (!array_key_exists('value', $payload)) {
                $_POST['meta'][$meta_id]['value'] = '';
            }
        }
    },
    0
);
