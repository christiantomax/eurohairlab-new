<?php
/**
 * Hair Specialist Daily Overview: per-agent per-day submission counts and round-robin assignment.
 *
 * Round-robin is strictly daily (calendar date in GMT+7, same as {@see eh_assessment_daily_overview_today_ymd_gmt7()}):
 * only `submission_assessment_count` for that `overview_date` is used to pick the next agent. Counts from
 * previous days are ignored for assignment, so load “resets” at each new day—agents start the day tied at 0
 * until the first submissions create/raise rows for that date.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function eh_hair_specialist_daily_overview_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'eh_hair_specialist_daily_overview';
}

/**
 * Create / migrate the daily overview table (round-robin load accounting).
 */
function eh_assessment_migrate_v210_hair_specialist_daily_overview(): void
{
    if ((string) get_option('eh_assessment_v210_hair_specialist_daily_overview', '') === '1') {
        return;
    }

    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = eh_hair_specialist_daily_overview_table_name();
    $found = (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($found !== $table) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hair_specialist_agent_id BIGINT UNSIGNED NOT NULL,
            overview_date DATE NOT NULL,
            submission_assessment_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_agent_overview_date (hair_specialist_agent_id, overview_date),
            KEY idx_overview_date (overview_date),
            KEY idx_agent (hair_specialist_agent_id)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    update_option('eh_assessment_v210_hair_specialist_daily_overview', '1');
}

/**
 * Calendar date (Y-m-d) in GMT+7 for daily overview buckets (matches site assessment time semantics).
 */
function eh_assessment_daily_overview_today_ymd_gmt7(): string
{
    try {
        $dt = new DateTimeImmutable('now', eh_assessment_gmt7_timezone());
    } catch (Throwable) {
        return gmdate('Y-m-d');
    }

    return $dt->format('Y-m-d');
}

/**
 * Active Hair Specialist agents in stable round-robin order (lowest id first).
 *
 * @return list<array<string, mixed>>
 */
function eh_assessment_daily_overview_list_active_agents_ordered(): array
{
    global $wpdb;
    $agent_table = eh_hair_specialist_agent_table_name();
    $rows = $wpdb->get_results(
        "SELECT id, masking_id, name, email, agent_code, branch_outlet_id FROM {$agent_table} WHERE deleted_at IS NULL ORDER BY id ASC",
        ARRAY_A
    );

    return is_array($rows) ? $rows : [];
}

/**
 * @param list<int> $agent_ids
 * @return array<int, int> agent id => count for that date
 */
function eh_assessment_daily_overview_counts_for_date(string $overviewYmd, array $agent_ids): array
{
    if ($agent_ids === []) {
        return [];
    }

    global $wpdb;
    $table = eh_hair_specialist_daily_overview_table_name();
    $ids = array_values(array_unique(array_filter(array_map('intval', $agent_ids), static fn ($id) => $id > 0)));
    if ($ids === []) {
        return [];
    }

    $in = implode(',', $ids);
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- IN list is built from integers only
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT hair_specialist_agent_id, submission_assessment_count FROM {$table} WHERE overview_date = %s AND hair_specialist_agent_id IN ({$in})",
            $overviewYmd
        ),
        ARRAY_A
    );
    $out = [];
    foreach ($ids as $id) {
        $out[$id] = 0;
    }
    if (is_array($rows)) {
        foreach ($rows as $r) {
            $aid = (int) ($r['hair_specialist_agent_id'] ?? 0);
            if ($aid > 0) {
                $out[$aid] = (int) ($r['submission_assessment_count'] ?? 0);
            }
        }
    }

    return $out;
}

/**
 * Pick next agent for round-robin on a single calendar day only (`$overviewYmd`, typically “today” GMT+7).
 * Uses per-day counts only — not cumulative across days — so fairness resets every new date.
 *
 * @param string $overviewYmd Date `Y-m-d` (must match the bucket used when incrementing after save).
 * @return array<string, mixed>|null
 */
