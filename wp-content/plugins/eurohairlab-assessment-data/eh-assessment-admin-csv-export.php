<?php
/**
 * Admin CSV export (UTF-8 BOM) for filtered custom tables — opens cleanly in Excel.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @param mixed $value
 */
function eh_assessment_csv_escape_scalar($value): string
{
    if ($value === null) {
        return '';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (!is_scalar($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    $s = str_replace(["\r\n", "\r", "\n"], ' ', trim((string) $value));
    if ($s !== '' && isset($s[0]) && ($s[0] === '=' || $s[0] === '+' || $s[0] === '-' || $s[0] === '@')) {
        return "'" . $s;
    }

    return $s;
}

/**
 * @param list<string> $header_labels
 * @param list<list<string>> $data_rows
 */
function eh_assessment_csv_stream_download(string $filename, array $header_labels, array $data_rows): void
{
    $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?? 'export.csv';
    if ($filename === '' || substr(strtolower($filename), -4) !== '.csv') {
        $filename = 'export.csv';
    }

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        wp_die('Could not open output stream.', 'Export failed', ['response' => 500]);
    }

    fwrite($out, (string) pack('CCC', 0xef, 0xbb, 0xbf));
    fputcsv($out, $header_labels);
    foreach ($data_rows as $row) {
        fputcsv($out, array_map('eh_assessment_csv_escape_scalar', $row));
    }
    fclose($out);
    exit;
}

/**
 * @param array<string, string> $extra_query GET params to preserve (filters).
 */
function eh_assessment_admin_export_csv_url(string $page_slug, array $extra_query = []): string
{
    $q = array_merge(['page' => $page_slug, 'eh_export' => 'csv'], $extra_query);

    return wp_nonce_url(add_query_arg($q, admin_url('admin.php')), 'eh_export_csv_' . $page_slug);
}

function eh_assessment_handle_admin_csv_export(): void
{
    if (!is_admin() || (string) ($_GET['eh_export'] ?? '') !== 'csv') {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
    if ($page === '' || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])), 'eh_export_csv_' . $page)) {
        wp_die('Invalid export link or expired session.', 'Export', ['response' => 403]);
    }

    switch ($page) {
        case 'eh-assessment-submissions':
            if (!eh_assessment_current_user_can_access_admin()) {
                wp_die('Forbidden.', 'Export', ['response' => 403]);
            }
            eh_assessment_export_csv_assessment_submissions();
            exit;
        case 'eh-assessment-branch-outlet':
            if (!eh_assessment_current_user_can_access_admin()) {
                wp_die('Forbidden.', 'Export', ['response' => 403]);
            }
            eh_assessment_export_csv_branch_outlets();
            exit;
        case 'eh-hair-specialist-agents':
            if (!eh_assessment_user_is_administrator()) {
                wp_die('Forbidden.', 'Export', ['response' => 403]);
            }
            eh_assessment_export_csv_hair_specialist_agents();
            exit;
        case 'eh-hair-specialist-daily-overview':
            if (!eh_assessment_current_user_can_access_admin()) {
                wp_die('Forbidden.', 'Export', ['response' => 403]);
            }
            eh_assessment_export_csv_hair_specialist_daily_overview();
            exit;
        default:
            wp_die('Unknown export.', 'Export', ['response' => 404]);
    }
}

