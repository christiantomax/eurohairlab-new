<?php
/**
 * Admin email when a public Online Assessment submission is stored.
 *
 * Configuration: constants in wp-config.php (see EMAIL_ADMIN, MAIL_*).
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function eh_assessment_mail_mailer_mode(): string
{
    if (!defined('MAIL_MAILER')) {
        return '';
    }

    return strtolower(trim((string) constant('MAIL_MAILER')));
}

/**
 * @param mixed $default
 */
function eh_assessment_mail_config_raw(string $constName, $default = ''): string
{
    if (!defined($constName)) {
        return is_string($default) ? $default : '';
    }

    $v = constant($constName);

    return is_scalar($v) ? trim((string) $v) : '';
}

function eh_assessment_admin_new_lead_recipient(): string
{
    $raw = eh_assessment_mail_config_raw('EMAIL_ADMIN', '');
    if ($raw === '' || !is_email($raw)) {
        return '';
    }

    return $raw;
}

function eh_assessment_format_submission_notice_footer_datetime(): string
{
    $tz = eh_assessment_gmt7_timezone();
    $dt = new DateTimeImmutable('now', $tz);

    if (class_exists(IntlDateFormatter::class)) {
        $fmt = new IntlDateFormatter(
            'id_ID',
            IntlDateFormatter::FULL,
            IntlDateFormatter::SHORT,
            $tz,
            IntlDateFormatter::GREGORIAN,
            "EEEE, d MMMM yyyy HH:mm 'WIB'"
        );
        if ($fmt !== null) {
            $out = $fmt->format($dt);
            if (is_string($out) && $out !== '') {
                return $out;
            }
        }
    }

    return wp_date('Y-m-d H:i', $dt->getTimestamp(), $tz) . ' WIB (GMT+7)';
}

/**
 * Normalize PDF template diagnosis_description for stable lookup (whitespace + dash variants).
 */
function eh_assessment_admin_mail_normalize_diagnosis_subtitle(string $s): string
{
    $s = trim(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $s = str_replace(
        ["\u{2014}", "\u{2013}", '—', '–'],
        '-',
        $s
    );
    $s = preg_replace('/\s+/u', ' ', $s) ?? '';

    return trim(strtolower($s));
}

/**
 * Full Indonesian rendering of template diagnosis_description (no shortening).
 * Unknown English strings are passed through {@see 'eh_assessment_admin_diagnosis_subtitle_translation'} unchanged.
 */
function eh_assessment_admin_mail_translate_diagnosis_subtitle(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }

    static $map = null;
    if ($map === null) {
        $en = <<<'EH_EN'
Your current hair and scalp condition is within a good range. This is the right time to build a treatment foundation that will maintain your hair quality over the long term. Healthy hair today does not happen by chance — and it will not sustain itself without care. Hormonal changes, accumulated stress, and environmental factors work silently over the years before their impact becomes visible. Patients who begin scalp care proactively — rather than reactively — are consistently in a much stronger position when these risk factors start to emerge.
EH_EN;
        $id = <<<'EH_ID'
Kondisi rambut dan kulit kepala Anda saat ini berada dalam rentang yang baik. Inilah saat yang tepat untuk membangun fondasi perawatan yang akan menjaga kualitas rambut Anda dalam jangka panjang. Rambut yang sehat saat ini tidak terjadi secara kebetulan — dan tidak akan tetap terjaga tanpa perawatan. Perubahan hormonal, stres yang menumpuk, serta faktor lingkungan bekerja secara perlahan selama bertahun-tahun sebelum dampaknya mulai terlihat. Pasien yang memulai perawatan kulit kepala secara proaktif — bukan hanya bereaksi setelah masalah muncul — secara konsisten berada pada posisi yang jauh lebih kuat ketika faktor risiko tersebut mulai terasa.
EH_ID;
        $map = [
            eh_assessment_admin_mail_normalize_diagnosis_subtitle($en) => trim($id),
        ];
    }

    $norm = eh_assessment_admin_mail_normalize_diagnosis_subtitle($raw);
    if (isset($map[$norm])) {
        return $map[$norm];
    }

    return (string) apply_filters('eh_assessment_admin_diagnosis_subtitle_translation', $raw, $norm);
}

