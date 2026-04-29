<?php
/**
 * Plugin Name: Eurohairlab Assessment Data
 * Description: Stores assessment submissions, branch office links, and related data in custom database tables.
 * Plugin URI: https://qoar.id
 * Version: 1.8.1
 * Author: Qoar Creative Agency
 * Author URI: https://qoar.id
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/eh-assessment-submission-logic.php';
require_once __DIR__ . '/eh-assessment-cekat-webhook-i18n.php';
require_once __DIR__ . '/eh-assessment-admin-notification-mail.php';

const EH_ASSESSMENT_DATA_VERSION = '1.8.2';
const EH_ASSESSMENT_REPORT_PDF_MASKING_ID_MAX_LENGTH = 64;
const EH_ASSESSMENT_AGENT_MASKING_ID_MAX_LENGTH = 64;
const EH_ASSESSMENT_AGENT_CODE_MAX_LENGTH = 64;
const EH_ASSESSMENT_ACCESS_CAPABILITY = 'eh_access_assessment_data';
const EH_ASSESSMENT_USER_WHATSAPP_META_KEY = 'eh_user_whatsapp_number';
const EH_ASSESSMENT_MAX_NAME_LENGTH = 191;
const EH_ASSESSMENT_MAX_ANSWER_TEXT_LENGTH = 500;
const EH_ASSESSMENT_MAX_QUESTION_TEXT_LENGTH = 500;
const EH_ASSESSMENT_MIN_WHATSAPP_DIGITS = 8;
const EH_ASSESSMENT_MAX_WHATSAPP_DIGITS = 20;
const EH_ASSESSMENT_MAX_JSON_BODY_BYTES = 65536;
const EH_ASSESSMENT_BRANCH_OUTLET_MASKING_ID_MAX_LENGTH = 64;
const EH_ASSESSMENT_SOURCE_PAGE_SLUG_MAX_LENGTH = 200;
const EH_ASSESSMENT_WA_TEMPLATE_MASKING_ID_MAX_LENGTH = 64;
const EH_ASSESSMENT_RATE_SUCCESS_WINDOW_SECONDS = 3600;
const EH_ASSESSMENT_RATE_SUCCESS_MAX = 10;
const EH_ASSESSMENT_WEBHOOK_COMPLETE_RATE_WINDOW_SECONDS = 60;
const EH_ASSESSMENT_WEBHOOK_COMPLETE_RATE_MAX = 60;
const EH_ASSESSMENT_WEBHOOK_COMPLETE_SECRET_MIN_LENGTH = 32;

/** Default Cekat workflow URL if {@see WEBHOOK_CEKAT_URL} / {@see WEBHOOK_TO_CEKAT_URL} / {@see EH_ASSESSMENT_CEKAT_SUBMISSION_WEBHOOK_URL} are unset or empty. */
const EH_ASSESSMENT_CEKAT_SUBMISSION_SAVED_WEBHOOK_DEFAULT = 'https://workflows.cekat.ai/webhook-test/0c7398de-52de-415c-be56-fdbb444db801';

function eh_assessment_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'eh_assessment_submissions';
}

function eh_hair_specialist_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'eh_hair_specialists';
}

function eh_branch_outlet_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'eh_cekat_branch_outlets';
}

function eh_hair_specialist_agent_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'eh_hair_specialist_agents';
}

function eh_assessment_report_pdf_template_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'eh_report_pdf_templates';
}

function eh_assessment_report_pdf_template_table_has_column(string $column): bool
{
    global $wpdb;

    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        return false;
    }

    $rows = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$table}` LIKE %s", $column), ARRAY_A);

    return is_array($rows) && $rows !== [];
}

function eh_assessment_normalize_report_pdf_template_masking_id(string $raw): string
{
    $v = trim(sanitize_text_field($raw));
    if (strlen($v) > EH_ASSESSMENT_REPORT_PDF_MASKING_ID_MAX_LENGTH) {
        $v = (string) substr($v, 0, EH_ASSESSMENT_REPORT_PDF_MASKING_ID_MAX_LENGTH);
    }

    return $v;
}

function eh_assessment_report_pdf_template_masking_id_taken(string $masking_id, int $exclude_row_id = 0): bool
{
    $masking_id = eh_assessment_normalize_report_pdf_template_masking_id($masking_id);
    if ($masking_id === '') {
        return false;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    if ($exclude_row_id > 0) {
        $found = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE masking_id = %s AND id != %d LIMIT 1",
                $masking_id,
                $exclude_row_id
            )
        );

        return $found > 0;
    }

    $found = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE masking_id = %s LIMIT 1", $masking_id));

    return $found > 0;
}

function eh_assessment_generate_report_pdf_template_masking_id(): string
{
    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    for ($i = 0; $i < 40; $i++) {
        $candidate = 'RPT-' . gmdate('ym') . '-' . strtoupper(wp_generate_password(8, false, false));
        $candidate = eh_assessment_normalize_report_pdf_template_masking_id($candidate);
        if ($candidate !== '' && !eh_assessment_report_pdf_template_masking_id_taken($candidate, 0)) {
            return $candidate;
        }
    }

    return eh_assessment_normalize_report_pdf_template_masking_id('RPT-' . wp_generate_password(20, false, false));
}

/**
 * @return array<string, string|null>
 */
function eh_assessment_report_pdf_template_get_row(int $id, bool $active_only = true): array
{
    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    if ($id <= 0) {
        return [];
    }

    if ($active_only) {
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL LIMIT 1", $id),
            ARRAY_A
        );
    } else {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id), ARRAY_A);
    }

    return is_array($row) ? $row : [];
}

/**
 * @return array<string, string|null>|null
 */
function eh_assessment_report_pdf_template_get_row_by_masking_id(string $masking_id): ?array
{
    $masking_id = eh_assessment_normalize_report_pdf_template_masking_id($masking_id);
    if ($masking_id === '') {
        return null;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE masking_id = %s AND deleted_at IS NULL LIMIT 1", $masking_id),
        ARRAY_A
    );

    return is_array($row) ? $row : null;
}

/**
 * Candidate template `masking_id` values for a submission report type (1–8), first DB match wins.
 *
 * @return list<string>
 */
function eh_assessment_report_pdf_template_masking_id_candidates_for_report_type(int $reportType): array
{
    $n = max(1, min(8, $reportType));
    $two = str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    $raw = [
        (string) $n,
        $two,
        'R' . $two,
        'R' . $n,
        'r' . $two,
        'report-' . $n,
        'REPORT-' . $two,
        'TYPE-' . $n,
    ];
    $out = [];
    foreach ($raw as $c) {
        $norm = eh_assessment_normalize_report_pdf_template_masking_id($c);
        if ($norm !== '' && !in_array($norm, $out, true)) {
            $out[] = $norm;
        }
    }

    /** @var list<string> $out */
    return apply_filters('eh_assessment_report_pdf_template_masking_id_candidates', $out, $reportType);
}

/**
 * @return array<string, mixed>|null
 */
function eh_assessment_report_pdf_template_get_for_report_type(int $reportType): ?array
{
    foreach (eh_assessment_report_pdf_template_masking_id_candidates_for_report_type($reportType) as $mid) {
        $row = eh_assessment_report_pdf_template_get_row_by_masking_id($mid);
        if ($row !== null) {
            return $row;
        }
    }

    return null;
}

/**
 * Whether `report_title` is intended to match submission Rpt (report type), e.g. contains
 * literal `%8%`, or contains 8 as its own token (not part of 18 / 80).
 */
function eh_assessment_report_pdf_template_report_title_matches_rpt(string $reportTitle, int $rpt): bool
{
    $reportTitle = trim($reportTitle);
    if ($reportTitle === '' || $rpt < 1) {
        return false;
    }

    $n = (string) $rpt;
    $nPadded = str_pad($n, 2, '0', STR_PAD_LEFT);

    if (str_contains($reportTitle, '%' . $n . '%')) {
        return true;
    }
    if ($rpt < 10 && str_contains($reportTitle, '%' . $nPadded . '%')) {
        return true;
    }

    if ($reportTitle === $n) {
        return true;
    }

    return (bool) preg_match('/(^|[\s\-_:])' . preg_quote($n, '/') . '([\s\-_:]|$)/', $reportTitle);
}

/**
 * First active PDF template (lowest `id`) whose `report_title` matches submission Rpt.
 *
 * @return array<string, mixed>|null
 */
function eh_assessment_report_pdf_template_get_first_matching_report_title_rpt(int $rpt): ?array
{
    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $rows = $wpdb->get_results(
        "SELECT * FROM {$table} WHERE deleted_at IS NULL ORDER BY id ASC",
        ARRAY_A
    );
    if (!is_array($rows)) {
        return null;
    }
    foreach ($rows as $row) {
        $title = (string) ($row['report_title'] ?? '');
        if (eh_assessment_report_pdf_template_report_title_matches_rpt($title, $rpt)) {
            return $row;
        }
    }

    return null;
}

/**
 * First active PDF template whose `masking_id` contains the literal substring `%%`
 * and contains no `%%` after substituting the submission report type (1–8).
 *
 * Use when templates are keyed like `RPT-%%` instead of a fixed id per type.
 *
 * @return array<string, mixed>|null
 */
function eh_assessment_report_pdf_template_get_first_placeholder_match(int $reportType): ?array
{
    $n = max(1, min(8, $reportType));
    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $like = '%' . $wpdb->esc_like('%%') . '%';
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE deleted_at IS NULL AND masking_id LIKE %s ORDER BY id ASC",
            $like
        ),
        ARRAY_A
    );
    if (!is_array($rows) || $rows === []) {
        return null;
    }

    $replacements = [
        (string) $n,
        str_pad((string) $n, 2, '0', STR_PAD_LEFT),
    ];
    foreach ($rows as $row) {
        $mid = (string) ($row['masking_id'] ?? '');
        if (strpos($mid, '%%') === false) {
            continue;
        }
        foreach ($replacements as $rep) {
            $resolved = str_replace('%%', $rep, $mid);
            if ($resolved !== '' && strpos($resolved, '%%') === false) {
                return $row;
            }
        }
    }

    return null;
}

/**
 * @param list<string> $fallback
 * @return list<string>
 */
function eh_assessment_pdf_text_to_bullet_items(string $text, array $fallback): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = [];
    foreach (explode("\n", $text) as $line) {
        $t = trim((string) $line);
        if ($t !== '') {
            $lines[] = $t;
        }
    }

    return $lines !== [] ? $lines : $fallback;
}

/**
 * @param list<string> $defaultItems
 * @return array{title: string, items: list<string>}
 */
function eh_assessment_pdf_parse_clinical_card(string $text, string $defaultTitle, array $defaultItems): array
{
    $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($text === '') {
        return ['title' => $defaultTitle, 'items' => $defaultItems];
    }

    $parts = preg_split("/\n{2,}/", $text) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts), static fn ($p) => $p !== ''));
    if (count($parts) >= 2) {
        $title = $parts[0];
        $body = implode("\n", array_slice($parts, 1));
        $items = eh_assessment_pdf_text_to_bullet_items($body, []);

        return [
            'title' => $title,
            'items' => $items !== [] ? $items : [$body],
        ];
    }

    $items = eh_assessment_pdf_text_to_bullet_items($text, []);

    return [
        'title' => $defaultTitle,
        'items' => $items !== [] ? $items : [$text],
    ];
}

/**
 * @return array<string, string>
 */
function eh_assessment_report_pdf_template_row_from_post(): array
{
    $str = static function ($key, int $max = 255): string {
        $v = isset($_POST[$key]) ? wp_unslash((string) $_POST[$key]) : '';
        $v = sanitize_text_field($v);
        if (strlen($v) > $max) {
            $v = (string) substr($v, 0, $max);
        }

        return $v;
    };
    $html = static function (string $key): string {
        $v = isset($_POST[$key]) ? wp_unslash((string) $_POST[$key]) : '';

        return wp_kses_post($v);
    };
    $img = static function (string $key): string {
        $v = isset($_POST[$key]) ? wp_unslash((string) $_POST[$key]) : '';
        $v = trim($v);
        if ($v === '') {
            return '';
        }
        if (strlen($v) > 500) {
            $v = (string) substr($v, 0, 500);
        }
        if (preg_match('#^https?://#i', $v)) {
            return esc_url_raw($v);
        }

        return sanitize_text_field($v);
    };

    $masking_in = isset($_POST['rpt_masking_id']) ? wp_unslash((string) $_POST['rpt_masking_id']) : '';
    $masking_id = eh_assessment_normalize_report_pdf_template_masking_id($masking_in);

    return [
        'masking_id' => $masking_id,
        'report_title' => $str('rpt_report_title', 255),
        'report_header_title' => $str('rpt_report_header_title', 255),
        'subtitle' => $str('rpt_subtitle', 255),
        'greeting_description' => $html('rpt_greeting_description'),
        'diagnosis_name' => $html('rpt_diagnosis_name'),
        'diagnosis_name_detail' => $str('rpt_diagnosis_name_detail', 255),
        'title_condition_explanation' => $str('rpt_title_condition_explanation', 255),
        'description_condition_explanation' => $html('rpt_description_condition_explanation'),
        'title_clinical_knowledge' => $str('rpt_title_clinical_knowledge', 255),
        'subtitle_clinical_knowledge' => $str('rpt_subtitle_clinical_knowledge', 255),
        'image_clinical_knowledge' => $img('rpt_image_clinical_knowledge'),
        'description_clinical_knowledge' => $html('rpt_description_clinical_knowledge'),
        'title_evaluation_urgency' => $str('rpt_title_evaluation_urgency', 255),
        'description_evaluation_urgency' => $html('rpt_description_evaluation_urgency'),
        'title_treatment_journey' => $str('rpt_title_treatment_journey', 255),
        'description_treatment_journey' => $html('rpt_description_treatment_journey'),
        'image_treatment_journey' => $img('rpt_image_treatment_journey'),
        'title_recommendation_approach' => $str('rpt_title_recommendation_approach', 255),
        'description_recommendation_approach' => $html('rpt_description_recommendation_approach'),
        'detail_recommendation_approach' => $html('rpt_detail_recommendation_approach'),
        'bottom_description_recommendation_approach' => $html('rpt_bottom_description_recommendation_approach'),
        'title_next_steps' => $str('rpt_title_next_steps', 255),
        'description_next_steps' => $html('rpt_description_next_steps'),
        'title_medical_notes' => $str('rpt_title_medical_notes', 255),
        'body_medical_notes' => $html('rpt_body_medical_notes'),
        'description_medical_notes' => $html('rpt_description_medical_notes'),
    ];
}

function eh_assessment_normalize_agent_masking_id(string $raw): string
{
    $v = trim(sanitize_text_field($raw));
    if (strlen($v) > EH_ASSESSMENT_AGENT_MASKING_ID_MAX_LENGTH) {
        $v = (string) substr($v, 0, EH_ASSESSMENT_AGENT_MASKING_ID_MAX_LENGTH);
    }

    return $v;
}

function eh_assessment_normalize_agent_code(string $raw): string
{
    $v = trim(sanitize_text_field($raw));
    $v = preg_replace('/[^A-Za-z0-9_-]/', '', $v) ?? '';
    if (strlen($v) > EH_ASSESSMENT_AGENT_CODE_MAX_LENGTH) {
        $v = (string) substr($v, 0, EH_ASSESSMENT_AGENT_CODE_MAX_LENGTH);
    }

    return $v;
}

/**
 * True if the trimmed code is non-empty, within max length, and URL/query-safe (no spaces or special characters).
 * Used for the public assessment link query parameter `code`.
 */
function eh_assessment_agent_code_raw_is_query_safe(string $raw): bool
{
    $raw = trim($raw);
    if ($raw === '' || strlen($raw) > EH_ASSESSMENT_AGENT_CODE_MAX_LENGTH) {
        return false;
    }

    return (bool) preg_match('/^[A-Za-z0-9_-]+$/', $raw);
}

/**
 * Another active row already uses this Cekat agent id (API field `id` stored as masking_id).
 */
function eh_assessment_hair_specialist_agent_masking_id_taken(string $masking_id, int $exclude_row_id = 0): bool
{
    $masking_id = eh_assessment_normalize_agent_masking_id($masking_id);
    if ($masking_id === '') {
        return false;
    }

    global $wpdb;
    $table = eh_hair_specialist_agent_table_name();
    if ($exclude_row_id > 0) {
        $found = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE masking_id = %s AND deleted_at IS NULL AND id != %d LIMIT 1",
                $masking_id,
                $exclude_row_id
            )
        );
    } else {
        $found = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE masking_id = %s AND deleted_at IS NULL LIMIT 1",
                $masking_id
            )
        );
    }

    return $found > 0;
}

/**
 * Any row (including soft-deleted) with this masking_id — agent can only ever exist once in the table.
 */
function eh_assessment_hair_specialist_agent_masking_id_exists_globally(string $masking_id): bool
{
    $masking_id = eh_assessment_normalize_agent_masking_id($masking_id);
    if ($masking_id === '') {
        return false;
    }

    global $wpdb;
    $table = eh_hair_specialist_agent_table_name();
    $found = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE masking_id = %s LIMIT 1",
            $masking_id
        )
    );

    return $found > 0;
}

/**
 * Another active row already uses this agent code.
 */
function eh_assessment_hair_specialist_agent_code_taken(string $agent_code, int $exclude_row_id = 0): bool
{
    $agent_code = eh_assessment_normalize_agent_code($agent_code);
    if ($agent_code === '') {
        return false;
    }

    global $wpdb;
    $table = eh_hair_specialist_agent_table_name();
    if ($exclude_row_id > 0) {
        $found = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE agent_code = %s AND deleted_at IS NULL AND id != %d LIMIT 1",
                $agent_code,
                $exclude_row_id
            )
        );
    } else {
        $found = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE agent_code = %s AND deleted_at IS NULL LIMIT 1",
                $agent_code
            )
        );
    }

    return $found > 0;
}

/**
 * Public assessment page URL (same path as {@see home_url()} for the assessment slug).
 * When {@see WP_ASSESSMENT_DOMAIN} is set in wp-config, uses that host + the site’s assessment path.
 */
function eh_assessment_get_public_assessment_page_url(): string
{
    $path = '/' . ltrim((string) apply_filters('eh_assessment_agent_assessment_path', 'assessment'), '/');
    $home = home_url($path);
    if (defined('WP_ASSESSMENT_DOMAIN')) {
        $domain = trim((string) WP_ASSESSMENT_DOMAIN);
        if ($domain !== '') {
            $path_component = parse_url($home, PHP_URL_PATH);
            $path_s = is_string($path_component) && $path_component !== ''
                ? $path_component
                : ('/' . trim($path, '/') . '/');

            return rtrim($domain, '/') . $path_s;
        }
    }

    return untrailingslashit($home);
}

/**
 * Public assessment URL with agent tracking code (`?code=` + WhatsApp / inquiry UTM tags).
 */
function eh_assessment_build_agent_assessment_public_url(string $agent_code): string
{
    $agent_code = eh_assessment_normalize_agent_code($agent_code);
    $base = eh_assessment_get_public_assessment_page_url();

    return add_query_arg(
        [
            'code' => $agent_code,
            'utm_source' => 'wa_direct_inquiry',
            'utm_medium' => 'social',
            'utm_campaign' => 'hair_assessment',
        ],
        $base
    );
}

/**
 * Resolve Hair Specialist Agent display name from public `code` / payload `agent_masking_id` (matches agent_code or Cekat masking_id).
 */
function eh_assessment_resolve_submission_agent_name_from_input(string $raw): ?string
{
    $code = eh_assessment_normalize_agent_code($raw);
    $mid = eh_assessment_normalize_agent_masking_id($raw);
    if ($code === '' && $mid === '') {
        return null;
    }

    global $wpdb;
    $table = eh_hair_specialist_agent_table_name();

    if ($code !== '') {
        $n = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT name FROM {$table} WHERE deleted_at IS NULL AND agent_code = %s LIMIT 1",
                $code
            )
        );
        if (is_string($n) && trim($n) !== '') {
            return sanitize_text_field($n);
        }
    }
    if ($mid !== '') {
        $n = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT name FROM {$table} WHERE deleted_at IS NULL AND masking_id = %s LIMIT 1",
                $mid
            )
        );
        if (is_string($n) && trim($n) !== '') {
            return sanitize_text_field($n);
        }
    }

    return null;
}

/**
 * Canonical `agent_masking_id` stored in assessment payload: always the Hair Specialist row's Cekat `masking_id`.
 * Accepts public `?code=` (agent_code) or an existing masking id string.
 */
function eh_assessment_canonical_agent_masking_id_for_payload(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }

    $code = eh_assessment_normalize_agent_code($raw);
    $mid_in = eh_assessment_normalize_agent_masking_id($raw);

    global $wpdb;
    $table = eh_hair_specialist_agent_table_name();

    if ($code !== '') {
        $db_mid = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT masking_id FROM {$table} WHERE deleted_at IS NULL AND agent_code = %s LIMIT 1",
                $code
            )
        );
        if (is_string($db_mid) && trim($db_mid) !== '') {
            return eh_assessment_normalize_agent_masking_id($db_mid);
        }
    }

    if ($mid_in !== '') {
        $found = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND masking_id = %s LIMIT 1",
                $mid_in
            )
        );
        if ($found > 0) {
            return $mid_in;
        }
    }

    return '';
}

/**
 * Active branch offices for assessment selects (not soft-deleted).
 *
 * @return list<array{id: int, cekat_masking_id: string, cekat_name: string, display_name: string}>
 */
function eh_assessment_get_active_branch_outlet_options(): array
{
    global $wpdb;

    $table = eh_branch_outlet_table_name();
    $orderLabel = eh_assessment_branch_outlet_label_sql('b');
    $rows = $wpdb->get_results(
        "SELECT b.id, b.cekat_masking_id, b.cekat_name, b.display_name FROM {$table} AS b WHERE b.deleted_at IS NULL ORDER BY {$orderLabel} ASC",
        ARRAY_A
    );

    return is_array($rows) ? $rows : [];
}

/**
 * Placeholder DOB when the public form does not collect birthdate (hidden field + API compatibility).
 */
function eh_assessment_default_placeholder_birthdate(): string
{
    return '1990-01-01';
}

function eh_assessment_normalize_submission_birthdate(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if (!$dt || $dt->format('Y-m-d') !== $value) {
        return null;
    }

    try {
        $today = new DateTimeImmutable('today', eh_assessment_gmt7_timezone());
    } catch (Throwable) {
        return $value;
    }

    if ($dt > $today) {
        return null;
    }

    return $value;
}

function eh_assessment_branch_outlet_id_is_active(int $id): bool
{
    if ($id <= 0) {
        return false;
    }

    global $wpdb;
    $table = eh_branch_outlet_table_name();
    $found = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id)
    );

    return $found > 0;
}

function eh_assessment_normalize_submission_branch_masking_id(string $raw): string
{
    $v = trim(sanitize_text_field($raw));
    if (strlen($v) > EH_ASSESSMENT_BRANCH_OUTLET_MASKING_ID_MAX_LENGTH) {
        $v = (string) substr($v, 0, EH_ASSESSMENT_BRANCH_OUTLET_MASKING_ID_MAX_LENGTH);
    }

    return $v;
}

/**
 * Resolve internal branch outlet row id from public Cekat masking id (API / payload value).
 */
function eh_assessment_resolve_branch_outlet_id_from_masking_id(string $masking_id): int
{
    $masking_id = eh_assessment_normalize_submission_branch_masking_id($masking_id);
    if ($masking_id === '') {
        return 0;
    }

    global $wpdb;
    $table = eh_branch_outlet_table_name();
    $id = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE cekat_masking_id = %s AND deleted_at IS NULL LIMIT 1",
            $masking_id
        )
    );

    return $id > 0 ? $id : 0;
}

/**
 * Resolve branch row id for Cekat webhook body: use stored id when set, else masking id from sanitized submission.
 */
function eh_assessment_branch_outlet_id_for_webhook_body(array $sanitized, int $branch_outlet_id): int
{
    if ($branch_outlet_id > 0) {
        return $branch_outlet_id;
    }

    $sub = is_array($sanitized['submission'] ?? null) ? $sanitized['submission'] : [];

    return eh_assessment_resolve_branch_outlet_id_from_masking_id(
        (string) ($sub['branch_outlet_masking_id'] ?? '')
    );
}

function eh_assessment_normalize_submission_source_page_slug(string $raw): string
{
    $raw = trim(sanitize_text_field($raw));
    if ($raw === '') {
        return '';
    }

    $slug = sanitize_title($raw);
    if ($slug === '' && preg_match('/^[A-Za-z0-9_-]+$/', $raw)) {
        $slug = strtolower($raw);
    }

    if (strlen($slug) > EH_ASSESSMENT_SOURCE_PAGE_SLUG_MAX_LENGTH) {
        $slug = (string) substr($slug, 0, EH_ASSESSMENT_SOURCE_PAGE_SLUG_MAX_LENGTH);
    }

    return $slug;
}

/**
 * Resolve a published Page post ID from its path slug (same as public URL segment).
 */
function eh_assessment_resolve_source_page_id_from_slug(string $slug): int
{
    $slug = eh_assessment_normalize_submission_source_page_slug($slug);
    if ($slug === '') {
        return 0;
    }

    $page = get_page_by_path($slug, OBJECT, 'page');
    $id = ($page instanceof WP_Post && $page->post_status === 'publish') ? (int) $page->ID : 0;

    return (int) apply_filters('eh_assessment_resolve_source_page_id_from_slug', $id, $slug);
}

/**
 * Encode sanitized assessment payload for DB without leaking internal numeric source page id.
 *
 * @param array<string, mixed> $sanitized Output of {@see eh_assessment_sanitize_payload()}.
 */
function eh_assessment_payload_json_for_storage(array $sanitized): string
{
    $copy = $sanitized;
    if (isset($copy['submission']) && is_array($copy['submission'])) {
        unset($copy['submission']['source_page_id']);
    }

    return wp_json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Restructure decoded `payload_json` for admin display to match the public REST POST body
 * (submission / respondent / answers; submission: source_page_slug, branch_outlet_masking_id, optional agent_masking_id = Hair Specialist Cekat masking_id).
 *
 * @param array<string,mixed> $payload Decoded payload.
 * @param array{branch_outlet_id?: int|null, source_page_id?: int|null} $submission_row Optional DB row when branch masking is only on FK.
 * @return array{submission: array<string, mixed>, respondent: array{name: string, whatsapp: string, gender: string, consent: bool}, answers: array<string, mixed>}
 */
function eh_assessment_payload_public_shape_for_display(array $payload, array $submission_row = []): array
{
    global $wpdb;

    $branch_tbl = eh_branch_outlet_table_name();
    $submission_in = is_array($payload['submission'] ?? null) ? $payload['submission'] : [];

    $source_slug = '';
    if (isset($submission_in['source_page_slug'])) {
        $source_slug = eh_assessment_normalize_submission_source_page_slug((string) $submission_in['source_page_slug']);
    }
    if ($source_slug === '' && isset($submission_in['source_page_id'])) {
        $legacy = (int) $submission_in['source_page_id'];
        if ($legacy > 0) {
            $p = get_post($legacy);
            if ($p instanceof WP_Post && (string) $p->post_name !== '') {
                $source_slug = eh_assessment_normalize_submission_source_page_slug((string) $p->post_name);
            }
        }
    }
    if ($source_slug === '' && !empty($submission_row['source_page_id'])) {
        $p = get_post((int) $submission_row['source_page_id']);
        if ($p instanceof WP_Post && (string) $p->post_name !== '') {
            $source_slug = eh_assessment_normalize_submission_source_page_slug((string) $p->post_name);
        }
    }

    $masking = '';
    if (isset($submission_in['branch_outlet_masking_id'])) {
        $masking = eh_assessment_normalize_submission_branch_masking_id((string) $submission_in['branch_outlet_masking_id']);
    }
    if ($masking === '' && isset($submission_in['branch_outlet_id'])) {
        $legacy = (int) $submission_in['branch_outlet_id'];
        if ($legacy > 0) {
            $masking = eh_assessment_normalize_submission_branch_masking_id(
                (string) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT cekat_masking_id FROM {$branch_tbl} WHERE id = %d AND deleted_at IS NULL LIMIT 1",
                        $legacy
                    )
                )
            );
        }
    }
    if ($masking === '' && !empty($submission_row['branch_outlet_id'])) {
        $bid = (int) $submission_row['branch_outlet_id'];
        if ($bid > 0) {
            $masking = eh_assessment_normalize_submission_branch_masking_id(
                (string) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT cekat_masking_id FROM {$branch_tbl} WHERE id = %d AND deleted_at IS NULL LIMIT 1",
                        $bid
                    )
                )
            );
        }
    }

    $resp_in = is_array($payload['respondent'] ?? null) ? $payload['respondent'] : [];
    $respondent = [
        'name' => (string) ($resp_in['name'] ?? ''),
        'whatsapp' => (string) ($resp_in['whatsapp'] ?? ''),
        'gender' => (string) ($resp_in['gender'] ?? ''),
        'consent' => !empty($resp_in['consent']),
    ];

    $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];

    $agent_mid = '';
    if (isset($submission_in['agent_masking_id'])) {
        $agent_mid = eh_assessment_canonical_agent_masking_id_for_payload((string) $submission_in['agent_masking_id']);
    }

    $submission_out = [
        'source_page_slug' => $source_slug,
        'branch_outlet_masking_id' => $masking,
    ];
    if ($agent_mid !== '') {
        $submission_out['agent_masking_id'] = $agent_mid;
    }
    if (isset($submission_in['report_type'])) {
        $rtp = (int) $submission_in['report_type'];
        if ($rtp >= 1 && $rtp <= 99) {
            $submission_out['report_type'] = $rtp;
        }
    }

    $lead_db = trim((string) ($submission_row['lead_source'] ?? ''));
    $submission_out['lead_source'] = $lead_db !== ''
        ? eh_assessment_normalize_lead_source($lead_db)
        : eh_assessment_lead_source_from_submission($submission_in);

    return [
        'submission' => $submission_out,
        'respondent' => $respondent,
        'answers' => $answers,
    ];
}

function eh_assessment_assessment_table_has_column(string $column): bool
{
    global $wpdb;

    $table = eh_assessment_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        return false;
    }

    $rows = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$table}` LIKE %s", $column), ARRAY_A);

    return is_array($rows) && $rows !== [];
}

function eh_assessment_branch_table_has_column(string $column): bool
{
    global $wpdb;

    $table = eh_branch_outlet_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        return false;
    }

    $rows = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$table}` LIKE %s", $column), ARRAY_A);

    return is_array($rows) && $rows !== [];
}

/**
 * SQL expression: branch label for assessment UI (display_name when set, else Cekat inbox name).
 *
 * @param string $alias Table alias (e.g. `bo`), or empty string when the FROM clause uses the branch table without an alias.
 */
function eh_assessment_branch_outlet_label_sql(string $alias = 'bo'): string
{
    $p = $alias !== '' ? $alias . '.' : '';

    return "COALESCE(NULLIF(TRIM({$p}display_name), ''), {$p}cekat_name)";
}

/**
 * @param array<string, mixed> $row Branch outlet row (DB or decoded).
 */
function eh_assessment_branch_outlet_display_label(array $row): string
{
    $dn = trim((string) ($row['display_name'] ?? ''));
    if ($dn !== '') {
        return $dn;
    }

    return trim((string) ($row['cekat_name'] ?? ''));
}

function eh_assessment_normalize_wa_template_masking_id(string $raw): string
{
    $v = trim(sanitize_text_field($raw));
    if (strlen($v) > EH_ASSESSMENT_WA_TEMPLATE_MASKING_ID_MAX_LENGTH) {
        $v = (string) substr($v, 0, EH_ASSESSMENT_WA_TEMPLATE_MASKING_ID_MAX_LENGTH);
    }

    return $v;
}

/**
 * Base URL for Cekat OpenAPI (wp-config.php). Supported: OPENAPI_CEKAT, OPENAPI_LOCAL_SERVER.
 */
function eh_assessment_get_openapi_cekat_base_url(): string
{
    foreach (['OPENAPI_CEKAT', 'OPENAPI_LOCAL_SERVER'] as $const) {
        if (defined($const) && is_string(constant($const)) && constant($const) !== '') {
            return rtrim((string) constant($const), '/');
        }
    }

    return '';
}

/**
 * API key for Cekat OpenAPI (wp-config.php). Cekat expects an HTTP header named `api_key` (Postman “API Key” auth).
 * Supported: OPENAPI_CEKAT_KEY, CEKAT_API_KEY.
 */
function eh_assessment_get_openapi_cekat_key(): string
{
    foreach (['OPENAPI_CEKAT_KEY', 'CEKAT_API_KEY'] as $const) {
        if (defined($const) && is_string(constant($const)) && constant($const) !== '') {
            return (string) constant($const);
        }
    }

    return '';
}

/**
 * Map one inbox object from Cekat /inboxes into flat cekat_* columns (API id → cekat_masking_id).
 *
 * @param array<string, mixed> $inbox
 * @return array<string, string|null>
 */
function eh_assessment_cekat_map_inbox_from_api(array $inbox): array
{
    $masking_id = sanitize_text_field((string) ($inbox['id'] ?? ''));
    $ai_agent = is_array($inbox['ai_agent'] ?? null) ? $inbox['ai_agent'] : null;
    $agent_json = null;
    if ($ai_agent) {
        $agent_json = wp_json_encode(
            [
                'cekat_masking_id' => sanitize_text_field((string) ($ai_agent['id'] ?? '')),
                'cekat_name' => sanitize_text_field((string) ($ai_agent['name'] ?? '')),
                'cekat_plugin_type' => sanitize_text_field((string) ($ai_agent['plugin_type'] ?? '')),
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    $desc = $inbox['description'] ?? null;
    $description = ($desc === null || $desc === '') ? null : sanitize_textarea_field((string) $desc);
    $img = $inbox['image_url'] ?? null;
    $image_url = ($img === null || $img === '') ? null : esc_url_raw((string) $img);
    $agent_id = $inbox['ai_agent_id'] ?? null;
    $cekat_ai_agent_id = ($agent_id === null || $agent_id === '') ? null : sanitize_text_field((string) $agent_id);

    return [
        'cekat_masking_id' => $masking_id,
        'cekat_created_at' => sanitize_text_field((string) ($inbox['created_at'] ?? '')),
        'cekat_business_id' => sanitize_text_field((string) ($inbox['business_id'] ?? '')),
        'cekat_name' => sanitize_text_field((string) ($inbox['name'] ?? '')),
        'cekat_description' => $description,
        'cekat_phone_number' => eh_assessment_normalize_whatsapp((string) ($inbox['phone_number'] ?? '')),
        'cekat_status' => sanitize_text_field((string) ($inbox['status'] ?? '')),
        'cekat_ai_agent_id' => $cekat_ai_agent_id,
        'cekat_image_url' => $image_url,
        'cekat_type' => sanitize_text_field((string) ($inbox['type'] ?? '')),
        'cekat_ai_agent_json' => $agent_json,
    ];
}

/**
 * @return array<int, array<string, string|null>>|WP_Error
 */
function eh_assessment_fetch_cekat_inboxes_from_api()
{
    $base = eh_assessment_get_openapi_cekat_base_url();
    $key = eh_assessment_get_openapi_cekat_key();
    if ($base === '' || $key === '') {
        return new WP_Error(
            'cekat_config',
            'Cekat API is not configured. Define OPENAPI_CEKAT (or OPENAPI_LOCAL_SERVER) and OPENAPI_CEKAT_KEY (or CEKAT_API_KEY) in wp-config.php.',
            ['status' => 503]
        );
    }

    $url = $base . '/inboxes';
    $request_args = [
        'timeout' => 25,
        'headers' => [
            'api_key' => $key,
            'Accept' => 'application/json',
        ],
    ];

    /**
     * @param array<string, mixed> $request_args
     * @return array<string, mixed>
     */
    $request_args = apply_filters('eh_assessment_cekat_remote_request_args', $request_args, $url);

    $response = wp_remote_get($url, $request_args);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error(
            'cekat_http',
            sprintf(
                'Cekat API request failed (HTTP %d). Check the base URL and that the token is sent as the `api_key` header (same as Postman).',
                $code
            ),
            ['status' => $code, 'body' => $body]
        );
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        return new WP_Error('cekat_bad_response', 'Unexpected Cekat API response.', ['status' => 502]);
    }

    $data = $decoded['data'] ?? null;
    if (!is_array($data)) {
        return new WP_Error('cekat_bad_response', 'Cekat API returned no data.', ['status' => 502]);
    }

    $mapped = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $m = eh_assessment_cekat_map_inbox_from_api($row);
        if ($m['cekat_masking_id'] !== '') {
            $mapped[] = $m;
        }
    }

    return $mapped;
}

/**
 * Flatten one WhatsApp template row from Cekat GET /templates for admin UI / REST.
 *
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function eh_assessment_cekat_normalize_template_api_row(array $row): array
{
    $inbox_id = (string) ($row['inbox_id'] ?? '');
    if ($inbox_id === '' && is_array($row['inbox'] ?? null)) {
        $inbox_id = (string) ($row['inbox']['id'] ?? '');
    }

    $buttons = $row['buttons'] ?? [];
    if (!is_array($buttons)) {
        $buttons = [];
    }

    $header = $row['header'] ?? null;
    $header_str = ($header === null || $header === '') ? '' : sanitize_text_field((string) $header);

    return [
        'id' => sanitize_text_field((string) ($row['id'] ?? '')),
        'name' => sanitize_text_field((string) ($row['name'] ?? '')),
        'category' => sanitize_text_field((string) ($row['category'] ?? '')),
        'header_type' => sanitize_text_field((string) ($row['header_type'] ?? '')),
        'header' => $header_str,
        'body' => is_string($row['body'] ?? null) ? (string) $row['body'] : '',
        'buttons' => $buttons,
        'file_url' => isset($row['file_url']) && (string) $row['file_url'] !== '' ? esc_url_raw((string) $row['file_url']) : '',
        'inbox_id' => sanitize_text_field($inbox_id),
    ];
}

/**
 * WhatsApp templates from Cekat OpenAPI GET /templates (same `api_key` header as /inboxes).
 *
 * @return list<array<string, mixed>>|WP_Error
 */
function eh_assessment_fetch_cekat_templates_from_api()
{
    $base = eh_assessment_get_openapi_cekat_base_url();
    $key = eh_assessment_get_openapi_cekat_key();
    if ($base === '' || $key === '') {
        return new WP_Error(
            'cekat_config',
            'Cekat API is not configured. Define OPENAPI_CEKAT (or OPENAPI_LOCAL_SERVER) and OPENAPI_CEKAT_KEY (or CEKAT_API_KEY) in wp-config.php.',
            ['status' => 503]
        );
    }

    $url = $base . '/templates';
    $request_args = [
        'timeout' => 25,
        'headers' => [
            'api_key' => $key,
            'Accept' => 'application/json',
        ],
    ];

    /**
     * @param array<string, mixed> $request_args
     * @return array<string, mixed>
     */
    $request_args = apply_filters('eh_assessment_cekat_templates_remote_request_args', $request_args, $url);

    $response = wp_remote_get($url, $request_args);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error(
            'cekat_http',
            sprintf('Cekat templates request failed (HTTP %d).', $code),
            ['status' => $code, 'body' => $body]
        );
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        return new WP_Error('cekat_bad_response', 'Unexpected Cekat templates API response.', ['status' => 502]);
    }

    $data = $decoded['data'] ?? null;
    if (!is_array($data)) {
        return new WP_Error('cekat_bad_response', 'Cekat templates API returned no data.', ['status' => 502]);
    }

    $list = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (array_key_exists('enabled', $row) && $row['enabled'] === false) {
            continue;
        }
        $n = eh_assessment_cekat_normalize_template_api_row($row);
        if ($n['id'] !== '') {
            $list[] = $n;
        }
    }

    return $list;
}

/**
 * @param list<array<string, mixed>> $templates Normalized rows from eh_assessment_cekat_normalize_template_api_row().
 * @return list<array<string, mixed>>
 */
function eh_assessment_cekat_filter_templates_by_inbox_masking(array $templates, string $inbox_masking_id): array
{
    $inbox_masking_id = sanitize_text_field(trim($inbox_masking_id));
    if ($inbox_masking_id === '') {
        return [];
    }

    $out = [];
    foreach ($templates as $t) {
        if (!is_array($t)) {
            continue;
        }
        if ((string) ($t['inbox_id'] ?? '') === $inbox_masking_id) {
            $out[] = $t;
        }
    }

    return $out;
}

/**
 * Hair specialist agents from Cekat OpenAPI GET /api/agents (API `id` is returned as `masking_id` for storage).
 * Includes every row in `data` with a non-empty `id` (all `role` values), matching the upstream list.
 *
 * @param int $limit Upstream list page size (clamped 1–10000).
 * @param int $page Upstream list page number (minimum 1).
 * @return list<array{masking_id: string, name: string, email: string}>|WP_Error
 */
function eh_assessment_fetch_cekat_agents_from_api(int $limit = 9999, int $page = 1)
{
    $base = eh_assessment_get_openapi_cekat_base_url();
    $key = eh_assessment_get_openapi_cekat_key();
    if ($base === '' || $key === '') {
        return new WP_Error(
            'cekat_config',
            'Cekat API is not configured. Define OPENAPI_CEKAT (or OPENAPI_LOCAL_SERVER) and OPENAPI_CEKAT_KEY (or CEKAT_API_KEY) in wp-config.php.',
            ['status' => 503]
        );
    }

    if ($limit < 1) {
        $limit = 9999;
    }
    if ($limit > 10000) {
        $limit = 10000;
    }
    if ($page < 1) {
        $page = 1;
    }

    $url = add_query_arg(
        [
            'limit' => $limit,
            'page' => $page,
        ],
        $base . '/api/agents'
    );
    $request_args = [
        'timeout' => 25,
        'headers' => [
            'api_key' => $key,
            'Accept' => 'application/json',
        ],
    ];

    /**
     * @param array<string, mixed> $request_args
     * @return array<string, mixed>
     */
    $request_args = apply_filters('eh_assessment_cekat_agents_remote_request_args', $request_args, $url);

    $response = wp_remote_get($url, $request_args);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error(
            'cekat_http',
            sprintf(
                'Cekat agents request failed (HTTP %d). Check the base URL and `api_key` header.',
                $code
            ),
            ['status' => $code, 'body' => $body]
        );
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        return new WP_Error('cekat_bad_response', 'Unexpected Cekat API response for agents.', ['status' => 502]);
    }

    $data = $decoded['data'] ?? null;
    if (!is_array($data)) {
        return new WP_Error('cekat_bad_response', 'Cekat API returned no agent data.', ['status' => 502]);
    }

    $mapped = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        // List all agents Cekat returns (agent, super-agent, supervisor, …) so WP admin matches GET /api/agents.
        $mid = eh_assessment_normalize_agent_masking_id((string) ($row['id'] ?? ''));
        if ($mid === '') {
            continue;
        }
        $mapped[] = [
            'masking_id' => $mid,
            'name' => sanitize_text_field((string) ($row['name'] ?? '')),
            'email' => sanitize_email((string) ($row['email'] ?? '')),
        ];
    }

    return $mapped;
}

function eh_assessment_question_key_map(): array
{
    return [
        1 => 'q1_focus_area',
        2 => 'q2_main_impact',
        3 => 'q3_duration',
        4 => 'q4_family_history',
        5 => 'q5_previous_attempts',
        6 => 'q6_trigger_factors',
        7 => 'q7_biggest_worry',
        8 => 'q8_previous_consultation',
        9 => 'q9_expected_result',
    ];
}

/**
 * English question copy stored with admin-created submissions.
 *
 * @return array<string, string>
 */
function eh_assessment_question_label_map(): array
{
    return [
        'q1_focus_area' => 'Which hair or scalp change are you noticing the most?',
        'q2_main_impact' => 'What is the biggest impact you are feeling?',
        'q3_duration' => 'How long have you noticed this change?',
        'q4_family_history' => 'Is there a family history of a similar condition?',
        'q5_previous_attempts' => 'What have you tried so far?',
        'q6_trigger_factors' => 'Are you currently experiencing any of the following factors?',
        'q7_biggest_worry' => 'If left untreated, what worries you the most?',
        'q8_previous_consultation' => 'Have you had a consultation before?',
        'q9_expected_result' => 'If your condition improves, what result are you hoping for?',
    ];
}

/**
 * @return array<string, string|null>|WP_Error
 */
function eh_assessment_parse_cekat_row_from_post()
{
    $json = isset($_POST['cekat_row_json']) ? wp_unslash((string) $_POST['cekat_row_json']) : '';
    if ($json === '') {
        return new WP_Error('cekat_required', 'Please select a Cekat inbox.');
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return new WP_Error('cekat_invalid', 'Invalid Cekat inbox payload.');
    }

    $masking = sanitize_text_field((string) ($data['cekat_masking_id'] ?? ''));
    if ($masking === '') {
        return new WP_Error('cekat_invalid', 'Cekat inbox is missing an identifier.');
    }

    return [
        'cekat_masking_id' => $masking,
        'cekat_name' => sanitize_text_field((string) ($data['cekat_name'] ?? '')),
        'cekat_phone_number' => eh_assessment_normalize_whatsapp((string) ($data['cekat_phone_number'] ?? '')),
        'cekat_status' => sanitize_text_field((string) ($data['cekat_status'] ?? '')),
        'cekat_type' => sanitize_text_field((string) ($data['cekat_type'] ?? '')),
    ];
}

/**
 * Branch Office DB row: masking, name, phone, type, status, customer WA template; legacy cekat_* columns cleared.
 *
 * @param array<string, string|null> $parts
 * @return array<string, string|null>
 */
function eh_assessment_branch_outlet_row_for_db(array $parts): array
{
    $phone = eh_assessment_normalize_whatsapp((string) ($parts['cekat_phone_number'] ?? ''));
    $type = sanitize_text_field((string) ($parts['cekat_type'] ?? ''));
    $status = sanitize_text_field((string) ($parts['cekat_status'] ?? ''));

    return [
        'cekat_masking_id' => sanitize_text_field((string) ($parts['cekat_masking_id'] ?? '')),
        'cekat_name' => sanitize_text_field((string) ($parts['cekat_name'] ?? '')),
        'display_name' => sanitize_text_field((string) substr(trim((string) ($parts['display_name'] ?? '')), 0, 191)),
        'cekat_phone_number' => $phone !== '' ? $phone : null,
        'cekat_type' => $type !== '' ? $type : null,
        'cekat_status' => $status !== '' ? $status : null,
        'cekat_wa_template_masking_id' => $parts['cekat_wa_template_masking_id'] ?? null,
        'cekat_wa_template_name' => $parts['cekat_wa_template_name'] ?? null,
        'cekat_created_at' => null,
        'cekat_business_id' => null,
        'cekat_description' => null,
        'cekat_ai_agent_id' => null,
        'cekat_image_url' => null,
        'cekat_ai_agent_json' => null,
    ];
}

/**
 * Sanitize Branch Office POST template fields (customer template only).
 *
 * @return array{
 *     cekat_wa_template_masking_id: ?string,
 *     cekat_wa_template_name: ?string
 * }
 */
function eh_assessment_sanitize_cekat_branch_fields_from_post(): array
{
    return [
        'cekat_wa_template_masking_id' => ($tid = eh_assessment_normalize_wa_template_masking_id(
            wp_unslash((string) ($_POST['cekat_wa_template_masking_id'] ?? ''))
        )) === '' ? null : $tid,
        'cekat_wa_template_name' => ($tn = wp_unslash((string) ($_POST['cekat_wa_template_name'] ?? ''))) === ''
            ? null
            : sanitize_text_field((string) substr($tn, 0, 191)),
    ];
}

function eh_assessment_sanitize_branch_display_name_from_post(): string
{
    $raw = isset($_POST['display_name']) ? wp_unslash((string) $_POST['display_name']) : '';

    return sanitize_text_field((string) substr(trim($raw), 0, 191));
}

function eh_assessment_current_mysql_time(): string
{
    return (new DateTimeImmutable('now', eh_assessment_gmt7_timezone()))->format('Y-m-d H:i:s');
}

function eh_assessment_gmt7_timezone(): DateTimeZone
{
    static $timezone = null;

    if ($timezone instanceof DateTimeZone) {
        return $timezone;
    }

    try {
        $timezone = new DateTimeZone('+07:00');
    } catch (Throwable) {
        $timezone = wp_timezone();
    }

    return $timezone;
}

function eh_assessment_normalize_whatsapp(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/[^0-9+]/', '', $value) ?: '';

    if ($value === '') {
        return '';
    }

    if (str_starts_with($value, '+')) {
        $value = ltrim($value, '+');
    }

    if (str_starts_with($value, '0')) {
        return '62' . ltrim(substr($value, 1), '0');
    }

    if (str_starts_with($value, '8')) {
        return '62' . $value;
    }

    return $value;
}

function eh_assessment_format_admin_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }

    $timezone = eh_assessment_gmt7_timezone();
    $date = date_create_immutable($datetime, $timezone);

    if (!$date) {
        return $datetime;
    }

    return $date->format('Y-m-d H:i:s');
}

function eh_assessment_get_current_user()
{
    return wp_get_current_user();
}

function eh_assessment_user_is_administrator(?WP_User $user = null): bool
{
    $user = $user instanceof WP_User ? $user : eh_assessment_get_current_user();

    return $user instanceof WP_User && in_array('administrator', (array) $user->roles, true);
}

function eh_assessment_user_is_hair_specialist(?WP_User $user = null): bool
{
    $user = $user instanceof WP_User ? $user : eh_assessment_get_current_user();

    return $user instanceof WP_User && in_array('subscriber', (array) $user->roles, true);
}

function eh_assessment_current_user_can_access_admin(): bool
{
    return current_user_can(EH_ASSESSMENT_ACCESS_CAPABILITY) || eh_assessment_user_is_administrator();
}

function eh_assessment_get_current_scope_hair_specialist_id(): int
{
    if (eh_assessment_user_is_administrator()) {
        return 0;
    }

    $user = eh_assessment_get_current_user();

    if (eh_assessment_user_is_hair_specialist($user)) {
        return (int) $user->ID;
    }

    return 0;
}

function eh_assessment_get_client_ip(): string
{
    $candidates = [
        (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
        (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
        (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    ];

    foreach ($candidates as $candidate) {
        if ($candidate === '') {
            continue;
        }

        $parts = array_map('trim', explode(',', $candidate));
        foreach ($parts as $part) {
            if (filter_var($part, FILTER_VALIDATE_IP)) {
                return $part;
            }
        }
    }

    return '';
}

function eh_assessment_get_active_hair_specialist_user_ids(): array
{
    $users = get_users([
        'role' => 'subscriber',
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => ['ID'],
    ]);

    if (!is_array($users)) {
        return [];
    }

    return array_map(
        static fn($user): int => (int) (is_object($user) ? $user->ID : 0),
        $users
    );
}

function eh_assessment_request_is_allowed_origin(): bool
{
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);

    if (!$site_host) {
        return true;
    }

    foreach ([$origin, $referer] as $candidate) {
        if ($candidate === '') {
            continue;
        }

        $candidate_host = wp_parse_url($candidate, PHP_URL_HOST);
        if ($candidate_host && strcasecmp((string) $candidate_host, (string) $site_host) === 0) {
            return true;
        }
    }

    return $origin === '' && $referer === '';
}

/**
 * Limit successful submissions per IP per hour (mitigates automated spam once validations pass).
 */
function eh_assessment_rate_limit_successful_submissions(): true|WP_Error
{
    $client_ip = eh_assessment_get_client_ip();
    if ($client_ip === '') {
        return true;
    }

    $transient_key = 'eh_asmt_ok_' . md5($client_ip);
    $now = time();
    $cutoff = $now - EH_ASSESSMENT_RATE_SUCCESS_WINDOW_SECONDS;
    $timestamps = get_transient($transient_key);
    $timestamps = is_array($timestamps) ? array_values(array_filter(
        array_map('intval', $timestamps),
        static fn(int $t): bool => $t > $cutoff
    )) : [];

    if (count($timestamps) >= EH_ASSESSMENT_RATE_SUCCESS_MAX) {
        return new WP_Error('rate_limited', 'Submission limit reached. Please try again later.', ['status' => 429]);
    }

    return true;
}

function eh_assessment_rate_limit_record_success(): void
{
    $client_ip = eh_assessment_get_client_ip();
    if ($client_ip === '') {
        return;
    }

    $transient_key = 'eh_asmt_ok_' . md5($client_ip);
    $now = time();
    $cutoff = $now - EH_ASSESSMENT_RATE_SUCCESS_WINDOW_SECONDS;
    $timestamps = get_transient($transient_key);
    $timestamps = is_array($timestamps) ? array_values(array_filter(
        array_map('intval', $timestamps),
        static fn(int $t): bool => $t > $cutoff
    )) : [];
    $timestamps[] = $now;
    set_transient($transient_key, $timestamps, EH_ASSESSMENT_RATE_SUCCESS_WINDOW_SECONDS + 120);
}

/**
 * Shared secret for the Cekat completion webhook: set in wp-config.php as
 * define('WEBHOOK_CEKAT_KEY', '…'); (same string as the Authorization header value).
 */
function eh_assessment_webhook_complete_config_secret(): string
{
    if (!defined('WEBHOOK_CEKAT_KEY')) {
        return '';
    }

    return trim((string) constant('WEBHOOK_CEKAT_KEY'));
}

function eh_assessment_webhook_complete_secret_is_configured(): bool
{
    $secret = eh_assessment_webhook_complete_config_secret();

    return $secret !== ''
        && strlen($secret) >= EH_ASSESSMENT_WEBHOOK_COMPLETE_SECRET_MIN_LENGTH
        && strlen($secret) <= 512;
}

/**
 * Current assessment submission masked_id: R[tipe 2 digit]-[MMYY]-[urutan harian 3 digit], e.g. R05-0326-001.
 */
function eh_assessment_submission_masked_id_is_valid(string $masked_id): bool
{
    return (bool) preg_match('/^R[0-9]{2}-[0-9]{4}-[0-9]{3}$/', $masked_id);
}

/**
 * Legacy format from {@see eh_assessment_generate_masked_id()} (prefix ASM).
 */
function eh_assessment_submission_masked_id_legacy_asm(string $masked_id): bool
{
    return (bool) preg_match('/^ASM-[0-9]{8}-[A-Z0-9]{6}$/', $masked_id);
}

function eh_assessment_submission_masked_id_valid_for_webhook(string $masked_id): bool
{
    return eh_assessment_submission_masked_id_is_valid($masked_id)
        || eh_assessment_submission_masked_id_legacy_asm($masked_id);
}

/**
 * Normalize masked_id for equality checks on public PDF links (DB vs query string).
 * Legacy public-link HMAC ({@see eh_assessment_public_report_download_signature()}) used the
 * raw trimmed URL `masked_id`. v2 links ({@see eh_assessment_public_report_download_signature_v2()})
 * sign the canonical normalized value so pasted IDs stay valid.
 *
 * Handles Unicode hyphen/minus (Word/PDF paste) vs ASCII hyphen, and zero-width chars.
 */
function eh_assessment_normalize_masked_id_for_download_compare(string $raw): string
{
    $t = trim($raw);
    if ($t === '') {
        return '';
    }

    $t = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $t) ?? $t;
    $t = preg_replace('/[\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2015}\x{2212}]/u', '-', $t) ?? $t;

    return trim($t);
}

function eh_assessment_webhook_complete_extract_token(WP_REST_Request $request): string
{
    $auth = (string) $request->get_header('authorization');
    if ($auth !== '') {
        $auth = trim($auth);
        if (stripos($auth, 'Bearer ') === 0) {
            $auth = trim(substr($auth, 7));
        }

        if ($auth !== '') {
            return $auth;
        }
    }

    return '';
}

function eh_assessment_webhook_complete_rate_limit(): true|WP_Error
{
    $client_ip = eh_assessment_get_client_ip();
    if ($client_ip === '') {
        return true;
    }

    $transient_key = 'eh_whcmp_' . md5($client_ip);
    $now = time();
    $cutoff = $now - EH_ASSESSMENT_WEBHOOK_COMPLETE_RATE_WINDOW_SECONDS;
    $timestamps = get_transient($transient_key);
    $timestamps = is_array($timestamps) ? array_values(array_filter(
        array_map('intval', $timestamps),
        static fn(int $t): bool => $t > $cutoff
    )) : [];

    if (count($timestamps) >= EH_ASSESSMENT_WEBHOOK_COMPLETE_RATE_MAX) {
        return new WP_Error('rate_limited', 'Too many requests.', ['status' => 429]);
    }

    return true;
}

function eh_assessment_webhook_complete_rate_limit_record(): void
{
    $client_ip = eh_assessment_get_client_ip();
    if ($client_ip === '') {
        return;
    }

    $transient_key = 'eh_whcmp_' . md5($client_ip);
    $now = time();
    $cutoff = $now - EH_ASSESSMENT_WEBHOOK_COMPLETE_RATE_WINDOW_SECONDS;
    $timestamps = get_transient($transient_key);
    $timestamps = is_array($timestamps) ? array_values(array_filter(
        array_map('intval', $timestamps),
        static fn(int $t): bool => $t > $cutoff
    )) : [];
    $timestamps[] = $now;
    set_transient(
        $transient_key,
        $timestamps,
        EH_ASSESSMENT_WEBHOOK_COMPLETE_RATE_WINDOW_SECONDS + 120
    );
}

function eh_assessment_rest_webhook_complete_submission(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    if (apply_filters('eh_assessment_webhook_complete_require_https', wp_get_environment_type() === 'production') && !is_ssl()) {
        return new WP_Error('https_required', 'HTTPS is required.', ['status' => 403]);
    }

    $rate = eh_assessment_webhook_complete_rate_limit();
    if (is_wp_error($rate)) {
        return $rate;
    }

    eh_assessment_webhook_complete_rate_limit_record();

    if (!eh_assessment_webhook_complete_secret_is_configured()) {
        return new WP_Error('not_configured', 'Webhook is not configured.', ['status' => 503]);
    }

    $stored = eh_assessment_webhook_complete_config_secret();
    $token = eh_assessment_webhook_complete_extract_token($request);
    if ($token === '' || strlen($token) > 512 || !hash_equals($stored, $token)) {
        return new WP_Error('invalid_auth', 'Invalid authentication.', ['status' => 401]);
    }

    $masked_id = trim((string) $request->get_param('submission_id'));
    if ($masked_id === '') {
        $masked_id = trim((string) $request->get_param('masked_id'));
    }
    if ($masked_id === '') {
        return new WP_Error('missing_submission_id', 'submission_id is required.', ['status' => 400]);
    }

    if (!eh_assessment_submission_masked_id_valid_for_webhook($masked_id)) {
        return new WP_Error('invalid_submission_id', 'submission_id format is invalid.', ['status' => 400]);
    }

    global $wpdb;
    $table = eh_assessment_table_name();
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, status, payload_json FROM {$table} WHERE masked_id = %s LIMIT 1",
            $masked_id
        ),
        ARRAY_A
    );

    if (!is_array($row) || !isset($row['id'])) {
        return new WP_Error('not_found', 'Submission not found.', ['status' => 404]);
    }

    $current = eh_assessment_normalize_status((string) ($row['status'] ?? ''));
    if ($current === 'Complete') {
        return new WP_REST_Response(
            [
                'ok' => true,
                'submission_id' => $masked_id,
                'masked_id' => $masked_id,
                'status' => 'Complete',
                'already_complete' => true,
            ],
            200
        );
    }

    $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
    $payload = is_array($payload) ? $payload : [];
    if (!isset($payload['submission']) || !is_array($payload['submission'])) {
        $payload['submission'] = [];
    }
    $payload['submission']['status'] = 'Complete';

    $updated = $wpdb->update(
        $table,
        [
            'status' => 'Complete',
            'payload_json' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => eh_assessment_current_mysql_time(),
        ],
        ['id' => (int) $row['id']],
        ['%s', '%s', '%s'],
        ['%d']
    );

    if ($updated === false) {
        return new WP_Error('update_failed', 'Could not update submission.', ['status' => 500]);
    }

    return new WP_REST_Response(
        [
            'ok' => true,
            'submission_id' => $masked_id,
            'masked_id' => $masked_id,
            'status' => 'Complete',
        ],
        200
    );
}

/**
 * Honeypot / bot fields must stay empty (not sent by the official front-end).
 *
 * @param array<string, mixed> $payload
 */
function eh_assessment_public_submission_honeypot_clean(array $payload): bool
{
    foreach (['url', 'website', 'company', 'eh_hp', '_gotcha'] as $trap) {
        if (!isset($payload[$trap])) {
            continue;
        }
        $v = $payload[$trap];
        if (is_string($v) && trim($v) !== '') {
            return false;
        }
        if (is_array($v) && $v !== []) {
            return false;
        }
    }

    return true;
}

function eh_assessment_normalize_respondent_gender_string(string $raw): string
{
    $trim = strtolower(trim($raw));
    if ($trim === 'male' || $trim === 'female') {
        return $trim;
    }

    $g = (string) (preg_replace('/[^a-z]/', '', $trim) ?? '');
    if ($g === 'm' || str_starts_with($g, 'male')) {
        return 'male';
    }
    if ($g === 'f' || str_starts_with($g, 'female')) {
        return 'female';
    }
    if ($g === 'pria' || str_starts_with($g, 'pria')) {
        return 'male';
    }
    if ($g === 'wanita' || str_starts_with($g, 'wanita')) {
        return 'female';
    }

    return '';
}

/**
 * @param array<string, mixed> $sanitized Output of {@see eh_assessment_sanitize_payload()}.
 */
function eh_assessment_validate_sanitized_submission_payload(array $sanitized): true|WP_Error
{
    $name = (string) ($sanitized['respondent']['name'] ?? '');
    if (trim($name) === '' || strlen($name) > EH_ASSESSMENT_MAX_NAME_LENGTH) {
        return new WP_Error('invalid_submission', 'Name is required or too long.', ['status' => 400]);
    }

    $wa = (string) ($sanitized['respondent']['whatsapp'] ?? '');
    $wa_digits = preg_replace('/\D/', '', $wa) ?? '';
    $wa_len = strlen($wa_digits);
    if ($wa_len < EH_ASSESSMENT_MIN_WHATSAPP_DIGITS || $wa_len > EH_ASSESSMENT_MAX_WHATSAPP_DIGITS) {
        return new WP_Error('invalid_submission', 'WhatsApp number is invalid.', ['status' => 400]);
    }

    $gender = (string) ($sanitized['respondent']['gender'] ?? '');
    if (!in_array($gender, ['male', 'female'], true)) {
        return new WP_Error('invalid_submission', 'Gender is invalid.', ['status' => 400]);
    }

    if (empty($sanitized['respondent']['consent'])) {
        return new WP_Error('invalid_submission', 'Consent is required.', ['status' => 400]);
    }

    foreach (eh_assessment_question_key_map() as $key) {
        $block = is_array($sanitized['answers'][$key] ?? null) ? $sanitized['answers'][$key] : [];
        $q = (string) ($block['question'] ?? '');
        $a = (string) ($block['answer'] ?? '');
        if (trim($q) === '' || strlen($q) > EH_ASSESSMENT_MAX_QUESTION_TEXT_LENGTH) {
            return new WP_Error('invalid_submission', 'Each question text is required.', ['status' => 400]);
        }
        if (trim($a) === '' || strlen($a) > EH_ASSESSMENT_MAX_ANSWER_TEXT_LENGTH) {
            return new WP_Error('invalid_submission', 'Each answer is required or too long.', ['status' => 400]);
        }
    }

    return true;
}

function eh_assessment_admin_sort_link(array $args, string $column, string $label): string
{
    $current_orderby = sanitize_key((string) ($_GET['orderby'] ?? ''));
    $current_order = strtolower(sanitize_text_field((string) ($_GET['order'] ?? 'desc'))) === 'asc' ? 'asc' : 'desc';
    $next_order = ($current_orderby === $column && $current_order === 'asc') ? 'desc' : 'asc';
    $url = add_query_arg(
        array_merge($args, [
            'orderby' => $column,
            'order' => $next_order,
        ]),
        admin_url('admin.php')
    );

    return '<a href="' . esc_url($url) . '"><span>' . esc_html($label) . '</span></a>';
}

function eh_assessment_generate_masked_id(string $prefix, string $table_name, string $column = 'masked_id'): string
{
    global $wpdb;

    do {
        $candidate = sprintf('%s-%s-%s', $prefix, gmdate('Ymd'), strtoupper(wp_generate_password(6, false, false)));
        $exists = (string) $wpdb->get_var(
            $wpdb->prepare("SELECT {$column} FROM {$table_name} WHERE {$column} = %s LIMIT 1", $candidate)
        );
    } while ($exists !== '');

    return $candidate;
}

/**
 * Normalized report type (1–99) for submission masked_id prefix R[tipe].
 * Uses JSON `submission.report_type` when set; otherwise filter `eh_assessment_default_submission_report_type` (default 5).
 */
function eh_assessment_resolve_submission_report_type(?int $from_payload): int
{
    if ($from_payload !== null && $from_payload >= 1 && $from_payload <= 99) {
        $n = $from_payload;
    } else {
        $n = (int) apply_filters('eh_assessment_default_submission_report_type', 5);
    }

    if ($n < 1) {
        $n = 1;
    }
    if ($n > 99) {
        $n = 99;
    }

    return (int) apply_filters('eh_assessment_submission_report_type', $n, $from_payload);
}

/**
 * Next assessment submission id: R[tipe]-[MMYY]-[nomor urut dalam bucket tipe+bulan, timezone situs].
 * Suffix is max(existing)+1 for that prefix (any row), because masked_id is globally UNIQUE.
 */
function eh_assessment_generate_submission_masked_id(int $report_type, string $assessment_table): string
{
    global $wpdb;

    $report_type = max(1, min(99, $report_type));
    $type2 = str_pad((string) $report_type, 2, '0', STR_PAD_LEFT);
    $tz = eh_assessment_gmt7_timezone();
    $now = new DateTimeImmutable('now', $tz);
    $mmyy = $now->format('my');
    $likePrefix = 'R' . $type2 . '-' . $mmyy . '-';
    $like = $wpdb->esc_like($likePrefix) . '%';
    $lockKey = 'eh_asmt_mid_' . $type2 . '_' . $mmyy;
    $locked = false;
    $got = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 15)', $lockKey));
    if ((int) $got === 1) {
        $locked = true;
    }

    try {
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT masked_id FROM {$assessment_table} WHERE masked_id LIKE %s",
                $like
            )
        );
        if (!is_array($ids)) {
            $ids = [];
        }
        $pattern = '/^' . preg_quote($likePrefix, '/') . '(\d{3})$/';
        $maxSeq = 0;
        foreach ($ids as $mid) {
            if (preg_match($pattern, (string) $mid, $m)) {
                $maxSeq = max($maxSeq, (int) $m[1]);
            }
        }
        $startSeq = $maxSeq + 1;
        for ($seq = $startSeq; $seq < $startSeq + 500; $seq++) {
            $candidate = sprintf('R%s-%s-%03d', $type2, $mmyy, $seq);
            $exists = (string) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT masked_id FROM {$assessment_table} WHERE masked_id = %s LIMIT 1",
                    $candidate
                )
            );
            if ($exists === '') {
                return $candidate;
            }
        }
    } finally {
        if ($locked) {
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lockKey));
        }
    }

    return sprintf(
        'R%s-%s-%s',
        $type2,
        $mmyy,
        strtoupper(wp_generate_password(3, false, false))
    );
}

/**
 * @param string $err Message from $wpdb->last_error
 */
function eh_assessment_db_error_is_duplicate_masked_id(string $err): bool
{
    $e = strtolower($err);

    return strpos($e, 'duplicate') !== false && strpos($e, 'masked_id') !== false;
}

function eh_assessment_apply_role_capabilities(): void
{
    foreach (['administrator', 'subscriber'] as $role_name) {
        $role = get_role($role_name);
        if ($role && !$role->has_cap(EH_ASSESSMENT_ACCESS_CAPABILITY)) {
            $role->add_cap(EH_ASSESSMENT_ACCESS_CAPABILITY);
        }
    }
}

function eh_assessment_override_role_labels(): void
{
    global $wp_roles;

    if (!($wp_roles instanceof WP_Roles) || !isset($wp_roles->roles['subscriber'])) {
        return;
    }

    $wp_roles->roles['subscriber']['name'] = 'Hair Specialist';
    $wp_roles->role_names['subscriber'] = 'Hair Specialist';
}

function eh_assessment_create_tables(): void
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $hair_specialist_table = eh_hair_specialist_table_name();
    $assessment_table = eh_assessment_table_name();

    $specialist_sql = "CREATE TABLE {$hair_specialist_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        masked_id VARCHAR(32) NOT NULL,
        name VARCHAR(191) NOT NULL,
        email VARCHAR(191) NOT NULL,
        wa_number VARCHAR(50) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY masked_id (masked_id),
        UNIQUE KEY email (email)
    ) {$charset_collate};";

    $assessment_sql = "CREATE TABLE {$assessment_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        masked_id VARCHAR(32) NOT NULL,
        source_page_id BIGINT UNSIGNED NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'On Progress',
        respondent_name VARCHAR(191) NOT NULL,
        respondent_whatsapp VARCHAR(50) NOT NULL,
        respondent_gender VARCHAR(20) NOT NULL,
        respondent_birthdate DATE NULL,
        branch_outlet_id BIGINT UNSIGNED NULL,
        agent_name VARCHAR(191) NULL,
        consent TINYINT(1) NOT NULL DEFAULT 0,
        lead_source VARCHAR(32) NOT NULL DEFAULT 'direct',
        q1_focus_area VARCHAR(191) NULL,
        q2_main_impact VARCHAR(191) NULL,
        q3_duration VARCHAR(191) NULL,
        q4_family_history VARCHAR(191) NULL,
        q5_previous_attempts VARCHAR(191) NULL,
        q6_trigger_factors VARCHAR(191) NULL,
        q7_biggest_worry VARCHAR(191) NULL,
        q8_previous_consultation VARCHAR(191) NULL,
        q9_expected_result VARCHAR(191) NULL,
        computed_report_type TINYINT UNSIGNED NULL,
        computed_score TINYINT UNSIGNED NULL,
        computed_band VARCHAR(64) NULL,
        computed_maintenance_path VARCHAR(64) NULL,
        computed_patient_type TINYINT UNSIGNED NULL,
        computed_communication_strategy VARCHAR(191) NULL,
        computed_condition_title VARCHAR(255) NULL,
        computed_clinical_warnings VARCHAR(500) NULL,
        computed_urgency_text VARCHAR(500) NULL,
        computed_genetic_clinical_text TEXT NULL,
        score_visual_scalp TINYINT UNSIGNED NULL,
        score_visual_follicle TINYINT UNSIGNED NULL,
        score_visual_thinning_risk TINYINT UNSIGNED NULL,
        cekat_masking_id VARCHAR(36) NULL,
        cekat_created_at VARCHAR(64) NULL,
        cekat_business_id VARCHAR(36) NULL,
        cekat_name VARCHAR(191) NULL,
        cekat_description TEXT NULL,
        cekat_phone_number VARCHAR(50) NULL,
        cekat_status VARCHAR(32) NULL,
        cekat_ai_agent_id VARCHAR(36) NULL,
        cekat_image_url TEXT NULL,
        cekat_type VARCHAR(32) NULL,
        cekat_ai_agent_json LONGTEXT NULL,
        payload_json LONGTEXT NOT NULL,
        submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY masked_id (masked_id),
        KEY cekat_masking_id (cekat_masking_id),
        KEY status (status),
        KEY branch_outlet_id (branch_outlet_id),
        KEY submitted_at (submitted_at),
        KEY cekat_name (cekat_name),
        KEY lead_source (lead_source),
        KEY computed_report_type (computed_report_type),
        KEY computed_score (computed_score)
    ) {$charset_collate};";

    $branch_table = eh_branch_outlet_table_name();
    $branch_sql = "CREATE TABLE {$branch_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        cekat_masking_id VARCHAR(36) NOT NULL,
        cekat_created_at VARCHAR(64) NULL,
        cekat_business_id VARCHAR(36) NULL,
        cekat_name VARCHAR(191) NOT NULL DEFAULT '',
        display_name VARCHAR(191) NOT NULL DEFAULT '',
        cekat_description TEXT NULL,
        cekat_phone_number VARCHAR(50) NULL,
        cekat_status VARCHAR(32) NULL,
        cekat_ai_agent_id VARCHAR(36) NULL,
        cekat_image_url TEXT NULL,
        cekat_type VARCHAR(32) NULL,
        cekat_ai_agent_json LONGTEXT NULL,
        cekat_wa_template_masking_id VARCHAR(64) NULL,
        cekat_wa_template_name VARCHAR(191) NULL,
        deleted_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY cekat_masking_id (cekat_masking_id),
        KEY deleted_at (deleted_at),
        KEY cekat_name (cekat_name)
    ) {$charset_collate};";

    $agent_table = eh_hair_specialist_agent_table_name();
    $agent_sql = "CREATE TABLE {$agent_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        masking_id VARCHAR(64) NOT NULL,
        name VARCHAR(191) NOT NULL DEFAULT '',
        email VARCHAR(191) NOT NULL DEFAULT '',
        branch_outlet_id BIGINT UNSIGNED NOT NULL,
        agent_code VARCHAR(64) NOT NULL,
        deleted_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY masking_id (masking_id),
        KEY agent_code (agent_code),
        KEY branch_outlet_id (branch_outlet_id),
        KEY deleted_at (deleted_at)
    ) {$charset_collate};";

    $report_pdf_table = eh_assessment_report_pdf_template_table_name();
    $report_pdf_sql = "CREATE TABLE {$report_pdf_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        masking_id VARCHAR(64) NOT NULL,
        report_title VARCHAR(255) NOT NULL DEFAULT '',
        report_header_title VARCHAR(255) NOT NULL DEFAULT '',
        subtitle VARCHAR(255) NOT NULL DEFAULT '',
        greeting_description LONGTEXT NULL,
        diagnosis_name LONGTEXT NULL,
        diagnosis_name_detail VARCHAR(255) NOT NULL DEFAULT '',
        title_condition_explanation VARCHAR(255) NOT NULL DEFAULT '',
        description_condition_explanation LONGTEXT NULL,
        title_clinical_knowledge VARCHAR(255) NOT NULL DEFAULT '',
        subtitle_clinical_knowledge VARCHAR(255) NOT NULL DEFAULT '',
        image_clinical_knowledge VARCHAR(500) NOT NULL DEFAULT '',
        description_clinical_knowledge LONGTEXT NULL,
        title_evaluation_urgency VARCHAR(255) NOT NULL DEFAULT '',
        description_evaluation_urgency LONGTEXT NULL,
        title_treatment_journey VARCHAR(255) NOT NULL DEFAULT '',
        description_treatment_journey LONGTEXT NULL,
        image_treatment_journey VARCHAR(500) NOT NULL DEFAULT '',
        title_recommendation_approach VARCHAR(255) NOT NULL DEFAULT '',
        description_recommendation_approach LONGTEXT NULL,
        detail_recommendation_approach LONGTEXT NULL,
        bottom_description_recommendation_approach LONGTEXT NULL,
        title_next_steps VARCHAR(255) NOT NULL DEFAULT '',
        description_next_steps LONGTEXT NULL,
        title_medical_notes VARCHAR(255) NOT NULL DEFAULT '',
        body_medical_notes LONGTEXT NULL,
        description_medical_notes LONGTEXT NULL,
        deleted_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY masking_id (masking_id),
        KEY deleted_at (deleted_at),
        KEY updated_at (updated_at)
    ) {$charset_collate};";

    dbDelta($specialist_sql);
    dbDelta($assessment_sql);
    dbDelta($branch_sql);
    dbDelta($agent_sql);
    dbDelta($report_pdf_sql);
}

/**
 * Drop legacy UNIQUE on assessment submissions cekat_masking_id (allow multiple submissions per inbox).
 */
function eh_assessment_migrate_assessment_cekat_unique_to_index(): void
{
    if ((string) get_option('eh_assessment_v140_cekat_uniq_dropped', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v140_cekat_uniq_dropped', '1');
        return;
    }

    $indexes = $wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name = 'cekat_masking_id'");
    $is_unique = false;
    foreach ($indexes as $ix) {
        if (isset($ix->Non_unique) && (int) $ix->Non_unique === 0) {
            $is_unique = true;
            break;
        }
    }

    if ($is_unique) {
        $wpdb->query("ALTER TABLE `{$table}` DROP INDEX `cekat_masking_id`");
        $wpdb->query("ALTER TABLE `{$table}` ADD INDEX `cekat_masking_id` (`cekat_masking_id`)");
    }

    update_option('eh_assessment_v140_cekat_uniq_dropped', '1');
}

/**
 * Add respondent_birthdate + branch_outlet_id; remove legacy hair_specialist_id from assessment submissions.
 */
function eh_assessment_migrate_v150_submission_branch_birthdate(): void
{
    if ((string) get_option('eh_assessment_v150_submission_schema', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v150_submission_schema', '1');
        return;
    }

    if (!eh_assessment_assessment_table_has_column('respondent_birthdate')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN respondent_birthdate DATE NULL AFTER respondent_gender");
    }

    if (!eh_assessment_assessment_table_has_column('branch_outlet_id')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN branch_outlet_id BIGINT UNSIGNED NULL AFTER respondent_birthdate");
    }

    $branch_idx = $wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name = 'branch_outlet_id'");
    if (!$branch_idx) {
        $wpdb->query("ALTER TABLE `{$table}` ADD KEY branch_outlet_id (branch_outlet_id)");
    }

    if (eh_assessment_assessment_table_has_column('hair_specialist_id')) {
        $hs_idx = $wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name = 'hair_specialist_id'");
        if ($hs_idx) {
            $wpdb->query("ALTER TABLE `{$table}` DROP INDEX `hair_specialist_id`");
        }
        $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN hair_specialist_id");
    }

    update_option('eh_assessment_v150_submission_schema', '1');
}

/**
 * WhatsApp template id + name on branch outlets (Cekat GET /templates).
 */
function eh_assessment_migrate_v170_branch_wa_template_columns(): void
{
    if ((string) get_option('eh_assessment_v170_branch_wa_template', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_branch_outlet_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v170_branch_wa_template', '1');

        return;
    }

    if (!eh_assessment_branch_table_has_column('cekat_wa_template_masking_id')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN cekat_wa_template_masking_id VARCHAR(64) NULL AFTER cekat_ai_agent_json");
    }

    if (!eh_assessment_branch_table_has_column('cekat_wa_template_name')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN cekat_wa_template_name VARCHAR(191) NULL AFTER cekat_wa_template_masking_id");
    }

    update_option('eh_assessment_v170_branch_wa_template', '1');
}

/**
 * Remove deprecated template_message_to_inbox_* columns from branch outlets (no longer used).
 */
function eh_assessment_migrate_v174_branch_drop_template_message_to_inbox(): void
{
    if ((string) get_option('eh_assessment_v174_branch_tpl_inbox_dropped', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_branch_outlet_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v174_branch_tpl_inbox_dropped', '1');

        return;
    }

    if (eh_assessment_branch_table_has_column('template_message_to_inbox_name')) {
        $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN template_message_to_inbox_name");
    }

    if (eh_assessment_branch_table_has_column('template_message_to_inbox_masking_id')) {
        $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN template_message_to_inbox_masking_id");
    }

    update_option('eh_assessment_v174_branch_tpl_inbox_dropped', '1');
}

function eh_assessment_migrate_v180_submission_agent_name(): void
{
    if ((string) get_option('eh_assessment_v180_submission_agent_name', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_table_name();
    if (!eh_assessment_assessment_table_has_column('agent_name')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN agent_name VARCHAR(191) NULL AFTER branch_outlet_id");
    }

    update_option('eh_assessment_v180_submission_agent_name', '1');
}

/**
 * Assessment outcomes: lead source (UTM), decision tree, score, band, visual cards (EUROHAIRLAB spec).
 */
function eh_assessment_migrate_v190_submission_computed_columns(): void
{
    if ((string) get_option('eh_assessment_v190_submission_computed', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v190_submission_computed', '1');

        return;
    }

    if (!eh_assessment_assessment_table_has_column('lead_source')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN lead_source VARCHAR(32) NOT NULL DEFAULT 'direct' AFTER consent");
    }
    if (!eh_assessment_assessment_table_has_column('computed_report_type')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_report_type TINYINT UNSIGNED NULL AFTER q9_expected_result");
    }
    if (!eh_assessment_assessment_table_has_column('computed_score')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_score TINYINT UNSIGNED NULL AFTER computed_report_type");
    }
    if (!eh_assessment_assessment_table_has_column('computed_band')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_band VARCHAR(64) NULL AFTER computed_score");
    }
    if (!eh_assessment_assessment_table_has_column('computed_maintenance_path')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_maintenance_path VARCHAR(64) NULL AFTER computed_band");
    }
    if (!eh_assessment_assessment_table_has_column('computed_patient_type')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_patient_type TINYINT UNSIGNED NULL AFTER computed_maintenance_path");
    }
    if (!eh_assessment_assessment_table_has_column('computed_communication_strategy')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_communication_strategy VARCHAR(191) NULL AFTER computed_patient_type");
    }
    if (!eh_assessment_assessment_table_has_column('computed_condition_title')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_condition_title VARCHAR(255) NULL AFTER computed_communication_strategy");
    }
    if (!eh_assessment_assessment_table_has_column('computed_clinical_warnings')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_clinical_warnings VARCHAR(500) NULL AFTER computed_condition_title");
    }
    if (!eh_assessment_assessment_table_has_column('computed_urgency_text')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_urgency_text VARCHAR(500) NULL AFTER computed_clinical_warnings");
    }
    if (!eh_assessment_assessment_table_has_column('computed_genetic_clinical_text')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN computed_genetic_clinical_text TEXT NULL AFTER computed_urgency_text");
    }
    if (!eh_assessment_assessment_table_has_column('score_visual_scalp')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN score_visual_scalp TINYINT UNSIGNED NULL AFTER computed_genetic_clinical_text");
    }
    if (!eh_assessment_assessment_table_has_column('score_visual_follicle')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN score_visual_follicle TINYINT UNSIGNED NULL AFTER score_visual_scalp");
    }
    if (!eh_assessment_assessment_table_has_column('score_visual_thinning_risk')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN score_visual_thinning_risk TINYINT UNSIGNED NULL AFTER score_visual_follicle");
    }

    $idx = $wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name = 'lead_source'");
    if (!$idx) {
        $wpdb->query("ALTER TABLE `{$table}` ADD KEY lead_source (lead_source)");
    }
    $idx2 = $wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name = 'computed_report_type'");
    if (!$idx2) {
        $wpdb->query("ALTER TABLE `{$table}` ADD KEY computed_report_type (computed_report_type)");
    }
    $idx3 = $wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name = 'computed_score'");
    if (!$idx3) {
        $wpdb->query("ALTER TABLE `{$table}` ADD KEY computed_score (computed_score)");
    }

    update_option('eh_assessment_v190_submission_computed', '1');
}

/**
 * Add deleted_at for Report PDF template soft deletes.
 */
function eh_assessment_migrate_v177_report_pdf_template_soft_delete(): void
{
    if ((string) get_option('eh_assessment_v177_rpt_tpl_deleted_at', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v177_rpt_tpl_deleted_at', '1');

        return;
    }

    if (!eh_assessment_report_pdf_template_table_has_column('deleted_at')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN deleted_at DATETIME NULL AFTER treatment_rec_2_image");
    }

    $idx = $wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name = 'deleted_at'");
    if (!$idx) {
        $wpdb->query("ALTER TABLE `{$table}` ADD KEY deleted_at (deleted_at)");
    }

    update_option('eh_assessment_v177_rpt_tpl_deleted_at', '1');
}

/**
 * Add PHASE OF HAIR GROWTH male/female image columns to report PDF templates.
 */
function eh_assessment_migrate_v178_report_pdf_template_phase_images(): void
{
    if ((string) get_option('eh_assessment_v178_rpt_tpl_phase_images', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v178_rpt_tpl_phase_images', '1');

        return;
    }

    if (!eh_assessment_report_pdf_template_table_has_column('phase_of_hair_growth_male_image')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN phase_of_hair_growth_male_image VARCHAR(500) NOT NULL DEFAULT '' AFTER treatment_rec_1_image");
    }

    if (!eh_assessment_report_pdf_template_table_has_column('phase_of_hair_growth_female_image')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN phase_of_hair_growth_female_image VARCHAR(500) NOT NULL DEFAULT '' AFTER phase_of_hair_growth_male_image");
    }

    update_option('eh_assessment_v178_rpt_tpl_phase_images', '1');
}

function eh_assessment_migrate_v180_report_pdf_template_risk_untreated_image(): void
{
    if (get_option('eh_assessment_v180_rpt_tpl_risk_untreated_image') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v180_rpt_tpl_risk_untreated_image', '1');

        return;
    }

    if (!eh_assessment_report_pdf_template_table_has_column('risk_untreated_image')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN risk_untreated_image VARCHAR(500) NOT NULL DEFAULT '' AFTER risk_delayed_description");
    }

    update_option('eh_assessment_v180_rpt_tpl_risk_untreated_image', '1');
}

/**
 * Add optional treatment recommendation #3 fields to report PDF templates.
 */
function eh_assessment_migrate_v191_report_pdf_template_treatment_rec_3(): void
{
    if ((string) get_option('eh_assessment_v191_rpt_tpl_treatment_rec_3', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v191_rpt_tpl_treatment_rec_3', '1');

        return;
    }

    if (!eh_assessment_report_pdf_template_table_has_column('treatment_rec_3_title')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN treatment_rec_3_title VARCHAR(255) NOT NULL DEFAULT '' AFTER treatment_rec_2_image");
    }
    if (!eh_assessment_report_pdf_template_table_has_column('treatment_rec_3_description')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN treatment_rec_3_description LONGTEXT NULL AFTER treatment_rec_3_title");
    }
    if (!eh_assessment_report_pdf_template_table_has_column('treatment_rec_3_image')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN treatment_rec_3_image VARCHAR(500) NOT NULL DEFAULT '' AFTER treatment_rec_3_description");
    }

    update_option('eh_assessment_v191_rpt_tpl_treatment_rec_3', '1');
}

/**
 * Add report v2 content columns for Pre-Consultation report layout.
 */
function eh_assessment_migrate_v200_report_pdf_template_precon_fields(): void
{
    if ((string) get_option('eh_assessment_v200_rpt_tpl_precon_fields', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v200_rpt_tpl_precon_fields', '1');

        return;
    }

    $columns = [
        'subtitle' => "ALTER TABLE `{$table}` ADD COLUMN subtitle VARCHAR(255) NOT NULL DEFAULT '' AFTER report_title",
        'greeting_description' => "ALTER TABLE `{$table}` ADD COLUMN greeting_description LONGTEXT NULL AFTER subtitle",
        'title_condition_explanation' => "ALTER TABLE `{$table}` ADD COLUMN title_condition_explanation VARCHAR(255) NOT NULL DEFAULT '' AFTER diagnosis_name",
        'description_condition_explanation' => "ALTER TABLE `{$table}` ADD COLUMN description_condition_explanation LONGTEXT NULL AFTER title_condition_explanation",
        'title_clinical_knowledge' => "ALTER TABLE `{$table}` ADD COLUMN title_clinical_knowledge VARCHAR(255) NOT NULL DEFAULT '' AFTER description_condition_explanation",
        'subtitle_clinical_knowledge' => "ALTER TABLE `{$table}` ADD COLUMN subtitle_clinical_knowledge VARCHAR(255) NOT NULL DEFAULT '' AFTER title_clinical_knowledge",
        'image_clinical_knowledge' => "ALTER TABLE `{$table}` ADD COLUMN image_clinical_knowledge VARCHAR(500) NOT NULL DEFAULT '' AFTER subtitle_clinical_knowledge",
        'description_clinical_knowledge' => "ALTER TABLE `{$table}` ADD COLUMN description_clinical_knowledge LONGTEXT NULL AFTER image_clinical_knowledge",
        'title_evaluation_urgency' => "ALTER TABLE `{$table}` ADD COLUMN title_evaluation_urgency VARCHAR(255) NOT NULL DEFAULT '' AFTER description_clinical_knowledge",
        'description_evaluation_urgency' => "ALTER TABLE `{$table}` ADD COLUMN description_evaluation_urgency LONGTEXT NULL AFTER title_evaluation_urgency",
        'title_treatment_journey' => "ALTER TABLE `{$table}` ADD COLUMN title_treatment_journey VARCHAR(255) NOT NULL DEFAULT '' AFTER description_evaluation_urgency",
        'description_treatment_journey' => "ALTER TABLE `{$table}` ADD COLUMN description_treatment_journey LONGTEXT NULL AFTER title_treatment_journey",
        'image_treatment_journey' => "ALTER TABLE `{$table}` ADD COLUMN image_treatment_journey VARCHAR(500) NOT NULL DEFAULT '' AFTER description_treatment_journey",
        'title_recommendation_approach' => "ALTER TABLE `{$table}` ADD COLUMN title_recommendation_approach VARCHAR(255) NOT NULL DEFAULT '' AFTER image_treatment_journey",
        'description_recommendation_approach' => "ALTER TABLE `{$table}` ADD COLUMN description_recommendation_approach LONGTEXT NULL AFTER title_recommendation_approach",
        'detail_recommendation_approach' => "ALTER TABLE `{$table}` ADD COLUMN detail_recommendation_approach LONGTEXT NULL AFTER description_recommendation_approach",
        'bottom_description_recommendation_approach' => "ALTER TABLE `{$table}` ADD COLUMN bottom_description_recommendation_approach LONGTEXT NULL AFTER detail_recommendation_approach",
        'title_next_steps' => "ALTER TABLE `{$table}` ADD COLUMN title_next_steps VARCHAR(255) NOT NULL DEFAULT '' AFTER bottom_description_recommendation_approach",
        'description_next_steps' => "ALTER TABLE `{$table}` ADD COLUMN description_next_steps LONGTEXT NULL AFTER title_next_steps",
        'title_medical_notes' => "ALTER TABLE `{$table}` ADD COLUMN title_medical_notes VARCHAR(255) NOT NULL DEFAULT '' AFTER description_next_steps",
        'description_medical_notes' => "ALTER TABLE `{$table}` ADD COLUMN description_medical_notes LONGTEXT NULL AFTER title_medical_notes",
    ];

    foreach ($columns as $column => $sql) {
        if (!eh_assessment_report_pdf_template_table_has_column($column)) {
            $wpdb->query($sql);
        }
    }

    update_option('eh_assessment_v200_rpt_tpl_precon_fields', '1');
}

/**
 * Drop legacy report template columns no longer used by the new Pre-Consultation layout.
 */
function eh_assessment_migrate_v201_report_pdf_template_drop_legacy_fields(): void
{
    if ((string) get_option('eh_assessment_v201_rpt_tpl_drop_legacy_fields', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v201_rpt_tpl_drop_legacy_fields', '1');

        return;
    }

    $legacyColumns = [
        'diagnosis_description',
        'clinical_desc_1',
        'clinical_desc_2',
        'clinical_desc_3',
        'risk_delayed_description',
        'risk_untreated_image',
        'risk_untreated_description',
        'treatment_rec_1_title',
        'treatment_rec_1_description',
        'treatment_rec_1_image',
        'phase_of_hair_growth_male_image',
        'phase_of_hair_growth_female_image',
        'treatment_rec_2_title',
        'treatment_rec_2_description',
        'treatment_rec_2_image',
        'treatment_rec_3_title',
        'treatment_rec_3_description',
        'treatment_rec_3_image',
    ];

    foreach ($legacyColumns as $column) {
        if (eh_assessment_report_pdf_template_table_has_column($column)) {
            $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
        }
    }

    update_option('eh_assessment_v201_rpt_tpl_drop_legacy_fields', '1');
}

/**
 * One-time seed of Pre-Consultation template fields for the eight default masking_id rows (Reports 1–8).
 * To re-run: delete option `eh_assessment_v202_rpt_tpl_seed_precon_defaults` from the options table.
 */
function eh_assessment_migrate_v202_report_pdf_template_seed_precon_defaults(): void
{
    if ((string) get_option('eh_assessment_v202_rpt_tpl_seed_precon_defaults', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v202_rpt_tpl_seed_precon_defaults', '1');

        return;
    }

    if (!eh_assessment_report_pdf_template_table_has_column('greeting_description')) {
        return;
    }

    require_once __DIR__ . '/eh-assessment-seed-precon-report-templates.php';
    $seeds = eh_assessment_precon_report_pdf_template_seed_rows();
    $now = eh_assessment_current_mysql_time();

    foreach ($seeds as $masking_id => $row) {
        $id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM `{$table}` WHERE masking_id = %s AND deleted_at IS NULL LIMIT 1",
                $masking_id
            )
        );
        if ($id <= 0) {
            continue;
        }

        $payload = array_merge($row, ['updated_at' => $now]);
        $formats = array_fill(0, count($payload), '%s');
        $wpdb->update($table, $payload, ['id' => $id], $formats, ['%d']);
    }

    update_option('eh_assessment_v202_rpt_tpl_seed_precon_defaults', '1');
}

/**
 * Pre-Consultation PDF: small caps line above the gold title (e.g. HAIR HEALTH). Renamed admin label: report name vs header title.
 */
function eh_assessment_migrate_v203_report_pdf_template_report_header_title(): void
{
    if ((string) get_option('eh_assessment_v203_rpt_tpl_report_header_title', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v203_rpt_tpl_report_header_title', '1');

        return;
    }

    if (!eh_assessment_report_pdf_template_table_has_column('report_header_title')) {
        $wpdb->query(
            "ALTER TABLE `{$table}` ADD COLUMN report_header_title VARCHAR(255) NOT NULL DEFAULT '' AFTER report_title"
        );
    }
    $wpdb->query(
        "UPDATE `{$table}` SET report_header_title = 'HAIR HEALTH' WHERE report_header_title = ''"
    );

    update_option('eh_assessment_v203_rpt_tpl_report_header_title', '1');
}

/**
 * Pre-Consultation: body copy under “CATATAN MEDIS” lives in body_medical_notes; description_medical_notes is footer-only.
 */
function eh_assessment_migrate_v204_report_pdf_template_body_medical_notes(): void
{
    if ((string) get_option('eh_assessment_v204_rpt_tpl_body_medical_notes', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v204_rpt_tpl_body_medical_notes', '1');

        return;
    }

    if (!eh_assessment_report_pdf_template_table_has_column('body_medical_notes')) {
        $wpdb->query(
            "ALTER TABLE `{$table}` ADD COLUMN body_medical_notes LONGTEXT NULL AFTER title_medical_notes"
        );
    }

    $wpdb->query(
        "UPDATE `{$table}` SET body_medical_notes = description_medical_notes
         WHERE (body_medical_notes IS NULL OR TRIM(body_medical_notes) = '')
         AND description_medical_notes IS NOT NULL
         AND TRIM(description_medical_notes) <> ''"
    );

    $seed_file = __DIR__ . '/eh-assessment-seed-precon-report-templates.php';
    if (is_readable($seed_file)) {
        require_once $seed_file;
        $seeds = eh_assessment_precon_report_pdf_template_seed_rows();
        $now = eh_assessment_current_mysql_time();
        foreach ($seeds as $masking_id => $row) {
            if (!isset($row['body_medical_notes'])) {
                continue;
            }
            $payload = [
                'body_medical_notes' => $row['body_medical_notes'],
                'updated_at' => $now,
            ];
            if (array_key_exists('description_medical_notes', $row)) {
                $payload['description_medical_notes'] = $row['description_medical_notes'];
            }
            $formats = array_fill(0, count($payload), '%s');
            $wpdb->update($table, $payload, ['masking_id' => $masking_id], $formats, ['%s']);
        }
    }

    update_option('eh_assessment_v204_rpt_tpl_body_medical_notes', '1');
}

/**
 * Pre-Consultation score pill subtitle: template {@see diagnosis_name_detail}, else submission computed band.
 */
function eh_assessment_migrate_v205_report_pdf_template_diagnosis_name_detail(): void
{
    if ((string) get_option('eh_assessment_v205_rpt_tpl_diagnosis_name_detail', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v205_rpt_tpl_diagnosis_name_detail', '1');

        return;
    }

    if (!eh_assessment_report_pdf_template_table_has_column('diagnosis_name_detail')) {
        $wpdb->query(
            "ALTER TABLE `{$table}` ADD COLUMN diagnosis_name_detail VARCHAR(255) NOT NULL DEFAULT '' AFTER diagnosis_name"
        );
    }

    $seed_file = __DIR__ . '/eh-assessment-seed-precon-report-templates.php';
    if (is_readable($seed_file)) {
        require_once $seed_file;
        $seeds = eh_assessment_precon_report_pdf_template_seed_rows();
        $now = eh_assessment_current_mysql_time();
        foreach ($seeds as $masking_id => $row) {
            if (!array_key_exists('diagnosis_name_detail', $row)) {
                continue;
            }
            $payload = [
                'diagnosis_name_detail' => (string) $row['diagnosis_name_detail'],
                'updated_at' => $now,
            ];
            $formats = array_fill(0, count($payload), '%s');
            $wpdb->update($table, $payload, ['masking_id' => $masking_id], $formats, ['%s']);
        }
    }

    update_option('eh_assessment_v205_rpt_tpl_diagnosis_name_detail', '1');
}

/**
 * Remove submission soft-delete: UNIQUE masked_id must stay global; trash rows blocked new IDs.
 */
function eh_assessment_migrate_v179_drop_submission_deleted_at(): void
{
    if ((string) get_option('eh_assessment_v179_submission_deleted_at_dropped', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_assessment_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v179_submission_deleted_at_dropped', '1');

        return;
    }

    if (eh_assessment_assessment_table_has_column('deleted_at')) {
        $idx = $wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name = 'deleted_at'");
        if ($idx) {
            $wpdb->query("ALTER TABLE `{$table}` DROP INDEX deleted_at");
        }
        $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN deleted_at");
    }

    update_option('eh_assessment_v179_submission_deleted_at_dropped', '1');
}

/**
 * Branch office: optional label for assessment UI (separate from Cekat inbox name).
 */
function eh_assessment_migrate_v181_branch_outlet_display_name(): void
{
    if ((string) get_option('eh_assessment_v181_branch_display_name', '') === '1') {
        return;
    }

    global $wpdb;
    $table = eh_branch_outlet_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        update_option('eh_assessment_v181_branch_display_name', '1');

        return;
    }

    if (!eh_assessment_branch_table_has_column('display_name')) {
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN display_name VARCHAR(191) NOT NULL DEFAULT '' AFTER cekat_name");
    }

    update_option('eh_assessment_v181_branch_display_name', '1');
}

function eh_assessment_seed_sample_data(): void
{
    global $wpdb;

    $hair_specialist_table = eh_hair_specialist_table_name();
    $assessment_table = eh_assessment_table_name();

    $specialist_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$hair_specialist_table}");
    if ($specialist_count === 0) {
        $specialists = [
            [
                'masked_id' => eh_assessment_generate_masked_id('HS', $hair_specialist_table),
                'name' => 'Dr. Amelia Hartono',
                'email' => 'amelia.hartono@eurohairlab.com',
                'wa_number' => eh_assessment_normalize_whatsapp('+6281110001001'),
            ],
            [
                'masked_id' => eh_assessment_generate_masked_id('HS', $hair_specialist_table),
                'name' => 'Dr. Benjamin Lee',
                'email' => 'benjamin.lee@eurohairlab.com',
                'wa_number' => eh_assessment_normalize_whatsapp('+6281110001002'),
            ],
        ];

        foreach ($specialists as $specialist) {
            $wpdb->insert($hair_specialist_table, $specialist);
        }
    }

    $count_sql = "SELECT COUNT(*) FROM {$assessment_table}";
    $assessment_count = (int) $wpdb->get_var($count_sql);
    if ($assessment_count > 0) {
        return;
    }

    $samples = [
        [
            'status' => 'On Progress',
            'respondent_birthdate' => '1992-05-14',
            'branch_outlet_masking_id' => '',
            'respondent_name' => 'Maximillian Christianto',
            'respondent_whatsapp' => eh_assessment_normalize_whatsapp('+6281230928614'),
            'respondent_gender' => 'male',
            'consent' => 1,
            'answers' => [
                'q1_focus_area' => ['question' => 'Which hair or scalp change are you noticing the most?', 'answer' => 'Crown Thinning'],
                'q2_main_impact' => ['question' => 'What is the biggest impact you are feeling?', 'answer' => 'Lower Confidence'],
                'q3_duration' => ['question' => 'How long have you noticed this change?', 'answer' => '3 to 6 Months'],
                'q4_family_history' => ['question' => 'Is there a family history of a similar condition?', 'answer' => 'No'],
                'q5_previous_attempts' => ['question' => 'What have you tried so far?', 'answer' => 'Nothing Yet'],
                'q6_trigger_factors' => ['question' => 'Are you currently experiencing any of the following factors?', 'answer' => 'Prolonged Stress'],
                'q7_biggest_worry' => ['question' => 'If left untreated, what worries you the most?', 'answer' => 'The Thinning Area Will Spread'],
                'q8_previous_consultation' => ['question' => 'Have you had a consultation before?', 'answer' => 'Never'],
                'q9_expected_result' => ['question' => 'If your condition improves, what result are you hoping for?', 'answer' => 'Looking Younger'],
            ],
        ],
        [
            'status' => 'Complete',
            'respondent_birthdate' => '1988-11-02',
            'branch_outlet_masking_id' => '',
            'respondent_name' => 'Clarissa Wijaya',
            'respondent_whatsapp' => eh_assessment_normalize_whatsapp('+628119990011'),
            'respondent_gender' => 'female',
            'consent' => 1,
            'answers' => [
                'q1_focus_area' => ['question' => 'Which hair or scalp change are you noticing the most?', 'answer' => 'Diffuse Thinning'],
                'q2_main_impact' => ['question' => 'What is the biggest impact you are feeling?', 'answer' => 'Looking Older'],
                'q3_duration' => ['question' => 'How long have you noticed this change?', 'answer' => 'More Than 1 Year'],
                'q4_family_history' => ['question' => 'Is there a family history of a similar condition?', 'answer' => 'Yes'],
                'q5_previous_attempts' => ['question' => 'What have you tried so far?', 'answer' => 'Products / Serums'],
                'q6_trigger_factors' => ['question' => 'Are you currently experiencing any of the following factors?', 'answer' => 'Hormonal Changes'],
                'q7_biggest_worry' => ['question' => 'If left untreated, what worries you the most?', 'answer' => 'I Will Lose Confidence'],
                'q8_previous_consultation' => ['question' => 'Have you had a consultation before?', 'answer' => 'Dermatologist'],
                'q9_expected_result' => ['question' => 'If your condition improves, what result are you hoping for?', 'answer' => 'Thicker and Healthier Hair'],
            ],
        ],
        [
            'status' => 'Failed',
            'respondent_birthdate' => null,
            'branch_outlet_masking_id' => '',
            'respondent_name' => 'Jonathan Saputra',
            'respondent_whatsapp' => eh_assessment_normalize_whatsapp('081299900123'),
            'respondent_gender' => 'male',
            'consent' => 1,
            'answers' => [
                'q1_focus_area' => ['question' => 'Which hair or scalp change are you noticing the most?', 'answer' => 'Excessive Hair Fall'],
                'q2_main_impact' => ['question' => 'What is the biggest impact you are feeling?', 'answer' => 'Stressed About My Hair'],
                'q3_duration' => ['question' => 'How long have you noticed this change?', 'answer' => 'Less Than 3 Months'],
                'q4_family_history' => ['question' => 'Is there a family history of a similar condition?', 'answer' => 'Not Sure'],
                'q5_previous_attempts' => ['question' => 'What have you tried so far?', 'answer' => 'Clinic Treatments'],
                'q6_trigger_factors' => ['question' => 'Are you currently experiencing any of the following factors?', 'answer' => 'Lack of Sleep'],
                'q7_biggest_worry' => ['question' => 'If left untreated, what worries you the most?', 'answer' => 'I May Need a Transplant'],
                'q8_previous_consultation' => ['question' => 'Have you had a consultation before?', 'answer' => 'Aesthetic Clinic'],
                'q9_expected_result' => ['question' => 'If your condition improves, what result are you hoping for?', 'answer' => 'Feeling More Confident'],
            ],
        ],
    ];

    foreach ($samples as $sample) {
        $payload = [
            'submission' => [
                'source_page_slug' => '',
                'status' => $sample['status'],
                'branch_outlet_masking_id' => eh_assessment_normalize_submission_branch_masking_id((string) ($sample['branch_outlet_masking_id'] ?? '')),
                'source' => 'website',
            ],
            'respondent' => [
                'name' => $sample['respondent_name'],
                'whatsapp' => $sample['respondent_whatsapp'],
                'gender' => $sample['respondent_gender'],
                'birthdate' => $sample['respondent_birthdate'] ?? '',
                'consent' => (bool) $sample['consent'],
            ],
            'answers' => $sample['answers'],
        ];

        $sanitized_seed = eh_assessment_sanitize_payload($payload);
        $cdb_seed = eh_assessment_attach_computed_to_sanitized($sanitized_seed);
        $rt_seed = (int) ($sanitized_seed['submission']['report_type'] ?? 5);

        $row = [
            'masked_id' => eh_assessment_generate_submission_masked_id($rt_seed, $assessment_table),
            'source_page_id' => null,
            'status' => $sample['status'],
            'respondent_name' => $sample['respondent_name'],
            'respondent_whatsapp' => $sample['respondent_whatsapp'],
            'respondent_gender' => $sample['respondent_gender'],
            'respondent_birthdate' => $sample['respondent_birthdate'] ?? null,
            'branch_outlet_id' => eh_assessment_resolve_branch_outlet_id_from_masking_id((string) ($sample['branch_outlet_masking_id'] ?? '')) ?: null,
            'agent_name' => null,
            'consent' => $sample['consent'],
            'lead_source' => $cdb_seed['lead_source'],
            'q1_focus_area' => $sample['answers']['q1_focus_area']['answer'],
            'q2_main_impact' => $sample['answers']['q2_main_impact']['answer'],
            'q3_duration' => $sample['answers']['q3_duration']['answer'],
            'q4_family_history' => $sample['answers']['q4_family_history']['answer'],
            'q5_previous_attempts' => $sample['answers']['q5_previous_attempts']['answer'],
            'q6_trigger_factors' => $sample['answers']['q6_trigger_factors']['answer'],
            'q7_biggest_worry' => $sample['answers']['q7_biggest_worry']['answer'],
            'q8_previous_consultation' => $sample['answers']['q8_previous_consultation']['answer'],
            'q9_expected_result' => $sample['answers']['q9_expected_result']['answer'],
        ];
        $row = array_merge($row, $cdb_seed['metrics']);
        $seed_tail = [
            'cekat_masking_id' => null,
            'cekat_created_at' => null,
            'cekat_business_id' => null,
            'cekat_name' => null,
            'cekat_description' => null,
            'cekat_phone_number' => null,
            'cekat_status' => null,
            'cekat_ai_agent_id' => null,
            'cekat_image_url' => null,
            'cekat_type' => null,
            'cekat_ai_agent_json' => null,
            'payload_json' => eh_assessment_payload_json_for_storage($sanitized_seed),
            'submitted_at' => eh_assessment_current_mysql_time(),
            'updated_at' => eh_assessment_current_mysql_time(),
        ];
        $row = array_merge($row, $seed_tail);

        $wpdb->insert($assessment_table, $row, eh_assessment_submission_row_format_types());
    }
}

function eh_assessment_activate(): void
{
    eh_assessment_apply_role_capabilities();
    eh_assessment_create_tables();
    eh_assessment_migrate_assessment_cekat_unique_to_index();
    eh_assessment_migrate_v150_submission_branch_birthdate();
    eh_assessment_migrate_v170_branch_wa_template_columns();
    eh_assessment_migrate_v174_branch_drop_template_message_to_inbox();
    eh_assessment_migrate_v180_submission_agent_name();
    eh_assessment_migrate_v190_submission_computed_columns();
    eh_assessment_migrate_v177_report_pdf_template_soft_delete();
    eh_assessment_migrate_v178_report_pdf_template_phase_images();
    eh_assessment_migrate_v180_report_pdf_template_risk_untreated_image();
    eh_assessment_migrate_v191_report_pdf_template_treatment_rec_3();
    eh_assessment_migrate_v200_report_pdf_template_precon_fields();
    eh_assessment_migrate_v201_report_pdf_template_drop_legacy_fields();
    eh_assessment_migrate_v203_report_pdf_template_report_header_title();
    eh_assessment_migrate_v204_report_pdf_template_body_medical_notes();
    eh_assessment_migrate_v205_report_pdf_template_diagnosis_name_detail();
    eh_assessment_migrate_v202_report_pdf_template_seed_precon_defaults();
    eh_assessment_migrate_v179_drop_submission_deleted_at();
    eh_assessment_migrate_v180_report_pdf_template_risk_untreated_image();
    eh_assessment_migrate_v181_branch_outlet_display_name();
    eh_assessment_seed_sample_data();
    update_option('eh_assessment_data_version', EH_ASSESSMENT_DATA_VERSION);
}
register_activation_hook(__FILE__, 'eh_assessment_activate');

function eh_assessment_maybe_upgrade(): void
{
    $installed_version = (string) get_option('eh_assessment_data_version', '');
    eh_assessment_apply_role_capabilities();
    eh_assessment_override_role_labels();
    eh_assessment_create_tables();
    // Do not call eh_assessment_seed_sample_data() here: it runs on every request. An empty
    // submissions table after admin DELETE would be refilled on refresh. Seeding stays on
    // {@see eh_assessment_activate()} only for first-time plugin activation.
    eh_assessment_migrate_legacy_records();
    eh_assessment_migrate_assessment_cekat_unique_to_index();
    eh_assessment_migrate_v150_submission_branch_birthdate();
    eh_assessment_migrate_v170_branch_wa_template_columns();
    eh_assessment_migrate_v174_branch_drop_template_message_to_inbox();
    eh_assessment_migrate_v180_submission_agent_name();
    eh_assessment_migrate_v190_submission_computed_columns();
    eh_assessment_migrate_v177_report_pdf_template_soft_delete();
    eh_assessment_migrate_v178_report_pdf_template_phase_images();
    eh_assessment_migrate_v180_report_pdf_template_risk_untreated_image();
    eh_assessment_migrate_v191_report_pdf_template_treatment_rec_3();
    eh_assessment_migrate_v200_report_pdf_template_precon_fields();
    eh_assessment_migrate_v201_report_pdf_template_drop_legacy_fields();
    eh_assessment_migrate_v203_report_pdf_template_report_header_title();
    eh_assessment_migrate_v204_report_pdf_template_body_medical_notes();
    eh_assessment_migrate_v205_report_pdf_template_diagnosis_name_detail();
    eh_assessment_migrate_v202_report_pdf_template_seed_precon_defaults();
    eh_assessment_migrate_v179_drop_submission_deleted_at();
    eh_assessment_migrate_v181_branch_outlet_display_name();

    if ($installed_version === EH_ASSESSMENT_DATA_VERSION) {
        return;
    }

    update_option('eh_assessment_data_version', EH_ASSESSMENT_DATA_VERSION);
}
add_action('plugins_loaded', 'eh_assessment_maybe_upgrade');
add_action('init', 'eh_assessment_override_role_labels');

function eh_assessment_translate_user_role(string $translated, string $role): string
{
    if ($role === 'Subscriber') {
        return 'Hair Specialist';
    }

    return $translated;
}
add_filter('translate_user_role', 'eh_assessment_translate_user_role', 10, 2);

function eh_assessment_filter_gettext_role_labels(string $translation, string $text, string $domain): string
{
    if ($text === 'Subscriber') {
        return 'Hair Specialist';
    }

    if ($text === 'Subscribers') {
        return 'Hair Specialists';
    }

    return $translation;
}
add_filter('gettext', 'eh_assessment_filter_gettext_role_labels', 10, 3);

function eh_assessment_filter_ngettext_role_labels(string $translation, string $single, string $plural, int $number, string $domain): string
{
    if ($single === 'Subscriber' || $plural === 'Subscribers') {
        return $number === 1 ? 'Hair Specialist' : 'Hair Specialists';
    }

    return $translation;
}
add_filter('ngettext', 'eh_assessment_filter_ngettext_role_labels', 10, 5);

function eh_assessment_filter_editable_roles(array $roles): array
{
    $allowed = ['administrator', 'subscriber'];

    foreach (array_keys($roles) as $role_key) {
        if (!in_array($role_key, $allowed, true)) {
            unset($roles[$role_key]);
        }
    }

    if (isset($roles['subscriber'])) {
        $roles['subscriber']['name'] = 'Hair Specialist';
    }

    if (isset($roles['administrator'])) {
        $roles['administrator']['name'] = 'Administrator';
    }

    return $roles;
}
add_filter('editable_roles', 'eh_assessment_filter_editable_roles');

function eh_assessment_maybe_migrate_failed_sample(): void
{
    global $wpdb;

    if ((string) get_option('eh_assessment_data_failed_sample_seeded', '') === '1') {
        return;
    }

    $assessment_table = eh_assessment_table_name();
    $exists_sql = "SELECT COUNT(*) FROM {$assessment_table} WHERE respondent_name = %s AND status = %s";
    $exists = (int) $wpdb->get_var($wpdb->prepare($exists_sql, 'Jonathan Saputra', 'Failed'));
    if ($exists > 0) {
        update_option('eh_assessment_data_failed_sample_seeded', '1');
        return;
    }

    $payload = [
        'submission' => [
            'source_page_slug' => '',
            'status' => 'Failed',
            'branch_outlet_masking_id' => '',
            'report_type' => 5,
        ],
        'respondent' => [
            'name' => 'Jonathan Saputra',
            'whatsapp' => eh_assessment_normalize_whatsapp('081299900123'),
            'gender' => 'male',
            'birthdate' => '',
            'consent' => true,
        ],
        'answers' => [
            'q1_focus_area' => ['question' => 'Which hair or scalp change are you noticing the most?', 'answer' => 'Excessive Hair Fall'],
            'q2_main_impact' => ['question' => 'What is the biggest impact you are feeling?', 'answer' => 'Stressed About My Hair'],
            'q3_duration' => ['question' => 'How long have you noticed this change?', 'answer' => 'Less Than 3 Months'],
            'q4_family_history' => ['question' => 'Is there a family history of a similar condition?', 'answer' => 'Not Sure'],
            'q5_previous_attempts' => ['question' => 'What have you tried so far?', 'answer' => 'Clinic Treatments'],
            'q6_trigger_factors' => ['question' => 'Are you currently experiencing any of the following factors?', 'answer' => 'Lack of Sleep'],
            'q7_biggest_worry' => ['question' => 'If left untreated, what worries you the most?', 'answer' => 'I May Need a Transplant'],
            'q8_previous_consultation' => ['question' => 'Have you had a consultation before?', 'answer' => 'Aesthetic Clinic'],
            'q9_expected_result' => ['question' => 'If your condition improves, what result are you hoping for?', 'answer' => 'Feeling More Confident'],
        ],
    ];

    $jon_row = [
        'masked_id' => eh_assessment_generate_submission_masked_id(5, $assessment_table),
        'source_page_id' => null,
        'status' => 'Failed',
        'respondent_name' => 'Jonathan Saputra',
        'respondent_whatsapp' => eh_assessment_normalize_whatsapp('081299900123'),
        'respondent_gender' => 'male',
        'respondent_birthdate' => null,
        'branch_outlet_id' => null,
        'consent' => 1,
        'q1_focus_area' => 'Excessive Hair Fall',
        'q2_main_impact' => 'Stressed About My Hair',
        'q3_duration' => 'Less Than 3 Months',
        'q4_family_history' => 'Not Sure',
        'q5_previous_attempts' => 'Clinic Treatments',
        'q6_trigger_factors' => 'Lack of Sleep',
        'q7_biggest_worry' => 'I May Need a Transplant',
        'q8_previous_consultation' => 'Aesthetic Clinic',
        'q9_expected_result' => 'Feeling More Confident',
        'payload_json' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'submitted_at' => eh_assessment_current_mysql_time(),
        'updated_at' => eh_assessment_current_mysql_time(),
    ];
    $wpdb->insert($assessment_table, $jon_row);

    update_option('eh_assessment_data_failed_sample_seeded', '1');
}
add_action('plugins_loaded', 'eh_assessment_maybe_migrate_failed_sample');

/**
 * Input bounds for public assessment UI (mirrors {@see eh_assessment_validate_sanitized_submission_payload()}).
 *
 * @return array{
 *     max_name_utf8_bytes: int,
 *     max_answer_utf8_bytes: int,
 *     max_question_utf8_bytes: int,
 *     whatsapp_digits_min: int,
 *     whatsapp_digits_max: int
 * }
 */
function eh_assessment_get_frontend_input_limits(): array
{
    return [
        'max_name_utf8_bytes' => EH_ASSESSMENT_MAX_NAME_LENGTH,
        'max_answer_utf8_bytes' => EH_ASSESSMENT_MAX_ANSWER_TEXT_LENGTH,
        'max_question_utf8_bytes' => EH_ASSESSMENT_MAX_QUESTION_TEXT_LENGTH,
        'whatsapp_digits_min' => EH_ASSESSMENT_MIN_WHATSAPP_DIGITS,
        'whatsapp_digits_max' => EH_ASSESSMENT_MAX_WHATSAPP_DIGITS,
    ];
}

function eh_assessment_get_frontend_config(): array
{
    $branch_offices = [];
    foreach (eh_assessment_get_active_branch_outlet_options() as $row) {
        $masking = eh_assessment_normalize_submission_branch_masking_id((string) ($row['cekat_masking_id'] ?? ''));
        if ($masking === '') {
            continue;
        }
        $branch_offices[] = [
            'branch_outlet_masking_id' => $masking,
            'name' => eh_assessment_branch_outlet_display_label($row),
        ];
    }

    return [
        'endpoint' => esc_url_raw(rest_url('eurohairlab/v1/assessment-submissions')),
        'branch_offices' => $branch_offices,
        'limits' => eh_assessment_get_frontend_input_limits(),
        'report_type' => eh_assessment_resolve_submission_report_type(null),
    ];
}

function eh_assessment_normalize_status(string $status): string
{
    $allowed = ['On Progress', 'Complete', 'Failed'];
    return in_array($status, $allowed, true) ? $status : 'On Progress';
}

function eh_assessment_mark_stale_submissions_failed(): int
{
    global $wpdb;
    $table = eh_assessment_table_name();
    $now = new DateTimeImmutable('now', eh_assessment_gmt7_timezone());
    $threshold = $now->sub(new DateInterval('PT1H'))->format('Y-m-d H:i:s');

    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table}
             SET status = 'Failed', updated_at = %s
             WHERE status = 'On Progress' AND updated_at <= %s",
            eh_assessment_current_mysql_time(),
            $threshold
        )
    );

    return is_int($updated) ? $updated : 0;
}

function eh_assessment_cekat_submission_saved_webhook_body_from_submission_id(int $submission_id): array|WP_Error
{
    if ($submission_id <= 0) {
        return new WP_Error('invalid_submission', 'Submission id is required.', ['status' => 400]);
    }

    global $wpdb;
    $table = eh_assessment_table_name();
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, masked_id, branch_outlet_id, lead_source, payload_json FROM {$table} WHERE id = %d LIMIT 1",
            $submission_id
        ),
        ARRAY_A
    );

    if (!is_array($row) || !isset($row['id'])) {
        return new WP_Error('not_found', 'Submission not found.', ['status' => 404]);
    }

    $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
    if (!is_array($payload)) {
        $payload = [];
    }
    if (!isset($payload['submission']) || !is_array($payload['submission'])) {
        $payload['submission'] = [];
    }
    if (!isset($payload['respondent']) || !is_array($payload['respondent'])) {
        $payload['respondent'] = [];
    }
    if (!isset($payload['answers']) || !is_array($payload['answers'])) {
        $payload['answers'] = [];
    }

    $sanitized = eh_assessment_attach_computed_to_sanitized($payload);
    $masked_id = trim((string) ($row['masked_id'] ?? ''));
    $branch_outlet_id = (int) ($row['branch_outlet_id'] ?? 0);
    $agent_masking_id = trim((string) ($sanitized['submission']['agent_masking_id'] ?? ''));
    $lead_source = trim((string) ($row['lead_source'] ?? ''));

    if ($masked_id === '') {
        return new WP_Error('invalid_submission', 'Masked id is required.', ['status' => 400]);
    }

    return eh_assessment_cekat_submission_saved_webhook_body(
        $sanitized,
        $masked_id,
        $branch_outlet_id,
        $agent_masking_id,
        $submission_id,
        $lead_source !== '' ? $lead_source : null
    );
}

function eh_assessment_cekat_submission_saved_webhook_dispatch(array $body, string $masked_id, bool $blocking = false): true|WP_Error
{
    $key = eh_assessment_cekat_submission_saved_webhook_key();
    if ($key === '') {
        return new WP_Error('not_configured', 'Webhook key is not configured.', ['status' => 503]);
    }

    $url = eh_assessment_cekat_submission_saved_webhook_url();
    if ($url === '') {
        return new WP_Error('no_url', 'Webhook URL is empty.', ['status' => 503]);
    }

    $headers = [
        'Content-Type' => 'application/json',
        'Authorization' => $key,
    ];
    $headers = apply_filters('eh_assessment_cekat_submission_saved_webhook_headers', $headers, $body, $masked_id);

    $args = [
        'method' => 'POST',
        'timeout' => $blocking ? 15 : 8,
        'blocking' => $blocking,
        'headers' => $headers,
        'body' => wp_json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
    $args = apply_filters('eh_assessment_cekat_submission_saved_webhook_request_args', $args, $body, $masked_id);

    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return $response;
    }

    if ($blocking) {
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('webhook_failed', 'Webhook returned HTTP ' . $code . '.', ['status' => 502]);
        }
    }

    return true;
}

/**
 * $wpdb->insert format specifiers for {@see eh_assessment_table_name()} full rows (cekat columns included).
 *
 * @return list<string>
 */
function eh_assessment_submission_row_format_types(): array
{
    return array_merge(
        ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s'],
        array_fill(0, 9, '%s'),
        ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d'],
        array_fill(0, 11, '%s'),
        ['%s', '%s', '%s']
    );
}

function eh_assessment_sanitize_payload(array $payload): array
{
    $submission = is_array($payload['submission'] ?? null) ? $payload['submission'] : [];
    $respondent = is_array($payload['respondent'] ?? null) ? $payload['respondent'] : [];
    $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
    $label_map = eh_assessment_question_label_map();

    $normalized_answers = [];
    foreach (eh_assessment_question_key_map() as $key) {
        $entry = is_array($answers[$key] ?? null) ? $answers[$key] : [];
        $q = sanitize_text_field((string) ($entry['question'] ?? ''));
        $a = sanitize_text_field((string) ($entry['answer'] ?? ''));
        if (trim($q) === '' && isset($label_map[$key])) {
            $q = sanitize_text_field((string) $label_map[$key]);
        }
        if (strlen($q) > EH_ASSESSMENT_MAX_QUESTION_TEXT_LENGTH) {
            $q = (string) substr($q, 0, EH_ASSESSMENT_MAX_QUESTION_TEXT_LENGTH);
        }
        if (strlen($a) > EH_ASSESSMENT_MAX_ANSWER_TEXT_LENGTH) {
            $a = (string) substr($a, 0, EH_ASSESSMENT_MAX_ANSWER_TEXT_LENGTH);
        }
        $normalized_answers[$key] = [
            'question' => $q,
            'answer' => $a,
        ];
    }

    $masking_raw = '';
    if (isset($submission['branch_outlet_masking_id'])) {
        $masking_raw = (string) $submission['branch_outlet_masking_id'];
    } elseif (isset($submission['branch_outlet_id'])) {
        $legacy_id = (int) $submission['branch_outlet_id'];
        if ($legacy_id > 0) {
            global $wpdb;
            $branch_tbl = eh_branch_outlet_table_name();
            $masking_raw = (string) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT cekat_masking_id FROM {$branch_tbl} WHERE id = %d AND deleted_at IS NULL LIMIT 1",
                    $legacy_id
                )
            );
        }
    }
    $branch_outlet_masking_id = eh_assessment_normalize_submission_branch_masking_id($masking_raw);

    $agent_masking_raw = (string) ($submission['agent_masking_id'] ?? '');
    $agent_masking_id = eh_assessment_canonical_agent_masking_id_for_payload($agent_masking_raw);

    $source_slug = '';
    if (isset($submission['source_page_slug']) && trim((string) $submission['source_page_slug']) !== '') {
        $source_slug = eh_assessment_normalize_submission_source_page_slug((string) $submission['source_page_slug']);
    } elseif (isset($submission['source_page_id']) && (int) $submission['source_page_id'] > 0) {
        $legacy_post = get_post((int) $submission['source_page_id']);
        if ($legacy_post instanceof WP_Post && $legacy_post->post_type === 'page' && (string) $legacy_post->post_name !== '') {
            $source_slug = eh_assessment_normalize_submission_source_page_slug((string) $legacy_post->post_name);
        }
    }
    $source_page_id = $source_slug !== '' ? eh_assessment_resolve_source_page_id_from_slug($source_slug) : 0;

    $report_type_in = null;
    if (isset($submission['report_type'])) {
        $rt = (int) $submission['report_type'];
        $report_type_in = $rt >= 1 && $rt <= 99 ? $rt : null;
    }

    $name = sanitize_text_field((string) ($respondent['name'] ?? ''));
    if (strlen($name) > EH_ASSESSMENT_MAX_NAME_LENGTH) {
        $name = (string) substr($name, 0, EH_ASSESSMENT_MAX_NAME_LENGTH);
    }

    $submission_out = [
        'source_page_slug' => $source_slug,
        'source_page_id' => $source_page_id,
        'branch_outlet_masking_id' => $branch_outlet_masking_id,
        'report_type' => eh_assessment_resolve_submission_report_type($report_type_in),
    ];
    if ($agent_masking_id !== '') {
        $submission_out['agent_masking_id'] = $agent_masking_id;
    }

    $submission_out['lead_source'] = eh_assessment_lead_source_from_submission($submission);

    return [
        'submission' => $submission_out,
        'respondent' => [
            'name' => $name,
            'whatsapp' => eh_assessment_normalize_whatsapp((string) ($respondent['whatsapp'] ?? '')),
            'gender' => eh_assessment_normalize_respondent_gender_string((string) ($respondent['gender'] ?? '')),
            'birthdate' => sanitize_text_field((string) ($respondent['birthdate'] ?? '')),
            'consent' => !empty($respondent['consent']),
        ],
        'answers' => $normalized_answers,
    ];
}

/**
 * Cekat “submission saved” workflow URL (wp-config.php).
 * define( 'WEBHOOK_CEKAT_URL', 'https://…' );
 *
 * Fallback: {@see EH_ASSESSMENT_CEKAT_SUBMISSION_WEBHOOK_URL}, then {@see EH_ASSESSMENT_CEKAT_SUBMISSION_SAVED_WEBHOOK_DEFAULT}.
 */
function eh_assessment_cekat_submission_saved_webhook_url(): string
{
    if (defined('WEBHOOK_CEKAT_URL')) {
        $u = trim((string) constant('WEBHOOK_CEKAT_URL'));
        if ($u !== '') {
            return $u;
        }
    }

    if (defined('WEBHOOK_TO_CEKAT_URL')) {
        $u = trim((string) constant('WEBHOOK_TO_CEKAT_URL'));
        if ($u !== '') {
            return $u;
        }
    }

    if (defined('EH_ASSESSMENT_CEKAT_SUBMISSION_WEBHOOK_URL')) {
        $u = trim((string) constant('EH_ASSESSMENT_CEKAT_SUBMISSION_WEBHOOK_URL'));
        if ($u !== '') {
            return $u;
        }
    }

    return EH_ASSESSMENT_CEKAT_SUBMISSION_SAVED_WEBHOOK_DEFAULT;
}

/**
 * Auth token for the Cekat submission webhook (wp-config.php).
 * define( 'WEBHOOK_CEKAT_KEY', '…' );
 */
function eh_assessment_cekat_submission_saved_webhook_key(): string
{
    if (defined('WEBHOOK_CEKAT_KEY')) {
        $key = trim((string) constant('WEBHOOK_CEKAT_KEY'));
        if ($key !== '') {
            return $key;
        }
    }

    if (!defined('WEBHOOK_TO_CEKAT_KEY')) {
        return '';
    }

    return trim((string) constant('WEBHOOK_TO_CEKAT_KEY'));
}

/**
 * Write optional Cekat webhook diagnostics to PHP error log (typically wp-content/debug.log).
 *
 * Enabled when {@see WP_DEBUG} and {@see WP_DEBUG_LOG} are true, or when the filter
 * {@see 'eh_assessment_cekat_webhook_debug_log'} returns true.
 */
function eh_assessment_cekat_webhook_debug_logging_enabled(): bool
{
    return (bool) apply_filters(
        'eh_assessment_cekat_webhook_debug_log',
        defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG
    );
}

function eh_assessment_cekat_webhook_debug_log(string $message): void
{
    if (!eh_assessment_cekat_webhook_debug_logging_enabled()) {
        return;
    }

    error_log('[eurohairlab-assessment][cekat-webhook] ' . $message);
}

/**
 * Notify Cekat workflow after {@see eh_assessment_insert_submission()} persists a row.
 * Skips when {@see WEBHOOK_CEKAT_KEY} / {@see WEBHOOK_TO_CEKAT_KEY} is not defined or empty. Uses non-blocking HTTP.
 *
 * JSON body shape: `submission` (branch_office_name = branch display label, lead_source, report_id, clinical_profile,
 * score, band, patient_type, strategy, report_pdf_url, agent_id), `respondent`, `answers`.
 * `submission.agent_id` is always a string (possibly empty). After
 * {@see 'eh_assessment_cekat_submission_saved_webhook_body'}, `agent_id` is re-applied if missing.
 * `submission.branch_office_name` is always the branch display label (display_name when set, else Cekat inbox name),
 * even if a filter merges other `submission` keys.
 *
 * @param array<string, mixed> $sanitized Output of {@see eh_assessment_sanitize_payload()} (must include `computed` from {@see eh_assessment_attach_computed_to_sanitized()}).
 */
function eh_assessment_cekat_submission_saved_webhook_body(
    array $sanitized,
    string $masked_id,
    int $branch_outlet_id,
    string $agent_masking_id,
    int $submission_id,
    ?string $lead_source_override = null
): array {
    global $wpdb;

    $branch_tbl = eh_branch_outlet_table_name();
    $branch_office_name = '';
    $resolved_branch_id = eh_assessment_branch_outlet_id_for_webhook_body($sanitized, $branch_outlet_id);
    if ($resolved_branch_id > 0) {
        $br = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT cekat_name, display_name FROM {$branch_tbl} WHERE id = %d AND deleted_at IS NULL LIMIT 1",
                $resolved_branch_id
            ),
            ARRAY_A
        );
        if (is_array($br)) {
            $branch_office_name = eh_assessment_branch_outlet_display_label($br);
        }
    }

    $agent_from_payload = '';
    if (isset($sanitized['submission']) && is_array($sanitized['submission']) && isset($sanitized['submission']['agent_masking_id'])) {
        $agent_from_payload = trim((string) $sanitized['submission']['agent_masking_id']);
    }
    $agent_id_cekat = trim($agent_masking_id);
    if ($agent_id_cekat === '' && $agent_from_payload !== '') {
        $agent_id_cekat = $agent_from_payload;
    }

    $comp = is_array($sanitized['computed'] ?? null) ? $sanitized['computed'] : [];
    $submission_in = is_array($sanitized['submission'] ?? null) ? $sanitized['submission'] : [];
    $lead_source = $lead_source_override !== null && trim($lead_source_override) !== ''
        ? eh_assessment_normalize_lead_source($lead_source_override)
        : eh_assessment_lead_source_from_submission($submission_in);
    $scoreNum = (int) ($comp['computed_score'] ?? 0);
    $score_str = $scoreNum > 0 ? (string) $scoreNum . '/100' : '';

    $gender = (string) ($sanitized['respondent']['gender'] ?? '');
    $raw_name = trim((string) ($sanitized['respondent']['name'] ?? ''));
    $salutation = function_exists('eh_assessment_salutation_id') ? eh_assessment_salutation_id($gender) : '';
    $respondent_display_name = $raw_name !== '' && $salutation !== ''
        ? trim($salutation . ' ' . $raw_name)
        : $raw_name;

    $submission_block = [
        'branch_office_name' => $branch_office_name,
        'lead_source' => $lead_source,
        'submission_id' => $masked_id,
        'report_id' => $masked_id,
        'clinical_profile' => (string) ($comp['computed_condition_title'] ?? ''),
        'score' => $score_str,
        'band' => (string) ($comp['computed_band'] ?? ''),
        'patient_type' => (int) ($comp['computed_patient_type'] ?? 0),
        'strategy' => (string) ($comp['computed_communication_strategy'] ?? ''),
        'report_pdf_url' => eh_assessment_get_public_report_download_url_for_json($submission_id, $masked_id),
        'agent_id' => $agent_id_cekat,
    ];

    $birth = isset($sanitized['respondent']['birthdate']) ? trim((string) $sanitized['respondent']['birthdate']) : '';
    $respondent_block = [
        'name' => $respondent_display_name,
        'whatsapp' => (string) ($sanitized['respondent']['whatsapp'] ?? ''),
        'gender' => $gender,
        'birthdate' => $birth,
        'consent' => !empty($sanitized['respondent']['consent']),
    ];

    $answers_block = is_array($sanitized['answers'] ?? null) ? $sanitized['answers'] : [];
    if ($answers_block !== []) {
        $answers_block = eh_assessment_cekat_webhook_localize_answers_id($answers_block);
    }

    return [
        'submission' => $submission_block,
        'respondent' => $respondent_block,
        'answers' => $answers_block,
    ];
}

function eh_assessment_fire_cekat_submission_saved_webhook(
    string $masked_id,
    int $branch_outlet_id,
    string $agent_masking_id,
    array $sanitized,
    int $submission_id
): void {
    $key = eh_assessment_cekat_submission_saved_webhook_key();
    if ($key === '') {
        eh_assessment_cekat_webhook_debug_log(
            'Skipped: WEBHOOK_CEKAT_KEY is not defined or empty (webhook never runs without this constant).'
        );

        return;
    }

    $url = eh_assessment_cekat_submission_saved_webhook_url();
    if ($url === '') {
        eh_assessment_cekat_webhook_debug_log('Skipped: webhook URL is empty after resolving WEBHOOK_CEKAT_URL / WEBHOOK_TO_CEKAT_URL / fallbacks.');

        return;
    }
    $default_body = eh_assessment_cekat_submission_saved_webhook_body(
        $sanitized,
        $masked_id,
        $branch_outlet_id,
        $agent_masking_id,
        $submission_id
    );
    $body = $default_body;

    /** @var array<string, mixed> $body */
    $body = apply_filters('eh_assessment_cekat_submission_saved_webhook_body', $body, $masked_id, $sanitized, $submission_id);
    if (!is_array($body)) {
        $body = $default_body;
    }
    if (!isset($body['submission']) || !is_array($body['submission'])) {
        $body['submission'] = $default_body['submission'];
    } else {
        $merged = array_merge($default_body['submission'], $body['submission']);
        $merged['agent_id'] = trim((string) ($merged['agent_id'] ?? ($default_body['submission']['agent_id'] ?? '')));
        $merged['branch_office_name'] = (string) ($default_body['submission']['branch_office_name'] ?? '');
        $body['submission'] = $merged;
    }
    if (!isset($body['respondent']) || !is_array($body['respondent'])) {
        $body['respondent'] = $default_body['respondent'];
    }
    if (!isset($body['answers']) || !is_array($body['answers'])) {
        $body['answers'] = $default_body['answers'];
    }

    $host = wp_parse_url($url, PHP_URL_HOST);
    $path = wp_parse_url($url, PHP_URL_PATH);
    eh_assessment_cekat_webhook_debug_log(
        sprintf(
            'Dispatching non-blocking POST (submission id=%s, host=%s, path=%s).',
            $masked_id,
            is_string($host) ? $host : '',
            is_string($path) ? $path : ''
        )
    );

    $response = eh_assessment_cekat_submission_saved_webhook_dispatch($body, $masked_id, false);
    if (is_wp_error($response)) {
        eh_assessment_cekat_webhook_debug_log('wp_remote_post returned error: ' . $response->get_error_message());
    }
}

function eh_assessment_insert_submission(array $payload)
{
    global $wpdb;

    $assessment_table = eh_assessment_table_name();

    eh_assessment_migrate_v179_drop_submission_deleted_at();
    eh_assessment_migrate_v181_branch_outlet_display_name();

    if (!eh_assessment_public_submission_honeypot_clean($payload)) {
        return new WP_Error('spam_detected', 'Submission rejected.', ['status' => 400]);
    }

    if (!eh_assessment_request_is_allowed_origin()) {
        return new WP_Error('invalid_origin', 'Invalid submission origin.', ['status' => 403]);
    }

    $sanitized = eh_assessment_sanitize_payload($payload);

    $branch_outlet_masking_id = eh_assessment_normalize_submission_branch_masking_id(
        (string) ($sanitized['submission']['branch_outlet_masking_id'] ?? '')
    );
    $branch_outlet_id = eh_assessment_resolve_branch_outlet_id_from_masking_id($branch_outlet_masking_id);
    if ($branch_outlet_masking_id !== '' && $branch_outlet_id <= 0) {
        return new WP_Error('invalid_branch_outlet', 'Invalid Branch Office.', ['status' => 400]);
    }

    $branch_table = eh_branch_outlet_table_name();
    $active_branch_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$branch_table} WHERE deleted_at IS NULL");
    if ($active_branch_count > 0 && $branch_outlet_id <= 0) {
        return new WP_Error('invalid_submission', 'Branch Office is required.', ['status' => 400]);
    }

    $birthdateRaw = trim((string) ($sanitized['respondent']['birthdate'] ?? ''));
    if ($birthdateRaw === '') {
        $birthdateRaw = eh_assessment_default_placeholder_birthdate();
    }
    $birthdate = eh_assessment_normalize_submission_birthdate($birthdateRaw);
    if ($birthdate === null) {
        return new WP_Error('invalid_birthdate', 'Birth date must be a valid past date (YYYY-MM-DD).', ['status' => 400]);
    }

    $sanitized['respondent']['birthdate'] = $birthdate;
    $sanitized['submission']['branch_outlet_masking_id'] = $branch_outlet_masking_id;

    $agent_mid = (string) ($sanitized['submission']['agent_masking_id'] ?? '');
    $agent_name = $agent_mid !== '' ? eh_assessment_resolve_submission_agent_name_from_input($agent_mid) : null;

    $source_slug_chk = trim((string) ($sanitized['submission']['source_page_slug'] ?? ''));
    $source_page_resolved = (int) ($sanitized['submission']['source_page_id'] ?? 0);
    if ($source_slug_chk !== '' && $source_page_resolved <= 0) {
        return new WP_Error('invalid_source_page', 'Invalid source page slug.', ['status' => 400]);
    }

    $payload_validation = eh_assessment_validate_sanitized_submission_payload($sanitized);
    if (is_wp_error($payload_validation)) {
        return $payload_validation;
    }

    $rate_ok = eh_assessment_rate_limit_successful_submissions();
    if (is_wp_error($rate_ok)) {
        return $rate_ok;
    }

    $cdb = eh_assessment_attach_computed_to_sanitized($sanitized);
    $report_type_row = (int) ($sanitized['submission']['report_type'] ?? eh_assessment_resolve_submission_report_type(null));

    $row = [
        'masked_id' => eh_assessment_generate_submission_masked_id($report_type_row, $assessment_table),
        'source_page_id' => $source_page_resolved > 0 ? $source_page_resolved : null,
        'status' => eh_assessment_normalize_status('On Progress'),
        'respondent_name' => $sanitized['respondent']['name'],
        'respondent_whatsapp' => $sanitized['respondent']['whatsapp'],
        'respondent_gender' => $sanitized['respondent']['gender'],
        'respondent_birthdate' => $birthdate,
        'branch_outlet_id' => $branch_outlet_id > 0 ? $branch_outlet_id : null,
        'agent_name' => $agent_name,
        'consent' => $sanitized['respondent']['consent'] ? 1 : 0,
        'lead_source' => $cdb['lead_source'],
        'q1_focus_area' => $sanitized['answers']['q1_focus_area']['answer'],
        'q2_main_impact' => $sanitized['answers']['q2_main_impact']['answer'],
        'q3_duration' => $sanitized['answers']['q3_duration']['answer'],
        'q4_family_history' => $sanitized['answers']['q4_family_history']['answer'],
        'q5_previous_attempts' => $sanitized['answers']['q5_previous_attempts']['answer'],
        'q6_trigger_factors' => $sanitized['answers']['q6_trigger_factors']['answer'],
        'q7_biggest_worry' => $sanitized['answers']['q7_biggest_worry']['answer'],
        'q8_previous_consultation' => $sanitized['answers']['q8_previous_consultation']['answer'],
        'q9_expected_result' => $sanitized['answers']['q9_expected_result']['answer'],
    ];
    $row = array_merge($row, $cdb['metrics']);
    $tail = [
        'cekat_masking_id' => null,
        'cekat_created_at' => null,
        'cekat_business_id' => null,
        'cekat_name' => null,
        'cekat_description' => null,
        'cekat_phone_number' => null,
        'cekat_status' => null,
        'cekat_ai_agent_id' => null,
        'cekat_image_url' => null,
        'cekat_type' => null,
        'cekat_ai_agent_json' => null,
        'payload_json' => '',
        'submitted_at' => eh_assessment_current_mysql_time(),
        'updated_at' => eh_assessment_current_mysql_time(),
    ];
    $row = array_merge($row, $tail);

    $row['payload_json'] = eh_assessment_payload_json_for_storage($sanitized);

    $formats = eh_assessment_submission_row_format_types();
    $inserted = 0;
    $lastDbErr = '';
    for ($attempt = 0; $attempt < 12; $attempt++) {
        if ($attempt > 0) {
            $row['masked_id'] = eh_assessment_generate_submission_masked_id($report_type_row, $assessment_table);
        }
        $inserted = $wpdb->insert($assessment_table, $row, $formats);
        if ($inserted !== false && (int) $inserted > 0) {
            break;
        }
        $lastDbErr = trim((string) $wpdb->last_error);
        if (!eh_assessment_db_error_is_duplicate_masked_id($lastDbErr)) {
            break;
        }
    }
    if ($inserted === false || (int) $inserted < 1) {
        $dbErr = $lastDbErr !== '' ? $lastDbErr : trim((string) $wpdb->last_error);

        return new WP_Error(
            'insert_failed',
            'Failed to store assessment submission.',
            [
                'status' => 500,
                'db_error' => $dbErr !== '' ? $dbErr : null,
            ]
        );
    }

    eh_assessment_rate_limit_record_success();

    $newSubmissionId = (int) $wpdb->insert_id;

    // Notify admin first: must not depend on Cekat webhook (network/errors/filters on webhook must not block email).
    eh_assessment_send_new_lead_admin_notification(
        (string) $row['masked_id'],
        $branch_outlet_id,
        $agent_mid,
        $sanitized,
        $newSubmissionId
    );

    try {
        eh_assessment_fire_cekat_submission_saved_webhook(
            (string) $row['masked_id'],
            $branch_outlet_id,
            $agent_mid,
            $sanitized,
            $newSubmissionId
        );
    } catch (Throwable $e) {
        error_log('[eurohairlab-assessment][cekat-webhook] Submission saved webhook aborted: ' . $e->getMessage());
    }

    return [
        'id' => $newSubmissionId,
        'masked_id' => $row['masked_id'],
        'status' => $row['status'],
        'branch_outlet_masking_id' => $branch_outlet_masking_id !== '' ? $branch_outlet_masking_id : null,
        'respondent_birthdate' => $birthdate,
    ];
}

function eh_assessment_register_rest_routes(): void
{
    register_rest_route('eurohairlab/v1', '/assessment-submissions', [
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $request) {
            $raw = $request->get_body();
            if (strlen($raw) > EH_ASSESSMENT_MAX_JSON_BODY_BYTES) {
                return new WP_Error('payload_too_large', 'Request body is too large.', ['status' => 413]);
            }

            $payload = $request->get_json_params();
            if (!is_array($payload)) {
                return new WP_Error('invalid_payload', 'Invalid JSON payload.', ['status' => 400]);
            }

            return eh_assessment_insert_submission($payload);
        },
    ]);

    register_rest_route('eurohairlab/v1', '/assessment-submissions/complete', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'args' => [
            'submission_id' => [
                'required' => false,
                'description' => 'Submission id (masked id), e.g. R05-0326-001 or legacy ASM-…. JSON body or application/x-www-form-urlencoded.',
                'type' => 'string',
                'sanitize_callback' => static function ($value): string {
                    return sanitize_text_field((string) $value);
                },
            ],
        ],
        'callback' => 'eh_assessment_rest_webhook_complete_submission',
    ]);

    register_rest_route('eurohairlab/v1', '/cekat-inboxes', [
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => static function (): bool {
            return eh_assessment_current_user_can_access_admin();
        },
        'callback' => static function (): WP_REST_Response|WP_Error {
            $inboxes = eh_assessment_fetch_cekat_inboxes_from_api();
            if (is_wp_error($inboxes)) {
                return $inboxes;
            }

            return new WP_REST_Response(['data' => $inboxes], 200);
        },
    ]);

    register_rest_route('eurohairlab/v1', '/cekat-agents', [
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => static function (): bool {
            return eh_assessment_user_is_administrator();
        },
        'args' => [
            'limit' => [
                'description' => 'Upstream Cekat /api/agents limit.',
                'type' => 'integer',
                'default' => 9999,
                'minimum' => 1,
                'maximum' => 10000,
            ],
            'page' => [
                'description' => 'Upstream Cekat /api/agents page.',
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
            ],
        ],
        'callback' => static function (WP_REST_Request $request): WP_REST_Response|WP_Error {
            $limit = (int) $request->get_param('limit');
            $page = (int) $request->get_param('page');
            if ($limit < 1) {
                $limit = 9999;
            }
            if ($limit > 10000) {
                $limit = 10000;
            }
            if ($page < 1) {
                $page = 1;
            }

            $agents = eh_assessment_fetch_cekat_agents_from_api($limit, $page);
            if (is_wp_error($agents)) {
                return $agents;
            }

            return new WP_REST_Response(['data' => $agents], 200);
        },
    ]);

    register_rest_route('eurohairlab/v1', '/cekat-templates', [
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => static function (): bool {
            return eh_assessment_current_user_can_access_admin();
        },
        'args' => [
            'inbox_masking_id' => [
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => static function ($v): string {
                    return sanitize_text_field((string) $v);
                },
            ],
        ],
        'callback' => static function (WP_REST_Request $request): WP_REST_Response|WP_Error {
            $all = eh_assessment_fetch_cekat_templates_from_api();
            if (is_wp_error($all)) {
                return $all;
            }

            $inbox = trim((string) $request->get_param('inbox_masking_id'));
            if ($inbox === '') {
                return new WP_REST_Response(['data' => []], 200);
            }

            $filtered = eh_assessment_cekat_filter_templates_by_inbox_masking($all, $inbox);

            return new WP_REST_Response(['data' => $filtered], 200);
        },
    ]);
}
add_action('rest_api_init', 'eh_assessment_register_rest_routes');

function eh_assessment_enqueue_submissions_admin_scripts(string $hook_suffix): void
{
    if (strpos($hook_suffix, 'eh-assessment-submissions') === false) {
        return;
    }

    if (!eh_assessment_current_user_can_access_admin()) {
        return;
    }

    // Submissions list: no admin JS (manual “Add submission” flow removed).
}
add_action('admin_enqueue_scripts', 'eh_assessment_enqueue_submissions_admin_scripts');

function eh_assessment_enqueue_branch_outlet_admin_scripts(string $hook_suffix): void
{
    if (strpos($hook_suffix, 'eh-assessment-branch-outlet') === false) {
        return;
    }

    if (!eh_assessment_current_user_can_access_admin()) {
        return;
    }

    wp_enqueue_script(
        'eh-branch-outlet-admin',
        plugins_url('assets/branch-outlet-admin.js', __FILE__),
        [],
        EH_ASSESSMENT_DATA_VERSION,
        true
    );
    wp_localize_script(
        'eh-branch-outlet-admin',
        'ehBranchOutletAdmin',
        [
            'restUrl' => esc_url_raw(rest_url('eurohairlab/v1/cekat-inboxes')),
            'templatesRestUrl' => esc_url_raw(rest_url('eurohairlab/v1/cekat-templates')),
            'nonce' => wp_create_nonce('wp_rest'),
            'strSelectPlaceholder' => 'Select Cekat inbox…',
            'strTemplatePlaceholder' => '— No template —',
            'strTemplatePickInbox' => '— Select inbox first —',
            'strTemplateLoading' => 'Loading templates…',
            'strTemplateLoadError' => 'Could not load templates from Cekat.',
            'strTemplateCategory' => 'Category',
            'strTemplateHeader' => 'Header',
            'strTemplateBody' => 'Body',
            'strTemplateButtons' => 'Buttons',
            'strTemplatePreviewNone' => 'No template linked, or preview could not be loaded.',
            'strTemplatePreviewLeadCustomer' => 'Template message to customer (from Cekat)',
            'strTemplatePreviewNoneCustomer' => 'No template linked for customer, or preview could not be loaded.',
        ]
    );
}
add_action('admin_enqueue_scripts', 'eh_assessment_enqueue_branch_outlet_admin_scripts');

function eh_assessment_enqueue_hair_specialist_agent_admin_scripts(string $hook_suffix): void
{
    if (strpos($hook_suffix, 'eh-hair-specialist-agents') === false) {
        return;
    }

    if (!eh_assessment_user_is_administrator()) {
        return;
    }

    wp_enqueue_script(
        'eh-hair-specialist-agent-admin',
        plugins_url('assets/hair-specialist-agent-admin.js', __FILE__),
        [],
        EH_ASSESSMENT_DATA_VERSION,
        true
    );
    wp_localize_script(
        'eh-hair-specialist-agent-admin',
        'ehHairSpecialistAgentAdmin',
        [
            'restUrl' => esc_url_raw(
                add_query_arg(
                    [
                        'limit' => 9999,
                        'page' => 1,
                    ],
                    rest_url('eurohairlab/v1/cekat-agents')
                )
            ),
            'nonce' => wp_create_nonce('wp_rest'),
            'agentCodeMaxLen' => EH_ASSESSMENT_AGENT_CODE_MAX_LENGTH,
            'strAgentCodeEmpty' => 'Agent code is required.',
            'strAgentCodeInvalid' => 'Agent code may only contain letters, numbers, underscores, and hyphens (no spaces or other characters). Maximum 64 characters. It is used in the assessment URL.',
        ]
    );
}
add_action('admin_enqueue_scripts', 'eh_assessment_enqueue_hair_specialist_agent_admin_scripts');

function eh_assessment_enqueue_report_pdf_templates_admin_scripts(string $hook_suffix): void
{
    if (strpos($hook_suffix, 'eh-assessment-report-pdf-templates') === false) {
        return;
    }

    if (!eh_assessment_current_user_can_access_admin()) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_editor();

    wp_enqueue_script(
        'eh-report-pdf-templates-admin',
        plugins_url('assets/report-pdf-templates-admin.js', __FILE__),
        ['jquery', 'media-views', 'editor'],
        filemtime(__DIR__ . '/assets/report-pdf-templates-admin.js'),
        true
    );

    $wysiwyg_field_ids = [];
    foreach (eh_assessment_report_pdf_template_flat_fields() as $f) {
        if (($f[2] ?? '') === 'wysiwyg') {
            $wysiwyg_field_ids[] = (string) ($f[0] ?? '');
        }
    }

    wp_localize_script(
        'eh-report-pdf-templates-admin',
        'ehReportPdfTemplatesAdmin',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eh_rpt_tpl_admin'),
            'strAddTitle' => 'New template',
            'strEditTitle' => 'Edit template',
            'strLoadError' => 'Could not load this template.',
            'wysiwygFieldIds' => $wysiwyg_field_ids,
        ]
    );
}
add_action('admin_enqueue_scripts', 'eh_assessment_enqueue_report_pdf_templates_admin_scripts');

function eh_assessment_ajax_get_report_pdf_template(): void
{
    if (!check_ajax_referer('eh_rpt_tpl_admin', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token.'], 403);
    }

    if (!eh_assessment_current_user_can_access_admin()) {
        wp_send_json_error(['message' => 'Forbidden.'], 403);
    }

    $id = isset($_POST['id']) ? (int) wp_unslash($_POST['id']) : 0;
    $row = eh_assessment_report_pdf_template_get_row($id);
    if ($row === []) {
        wp_send_json_error(['message' => 'Not found.'], 404);
    }

    wp_send_json_success(['row' => $row]);
}
add_action('wp_ajax_eh_assessment_get_report_pdf_template', 'eh_assessment_ajax_get_report_pdf_template');

function eh_assessment_get_submission_detail_row(int $submission_id): ?array
{
    if ($submission_id <= 0) {
        return null;
    }

    global $wpdb;

    $assessment_table = eh_assessment_table_name();
    $branch_table = eh_branch_outlet_table_name();

    $branchLabel = eh_assessment_branch_outlet_label_sql('bo');
    $submission = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT s.*, {$branchLabel} AS branch_outlet_name
             FROM {$assessment_table} s
             LEFT JOIN {$branch_table} bo ON bo.id = s.branch_outlet_id AND bo.deleted_at IS NULL
             WHERE s.id = %d
             LIMIT 1",
            $submission_id
        ),
        ARRAY_A
    );

    return is_array($submission) ? $submission : null;
}

/**
 * Load submission row for public PDF download without JOIN (survives missing branch table after partial DB import).
 * Fills {@see eh_assessment_build_report_data()}’s `branch_outlet_name` when branch row exists.
 */
function eh_assessment_get_submission_for_public_report_download(int $submission_id): ?array
{
    if ($submission_id <= 0) {
        return null;
    }

    global $wpdb;

    $assessment_table = eh_assessment_table_name();
    $submission = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$assessment_table} WHERE id = %d LIMIT 1",
            $submission_id
        ),
        ARRAY_A
    );

    if (!is_array($submission)) {
        return null;
    }

    $submission['branch_outlet_name'] = '';
    $branch_id = (int) ($submission['branch_outlet_id'] ?? 0);
    if ($branch_id <= 0) {
        return $submission;
    }

    $branch_table = eh_branch_outlet_table_name();
    $suppress = $wpdb->suppress_errors(true);
    $br = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT cekat_name, display_name FROM {$branch_table} WHERE id = %d AND deleted_at IS NULL LIMIT 1",
            $branch_id
        ),
        ARRAY_A
    );
    $wpdb->suppress_errors($suppress);

    if (is_array($br)) {
        $submission['branch_outlet_name'] = eh_assessment_branch_outlet_display_label($br);
    }

    return $submission;
}

/**
 * Resolve a submission row for public PDF download by globally unique `masked_id` column.
 */
function eh_assessment_get_submission_for_public_report_download_by_masked_id(string $masked_id): ?array
{
    global $wpdb;

    $canonical = eh_assessment_normalize_masked_id_for_download_compare($masked_id);
    if ($canonical === '') {
        return null;
    }

    $assessment_table = eh_assessment_table_name();
    $submission = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$assessment_table} WHERE masked_id = %s LIMIT 1",
            $canonical
        ),
        ARRAY_A
    );

    if (!is_array($submission) || !isset($submission['id'])) {
        $trimmed = trim($masked_id);
        if ($trimmed !== '' && $trimmed !== $canonical) {
            $submission = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$assessment_table} WHERE masked_id = %s LIMIT 1",
                    $trimmed
                ),
                ARRAY_A
            );
        }
    }

    if (!is_array($submission) || !isset($submission['id'])) {
        return null;
    }

    $rowMaskNorm = eh_assessment_normalize_masked_id_for_download_compare((string) ($submission['masked_id'] ?? ''));
    if ($rowMaskNorm === '' || !hash_equals($rowMaskNorm, $canonical)) {
        return null;
    }

    $submission['branch_outlet_name'] = '';
    $branch_id = (int) ($submission['branch_outlet_id'] ?? 0);
    if ($branch_id <= 0) {
        return $submission;
    }

    $branch_table = eh_branch_outlet_table_name();
    $suppress = $wpdb->suppress_errors(true);
    $br = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT cekat_name, display_name FROM {$branch_table} WHERE id = %d AND deleted_at IS NULL LIMIT 1",
            $branch_id
        ),
        ARRAY_A
    );
    $wpdb->suppress_errors($suppress);

    if (is_array($br)) {
        $submission['branch_outlet_name'] = eh_assessment_branch_outlet_display_label($br);
    }

    return $submission;
}

function eh_assessment_get_report_download_url(int $submission_id): string
{
    $url = add_query_arg(
        [
            'action' => 'eh_download_assessment_report',
            'submission_id' => $submission_id,
        ],
        admin_url('admin-post.php')
    );

    return wp_nonce_url($url, 'eh_download_assessment_report_' . $submission_id);
}

function eh_assessment_public_report_download_signature(string $masked_id, int $submission_id): string
{
    $masked_id = trim($masked_id);
    if ($masked_id === '' || $submission_id <= 0) {
        return '';
    }

    return hash_hmac('sha256', $submission_id . '|' . $masked_id, wp_salt('auth'));
}

/**
 * Public PDF link signature keyed only by report `masked_id` (stable across DB dumps / AUTO_INCREMENT).
 */
function eh_assessment_public_report_download_signature_v2(string $masked_id): string
{
    $canonical = eh_assessment_normalize_masked_id_for_download_compare($masked_id);
    if ($canonical === '') {
        return '';
    }

    return hash_hmac('sha256', 'v2|' . $canonical, wp_salt('auth'));
}

/**
 * @param int $submission_id Kept for backward compatibility with callers; not embedded in v2 public URLs.
 */
function eh_assessment_get_public_report_download_url(int $submission_id, string $masked_id): string
{
    $masked_id = trim($masked_id);
    if ($masked_id === '') {
        return '';
    }

    $canonical = eh_assessment_normalize_masked_id_for_download_compare($masked_id);
    if ($canonical === '') {
        return '';
    }

    return add_query_arg(
        [
            'action' => 'eh_download_assessment_report_public',
            'masked_id' => $canonical,
            'sig' => eh_assessment_public_report_download_signature_v2($masked_id),
        ],
        admin_url('admin-post.php')
    );
}

/**
 * Same report PDF URL as {@see eh_assessment_get_report_download_url()} but with plain `&` for JSON (no HTML entities).
 */
function eh_assessment_get_report_download_url_for_json(int $submission_id): string
{
    if ($submission_id <= 0) {
        return '';
    }

    $url = html_entity_decode(
        eh_assessment_get_report_download_url($submission_id),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    return $url;
}

function eh_assessment_get_public_report_download_url_for_json(int $submission_id, string $masked_id): string
{
    $url = eh_assessment_get_public_report_download_url($submission_id, $masked_id);
    if ($url === '') {
        return '';
    }

    return html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function eh_assessment_render_report_download_link(int $submission_id, string $class = 'button', string $label = 'Download Report PDF'): string
{
    return '<a class="' . esc_attr($class) . '" href="' . esc_url(eh_assessment_get_report_download_url($submission_id)) . '">' . esc_html($label) . '</a>';
}

/**
 * Plain-text quick view for consultants (EUROHAIRLAB [NEW LEAD] format).
 *
 * @param array<string, mixed> $submission Row from {@see eh_assessment_get_submission_detail_row()}.
 * @param array<string, mixed> $payload Decoded `payload_json`.
 */
function eh_assessment_submission_quick_view_text(array $submission, array $payload, int $submission_id): string
{
    $comp = is_array($payload['computed'] ?? null) ? $payload['computed'] : [];
    $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];

    $pick = static function (string $col, string $compKey, array $submission, array $comp): string {
        $v = trim((string) ($submission[$col] ?? ''));
        if ($v === '' && isset($comp[$compKey])) {
            $v = trim((string) $comp[$compKey]);
        }

        return $v;
    };

    /** User-facing answer text (payload first, then denormalized DB columns). */
    $qAnswerText = static function (string $answerKey, array $answers, array $submission): string {
        $block = is_array($answers[$answerKey] ?? null) ? $answers[$answerKey] : [];
        $a = trim((string) ($block['answer'] ?? ''));
        if ($a !== '') {
            return $a;
        }

        $fb = trim((string) ($submission[$answerKey] ?? ''));

        return $fb !== '' ? $fb : '-';
    };

    $gender = (string) ($submission['respondent_gender'] ?? '');
    $salutation = trim((string) ($comp['computed_salutation'] ?? ''));
    if ($salutation === '') {
        $salutation = eh_assessment_salutation_id($gender);
    }

    $name = trim((string) ($submission['respondent_name'] ?? ''));
    $wa = trim((string) ($submission['respondent_whatsapp'] ?? ''));
    $reportId = trim((string) ($submission['masked_id'] ?? ''));
    $condition = $pick('computed_condition_title', 'computed_condition_title', $submission, $comp);
    $score = (int) ($submission['computed_score'] ?? $comp['computed_score'] ?? 0);
    $scoreStr = $score > 0 ? (string) $score : '-';
    $band = $pick('computed_band', 'computed_band', $submission, $comp);
    $tipeNum = (int) ($submission['computed_patient_type'] ?? $comp['computed_patient_type'] ?? 0);
    $tipeStr = $tipeNum > 0 ? 'Tipe ' . $tipeNum : '-';
    $strategi = $pick('computed_communication_strategy', 'computed_communication_strategy', $submission, $comp);
    $source = trim((string) ($submission['lead_source'] ?? ''));
    if ($source === '' && isset($payload['submission']['lead_source'])) {
        $source = trim((string) $payload['submission']['lead_source']);
    }
    if ($source === '') {
        $source = eh_assessment_lead_source_from_submission(is_array($payload['submission'] ?? null) ? $payload['submission'] : []);
    }

    $peringatan = $pick('computed_clinical_warnings', 'computed_clinical_warnings', $submission, $comp);
    if ($peringatan === '') {
        $peringatan = 'Tidak ada peringatan';
    }

    $pdfUrl = $submission_id > 0 ? eh_assessment_get_report_download_url_for_json($submission_id) : '';

    $lines = [
        '[NEW LEAD]',
        '',
        '• Nama: ' . $salutation . ' ' . $name,
        '• WhatsApp: ' . $wa,
        '• ID Laporan: ' . $reportId,
        '• Profil Klinis: ' . ($condition !== '' ? $condition : '-') . '  |  Skor: ' . $scoreStr . '/100  |  Band: ' . ($band !== '' ? $band : '-'),
        '• Tipe Pasien: ' . $tipeStr . '  |  Strategi: ' . ($strategi !== '' ? $strategi : '-'),
        '• Sumber: ' . $source,
        '',
        'DATA KLINIS:',
        '  Q1 (Area perubahan): ' . $qAnswerText('q1_focus_area', $answers, $submission),
        '  Q3 (Durasi): ' . $qAnswerText('q3_duration', $answers, $submission),
        '  Q4 (Riwayat genetik): ' . $qAnswerText('q4_family_history', $answers, $submission),
        '  Q6 (Faktor pemicu): ' . $qAnswerText('q6_trigger_factors', $answers, $submission),
        '  Q8 (Riwayat konsultasi): ' . $qAnswerText('q8_previous_consultation', $answers, $submission),
        '',
        'DATA PERILAKU:',
        '  Q2 (Dampak utama): ' . $qAnswerText('q2_main_impact', $answers, $submission),
        '  Q5 (Riwayat perawatan): ' . $qAnswerText('q5_previous_attempts', $answers, $submission),
        '  Q7 (Kekhawatiran terbesar): ' . $qAnswerText('q7_biggest_worry', $answers, $submission),
        '  Q9 (Harapan hasil): ' . $qAnswerText('q9_expected_result', $answers, $submission),
        '',
        'PERINGATAN KLINIS: ' . $peringatan,
        'PDF Laporan: ' . ($pdfUrl !== '' ? $pdfUrl : '-'),
        '',
        'SLA: Respons target 45 menit. Batas maksimum 1 jam.',
    ];

    return implode("\n", $lines);
}

/**
 * Diagnosis copy for the PDF "CLINICAL DIAGNOSIS" block ({@see report-preview.php}):
 * template `diagnosis_name` / `subtitle`, same template row resolution as {@see eh_assessment_build_report_data()}.
 *
 * @return array{title: string, subtitle: string}
 */
function eh_assessment_clinical_diagnosis_from_pdf_template(int $reportType): array
{
    if ($reportType < 1 || $reportType > 8) {
        $reportType = 1;
    }

    $tplRow = eh_assessment_report_pdf_template_get_first_matching_report_title_rpt($reportType);
    if ($tplRow === null) {
        $tplRow = eh_assessment_report_pdf_template_get_for_report_type($reportType);
    }
    if ($tplRow === null) {
        $tplRow = eh_assessment_report_pdf_template_get_first_placeholder_match($reportType);
    }

    if ($tplRow === null) {
        return ['title' => '', 'subtitle' => ''];
    }

    return [
        'title' => trim((string) ($tplRow['diagnosis_name'] ?? '')),
        'subtitle' => trim((string) ($tplRow['subtitle'] ?? '')),
    ];
}

function eh_assessment_build_report_data(array $submission): array
{
    $payload = json_decode((string) ($submission['payload_json'] ?? ''), true);
    $payload = is_array($payload) ? $payload : [];
    $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
    $comp = is_array($payload['computed'] ?? null) ? $payload['computed'] : [];
    $question_map = eh_assessment_question_key_map();

    $question_rows = [];
    $highlight_rows = [];

    foreach ($question_map as $number => $key) {
        $answer = is_array($answers[$key] ?? null) ? $answers[$key] : [];
        $question_rows[] = [
            'number' => $number,
            'key' => $key,
            'question' => (string) ($answer['question'] ?? ''),
            'answer' => (string) ($answer['answer'] ?? '-'),
        ];
    }

    foreach (['q1_focus_area', 'q2_main_impact', 'q7_biggest_worry'] as $key) {
        $answer = is_array($answers[$key] ?? null) ? $answers[$key] : [];
        $highlight_rows[] = [
            'key' => $key,
            'question' => (string) ($answer['question'] ?? ''),
            'answer' => (string) ($answer['answer'] ?? '-'),
        ];
    }

    $birthdate_sql = (string) ($submission['respondent_birthdate'] ?? '');
    $age_display = '42';
    if ($birthdate_sql !== '') {
        try {
            $bd = new DateTimeImmutable($birthdate_sql, eh_assessment_gmt7_timezone());
            $age_display = (string) $bd->diff(new DateTimeImmutable('now', eh_assessment_gmt7_timezone()))->y;
        } catch (Throwable) {
            $age_display = '42';
        }
    }

    $branch_label = trim((string) ($submission['branch_outlet_name'] ?? ''));
    if ($branch_label === '') {
        $branch_label = '-';
    }

    $submissionPayload = is_array($payload['submission'] ?? null) ? $payload['submission'] : [];
    $reportType = (int) ($submission['computed_report_type'] ?? 0);
    if ($reportType < 1 || $reportType > 8) {
        $reportType = (int) ($comp['computed_report_type'] ?? 0);
    }
    if ($reportType < 1 || $reportType > 8) {
        $reportType = (int) ($submissionPayload['report_type'] ?? 0);
    }
    if ($reportType < 1 || $reportType > 8) {
        $reportType = 1;
    }

    $score = (int) ($submission['computed_score'] ?? $comp['computed_score'] ?? 0);
    if ($score < 30 || $score > 100) {
        $score = 65;
    }

    $scalp = (int) ($submission['score_visual_scalp'] ?? $comp['score_visual_scalp'] ?? 5);
    $follicle = (int) ($submission['score_visual_follicle'] ?? $comp['score_visual_follicle'] ?? 6);
    $thinning = (int) ($submission['score_visual_thinning_risk'] ?? $comp['score_visual_thinning_risk'] ?? 4);

    $conditionTitle = (string) ($submission['computed_condition_title'] ?? $comp['computed_condition_title'] ?? '');
    if ($conditionTitle === '') {
        $conditionTitle = eh_assessment_condition_title_id($reportType);
    }

    $gender = (string) ($submission['respondent_gender'] ?? '');
    $salutation = (string) ($comp['computed_salutation'] ?? eh_assessment_salutation_id($gender));
    $bandStr = (string) ($submission['computed_band'] ?? $comp['computed_band'] ?? '');
    $answerLetters = is_array($comp['answer_letters'] ?? null) ? $comp['answer_letters'] : eh_assessment_extract_answer_letters($answers);
    $genderLower = strtolower(trim($gender));
    $isFemale = $genderLower === 'female';
    $report2ConditionText = $isFemale
        ? 'Rambut rontok genetik pada wanita biasanya ditandai dengan penipisan difus dan pelebaran belahan, mengikuti pola Ludwig/Savin.'
        : 'Rambut rontok genetik pada pria biasanya dimulai dari garis rambut dan area mahkota, berkembang bertahap mengikuti pola Norwood.';
    $report2UrgencyText = 'Evaluasi klinis disarankan untuk menentukan strategi terbaik sebelum kondisi bertambah parah.';
    $report2PsychologicalText = 'Bapak/Ibu menyebutkan kepercayaan diri sebagai dampak utama yang dirasakan.';

    $defaultBasis = [];
    $defaultClinicalCards = [
        [
            'title' => '',
            'items' => [],
            'accent' => 'white',
        ],
        [
            'title' => '',
            'items' => [],
            'accent' => 'white',
        ],
        [
            'title' => '',
            'items' => [],
            'accent' => 'gold',
        ],
    ];
    $defaultRiskDelayed = [];
    $defaultRiskUntreated = [];

    $tplRow = eh_assessment_report_pdf_template_get_first_matching_report_title_rpt($reportType);
    if ($tplRow === null) {
        $tplRow = eh_assessment_report_pdf_template_get_for_report_type($reportType);
    }
    if ($tplRow === null) {
        $tplRow = eh_assessment_report_pdf_template_get_first_placeholder_match($reportType);
    }

    $reportTitle = '';
    $diagTitle = '';
    $diagSubtitle = '';
    $diagBasis = $defaultBasis;
    $clinicalCards = $defaultClinicalCards;
    $riskDelayed = $defaultRiskDelayed;
    $riskUntreated = $defaultRiskUntreated;
    $treatments = [];
    $pdfTemplateMeta = [];

    if ($tplRow !== null) {
        $pdfTemplateMeta = [
            'id' => (int) ($tplRow['id'] ?? 0),
            'masking_id' => (string) ($tplRow['masking_id'] ?? ''),
            'image_clinical_knowledge' => trim((string) ($tplRow['image_clinical_knowledge'] ?? '')),
            'image_treatment_journey' => trim((string) ($tplRow['image_treatment_journey'] ?? '')),
        ];

        $rt = trim((string) ($tplRow['report_title'] ?? ''));
        if ($rt !== '') {
            $cleaned = trim(preg_replace('/\s+/', ' ', (string) preg_replace('/%\d{1,2}%/', '', $rt)) ?? '');
            $reportTitle = $cleaned !== '' ? $cleaned : $rt;
        }

        $dn = trim(wp_strip_all_tags((string) ($tplRow['diagnosis_name'] ?? '')));
        if ($dn !== '') {
            $diagTitle = $dn;
        }

        $dd = trim((string) ($tplRow['subtitle'] ?? ''));
        if ($dd !== '') {
            $diagSubtitle = $dd;
        }
    }

    $out = [
        'report_type' => $reportType,
        'template_name' => 'report_' . $reportType,
        'brand' => 'EUROHAIRLAB',
        'title' => $reportTitle,
        'subtitle' => 'Confidential Clinical Assessment',
        'submission' => [
            'id' => (int) ($submission['id'] ?? 0),
            'masked_id' => (string) ($submission['masked_id'] ?? ''),
            'status' => (string) ($submission['status'] ?? ''),
            'submitted_at' => (string) ($submission['submitted_at'] ?? ''),
            'updated_at' => (string) ($submission['updated_at'] ?? ''),
            'lead_source' => (string) ($submission['lead_source'] ?? ''),
        ],
        'patient' => [
            'name' => (string) ($submission['respondent_name'] ?? ''),
            'whatsapp' => (string) ($submission['respondent_whatsapp'] ?? ''),
            'gender' => $gender,
            'salutation' => $salutation,
            'birthdate' => $birthdate_sql,
            'age' => $age_display,
            'branch_office' => $branch_label,
        ],
        'computed' => [
            'band' => $bandStr,
            'maintenance_path' => (string) ($submission['computed_maintenance_path'] ?? $comp['computed_maintenance_path'] ?? ''),
            'patient_type' => (int) ($submission['computed_patient_type'] ?? $comp['computed_patient_type'] ?? 1),
            'communication_strategy' => (string) ($submission['computed_communication_strategy'] ?? $comp['computed_communication_strategy'] ?? ''),
            'clinical_warnings' => (string) ($submission['computed_clinical_warnings'] ?? $comp['computed_clinical_warnings'] ?? ''),
            'urgency_text' => (string) ($submission['computed_urgency_text'] ?? $comp['computed_urgency_text'] ?? ''),
            'genetic_clinical_text' => (string) ($submission['computed_genetic_clinical_text'] ?? $comp['computed_genetic_clinical_text'] ?? ''),
            'answer_letters' => is_array($comp['answer_letters'] ?? null) ? $comp['answer_letters'] : [],
        ],
        'scores' => [
            'overall' => $score,
            'scalp' => $scalp,
            'follicle' => $follicle,
            'thinning' => $thinning,
        ],
        'diagnosis' => [
            'level' => $score,
            'title' => $diagTitle,
            'subtitle' => $diagSubtitle,
            'basis' => $diagBasis,
        ],
        'report_2' => [
            'condition_explanation' => $report2ConditionText,
            'urgency_text' => $report2UrgencyText,
            'psychological_text' => $report2PsychologicalText,
        ],
        'highlights' => $highlight_rows,
        'questions' => $question_rows,
        'generated_at' => eh_assessment_current_mysql_time(),
        'clinical_cards' => $clinicalCards,
        'risks' => [
            'delayed' => $riskDelayed,
            'untreated' => $riskUntreated,
        ],
    ];

    if ($tplRow !== null) {
        $out['pdf_template'] = $pdfTemplateMeta;
        $out['pdf_template_row'] = $tplRow;
        $out['treatments'] = $treatments;
    }

    return $out;
}

function eh_assessment_format_indonesian_date(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }

    try {
        $date = new DateTimeImmutable($datetime, eh_assessment_gmt7_timezone());
    } catch (Throwable) {
        return $datetime;
    }

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $day = $date->format('j');
    $month = $months[(int) $date->format('n')] ?? $date->format('F');
    $year = $date->format('Y');

    return trim($day . ' ' . $month . ' ' . $year);
}

/** PDF output is built from {@see report-preview.php} (A4 portrait; dynamic HTML from submission data). */
final class EH_Assessment_Report_Pdf_Renderer
{
    public function render(array $report): string
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            throw new RuntimeException('Dompdf is not available on this server.');
        }

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        // Template treatment images may be full URLs (uploads/CDN); allow Dompdf to fetch them.
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $chroot = (defined('ABSPATH') && is_string(ABSPATH) && ABSPATH !== '') ? ABSPATH : __DIR__;
        $options->set('chroot', $chroot);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->addInfo('Title', (string) ($report['title'] ?? 'Hair Diagnostics Report'));

        $previous_display_errors = ini_get('display_errors');
        $previous_error_reporting = error_reporting();
        $previous_error_handler = set_error_handler(static function (): bool {
            return true;
        });
        ini_set('display_errors', '0');

        try {
            if (!defined('EH_ASSESSMENT_REPORT_PDF_RENDER')) {
                define('EH_ASSESSMENT_REPORT_PDF_RENDER', true);
            }

            ob_start();
            $submission_id = (int) (($report['submission']['id'] ?? 8));
            $submission = $report['submission'] ?? [];
            include __DIR__ . '/report-preview.php';
            $html = (string) ob_get_clean();
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();

            return (string) $dompdf->output();
        } finally {
            restore_error_handler();
            ini_set('display_errors', $previous_display_errors !== false ? (string) $previous_display_errors : '1');
            error_reporting($previous_error_reporting);
        }
    }
}

function eh_assessment_handle_admin_actions(): void
{
    if (!is_admin() || !eh_assessment_current_user_can_access_admin()) {
        return;
    }

    if (!isset($_POST['eh_assessment_action'])) {
        return;
    }

    $action = sanitize_key((string) $_POST['eh_assessment_action']);
    global $wpdb;
    if ($action === 'update_submission') {
        check_admin_referer('eh_update_submission');

        $assessment_table = eh_assessment_table_name();
        $submission_id = isset($_POST['submission_id']) ? (int) $_POST['submission_id'] : 0;
        $status = eh_assessment_normalize_status(sanitize_text_field((string) ($_POST['status'] ?? 'On Progress')));
        $branch_masking_in = isset($_POST['assessment_branch_outlet_masking_id']) ? wp_unslash((string) $_POST['assessment_branch_outlet_masking_id']) : '';
        $branch_masking = eh_assessment_normalize_submission_branch_masking_id($branch_masking_in);
        $branch_outlet_id = eh_assessment_resolve_branch_outlet_id_from_masking_id($branch_masking);
        if ($branch_masking !== '' && $branch_outlet_id <= 0) {
            $branch_masking = '';
            $branch_outlet_id = 0;
        }

        if ($submission_id > 0) {
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT payload_json, respondent_birthdate FROM {$assessment_table} WHERE id = %d LIMIT 1",
                    $submission_id
                ),
                ARRAY_A
            );
            $payload = is_array($existing) ? json_decode((string) ($existing['payload_json'] ?? ''), true) : null;
            $payload = is_array($payload) ? $payload : [];
            if (!isset($payload['respondent']) || !is_array($payload['respondent'])) {
                $payload['respondent'] = [];
            }
            if (!isset($payload['submission']) || !is_array($payload['submission'])) {
                $payload['submission'] = [];
            }
            $birthdate = null;
            if (is_array($existing)) {
                $birthdate = eh_assessment_normalize_submission_birthdate(trim((string) ($existing['respondent_birthdate'] ?? '')));
            }
            if ($birthdate === null) {
                $birthdate = eh_assessment_normalize_submission_birthdate(trim((string) ($payload['respondent']['birthdate'] ?? '')));
            }
            $payload['respondent']['birthdate'] = $birthdate ?? '';
            $payload['submission']['branch_outlet_masking_id'] = $branch_masking;
            unset($payload['submission']['branch_outlet_id'], $payload['submission']['source_page_id']);

            $ag_canon = eh_assessment_canonical_agent_masking_id_for_payload((string) ($payload['submission']['agent_masking_id'] ?? ''));
            if ($ag_canon !== '') {
                $payload['submission']['agent_masking_id'] = $ag_canon;
            } else {
                unset($payload['submission']['agent_masking_id']);
            }
            $agent_name_up = $ag_canon !== '' ? eh_assessment_resolve_submission_agent_name_from_input($ag_canon) : null;

            $wpdb->update(
                $assessment_table,
                [
                    'status' => $status,
                    'respondent_birthdate' => $birthdate,
                    'branch_outlet_id' => $branch_outlet_id > 0 ? $branch_outlet_id : null,
                    'agent_name' => $agent_name_up,
                    'payload_json' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => eh_assessment_current_mysql_time(),
                ],
                ['id' => $submission_id],
                ['%s', '%s', '%d', '%s', '%s', '%s'],
                ['%d']
            );
        }

        $redirect_url = add_query_arg(
            [
                'page' => 'eh-assessment-submissions',
                'view' => 'detail',
                'submission_id' => $submission_id,
                'eh_submission_id' => $submission_id,
                'updated' => '1',
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    if ($action === 'resend_submission_notification') {
        check_admin_referer('eh_resend_submission_notification');

        $assessment_table = eh_assessment_table_name();
        $submission_id = isset($_POST['submission_id']) ? (int) $_POST['submission_id'] : 0;
        $row = $submission_id > 0
            ? $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, masked_id, branch_outlet_id, payload_json, lead_source FROM {$assessment_table} WHERE id = %d LIMIT 1",
                    $submission_id
                ),
                ARRAY_A
            )
            : null;

        if (!is_array($row) || !isset($row['id'])) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'view' => 'detail',
                        'submission_id' => $submission_id,
                        'eh_submission_id' => $submission_id,
                        'resend_err' => 'not_found',
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $body = eh_assessment_cekat_submission_saved_webhook_body_from_submission_id($submission_id);
        if (is_wp_error($body)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'view' => 'detail',
                        'submission_id' => $submission_id,
                        'eh_submission_id' => $submission_id,
                        'resend_err' => $body->get_error_code(),
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $sent = eh_assessment_cekat_submission_saved_webhook_dispatch($body, (string) ($row['masked_id'] ?? ''), true);
        if (is_wp_error($sent)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'view' => 'detail',
                        'submission_id' => $submission_id,
                        'eh_submission_id' => $submission_id,
                        'resend_err' => $sent->get_error_code(),
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $wpdb->update(
            $assessment_table,
            ['updated_at' => eh_assessment_current_mysql_time()],
            ['id' => $submission_id],
            ['%s'],
            ['%d']
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'eh-assessment-submissions',
                    'view' => 'detail',
                    'submission_id' => $submission_id,
                    'eh_submission_id' => $submission_id,
                    'resend_ok' => '1',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'save_hair_specialist') {
        if (!eh_assessment_user_is_administrator()) {
            wp_die('You do not have permission to access this page.');
        }

        check_admin_referer('eh_save_hair_specialist');

        $specialist_table = eh_hair_specialist_table_name();
        $specialist_id = isset($_POST['specialist_id']) ? (int) $_POST['specialist_id'] : 0;
        $name = sanitize_text_field((string) ($_POST['name'] ?? ''));
        $email = sanitize_email((string) ($_POST['email'] ?? ''));
        $wa_number = eh_assessment_normalize_whatsapp((string) ($_POST['wa_number'] ?? ''));

        if ($name !== '' && $email !== '' && $wa_number !== '') {
            if ($specialist_id > 0) {
                $wpdb->update(
                    $specialist_table,
                    [
                        'name' => $name,
                        'email' => $email,
                        'wa_number' => $wa_number,
                        'updated_at' => eh_assessment_current_mysql_time(),
                    ],
                    ['id' => $specialist_id],
                    ['%s', '%s', '%s', '%s'],
                    ['%d']
                );
            } else {
                $wpdb->insert(
                    $specialist_table,
                    [
                        'masked_id' => eh_assessment_generate_masked_id('HS', $specialist_table),
                        'name' => $name,
                        'email' => $email,
                        'wa_number' => $wa_number,
                        'created_at' => eh_assessment_current_mysql_time(),
                        'updated_at' => eh_assessment_current_mysql_time(),
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s']
                );
                $specialist_id = (int) $wpdb->insert_id;
            }
        }

        $redirect_args = [
            'page' => 'eh-hair-specialists',
            'saved' => '1',
        ];

        if ($specialist_id > 0) {
            $redirect_args['edit_id'] = $specialist_id;
        }

        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    if ($action === 'save_manual_submission') {
        check_admin_referer('eh_save_manual_submission');

        if (!apply_filters('eh_assessment_allow_manual_submission_in_admin', false)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'eh_manual_sub' => 'disabled',
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $assessment_table = eh_assessment_table_name();
        eh_assessment_migrate_v179_drop_submission_deleted_at();
        eh_assessment_migrate_v180_report_pdf_template_risk_untreated_image();
        eh_assessment_migrate_v191_report_pdf_template_treatment_rec_3();
        eh_assessment_migrate_v200_report_pdf_template_precon_fields();
        eh_assessment_migrate_v201_report_pdf_template_drop_legacy_fields();
        eh_assessment_migrate_v203_report_pdf_template_report_header_title();
        eh_assessment_migrate_v204_report_pdf_template_body_medical_notes();
        eh_assessment_migrate_v205_report_pdf_template_diagnosis_name_detail();
        eh_assessment_migrate_v202_report_pdf_template_seed_precon_defaults();
        eh_assessment_migrate_v181_branch_outlet_display_name();
        $cekat = eh_assessment_parse_cekat_row_from_post();
        if (is_wp_error($cekat)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'eh_cekat_err' => $cekat->get_error_code(),
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $questions = eh_assessment_question_label_map();
        $answers_in = [];
        foreach (eh_assessment_question_key_map() as $key) {
            $field = 'answer_' . $key;
            $answers_in[$key] = [
                'question' => $questions[$key] ?? '',
                'answer' => isset($_POST[$field]) ? sanitize_text_field(wp_unslash((string) $_POST[$field])) : '',
            ];
        }

        $assessment_branch_masking = isset($_POST['assessment_branch_outlet_masking_id'])
            ? wp_unslash((string) $_POST['assessment_branch_outlet_masking_id'])
            : '';

        $source_slug_post = isset($_POST['source_page_slug']) ? wp_unslash((string) $_POST['source_page_slug']) : '';

        $payload = [
            'submission' => [
                'source_page_slug' => eh_assessment_normalize_submission_source_page_slug($source_slug_post),
                'branch_outlet_masking_id' => eh_assessment_normalize_submission_branch_masking_id($assessment_branch_masking),
                'report_type' => isset($_POST['report_type']) ? (int) $_POST['report_type'] : 0,
            ],
            'respondent' => [
                'name' => isset($_POST['respondent_name']) ? sanitize_text_field(wp_unslash((string) $_POST['respondent_name'])) : '',
                'whatsapp' => eh_assessment_normalize_whatsapp(isset($_POST['respondent_whatsapp']) ? wp_unslash((string) $_POST['respondent_whatsapp']) : ''),
                'gender' => isset($_POST['respondent_gender']) ? (string) wp_unslash((string) $_POST['respondent_gender']) : '',
                'birthdate' => isset($_POST['respondent_birthdate']) ? sanitize_text_field(wp_unslash((string) $_POST['respondent_birthdate'])) : '',
                'consent' => !empty($_POST['consent']),
            ],
            'answers' => $answers_in,
        ];

        $sanitized = eh_assessment_sanitize_payload($payload);

        $row_branch_masking = eh_assessment_normalize_submission_branch_masking_id(
            (string) ($sanitized['submission']['branch_outlet_masking_id'] ?? '')
        );
        $row_branch_id = eh_assessment_resolve_branch_outlet_id_from_masking_id($row_branch_masking);
        if ($row_branch_masking !== '' && $row_branch_id <= 0) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'eh_cekat_err' => 'invalid_branch_outlet',
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $branch_table_manual = eh_branch_outlet_table_name();
        $active_branch_count_manual = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$branch_table_manual} WHERE deleted_at IS NULL");
        if ($active_branch_count_manual > 0 && $row_branch_id <= 0) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'eh_cekat_err' => 'branch_office_required',
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $row_birthdate_raw = trim((string) ($sanitized['respondent']['birthdate'] ?? ''));
        if ($row_birthdate_raw === '') {
            $row_birthdate_raw = eh_assessment_default_placeholder_birthdate();
        }
        $row_birthdate = eh_assessment_normalize_submission_birthdate($row_birthdate_raw);
        if ($row_birthdate === null) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'eh_cekat_err' => 'invalid_birthdate',
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $sanitized['respondent']['birthdate'] = $row_birthdate;
        $sanitized['submission']['branch_outlet_masking_id'] = $row_branch_masking;

        $manual_slug = trim((string) ($sanitized['submission']['source_page_slug'] ?? ''));
        $manual_sp_id = (int) ($sanitized['submission']['source_page_id'] ?? 0);
        if ($manual_slug !== '' && $manual_sp_id <= 0) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'eh_cekat_err' => 'invalid_source_page',
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $payload_validation = eh_assessment_validate_sanitized_submission_payload($sanitized);
        if (is_wp_error($payload_validation)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'eh_cekat_err' => $payload_validation->get_error_code(),
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $sanitized['cekat_inbox'] = $cekat;

        $agent_mid_manual = (string) ($sanitized['submission']['agent_masking_id'] ?? '');
        $agent_name_manual = $agent_mid_manual !== '' ? eh_assessment_resolve_submission_agent_name_from_input($agent_mid_manual) : null;

        $cdbm = eh_assessment_attach_computed_to_sanitized($sanitized);
        $report_rt_manual = (int) ($sanitized['submission']['report_type'] ?? eh_assessment_resolve_submission_report_type(null));

        $row = [
            'masked_id' => eh_assessment_generate_submission_masked_id($report_rt_manual, $assessment_table),
            'source_page_id' => $manual_sp_id > 0 ? $manual_sp_id : null,
            'status' => eh_assessment_normalize_status('On Progress'),
            'respondent_name' => $sanitized['respondent']['name'],
            'respondent_whatsapp' => $sanitized['respondent']['whatsapp'],
            'respondent_gender' => $sanitized['respondent']['gender'],
            'respondent_birthdate' => $row_birthdate,
            'branch_outlet_id' => $row_branch_id > 0 ? $row_branch_id : null,
            'agent_name' => $agent_name_manual,
            'consent' => $sanitized['respondent']['consent'] ? 1 : 0,
            'lead_source' => $cdbm['lead_source'],
            'q1_focus_area' => $sanitized['answers']['q1_focus_area']['answer'],
            'q2_main_impact' => $sanitized['answers']['q2_main_impact']['answer'],
            'q3_duration' => $sanitized['answers']['q3_duration']['answer'],
            'q4_family_history' => $sanitized['answers']['q4_family_history']['answer'],
            'q5_previous_attempts' => $sanitized['answers']['q5_previous_attempts']['answer'],
            'q6_trigger_factors' => $sanitized['answers']['q6_trigger_factors']['answer'],
            'q7_biggest_worry' => $sanitized['answers']['q7_biggest_worry']['answer'],
            'q8_previous_consultation' => $sanitized['answers']['q8_previous_consultation']['answer'],
            'q9_expected_result' => $sanitized['answers']['q9_expected_result']['answer'],
        ];
        $row = array_merge($row, $cdbm['metrics']);
        $admin_tail = [
            'cekat_masking_id' => $cekat['cekat_masking_id'],
            'cekat_created_at' => $cekat['cekat_created_at'],
            'cekat_business_id' => $cekat['cekat_business_id'],
            'cekat_name' => $cekat['cekat_name'],
            'cekat_description' => $cekat['cekat_description'],
            'cekat_phone_number' => $cekat['cekat_phone_number'],
            'cekat_status' => $cekat['cekat_status'],
            'cekat_ai_agent_id' => $cekat['cekat_ai_agent_id'],
            'cekat_image_url' => $cekat['cekat_image_url'],
            'cekat_type' => $cekat['cekat_type'],
            'cekat_ai_agent_json' => $cekat['cekat_ai_agent_json'],
            'payload_json' => '',
            'submitted_at' => eh_assessment_current_mysql_time(),
            'updated_at' => eh_assessment_current_mysql_time(),
        ];
        $row = array_merge($row, $admin_tail);
        $row['payload_json'] = eh_assessment_payload_json_for_storage($sanitized);

        $formats = eh_assessment_submission_row_format_types();
        $inserted = false;
        $last_error = '';
        for ($attempt = 0; $attempt < 12; $attempt++) {
            if ($attempt > 0) {
                $row['masked_id'] = eh_assessment_generate_submission_masked_id($report_rt_manual, $assessment_table);
            }
            $inserted = $wpdb->insert($assessment_table, $row, $formats);
            if ($inserted !== false && (int) $inserted > 0) {
                break;
            }
            $last_error = (string) $wpdb->last_error;
            if (!eh_assessment_db_error_is_duplicate_masked_id($last_error)) {
                break;
            }
        }

        if ($inserted === false || (int) $inserted < 1) {
            $le = strtolower($last_error !== '' ? $last_error : (string) $wpdb->last_error);
            if (strpos($le, 'duplicate') !== false && strpos($le, 'cekat') !== false) {
                wp_safe_redirect(
                    add_query_arg(
                        [
                            'page' => 'eh-assessment-submissions',
                            'eh_cekat_dup' => '1',
                        ],
                        admin_url('admin.php')
                    )
                );
                exit;
            }

            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'eh-assessment-submissions',
                        'eh_cekat_err' => 'insert_failed',
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $new_id = (int) $wpdb->insert_id;
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'eh-assessment-submissions',
                    'view' => 'detail',
                    'submission_id' => $new_id,
                    'eh_submission_id' => $new_id,
                    'eh_saved' => '1',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'save_branch_outlet') {
        check_admin_referer('eh_save_branch_outlet');

        $branch_table = eh_branch_outlet_table_name();
        $branch_id = isset($_POST['branch_outlet_id']) ? (int) $_POST['branch_outlet_id'] : 0;
        $now = eh_assessment_current_mysql_time();

        if ($branch_id > 0) {
            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT id FROM {$branch_table} WHERE id = %d LIMIT 1", $branch_id),
                ARRAY_A
            );
            if (!is_array($existing)) {
                wp_safe_redirect(
                    add_query_arg(
                        ['page' => 'eh-assessment-branch-outlet', 'bo_err' => 'not_found'],
                        admin_url('admin.php')
                    )
                );
                exit;
            }

            $existing_row = $wpdb->get_row(
                $wpdb->prepare("SELECT cekat_name FROM {$branch_table} WHERE id = %d LIMIT 1", $branch_id),
                ARRAY_A
            );
            if (!is_array($existing_row) || trim((string) ($existing_row['cekat_name'] ?? '')) === '') {
                wp_safe_redirect(
                    add_query_arg(
                        ['page' => 'eh-assessment-branch-outlet', 'bo_err' => 'name_required'],
                        admin_url('admin.php')
                    )
                );
                exit;
            }

            $posted = eh_assessment_sanitize_cekat_branch_fields_from_post();
            $display_name = eh_assessment_sanitize_branch_display_name_from_post();
            $wpdb->update(
                $branch_table,
                [
                    'cekat_wa_template_masking_id' => $posted['cekat_wa_template_masking_id'],
                    'cekat_wa_template_name' => $posted['cekat_wa_template_name'],
                    'display_name' => $display_name,
                    'cekat_created_at' => null,
                    'cekat_business_id' => null,
                    'cekat_description' => null,
                    'cekat_ai_agent_id' => null,
                    'cekat_image_url' => null,
                    'cekat_ai_agent_json' => null,
                    'updated_at' => $now,
                ],
                ['id' => $branch_id],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
            wp_safe_redirect(
                add_query_arg(
                    ['page' => 'eh-assessment-branch-outlet', 'bo_saved' => '1'],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $cekat = eh_assessment_parse_cekat_row_from_post();
        if (is_wp_error($cekat)) {
            wp_safe_redirect(
                add_query_arg(
                    ['page' => 'eh-assessment-branch-outlet', 'bo_err' => $cekat->get_error_code()],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $posted = eh_assessment_sanitize_cekat_branch_fields_from_post();
        $cekat['cekat_wa_template_masking_id'] = $posted['cekat_wa_template_masking_id'];
        $cekat['cekat_wa_template_name'] = $posted['cekat_wa_template_name'];
        $cekat['display_name'] = eh_assessment_sanitize_branch_display_name_from_post();

        if ($cekat['cekat_name'] === '') {
            wp_safe_redirect(
                add_query_arg(
                    ['page' => 'eh-assessment-branch-outlet', 'bo_err' => 'name_required'],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $dup = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, deleted_at FROM {$branch_table} WHERE cekat_masking_id = %s LIMIT 1",
                $cekat['cekat_masking_id']
            ),
            ARRAY_A
        );

        if (is_array($dup) && $dup['deleted_at'] === null) {
            wp_safe_redirect(
                add_query_arg(
                    ['page' => 'eh-assessment-branch-outlet', 'bo_err' => 'duplicate'],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $insert_row = array_merge(
            eh_assessment_branch_outlet_row_for_db($cekat),
            [
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if (is_array($dup) && $dup['deleted_at'] !== null) {
            $restore_id = (int) $dup['id'];
            $update_restore = array_merge(
                eh_assessment_branch_outlet_row_for_db($cekat),
                [
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]
            );
            $wpdb->update(
                $branch_table,
                $update_restore,
                ['id' => $restore_id],
                array_fill(0, count($update_restore), '%s'),
                ['%d']
            );
        } else {
            $wpdb->insert(
                $branch_table,
                $insert_row,
                array_fill(0, count($insert_row), '%s')
            );
        }

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'eh-assessment-branch-outlet', 'bo_saved' => '1'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'trash_branch_outlet') {
        check_admin_referer('eh_trash_branch_outlet');

        $branch_table = eh_branch_outlet_table_name();
        $branch_id = isset($_POST['branch_outlet_id']) ? (int) $_POST['branch_outlet_id'] : 0;
        if ($branch_id > 0) {
            $now = eh_assessment_current_mysql_time();
            $wpdb->update(
                $branch_table,
                [
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ],
                ['id' => $branch_id],
                ['%s', '%s'],
                ['%d']
            );
        }

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'eh-assessment-branch-outlet', 'bo_trashed' => '1'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'restore_branch_outlet') {
        check_admin_referer('eh_restore_branch_outlet');

        $branch_table = eh_branch_outlet_table_name();
        $branch_id = isset($_POST['branch_outlet_id']) ? (int) $_POST['branch_outlet_id'] : 0;
        if ($branch_id > 0) {
            $now = eh_assessment_current_mysql_time();
            $wpdb->update(
                $branch_table,
                [
                    'deleted_at' => null,
                    'updated_at' => $now,
                ],
                ['id' => $branch_id],
                ['%s', '%s'],
                ['%d']
            );
        }

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'eh-assessment-branch-outlet', 'bo_status' => 'trash', 'bo_restored' => '1'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'save_hair_specialist_agent') {
        if (!eh_assessment_user_is_administrator()) {
            wp_die('You do not have permission to access this page.');
        }

        check_admin_referer('eh_save_hair_specialist_agent');

        $agent_table = eh_hair_specialist_agent_table_name();
        $row_id = isset($_POST['hair_specialist_agent_id']) ? (int) $_POST['hair_specialist_agent_id'] : 0;
        $now = eh_assessment_current_mysql_time();

        $masking_id = eh_assessment_normalize_agent_masking_id((string) ($_POST['masking_id'] ?? ''));
        $name = sanitize_text_field((string) ($_POST['agent_name'] ?? ''));
        $email = sanitize_email((string) ($_POST['agent_email'] ?? ''));
        $branch_outlet_id = isset($_POST['branch_outlet_id']) ? (int) $_POST['branch_outlet_id'] : 0;
        $agent_code_raw = isset($_POST['agent_code']) ? trim(wp_unslash((string) $_POST['agent_code'])) : '';

        $redirect_err = static function (string $code) use ($row_id): void {
            $args = [
                'page' => 'eh-hair-specialist-agents',
                'hsa_err' => $code,
            ];
            if ($row_id > 0) {
                $args['hsa_edit'] = (string) $row_id;
            }
            wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
            exit;
        };

        if ($branch_outlet_id <= 0 || !eh_assessment_branch_outlet_id_is_active($branch_outlet_id)) {
            $redirect_err('branch_required');
        }

        if ($agent_code_raw === '') {
            $redirect_err('code_required');
        }
        if (!eh_assessment_agent_code_raw_is_query_safe($agent_code_raw)) {
            $redirect_err('code_invalid');
        }
        $agent_code = eh_assessment_normalize_agent_code($agent_code_raw);

        if ($row_id > 0) {
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, masking_id, name, email FROM {$agent_table} WHERE id = %d LIMIT 1",
                    $row_id
                ),
                ARRAY_A
            );
            if (!is_array($existing)) {
                $redirect_err('not_found');
            }
            $masking_id = eh_assessment_normalize_agent_masking_id((string) ($existing['masking_id'] ?? ''));
            // Name and email come from Cekat at creation time; do not allow changing via POST on edit.
            $name = sanitize_text_field((string) ($existing['name'] ?? ''));
            $email = sanitize_email((string) ($existing['email'] ?? ''));
        } else {
            if ($masking_id === '') {
                $redirect_err('masking_required');
            }
            if (eh_assessment_hair_specialist_agent_masking_id_exists_globally($masking_id)) {
                $redirect_err('duplicate_agent');
            }
        }

        if (eh_assessment_hair_specialist_agent_code_taken($agent_code, $row_id)) {
            $redirect_err('duplicate_code');
        }

        if ($row_id > 0) {
            $wpdb->update(
                $agent_table,
                [
                    'name' => $name,
                    'email' => $email,
                    'branch_outlet_id' => $branch_outlet_id,
                    'agent_code' => $agent_code,
                    'updated_at' => $now,
                ],
                ['id' => $row_id],
                ['%s', '%s', '%d', '%s', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $agent_table,
                [
                    'masking_id' => $masking_id,
                    'name' => $name,
                    'email' => $email,
                    'branch_outlet_id' => $branch_outlet_id,
                    'agent_code' => $agent_code,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
            );
            $row_id = (int) $wpdb->insert_id;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'eh-hair-specialist-agents',
                    'hsa_saved' => '1',
                    'hsa_edit' => (string) $row_id,
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'trash_hair_specialist_agent') {
        if (!eh_assessment_user_is_administrator()) {
            wp_die('You do not have permission to access this page.');
        }

        check_admin_referer('eh_trash_hair_specialist_agent');

        $agent_table = eh_hair_specialist_agent_table_name();
        $row_id = isset($_POST['hair_specialist_agent_id']) ? (int) $_POST['hair_specialist_agent_id'] : 0;
        if ($row_id > 0) {
            $now = eh_assessment_current_mysql_time();
            $wpdb->update(
                $agent_table,
                [
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ],
                ['id' => $row_id],
                ['%s', '%s'],
                ['%d']
            );
        }

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'eh-hair-specialist-agents', 'hsa_trashed' => '1'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'restore_hair_specialist_agent') {
        if (!eh_assessment_user_is_administrator()) {
            wp_die('You do not have permission to access this page.');
        }

        check_admin_referer('eh_restore_hair_specialist_agent');

        $agent_table = eh_hair_specialist_agent_table_name();
        $row_id = isset($_POST['hair_specialist_agent_id']) ? (int) $_POST['hair_specialist_agent_id'] : 0;
        if ($row_id > 0) {
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT masking_id, agent_code FROM {$agent_table} WHERE id = %d LIMIT 1",
                    $row_id
                ),
                ARRAY_A
            );
            if (is_array($existing)) {
                $mid = eh_assessment_normalize_agent_masking_id((string) ($existing['masking_id'] ?? ''));
                $ac = eh_assessment_normalize_agent_code((string) ($existing['agent_code'] ?? ''));
                if (
                    ($mid !== '' && eh_assessment_hair_specialist_agent_masking_id_taken($mid, $row_id))
                    || ($ac !== '' && eh_assessment_hair_specialist_agent_code_taken($ac, $row_id))
                ) {
                    wp_safe_redirect(
                        add_query_arg(
                            [
                                'page' => 'eh-hair-specialist-agents',
                                'hsa_status' => 'trash',
                                'hsa_err' => 'restore_conflict',
                            ],
                            admin_url('admin.php')
                        )
                    );
                    exit;
                }
            }

            $now = eh_assessment_current_mysql_time();
            $wpdb->update(
                $agent_table,
                [
                    'deleted_at' => null,
                    'updated_at' => $now,
                ],
                ['id' => $row_id],
                ['%s', '%s'],
                ['%d']
            );
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'eh-hair-specialist-agents',
                    'hsa_status' => 'trash',
                    'hsa_restored' => '1',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'save_report_pdf_template') {
        check_admin_referer('eh_save_report_pdf_template');
        if (!eh_assessment_current_user_can_access_admin()) {
            wp_die('You do not have permission to access this page.');
        }

        $table = eh_assessment_report_pdf_template_table_name();
        $template_id = isset($_POST['report_pdf_template_id']) ? (int) $_POST['report_pdf_template_id'] : 0;
        $data = eh_assessment_report_pdf_template_row_from_post();
        $now = eh_assessment_current_mysql_time();

        if ($template_id > 0) {
            $existing = eh_assessment_report_pdf_template_get_row($template_id);
            if ($existing === []) {
                wp_safe_redirect(
                    add_query_arg(
                        ['page' => 'eh-assessment-report-pdf-templates', 'rpt_err' => 'not_found'],
                        admin_url('admin.php')
                    )
                );
                exit;
            }
            $data['masking_id'] = eh_assessment_normalize_report_pdf_template_masking_id((string) ($existing['masking_id'] ?? ''));
        } elseif ($data['masking_id'] === '') {
            $data['masking_id'] = eh_assessment_generate_report_pdf_template_masking_id();
        } elseif (eh_assessment_report_pdf_template_masking_id_taken($data['masking_id'], 0)) {
            wp_safe_redirect(
                add_query_arg(
                    ['page' => 'eh-assessment-report-pdf-templates', 'rpt_err' => 'dup_mask'],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        $row_payload = [
            'report_title' => $data['report_title'],
            'report_header_title' => $data['report_header_title'] !== '' ? $data['report_header_title'] : 'HAIR HEALTH',
            'subtitle' => $data['subtitle'],
            'greeting_description' => $data['greeting_description'],
            'diagnosis_name' => $data['diagnosis_name'],
            'diagnosis_name_detail' => $data['diagnosis_name_detail'],
            'title_condition_explanation' => $data['title_condition_explanation'],
            'description_condition_explanation' => $data['description_condition_explanation'],
            'title_clinical_knowledge' => $data['title_clinical_knowledge'],
            'subtitle_clinical_knowledge' => $data['subtitle_clinical_knowledge'],
            'image_clinical_knowledge' => $data['image_clinical_knowledge'],
            'description_clinical_knowledge' => $data['description_clinical_knowledge'],
            'title_evaluation_urgency' => $data['title_evaluation_urgency'],
            'description_evaluation_urgency' => $data['description_evaluation_urgency'],
            'title_treatment_journey' => $data['title_treatment_journey'],
            'description_treatment_journey' => $data['description_treatment_journey'],
            'image_treatment_journey' => $data['image_treatment_journey'],
            'title_recommendation_approach' => $data['title_recommendation_approach'],
            'description_recommendation_approach' => $data['description_recommendation_approach'],
            'detail_recommendation_approach' => $data['detail_recommendation_approach'],
            'bottom_description_recommendation_approach' => $data['bottom_description_recommendation_approach'],
            'title_next_steps' => $data['title_next_steps'],
            'description_next_steps' => $data['description_next_steps'],
            'title_medical_notes' => $data['title_medical_notes'],
            'body_medical_notes' => $data['body_medical_notes'],
            'description_medical_notes' => $data['description_medical_notes'],
            'updated_at' => $now,
        ];
        $formats = array_fill(0, count($row_payload), '%s');

        if ($template_id > 0) {
            $wpdb->update($table, $row_payload, ['id' => $template_id], $formats, ['%d']);
            $redirect_id = $template_id;
        } else {
            $insert = array_merge(
                ['masking_id' => $data['masking_id'], 'created_at' => $now],
                $row_payload
            );
            $insert_formats = array_fill(0, count($insert), '%s');
            $wpdb->insert($table, $insert, $insert_formats);
            $redirect_id = (int) $wpdb->insert_id;
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'eh-assessment-report-pdf-templates',
                    'rpt_saved' => '1',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'delete_report_pdf_template') {
        check_admin_referer('eh_delete_report_pdf_template');
        if (!eh_assessment_current_user_can_access_admin()) {
            wp_die('You do not have permission to access this page.');
        }

        $table = eh_assessment_report_pdf_template_table_name();
        $template_id = isset($_POST['report_pdf_template_id']) ? (int) $_POST['report_pdf_template_id'] : 0;
        if ($template_id > 0) {
            $now = eh_assessment_current_mysql_time();
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET deleted_at = %s, updated_at = %s WHERE id = %d AND deleted_at IS NULL",
                    $now,
                    $now,
                    $template_id
                )
            );
        }

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'eh-assessment-report-pdf-templates', 'rpt_trashed' => '1'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    if ($action === 'restore_report_pdf_template') {
        check_admin_referer('eh_restore_report_pdf_template');
        if (!eh_assessment_current_user_can_access_admin()) {
            wp_die('You do not have permission to access this page.');
        }

        $table = eh_assessment_report_pdf_template_table_name();
        $template_id = isset($_POST['report_pdf_template_id']) ? (int) $_POST['report_pdf_template_id'] : 0;
        if ($template_id > 0) {
            $now = eh_assessment_current_mysql_time();
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET deleted_at = NULL, updated_at = %s WHERE id = %d AND deleted_at IS NOT NULL",
                    $now,
                    $template_id
                )
            );
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'eh-assessment-report-pdf-templates',
                    'rpt_status' => 'trash',
                    'rpt_restored' => '1',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }
}
add_action('admin_init', 'eh_assessment_handle_admin_actions');

function eh_assessment_stream_report_pdf(array $submission): void
{
    if (!class_exists(\Dompdf\Dompdf::class)) {
        wp_die('PDF generation is unavailable on this server.');
    }

    $report_data = eh_assessment_build_report_data($submission);
    $renderer = new EH_Assessment_Report_Pdf_Renderer();
    $pdf_blob = $renderer->render($report_data);
    $report_id = trim((string) ($submission['masked_id'] ?? ''));
    if (eh_assessment_submission_masked_id_is_valid($report_id)) {
        $filename = $report_id . '.pdf';
    } else {
        $respondent_name = trim((string) ($submission['respondent_name'] ?? ''));
        if ($respondent_name === '') {
            $respondent_name = 'Submission';
        }

        $created_at = eh_assessment_format_indonesian_date((string) ($submission['submitted_at'] ?? ''));
        $filename = trim(
            preg_replace(
                '/[^\pL\pN\s_\-\.]/u',
                '',
                sprintf(
                    'EUROHAIRLAB HAIR ASSESSMENT - %s - %s.pdf',
                    $respondent_name,
                    $created_at !== '' ? $created_at : 'Unknown Date'
                )
            ) ?? ''
        );
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    nocache_headers();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf_blob));
    echo $pdf_blob;
    exit;
}

function eh_assessment_handle_report_download(): void
{
    if (!eh_assessment_current_user_can_access_admin()) {
        wp_die('You do not have permission to access this page.');
    }

    $submission_id = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
    if ($submission_id <= 0) {
        wp_die('Submission not found.');
    }

    $nonce = isset($_GET['_wpnonce']) ? (string) wp_unslash($_GET['_wpnonce']) : '';
    if ($nonce === '' || !wp_verify_nonce($nonce, 'eh_download_assessment_report_' . $submission_id)) {
        wp_die(
            'Invalid or expired PDF link. In WP Admin open Assessment Submissions, open the submission, and click Download Report PDF again.',
            'Report PDF',
            403
        );
    }

    $submission = eh_assessment_get_submission_detail_row($submission_id);
    if (!$submission) {
        wp_die('Submission not found.');
    }

    eh_assessment_stream_report_pdf($submission);
}

function eh_assessment_handle_public_report_download(): void
{
    $submission_id = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
    $masked_id = isset($_GET['masked_id']) ? trim((string) wp_unslash($_GET['masked_id'])) : '';
    $sig = isset($_GET['sig']) ? trim((string) wp_unslash($_GET['sig'])) : '';

    if ($masked_id === '' || $sig === '') {
        wp_die('Invalid report link.', 'Report PDF', 400);
    }

    $expectedV2 = eh_assessment_public_report_download_signature_v2($masked_id);
    if ($expectedV2 !== '' && hash_equals($expectedV2, $sig)) {
        $submission = eh_assessment_get_submission_for_public_report_download_by_masked_id($masked_id);
        if (!$submission) {
            wp_die('Submission not found.', 'Report PDF', 404);
        }
        eh_assessment_stream_report_pdf($submission);

        return;
    }

    if ($submission_id <= 0) {
        wp_die('Invalid or expired report link.', 'Report PDF', 403);
    }

    $expectedLegacy = eh_assessment_public_report_download_signature($masked_id, $submission_id);
    if ($expectedLegacy === '' || !hash_equals($expectedLegacy, $sig)) {
        wp_die('Invalid or expired report link.', 'Report PDF', 403);
    }

    $submission = eh_assessment_get_submission_for_public_report_download($submission_id);
    if (!$submission) {
        wp_die('Submission not found.', 'Report PDF', 404);
    }

    $masked_from_row = eh_assessment_normalize_masked_id_for_download_compare((string) ($submission['masked_id'] ?? ''));
    $masked_from_url = eh_assessment_normalize_masked_id_for_download_compare($masked_id);
    if ($masked_from_row === '' || $masked_from_url === '' || !hash_equals($masked_from_row, $masked_from_url)) {
        wp_die('Submission not found.', 'Report PDF', 404);
    }

    eh_assessment_stream_report_pdf($submission);
}
add_action('admin_post_eh_download_assessment_report', 'eh_assessment_handle_report_download');
add_action('admin_post_eh_download_assessment_report_public', 'eh_assessment_handle_public_report_download');
add_action('admin_post_nopriv_eh_download_assessment_report_public', 'eh_assessment_handle_public_report_download');

function eh_assessment_render_user_whatsapp_field($user = null): void
{
    $value = '';
    if ($user instanceof WP_User && $user->ID > 0) {
        $value = (string) get_user_meta($user->ID, EH_ASSESSMENT_USER_WHATSAPP_META_KEY, true);
    }

    echo '<h2>Hair Specialist Details</h2>';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr>';
    echo '<th scope="row"><label for="eh_user_whatsapp_number">WhatsApp Number</label></th>';
    echo '<td>';
    echo '<input type="text" name="eh_user_whatsapp_number" id="eh_user_whatsapp_number" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Format Indonesia. Input 0 atau +62 akan otomatis disimpan sebagai 62xxxxxxxxx.</p>';
    echo '</td>';
    echo '</tr>';
    echo '</tbody></table>';
}
add_action('show_user_profile', 'eh_assessment_render_user_whatsapp_field');
add_action('edit_user_profile', 'eh_assessment_render_user_whatsapp_field');
add_action('user_new_form', 'eh_assessment_render_user_whatsapp_field');

function eh_assessment_save_user_whatsapp_field(int $user_id): void
{
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    if (!isset($_POST['eh_user_whatsapp_number'])) {
        return;
    }

    update_user_meta(
        $user_id,
        EH_ASSESSMENT_USER_WHATSAPP_META_KEY,
        eh_assessment_normalize_whatsapp((string) $_POST['eh_user_whatsapp_number'])
    );
}
add_action('personal_options_update', 'eh_assessment_save_user_whatsapp_field');
add_action('edit_user_profile_update', 'eh_assessment_save_user_whatsapp_field');
add_action('user_register', 'eh_assessment_save_user_whatsapp_field');

function eh_assessment_add_users_table_columns(array $columns): array
{
    $result = [];

    foreach ($columns as $key => $label) {
        $result[$key] = $label;

        if ($key === 'email') {
            $result['eh_user_whatsapp'] = 'WhatsApp';
        }
    }

    if (!isset($result['eh_user_whatsapp'])) {
        $result['eh_user_whatsapp'] = 'WhatsApp';
    }

    return $result;
}
add_filter('manage_users_columns', 'eh_assessment_add_users_table_columns');

function eh_assessment_render_users_table_column(string $value, string $column_name, int $user_id): string
{
    if ($column_name === 'eh_user_whatsapp') {
        return esc_html((string) get_user_meta($user_id, EH_ASSESSMENT_USER_WHATSAPP_META_KEY, true));
    }

    return $value;
}
add_filter('manage_users_custom_column', 'eh_assessment_render_users_table_column', 10, 3);

function eh_assessment_migrate_legacy_records(): void
{
    global $wpdb;

    if ((string) get_option('eh_assessment_data_v110_migrated', '') === '1') {
        return;
    }

    $assessment_table = eh_assessment_table_name();
    $specialist_table = eh_hair_specialist_table_name();
    $timezone = eh_assessment_gmt7_timezone();

    $assessment_rows = $wpdb->get_results("SELECT id, respondent_whatsapp, payload_json, submitted_at, updated_at FROM {$assessment_table}", ARRAY_A);
    foreach ($assessment_rows as $row) {
        $payload = json_decode((string) $row['payload_json'], true);
        if (is_array($payload)) {
            $payload['respondent']['whatsapp'] = eh_assessment_normalize_whatsapp((string) ($payload['respondent']['whatsapp'] ?? ''));
        }

        $submitted_at = date_create_immutable((string) $row['submitted_at'], new DateTimeZone('UTC'));
        $updated_at = date_create_immutable((string) $row['updated_at'], new DateTimeZone('UTC'));

        $wpdb->update(
            $assessment_table,
            [
                'respondent_whatsapp' => eh_assessment_normalize_whatsapp((string) $row['respondent_whatsapp']),
                'payload_json' => is_array($payload) ? wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $row['payload_json'],
                'submitted_at' => $submitted_at ? $submitted_at->setTimezone($timezone)->format('Y-m-d H:i:s') : (string) $row['submitted_at'],
                'updated_at' => $updated_at ? $updated_at->setTimezone($timezone)->format('Y-m-d H:i:s') : (string) $row['updated_at'],
            ],
            ['id' => (int) $row['id']],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
    }

    $specialist_rows = $wpdb->get_results("SELECT id, wa_number, created_at, updated_at FROM {$specialist_table}", ARRAY_A);
    foreach ($specialist_rows as $row) {
        $created_at = date_create_immutable((string) $row['created_at'], new DateTimeZone('UTC'));
        $updated_at = date_create_immutable((string) $row['updated_at'], new DateTimeZone('UTC'));

        $wpdb->update(
            $specialist_table,
            [
                'wa_number' => eh_assessment_normalize_whatsapp((string) $row['wa_number']),
                'created_at' => $created_at ? $created_at->setTimezone($timezone)->format('Y-m-d H:i:s') : (string) $row['created_at'],
                'updated_at' => $updated_at ? $updated_at->setTimezone($timezone)->format('Y-m-d H:i:s') : (string) $row['updated_at'],
            ],
            ['id' => (int) $row['id']],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    update_option('eh_assessment_data_v110_migrated', '1');
}

function eh_assessment_register_admin_menu(): void
{
    add_menu_page(
        'Assessment Data',
        'Assessment Data',
        EH_ASSESSMENT_ACCESS_CAPABILITY,
        'eh-assessment-submissions',
        'eh_assessment_render_submissions_page',
        'dashicons-clipboard',
        26
    );

    add_submenu_page(
        'eh-assessment-submissions',
        'Assessment Submissions',
        'Assessment Submissions',
        EH_ASSESSMENT_ACCESS_CAPABILITY,
        'eh-assessment-submissions',
        'eh_assessment_render_submissions_page'
    );

    add_submenu_page(
        'eh-assessment-submissions',
        'Branch Office',
        'Branch Office',
        EH_ASSESSMENT_ACCESS_CAPABILITY,
        'eh-assessment-branch-outlet',
        'eh_assessment_render_branch_outlet_page'
    );

    add_submenu_page(
        'eh-assessment-submissions',
        'Hair Specialist Agents',
        'Hair Specialist Agents',
        'manage_options',
        'eh-hair-specialist-agents',
        'eh_assessment_render_hair_specialist_agents_page'
    );

    add_submenu_page(
        'eh-assessment-submissions',
        'Report PDF templates',
        'Report PDF templates',
        EH_ASSESSMENT_ACCESS_CAPABILITY,
        'eh-assessment-report-pdf-templates',
        'eh_assessment_render_report_pdf_templates_page'
    );

    add_submenu_page(
        'eh-assessment-submissions',
        'Report Preview',
        'Report Preview',
        EH_ASSESSMENT_ACCESS_CAPABILITY,
        'eh-assessment-report-preview',
        'eh_assessment_render_report_preview_page'
    );

    add_submenu_page(
        'eh-assessment-submissions',
        'Cekat completion webhook',
        'Cekat webhook',
        'manage_options',
        'eh-assessment-cekat-webhook',
        'eh_assessment_render_cekat_webhook_page'
    );

}
add_action('admin_menu', 'eh_assessment_register_admin_menu');

function eh_assessment_render_cekat_webhook_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $endpoint = rest_url('eurohairlab/v1/assessment-submissions/complete');
    $configured = eh_assessment_webhook_complete_secret_is_configured();
    $raw_len = strlen(eh_assessment_webhook_complete_config_secret());

    echo '<div class="wrap">';
    echo '<h1>Cekat completion webhook</h1>';
    echo '<p class="description" style="margin-top:0;">Cekat calls this URL at end of flow with <strong>POST</strong> and JSON <code>{"submission_id":"R05-0326-001"}</code> (or legacy <code>ASM-…</code>). Authentication: <code>Authorization: &lt;secret&gt;</code>.</p>';

    if (!$configured) {
        if (defined('WEBHOOK_CEKAT_KEY') && $raw_len > 0 && $raw_len < EH_ASSESSMENT_WEBHOOK_COMPLETE_SECRET_MIN_LENGTH) {
            echo '<div class="notice notice-warning"><p><code>WEBHOOK_CEKAT_KEY</code> is set but too short. Use at least ' . esc_html((string) EH_ASSESSMENT_WEBHOOK_COMPLETE_SECRET_MIN_LENGTH) . ' characters (max 512).</p></div>';
        } elseif (!defined('WEBHOOK_CEKAT_KEY')) {
            echo '<div class="notice notice-warning"><p>Define <code>WEBHOOK_CEKAT_KEY</code> in <code>wp-config.php</code> to enable the webhook (see below).</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p><code>WEBHOOK_CEKAT_KEY</code> is empty. Set a non-empty value in <code>wp-config.php</code>.</p></div>';
        }
    } else {
        echo '<div class="notice notice-success"><p>Webhook secret is loaded from <code>wp-config.php</code> (length OK). Paste the <strong>same value</strong> into Cekat as the <code>Authorization</code> header value.</p></div>';
    }

    echo '<h2 class="title" style="margin-top:24px;">Endpoint</h2>';
    echo '<p><code style="word-break:break-all;">' . esc_html($endpoint) . '</code></p>';
    echo '<p class="description">In production, HTTPS is expected (filter <code>eh_assessment_webhook_complete_require_https</code>).</p>';

    echo '<h2 class="title" style="margin-top:24px;">wp-config.php</h2>';
    echo '<p>Add <strong>before</strong> <code>/* That\'s all, stop editing! Happy publishing. */</code>:</p>';
    echo '<pre style="overflow:auto;padding:12px;background:#f6f7f7;border:1px solid #c3c4c7;">define( \'WEBHOOK_CEKAT_KEY\', \'paste-a-long-random-string-at-least-' . esc_html((string) EH_ASSESSMENT_WEBHOOK_COMPLETE_SECRET_MIN_LENGTH) . '-chars\' );</pre>';
    echo '<p class="description">Generate a random string (for example 48 characters). Use the <strong>exact same string</strong> in Cekat &rarr; HTTP Request &rarr; Bearer Auth &rarr; Bearer Token field.</p>';

    echo '</div>';
}

function eh_assessment_render_branch_outlet_page(): void
{
    if (!eh_assessment_current_user_can_access_admin()) {
        wp_die('You do not have permission to access this page.');
    }

    global $wpdb;
    $branch_table = eh_branch_outlet_table_name();
    $bo_status = isset($_GET['bo_status']) && (string) $_GET['bo_status'] === 'trash' ? 'trash' : 'active';
    $base = admin_url('admin.php?page=eh-assessment-branch-outlet');
    $bo_search = isset($_GET['bo_search']) ? trim(sanitize_text_field(wp_unslash((string) $_GET['bo_search']))) : '';
    if (strlen($bo_search) > 191) {
        $bo_search = substr($bo_search, 0, 191);
    }

    echo '<div class="wrap">';
    echo '<h1>Branch Office</h1>';
    echo '<p class="description" style="margin-top:0;">Add branches by choosing a Cekat inbox from the API list, set an optional <strong>Display name</strong> for the public assessment branch list, then optionally pick <strong>Template Message To Customer</strong> (Cekat template list). Inbox fields from Cekat are read-only; you can edit display name and customer template on save. Use View for a read-only summary. Delete moves a row to Trash; Restore brings it back.</p>';

    if (isset($_GET['bo_saved']) && (string) $_GET['bo_saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Branch Office saved.</p></div>';
    }
    if (isset($_GET['bo_trashed']) && (string) $_GET['bo_trashed'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Branch Office moved to trash.</p></div>';
    }
    if (isset($_GET['bo_restored']) && (string) $_GET['bo_restored'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Branch Office restored.</p></div>';
    }

    $bo_err = sanitize_key((string) ($_GET['bo_err'] ?? ''));
    $bo_err_map = [
        'duplicate' => 'This inbox already exists as an active record. Trash it first or restore it from trash.',
        'name_required' => 'Branch Office name is required.',
        'not_found' => 'Record not found.',
        'cekat_required' => 'Select a Cekat inbox from the API list first.',
        'cekat_invalid' => 'Cekat inbox data from the API is invalid.',
    ];
    if ($bo_err !== '' && isset($bo_err_map[$bo_err])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($bo_err_map[$bo_err]) . '</p></div>';
    } elseif ($bo_err !== '') {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html('Could not complete the Branch Office action.') . '</p></div>';
    }

    echo '<p style="margin:12px 0 16px;"><button type="button" class="button button-primary" id="eh-bo-open-add">Add Branch Office</button></p>';

    echo '<div class="eh-bo-toolbar" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin:8px 0 12px;">';
    echo '<ul class="subsubsub" style="margin:0;float:none;">';
    $active_url = $bo_search !== '' ? add_query_arg('bo_search', $bo_search, $base) : $base;
    $trash_url = add_query_arg('bo_status', 'trash', $base);
    if ($bo_search !== '') {
        $trash_url = add_query_arg('bo_search', $bo_search, $trash_url);
    }
    echo '<li><a href="' . esc_url($active_url) . '"' . ($bo_status === 'active' ? ' class="current"' : '') . '>Active</a> | </li>';
    echo '<li><a href="' . esc_url($trash_url) . '"' . ($bo_status === 'trash' ? ' class="current"' : '') . '>Trash</a></li>';
    echo '</ul>';
    echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0;">';
    echo '<input type="hidden" name="page" value="eh-assessment-branch-outlet" />';
    if ($bo_status === 'trash') {
        echo '<input type="hidden" name="bo_status" value="trash" />';
    }
    echo '<label for="eh-bo-search" class="screen-reader-text">Search branch offices</label>';
    echo '<input type="search" id="eh-bo-search" name="bo_search" value="' . esc_attr($bo_search) . '" placeholder="Name, phone, type, status, masking id, template…" style="min-width:200px;max-width:100%;" />';
    echo '<input type="submit" class="button" value="Search" />';
    if ($bo_search !== '') {
        $clear_href = $bo_status === 'trash' ? add_query_arg('bo_status', 'trash', $base) : $base;
        echo '<a class="button" href="' . esc_url($clear_href) . '">Clear</a>';
    }
    echo '</form>';
    echo '</div>';

    $boOrderLabel = eh_assessment_branch_outlet_label_sql('');
    if ($bo_search === '') {
        $sql = $bo_status === 'trash'
            ? "SELECT * FROM {$branch_table} WHERE deleted_at IS NOT NULL ORDER BY updated_at DESC"
            : "SELECT * FROM {$branch_table} WHERE deleted_at IS NULL ORDER BY {$boOrderLabel} ASC";
        $rows = $wpdb->get_results($sql, ARRAY_A);
    } else {
        $like = '%' . $wpdb->esc_like($bo_search) . '%';
        $search_where = '(
            cekat_name LIKE %s OR display_name LIKE %s OR cekat_phone_number LIKE %s OR cekat_type LIKE %s OR cekat_status LIKE %s
            OR cekat_masking_id LIKE %s OR cekat_wa_template_name LIKE %s OR cekat_wa_template_masking_id LIKE %s
            OR cekat_description LIKE %s OR cekat_business_id LIKE %s OR cekat_ai_agent_id LIKE %s
        )';
        if ($bo_status === 'trash') {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$branch_table} WHERE deleted_at IS NOT NULL AND {$search_where} ORDER BY updated_at DESC",
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$branch_table} WHERE deleted_at IS NULL AND {$search_where} ORDER BY {$boOrderLabel} ASC",
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like,
                $like
            );
        }
        $rows = $wpdb->get_results($sql, ARRAY_A);
    }
    if (!is_array($rows)) {
        $rows = [];
    }

    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>Display name</th><th>Cekat inbox name</th><th>WhatsApp</th><th>Type</th><th>Status</th>';
    echo '<th>Template Message To Customer</th><th>Updated</th><th>Actions</th>';
    echo '</tr></thead><tbody>';

    if ($rows === []) {
        $empty_msg = $bo_search !== ''
            ? 'No branch offices match your search.'
            : 'No branch offices yet.';
        echo '<tr><td colspan="8">' . esc_html($empty_msg) . '</td></tr>';
    } else {
        foreach ($rows as $row) {
            $row_id = (int) ($row['id'] ?? 0);

            $payload = $row;
            $b64 = base64_encode(wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            echo '<tr>';
            $dnCell = trim((string) ($row['display_name'] ?? ''));
            echo '<td>' . esc_html($dnCell !== '' ? $dnCell : '—') . '</td>';
            echo '<td>' . esc_html((string) ($row['cekat_name'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['cekat_phone_number'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['cekat_type'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['cekat_status'] ?? '')) . '</td>';
            $tname = trim((string) ($row['cekat_wa_template_name'] ?? ''));
            echo '<td>' . esc_html($tname !== '' ? $tname : '—') . '</td>';
            echo '<td>' . esc_html(eh_assessment_format_admin_datetime((string) ($row['updated_at'] ?? ''))) . '</td>';
            echo '<td>';
            echo '<button type="button" class="button button-small eh-bo-open-view" data-bo="' . esc_attr($b64) . '">View</button> ';
            if ($bo_status === 'active') {
                echo '<button type="button" class="button button-small eh-bo-open-edit" data-bo="' . esc_attr($b64) . '">Edit</button> ';
            }
            if ($bo_status === 'trash') {
                echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '" style="display:inline;margin-left:4px;">';
                wp_nonce_field('eh_restore_branch_outlet');
                echo '<input type="hidden" name="eh_assessment_action" value="restore_branch_outlet">';
                echo '<input type="hidden" name="branch_outlet_id" value="' . esc_attr((string) $row_id) . '">';
                echo '<button type="submit" class="button button-small">Restore</button></form>';
            } else {
                echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '" style="display:inline;margin-left:4px;">';
                wp_nonce_field('eh_trash_branch_outlet');
                echo '<input type="hidden" name="eh_assessment_action" value="trash_branch_outlet">';
                echo '<input type="hidden" name="branch_outlet_id" value="' . esc_attr((string) $row_id) . '">';
                echo '<button type="submit" class="button button-small" onclick="return confirm(\'Move this Branch Office to trash?\');">Delete</button></form>';
            }
            echo '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';

    echo '<div id="eh-bo-modal-view" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99999;align-items:center;justify-content:center;padding:24px;">';
    echo '<div style="background:#fff;width:100%;max-width:920px;border-radius:8px;box-shadow:0 24px 80px rgba(0,0,0,.18);max-height:92vh;overflow:auto;">';
    echo '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e5e5;">';
    echo '<h2 style="margin:0;font-size:18px;">' . esc_html('Branch office details') . '</h2>';
    echo '<button type="button" class="button-link" id="eh-bo-close-view" style="font-size:20px;text-decoration:none;">×</button>';
    echo '</div><div style="padding:20px;">';
    echo '<p class="description" style="margin-top:0;">' . esc_html('Read-only. Template preview is loaded from Cekat when available.') . '</p>';
    echo '<div id="eh-bo-view-fields"></div>';
    echo '<div id="eh-bo-view-template-preview" class="eh-bo-template-preview" style="margin-top:16px;padding:12px;border:1px solid #c3c4c7;border-radius:4px;background:#f6f7f7;display:none;"></div>';
    echo '</div></div></div>';

    $modal = function (string $id, string $title, string $close_id, string $form_inner): void {
        echo '<div id="' . esc_attr($id) . '" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99999;align-items:center;justify-content:center;padding:24px;">';
        echo '<div style="background:#fff;width:100%;max-width:920px;border-radius:8px;box-shadow:0 24px 80px rgba(0,0,0,.18);max-height:92vh;overflow:auto;">';
        echo '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e5e5;">';
        echo '<h2 style="margin:0;font-size:18px;">' . esc_html($title) . '</h2>';
        echo '<button type="button" class="button-link" id="' . esc_attr($close_id) . '" style="font-size:20px;text-decoration:none;">×</button>';
        echo '</div><div style="padding:20px;">' . $form_inner . '</div></div></div>';
    };

    ob_start();
    echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '">';
    wp_nonce_field('eh_save_branch_outlet');
    echo '<input type="hidden" name="eh_assessment_action" value="save_branch_outlet">';
    echo '<input type="hidden" name="branch_outlet_id" value="0">';
    echo '<p id="eh-bo-api-error" class="notice notice-error" style="display:none;">Could not load the list from the Cekat API.</p>';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row"><label for="eh-bo-api-select">Select from API</label></th><td><select id="eh-bo-api-select" style="min-width:280px;"><option value="">Loading…</option></select>';
    echo '<p class="description">Choose a Cekat inbox; inbox details below are read-only and saved from Cekat data.</p></td></tr>';
    echo '<tr><th scope="row">cekat_masking_id</th><td><code id="eh-bo-add-cekat_masking_display"></code>';
    echo '<input type="hidden" name="cekat_row_json" id="eh-bo-add-cekat_row_json" value=""></td></tr>';
    echo '<tr><th scope="row">cekat_name</th><td><code id="eh-bo-add-disp-cekat_name">—</code></td></tr>';
    echo '<tr><th scope="row">cekat_phone_number</th><td><code id="eh-bo-add-disp-cekat_phone_number">—</code></td></tr>';
    echo '<tr><th scope="row">cekat_type</th><td><code id="eh-bo-add-disp-cekat_type">—</code></td></tr>';
    echo '<tr><th scope="row">cekat_status</th><td><code id="eh-bo-add-disp-cekat_status">—</code></td></tr>';
    echo '<tr><th scope="row"><label for="eh-bo-add-display_name">' . esc_html('Display name') . '</label></th><td>';
    echo '<input type="text" class="regular-text" name="display_name" id="eh-bo-add-display_name" value="" maxlength="191" autocomplete="off" />';
    echo '<p class="description">' . esc_html('Shown on the public assessment branch selector. Leave empty to use the Cekat inbox name.') . '</p></td></tr>';
    echo '<tr><th scope="row"><label for="eh-bo-add-wa_template_select">Template Message To Customer</label></th><td>';
    echo '<select id="eh-bo-add-wa_template_select" style="min-width:320px;"><option value="">— Select inbox first —</option></select>';
    echo '<input type="hidden" name="cekat_wa_template_masking_id" id="eh-bo-add-cekat_wa_template_masking_id" value="">';
    echo '<input type="hidden" name="cekat_wa_template_name" id="eh-bo-add-cekat_wa_template_name" value="">';
    echo '<p class="description">Templates from Cekat <code>/templates</code> for this inbox (pick inbox above first).</p>';
    echo '<div id="eh-bo-add-template-preview" class="eh-bo-template-preview" style="margin-top:12px;padding:12px;border:1px solid #c3c4c7;border-radius:4px;background:#f6f7f7;display:none;"></div>';
    echo '</td></tr>';
    echo '</tbody></table>';
    submit_button('Save Branch Office');
    echo '</form>';
    $add_form = (string) ob_get_clean();
    $modal('eh-bo-modal-add', 'Add Branch Office', 'eh-bo-close-add', $add_form);

    ob_start();
    echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '">';
    wp_nonce_field('eh_save_branch_outlet');
    echo '<input type="hidden" name="eh_assessment_action" value="save_branch_outlet">';
    echo '<input type="hidden" name="branch_outlet_id" id="eh-bo-edit-branch_outlet_id" value="">';
    echo '<p class="description" style="margin-top:0;">' . esc_html('Inbox fields from Cekat are read-only. You can change the display name and the template message to customer here.') . '</p>';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row">cekat_masking_id</th><td><code id="eh-bo-edit-cekat_masking_display"></code></td></tr>';
    echo '<tr><th scope="row">cekat_name</th><td><code id="eh-bo-edit-disp-cekat_name">—</code></td></tr>';
    echo '<tr><th scope="row">cekat_phone_number</th><td><code id="eh-bo-edit-disp-cekat_phone_number">—</code></td></tr>';
    echo '<tr><th scope="row">cekat_type</th><td><code id="eh-bo-edit-disp-cekat_type">—</code></td></tr>';
    echo '<tr><th scope="row">cekat_status</th><td><code id="eh-bo-edit-disp-cekat_status">—</code></td></tr>';
    echo '<tr><th scope="row"><label for="eh-bo-edit-display_name">' . esc_html('Display name') . '</label></th><td>';
    echo '<input type="text" class="regular-text" name="display_name" id="eh-bo-edit-display_name" value="" maxlength="191" autocomplete="off" />';
    echo '<p class="description">' . esc_html('Shown on the public assessment branch selector. Leave empty to use the Cekat inbox name.') . '</p></td></tr>';
    echo '<tr><th scope="row"><label for="eh-bo-edit-wa_template_select">Template Message To Customer</label></th><td>';
    echo '<select id="eh-bo-edit-wa_template_select" style="min-width:320px;"><option value="">Loading…</option></select>';
    echo '<input type="hidden" name="cekat_wa_template_masking_id" id="eh-bo-edit-cekat_wa_template_masking_id" value="">';
    echo '<input type="hidden" name="cekat_wa_template_name" id="eh-bo-edit-cekat_wa_template_name" value="">';
    echo '<p class="description">Stored: template masking id and name only. Preview is loaded from Cekat.</p>';
    echo '<div id="eh-bo-edit-template-preview" class="eh-bo-template-preview" style="margin-top:12px;padding:12px;border:1px solid #c3c4c7;border-radius:4px;background:#f6f7f7;display:none;"></div>';
    echo '</td></tr>';
    echo '</tbody></table>';
    submit_button('Update Branch Office');
    echo '</form>';
    $edit_form = (string) ob_get_clean();
    $modal('eh-bo-modal-edit', 'Edit Branch Office', 'eh-bo-close-edit', $edit_form);

    echo '</div>';
}

function eh_assessment_render_hair_specialist_agents_page(): void
{
    if (!eh_assessment_user_is_administrator()) {
        wp_die('You do not have permission to access this page.');
    }

    global $wpdb;
    $agent_table = eh_hair_specialist_agent_table_name();
    $branch_table = eh_branch_outlet_table_name();
    $hsa_status = isset($_GET['hsa_status']) && (string) $_GET['hsa_status'] === 'trash' ? 'trash' : 'active';
    $search_term = sanitize_text_field((string) ($_GET['s'] ?? ''));
    $base = admin_url('admin.php?page=eh-hair-specialist-agents');

    echo '<div class="wrap">';
    echo '<h1>Hair Specialist Agents</h1>';

    if (isset($_GET['hsa_saved']) && (string) $_GET['hsa_saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Hair Specialist Agent saved.</p></div>';
    }
    if (isset($_GET['hsa_trashed']) && (string) $_GET['hsa_trashed'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Record moved to trash.</p></div>';
    }
    if (isset($_GET['hsa_restored']) && (string) $_GET['hsa_restored'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Record restored.</p></div>';
    }

    $hsa_err = sanitize_key((string) ($_GET['hsa_err'] ?? ''));
    $hsa_err_map = [
        'masking_required' => 'Select an agent from the API list (masking id is required).',
        'branch_required' => 'Branch Office is required and must be active.',
        'code_required' => 'Agent Code is required.',
        'code_invalid' => 'Agent Code may only contain letters, numbers, underscores, and hyphens (no spaces or other characters). It is used in the assessment page URL as a query parameter.',
        'duplicate_agent' => 'This agent is already in the database (including trash). Restore the existing row or pick another agent.',
        'duplicate_code' => 'Agent Code is already in use. Choose a unique code.',
        'not_found' => 'Record not found.',
        'restore_conflict' => 'Cannot restore: another active row already uses the same agent or agent code.',
    ];
    if ($hsa_err !== '' && isset($hsa_err_map[$hsa_err])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($hsa_err_map[$hsa_err]) . '</p></div>';
    } elseif ($hsa_err !== '') {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html('Could not save the Hair Specialist Agent.') . '</p></div>';
    }

    echo '<p style="margin:12px 0 16px;"><button type="button" class="button button-primary" id="eh-hsa-open-add">Add Hair Specialist Agent</button></p>';

    $active_tab_url = $search_term !== '' ? add_query_arg('s', $search_term, $base) : $base;
    $trash_tab_url = add_query_arg('hsa_status', 'trash', $base);
    if ($search_term !== '') {
        $trash_tab_url = add_query_arg('s', $search_term, $trash_tab_url);
    }

    echo '<ul class="subsubsub">';
    echo '<li><a href="' . esc_url($active_tab_url) . '"' . ($hsa_status === 'active' ? ' class="current"' : '') . '>Active</a> | </li>';
    echo '<li><a href="' . esc_url($trash_tab_url) . '"' . ($hsa_status === 'trash' ? ' class="current"' : '') . '>Trash</a></li>';
    echo '</ul>';

    $search_sql = '';
    $search_vals = [];
    if ($search_term !== '') {
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        $search_sql = ' AND (CAST(a.id AS CHAR) LIKE %s OR a.masking_id LIKE %s OR a.name LIKE %s OR a.email LIKE %s OR a.agent_code LIKE %s OR IFNULL(bo.cekat_name, \'\') LIKE %s OR IFNULL(bo.display_name, \'\') LIKE %s OR CAST(a.updated_at AS CHAR) LIKE %s)';
        $search_vals = [$like, $like, $like, $like, $like, $like, $like, $like];
    }

    $hsaBranchLabel = eh_assessment_branch_outlet_label_sql('bo');
    if ($hsa_status === 'trash') {
        $sql = "SELECT a.*, {$hsaBranchLabel} AS branch_outlet_name
           FROM {$agent_table} a
           LEFT JOIN {$branch_table} bo ON bo.id = a.branch_outlet_id
           WHERE a.deleted_at IS NOT NULL{$search_sql}
           ORDER BY a.updated_at DESC";
    } else {
        $sql = "SELECT a.*, {$hsaBranchLabel} AS branch_outlet_name
           FROM {$agent_table} a
           LEFT JOIN {$branch_table} bo ON bo.id = a.branch_outlet_id AND bo.deleted_at IS NULL
           WHERE a.deleted_at IS NULL{$search_sql}
           ORDER BY a.id DESC";
    }
    if ($search_vals !== []) {
        $sql = $wpdb->prepare($sql, ...$search_vals);
    }
    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (!is_array($rows)) {
        $rows = [];
    }

    echo '<form method="get" action="" style="margin:12px 0 16px;display:flex;gap:8px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">';
    echo '<input type="hidden" name="page" value="eh-hair-specialist-agents">';
    if ($hsa_status === 'trash') {
        echo '<input type="hidden" name="hsa_status" value="trash">';
    }
    echo '<input type="search" name="s" value="' . esc_attr($search_term) . '" class="regular-text" placeholder="Search ID, masking id, name, email, code, branch, updated…">';
    echo '<button type="submit" class="button">Search</button> ';
    $reset_href = $hsa_status === 'trash' ? add_query_arg('hsa_status', 'trash', $base) : $base;
    echo '<a class="button" href="' . esc_url($reset_href) . '">Reset</a>';
    echo '</form>';

    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>ID</th><th>Name</th><th>Email</th><th>Branch Office</th><th>Agent Code</th><th>Updated</th><th>Actions</th>';
    echo '</tr></thead><tbody>';

    if ($rows === []) {
        echo '<tr><td colspan="7">' . esc_html($search_term !== '' ? 'No agents match your search.' : 'No records yet.') . '</td></tr>';
    } else {
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $agent_code = (string) ($row['agent_code'] ?? '');
            $assessment_url = $agent_code !== '' ? eh_assessment_build_agent_assessment_public_url($agent_code) : '';
            $payload = $row;
            $b64 = base64_encode(wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            echo '<tr>';
            echo '<td>' . esc_html((string) $id) . '</td>';
            echo '<td>' . esc_html((string) ($row['name'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['email'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['branch_outlet_name'] ?? '')) . '</td>';
            echo '<td><code>' . esc_html($agent_code) . '</code></td>';
            echo '<td>' . esc_html(eh_assessment_format_admin_datetime((string) ($row['updated_at'] ?? ''))) . '</td>';
            echo '<td>';
            if ($hsa_status === 'trash') {
                echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '" style="display:inline;">';
                wp_nonce_field('eh_restore_hair_specialist_agent');
                echo '<input type="hidden" name="eh_assessment_action" value="restore_hair_specialist_agent">';
                echo '<input type="hidden" name="hair_specialist_agent_id" value="' . esc_attr((string) $id) . '">';
                echo '<button type="submit" class="button button-small">Restore</button></form>';
            } else {
                if ($assessment_url !== '') {
                    echo '<button type="button" class="button button-small eh-hsa-copy-link" data-url="' . esc_url($assessment_url) . '">Get Agent Assessment Link</button> ';
                }
                echo '<button type="button" class="button button-small eh-hsa-open-edit" data-row="' . esc_attr($b64) . '">Edit</button> ';
                echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '" style="display:inline;margin-left:4px;">';
                wp_nonce_field('eh_trash_hair_specialist_agent');
                echo '<input type="hidden" name="eh_assessment_action" value="trash_hair_specialist_agent">';
                echo '<input type="hidden" name="hair_specialist_agent_id" value="' . esc_attr((string) $id) . '">';
                echo '<button type="submit" class="button button-small" onclick="return confirm(\'Move this record to trash?\');">Delete</button></form>';
            }
            echo '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';

    $branch_opts = eh_assessment_get_active_branch_outlet_options();

    $modal = static function (string $id, string $title, string $close_id, string $form_inner): void {
        echo '<div id="' . esc_attr($id) . '" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99999;align-items:center;justify-content:center;padding:24px;">';
        echo '<div style="background:#fff;width:100%;max-width:720px;border-radius:8px;box-shadow:0 24px 80px rgba(0,0,0,.18);max-height:92vh;overflow:auto;">';
        echo '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e5e5;">';
        echo '<h2 style="margin:0;font-size:18px;">' . esc_html($title) . '</h2>';
        echo '<button type="button" class="button-link" id="' . esc_attr($close_id) . '" style="font-size:20px;text-decoration:none;">×</button>';
        echo '</div><div style="padding:20px;">' . $form_inner . '</div></div></div>';
    };

    ob_start();
    echo '<form id="eh-hsa-form-add" method="post" action="' . esc_url(admin_url('admin.php')) . '">';
    wp_nonce_field('eh_save_hair_specialist_agent');
    echo '<input type="hidden" name="eh_assessment_action" value="save_hair_specialist_agent">';
    echo '<input type="hidden" name="hair_specialist_agent_id" value="0">';
    echo '<p id="eh-hsa-api-error" class="notice notice-error" style="display:none;">Could not load agents from the Cekat API. Check OPENAPI_CEKAT and OPENAPI_CEKAT_KEY.</p>';
    echo '<p id="eh-hsa-add-form-error" class="notice notice-error" style="display:none;margin-top:0;" role="alert"></p>';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row"><label for="eh-hsa-add-api-select">Agent (from API)</label></th><td>';
    echo '<select id="eh-hsa-add-api-select" style="min-width:320px;"><option value="">Loading…</option></select>';
    echo '<p class="description">List from <code>/api/agents</code>. The API <code>id</code> is stored as <code>masking_id</code>.</p></td></tr>';
    echo '<tr><th scope="row">masking_id</th><td><code id="eh-hsa-add-masking-display"></code>';
    echo '<input type="hidden" name="masking_id" id="eh-hsa-add-masking-id" value=""></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hsa-add-agent-name">Name</label></th><td>';
    echo '<input class="regular-text" type="text" name="agent_name" id="eh-hsa-add-agent-name" value="" required readonly autocomplete="off" style="background-color:#f6f7f7;cursor:default;">';
    echo '<p class="description" style="margin-top:6px;">Taken from Cekat when you select an agent above; cannot be edited.</p></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hsa-add-agent-email">Email</label></th><td>';
    echo '<input class="regular-text" type="email" name="agent_email" id="eh-hsa-add-agent-email" value="" readonly autocomplete="off" style="background-color:#f6f7f7;cursor:default;">';
    echo '<p class="description" style="margin-top:6px;">Taken from Cekat; cannot be edited.</p></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hsa-add-branch">Branch Office</label></th><td><select name="branch_outlet_id" id="eh-hsa-add-branch" required style="min-width:280px;">';
    echo '<option value="">Select…</option>';
    foreach ($branch_opts as $bo) {
        $bid = (int) ($bo['id'] ?? 0);
        if ($bid <= 0) {
            continue;
        }
        echo '<option value="' . esc_attr((string) $bid) . '">' . esc_html((string) ($bo['cekat_name'] ?? '')) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hsa-add-agent-code">Agent Code</label></th><td><input class="regular-text" type="text" name="agent_code" id="eh-hsa-add-agent-code" required maxlength="64" pattern="[A-Za-z0-9_-]+" autocomplete="off" title="Letters, numbers, underscore, and hyphen only. No spaces.">';
    echo '<p class="description">Letters, numbers, underscore, and hyphen only—no spaces or other special characters. Used as the <code>code</code> query parameter on the public assessment page (must stay URL-safe).</p></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Hair Specialist Agent');
    echo '</form>';
    $add_form = (string) ob_get_clean();
    $modal('eh-hsa-modal-add', 'Add Hair Specialist Agent', 'eh-hsa-close-add', $add_form);

    ob_start();
    echo '<form id="eh-hsa-form-edit" method="post" action="' . esc_url(admin_url('admin.php')) . '">';
    wp_nonce_field('eh_save_hair_specialist_agent');
    echo '<input type="hidden" name="eh_assessment_action" value="save_hair_specialist_agent">';
    echo '<input type="hidden" name="hair_specialist_agent_id" id="eh-hsa-edit-row-id" value="">';
    echo '<p id="eh-hsa-edit-form-error" class="notice notice-error" style="display:none;margin-top:0;" role="alert"></p>';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row">masking_id</th><td><code id="eh-hsa-edit-masking-display"></code>';
    echo '<input type="hidden" name="masking_id" id="eh-hsa-edit-masking-id" value=""></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hsa-edit-agent-name">Name</label></th><td>';
    echo '<input class="regular-text" type="text" name="agent_name" id="eh-hsa-edit-agent-name" value="" required readonly autocomplete="off" style="background-color:#f6f7f7;cursor:default;">';
    echo '<p class="description" style="margin-top:6px;">From Cekat; cannot be edited here.</p></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hsa-edit-agent-email">Email</label></th><td>';
    echo '<input class="regular-text" type="email" name="agent_email" id="eh-hsa-edit-agent-email" value="" readonly autocomplete="off" style="background-color:#f6f7f7;cursor:default;">';
    echo '<p class="description" style="margin-top:6px;">From Cekat; cannot be edited here.</p></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hsa-edit-branch">Branch Office</label></th><td><select name="branch_outlet_id" id="eh-hsa-edit-branch" required style="min-width:280px;">';
    echo '<option value="">Select…</option>';
    foreach ($branch_opts as $bo) {
        $bid = (int) ($bo['id'] ?? 0);
        if ($bid <= 0) {
            continue;
        }
        echo '<option value="' . esc_attr((string) $bid) . '">' . esc_html((string) ($bo['cekat_name'] ?? '')) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hsa-edit-agent-code">Agent Code</label></th><td><input class="regular-text" type="text" name="agent_code" id="eh-hsa-edit-agent-code" required maxlength="64" pattern="[A-Za-z0-9_-]+" autocomplete="off" title="Letters, numbers, underscore, and hyphen only. No spaces.">';
    echo '<p class="description">Same rules as when adding: URL-safe for the assessment <code>?code=</code> link—no spaces or special characters.</p></td></tr>';
    echo '</tbody></table>';
    submit_button('Update Hair Specialist Agent');
    echo '</form>';
    $edit_form = (string) ob_get_clean();
    $modal('eh-hsa-modal-edit', 'Edit Hair Specialist Agent', 'eh-hsa-close-edit', $edit_form);

    echo '<span id="eh-hsa-copy-toast" style="display:none;position:fixed;bottom:24px;right:24px;background:#1d2327;color:#fff;padding:10px 16px;border-radius:6px;z-index:100000;">Link copied to clipboard</span>';
    echo '</div>';
}

function eh_assessment_render_report_preview_page(): void
{
    if (!eh_assessment_current_user_can_access_admin()) {
        wp_die('You do not have permission to access this page.');
    }

    require __DIR__ . '/report-preview.php';
    exit;
}

/**
 * Pre-Consultation report template form: grouped sections (modal add/edit).
 *
 * @return list<array{title: string, fields: list<array{0: string, 1: string, 2: string, 3: string}>}>
 */
function eh_assessment_report_pdf_template_form_sections(): array
{
    return [
        [
            'title' => 'Report name',
            'fields' => [
                ['rpt_report_title', 'Report name', 'text', 'report_title'],
            ],
        ],
        [
            'title' => 'Section header',
            'fields' => [
                ['rpt_report_header_title', 'Report header title', 'text', 'report_header_title'],
                ['rpt_subtitle', 'Subtitle', 'text', 'subtitle'],
            ],
        ],
        [
            'title' => 'Section greeting',
            'fields' => [
                ['rpt_greeting_description', 'Greeting description', 'wysiwyg', 'greeting_description'],
            ],
        ],
        [
            'title' => 'Section diagnosis',
            'fields' => [
                ['rpt_diagnosis_name', 'Diagnosis name', 'wysiwyg', 'diagnosis_name'],
                ['rpt_diagnosis_name_detail', 'Diagnosis name detail', 'text', 'diagnosis_name_detail'],
            ],
        ],
        [
            'title' => 'Section condition explanation',
            'fields' => [
                ['rpt_title_condition_explanation', 'Title condition explanation', 'text', 'title_condition_explanation'],
                ['rpt_description_condition_explanation', 'Description condition explanation', 'wysiwyg', 'description_condition_explanation'],
            ],
        ],
        [
            'title' => 'Section clinical knowledge',
            'fields' => [
                ['rpt_title_clinical_knowledge', 'Title clinical knowledge', 'text', 'title_clinical_knowledge'],
                ['rpt_subtitle_clinical_knowledge', 'Subtitle clinical knowledge', 'text', 'subtitle_clinical_knowledge'],
                ['rpt_image_clinical_knowledge', 'Image clinical knowledge', 'image', 'image_clinical_knowledge'],
                ['rpt_description_clinical_knowledge', 'Description clinical knowledge', 'wysiwyg', 'description_clinical_knowledge'],
            ],
        ],
        [
            'title' => 'Section evaluation urgency',
            'fields' => [
                ['rpt_title_evaluation_urgency', 'Title evaluation urgency', 'text', 'title_evaluation_urgency'],
                ['rpt_description_evaluation_urgency', 'Description evaluation urgency', 'wysiwyg', 'description_evaluation_urgency'],
            ],
        ],
        [
            'title' => 'Section treatment journey',
            'fields' => [
                ['rpt_title_treatment_journey', 'Title treatment journey', 'text', 'title_treatment_journey'],
                ['rpt_description_treatment_journey', 'Description treatment journey', 'wysiwyg', 'description_treatment_journey'],
                ['rpt_image_treatment_journey', 'Image treatment journey', 'image', 'image_treatment_journey'],
            ],
        ],
        [
            'title' => 'Section recommendation approach',
            'fields' => [
                ['rpt_title_recommendation_approach', 'Title recommendation approach', 'text', 'title_recommendation_approach'],
                ['rpt_description_recommendation_approach', 'Description recommendation approach', 'wysiwyg', 'description_recommendation_approach'],
                ['rpt_detail_recommendation_approach', 'Detail recommendation approach', 'wysiwyg', 'detail_recommendation_approach'],
                ['rpt_bottom_description_recommendation_approach', 'Bottom description recommendation approach', 'wysiwyg', 'bottom_description_recommendation_approach'],
            ],
        ],
        [
            'title' => 'Section next steps',
            'fields' => [
                ['rpt_title_next_steps', 'Title next steps', 'text', 'title_next_steps'],
                ['rpt_description_next_steps', 'Description next steps', 'wysiwyg', 'description_next_steps'],
            ],
        ],
        [
            'title' => 'Section medical notes',
            'fields' => [
                ['rpt_title_medical_notes', 'Title medical notes', 'text', 'title_medical_notes'],
                ['rpt_body_medical_notes', 'Description medical notes', 'wysiwyg', 'body_medical_notes'],
            ],
        ],
        [
            'title' => 'Section footer statement',
            'fields' => [
                ['rpt_description_medical_notes', 'Footer statement', 'wysiwyg', 'description_medical_notes'],
            ],
        ],
    ];
}

/**
 * @return list<array{0: string, 1: string, 2: string, 3: string}>
 */
function eh_assessment_report_pdf_template_flat_fields(): array
{
    $out = [];
    foreach (eh_assessment_report_pdf_template_form_sections() as $section) {
        foreach ($section['fields'] as $field) {
            $out[] = $field;
        }
    }

    return $out;
}

/**
 * @param array{0: string, 1: string, 2: string, 3: string} $f
 */
function eh_assessment_render_report_pdf_template_field_row(array $f): void
{
    [$fid, $label, $type] = $f;
    echo '<tr><th scope="row"><label for="' . esc_attr($fid) . '">' . esc_html($label) . '</label></th><td>';
    if ($type === 'textarea') {
        echo '<textarea name="' . esc_attr($fid) . '" id="' . esc_attr($fid) . '" class="large-text" rows="3" cols="50"></textarea>';
    } elseif ($type === 'wysiwyg') {
        echo '<div class="eh-rpt-wysiwyg-wrap" style="max-width:100%;">';
        echo '<textarea name="' . esc_attr($fid) . '" id="' . esc_attr($fid) . '" class="large-text eh-rpt-wysiwyg" rows="5" cols="50"></textarea>';
        echo '</div>';
        echo '<p class="description" style="margin-top:6px;">' . esc_html('Rich text; stored HTML is sanitized on save (same as post content).') . '</p>';
    } elseif ($type === 'image') {
        echo '<input type="hidden" name="' . esc_attr($fid) . '" id="' . esc_attr($fid) . '" value="" autocomplete="off" />';
        echo '<div id="' . esc_attr($fid) . '_preview_wrap" class="eh-rpt-media-preview-wrap" style="margin:0 0 10px;">';
        echo '<img id="' . esc_attr($fid) . '_preview" src="" alt="" style="max-height:160px;max-width:100%;border:1px solid #c3c4c7;border-radius:6px;display:none;" />';
        echo '</div>';
        echo '<p class="eh-rpt-media-actions" style="margin:0;">';
        echo '<button type="button" class="button eh-rpt-media-select" data-target="' . esc_attr($fid) . '">' . esc_html('Select or upload image') . '</button> ';
        echo '<button type="button" class="button eh-rpt-media-clear" data-target="' . esc_attr($fid) . '">' . esc_html('Remove image') . '</button>';
        echo '</p>';
        echo '<p class="description" style="margin-top:8px;">' . esc_html('Stored value is the image URL (suitable for PDF generation).') . '</p>';
    } else {
        echo '<input name="' . esc_attr($fid) . '" id="' . esc_attr($fid) . '" type="text" class="large-text" value="" autocomplete="off" />';
    }
    echo '</td></tr>';
}

function eh_assessment_render_report_pdf_templates_page(): void
{
    if (!eh_assessment_current_user_can_access_admin()) {
        wp_die('You do not have permission to access this page.');
    }

    global $wpdb;
    $table = eh_assessment_report_pdf_template_table_name();

    echo '<div class="wrap">';
    echo '<h1>Report PDF templates</h1>';
    echo '<p class="description" style="margin-top:0;">Custom rows for PDF content mapping. Each row has a unique <strong>masking id</strong> for stable references. Use <strong>Add template</strong> or <strong>Edit</strong> in the table to open the form in a modal.</p>';

    if (isset($_GET['rpt_saved']) && (string) $_GET['rpt_saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Template saved.</p></div>';
    }
    if (isset($_GET['rpt_trashed']) && (string) $_GET['rpt_trashed'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Template moved to trash.</p></div>';
    }
    if (isset($_GET['rpt_restored']) && (string) $_GET['rpt_restored'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Template restored.</p></div>';
    }
    $rpt_err = sanitize_key((string) ($_GET['rpt_err'] ?? ''));
    if ($rpt_err === 'dup_mask') {
        echo '<div class="notice notice-error is-dismissible"><p>That masking id is already in use. Choose another or leave blank to auto-generate.</p></div>';
    } elseif ($rpt_err === 'not_found') {
        echo '<div class="notice notice-error is-dismissible"><p>Template not found.</p></div>';
    }

    $rpt_status = isset($_GET['rpt_status']) && (string) $_GET['rpt_status'] === 'trash' ? 'trash' : 'active';
    $base = admin_url('admin.php?page=eh-assessment-report-pdf-templates');
    $active_url = $base;
    $trash_url = add_query_arg('rpt_status', 'trash', $base);

    if ($rpt_status === 'trash') {
        $sql = "SELECT id, masking_id, report_title, diagnosis_name, updated_at, deleted_at FROM {$table} WHERE deleted_at IS NOT NULL ORDER BY created_at ASC";
    } else {
        $sql = "SELECT id, masking_id, report_title, diagnosis_name, updated_at FROM {$table} WHERE deleted_at IS NULL ORDER BY created_at ASC";
    }
    $list = $wpdb->get_results($sql, ARRAY_A);
    if (!is_array($list)) {
        $list = [];
    }

    echo '<h2 class="title" style="margin-top:24px;">All templates</h2>';
    echo '<div class="eh-rpt-toolbar" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin:8px 0 12px;">';
    echo '<ul class="subsubsub" style="margin:0;float:none;">';
    echo '<li><a href="' . esc_url($active_url) . '"' . ($rpt_status === 'active' ? ' class="current"' : '') . '>Active</a> | </li>';
    echo '<li><a href="' . esc_url($trash_url) . '"' . ($rpt_status === 'trash' ? ' class="current"' : '') . '>Trash</a></li>';
    echo '</ul>';
    if ($rpt_status === 'active') {
        echo '<p style="margin:0;"><button type="button" class="button button-primary" id="eh-rpt-open-add">' . esc_html('Add template') . '</button></p>';
    } else {
        echo '<p style="margin:0;"></p>';
    }
    echo '</div>';

    echo '<table class="widefat striped"><thead><tr>';
    if ($rpt_status === 'trash') {
        echo '<th>Masking ID</th><th>Report name</th><th>Diagnosis name</th><th>Deleted at</th><th>Actions</th>';
    } else {
        echo '<th>Masking ID</th><th>Report name</th><th>Diagnosis name</th><th>Updated</th><th>Actions</th>';
    }
    echo '</tr></thead><tbody>';
    if ($list === []) {
        $empty = $rpt_status === 'trash' ? 'Trash is empty.' : 'No templates yet.';
        echo '<tr><td colspan="5">' . esc_html($empty) . '</td></tr>';
    } else {
        foreach ($list as $r) {
            $rid = (int) ($r['id'] ?? 0);
            echo '<tr>';
            echo '<td><code>' . esc_html((string) ($r['masking_id'] ?? '')) . '</code></td>';
            echo '<td>' . esc_html((string) ($r['report_title'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($r['diagnosis_name'] ?? '')) . '</td>';
            if ($rpt_status === 'trash') {
                echo '<td>' . esc_html(eh_assessment_format_admin_datetime((string) ($r['deleted_at'] ?? ''))) . '</td>';
                echo '<td>';
                echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '" style="display:inline;">';
                wp_nonce_field('eh_restore_report_pdf_template');
                echo '<input type="hidden" name="eh_assessment_action" value="restore_report_pdf_template" />';
                echo '<input type="hidden" name="report_pdf_template_id" value="' . esc_attr((string) $rid) . '" />';
                echo '<button type="submit" class="button button-small">' . esc_html('Restore') . '</button></form>';
                echo '</td>';
            } else {
                echo '<td>' . esc_html(eh_assessment_format_admin_datetime((string) ($r['updated_at'] ?? ''))) . '</td>';
                echo '<td><button type="button" class="button button-small eh-rpt-open-edit" data-template-id="' . esc_attr((string) $rid) . '">' . esc_html('Edit') . '</button> ';
                echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '" style="display:inline;margin-left:4px;">';
                wp_nonce_field('eh_delete_report_pdf_template');
                echo '<input type="hidden" name="eh_assessment_action" value="delete_report_pdf_template" />';
                echo '<input type="hidden" name="report_pdf_template_id" value="' . esc_attr((string) $rid) . '" />';
                echo '<button type="submit" class="button button-small" onclick="return confirm(\'Move this template to the trash?\');">' . esc_html('Trash') . '</button></form></td>';
            }
            echo '</tr>';
        }
    }
    echo '</tbody></table>';

    echo '<div id="eh-rpt-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000;align-items:center;justify-content:center;padding:20px;overflow:auto;">';
    echo '<div style="background:#fff;width:100%;max-width:880px;border-radius:8px;box-shadow:0 24px 80px rgba(0,0,0,.18);max-height:92vh;overflow:auto;margin:auto;">';
    echo '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e5e5;">';
    echo '<h2 id="eh-rpt-modal-title" style="margin:0;font-size:18px;">' . esc_html('New template') . '</h2>';
    echo '<button type="button" class="button-link" id="eh-rpt-modal-close" style="font-size:20px;text-decoration:none;line-height:1;" aria-label="' . esc_attr('Close') . '">&times;</button>';
    echo '</div>';
    echo '<div style="padding:16px 20px 20px;">';
    echo '<p id="eh-rpt-modal-loading" class="description" style="display:none;margin-top:0;">' . esc_html('Loading…') . '</p>';
    echo '<form id="eh-rpt-form" method="post" action="' . esc_url(admin_url('admin.php')) . '">';
    wp_nonce_field('eh_save_report_pdf_template');
    echo '<input type="hidden" name="eh_assessment_action" value="save_report_pdf_template" />';
    echo '<input type="hidden" name="report_pdf_template_id" id="eh-rpt-field-id" value="0" />';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row"><label for="rpt_masking_id">Masking ID</label></th><td>';
    echo '<input name="rpt_masking_id" id="rpt_masking_id" type="text" class="regular-text" value="" maxlength="' . esc_attr((string) EH_ASSESSMENT_REPORT_PDF_MASKING_ID_MAX_LENGTH) . '" autocomplete="off" />';
    echo '<p class="description" id="eh-rpt-masking-help">' . esc_html('Optional on create. Leave blank to auto-generate. Read-only when editing.') . '</p>';
    echo '</td></tr>';
    echo '</tbody></table>';

    foreach (eh_assessment_report_pdf_template_form_sections() as $section) {
        $section_title = (string) ($section['title'] ?? '');
        echo '<section class="eh-rpt-section-card" style="border:1px solid #dcdcde;border-radius:6px;padding:14px 16px;margin:0 0 14px;background:#f6f7f7;">';
        echo '<h3 class="eh-rpt-section-card__title" style="margin:0 0 12px;padding:0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#1d2327;border-bottom:1px solid #dcdcde;padding-bottom:8px;">' . esc_html($section_title) . '</h3>';
        echo '<table class="form-table" role="presentation" style="margin:0;"><tbody>';
        foreach ($section['fields'] as $field) {
            eh_assessment_render_report_pdf_template_field_row($field);
        }
        echo '</tbody></table>';
        echo '</section>';
    }
    echo '<p class="submit" style="margin-bottom:0;padding-bottom:0;">';
    echo '<button type="submit" class="button button-primary">' . esc_html('Save template') . '</button> ';
    echo '<button type="button" class="button" id="eh-rpt-modal-cancel">' . esc_html('Cancel') . '</button>';
    echo '</p>';
    echo '</form>';
    echo '</div></div></div>';

    echo '</div>';
}

function eh_assessment_render_submissions_page(): void
{
    if (!eh_assessment_current_user_can_access_admin()) {
        wp_die('You do not have permission to access this page.');
    }

    global $wpdb;
    $assessment_table = eh_assessment_table_name();
    $branch_table = eh_branch_outlet_table_name();
    $view = sanitize_key((string) ($_GET['view'] ?? 'list'));
    eh_assessment_mark_stale_submissions_failed();

    echo '<div class="wrap">';
    echo '<h1>Assessment Submissions</h1>';

    if ($view === 'detail') {
        $submission_id = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
        if ($submission_id <= 0 && isset($_GET['eh_submission_id'])) {
            $submission_id = (int) $_GET['eh_submission_id'];
        }
        $submission = eh_assessment_get_submission_detail_row($submission_id);

        if (!$submission) {
            echo '<p>Submission not found.</p></div>';
            return;
        }

        $payload = json_decode((string) $submission['payload_json'], true);
        $payload = is_array($payload) ? $payload : [];
        $branch_options = eh_assessment_get_active_branch_outlet_options();

        if (isset($_GET['updated']) && $_GET['updated'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Submission updated.</p></div>';
        }
        if (isset($_GET['eh_saved']) && $_GET['eh_saved'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Submission created.</p></div>';
        }
        if (isset($_GET['resend_ok']) && $_GET['resend_ok'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Notification resent to Cekat.</p></div>';
        }
        if (isset($_GET['resend_err']) && $_GET['resend_err'] !== '') {
            echo '<div class="notice notice-error is-dismissible"><p>Could not resend notification.</p></div>';
        }

        $status_color = match ((string) $submission['status']) {
            'Complete' => ['bg' => '#ecfdf3', 'text' => '#027a48', 'border' => '#abefc6'],
            'Failed' => ['bg' => '#fef3f2', 'text' => '#b42318', 'border' => '#fecdca'],
            default => ['bg' => '#eff8ff', 'text' => '#175cd3', 'border' => '#b2ddff'],
        };
        $detail_card_style = 'background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:20px;box-shadow:0 1px 2px rgba(16,24,40,.04); margin-bottom:20px;';
        $meta_item_style = 'background:#f8fafc;border:1px solid #eaecf0;border-radius:10px;padding:14px 16px;';

        echo '<style>
            .eh-assessment-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
            .eh-assessment-actions{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 20px;}
            .eh-assessment-topbar{display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap;margin:0 0 16px;}
            .eh-assessment-section{margin-top:20px;}
            .eh-assessment-answer-table th{width:160px;}
            @media (max-width: 900px){
                .eh-assessment-detail-grid{grid-template-columns:1fr;}
            }
        </style>';
        echo '<div class="eh-assessment-topbar">';
        $back_list = admin_url('admin.php?page=eh-assessment-submissions');
        echo '<p style="margin:0;"><a href="' . esc_url($back_list) . '">&larr; Back to submissions</a></p>';
        echo '<div class="eh-assessment-actions" style="margin:0;">';
        if ((string) $submission['status'] === 'Failed') {
            echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '" style="display:inline;">';
            wp_nonce_field('eh_resend_submission_notification');
            echo '<input type="hidden" name="eh_assessment_action" value="resend_submission_notification">';
            echo '<input type="hidden" name="submission_id" value="' . esc_attr((string) $submission_id) . '">';
            echo '<button type="submit" class="button">Resend Notification</button>';
            echo '</form> ';
        }
        echo eh_assessment_render_report_download_link((int) $submission['id'], 'button button-primary');
        echo '</div>';
        echo '</div>';
        echo '<div style="' . esc_attr($detail_card_style) . '">';
        echo '<div style="display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:18px;">';
        echo '<h2 style="margin:0;font-size:18px;line-height:1.3;font-weight:600;">' . esc_html('Submission ' . (string) $submission['masked_id']) . '</h2>';
        echo '<span style="display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;border:1px solid ' . esc_attr($status_color['border']) . ';background:' . esc_attr($status_color['bg']) . ';color:' . esc_attr($status_color['text']) . ';font-weight:600;">' . esc_html((string) $submission['status']) . '</span>';
        echo '</div>';
        echo '<div class="eh-assessment-detail-grid">';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Masked ID</div><div style="font-size:16px;font-weight:600;">' . esc_html((string) $submission['masked_id']) . '</div></div>';
        $branch_display = trim((string) ($submission['branch_outlet_name'] ?? ''));
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Branch Office</div><div style="font-size:16px;font-weight:600;">' . esc_html($branch_display !== '' ? $branch_display : '—') . '</div></div>';
        $agent_name_display = trim((string) ($submission['agent_name'] ?? ''));
        echo '<div style="' . esc_attr($meta_item_style) . ';grid-column:1/-1;"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Agent name</div><div style="font-size:16px;font-weight:600;">' . esc_html($agent_name_display !== '' ? $agent_name_display : '—') . '</div></div>';
        $name_display = trim((string) ($submission['respondent_name'] ?? ''));
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Name</div><div style="font-size:16px;font-weight:600;">' . esc_html($name_display !== '' ? $name_display : '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Gender</div><div style="font-size:16px;font-weight:600;text-transform:capitalize;">' . esc_html((string) $submission['respondent_gender']) . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">WhatsApp</div><div style="font-size:16px;font-weight:600;">' . esc_html((string) $submission['respondent_whatsapp']) . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Submitted At</div><div style="font-size:16px;font-weight:600;">' . esc_html(eh_assessment_format_admin_datetime((string) $submission['submitted_at'])) . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Updated At</div><div style="font-size:16px;font-weight:600;">' . esc_html(eh_assessment_format_admin_datetime((string) $submission['updated_at'])) . '</div></div>';
        echo '</div>';
        echo '</div>';

        $comp_payload = is_array($payload['computed'] ?? null) ? $payload['computed'] : [];
        $sal_detail = (string) ($comp_payload['computed_salutation'] ?? eh_assessment_salutation_id((string) ($submission['respondent_gender'] ?? '')));
        $src_detail = trim((string) ($submission['lead_source'] ?? ''));
        if ($src_detail === '' && isset($comp_payload['lead_source'])) {
            $src_detail = (string) $comp_payload['lead_source'];
        }
        $rpt_n = isset($submission['computed_report_type']) && $submission['computed_report_type'] !== null && $submission['computed_report_type'] !== ''
            ? (int) $submission['computed_report_type']
            : (int) ($comp_payload['computed_report_type'] ?? 0);
        $sc_n = isset($submission['computed_score']) && $submission['computed_score'] !== null && $submission['computed_score'] !== ''
            ? (int) $submission['computed_score']
            : (int) ($comp_payload['computed_score'] ?? 0);

        echo '<div class="eh-assessment-section" style="' . esc_attr($detail_card_style) . '">';
        echo '<h2 style="margin-top:0;">Computed assessment (EUROHAIRLAB)</h2>';
        echo '<div class="eh-assessment-detail-grid">';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Salutation</div><div style="font-size:16px;font-weight:600;">' . esc_html($sal_detail) . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Source</div><div style="font-size:16px;font-weight:600;">' . esc_html($src_detail !== '' ? $src_detail : '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Report type (1–8)</div><div style="font-size:16px;font-weight:600;">' . esc_html($rpt_n > 0 ? (string) $rpt_n : '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Profil klinis (condition title)</div><div style="font-size:16px;font-weight:600;">' . esc_html(trim((string) ($submission['computed_condition_title'] ?? $comp_payload['computed_condition_title'] ?? '')) ?: '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Score /100</div><div style="font-size:16px;font-weight:600;">' . esc_html($sc_n > 0 ? (string) $sc_n : '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Band</div><div style="font-size:16px;font-weight:600;">' . esc_html(trim((string) ($submission['computed_band'] ?? $comp_payload['computed_band'] ?? '')) ?: '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Tipe pasien</div><div style="font-size:16px;font-weight:600;">' . esc_html(isset($submission['computed_patient_type']) && $submission['computed_patient_type'] !== null && $submission['computed_patient_type'] !== ''
            ? 'Tipe ' . (string) (int) $submission['computed_patient_type']
            : (isset($comp_payload['computed_patient_type']) ? 'Tipe ' . (string) (int) $comp_payload['computed_patient_type'] : '—')) . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . ';grid-column:1/-1;"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Strategi komunikasi</div><div style="font-size:15px;font-weight:600;">' . esc_html(trim((string) ($submission['computed_communication_strategy'] ?? $comp_payload['computed_communication_strategy'] ?? '')) ?: '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . ';grid-column:1/-1;"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Jalur pemeliharaan</div><div style="font-size:15px;font-weight:600;">' . esc_html(trim((string) ($submission['computed_maintenance_path'] ?? $comp_payload['computed_maintenance_path'] ?? '')) ?: '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . ';grid-column:1/-1;"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Peringatan klinis</div><div style="font-size:15px;font-weight:600;">' . esc_html(trim((string) ($submission['computed_clinical_warnings'] ?? $comp_payload['computed_clinical_warnings'] ?? '')) ?: '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . ';grid-column:1/-1;"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Urgency text</div><div style="font-size:14px;line-height:1.45;">' . esc_html(trim((string) ($submission['computed_urgency_text'] ?? $comp_payload['computed_urgency_text'] ?? '')) ?: '—') . '</div></div>';
        $vs = isset($submission['score_visual_scalp']) ? (int) $submission['score_visual_scalp'] : (int) ($comp_payload['score_visual_scalp'] ?? 0);
        $vf = isset($submission['score_visual_follicle']) ? (int) $submission['score_visual_follicle'] : (int) ($comp_payload['score_visual_follicle'] ?? 0);
        $vt = isset($submission['score_visual_thinning_risk']) ? (int) $submission['score_visual_thinning_risk'] : (int) ($comp_payload['score_visual_thinning_risk'] ?? 0);
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Skor visual kulit kepala</div><div style="font-size:16px;font-weight:600;">' . esc_html($vs > 0 ? $vs . '/10' : '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Skor visual folikel</div><div style="font-size:16px;font-weight:600;">' . esc_html($vf > 0 ? $vf . '/10' : '—') . '</div></div>';
        echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Skor visual risiko penipisan</div><div style="font-size:16px;font-weight:600;">' . esc_html($vt > 0 ? $vt . '/10' : '—') . '</div></div>';
        $letters = is_array($comp_payload['answer_letters'] ?? null) ? $comp_payload['answer_letters'] : [];
        if ($letters !== []) {
            $bits = [];
            foreach ($letters as $qk => $lv) {
                $bits[] = $qk . '=' . $lv;
            }
            echo '<div style="' . esc_attr($meta_item_style) . ';grid-column:1/-1;"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Normalized answers (letters)</div><div style="font-size:13px;font-family:ui-monospace,monospace;word-break:break-all;">' . esc_html(implode(', ', $bits)) . '</div></div>';
        }
        echo '</div></div>';

        $payload_for_quick = is_array($payload) ? $payload : [];
        $quick_view_text = eh_assessment_submission_quick_view_text($submission, $payload_for_quick, $submission_id);
        echo '<div class="eh-assessment-section eh-assessment-quick-view" style="' . esc_attr($detail_card_style) . '">';
        echo '<h2 style="margin-top:0;">Quick view (konsultan)</h2>';
        echo '<p class="description" style="margin-top:0;">Ringkasan lead format <code>[NEW LEAD]</code> — siap salin untuk WhatsApp atau catatan internal.</p>';
        echo '<textarea id="eh-assessment-quick-view-text" readonly rows="22" style="width:100%;max-width:100%;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;line-height:1.45;border:1px solid #d0d5dd;border-radius:10px;padding:14px;background:#0b1020;color:#f8fafc;box-sizing:border-box;">' . esc_textarea($quick_view_text) . '</textarea>';
        echo '<p style="margin:12px 0 0;"><button type="button" class="button" id="eh-assessment-quick-view-copy">Copy to clipboard</button></p>';
        echo '</div>';
        echo '<script>(function(){var b=document.getElementById("eh-assessment-quick-view-copy"),t=document.getElementById("eh-assessment-quick-view-text");if(!b||!t)return;var def=b.textContent;b.addEventListener("click",function(){t.focus();t.select();var done=function(){b.textContent="Copied!";setTimeout(function(){b.textContent=def},2000);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t.value).then(done).catch(function(){try{document.execCommand("copy");done();}catch(e2){}});}else{try{document.execCommand("copy");done();}catch(e){}}});})();</script>';

        if (!empty($submission['cekat_masking_id'])) {
            echo '<div class="eh-assessment-section" style="' . esc_attr($detail_card_style) . '">';
            echo '<h2 style="margin-top:0;">Cekat inbox</h2>';
            echo '<div class="eh-assessment-detail-grid">';
            $cekat_fields = [
                'cekat_masking_id' => 'Inbox masking ID',
                'cekat_name' => 'Name',
                'cekat_phone_number' => 'Phone (WhatsApp)',
                'cekat_type' => 'Type',
                'cekat_status' => 'Status',
                'cekat_business_id' => 'Business ID',
                'cekat_created_at' => 'Created at',
                'cekat_ai_agent_id' => 'AI agent ID',
                'cekat_image_url' => 'Image URL',
            ];
            foreach ($cekat_fields as $col => $lab) {
                $val = (string) ($submission[$col] ?? '');
                if ($val === '' && $col !== 'cekat_masking_id') {
                    continue;
                }
                echo '<div style="' . esc_attr($meta_item_style) . '"><div style="font-size:12px;color:#667085;margin-bottom:6px;">' . esc_html($lab) . '</div><div style="font-size:15px;font-weight:600;word-break:break-all;">' . esc_html($val !== '' ? $val : '—') . '</div></div>';
            }
            $desc = (string) ($submission['cekat_description'] ?? '');
            if ($desc !== '') {
                echo '<div style="' . esc_attr($meta_item_style) . ';grid-column:1/-1;"><div style="font-size:12px;color:#667085;margin-bottom:6px;">Description</div><div style="font-size:15px;">' . esc_html($desc) . '</div></div>';
            }
            $agent_json = (string) ($submission['cekat_ai_agent_json'] ?? '');
            if ($agent_json !== '') {
                echo '<div style="' . esc_attr($meta_item_style) . ';grid-column:1/-1;"><div style="font-size:12px;color:#667085;margin-bottom:6px;">AI agent (JSON)</div><pre style="margin:0;white-space:pre-wrap;font-size:13px;">' . esc_html($agent_json) . '</pre></div>';
            }
            echo '</div></div>';
        }

        echo '<div class="eh-assessment-section" style="' . esc_attr($detail_card_style) . '">';
        echo '<h2 style="margin-top:0;">Assignment</h2>';
        echo '<form method="post" action="" style="margin:0;">';
        wp_nonce_field('eh_update_submission');
        echo '<input type="hidden" name="eh_assessment_action" value="update_submission">';
        echo '<input type="hidden" name="submission_id" value="' . esc_attr((string) $submission_id) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="status">Status</label></th><td><select id="status" name="status">';
        foreach (['On Progress', 'Complete', 'Failed'] as $status) {
            echo '<option value="' . esc_attr($status) . '"' . selected($submission['status'], $status, false) . '>' . esc_html($status) . '</option>';
        }
        echo '</select></td></tr>';
        $current_masking = '';
        if (is_array($payload['submission'] ?? null)) {
            $current_masking = eh_assessment_normalize_submission_branch_masking_id((string) ($payload['submission']['branch_outlet_masking_id'] ?? ''));
        }
        $legacy_bo_id = (int) ($submission['branch_outlet_id'] ?? 0);
        if ($current_masking === '' && $legacy_bo_id > 0) {
            $current_masking = eh_assessment_normalize_submission_branch_masking_id(
                (string) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT cekat_masking_id FROM {$branch_table} WHERE id = %d LIMIT 1",
                        $legacy_bo_id
                    )
                )
            );
        }
        echo '<tr><th scope="row"><label for="assessment_branch_outlet_masking_id">Branch Office</label></th><td><select id="assessment_branch_outlet_masking_id" name="assessment_branch_outlet_masking_id">';
        echo '<option value="">— None —</option>';
        foreach ($branch_options as $bo) {
            $mid = eh_assessment_normalize_submission_branch_masking_id((string) ($bo['cekat_masking_id'] ?? ''));
            if ($mid === '') {
                continue;
            }
            echo '<option value="' . esc_attr($mid) . '"' . selected($current_masking, $mid, false) . '>' . esc_html(eh_assessment_branch_outlet_display_label($bo)) . '</option>';
        }
        echo '</select><p class="description">Values are Cekat masking IDs from Branch Office admin.</p></td></tr>';
        echo '</tbody></table>';
        submit_button('Save Changes');
        echo '</form>';
        echo '</div>';

        echo '<div class="eh-assessment-section" style="' . esc_attr($detail_card_style) . '">';
        echo '<h2 style="margin-top:0;">Answer Summary</h2>';
        if (is_array($payload['answers'] ?? null)) {
            echo '<table class="widefat striped eh-assessment-answer-table"><thead><tr><th>Key</th><th>Question</th><th>Answer</th></tr></thead><tbody>';
            foreach ($payload['answers'] as $key => $answer) {
                echo '<tr>';
                echo '<td>' . esc_html((string) $key) . '</td>';
                echo '<td>' . esc_html((string) ($answer['question'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($answer['answer'] ?? '')) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';

        echo '<div class="eh-assessment-section" style="' . esc_attr($detail_card_style) . '">';
        echo '<h2 style="margin-top:0;">Raw Payload</h2>';
        if (is_array($payload) && function_exists('eh_assessment_cekat_submission_saved_webhook_body')) {
            echo '<p class="description" style="margin-top:0;">Shown in the same shape as the Cekat webhook POST body: <code>submission</code> (<code>branch_office_name</code>, <code>lead_source</code>, <code>report_id</code>, <code>clinical_profile</code>, <code>score</code>, <code>band</code>, <code>patient_type</code>, <code>strategy</code>, <code>report_pdf_url</code>, <code>agent_id</code>), <code>respondent</code>, and <code>answers</code>. This is the body sent to the webhook after the submission is normalized.</p>';
        } else {
            echo '<p class="description" style="margin-top:0;">Webhook payload view is unavailable; update the plugin or reload.</p>';
        }
        $payload_display = ['submission' => [], 'respondent' => [], 'answers' => []];
        if (is_array($payload)) {
            $display_computed = is_array($payload['computed'] ?? null) ? $payload['computed'] : [];
            if ($display_computed === [] && function_exists('eh_assessment_compute_submission_outcomes')) {
                $display_answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
                $display_respondent = is_array($payload['respondent'] ?? null) ? $payload['respondent'] : [];
                $display_computed = eh_assessment_compute_submission_outcomes($display_answers, (string) ($display_respondent['gender'] ?? ''));
            }
            $display_sanitized = [
                'submission' => is_array($payload['submission'] ?? null) ? $payload['submission'] : [],
                'respondent' => is_array($payload['respondent'] ?? null) ? $payload['respondent'] : [],
                'answers' => is_array($payload['answers'] ?? null) ? $payload['answers'] : [],
                'computed' => $display_computed,
            ];
            if (function_exists('eh_assessment_cekat_submission_saved_webhook_body')) {
                $payload_display = eh_assessment_cekat_submission_saved_webhook_body(
                    $display_sanitized,
                    (string) ($submission['masked_id'] ?? ''),
                    (int) ($submission['branch_outlet_id'] ?? 0),
                    (string) (is_array($payload['submission'] ?? null) ? ($payload['submission']['agent_masking_id'] ?? '') : ''),
                    (int) ($submission['id'] ?? 0),
                    (string) ($submission['lead_source'] ?? '')
                );
            } else {
                $payload_display = $display_sanitized;
            }
            if (isset($payload_display['respondent']) && is_array($payload_display['respondent'])) {
                unset($payload_display['respondent']['birthdate']);
            }
        }
        $display_json = wp_json_encode($payload_display, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo '<textarea readonly rows="18" style="width:100%;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;border:1px solid #d0d5dd;border-radius:10px;padding:14px;background:#0b1020;color:#f8fafc;">' . esc_textarea((string) $display_json) . '</textarea>';
        $payload_full_for_admin = is_array($payload) ? $payload : [];
        if (isset($payload_full_for_admin['respondent']) && is_array($payload_full_for_admin['respondent'])) {
            unset($payload_full_for_admin['respondent']['birthdate']);
        }
        $full_json = $payload_full_for_admin !== []
            ? wp_json_encode($payload_full_for_admin, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
        $show_full_stored = is_array($payload) && (
            isset($payload['cekat_inbox'])
            ||             isset($payload['submission']['branch_outlet_id'])
            || isset($payload['submission']['source_page_id'])
            || isset($payload['submission']['status'])
            || $full_json !== $display_json
        );
        if ($full_json !== '' && $show_full_stored) {
            echo '<details style="margin-top:12px;"><summary style="cursor:pointer;font-weight:600;">Full stored JSON (includes admin-only keys such as <code>cekat_inbox</code> or legacy fields)</summary>';
            echo '<textarea readonly rows="12" style="width:100%;margin-top:8px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;border:1px solid #d0d5dd;border-radius:10px;padding:14px;background:#1a1f2e;color:#e5e7eb;">' . esc_textarea($full_json) . '</textarea>';
            echo '</details>';
        }
        echo '</div>';
        echo '</div>';
        return;
    }

    $status_filter = sanitize_text_field((string) ($_GET['status'] ?? ''));
    $search_term = sanitize_text_field((string) ($_GET['s'] ?? ''));
    $created_from = sanitize_text_field((string) ($_GET['created_from'] ?? ''));
    $created_to = sanitize_text_field((string) ($_GET['created_to'] ?? ''));
    $where_clauses = [];
    $where_values = [];
    if (in_array($status_filter, ['On Progress', 'Complete', 'Failed'], true)) {
        $where_clauses[] = 's.status = %s';
        $where_values[] = $status_filter;
    }
    if ($search_term !== '') {
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        $where_clauses[] = '(CAST(s.id AS CHAR) LIKE %s OR s.masked_id LIKE %s OR s.respondent_name LIKE %s OR s.respondent_whatsapp LIKE %s OR s.respondent_gender LIKE %s OR s.status LIKE %s OR bo.cekat_name LIKE %s OR bo.display_name LIKE %s OR s.submitted_at LIKE %s OR s.updated_at LIKE %s OR s.cekat_name LIKE %s OR s.cekat_masking_id LIKE %s OR CAST(s.respondent_birthdate AS CHAR) LIKE %s OR s.agent_name LIKE %s OR s.lead_source LIKE %s OR s.computed_condition_title LIKE %s OR s.computed_band LIKE %s OR CAST(s.computed_report_type AS CHAR) LIKE %s OR CAST(s.computed_score AS CHAR) LIKE %s OR s.computed_clinical_warnings LIKE %s OR s.computed_communication_strategy LIKE %s)';
        array_push(
            $where_values,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like
        );
    }
    if ($created_from !== '') {
        $where_clauses[] = 'DATE(s.submitted_at) >= %s';
        $where_values[] = $created_from;
    }
    if ($created_to !== '') {
        $where_clauses[] = 'DATE(s.submitted_at) <= %s';
        $where_values[] = $created_to;
    }
    $where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
    $allowed_orderby = [
        'id' => 's.id',
        'masked_id' => 's.masked_id',
        'respondent_name' => 's.respondent_name',
        'respondent_whatsapp' => 's.respondent_whatsapp',
        'respondent_gender' => 's.respondent_gender',
        'agent_name' => 's.agent_name',
        'status' => 's.status',
        'branch_outlet_name' => eh_assessment_branch_outlet_label_sql('bo'),
        'cekat_name' => 's.cekat_name',
        'submitted_at' => 's.submitted_at',
        'lead_source' => 's.lead_source',
        'computed_report_type' => 's.computed_report_type',
        'computed_score' => 's.computed_score',
        'computed_condition_title' => 's.computed_condition_title',
    ];
    $orderby_key = sanitize_key((string) ($_GET['orderby'] ?? 'id'));
    $orderby_sql = $allowed_orderby[$orderby_key] ?? 's.id';
    $order_sql = strtolower(sanitize_text_field((string) ($_GET['order'] ?? 'desc'))) === 'asc' ? 'ASC' : 'DESC';
    $branchOutletLabel = eh_assessment_branch_outlet_label_sql('bo');
    $sql = "SELECT s.id, s.masked_id, s.status, s.respondent_name, s.respondent_whatsapp, s.respondent_gender, s.submitted_at, s.cekat_name, s.agent_name,
            s.lead_source, s.computed_report_type, s.computed_score, s.computed_band, s.computed_condition_title, s.computed_patient_type,
            {$branchOutletLabel} AS branch_outlet_name
         FROM {$assessment_table} s
         LEFT JOIN {$branch_table} bo ON bo.id = s.branch_outlet_id AND bo.deleted_at IS NULL
         {$where_sql}
         ORDER BY {$orderby_sql} {$order_sql}";
    if ($where_values) {
        $sql = $wpdb->prepare($sql, ...$where_values);
    }
    $rows = $wpdb->get_results($sql, ARRAY_A);

    $base_url = admin_url('admin.php?page=eh-assessment-submissions');
    $base_args = ['page' => 'eh-assessment-submissions'];
    if ($status_filter !== '') {
        $base_args['status'] = $status_filter;
    }
    if ($search_term !== '') {
        $base_args['s'] = $search_term;
    }
    if ($created_from !== '') {
        $base_args['created_from'] = $created_from;
    }
    if ($created_to !== '') {
        $base_args['created_to'] = $created_to;
    }

    $cekat_err = sanitize_key((string) ($_GET['eh_cekat_err'] ?? ''));
    $cekat_err_messages = [
        'cekat_required' => 'Please select a Cekat inbox.',
        'cekat_invalid' => 'Cekat inbox data was invalid. Select the inbox again.',
        'incomplete_respondent' => 'Respondent name, WhatsApp, gender, and consent are required.',
        'incomplete_answers' => 'All assessment answers are required.',
        'invalid_submission' => 'Some required fields are missing or invalid. Check name, WhatsApp, gender, consent, Branch Office, and every question answer.',
        'invalid_branch_outlet' => 'The selected Branch Office is invalid or inactive.',
        'invalid_source_page' => 'Source page slug is unknown or not a published Page. Leave it empty or use the exact URL slug (e.g. hair-assessment).',
        'branch_office_required' => 'Branch Office is required.',
        'invalid_birthdate' => 'Birth date is required, must be YYYY-MM-DD, and cannot be in the future.',
        'spam_detected' => 'Submission could not be saved.',
        'insert_failed' => 'Could not save the submission. Check the database error log.',
    ];
    if ($cekat_err !== '' && isset($cekat_err_messages[$cekat_err])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($cekat_err_messages[$cekat_err]) . '</p></div>';
    } elseif ($cekat_err !== '') {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html('Could not save the submission.') . '</p></div>';
    }
    if (isset($_GET['eh_cekat_dup']) && (string) $_GET['eh_cekat_dup'] === '1') {
        echo '<div class="notice notice-warning is-dismissible"><p>A submission for this Cekat inbox already exists. Each inbox can only be linked once.</p></div>';
    }
    if (isset($_GET['eh_manual_sub']) && (string) $_GET['eh_manual_sub'] === 'disabled') {
        echo '<div class="notice notice-info is-dismissible"><p>Adding submissions from this screen is disabled. Submissions are created from the website assessment form only.</p></div>';
    }

    echo '<style>
.eh-assessment-submissions-toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px 20px; margin:0 0 16px; width:100%; box-sizing:border-box; }
.eh-assessment-submissions-toolbar__views.subsubsub { margin:0; float:none; display:block; line-height:2; flex:0 1 auto; }
.eh-assessment-submissions-toolbar__views.subsubsub li { display:inline-block; margin:0; padding:0; }
.eh-assessment-submissions-toolbar__views.subsubsub li + li::before { content:"|"; display:inline-block; margin:0 10px 0 6px; color:#c3c4c7; font-weight:400; }
.eh-assessment-submissions-toolbar__form { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:8px; margin:0; flex:1 1 280px; }
</style>';
    echo '<div class="eh-assessment-submissions-toolbar">';
    echo '<ul class="subsubsub eh-assessment-submissions-views eh-assessment-submissions-toolbar__views">';
    $filters = ['' => 'All', 'On Progress' => 'On Progress', 'Complete' => 'Complete', 'Failed' => 'Failed'];
    foreach ($filters as $value => $label) {
        $url = $value === '' ? $base_url : add_query_arg('status', rawurlencode($value), $base_url);
        $class = $status_filter === $value ? ' class="current"' : '';
        echo '<li><a href="' . esc_url($url) . '"' . $class . '>' . esc_html($label) . '</a></li>';
    }
    echo '</ul>';

    echo '<form class="eh-assessment-submissions-toolbar__form" method="get" action="">';
    echo '<input type="hidden" name="page" value="eh-assessment-submissions">';
    if ($status_filter !== '') {
        echo '<input type="hidden" name="status" value="' . esc_attr($status_filter) . '">';
    }
    echo '<label for="eh-assessment-created-from" style="font-size:12px;color:#50575e;">Created At</label>';
    echo '<input id="eh-assessment-created-from" type="date" name="created_from" value="' . esc_attr($created_from) . '">';
    echo '<span style="color:#50575e;">to</span>';
    echo '<input type="date" name="created_to" value="' . esc_attr($created_to) . '">';
    echo '<input type="search" name="s" value="' . esc_attr($search_term) . '" class="regular-text" placeholder="Search all columns...">';
    $reset_href = $base_url;
    if ($status_filter !== '') {
        $reset_href = add_query_arg('status', rawurlencode($status_filter), $reset_href);
    }
    echo '<button type="submit" class="button">Search</button> <a class="button" href="' . esc_url($reset_href) . '">Reset</a>';
    echo '</form>';
    echo '</div>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'id', 'ID') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'masked_id', 'Report ID') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'computed_report_type', 'Rpt') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'computed_condition_title', 'Profil klinis') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'computed_score', 'Score') . '</th>';
    echo '<th>Band</th>';
    echo '<th>Tipe</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'lead_source', 'Source') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'branch_outlet_name', 'Branch Office') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'respondent_name', 'Respondent') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'respondent_whatsapp', 'WhatsApp') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'respondent_gender', 'Gender') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'agent_name', 'Agent') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'status', 'Status') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'submitted_at', 'Submitted At') . '</th>';
    echo '<th>Actions</th>';
    echo '</tr></thead><tbody>';

    if (!$rows) {
        echo '<tr><td colspan="16">No submissions found.</td></tr>';
    } else {
        foreach ($rows as $row) {
            $detail_url = add_query_arg(
                [
                    'page' => 'eh-assessment-submissions',
                    'view' => 'detail',
                    'submission_id' => (int) $row['id'],
                    'eh_submission_id' => (int) $row['id'],
                ],
                admin_url('admin.php')
            );

            echo '<tr>';
            echo '<td>' . (int) $row['id'] . '</td>';
            echo '<td><strong><a href="' . esc_url($detail_url) . '">' . esc_html((string) $row['masked_id']) . '</a></strong></td>';
            $crt = isset($row['computed_report_type']) && $row['computed_report_type'] !== null && $row['computed_report_type'] !== ''
                ? (int) $row['computed_report_type']
                : 0;
            echo '<td>' . esc_html($crt > 0 ? (string) $crt : '—') . '</td>';
            $cct = trim((string) ($row['computed_condition_title'] ?? ''));
            echo '<td style="max-width:200px;">' . ($cct !== '' ? esc_html($cct) : '—') . '</td>';
            $csc = isset($row['computed_score']) && $row['computed_score'] !== null && $row['computed_score'] !== ''
                ? (int) $row['computed_score']
                : 0;
            echo '<td>' . esc_html($csc > 0 ? (string) $csc : '—') . '</td>';
            $cbd = trim((string) ($row['computed_band'] ?? ''));
            echo '<td style="max-width:120px;">' . ($cbd !== '' ? esc_html($cbd) : '—') . '</td>';
            $cpt = isset($row['computed_patient_type']) && $row['computed_patient_type'] !== null && $row['computed_patient_type'] !== ''
                ? (int) $row['computed_patient_type']
                : 0;
            echo '<td>' . esc_html($cpt > 0 ? 'Tipe ' . $cpt : '—') . '</td>';
            $ls = trim((string) ($row['lead_source'] ?? ''));
            echo '<td>' . esc_html($ls !== '' ? $ls : '—') . '</td>';
            $outlet = trim((string) ($row['branch_outlet_name'] ?? ''));
            echo '<td>' . ($outlet !== '' ? esc_html($outlet) : '—') . '</td>';
            echo '<td>' . esc_html((string) $row['respondent_name']) . '</td>';
            echo '<td>' . esc_html((string) $row['respondent_whatsapp']) . '</td>';
            echo '<td>' . esc_html((string) $row['respondent_gender']) . '</td>';
            $an = trim((string) ($row['agent_name'] ?? ''));
            echo '<td>' . ($an !== '' ? esc_html($an) : '—') . '</td>';
            echo '<td>' . esc_html((string) $row['status']) . '</td>';
            echo '<td>' . esc_html(eh_assessment_format_admin_datetime((string) $row['submitted_at'])) . '</td>';
            echo '<td><a class="button button-small" href="' . esc_url($detail_url) . '">View</a> ';
            if ((string) $row['status'] === 'Failed') {
                echo '<form method="post" action="' . esc_url(admin_url('admin.php')) . '" style="display:inline;margin-left:4px;">';
                wp_nonce_field('eh_resend_submission_notification');
                echo '<input type="hidden" name="eh_assessment_action" value="resend_submission_notification">';
                echo '<input type="hidden" name="submission_id" value="' . esc_attr((string) $row['id']) . '">';
                echo '<button type="submit" class="button button-small">Resend Notification</button></form> ';
            }
            echo eh_assessment_render_report_download_link((int) $row['id'], 'button button-small');
            echo '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';

    echo '</div>';
}

function eh_assessment_render_hair_specialists_page(): void
{
    if (!eh_assessment_user_is_administrator()) {
        wp_die('You do not have permission to access this page.');
    }

    global $wpdb;
    $specialist_table = eh_hair_specialist_table_name();
    $search_term = sanitize_text_field((string) ($_GET['s'] ?? ''));
    $created_from = sanitize_text_field((string) ($_GET['created_from'] ?? ''));
    $created_to = sanitize_text_field((string) ($_GET['created_to'] ?? ''));
    $where_clauses = [];
    $where_values = [];
    if ($search_term !== '') {
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        $where_clauses[] = '(CAST(id AS CHAR) LIKE %s OR masked_id LIKE %s OR name LIKE %s OR email LIKE %s OR wa_number LIKE %s OR created_at LIKE %s OR updated_at LIKE %s)';
        array_push($where_values, $like, $like, $like, $like, $like, $like, $like);
    }
    if ($created_from !== '') {
        $where_clauses[] = 'DATE(created_at) >= %s';
        $where_values[] = $created_from;
    }
    if ($created_to !== '') {
        $where_clauses[] = 'DATE(created_at) <= %s';
        $where_values[] = $created_to;
    }
    $where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
    $allowed_orderby = [
        'id' => 'id',
        'masked_id' => 'masked_id',
        'name' => 'name',
        'email' => 'email',
        'wa_number' => 'wa_number',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];
    $orderby_key = sanitize_key((string) ($_GET['orderby'] ?? 'id'));
    $orderby_sql = $allowed_orderby[$orderby_key] ?? 'id';
    $order_sql = strtolower(sanitize_text_field((string) ($_GET['order'] ?? 'desc'))) === 'asc' ? 'ASC' : 'DESC';
    $sql = "SELECT id, masked_id, name, email, wa_number, created_at, updated_at FROM {$specialist_table} {$where_sql} ORDER BY {$orderby_sql} {$order_sql}";
    if ($where_values) {
        $sql = $wpdb->prepare($sql, ...$where_values);
    }
    $rows = $wpdb->get_results($sql, ARRAY_A);

    echo '<div class="wrap">';
    echo '<h1>Hair Specialists</h1>';
    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Hair specialist saved.</p></div>';
    }

    $base_args = ['page' => 'eh-hair-specialists'];
    if ($search_term !== '') {
        $base_args['s'] = $search_term;
    }
    if ($created_from !== '') {
        $base_args['created_from'] = $created_from;
    }
    if ($created_to !== '') {
        $base_args['created_to'] = $created_to;
    }
    echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px;">';
    echo '<div><button type="button" class="button button-primary" id="eh-open-hs-modal">Add Hair Specialist</button></div>';
    echo '<form method="get" action="" style="display:flex;gap:8px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">';
    echo '<input type="hidden" name="page" value="eh-hair-specialists">';
    echo '<label for="eh-hs-created-from" style="font-size:12px;color:#50575e;">Created At</label>';
    echo '<input id="eh-hs-created-from" type="date" name="created_from" value="' . esc_attr($created_from) . '">';
    echo '<span style="color:#50575e;">to</span>';
    echo '<input type="date" name="created_to" value="' . esc_attr($created_to) . '">';
    echo '<input type="search" name="s" value="' . esc_attr($search_term) . '" class="regular-text" placeholder="Search all columns...">';
    echo '<button type="submit" class="button">Search</button> <a class="button" href="' . esc_url(admin_url('admin.php?page=eh-hair-specialists')) . '">Reset</a>';
    echo '</form>';
    echo '</div>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'id', 'ID') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'masked_id', 'Masked ID') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'name', 'Name') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'email', 'Email') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'wa_number', 'WhatsApp') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'created_at', 'Created At') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'updated_at', 'Updated At') . '</th><th>Actions</th>';
    echo '</tr></thead><tbody>';

    if (!$rows) {
        echo '<tr><td colspan="8">No hair specialists found.</td></tr>';
    } else {
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . (int) $row['id'] . '</td>';
            echo '<td>' . esc_html((string) $row['masked_id']) . '</td>';
            echo '<td>' . esc_html((string) $row['name']) . '</td>';
            echo '<td>' . esc_html((string) $row['email']) . '</td>';
            echo '<td>' . esc_html((string) $row['wa_number']) . '</td>';
            echo '<td>' . esc_html(eh_assessment_format_admin_datetime((string) $row['created_at'])) . '</td>';
            echo '<td>' . esc_html(eh_assessment_format_admin_datetime((string) $row['updated_at'])) . '</td>';
            echo '<td><button type="button" class="button-link eh-edit-hs-button"';
            echo ' data-id="' . esc_attr((string) $row['id']) . '"';
            echo ' data-masked-id="' . esc_attr((string) $row['masked_id']) . '"';
            echo ' data-name="' . esc_attr((string) $row['name']) . '"';
            echo ' data-email="' . esc_attr((string) $row['email']) . '"';
            echo ' data-wa-number="' . esc_attr((string) $row['wa_number']) . '"';
            echo ' data-created-at="' . esc_attr(eh_assessment_format_admin_datetime((string) $row['created_at'])) . '"';
            echo ' data-updated-at="' . esc_attr(eh_assessment_format_admin_datetime((string) $row['updated_at'])) . '"';
            echo '>Edit</button></td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
    echo '<div id="eh-hs-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99999;align-items:center;justify-content:center;padding:24px;">';
    echo '<div style="background:#fff;width:100%;max-width:760px;border-radius:8px;box-shadow:0 24px 80px rgba(0,0,0,.18);max-height:90vh;overflow:auto;">';
    echo '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e5e5;">';
    echo '<h2 id="eh-hs-modal-title" style="margin:0;font-size:20px;">Add Hair Specialist</h2>';
    echo '<button type="button" class="button-link" id="eh-close-hs-modal" aria-label="Close modal" style="font-size:18px;text-decoration:none;">X</button>';
    echo '</div>';
    echo '<div style="padding:20px;">';
    echo '<form method="post" action="">';
    wp_nonce_field('eh_save_hair_specialist');
    echo '<input type="hidden" name="eh_assessment_action" value="save_hair_specialist">';
    echo '<input type="hidden" name="specialist_id" id="eh-hs-id" value="0">';
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th scope="row"><label for="eh-hs-name">Name</label></th><td><input id="eh-hs-name" name="name" type="text" class="regular-text" value="" required></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hs-email">Email</label></th><td><input id="eh-hs-email" name="email" type="email" class="regular-text" value="" required></td></tr>';
    echo '<tr><th scope="row"><label for="eh-hs-wa">WhatsApp Number</label></th><td><input id="eh-hs-wa" name="wa_number" type="text" class="regular-text" value="" required></td></tr>';
    echo '<tr id="eh-hs-meta-masked" style="display:none;"><th scope="row">Masked ID</th><td id="eh-hs-masked-id"></td></tr>';
    echo '<tr id="eh-hs-meta-created" style="display:none;"><th scope="row">Created At</th><td id="eh-hs-created-at"></td></tr>';
    echo '<tr id="eh-hs-meta-updated" style="display:none;"><th scope="row">Updated At</th><td id="eh-hs-updated-at"></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Hair Specialist');
    echo '</form>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<script>';
    echo '(function(){';
    echo 'const modal=document.getElementById("eh-hs-modal");';
    echo 'const openBtn=document.getElementById("eh-open-hs-modal");';
    echo 'const closeBtn=document.getElementById("eh-close-hs-modal");';
    echo 'const title=document.getElementById("eh-hs-modal-title");';
    echo 'const idInput=document.getElementById("eh-hs-id");';
    echo 'const nameInput=document.getElementById("eh-hs-name");';
    echo 'const emailInput=document.getElementById("eh-hs-email");';
    echo 'const waInput=document.getElementById("eh-hs-wa");';
    echo 'const maskedRow=document.getElementById("eh-hs-meta-masked");';
    echo 'const createdRow=document.getElementById("eh-hs-meta-created");';
    echo 'const updatedRow=document.getElementById("eh-hs-meta-updated");';
    echo 'const maskedValue=document.getElementById("eh-hs-masked-id");';
    echo 'const createdValue=document.getElementById("eh-hs-created-at");';
    echo 'const updatedValue=document.getElementById("eh-hs-updated-at");';
    echo 'function openModal(){modal.style.display="flex";}';
    echo 'function closeModal(){modal.style.display="none";}';
    echo 'function resetModal(){title.textContent="Add Hair Specialist";idInput.value="0";nameInput.value="";emailInput.value="";waInput.value="";maskedValue.textContent="";createdValue.textContent="";updatedValue.textContent="";maskedRow.style.display="none";createdRow.style.display="none";updatedRow.style.display="none";}';
    echo 'openBtn&&openBtn.addEventListener("click",function(){resetModal();openModal();});';
    echo 'closeBtn&&closeBtn.addEventListener("click",closeModal);';
    echo 'modal&&modal.addEventListener("click",function(e){if(e.target===modal){closeModal();}});';
    echo 'document.querySelectorAll(".eh-edit-hs-button").forEach(function(btn){btn.addEventListener("click",function(){title.textContent="Edit Hair Specialist";idInput.value=btn.dataset.id||"0";nameInput.value=btn.dataset.name||"";emailInput.value=btn.dataset.email||"";waInput.value=btn.dataset.waNumber||"";maskedValue.textContent=btn.dataset.maskedId||"";createdValue.textContent=btn.dataset.createdAt||"";updatedValue.textContent=btn.dataset.updatedAt||"";maskedRow.style.display="table-row";createdRow.style.display="table-row";updatedRow.style.display="table-row";openModal();});});';
    echo 'document.addEventListener("keydown",function(e){if(e.key==="Escape"){closeModal();}});';
    echo '})();';
    echo '</script>';
    echo '</div>';
}

function eh_assessment_migrate_role_access_and_user_assignments(): void
{
    global $wpdb;

    if ((string) get_option('eh_assessment_data_v120_role_access_migrated', '') === '1') {
        return;
    }

    eh_assessment_migrate_v179_drop_submission_deleted_at();
    eh_assessment_migrate_v180_report_pdf_template_risk_untreated_image();
    eh_assessment_migrate_v191_report_pdf_template_treatment_rec_3();
    eh_assessment_migrate_v200_report_pdf_template_precon_fields();
    eh_assessment_migrate_v201_report_pdf_template_drop_legacy_fields();
    eh_assessment_migrate_v203_report_pdf_template_report_header_title();
    eh_assessment_migrate_v204_report_pdf_template_body_medical_notes();
    eh_assessment_migrate_v205_report_pdf_template_diagnosis_name_detail();
    eh_assessment_migrate_v202_report_pdf_template_seed_precon_defaults();
    eh_assessment_migrate_v181_branch_outlet_display_name();
    eh_assessment_migrate_v190_submission_computed_columns();

    $assessment_table = eh_assessment_table_name();
    $specialist_table = eh_hair_specialist_table_name();

    if (eh_assessment_assessment_table_has_column('hair_specialist_id')) {
        $wpdb->query($wpdb->prepare("UPDATE {$assessment_table} SET hair_specialist_id = %d, updated_at = %s", 2, eh_assessment_current_mysql_time()));
    }

    $aurelia_sql = "SELECT COUNT(*) FROM {$assessment_table} WHERE respondent_name = %s";
    $sample_exists = (int) $wpdb->get_var($wpdb->prepare($aurelia_sql, 'Aurelia Tan'));

    if ($sample_exists === 0) {
        $payload = [
            'submission' => [
                'source_page_slug' => '',
                'status' => 'On Progress',
                'branch_outlet_masking_id' => '',
                'source' => 'website',
            ],
            'respondent' => [
                'name' => 'Aurelia Tan',
                'whatsapp' => eh_assessment_normalize_whatsapp('081377700321'),
                'gender' => 'female',
                'birthdate' => '',
                'consent' => true,
            ],
            'answers' => [
                'q1_focus_area' => ['question' => 'Which hair or scalp change are you noticing the most?', 'answer' => 'Hairline Receding'],
                'q2_main_impact' => ['question' => 'What is the biggest impact you are feeling?', 'answer' => 'Lower Confidence'],
                'q3_duration' => ['question' => 'How long have you noticed this change?', 'answer' => '6 to 12 Months'],
                'q4_family_history' => ['question' => 'Is there a family history of a similar condition?', 'answer' => 'Yes'],
                'q5_previous_attempts' => ['question' => 'What have you tried so far?', 'answer' => 'Products / Serums'],
                'q6_trigger_factors' => ['question' => 'Are you currently experiencing any of the following factors?', 'answer' => 'Hormonal Changes'],
                'q7_biggest_worry' => ['question' => 'If left untreated, what worries you the most?', 'answer' => 'The Thinning Area Will Spread'],
                'q8_previous_consultation' => ['question' => 'Have you had a consultation before?', 'answer' => 'Aesthetic Clinic'],
                'q9_expected_result' => ['question' => 'If your condition improves, what result are you hoping for?', 'answer' => 'Thicker and Healthier Hair'],
            ],
        ];

        $san_a = eh_assessment_sanitize_payload($payload);
        $cdb_a = eh_assessment_attach_computed_to_sanitized($san_a);
        $rt_a = (int) ($san_a['submission']['report_type'] ?? 5);

        $insert_row = [
            'masked_id' => eh_assessment_generate_submission_masked_id($rt_a, $assessment_table),
            'source_page_id' => null,
            'status' => 'On Progress',
            'respondent_name' => 'Aurelia Tan',
            'respondent_whatsapp' => eh_assessment_normalize_whatsapp('081377700321'),
            'respondent_gender' => 'female',
            'respondent_birthdate' => null,
            'branch_outlet_id' => null,
            'agent_name' => null,
            'consent' => 1,
            'lead_source' => $cdb_a['lead_source'],
            'q1_focus_area' => 'Hairline Receding',
            'q2_main_impact' => 'Lower Confidence',
            'q3_duration' => '6 to 12 Months',
            'q4_family_history' => 'Yes',
            'q5_previous_attempts' => 'Products / Serums',
            'q6_trigger_factors' => 'Hormonal Changes',
            'q7_biggest_worry' => 'The Thinning Area Will Spread',
            'q8_previous_consultation' => 'Aesthetic Clinic',
            'q9_expected_result' => 'Thicker and Healthier Hair',
        ];
        $insert_row = array_merge($insert_row, $cdb_a['metrics']);
        $fail_tail = [
            'cekat_masking_id' => null,
            'cekat_created_at' => null,
            'cekat_business_id' => null,
            'cekat_name' => null,
            'cekat_description' => null,
            'cekat_phone_number' => null,
            'cekat_status' => null,
            'cekat_ai_agent_id' => null,
            'cekat_image_url' => null,
            'cekat_type' => null,
            'cekat_ai_agent_json' => null,
            'payload_json' => eh_assessment_payload_json_for_storage($san_a),
            'submitted_at' => eh_assessment_current_mysql_time(),
            'updated_at' => eh_assessment_current_mysql_time(),
        ];
        $insert_row = array_merge($insert_row, $fail_tail);
        $wpdb->insert($assessment_table, $insert_row, eh_assessment_submission_row_format_types());
    }

    $user_ids = get_users([
        'role__in' => ['subscriber'],
        'fields' => 'ID',
    ]);

    foreach ($user_ids as $user_id) {
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id, masked_id FROM {$specialist_table} WHERE id = %d LIMIT 1", (int) $user_id), ARRAY_A);
        if (!$existing) {
            $user = get_user_by('id', (int) $user_id);
            if ($user instanceof WP_User) {
                $wpdb->insert(
                    $specialist_table,
                    [
                        'id' => (int) $user_id,
                        'masked_id' => eh_assessment_generate_masked_id('HS', $specialist_table),
                        'name' => $user->display_name ?: $user->user_login,
                        'email' => $user->user_email,
                        'wa_number' => eh_assessment_normalize_whatsapp((string) get_user_meta((int) $user_id, EH_ASSESSMENT_USER_WHATSAPP_META_KEY, true)),
                        'created_at' => eh_assessment_current_mysql_time(),
                        'updated_at' => eh_assessment_current_mysql_time(),
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
                );
            }
        }
    }

    update_option('eh_assessment_data_v120_role_access_migrated', '1');
}
add_action('plugins_loaded', 'eh_assessment_migrate_role_access_and_user_assignments');
