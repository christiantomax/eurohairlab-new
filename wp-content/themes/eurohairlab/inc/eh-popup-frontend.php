<?php

declare(strict_types=1);

/**
 * Frontend popup modal (reads wp_eh_popups, respects eurohairlab_get_public_lang()).
 */

add_action('wp_enqueue_scripts', 'eh_popup_enqueue_assets', 30);

function eh_popup_enqueue_assets(): void
{
    $row = eh_popup_get_active();
    if (!eh_popup_should_show_on_current_request($row)) {
        return;
    }

    $theme_uri = get_template_directory_uri();
    $version = wp_get_theme()->get('Version') ?: '1.0.0';

    wp_enqueue_style(
        'eh-popup',
        $theme_uri . '/assets/css/popup.css',
        [],
        $version
    );

    wp_enqueue_script(
        'eh-popup',
        $theme_uri . '/assets/js/popup.js',
        [],
        $version,
        true
    );

    wp_localize_script('eh-popup', 'ehPopup', [
        'delayMs' => (int) ($row['delay_ms'] ?? 0),
        'trigger' => (string) ($row['trigger_event'] ?? 'pageLoaded'),
        'closeOnEsc' => !empty($row['close_on_esc']),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'popupId' => (int) ($row['id'] ?? 0),
        'nonce' => wp_create_nonce('eh_popup_view'),
    ]);
}

add_action('wp_footer', 'eh_popup_render_markup', 5);

function eh_popup_render_markup(): void
{
    $row = eh_popup_get_active();
    if (!eh_popup_should_show_on_current_request($row)) {
        return;
    }

    get_template_part('template-parts/site', 'popup', ['row' => $row]);
}

add_action('wp_ajax_eh_popup_record_view', 'eh_popup_ajax_record_view');
add_action('wp_ajax_nopriv_eh_popup_record_view', 'eh_popup_ajax_record_view');

function eh_popup_ajax_record_view(): void
{
    check_ajax_referer('eh_popup_view', 'nonce');

    $id = isset($_POST['popupId']) ? (int) wp_unslash($_POST['popupId']) : 0;
    if ($id > 0) {
        eh_popup_increment_views($id);
    }

    wp_send_json_success();
}

add_action('wp_head', 'eh_popup_print_custom_css', 99);

/**
 * Drop Popup Box / old theme rules that override close-button placement.
 */
function eh_popup_sanitize_legacy_custom_css(string $css): string
{
    if ($css === '') {
        return '';
    }

    $patterns = [
        '/\.ays[_-][^{]*close[^{]*\{[^}]*\}/is',
        '/\.eh-popup__body\s+\.eh-popup__close\s*\{[^}]*\}/is',
        '/\.close-template-btn[^{]*\{[^}]*\}/is',
        '/\.close-template-btn-container[^{]*\{[^}]*\}/is',
    ];

    foreach ($patterns as $pattern) {
        $css = (string) preg_replace($pattern, '', $css);
    }

    return trim($css);
}

function eh_popup_print_custom_css(): void
{
    $row = eh_popup_get_active();
    if (!eh_popup_should_show_on_current_request($row)) {
        return;
    }

    $css = isset($row['custom_css']) ? trim((string) $row['custom_css']) : '';
    if ($css === '') {
        return;
    }

    $css = eh_popup_sanitize_legacy_custom_css($css);
    if ($css === '') {
        return;
    }

    echo "\n<style id=\"eh-popup-custom-css\">\n";
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored CSS from Popups settings.
    echo $css;
    echo "\n</style>\n";
}