/**
 * @param array<string, string> $rows label => value (plain text)
 */
function eh_assessment_admin_mail_kv_card(string $title, array $rows): string
{
    $buf = '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 18px;border-collapse:collapse;">';
    $buf .= '<tr><td style="background:#f9fafb;border-radius:12px;padding:20px 22px;border:1px solid #e5e7eb;">';
    $buf .= '<div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#6b7280;">' . esc_html($title) . '</div>';
    $buf .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px;border-collapse:collapse;">';

    foreach ($rows as $label => $value) {
        $buf .= '<tr><td style="padding:10px 0;border-top:1px solid #e5e7eb;">';
        $buf .= '<div style="font-size:12px;color:#6b7280;">' . esc_html($label) . '</div>';
        $buf .= '<div style="font-size:15px;font-weight:600;color:#111827;margin-top:4px;line-height:1.45;">' . nl2br(esc_html($value)) . '</div>';
        $buf .= '</td></tr>';
    }

    $buf .= '</table></td></tr></table>';

    return $buf;
}

/**
 * @param list<array{label: string, value: string}> $lines
 */
function eh_assessment_admin_mail_q_block(string $title, array $lines): string
{
    $buf = '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 18px;border-collapse:collapse;">';
    $buf .= '<tr><td style="background:#ffffff;border-radius:12px;padding:20px 22px;border:1px solid #e5e7eb;">';
    $buf .= '<div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#6b7280;">' . esc_html($title) . '</div>';
    $buf .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;border-collapse:collapse;">';

    foreach ($lines as $row) {
        $buf .= '<tr><td style="padding:8px 0;font-size:14px;color:#374151;line-height:1.5;border-bottom:1px solid #f3f4f6;">';
        $buf .= '<span style="color:#111827;font-weight:600;">' . esc_html($row['label']) . '</span>';
        $buf .= '<span style="color:#9ca3af;"> · </span>';
        $buf .= esc_html($row['value']);
        $buf .= '</td></tr>';
    }

    $buf .= '</table></td></tr></table>';

    return $buf;
}

/**
 * @param array<string, mixed> $sanitized
 */
