<?php

declare(strict_types=1);

/**
 * Custom table storage for site popups (replaces Popup Box / wp_ays_pb).
 */

const EH_POPUP_DB_VERSION = '1.1.0';
const EH_POPUP_DB_VERSION_OPTION = 'eh_popup_db_version';

/**
 * @return string
 */
function eh_popup_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'eh_popups';
}

function eh_popup_install_table(): void
{
    global $wpdb;

    $table = eh_popup_table_name();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL DEFAULT 0,
        admin_title varchar(255) NOT NULL DEFAULT '',
        is_active tinyint(1) NOT NULL DEFAULT 1,
        headline_en text NOT NULL,
        headline_id text NOT NULL,
        cta_text_en varchar(255) NOT NULL DEFAULT '',
        cta_text_id varchar(255) NOT NULL DEFAULT '',
        cta_url varchar(500) NOT NULL DEFAULT '',
        image_attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
        overlay_opacity decimal(4,3) NOT NULL DEFAULT 0.500,
        delay_ms int unsigned NOT NULL DEFAULT 0,
        trigger_event varchar(32) NOT NULL DEFAULT 'pageLoaded',
        show_page_ids text NULL,
        close_on_esc tinyint(1) NOT NULL DEFAULT 1,
        custom_css mediumtext NULL,
        popup_width_percent tinyint unsigned NOT NULL DEFAULT 50,
        popup_height_px smallint unsigned NOT NULL DEFAULT 500,
        views bigint(20) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY is_active (is_active)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    eh_popup_migrate_db_exclude_to_show_pages();

    update_option(EH_POPUP_DB_VERSION_OPTION, EH_POPUP_DB_VERSION);
}

function eh_popup_maybe_install_table(): void
{
    $installed = get_option(EH_POPUP_DB_VERSION_OPTION);

    if ($installed !== EH_POPUP_DB_VERSION) {
        eh_popup_install_table();
        eh_popup_maybe_migrate_from_ays();

        return;
    }

    eh_popup_migrate_db_exclude_to_show_pages();
}

/**
 * Rename legacy exclude_page_ids column to show_page_ids.
 */
function eh_popup_migrate_db_exclude_to_show_pages(): void
{
    if (get_option('eh_popup_db_show_pages_column_migrated') === '1') {
        return;
    }

    global $wpdb;

    $table = eh_popup_table_name();
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $columns = $wpdb->get_col("DESCRIBE {$table}", 0);

    if (!is_array($columns)) {
        return;
    }

    if (in_array('exclude_page_ids', $columns, true) && !in_array('show_page_ids', $columns, true)) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("ALTER TABLE {$table} CHANGE exclude_page_ids show_page_ids text NULL");
    } elseif (!in_array('show_page_ids', $columns, true)) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN show_page_ids text NULL AFTER trigger_event");
    }

    update_option('eh_popup_db_show_pages_column_migrated', '1');
}

add_action('after_switch_theme', 'eh_popup_install_table');
add_action('init', 'eh_popup_maybe_install_table', 5);

/**
 * @return array<string, mixed>|null
 */
function eh_popup_get_row(int $id): ?array
{
    global $wpdb;

    if ($id <= 0) {
        return null;
    }

    $table = eh_popup_table_name();
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
        ARRAY_A
    );

    return is_array($row) ? $row : null;
}

/**
 * @return array<string, mixed>|null
 */
function eh_popup_get_row_by_post_id(int $post_id): ?array
{
    global $wpdb;

    if ($post_id <= 0) {
        return null;
    }

    $table = eh_popup_table_name();
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d LIMIT 1", $post_id),
        ARRAY_A
    );

    return is_array($row) ? $row : null;
}

/**
 * First active popup for the frontend.
 *
 * @return array<string, mixed>|null
 */
function eh_popup_get_active(): ?array
{
    global $wpdb;

    $table = eh_popup_table_name();
    $row = $wpdb->get_row(
        "SELECT * FROM {$table} WHERE is_active = 1 ORDER BY id ASC LIMIT 1",
        ARRAY_A
    );

    return is_array($row) ? $row : null;
}