function eh_assessment_export_csv_assessment_submissions(): void
{
    eh_assessment_mark_prior_day_on_progress_submissions_complete();

    global $wpdb;
    $assessment_table = eh_assessment_table_name();
    $branch_table = eh_branch_outlet_table_name();

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
    $branchOutletLabel = eh_assessment_branch_outlet_label_sql('bo');
    $sql = "SELECT s.id, s.masked_id, s.status, s.respondent_name, s.respondent_whatsapp, s.respondent_gender,
            CAST(s.respondent_birthdate AS CHAR) AS respondent_birthdate, s.agent_name, s.lead_source,
            s.computed_report_type, s.computed_score, s.computed_band, s.computed_condition_title, s.computed_patient_type,
            s.computed_communication_strategy, s.computed_maintenance_path, s.computed_clinical_warnings,
            CAST(s.submitted_at AS CHAR) AS submitted_at, CAST(s.updated_at AS CHAR) AS updated_at,
            {$branchOutletLabel} AS branch_outlet_name
         FROM {$assessment_table} s
         LEFT JOIN {$branch_table} bo ON bo.id = s.branch_outlet_id AND bo.deleted_at IS NULL
         {$where_sql}
         ORDER BY s.id ASC";
    if ($where_values !== []) {
        $sql = $wpdb->prepare($sql, ...$where_values);
    }
    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (!is_array($rows)) {
        $rows = [];
    }

    $headers = [
        'ID',
        'Report ID',
        'Status',
        'Respondent name',
        'WhatsApp',
        'Gender',
        'Birthdate',
        'Hair specialist',
        'Lead source',
        'Rpt',
        'Score',
        'Band',
        'Profil klinis',
        'Patient type',
        'Communication strategy',
        'Maintenance path',
        'Clinical warnings',
        'Branch office',
        'Submitted at',
        'Updated at',
    ];
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            (string) (int) ($r['id'] ?? 0),
            (string) ($r['masked_id'] ?? ''),
            (string) ($r['status'] ?? ''),
            (string) ($r['respondent_name'] ?? ''),
            (string) ($r['respondent_whatsapp'] ?? ''),
            (string) ($r['respondent_gender'] ?? ''),
            (string) ($r['respondent_birthdate'] ?? ''),
            (string) ($r['agent_name'] ?? ''),
            (string) ($r['lead_source'] ?? ''),
            (string) ($r['computed_report_type'] ?? ''),
            (string) ($r['computed_score'] ?? ''),
            (string) ($r['computed_band'] ?? ''),
            (string) ($r['computed_condition_title'] ?? ''),
            (string) ($r['computed_patient_type'] ?? ''),
            (string) ($r['computed_communication_strategy'] ?? ''),
            (string) ($r['computed_maintenance_path'] ?? ''),
            (string) ($r['computed_clinical_warnings'] ?? ''),
            (string) ($r['branch_outlet_name'] ?? ''),
            (string) ($r['submitted_at'] ?? ''),
            (string) ($r['updated_at'] ?? ''),
        ];
    }

    $suffix = gmdate('Y-m-d');
    eh_assessment_csv_stream_download('assessment-submissions-' . $suffix . '.csv', $headers, $out);
}