function eh_assessment_build_new_lead_admin_email_html(
    array $sanitized,
    string $masked_id,
    int $branch_outlet_id,
    string $agent_masking_id,
    int $submission_id
): string {
    $body = eh_assessment_cekat_submission_saved_webhook_body(
        $sanitized,
        $masked_id,
        $branch_outlet_id,
        $agent_masking_id,
        $submission_id
    );

    $sub = is_array($body['submission'] ?? null) ? $body['submission'] : [];
    $resp = is_array($body['respondent'] ?? null) ? $body['respondent'] : [];
    $answers = is_array($body['answers'] ?? null) ? $body['answers'] : [];

    $comp = is_array($sanitized['computed'] ?? null) ? $sanitized['computed'] : [];

    $reportType = (int) ($sanitized['submission']['report_type'] ?? $comp['computed_report_type'] ?? 1);
    if ($reportType < 1 || $reportType > 8) {
        $reportType = 1;
    }
    $tplDiag = eh_assessment_clinical_diagnosis_from_pdf_template($reportType);
    // Email admin: utamakan profil klinis (ID) daripada nama diagnosis template (sering bahasa Inggris).
    $diagnosisTitle = trim((string) ($comp['computed_condition_title'] ?? ''));
    if ($diagnosisTitle === '') {
        $diagnosisTitle = $tplDiag['title'] !== '' ? $tplDiag['title'] : eh_assessment_condition_title_id($reportType);
    }
    $diagnosisLevel = (int) ($comp['computed_score'] ?? 0);

    $tplSubtitle = trim((string) ($tplDiag['subtitle'] ?? ''));
    if ($tplSubtitle !== '') {
        $diagnosisKeterangan = eh_assessment_admin_mail_translate_diagnosis_subtitle($tplSubtitle);
    } else {
        $diagnosisKeterangan = trim((string) ($comp['computed_urgency_text'] ?? ''));
        if ($diagnosisKeterangan === '') {
            $diagnosisKeterangan = '';
        }
    }

    $branch = trim((string) ($sub['branch_office_name'] ?? ''));
    if ($branch === '') {
        $branch = 'EUROHAIRLAB';
    }

    $name = trim((string) ($resp['name'] ?? ''));
    $wa = trim((string) ($resp['whatsapp'] ?? ''));
    $leadSource = trim((string) ($sub['lead_source'] ?? ''));

    $condition = trim((string) ($sub['clinical_profile'] ?? ''));
    $weighted = (int) ($comp['computed_score'] ?? 0);
    $scalp = (int) ($comp['score_visual_scalp'] ?? 0);
    $fol = (int) ($comp['score_visual_follicle'] ?? 0);
    $scoreTriple = sprintf('%d/%d/%d', $weighted, min(100, max(0, $scalp) * 10), min(100, max(0, $fol) * 10));

    $band = trim((string) ($sub['band'] ?? ''));
    $tipeNum = (int) ($sub['patient_type'] ?? 0);
    $tipeStr = $tipeNum > 0 ? (string) $tipeNum : '-';
    $strategy = trim((string) ($sub['strategy'] ?? ''));

    $pdfUrl = eh_assessment_get_public_report_download_url_for_json($submission_id, $masked_id);

    $qAnswer = static function (string $key, array $answersBlock): string {
        $block = is_array($answersBlock[$key] ?? null) ? $answersBlock[$key] : [];
        $a = trim((string) ($block['answer'] ?? ''));

        return $a !== '' ? $a : '—';
    };

    $clinicalLines = [
        ['label' => 'Q1 (Area perubahan)', 'value' => $qAnswer('q1_focus_area', $answers)],
        ['label' => 'Q3 (Durasi)', 'value' => $qAnswer('q3_duration', $answers)],
        ['label' => 'Q4 (Riwayat genetik)', 'value' => $qAnswer('q4_family_history', $answers)],
        ['label' => 'Q6 (Faktor pemicu)', 'value' => $qAnswer('q6_trigger_factors', $answers)],
        ['label' => 'Q8 (Riwayat konsultasi)', 'value' => $qAnswer('q8_previous_consultation', $answers)],
    ];
    $behaviorLines = [
        ['label' => 'Q2 (Dampak utama)', 'value' => $qAnswer('q2_main_impact', $answers)],
        ['label' => 'Q5 (Riwayat perawatan)', 'value' => $qAnswer('q5_previous_attempts', $answers)],
        ['label' => 'Q7 (Kekhawatiran terbesar)', 'value' => $qAnswer('q7_biggest_worry', $answers)],
        ['label' => 'Q9 (Harapan hasil)', 'value' => $qAnswer('q9_expected_result', $answers)],
    ];

    $peringatanLine = $condition !== '' ? $condition : '—';

    $hero = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;border-collapse:collapse;">';
    $hero .= '<tr><td style="background:linear-gradient(135deg,#111827 0%,#1f2937 100%);border-radius:12px;padding:22px 24px;">';
    $hero .= '<div style="font-size:12px;font-weight:700;letter-spacing:0.12em;color:#9ca3af;">NEW LEAD</div>';
    $hero .= '<div style="font-size:20px;font-weight:700;color:#ffffff;margin-top:8px;line-height:1.35;">Hair Online Assessment</div>';
    $hero .= '<div style="font-size:14px;color:#d1d5db;margin-top:6px;">Report ID: <span style="color:#fff;font-weight:600;">' . esc_html($masked_id) . '</span></div>';
    $hero .= '</td></tr></table>';

    $diagnosisRows = [
        'Nama diagnosis' => $diagnosisTitle !== '' ? $diagnosisTitle : '—',
    ];
    if ($diagnosisLevel > 0) {
        $diagnosisRows['Level (indikator laporan)'] = (string) $diagnosisLevel;
    }
    if ($diagnosisKeterangan !== '') {
        $diagnosisRows['Keterangan diagnosis'] = $diagnosisKeterangan;
    }

    $summaryRows = [
        'Nama' => $name !== '' ? $name : '—',
        'WhatsApp' => $wa !== '' ? $wa : '—',
        'Cabang' => $branch,
        'ID laporan' => $masked_id,
        'Profil klinis' => $condition !== '' ? $condition : '—',
        'Skor (utama / visual)' => $scoreTriple,
        'Band' => $band !== '' ? $band : '—',
        'Tipe pasien' => $tipeStr,
        'Strategi komunikasi' => $strategy !== '' ? $strategy : '—',
        'Sumber' => $leadSource !== '' ? $leadSource : '—',
    ];

    $sla = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border-collapse:collapse;">';
    $sla .= '<tr><td style="background:#fffbeb;border-radius:12px;padding:16px 20px;border:1px solid #fcd34d;">';
    $sla .= '<div style="font-size:14px;font-weight:700;color:#92400e;">Target respons (SLA)</div>';
    $sla .= '<div style="font-size:14px;color:#78350f;margin-top:6px;line-height:1.5;">Respons target <strong>45 menit</strong>. Batas maksimum <strong>1 jam</strong>.</div>';
    $sla .= '</td></tr></table>';

    $pdfBlock = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border-collapse:collapse;">';
    $pdfBlock .= '<tr><td style="background:#eff6ff;border-radius:12px;padding:16px 20px;border:1px solid #bfdbfe;">';
    $pdfBlock .= '<div style="font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#1d4ed8;">PDF laporan</div>';
    if ($pdfUrl !== '') {
        $pdfBlock .= '<div style="margin-top:10px;font-size:15px;"><a href="' . esc_url($pdfUrl) . '" style="color:#1d4ed8;font-weight:600;">Unduh / buka tautan laporan</a></div>';
    } else {
        $pdfBlock .= '<div style="margin-top:10px;font-size:14px;color:#374151;">—</div>';
    }
    $pdfBlock .= '</td></tr></table>';

    $warn = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border-collapse:collapse;">';
    $warn .= '<tr><td style="background:#fef2f2;border-radius:12px;padding:16px 20px;border:1px solid #fecaca;">';
    $warn .= '<div style="font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#b91c1c;">Profil / peringatan klinis</div>';
    $warn .= '<div style="margin-top:8px;font-size:15px;font-weight:600;color:#7f1d1d;line-height:1.45;">' . esc_html($peringatanLine) . '</div>';
    $warn .= '</td></tr></table>';

    $footerText = 'Email ini dikirim otomatis dari sistem WordPress EUROHAIRLAB (form Online Assessment / hair submission). '
        . 'Waktu pengiriman oleh pengguna (GMT+7): ' . eh_assessment_format_submission_notice_footer_datetime() . '.';

    $footer = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;border-collapse:collapse;">';
    $footer .= '<tr><td style="padding:18px 4px 0;font-size:12px;color:#6b7280;line-height:1.6;">' . esc_html($footerText) . '</td></tr></table>';

    $wrap = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:24px 12px;background:#e5e7eb;">';
    $wrap .= '<table role="presentation" align="center" cellpadding="0" cellspacing="0" width="100%" style="max-width:640px;margin:0 auto;border-collapse:collapse;">';
    $wrap .= '<tr><td style="font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;">';
    $wrap .= $hero;
    $wrap .= eh_assessment_admin_mail_kv_card('Diagnosis klinis', $diagnosisRows);
    $wrap .= eh_assessment_admin_mail_kv_card('Ringkasan lead', $summaryRows);
    $wrap .= eh_assessment_admin_mail_q_block('Data klinis', $clinicalLines);
    $wrap .= eh_assessment_admin_mail_q_block('Data perilaku', $behaviorLines);
    $wrap .= $warn;
    $wrap .= $pdfBlock;
    $wrap .= $sla;
    $wrap .= $footer;
    $wrap .= '</td></tr></table></body></html>';

    return $wrap;
}