/**
 * @param array<string, mixed> $data
 */
function eh_popup_upsert_from_post(int $post_id, array $data): int
{
    global $wpdb;

    $table = eh_popup_table_name();
    $existing = eh_popup_get_row_by_post_id($post_id);
    $now = current_time('mysql');

    $record = [
        'post_id' => $post_id,
        'admin_title' => (string) ($data['admin_title'] ?? ''),
        'is_active' => !empty($data['is_active']) ? 1 : 0,
        'headline_en' => (string) ($data['headline_en'] ?? ''),
        'headline_id' => (string) ($data['headline_id'] ?? ''),
        'cta_text_en' => (string) ($data['cta_text_en'] ?? ''),
        'cta_text_id' => (string) ($data['cta_text_id'] ?? ''),
        'cta_url' => esc_url_raw((string) ($data['cta_url'] ?? '')),
        'image_attachment_id' => max(0, (int) ($data['image_attachment_id'] ?? 0)),
        'overlay_opacity' => min(1, max(0, (float) ($data['overlay_opacity'] ?? 0.5))),
        'delay_ms' => max(0, (int) ($data['delay_ms'] ?? 0)),
        'trigger_event' => in_array(($data['trigger_event'] ?? ''), ['pageLoaded'], true)
            ? (string) $data['trigger_event']
            : 'pageLoaded',
        'show_page_ids' => wp_json_encode(eh_popup_normalize_page_ids($data['show_page_ids'] ?? [])),
        'close_on_esc' => !empty($data['close_on_esc']) ? 1 : 0,
        'custom_css' => (string) ($data['custom_css'] ?? ''),
        'popup_width_percent' => min(100, max(20, (int) ($data['popup_width_percent'] ?? 50))),
        'popup_height_px' => min(2000, max(200, (int) ($data['popup_height_px'] ?? 500))),
        'updated_at' => $now,
    ];

    if ($existing) {
        $wpdb->update($table, $record, ['id' => (int) $existing['id']]);

        return (int) $existing['id'];
    }

    $record['created_at'] = $now;
    $record['views'] = 0;
    $wpdb->insert($table, $record);

    return (int) $wpdb->insert_id;
}

/**
 * @param mixed $raw
 * @return list<int>
 */
/**
 * Front page ID, else page with slug `home`.
 */
function eh_popup_get_default_home_page_id(): int
{
    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id > 0) {
        return $front_page_id;
    }

    $home = get_page_by_path('home', OBJECT, 'page');

    return $home instanceof WP_Post ? (int) $home->ID : 0;
}

/**
 * @param mixed $raw
 * @return list<int>
 */
function eh_popup_normalize_page_ids(mixed $raw): array
{
    if (is_string($raw)) {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        if ($raw[0] === '[') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = array_map('trim', explode(',', $raw));
            }
        } else {
            $raw = array_map('trim', explode(',', $raw));
        }
    }

    if (!is_array($raw)) {
        return [];
    }

    $ids = [];
    foreach ($raw as $item) {
        $id = (int) $item;
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

/**
 * @param array<string, mixed> $row
 * @return list<int>
 */
function eh_popup_row_show_page_ids(array $row): array
{
    $raw = $row['show_page_ids'] ?? ($row['exclude_page_ids'] ?? '[]');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        $ids = eh_popup_normalize_page_ids(is_array($decoded) ? $decoded : []);
    } else {
        $ids = [];
    }

    if ($ids !== []) {
        return $ids;
    }

    $default_home = eh_popup_get_default_home_page_id();

    return $default_home > 0 ? [$default_home] : [];
}

function eh_popup_increment_views(int $id): void
{
    global $wpdb;

    if ($id <= 0) {
        return;
    }

    $table = eh_popup_table_name();
    $wpdb->query($wpdb->prepare("UPDATE {$table} SET views = views + 1 WHERE id = %d", $id));
}

/**
 * Localized headline / CTA for current public language.
 *
 * @param array<string, mixed> $row
 */