function eh_assessment_pick_round_robin_agent_for_date(string $overviewYmd): ?array
{
    $agents = eh_assessment_daily_overview_list_active_agents_ordered();
    if ($agents === []) {
        return null;
    }

    $ids = [];
    foreach ($agents as $a) {
        $ids[] = (int) ($a['id'] ?? 0);
    }
    $counts = eh_assessment_daily_overview_counts_for_date($overviewYmd, $ids);

    $best = null;
    $bestCount = PHP_INT_MAX;
    $bestId = PHP_INT_MAX;
    foreach ($agents as $a) {
        $id = (int) ($a['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $c = (int) ($counts[$id] ?? 0);
        if ($c < $bestCount || ($c === $bestCount && $id < $bestId)) {
            $bestCount = $c;
            $bestId = $id;
            $best = $a;
        }
    }

    return $best;
}

function eh_assessment_daily_overview_increment(int $hair_specialist_agent_id, string $overviewYmd): void
{
    if ($hair_specialist_agent_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $overviewYmd)) {
        return;
    }

    global $wpdb;
    $table = eh_hair_specialist_daily_overview_table_name();
    $now = eh_assessment_current_mysql_time();

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from trusted prefix helper
    $sql = $wpdb->prepare(
        "INSERT INTO {$table} (hair_specialist_agent_id, overview_date, submission_assessment_count, created_at, updated_at)
         VALUES (%d, %s, 1, %s, %s)
         ON DUPLICATE KEY UPDATE submission_assessment_count = submission_assessment_count + 1, updated_at = %s",
        $hair_specialist_agent_id,
        $overviewYmd,
        $now,
        $now,
        $now
    );
    $wpdb->query($sql);
}

/**
 * If submission has no hair specialist agent yet, assign the round-robin agent using today’s GMT+7 date only.
 * Yesterday’s overview rows do not affect today’s pick (daily reset of round-robin load).
 * Returns metadata for daily overview increment after successful insert.
 *
 * @param array<string, mixed> $sanitized
 * @return array{agent_db_id: int, overview_date: string, agent_masking_id: string, assigned_via_round_robin: bool}
 */
function eh_assessment_apply_agent_assignment_for_submission(array &$sanitized): array
{
    $today = eh_assessment_daily_overview_today_ymd_gmt7();
    $mid = trim((string) ($sanitized['submission']['agent_masking_id'] ?? ''));
    $assignedViaRr = false;

    if ($mid === '') {
        $picked = eh_assessment_pick_round_robin_agent_for_date($today);
        if ($picked !== null) {
            $mid = eh_assessment_normalize_agent_masking_id((string) ($picked['masking_id'] ?? ''));
            if ($mid !== '') {
                $sanitized['submission']['agent_masking_id'] = $mid;
                $assignedViaRr = true;
            }
        }
    }

    $agent_db_id = 0;
    if ($mid !== '') {
        global $wpdb;
        $agent_table = eh_hair_specialist_agent_table_name();
        $agent_db_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$agent_table} WHERE deleted_at IS NULL AND masking_id = %s LIMIT 1",
                $mid
            )
        );
    }

    return [
        'agent_db_id' => $agent_db_id,
        'overview_date' => $today,
        'agent_masking_id' => $mid,
        'assigned_via_round_robin' => $assignedViaRr,
    ];
}