/**
 * @param array<string, mixed> $sanitized
 */
function eh_assessment_send_new_lead_admin_notification(
    string $masked_id,
    int $branch_outlet_id,
    string $agent_masking_id,
    array $sanitized,
    int $submission_id
): void {
    if (!apply_filters('eh_assessment_send_new_lead_admin_email', true, $masked_id, $submission_id, $sanitized)) {
        error_log('[eurohairlab-assessment][admin-mail] skip: filter eh_assessment_send_new_lead_admin_email returned false. report=' . $masked_id);

        return;
    }

    $to = eh_assessment_admin_new_lead_recipient();
    if ($to === '') {
        $raw = eh_assessment_mail_config_raw('EMAIL_ADMIN', '');
        $hint = !defined('EMAIL_ADMIN')
            ? 'constant EMAIL_ADMIN is not defined in wp-config.php'
            : ($raw === '' ? 'EMAIL_ADMIN is empty' : 'EMAIL_ADMIN fails WordPress is_email() check');
        error_log('[eurohairlab-assessment][admin-mail] skip: no valid recipient. ' . $hint . ' report=' . $masked_id);

        return;
    }

    $mailerMode = eh_assessment_mail_mailer_mode();

    $subject = sprintf(
        '[EUROHAIRLAB] New Lead – Online Assessment (Report ID: %s)',
        $masked_id
    );

    $message = eh_assessment_build_new_lead_admin_email_html(
        $sanitized,
        $masked_id,
        $branch_outlet_id,
        $agent_masking_id,
        $submission_id
    );

    $fromName = eh_assessment_mail_config_raw('MAIL_FROM_NAME', '[EUROHAIRLAB] Online Assessment System');
    $fromAddr = eh_assessment_mail_config_raw('MAIL_FROM_ADDRESS', '');
    if ($fromAddr === '' || !is_email($fromAddr)) {
        $fromAddr = (string) get_option('admin_email');
    }

    $fromNameFilter = static function () use ($fromName): string {
        return $fromName;
    };
    $fromEmailFilter = static function () use ($fromAddr): string {
        return $fromAddr;
    };
    $contentTypeFilter = static function (): string {
        return 'text/html';
    };

    $lastMailError = '';
    $onMailFailed = static function ($error) use (&$lastMailError): void {
        if ($error instanceof WP_Error) {
            $lastMailError = $error->get_error_message();
            $data = $error->get_error_data();
            if (is_array($data) && isset($data['phpmailer_exception_code'])) {
                $lastMailError .= ' (code ' . (string) $data['phpmailer_exception_code'] . ')';
            }
        }
    };

    add_filter('wp_mail_from_name', $fromNameFilter, 999);
    add_filter('wp_mail_from', $fromEmailFilter, 999);
    add_filter('wp_mail_content_type', $contentTypeFilter, 999);
    add_action('wp_mail_failed', $onMailFailed, 10, 1);

    $sent = wp_mail($to, $subject, $message);

    remove_action('wp_mail_failed', $onMailFailed, 10);
    remove_filter('wp_mail_from_name', $fromNameFilter, 999);
    remove_filter('wp_mail_from', $fromEmailFilter, 999);
    remove_filter('wp_mail_content_type', $contentTypeFilter, 999);

    if ($mailerMode === 'log') {
        error_log(
            '[eurohairlab-assessment][admin-mail] MAIL_MAILER=log: nothing delivered to inbox; payload was logged as [mail:log]. report='
            . $masked_id . ' intended_to=' . $to
        );

        return;
    }

    if ($sent) {
        error_log(
            '[eurohairlab-assessment][admin-mail] wp_mail ok report=' . $masked_id
            . ' to=' . $to . ' from=' . $fromAddr . ' mailer=' . ($mailerMode !== '' ? $mailerMode : 'default')
        );

        return;
    }

    error_log(
        '[eurohairlab-assessment][admin-mail] wp_mail FAILED report=' . $masked_id
        . ' to=' . $to . ' from=' . $fromAddr . ' mailer=' . ($mailerMode !== '' ? $mailerMode : 'default')
        . ( $lastMailError !== '' ? ' wp_mail_failed: ' . $lastMailError : ' (no wp_mail_failed message; check server mail / SMTP)' )
    );
}