function eh_popup_localized(string $base_key, array $row): string
{
    $en = isset($row[$base_key . '_en']) ? (string) $row[$base_key . '_en'] : '';
    $id = isset($row[$base_key . '_id']) ? (string) $row[$base_key . '_id'] : '';

    if (function_exists('eurohairlab_get_public_lang') && eurohairlab_get_public_lang() === 'id') {
        $id = trim($id);

        return $id !== '' ? $id : $en;
    }

    return $en;
}

function eh_popup_should_show_on_current_request(?array $row): bool
{
    if ($row === null || empty($row['is_active'])) {
        return false;
    }

    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return false;
    }

    if (!is_page()) {
        return false;
    }

    $page_id = (int) get_queried_object_id();
    if ($page_id <= 0) {
        return false;
    }

    $show_on = eh_popup_row_show_page_ids($row);

    return in_array($page_id, $show_on, true);
}

/**
 * One-time seed from legacy Popup Box row + default metabox values.
 */
function eh_popup_maybe_migrate_from_ays(): void
{
    if (get_option('eh_popup_migrated_from_ays') === '1') {
        return;
    }

    global $wpdb;

    $legacy = $wpdb->prefix . 'ays_pb';
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $legacy));
    if ($exists !== $legacy) {
        update_option('eh_popup_migrated_from_ays', '1');

        return;
    }

    $legacy_row = $wpdb->get_row("SELECT * FROM {$legacy} WHERE id = 1 LIMIT 1", ARRAY_A);
    if (!is_array($legacy_row)) {
        update_option('eh_popup_migrated_from_ays', '1');

        return;
    }

    $options = [];
    if (!empty($legacy_row['options'])) {
        $decoded = json_decode((string) $legacy_row['options'], true);
        if (is_array($decoded)) {
            $options = $decoded;
        }
    }

    $post_id = eh_popup_ensure_default_cpt_post();
    $default_show = array_filter([eh_popup_get_default_home_page_id()]);

    if (function_exists('rwmb_set_meta')) {
        rwmb_set_meta($post_id, 'eh_popup_active', ($legacy_row['onoffswitch'] ?? '') === 'On' ? 1 : 0);
        rwmb_set_meta($post_id, 'eh_popup_headline_en', (string) ($legacy_row['description'] ?? ''));
        rwmb_set_meta($post_id, 'eh_popup_headline_id', '');
        rwmb_set_meta($post_id, 'eh_popup_cta_text_en', 'Diagnose Now');
        rwmb_set_meta($post_id, 'eh_popup_cta_text_id', '');
        rwmb_set_meta(
            $post_id,
            'eh_popup_cta_url',
            'https://assessment.eurohairlab.com/assessment?utm_source=redirect&utm_medium=web&utm_campaign=euro_launch'
        );
        rwmb_set_meta($post_id, 'eh_popup_custom_css', (string) ($legacy_row['custom_css'] ?? ''));
        rwmb_set_meta($post_id, 'eh_popup_overlay_opacity', (float) ($legacy_row['overlay_opacity'] ?? 0.5));
        rwmb_set_meta($post_id, 'eh_popup_delay_ms', (int) ($legacy_row['delay'] ?? 0));
        rwmb_set_meta($post_id, 'eh_popup_trigger', 'pageLoaded');
        rwmb_set_meta($post_id, 'eh_popup_show_page_ids', $default_show);
        rwmb_set_meta($post_id, 'eh_popup_close_on_esc', 1);
        rwmb_set_meta($post_id, 'eh_popup_width_percent', (int) ($legacy_row['width'] ?? 50));
        rwmb_set_meta($post_id, 'eh_popup_height_px', (int) ($legacy_row['height'] ?? 500));

        $bg = (string) ($legacy_row['bg_image'] ?? '');
        if ($bg !== '') {
            $attachment_id = attachment_url_to_postid($bg);
            if ($attachment_id > 0) {
                if (function_exists('eurohairlab_optimize_popup_panel_attachment')) {
                    eurohairlab_optimize_popup_panel_attachment($attachment_id);
                }
                rwmb_set_meta($post_id, 'eh_popup_image', $attachment_id);
            }
        }
    }

    if (function_exists('eh_popup_sync_post_to_table')) {
        eh_popup_sync_post_to_table($post_id);
    }

    update_option('eh_popup_migrated_from_ays', '1');
}