function eh_assessment_export_csv_branch_outlets(): void
{
    global $wpdb;
    $branch_table = eh_branch_outlet_table_name();
    $bo_status = isset($_GET['bo_status']) && (string) $_GET['bo_status'] === 'trash' ? 'trash' : 'active';
    $bo_search = isset($_GET['bo_search']) ? trim(sanitize_text_field(wp_unslash((string) $_GET['bo_search']))) : '';
    if (strlen($bo_search) > 191) {
        $bo_search = substr($bo_search, 0, 191);
    }

    $boOrderLabel = eh_assessment_branch_outlet_label_sql('');
    if ($bo_search === '') {
        $sql = $bo_status === 'trash'
            ? "SELECT * FROM {$branch_table} WHERE deleted_at IS NOT NULL ORDER BY id ASC"
            : "SELECT * FROM {$branch_table} WHERE deleted_at IS NULL ORDER BY id ASC";
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
                "SELECT * FROM {$branch_table} WHERE deleted_at IS NOT NULL AND {$search_where} ORDER BY id ASC",
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
                "SELECT * FROM {$branch_table} WHERE deleted_at IS NULL AND {$search_where} ORDER BY id ASC",
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
    if (!is_array($rows) || $rows === []) {
        $desc = $wpdb->get_results("SHOW COLUMNS FROM {$branch_table}", ARRAY_A);
        $headers = [];
        if (is_array($desc)) {
            foreach ($desc as $c) {
                if (isset($c['Field'])) {
                    $headers[] = (string) $c['Field'];
                }
            }
        }
        if ($headers === []) {
            $headers = ['id'];
        }
        eh_assessment_csv_stream_download('branch-offices-' . gmdate('Y-m-d') . '.csv', $headers, []);

        return;
    }

    $keys = array_keys($rows[0]);
    $headers = $keys;
    $out = [];
    foreach ($rows as $r) {
        $line = [];
        foreach ($keys as $k) {
            $line[] = (string) ($r[$k] ?? '');
        }
        $out[] = $line;
    }

    $fn = 'branch-offices-' . ($bo_status === 'trash' ? 'trash-' : '') . gmdate('Y-m-d') . '.csv';
    eh_assessment_csv_stream_download($fn, $headers, $out);
}

function eh_assessment_export_csv_hair_specialist_agents(): void
{
    global $wpdb;
    $agent_table = eh_hair_specialist_agent_table_name();
    $branch_table = eh_branch_outlet_table_name();
    $hsa_status = isset($_GET['hsa_status']) && (string) $_GET['hsa_status'] === 'trash' ? 'trash' : 'active';
    $search_term = sanitize_text_field((string) ($_GET['s'] ?? ''));

    $search_sql = '';
    $search_vals = [];
    if ($search_term !== '') {
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        $search_sql = ' AND (CAST(a.id AS CHAR) LIKE %s OR a.masking_id LIKE %s OR a.name LIKE %s OR a.email LIKE %s OR a.agent_code LIKE %s OR IFNULL(bo.cekat_name, \'\') LIKE %s OR IFNULL(bo.display_name, \'\') LIKE %s OR CAST(a.updated_at AS CHAR) LIKE %s)';
        $search_vals = [$like, $like, $like, $like, $like, $like, $like, $like];
    }

    $hsaBranchLabel = eh_assessment_branch_outlet_label_sql('bo');
    if ($hsa_status === 'trash') {
        $sql = "SELECT a.id, a.masking_id, a.name, a.email, a.agent_code, a.branch_outlet_id, {$hsaBranchLabel} AS branch_outlet_name,
                CAST(a.created_at AS CHAR) AS created_at, CAST(a.updated_at AS CHAR) AS updated_at, CAST(a.deleted_at AS CHAR) AS deleted_at
           FROM {$agent_table} a
           LEFT JOIN {$branch_table} bo ON bo.id = a.branch_outlet_id
           WHERE a.deleted_at IS NOT NULL{$search_sql}
           ORDER BY a.id ASC";
    } else {
        $sql = "SELECT a.id, a.masking_id, a.name, a.email, a.agent_code, a.branch_outlet_id, {$hsaBranchLabel} AS branch_outlet_name,
                CAST(a.created_at AS CHAR) AS created_at, CAST(a.updated_at AS CHAR) AS updated_at
           FROM {$agent_table} a
           LEFT JOIN {$branch_table} bo ON bo.id = a.branch_outlet_id AND bo.deleted_at IS NULL
           WHERE a.deleted_at IS NULL{$search_sql}
           ORDER BY a.id ASC";
    }
    if ($search_vals !== []) {
        $sql = $wpdb->prepare($sql, ...$search_vals);
    }
    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (!is_array($rows)) {
        $rows = [];
    }

    $headers = $hsa_status === 'trash'
        ? ['ID', 'Masking ID', 'Name', 'Email', 'Agent code', 'Branch outlet ID', 'Branch office', 'Created at', 'Updated at', 'Deleted at']
        : ['ID', 'Masking ID', 'Name', 'Email', 'Agent code', 'Branch outlet ID', 'Branch office', 'Created at', 'Updated at'];
    $out = [];
    foreach ($rows as $r) {
        if ($hsa_status === 'trash') {
            $out[] = [
                (string) (int) ($r['id'] ?? 0),
                (string) ($r['masking_id'] ?? ''),
                (string) ($r['name'] ?? ''),
                (string) ($r['email'] ?? ''),
                (string) ($r['agent_code'] ?? ''),
                (string) (int) ($r['branch_outlet_id'] ?? 0),
                (string) ($r['branch_outlet_name'] ?? ''),
                (string) ($r['created_at'] ?? ''),
                (string) ($r['updated_at'] ?? ''),
                (string) ($r['deleted_at'] ?? ''),
            ];
        } else {
            $out[] = [
                (string) (int) ($r['id'] ?? 0),
                (string) ($r['masking_id'] ?? ''),
                (string) ($r['name'] ?? ''),
                (string) ($r['email'] ?? ''),
                (string) ($r['agent_code'] ?? ''),
                (string) (int) ($r['branch_outlet_id'] ?? 0),
                (string) ($r['branch_outlet_name'] ?? ''),
                (string) ($r['created_at'] ?? ''),
                (string) ($r['updated_at'] ?? ''),
            ];
        }
    }

    $fn = 'hair-specialist-agents-' . ($hsa_status === 'trash' ? 'trash-' : '') . gmdate('Y-m-d') . '.csv';
    eh_assessment_csv_stream_download($fn, $headers, $out);
}

function eh_assessment_export_csv_hair_specialist_daily_overview(): void
{
    eh_assessment_migrate_v210_hair_specialist_daily_overview();

    global $wpdb;
    $ov = eh_hair_specialist_daily_overview_table_name();
    $agent_tbl = eh_hair_specialist_agent_table_name();
    $branch_tbl = eh_branch_outlet_table_name();

    $search_term = sanitize_text_field((string) ($_GET['s'] ?? ''));
    $d_from = sanitize_text_field((string) ($_GET['overview_from'] ?? ''));
    $d_to = sanitize_text_field((string) ($_GET['overview_to'] ?? ''));

    $where_clauses = ['1=1'];
    $where_values = [];

    if ($d_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d_from)) {
        $where_clauses[] = 'o.overview_date >= %s';
        $where_values[] = $d_from;
    }
    if ($d_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d_to)) {
        $where_clauses[] = 'o.overview_date <= %s';
        $where_values[] = $d_to;
    }

    if ($search_term !== '') {
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        $branchLabel = eh_assessment_branch_outlet_label_sql('bo');
        $where_clauses[] = '(CAST(o.id AS CHAR) LIKE %s OR CAST(o.hair_specialist_agent_id AS CHAR) LIKE %s OR CAST(o.submission_assessment_count AS CHAR) LIKE %s OR CAST(o.overview_date AS CHAR) LIKE %s OR o.created_at LIKE %s OR o.updated_at LIKE %s OR a.masking_id LIKE %s OR a.agent_code LIKE %s OR a.name LIKE %s OR a.email LIKE %s OR bo.cekat_name LIKE %s OR bo.display_name LIKE %s OR ' . $branchLabel . ' LIKE %s)';
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
            $like
        );
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
    $branchLabelSel = eh_assessment_branch_outlet_label_sql('bo');

    $sql = "SELECT o.id, o.hair_specialist_agent_id, o.overview_date, o.submission_assessment_count,
            CAST(o.created_at AS CHAR) AS created_at, CAST(o.updated_at AS CHAR) AS updated_at,
            a.masking_id AS agent_masking_id, a.agent_code, a.name AS agent_name, a.email AS agent_email,
            {$branchLabelSel} AS branch_label
        FROM {$ov} AS o
        INNER JOIN {$agent_tbl} AS a ON a.id = o.hair_specialist_agent_id AND a.deleted_at IS NULL
        LEFT JOIN {$branch_tbl} AS bo ON bo.id = a.branch_outlet_id AND bo.deleted_at IS NULL
        {$where_sql}
        ORDER BY o.id ASC";

    if ($where_values !== []) {
        $sql = $wpdb->prepare($sql, ...$where_values);
    }
    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (!is_array($rows)) {
        $rows = [];
    }

    $headers = [
        'ID',
        'Hair specialist agent ID',
        'Overview date',
        'Submission count',
        'Created at',
        'Updated at',
        'Agent masking ID',
        'Agent code',
        'Hair specialist name',
        'Email',
        'Branch office',
    ];
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            (string) (int) ($r['id'] ?? 0),
            (string) (int) ($r['hair_specialist_agent_id'] ?? 0),
            (string) ($r['overview_date'] ?? ''),
            (string) (int) ($r['submission_assessment_count'] ?? 0),
            (string) ($r['created_at'] ?? ''),
            (string) ($r['updated_at'] ?? ''),
            (string) ($r['agent_masking_id'] ?? ''),
            (string) ($r['agent_code'] ?? ''),
            (string) ($r['agent_name'] ?? ''),
            (string) ($r['agent_email'] ?? ''),
            (string) ($r['branch_label'] ?? ''),
        ];
    }

    eh_assessment_csv_stream_download('hair-specialist-daily-overview-' . gmdate('Y-m-d') . '.csv', $headers, $out);
}

add_action('admin_init', 'eh_assessment_handle_admin_csv_export', 1);