/**
 * Email the Hair Specialist agent when their assigned Online Assessment lead is stored.
 * Skips when the agent row has no valid email (admin notification still goes to EMAIL_ADMIN).
 *
 * @param array<string, mixed> $sanitized
 */
function eh_assessment_send_new_lead_hair_specialist_notification(
    string $masked_id,
    int $branch_outlet_id,
    string $agent_masking_id,
    array $sanitized,
    int $submission_id
): void {
    if (!apply_filters('eh_assessment_send_new_lead_hair_specialist_email', true, $masked_id, $submission_id, $sanitized)) {
        error_log('[eurohairlab-assessment][specialist-mail] skip: filter eh_assessment_send_new_lead_hair_specialist_email returned false. report=' . $masked_id);

        return;
    }

    $agent_masking_id = eh_assessment_normalize_agent_masking_id($agent_masking_id);
    if ($agent_masking_id === '') {
        return;
    }

    global $wpdb;
    $table = eh_hair_specialist_agent_table_name();
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT name, email FROM {$table} WHERE deleted_at IS NULL AND masking_id = %s LIMIT 1",
            $agent_masking_id
        ),
        ARRAY_A
    );
    if (!is_array($row)) {
        error_log('[eurohairlab-assessment][specialist-mail] skip: agent row not found. report=' . $masked_id);

        return;
    }

    $to = trim((string) ($row['email'] ?? ''));
    if ($to === '' || !is_email($to)) {
        error_log('[eurohairlab-assessment][specialist-mail] skip: agent email empty or invalid. report=' . $masked_id);

        return;
    }

    $mailerMode = eh_assessment_mail_mailer_mode();
    $agentLabel = trim((string) ($row['name'] ?? ''));
    $subject = sprintf(
        '[EUROHAIRLAB] New lead assigned to you — Online Assessment (Report ID: %s)',
        $masked_id
    );

    $message = eh_assessment_build_new_lead_admin_email_html(
        $sanitized,
        $masked_id,
        $branch_outlet_id,
        $agent_masking_id,
        $submission_id
    );

    $fromName = eh_assessment_mail_config_raw('MAIL_FROM_NAME', '[EUROHAIRLAB] Online Assessment System');
    $fromAddr = eh_assessment_mail_config_raw('MAIL_FROM_ADDRESS', '');
    if ($fromAddr === '' || !is_email($fromAddr)) {
        $fromAddr = (string) get_option('admin_email');
    }

    $fromNameFilter = static function () use ($fromName): string {
        return $fromName;
    };
    $fromEmailFilter = static function () use ($fromAddr): string {
        return $fromAddr;
    };
    $contentTypeFilter = static function (): string {
        return 'text/html';
    };

    $lastMailError = '';
    $onMailFailed = static function ($error) use (&$lastMailError): void {
        if ($error instanceof WP_Error) {
            $lastMailError = $error->get_error_message();
            $data = $error->get_error_data();
            if (is_array($data) && isset($data['phpmailer_exception_code'])) {
                $lastMailError .= ' (code ' . (string) $data['phpmailer_exception_code'] . ')';
            }
        }
    };

    add_filter('wp_mail_from_name', $fromNameFilter, 999);
    add_filter('wp_mail_from', $fromEmailFilter, 999);
    add_filter('wp_mail_content_type', $contentTypeFilter, 999);
    add_action('wp_mail_failed', $onMailFailed, 10, 1);

    $sent = wp_mail($to, $subject, $message);

    remove_action('wp_mail_failed', $onMailFailed, 10);
    remove_filter('wp_mail_from_name', $fromNameFilter, 999);
    remove_filter('wp_mail_from', $fromEmailFilter, 999);
    remove_filter('wp_mail_content_type', $contentTypeFilter, 999);

    if ($mailerMode === 'log') {
        error_log(
            '[eurohairlab-assessment][specialist-mail] MAIL_MAILER=log: nothing delivered; report='
            . $masked_id . ' intended_to=' . $to . ($agentLabel !== '' ? ' agent=' . $agentLabel : '')
        );

        return;
    }

    if ($sent) {
        error_log(
            '[eurohairlab-assessment][specialist-mail] wp_mail ok report=' . $masked_id
            . ' to=' . $to . ' from=' . $fromAddr . ' mailer=' . ($mailerMode !== '' ? $mailerMode : 'default')
        );

        return;
    }

    error_log(
        '[eurohairlab-assessment][specialist-mail] wp_mail FAILED report=' . $masked_id
        . ' to=' . $to . ' from=' . $fromAddr
        . ($lastMailError !== '' ? ' wp_mail_failed: ' . $lastMailError : '')
    );
}