function eh_assessment_render_hair_specialist_daily_overview_page(): void
{
    if (!eh_assessment_current_user_can_access_admin()) {
        wp_die('You do not have permission to access this page.');
    }

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

    $allowed_orderby = [
        'id' => 'o.id',
        'overview_date' => 'o.overview_date',
        'submission_assessment_count' => 'o.submission_assessment_count',
        'agent_name' => 'a.name',
        'agent_code' => 'a.agent_code',
        'updated_at' => 'o.updated_at',
    ];
    $orderby_key = sanitize_key((string) ($_GET['orderby'] ?? 'id'));
    $orderby_sql = $allowed_orderby[$orderby_key] ?? 'o.id';
    $order_sql = strtolower(sanitize_text_field((string) ($_GET['order'] ?? 'asc'))) === 'desc' ? 'DESC' : 'ASC';

    $sql = "SELECT o.id, o.hair_specialist_agent_id, o.overview_date, o.submission_assessment_count, o.created_at, o.updated_at,
            a.masking_id AS agent_masking_id, a.agent_code, a.name AS agent_name, a.email AS agent_email,
            {$branchLabelSel} AS branch_label
        FROM {$ov} AS o
        INNER JOIN {$agent_tbl} AS a ON a.id = o.hair_specialist_agent_id AND a.deleted_at IS NULL
        LEFT JOIN {$branch_tbl} AS bo ON bo.id = a.branch_outlet_id AND bo.deleted_at IS NULL
        {$where_sql}
        ORDER BY {$orderby_sql} {$order_sql}, o.id ASC";

    if ($where_values !== []) {
        $sql = $wpdb->prepare($sql, ...$where_values);
    }

    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (!is_array($rows)) {
        $rows = [];
    }

    $base_args = ['page' => 'eh-hair-specialist-daily-overview'];
    if ($search_term !== '') {
        $base_args['s'] = $search_term;
    }
    if ($d_from !== '') {
        $base_args['overview_from'] = $d_from;
    }
    if ($d_to !== '') {
        $base_args['overview_to'] = $d_to;
    }

    echo '<div class="wrap">';
    echo '<h1>Hair Specialist Daily Overview</h1>';

    echo '<form method="get" action="" style="margin:12px 0 16px;width:100%;box-sizing:border-box;display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:flex-end;">';
    echo '<input type="hidden" name="page" value="eh-hair-specialist-daily-overview">';
    echo '<label for="eh-hsdo-from" style="font-size:12px;color:#50575e;">Overview date</label>';
    echo '<input id="eh-hsdo-from" type="date" name="overview_from" value="' . esc_attr($d_from) . '">';
    echo '<span style="color:#50575e;">to</span>';
    echo '<input type="date" name="overview_to" value="' . esc_attr($d_to) . '">';
    echo '<input type="search" name="s" value="' . esc_attr($search_term) . '" class="regular-text" placeholder="Search all columns…">';
    echo '<button type="submit" class="button">Apply</button>';
    echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=eh-hair-specialist-daily-overview')) . '">Reset</a>';
    $csv_do_extra = [];
    if ($search_term !== '') {
        $csv_do_extra['s'] = $search_term;
    }
    if ($d_from !== '') {
        $csv_do_extra['overview_from'] = $d_from;
    }
    if ($d_to !== '') {
        $csv_do_extra['overview_to'] = $d_to;
    }
    echo ' <a class="button" href="' . esc_url(eh_assessment_admin_export_csv_url('eh-hair-specialist-daily-overview', $csv_do_extra)) . '">Export CSV</a>';
    echo '</form>';

    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'id', 'ID') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'overview_date', 'Date') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'agent_name', 'Hair specialist') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'agent_code', 'Agent code') . '</th>';
    echo '<th>Agent masking ID</th>';
    echo '<th>Email</th>';
    echo '<th>Branch Office</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'submission_assessment_count', 'Submission count') . '</th>';
    echo '<th>' . eh_assessment_admin_sort_link($base_args, 'updated_at', 'Updated') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows === []) {
        echo '<tr><td colspan="9">No overview rows for this range.</td></tr>';
    } else {
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . (int) ($row['id'] ?? 0) . '</td>';
            echo '<td>' . esc_html(eh_assessment_format_admin_date((string) ($row['overview_date'] ?? ''))) . '</td>';
            echo '<td>' . esc_html((string) ($row['agent_name'] ?? '')) . '</td>';
            echo '<td><code>' . esc_html((string) ($row['agent_code'] ?? '')) . '</code></td>';
            echo '<td style="word-break:break-all;"><code>' . esc_html((string) ($row['agent_masking_id'] ?? '')) . '</code></td>';
            $em = trim((string) ($row['agent_email'] ?? ''));
            echo '<td>' . ($em !== '' ? esc_html($em) : '—') . '</td>';
            $bl = trim((string) ($row['branch_label'] ?? ''));
            echo '<td>' . ($bl !== '' ? esc_html($bl) : '—') . '</td>';
            echo '<td>' . (int) ($row['submission_assessment_count'] ?? 0) . '</td>';
            echo '<td>' . esc_html(eh_assessment_format_admin_datetime((string) ($row['updated_at'] ?? ''))) . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table></div>';
}