/**
 * Ensures one CPT entry exists for editing in wp-admin.
 */
function eh_popup_ensure_default_cpt_post(): int
{
    $existing = get_posts([
        'post_type' => 'eh_popup',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);

    if (!empty($existing[0])) {
        return (int) $existing[0];
    }

    $post_id = wp_insert_post([
        'post_type' => 'eh_popup',
        'post_status' => 'publish',
        'post_title' => 'Start Online Assessment',
    ]);

    if ($post_id > 0) {
        eh_popup_seed_default_meta($post_id);
    }

    return (int) $post_id;
}

/**
 * Default content matching the former Popup Box popup (EN); ID fields left for editors.
 */
function eh_popup_seed_default_meta(int $post_id): void
{
    if (!function_exists('rwmb_meta') || !function_exists('rwmb_set_meta')) {
        return;
    }

    $existing = rwmb_meta('eh_popup_headline_en', [], $post_id);
    if (is_string($existing) && trim($existing) !== '') {
        return;
    }

    $default_css = <<<'CSS'
.eh-popup__headline {
  font-family: Futura Hv BT, Futura BT, sans-serif !important;
  line-height: 1;
  padding: 0 !important;
}

.eh-popup__body {
  padding: 0 3rem !important;
}

.eh-popup__media {
  width: 50% !important;
}

.eh-popup__body {
  width: 50% !important;
}

.eh-popup__cta {
  font-family: Futura Bk BT, Futura BT, sans-serif !important;
}

.eh-popup__cta-wrap {
  font-family: Futura Bk BT, Futura BT, sans-serif !important;
  margin: 3rem 0;
}

.eh-popup__close-container {
  top: 14px !important;
  right: 30px !important;
}

@media screen and (max-width: 768px) {
  .eh-popup__media {
    width: 100% !important;
    min-height: 400px !important;
  }

  .eh-popup__body {
    width: 100% !important;
    padding: 1rem !important;
  }

  .eh-popup__headline {
    font-size: 27px !important;
  }
}
CSS;

    rwmb_set_meta($post_id, 'eh_popup_active', 1);
    rwmb_set_meta($post_id, 'eh_popup_headline_en', '<h1 style="text-align: center;">Don\'t Wait Until<br>It\'s Permanent!</h1>');
    rwmb_set_meta($post_id, 'eh_popup_headline_id', '');
    rwmb_set_meta($post_id, 'eh_popup_cta_text_en', 'Diagnose Now');
    rwmb_set_meta($post_id, 'eh_popup_cta_text_id', '');
    rwmb_set_meta(
        $post_id,
        'eh_popup_cta_url',
        'https://assessment.eurohairlab.com/assessment?utm_source=redirect&utm_medium=web&utm_campaign=euro_launch'
    );
    rwmb_set_meta($post_id, 'eh_popup_overlay_opacity', 0.5);
    rwmb_set_meta($post_id, 'eh_popup_delay_ms', 0);
    rwmb_set_meta($post_id, 'eh_popup_trigger', 'pageLoaded');
    rwmb_set_meta($post_id, 'eh_popup_close_on_esc', 1);
    rwmb_set_meta($post_id, 'eh_popup_width_percent', 50);
    rwmb_set_meta($post_id, 'eh_popup_height_px', 500);
    rwmb_set_meta($post_id, 'eh_popup_custom_css', $default_css);

    $default_show = array_filter([eh_popup_get_default_home_page_id()]);
    if ($default_show !== []) {
        rwmb_set_meta($post_id, 'eh_popup_show_page_ids', $default_show);
    }

    if (function_exists('eh_popup_sync_post_to_table')) {
        eh_popup_sync_post_to_table($post_id);
    }
}