/**
 * @param null|bool $return
 * @param array<string, mixed> $atts
 * @return null|bool
 */
function eh_assessment_mail_pre_wp_mail_log($return, array $atts)
{
    if (null !== $return) {
        return $return;
    }

    if (eh_assessment_mail_mailer_mode() !== 'log') {
        return null;
    }

    $to = isset($atts['to']) ? $atts['to'] : '';
    $subject = isset($atts['subject']) ? $atts['subject'] : '';
    $snippet = isset($atts['message']) ? wp_strip_all_tags((string) $atts['message']) : '';
    $snippet = substr(preg_replace('/\s+/', ' ', $snippet) ?? '', 0, 500);
    error_log(sprintf('[eurohairlab-assessment][mail:log] to=%s subject=%s body=%s', wp_json_encode($to), $subject, $snippet));

    return true;
}

/**
 * @param PHPMailer\PHPMailer\PHPMailer|\WP_PHPMailer $phpmailer
 */
function eh_assessment_mail_phpmailer_init_smtp($phpmailer): void
{
    if (eh_assessment_mail_mailer_mode() !== 'smtp') {
        return;
    }

    $host = eh_assessment_mail_config_raw('MAIL_HOST', '');
    if ($host === '') {
        return;
    }

    $port = (int) eh_assessment_mail_config_raw('MAIL_PORT', '587');
    if ($port <= 0) {
        $port = 587;
    }

    $user = eh_assessment_mail_config_raw('MAIL_USERNAME', '');
    if ($user === '') {
        $user = eh_assessment_mail_config_raw('MAIL_FROM_ADDRESS', '');
    }

    $pass = eh_assessment_mail_config_raw('MAIL_PASSWORD', '');
    $enc = strtolower(eh_assessment_mail_config_raw('MAIL_ENCRYPTION', 'tls'));

    $phpmailer->isSMTP();
    $phpmailer->Host = $host;
    $phpmailer->Port = $port;
    $phpmailer->SMTPAuth = true;
    $phpmailer->Username = $user;
    $phpmailer->Password = $pass;

    if ($enc === 'ssl') {
        $phpmailer->SMTPSecure = 'ssl';
    } elseif ($enc === 'tls') {
        $phpmailer->SMTPSecure = 'tls';
    } else {
        $phpmailer->SMTPSecure = '';
    }

    $phpmailer->CharSet = 'UTF-8';
}

add_filter('pre_wp_mail', 'eh_assessment_mail_pre_wp_mail_log', 5, 2);
add_action('phpmailer_init', 'eh_assessment_mail_phpmailer_init_smtp', 5, 1);
