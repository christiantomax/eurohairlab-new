<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('eh_report_preview_value')) {
    function eh_report_preview_value(array $data, string $path, string $default = ''): string
    {
        $cursor = $data;
        foreach (explode('.', $path) as $key) {
            if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
                return $default;
            }
            $cursor = $cursor[$key];
        }

        return is_string($cursor) || is_numeric($cursor) ? (string) $cursor : $default;
    }
}

if (!function_exists('eh_report_preview_asset_url')) {
    /**
     * Browser preview: public HTTP URL (e.g. http://localhost:8081/wp-content/plugins/.../assets/report/...).
     * PDF (Dompdf): absolute filesystem path under plugin directory.
     */
    function eh_report_preview_asset_url(string $file): string
    {
        $file = ltrim($file, '/');
        $path = __DIR__ . '/assets/report/' . $file;
        if (defined('EH_ASSESSMENT_REPORT_PDF_RENDER') && EH_ASSESSMENT_REPORT_PDF_RENDER) {
            return $path;
        }

        return plugins_url('assets/report/' . $file, __FILE__);
    }
}

if (!function_exists('eh_report_preview_upload_or_content_url_to_path')) {
    /**
     * Map a same-site URL to a local filesystem path when possible (better for Dompdf than HTTP).
     */
    function eh_report_preview_upload_or_content_url_to_path(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts) || empty($parts['path'])) {
            return '';
        }

        $pathOnly = (string) $parts['path'];
        $uploads = wp_upload_dir();
        if (($uploads['error'] ?? '') === '' && !empty($uploads['baseurl']) && !empty($uploads['basedir'])) {
            $uploadBase = wp_parse_url($uploads['baseurl'], PHP_URL_PATH);
            if (is_string($uploadBase) && $uploadBase !== '' && str_starts_with($pathOnly, $uploadBase)) {
                $suffix = substr($pathOnly, strlen($uploadBase));

                return rtrim($uploads['basedir'], '/') . '/' . ltrim($suffix, '/');
            }
        }

        $contentBase = wp_parse_url(content_url('/'), PHP_URL_PATH);
        if (is_string($contentBase) && $contentBase !== '' && str_starts_with($pathOnly, $contentBase)) {
            $suffix = substr($pathOnly, strlen($contentBase));

            return rtrim(WP_CONTENT_DIR, '/') . '/' . ltrim($suffix, '/');
        }

        $sitePath = wp_parse_url(site_url('/'), PHP_URL_PATH);
        if (is_string($sitePath) && $sitePath !== '' && str_starts_with($pathOnly, $sitePath)) {
            $suffix = substr($pathOnly, strlen($sitePath));

            return rtrim(ABSPATH, '/') . '/' . ltrim($suffix, '/');
        }

        return '';
    }
}

if (!function_exists('eh_report_preview_treatment_image_src')) {
    /**
     * @param string $raw URL or path from report PDF template; empty uses $fallback (bundled asset).
     */
    function eh_report_preview_treatment_image_src(string $raw, string $fallback): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return $fallback;
        }

        $is_pdf = defined('EH_ASSESSMENT_REPORT_PDF_RENDER') && EH_ASSESSMENT_REPORT_PDF_RENDER;

        if (preg_match('#^https?://#i', $raw)) {
            if ($is_pdf) {
                $local = eh_report_preview_upload_or_content_url_to_path($raw);
                if ($local !== '' && is_readable($local)) {
                    return $local;
                }
            }

            return $raw;
        }

        if ($raw !== '' && is_readable($raw)) {
            return $raw;
        }

        $underRoot = rtrim(ABSPATH, '/') . '/' . ltrim($raw, '/');
        if (is_readable($underRoot)) {
            return $underRoot;
        }

        return $fallback;
    }
}

if (!function_exists('eh_report_preview_overview_image_src')) {
    /**
     * Choose the clinical overview image by patient gender, falling back to the bundled default.
     *
     * @param array<string, mixed> $report
     */
    function eh_report_preview_overview_image_src(array $report, string $fallback): string
    {
        $gender = strtolower(trim((string) ($report['patient']['gender'] ?? '')));
        $template = isset($report['pdf_template']) && is_array($report['pdf_template']) ? $report['pdf_template'] : [];
        $candidate = '';

        if ($gender === 'male') {
            $candidate = trim((string) ($template['phase_of_hair_growth_male_image'] ?? ''));
        } elseif ($gender === 'female') {
            $candidate = trim((string) ($template['phase_of_hair_growth_female_image'] ?? ''));
        }

        return eh_report_preview_treatment_image_src($candidate, $fallback);
    }
}

if (!function_exists('eh_report_preview_render_bullet_list')) {
    function eh_report_preview_render_bullet_list(array $items, string $color = '#1e1a17'): void
    {
        echo '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">';
        foreach ($items as $item) {
            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }

            echo '<tr>';
            echo '<td width="18" valign="top" class="ff-mt" style="padding:0 0 4px 0;color:' . esc_attr($color) . ';font-size:13px;line-height:1.4;">&bull;</td>';
            echo '<td valign="top" class="ff-mt" style="padding:0 0 4px 0;color:' . esc_attr($color) . ';font-size:12px;line-height:1.4;">' . esc_html($text) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

if (!function_exists('eh_report_preview_score_card')) {
    function eh_report_preview_score_card(string $label, string $value, string $fillColor, string $ringColor): void
    {
        $lines = array_values(array_filter(array_map('trim', explode('<br>', $label)), static fn ($line) => $line !== ''));
        $valueText = esc_xml($value);
        $labelLines = array_map(static fn ($line) => esc_xml($line), $lines);
        $svgLines = '';
        $lineY = 112;
        foreach ($labelLines as $index => $line) {
            $y = $lineY + ($index * 12);
            $svgLines .= '<text x="61" y="' . $y . '" text-anchor="middle" font-size="9.5" font-family="Arial, Arial, Helvetica, sans-serif" font-weight="700" fill="#ffffff">' . $line . '</text>';
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="122" height="170" viewBox="0 0 122 170">'
            . '<defs>'
            . '<linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0%" stop-color="#f6f4ef"/>'
            . '<stop offset="100%" stop-color="#eceae4"/>'
            . '</linearGradient>'
            . '</defs>'
            . '<ellipse cx="61" cy="85" rx="58" ry="82" fill="url(#bg)" stroke="' . esc_attr($ringColor) . '" stroke-width="2"/>'
            . '<ellipse cx="61" cy="85" rx="48" ry="68" fill="' . esc_attr($fillColor) . '"/>'
            . '<text x="61" y="79" text-anchor="middle" font-size="18" font-family="Arial, Arial, Helvetica, sans-serif" font-weight="700" fill="#ffffff">' . $valueText . '</text>'
            . $svgLines
            . '</svg>';
        $svg_data = 'data:image/svg+xml;base64,' . base64_encode($svg);
        ?>
        <td width="25%" valign="top" align="center" style="padding:0 10px;">
            <img src="<?php echo esc_url($svg_data); ?>" alt="" width="122" height="170" style="display:block;border:0;outline:none;text-decoration:none;" />
        </td>
        <?php
    }
}

if (!function_exists('eh_report_preview_precon_collapse_manual_line_breaks')) {
    /**
     * TinyMCE often inserts &lt;br&gt;, which forces short line boxes. Browsers then skip inter-word
     * justification on those lines. Collapse breaks to spaces so the paragraph reflows as one measure.
     */
    function eh_report_preview_precon_collapse_manual_line_breaks(string $html): string
    {
        if ($html === '') {
            return '';
        }

        return (string) preg_replace('#<br\s*/?>#iu', ' ', $html);
    }
}


if (!function_exists('eh_assessment_render_report_preview_html')) {
    function eh_assessment_render_report_preview_html(array $report): void
    {
        $template = isset($report['pdf_template_row']) && is_array($report['pdf_template_row']) ? $report['pdf_template_row'] : [];
        if ($template !== []) {
            $name = eh_report_preview_value($report, 'patient.name', 'Unknown');
            $salutation = trim(eh_report_preview_value($report, 'patient.salutation', ''));
            $displayName = trim(($salutation !== '' ? $salutation . ' ' : '') . $name);
            $score = (string) ((int) ($report['scores']['overall'] ?? 0));
            $clinicalImage = eh_report_preview_treatment_image_src((string) ($template['image_clinical_knowledge'] ?? ''), '');
            $journeyImage = eh_report_preview_treatment_image_src((string) ($template['image_treatment_journey'] ?? ''), '');
            $is_pdf_render = defined('EH_ASSESSMENT_REPORT_PDF_RENDER') && EH_ASSESSMENT_REPORT_PDF_RENDER;
            $resultSubStr = trim((string) ($template['diagnosis_name_detail'] ?? ''));
            if ($resultSubStr === '' || strcasecmp($resultSubStr, 'null') === 0) {
                $resultSubStr = '';
            }
            $preconReportType = (int) ($report['report_type'] ?? 0);
            /** Report 2 (genetic hair loss): subtitle + clinical image stacked vertically (not two columns). */
            $preconClinicalKnowledgeStacked = ($preconReportType === 2);
            $submittedAt = trim((string) ($report['submission']['submitted_at'] ?? ''));
            $reportDateLabel = '';
            if ($submittedAt !== '' && function_exists('eh_assessment_format_indonesian_date')) {
                $reportDateLabel = eh_assessment_format_indonesian_date($submittedAt);
            } elseif ($submittedAt !== '') {
                $reportDateLabel = $submittedAt;
            }
            $preconImgSrcAttr = static function (string $raw) use ($is_pdf_render): string {
                $raw = trim($raw);
                if ($raw === '') {
                    return '';
                }
                if ($is_pdf_render && !preg_match('#^https?://#i', $raw)) {
                    return esc_attr($raw);
                }

                return esc_url($raw);
            };
            $preconLogoFile = 'precon-header-logo.png';
            $preconLogoPath = __DIR__ . '/assets/report/' . $preconLogoFile;
            if (!is_readable($preconLogoPath)) {
                $preconLogoFile = 'logo-black.png';
            }
            $preconLogoSrc = eh_report_preview_asset_url($preconLogoFile);
            $preconLegalBadgeFile = 'precon-legal-badge.png';
            $preconLegalBadgePath = __DIR__ . '/assets/report/' . $preconLegalBadgeFile;
            if (!is_readable($preconLegalBadgePath)) {
                $preconLegalBadgeFile = 'precon-legal-badge.svg';
            }
            $preconLegalBadgeSrc = eh_report_preview_asset_url($preconLegalBadgeFile);
            $renderHtml = static function (string $value): string {
                $safe = wp_kses_post($value);
                if ($safe === '') {
                    return '';
                }
                $safe = eh_report_preview_precon_collapse_manual_line_breaks($safe);

                return wpautop($safe);
            };
            $diagPlain = trim(wp_strip_all_tags((string) ($template['diagnosis_name'] ?? '')));
            if ($diagPlain === '') {
                $diagPlain = '—';
            }
            $scoreNum = $score !== '0' ? $score : '—';
            $reportHeaderTitle = trim((string) ($template['report_header_title'] ?? ''));
            if ($reportHeaderTitle === '') {
                $reportHeaderTitle = 'HAIR HEALTH';
            }
            $docTitle = trim((string) ($template['subtitle'] ?? ''));
            if ($docTitle === '') {
                $docTitle = trim((string) ($template['report_title'] ?? ''));
            }
            if ($docTitle === '') {
                $docTitle = 'Pre-Consultation Report';
            }
            ?>
            <!DOCTYPE html>
            <html lang="en" class="<?php echo $is_pdf_render ? 'eh-report-pdf eh-precon-pdf' : 'eh-precon-html'; ?>">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title><?php echo esc_html($docTitle); ?></title>
                <style>
                    @page { size: A4 portrait; margin: 0; }
                    html, body {
                        margin: 0;
                        padding: 0;
                        color: #1f1f1f;
                        font-family: Arial, Helvetica, sans-serif;
                        font-size: 13px;
                        line-height: 1.45;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    /* Preview: gradient. Dompdf often skips radial-gradient on body → flat cream for PDF only. */
                    html.eh-precon-html,
                    html.eh-precon-html body {
                        background: #E3E2D3;
                    }
                    html.eh-precon-pdf,
                    html.eh-precon-pdf body {
                        min-height: 0 !important;
                        height: auto !important;
                        background-color: #f4f1ea;
                        background-image: none;
                    }
                    /*
                     * Dompdf-only type scale (reference proportions vs browser preview).
                     * @page background-color is not applied by Dompdf; body fill must be solid.
                     */
                    html.eh-precon-pdf {
                        font-size: 11px;
                    }
                    html.eh-precon-pdf .eh-precon-run-header {
                        font-size: 8px;
                        color: #4a4a4a;
                    }
                    html.eh-precon-pdf .eh-precon-footer {
                        font-size: 10px;
                    }
                    html.eh-precon-pdf .eh-precon-title-series {
                        font-size: 24px;
                        font-weight: 400;
                        letter-spacing: 0.11em;
                        color: #1a1a1a;
                        margin: 0;
                    }
                    html.eh-precon-pdf .eh-precon-title-hero {
                        font-size: 30px;
                        line-height: 1.05;
                        font-weight: 800;
                        color: #b8894d;
                        letter-spacing: 0.05em;
                    }
                    html.eh-precon-pdf .eh-precon-prepared-line {
                        font-size: 11px;
                        line-height: 1.35;
                        color: #2a2a2a;
                        text-align: center;
                    }
                    html.eh-precon-pdf .eh-precon-salutation {
                        font-size: 14px;
                        text-align: justify;
                    }
                    html.eh-precon-pdf .eh-precon-body-copy,
                    html.eh-precon-pdf .eh-precon-body-copy li {
                        font-size: 11px;
                        line-height: 1.45;
                        color: #303030;
                        text-align: justify;
                        text-justify: inter-word;
                        hyphens: auto;
                        -webkit-hyphens: auto;
                        overflow-wrap: break-word;
                        word-break: normal;
                    }
                    html.eh-precon-pdf .eh-precon-body-copy p {
                        font-size: 12px;
                        line-height: 1.45;
                        color: #303030;
                        text-align: justify;
                        text-justify: inter-word;
                        text-align-last: left;
                        hyphens: auto;
                        -webkit-hyphens: auto;
                        overflow-wrap: break-word;
                        word-break: normal;
                    }
                    html.eh-precon-pdf .eh-precon-body-copy--clinical-knowledge,
                    html.eh-precon-pdf .eh-precon-body-copy--clinical-knowledge p,
                    html.eh-precon-pdf .eh-precon-body-copy--clinical-knowledge li {
                        font-size: 10px;
                        text-align: center;
                        text-align-last: center;
                        text-justify: auto;
                        hyphens: manual;
                    }
                    html.eh-precon-pdf .eh-precon-body-copy--recommendation-intro {
                        text-align: center;
                    }
                    html.eh-precon-pdf .eh-precon-body-copy--recommendation-intro p {
                        text-align: center;
                    }
                    html.eh-precon-pdf .eh-precon-body-copy--recommendation-footer,
                    html.eh-precon-pdf .eh-precon-body-copy--recommendation-footer p {
                        font-size: 9.5px;
                        text-align: center;
                        font-style: italic;
                    }
                    html.eh-precon-pdf .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail,
                    html.eh-precon-pdf .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail p,
                    html.eh-precon-pdf .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail li {
                        text-align: center;
                    }
                    html.eh-precon-pdf .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail ul {
                        padding-left: 0;
                        list-style-position: inside;
                    }
                    html.eh-precon-pdf .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail li {
                        font-weight: 700;
                    }
                    html.eh-precon-pdf .eh-precon-section-kicker {
                        font-size: 18px;
                        text-align: center;
                    }
                    html.eh-precon-pdf .eh-precon-score-panel {
                        background: #D7AD74 !important;
                        background-image: none !important;
                    }
                    html.eh-precon-pdf .eh-precon-score-panel-label {
                        font-size: 15px;
                    }
                    html.eh-precon-pdf .eh-precon-score-panel-num {
                        font-size: 52px;
                    }
                    html.eh-precon-pdf .eh-precon-result-title {
                        font-size: 24px;
                        line-height: 1;
                        margin: 0;
                        color: #1a1a1a;
                    }
                    html.eh-precon-pdf .eh-precon-result-sub {
                        font-size: 20px;
                        color: #1a1a1a;
                    }
                    html.eh-precon-pdf .eh-precon-heading {
                        font-size: 18px;
                        text-align: center;
                        margin: 0 0 5px;
                    }
                    html.eh-precon-pdf .eh-precon-heading--journey {
                        text-align: left;
                    }
                    html.eh-precon-pdf .eh-precon-journey-square .eh-precon-img-frame--journey {
                        width: 100%;
                        max-width: 380px;
                        height: auto;
                        aspect-ratio: auto;
                        box-sizing: border-box;
                        overflow: visible;
                    }
                    html.eh-precon-pdf .eh-precon-journey-square .eh-precon-img-frame--journey img {
                        width: 100%;
                        height: auto;
                        object-fit: contain;
                    }
                    html.eh-precon-pdf .eh-precon-subheading {
                        font-size: 11px;
                        font-weight: 400;
                    }
                    html.eh-precon-pdf .eh-precon-legal-note,
                    html.eh-precon-pdf .eh-precon-legal-note__copy p {
                        font-size: 9.5px;
                        text-align: justify;
                    }
                    /* Repeats on every printed page (Dompdf + browser print). */
                    .eh-precon-run-header {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        z-index: 20;
                        box-sizing: border-box;
                        padding: 8px 12px 6px;
                        text-align: right;
                        font-size: 12px;
                        font-style: italic;
                        color: #5c5a57;
                        background: transparent;
                    }
                    .eh-precon-footer {
                        position: fixed;
                        bottom: 0;
                        left: 0;
                        right: 0;
                        z-index: 20;
                        box-sizing: border-box;
                        padding: 8px 12px;
                        text-align: center;
                        font-size: 12px;
                        color: #3a3836;
                        background: #e4e2de;
                        border-top: 1px solid #d0cec9;
                    }
                    .eh-precon-footer-item,
                    .eh-precon-footer-sep {
                        display: inline-block;
                        vertical-align: middle;
                    }
                    .eh-precon-footer-item {
                        padding: 0 0.2em;
                    }
                    .eh-precon-footer-sep {
                        padding: 0 0.55em;
                        color: #6e6b68;
                        font-weight: 400;
                    }
                    .eh-precon-shell {
                        max-width: 700px;
                        margin: 0 auto;
                        padding: 44px 12px 56px;
                        box-sizing: border-box;
                    }
                    /* Logo in document flow → first PDF page only (not fixed). */
                    .eh-precon-first-hero {
                        text-align: center;
                        margin: 0 0 12px;
                    }
                    .eh-precon-logo {
                        display: block;
                        margin: 0 auto;
                        max-width: 265px;
                        width: 35%;
                        height: auto;
                        border: 0;
                    }
                    h1, h2, h3 { margin: 0; }
                    /* Header stack (matches PDF: small series title, large gold report type). */
                    .eh-precon-title-series {
                        text-align: center;
                        font-size: 11px;
                        font-weight: 300;
                        color: #4a4a4a;
                        letter-spacing: 0.18em;
                        margin: 0;
                        text-transform: uppercase;
                    }
                    .eh-precon-title-hero {
                        text-align: center;
                        margin: 0;
                        font-size: 21px;
                        font-weight: 700;
                        color: #b08b52;
                        letter-spacing: 0.08em;
                        line-height: 1.2;
                        text-transform: uppercase;
                    }
                    .eh-precon-prepared-line {
                        text-align: center;
                        font-size: 11px;
                        color: #1a1a1a;
                        margin: 0 0 16px;
                        line-height: 1.45;
                    }
                    .eh-precon-rule {
                        border: 0;
                        height: 1px;
                        background: #c8c4bc;
                        margin: 0 0 16px;
                    }
                    .eh-precon-salutation {
                        font-size: 13px;
                        font-weight: 700;
                        color: #1a1a1a;
                        margin: 0 0 10px;
                        text-align: justify;
                    }
                    .eh-precon-open-block {
                        margin: 0 0 18px;
                        padding-left: 1.25rem;
                        padding-right: 1.25rem;
                    }
                    .eh-precon-open-block .eh-precon-body-copy {
                        margin-bottom: 0;
                    }
                    .eh-precon-open-block .eh-precon-body-copy p:last-child {
                        margin-bottom: 0;
                    }
                    .eh-precon-section-kicker {
                        text-align: center;
                        font-size: 18px;
                        font-weight: 700;
                        color: #b08b52;
                        letter-spacing: 0.1em;
                        margin: 0 0 12px;
                        text-transform: uppercase;
                    }
                    /* Score + diagnosis: table (Dompdf flexbox is unreliable). */
                    .eh-precon-score-shell {
                        width: calc(100% - 2.5rem);
                        max-width: 100%;
                        border-collapse: separate;
                        border-spacing: 0;
                        box-sizing: border-box;
                        border-radius: 28px;
                        overflow: hidden;
                        margin: 0 1.25rem 1rem;
                        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
                        border: 0;
                        background: #cca367;
                    }
                    .eh-precon-score-shell td {
                        vertical-align: middle;
                    }
                    .eh-precon-score-panel {
                        width: 22%;
                        box-sizing: border-box;
                        background: #c9a063;
                        background: linear-gradient(180deg, #d2aa72 0%, #c49858 100%);
                        color: #ffffff;
                        text-align: center;
                        padding: 20px 10px;
                    }
                    .eh-precon-score-panel-label {
                        font-size: 15px;
                        font-weight: 700;
                        letter-spacing: 0.1em;
                        margin: 0 0 8px;
                    }
                    .eh-precon-score-panel-num {
                        font-size: 52px;
                        font-weight: 700;
                        line-height: 1;
                        letter-spacing: -0.02em;
                    }
                    .eh-precon-result-panel {
                        width: 78%;
                        box-sizing: border-box;
                        background: #e8e8e8;
                        padding: 0;
                        border-radius: 24px;
                        text-align: center;
                    }
                    .eh-precon-result-wrap {
                        display: inline-block;
                        width: calc(100% - 40px);
                        max-width: calc(100% - 40px);
                        margin: 10px 20px;
                        vertical-align: middle;
                        border-radius: 35px;
                        overflow: hidden;
                        background: #ffffff;
                        border: 1px solid #e0e0e0;
                        box-sizing: border-box;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
                    }
                    .eh-precon-result-pill {
                        background: #ffffff;
                        border-radius: 0;
                        border: 0;
                        box-shadow: none;
                        padding: 16px 26px;
                        text-align: center;
                    }
                    .eh-precon-result-title {
                        font-size: 24px;
                        font-weight: 700;
                        color: #2f2b2a;
                        line-height: 1;
                        margin: 0;
                    }
                    .eh-precon-result-sub {
                        font-size: 20px;
                        font-weight: 400;
                        color: #4a4744;
                        margin: 0;
                    }
                    .eh-precon-meta { font-size: 12px; color: #444; line-height: 1.55; margin: 0 0 14px; text-align: justify; }
                    .eh-precon-meta strong { color: #222; }
                    .eh-precon-body-copy {
                        font-size: 11px;
                        color: #222;
                        line-height: 1.5;
                        margin: 0 0 8px;
                        text-align: justify;
                        text-justify: inter-word;
                        hyphens: auto;
                        -webkit-hyphens: auto;
                        overflow-wrap: break-word;
                        word-break: normal;
                    }
                    .eh-precon-body-copy li {
                        font-size: 11px;
                        text-align: justify;
                        text-justify: inter-word;
                        hyphens: auto;
                        -webkit-hyphens: auto;
                        overflow-wrap: break-word;
                        word-break: normal;
                    }
                    .eh-precon-body-copy p {
                        font-size: 12px;
                        margin: 0 0 10px;
                        text-align: justify;
                        text-justify: inter-word;
                        text-align-last: left;
                        hyphens: auto;
                        -webkit-hyphens: auto;
                        overflow-wrap: break-word;
                        word-break: normal;
                    }
                    .eh-precon-body-copy--clinical-knowledge {
                        text-align: center;
                        font-size: 10px;
                        text-align-last: center;
                        text-justify: auto;
                        hyphens: manual;
                    }
                    .eh-precon-body-copy--clinical-knowledge p,
                    .eh-precon-body-copy--clinical-knowledge li {
                        font-size: 10px;
                        text-align: center;
                        text-align-last: center;
                        text-justify: auto;
                        hyphens: manual;
                    }
                    .eh-precon-body-copy--recommendation-intro {
                        text-align: center;
                    }
                    .eh-precon-body-copy--recommendation-intro p {
                        text-align: center;
                    }
                    .eh-precon-recommendation-card {
                        background: #ffffff;
                        border: 1px solid #e8e5de;
                        border-radius: 28px;
                        padding: 16px 22px;
                        margin: 12px 0 14px;
                        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
                    }
                    .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail {
                        margin: 0;
                        text-align: center;
                    }
                    .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail ul {
                        margin: 0;
                        padding-left: 0;
                        list-style-position: inside;
                        text-align: center;
                    }
                    .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail p {
                        text-align: center;
                    }
                    .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail li {
                        font-weight: 700;
                        text-align: center;
                        color: #1a1a1a;
                    }
                    .eh-precon-body-copy--recommendation-footer,
                    .eh-precon-body-copy--recommendation-footer p {
                        text-align: center;
                        font-style: italic;
                        font-size: 9.5px;
                        color: #555555;
                        margin-top: 0;
                    }
                    .eh-precon-body-copy--recommendation-footer p {
                        margin: 0 0 8px;
                    }
                    .eh-precon-body-copy--recommendation-footer p:last-child {
                        margin-bottom: 0;
                    }
                    .eh-precon-block {
                        margin: 0;
                        border-top: 2px solid #c8c4bc;
                        padding-top: 14px;
                        padding-left: 1.25rem;
                        padding-right: 1.25rem;
                    }
                    .eh-precon-block--no-rule {
                        border-top: 0;
                        padding-top: 0;
                        padding-bottom: 1rem;
                    }
                    .eh-precon-block--journey {
                        padding-bottom: 1rem;
                    }
                    .eh-precon-block--no-rule .eh-precon-body-copy {
                        margin-bottom: 0;
                    }
                    .eh-precon-block--no-rule .eh-precon-body-copy p:last-child {
                        margin-bottom: 0;
                    }
                    .eh-precon-block--no-top-border {
                        border-top: 0;
                    }
                    .eh-precon-heading { font-size: 18px; font-weight: 700; color: #b08b52; letter-spacing: 0.1em; margin: 0 0 5px; text-transform: uppercase; text-align: center; }
                    .eh-precon-subheading { font-size: 13px; font-weight: 400; color: #1a1a1a; margin: 0 0 10px; line-height: 1.35; white-space: pre-line; text-align: justify; }
                    .eh-precon-clinical-layout {
                        width: 100%;
                        border-collapse: separate;
                        border-spacing: 0;
                        margin: 0 0 14px;
                    }
                    .eh-precon-clinical-layout td { vertical-align: middle; }
                    .eh-precon-clinical-left { width: 32%; padding: 0 14px 0 0; }
                    .eh-precon-clinical-left .eh-precon-subheading {
                        font-size: 22px;
                        font-weight: 400;
                        line-height: 1.18;
                        letter-spacing: 0.02em;
                        margin: 0;
                        text-align: justify;
                    }
                    .eh-precon-clinical-right { width: 68%; }
                    /* Report type 2: subtitle above image (full width), not beside image. */
                    .eh-precon-clinical-stacked {
                        width: 100%;
                        margin: 0 0 14px;
                        box-sizing: border-box;
                    }
                    .eh-precon-clinical-stacked .eh-precon-subheading {
                        margin: 0 0 14px;
                        text-align: center;
                        white-space: pre-line;
                    }
                    .eh-precon-clinical-stacked .eh-precon-clinical-card {
                        margin: 0 auto;
                        max-width: 100%;
                    }
                    .eh-precon-clinical-stacked .eh-precon-img-frame {
                        overflow: visible;
                    }
                    /* Treatment journey: text left (~1/3), diagram right (~2/3), table for Dompdf. */
                    .eh-precon-journey-layout {
                        width: 100%;
                        border-collapse: separate;
                        border-spacing: 0;
                        margin: 0;
                    }
                    .eh-precon-journey-layout td {
                        box-sizing: border-box;
                    }
                    .eh-precon-journey-left {
                        width: 34%;
                        padding: 0 18px 0 0;
                        vertical-align: middle;
                    }
                    .eh-precon-journey-right {
                        width: 66%;
                        padding: 0 0 0 5rem;
                        vertical-align: middle;
                    }
                    .eh-precon-heading--journey {
                        text-align: left;
                        margin: 0 0 5px;
                    }
                    .eh-precon-body-copy--journey {
                        text-align: justify;
                        margin: 0;
                    }
                    .eh-precon-body-copy--journey p {
                        text-align: justify;
                    }
                    .eh-precon-journey-square {
                        width: 100%;
                        max-width: 100%;
                        margin: 0;
                    }
                    .eh-precon-journey-square .eh-precon-img-frame--journey {
                        width: 100%;
                        height: auto;
                        min-height: 0;
                        margin: 0;
                        border-radius: 8px;
                        background: #f2efea;
                        overflow: visible;
                    }
                    .eh-precon-journey-square .eh-precon-img-frame--journey img {
                        width: 100%;
                        height: auto;
                        max-width: 100%;
                        object-fit: contain;
                        display: block;
                    }
                    .eh-precon-clinical-card {
                        border-radius: 24px;
                        background: #ffffff;
                        border: 1px solid #ebe5da;
                        padding: 10px;
                        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
                    }
                    .eh-precon-clinical-card .eh-precon-img-frame {
                        margin: 0;
                        height: 250px;
                        border-radius: 18px;
                        background: #f6f3ed;
                    }
                    .eh-precon-img-frame {
                        width: 100%;
                        max-width: 100%;
                        height: 220px;
                        margin: 10px auto 14px;
                        overflow: hidden;
                        border-radius: 6px;
                        background: #eceae4;
                    }
                    .eh-precon-img-frame img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                        object-position: center;
                        display: block;
                    }
                    .eh-precon-page2 { page-break-before: always; break-before: page; padding-top: 8px; }
                    .eh-precon-legal-note {
                        font-size: 9.5px;
                        line-height: 1.45;
                        color: #555;
                        margin: 18px 0 0;
                        padding-top: 2.5rem;
                        padding-left: 1.25rem;
                        padding-right: 1.25rem;
                        border-top: 1px solid #dcd8d0;
                        text-align: justify;
                    }
                    .eh-precon-legal-note-layout {
                        width: 100%;
                        border-collapse: collapse;
                        border-spacing: 0;
                        margin: 0;
                    }
                    .eh-precon-legal-note__copy {
                        vertical-align: middle;
                        padding: 0 14px 0 0;
                    }
                    .eh-precon-legal-note__copy p {
                        margin: 0 0 8px;
                        font-size: 9.5px;
                        text-align: justify;
                    }
                    .eh-precon-legal-note__copy p:last-child {
                        margin-bottom: 0;
                    }
                    .eh-precon-legal-note__mark {
                        vertical-align: middle;
                        width: 60px;
                        padding: 0;
                        text-align: right;
                    }
                    .eh-precon-legal-note__img {
                        display: block;
                        width: 56px;
                        height: 56px;
                        margin: 0 0 0 auto;
                        border: 0;
                        object-fit: contain;
                    }
                    /*
                     * Admin browser preview only: type scale aligned to print/PDF reference
                     * (body ~14px, section titles ~23px, side titles ~19px, footer note ~11–12px).
                     */
                    html.eh-precon-html .eh-precon-body-copy,
                    html.eh-precon-html .eh-precon-body-copy li {
                        font-size: 11px;
                        line-height: 1.55;
                        color: #333333;
                        text-align: justify;
                        text-justify: inter-word;
                        hyphens: auto;
                        -webkit-hyphens: auto;
                        overflow-wrap: break-word;
                        word-break: normal;
                    }
                    html.eh-precon-html .eh-precon-body-copy p {
                        font-size: 12px;
                        line-height: 1.55;
                        color: #333333;
                        text-align: justify;
                        text-justify: inter-word;
                        text-align-last: left;
                        hyphens: auto;
                        -webkit-hyphens: auto;
                        overflow-wrap: break-word;
                        word-break: normal;
                    }
                    html.eh-precon-html .eh-precon-body-copy--clinical-knowledge,
                    html.eh-precon-html .eh-precon-body-copy--clinical-knowledge p,
                    html.eh-precon-html .eh-precon-body-copy--clinical-knowledge li {
                        font-size: 10px;
                        text-align: center;
                        text-align-last: center;
                        text-justify: auto;
                        hyphens: manual;
                    }
                    html.eh-precon-html .eh-precon-body-copy--recommendation-intro {
                        text-align: center;
                    }
                    html.eh-precon-html .eh-precon-body-copy--recommendation-intro p {
                        text-align: center;
                    }
                    html.eh-precon-html .eh-precon-body-copy--recommendation-footer,
                    html.eh-precon-html .eh-precon-body-copy--recommendation-footer p {
                        font-size: 9.5px;
                        text-align: center;
                        font-style: italic;
                    }
                    html.eh-precon-html .eh-precon-recommendation-card {
                        border-radius: 32px;
                        padding: 18px 24px;
                    }
                    html.eh-precon-html .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail,
                    html.eh-precon-html .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail p,
                    html.eh-precon-html .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail li {
                        text-align: center;
                    }
                    html.eh-precon-html .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail ul {
                        padding-left: 0;
                        list-style-position: inside;
                    }
                    html.eh-precon-html .eh-precon-recommendation-card .eh-precon-body-copy--recommendation-detail li {
                        font-weight: 700;
                    }
                    html.eh-precon-html .eh-precon-body-copy strong {
                        font-weight: 700;
                        color: #222222;
                    }
                    html.eh-precon-html .eh-precon-section-kicker {
                        font-size: 18px;
                        font-weight: 700;
                        color: #9c7c5d;
                        letter-spacing: 0.1em;
                        margin: 0 0 20px;
                        text-align: center;
                        line-height: 1.25;
                    }
                    html.eh-precon-html .eh-precon-heading {
                        font-size: 18px;
                        font-weight: 700;
                        color: #9c7c5d;
                        letter-spacing: 0.1em;
                        margin: 0 0 5px;
                        text-align: center;
                        line-height: 1.25;
                    }
                    html.eh-precon-html .eh-precon-heading--journey {
                        text-align: left;
                        margin: 0 0 5px;
                    }
                    html.eh-precon-html .eh-precon-subheading {
                        font-size: 19px;
                        font-weight: 400;
                        color: #1a1a1a;
                        margin: 0 0 16px;
                        line-height: 1.3;
                    }
                    html.eh-precon-html .eh-precon-clinical-left .eh-precon-subheading {
                        font-size: 52px;
                        font-weight: 400;
                        line-height: 1.05;
                        letter-spacing: 0.01em;
                    }
                    html.eh-precon-html .eh-precon-clinical-stacked .eh-precon-subheading {
                        font-size: 52px;
                        font-weight: 400;
                        line-height: 1.05;
                        letter-spacing: 0.01em;
                        text-align: center;
                    }
                    html.eh-precon-html .eh-precon-clinical-stacked .eh-precon-clinical-card .eh-precon-img-frame {
                        height: auto;
                        min-height: 0;
                    }
                    html.eh-precon-html .eh-precon-clinical-stacked .eh-precon-clinical-card .eh-precon-img-frame img {
                        width: 100%;
                        height: auto;
                        max-height: 420px;
                        object-fit: contain;
                        display: block;
                    }
                    html.eh-precon-html .eh-precon-clinical-card {
                        border-radius: 35px;
                        border: 1px solid #e8e5de;
                        padding: 14px;
                        background: #ffffff;
                    }
                    html.eh-precon-html .eh-precon-clinical-card .eh-precon-img-frame {
                        height: 290px;
                        border-radius: 20px;
                    }
                    html.eh-precon-html .eh-precon-legal-note,
                    html.eh-precon-html .eh-precon-legal-note__copy p {
                        font-size: 9.5px;
                        line-height: 1.5;
                        color: #444444;
                        text-align: justify;
                    }
                    html.eh-precon-html .eh-precon-img-frame {
                        border-radius: 18px;
                    }
                    html.eh-precon-html .eh-precon-journey-square {
                        max-width: 100%;
                    }
                    html.eh-precon-html .eh-precon-journey-square .eh-precon-img-frame--journey {
                        border-radius: 10px;
                        height: auto;
                        overflow: visible;
                    }
                    html.eh-precon-html .eh-precon-journey-square .eh-precon-img-frame--journey img {
                        width: 100%;
                        height: auto;
                        object-fit: contain;
                    }
                    html.eh-precon-html .eh-precon-block {
                        margin: 0;
                        border-top: 2px solid #bcb8af;
                        padding-top: 16px;
                        padding-left: 1.25rem;
                        padding-right: 1.25rem;
                    }
                    html.eh-precon-html .eh-precon-block--no-rule,
                    html.eh-precon-pdf .eh-precon-block--no-rule {
                        border-top: 0;
                        padding-top: 0;
                        padding-bottom: 1rem;
                    }
                    html.eh-precon-html .eh-precon-block--no-top-border,
                    html.eh-precon-pdf .eh-precon-block--no-top-border {
                        border-top: 0;
                    }
                    html.eh-precon-html .eh-precon-rule {
                        margin: 0 0 20px;
                    }
                    html.eh-precon-html .eh-precon-title-series {
                        font-size: 14px;
                        font-weight: 300;
                    }
                    html.eh-precon-html .eh-precon-title-hero {
                        font-size: 28px;
                        color: #9c7c5d;
                    }
                    html.eh-precon-html .eh-precon-prepared-line {
                        font-size: 11px;
                        text-align: center;
                    }
                    html.eh-precon-html .eh-precon-salutation {
                        font-size: 14px;
                        text-align: justify;
                    }
                    html.eh-precon-html .eh-precon-result-title {
                        font-size: 24px;
                        line-height: 1;
                        margin: 0;
                    }
                    html.eh-precon-html .eh-precon-result-sub {
                        font-size: 20px;
                    }
                    html.eh-precon-html .eh-precon-score-panel-label {
                        font-size: 15px;
                    }
                    html.eh-precon-html .eh-precon-score-panel-num {
                        font-size: 36px;
                    }
                    html.eh-precon-html .eh-precon-run-header {
                        font-size: 11px;
                    }
                    html.eh-precon-html .eh-precon-footer {
                        font-size: 11.5px;
                    }
                    html.eh-precon-pdf .eh-precon-clinical-left .eh-precon-subheading {
                        font-size: 24px;
                        font-weight: 400;
                        line-height: 1.15;
                    }
                    html.eh-precon-pdf .eh-precon-clinical-stacked .eh-precon-subheading {
                        font-size: 24px;
                        font-weight: 400;
                        line-height: 1.15;
                        text-align: center;
                    }
                    html.eh-precon-pdf .eh-precon-clinical-stacked .eh-precon-clinical-card .eh-precon-img-frame {
                        height: auto;
                        min-height: 0;
                    }
                    html.eh-precon-pdf .eh-precon-clinical-stacked .eh-precon-clinical-card .eh-precon-img-frame img {
                        width: 100%;
                        height: auto;
                        max-height: 420px;
                        object-fit: contain;
                        display: block;
                    }
                    html.eh-precon-pdf .eh-precon-clinical-card {
                        border-radius: 22px;
                        border: 1px solid #e5dfd3;
                        padding: 10px;
                        background: #ffffff;
                    }
                    html.eh-precon-pdf .eh-precon-clinical-card .eh-precon-img-frame {
                        height: 240px;
                        border-radius: 16px;
                    }
                </style>
            </head>
            <body>
                <div class="eh-precon-run-header"><?php echo esc_html('Confidential Clinical Assessment'); ?></div>
                <div class="eh-precon-footer">
                    <span class="eh-precon-footer-item"><?php echo esc_html('Jakarta'); ?></span><span class="eh-precon-footer-sep"><?php echo esc_html('|'); ?></span><span class="eh-precon-footer-item"><?php echo esc_html('eurohairlab.com'); ?></span><span class="eh-precon-footer-sep"><?php echo esc_html('|'); ?></span><span class="eh-precon-footer-item"><?php echo esc_html('WhatsApp: +62 813 2106 788'); ?></span>
                </div>

                <div class="eh-precon-shell">
                <div class="eh-precon-first-hero">
                    <img class="eh-precon-logo" src="<?php echo $is_pdf_render ? esc_attr($preconLogoSrc) : esc_url($preconLogoSrc); ?>" alt="<?php echo esc_attr('EUROHAIRLAB by Dr. Scalp'); ?>" />
                </div>
                <div class="eh-precon-title-series"><?php echo esc_html($reportHeaderTitle); ?></div>
                <div class="eh-precon-title-hero"><?php echo esc_html((string) ($template['subtitle'] ?? '')); ?></div>
                <p class="eh-precon-prepared-line">
                    <?php echo esc_html('Prepared for :'); ?> <?php echo esc_html($displayName); ?>
                    <?php if ($reportDateLabel !== '') : ?>
                        <?php echo esc_html(' Report '); ?><?php echo esc_html($reportDateLabel); ?>
                    <?php endif; ?>
                </p>
                <hr class="eh-precon-rule" />
                <div class="eh-precon-open-block">
                    <p class="eh-precon-salutation"><?php echo esc_html('Yth.'); ?> <?php echo esc_html($displayName); ?>,</p>
                    <div class="eh-precon-body-copy"><?php echo $renderHtml((string) ($template['greeting_description'] ?? '')); ?></div>
                </div>
                <hr class="eh-precon-rule" />
                <table class="eh-precon-score-shell" cellpadding="0" cellspacing="0" border="0" role="presentation">
                    <tr>
                        <td class="eh-precon-score-panel" width="22%">
                            <div class="eh-precon-score-panel-label"><?php echo esc_html('SCORE'); ?></div>
                            <div class="eh-precon-score-panel-num"><?php echo esc_html($scoreNum); ?></div>
                        </td>
                        <td class="eh-precon-result-panel" width="78%">
                            <div class="eh-precon-result-wrap">
                                <div class="eh-precon-result-pill">
                                    <div class="eh-precon-result-title"><?php echo esc_html($diagPlain); ?></div>
                                    <?php if ($resultSubStr !== '') : ?>
                                        <p class="eh-precon-result-sub">(<?php echo esc_html($resultSubStr); ?>)</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="eh-precon-block eh-precon-block--no-rule">
                    <div class="eh-precon-section-kicker"><?php echo esc_html((string) ($template['title_condition_explanation'] ?? '')); ?></div>
                    <div class="eh-precon-body-copy"><?php echo $renderHtml((string) ($template['description_condition_explanation'] ?? '')); ?></div>
                </div>

                <div class="eh-precon-block">
                    <div class="eh-precon-heading"><?php echo esc_html((string) ($template['title_clinical_knowledge'] ?? '')); ?></div>
                    <?php if ($preconClinicalKnowledgeStacked) : ?>
                        <div class="eh-precon-clinical-stacked">
                            <div class="eh-precon-subheading"><?php echo esc_html((string) ($template['subtitle_clinical_knowledge'] ?? '')); ?></div>
                            <?php if ($clinicalImage !== '') : ?>
                                <div class="eh-precon-clinical-card">
                                    <div class="eh-precon-img-frame">
                                        <img src="<?php echo $preconImgSrcAttr($clinicalImage); ?>" alt="" />
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <table class="eh-precon-clinical-layout" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                            <tr>
                                <td class="eh-precon-clinical-left" width="32%">
                                    <div class="eh-precon-subheading"><?php echo esc_html((string) ($template['subtitle_clinical_knowledge'] ?? '')); ?></div>
                                </td>
                                <td class="eh-precon-clinical-right" width="68%">
                                    <?php if ($clinicalImage !== '') : ?>
                                        <div class="eh-precon-clinical-card">
                                            <div class="eh-precon-img-frame">
                                                <img src="<?php echo $preconImgSrcAttr($clinicalImage); ?>" alt="" />
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    <?php endif; ?>
                    <div class="eh-precon-body-copy eh-precon-body-copy--clinical-knowledge"><?php echo $renderHtml((string) ($template['description_clinical_knowledge'] ?? '')); ?></div>
                </div>

                <div class="eh-precon-page2">
                    <div class="eh-precon-block eh-precon-block--no-top-border">
                        <div class="eh-precon-heading"><?php echo esc_html((string) ($template['title_evaluation_urgency'] ?? '')); ?></div>
                        <div class="eh-precon-body-copy"><?php echo $renderHtml((string) ($template['description_evaluation_urgency'] ?? '')); ?></div>
                    </div>
                    <div class="eh-precon-block eh-precon-block--journey">
                        <?php if ($journeyImage !== '') : ?>
                            <table class="eh-precon-journey-layout" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                                <tr>
                                    <td class="eh-precon-journey-left" width="34%" valign="middle">
                                        <div class="eh-precon-heading eh-precon-heading--journey"><?php echo esc_html((string) ($template['title_treatment_journey'] ?? '')); ?></div>
                                        <div class="eh-precon-body-copy eh-precon-body-copy--journey"><?php echo $renderHtml((string) ($template['description_treatment_journey'] ?? '')); ?></div>
                                    </td>
                                    <td class="eh-precon-journey-right" width="66%" valign="middle">
                                        <div class="eh-precon-journey-square">
                                            <div class="eh-precon-img-frame eh-precon-img-frame--journey">
                                                <img src="<?php echo $preconImgSrcAttr($journeyImage); ?>" alt="" />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        <?php else : ?>
                            <div class="eh-precon-heading"><?php echo esc_html((string) ($template['title_treatment_journey'] ?? '')); ?></div>
                            <div class="eh-precon-body-copy"><?php echo $renderHtml((string) ($template['description_treatment_journey'] ?? '')); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="eh-precon-block">
                        <div class="eh-precon-heading"><?php echo esc_html((string) ($template['title_recommendation_approach'] ?? '')); ?></div>
                        <?php
                        $recIntro = trim((string) ($template['description_recommendation_approach'] ?? ''));
                        $recDetail = trim((string) ($template['detail_recommendation_approach'] ?? ''));
                        $recFooter = trim((string) ($template['bottom_description_recommendation_approach'] ?? ''));
                        ?>
                        <?php if ($recIntro !== '') : ?>
                            <div class="eh-precon-body-copy eh-precon-body-copy--recommendation-intro"><?php echo $renderHtml($recIntro); ?></div>
                        <?php endif; ?>
                        <?php if ($recDetail !== '') : ?>
                            <div class="eh-precon-recommendation-card">
                                <div class="eh-precon-body-copy eh-precon-body-copy--recommendation-detail"><?php echo $renderHtml($recDetail); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($recFooter !== '') : ?>
                            <div class="eh-precon-body-copy eh-precon-body-copy--recommendation-footer"><?php echo $renderHtml($recFooter); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="eh-precon-block">
                        <div class="eh-precon-heading"><?php echo esc_html((string) ($template['title_next_steps'] ?? '')); ?></div>
                        <div class="eh-precon-body-copy"><?php echo $renderHtml((string) ($template['description_next_steps'] ?? '')); ?></div>
                    </div>
                    <div class="eh-precon-block">
                        <div class="eh-precon-heading"><?php echo esc_html((string) ($template['title_medical_notes'] ?? '')); ?></div>
                        <div class="eh-precon-body-copy"><?php echo $renderHtml((string) ($template['body_medical_notes'] ?? '')); ?></div>
                    </div>
                    <div class="eh-precon-legal-note">
                        <table class="eh-precon-legal-note-layout" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                            <tr>
                                <td class="eh-precon-legal-note__copy" valign="middle">
                                    <p><?php echo esc_html('Disiapkan oleh EUROHAIRLAB Clinical Assessment System'); ?></p>
                                    <p><?php echo esc_html('Pre-Consultation Report ini disiapkan di bawah pengawasan Medical Director EUROHAIRLAB by Dr. Scalp. Ini bukan diagnosis medis. Diagnosis akurat hanya dapat ditentukan melalui pemeriksaan langsung oleh dokter.'); ?></p>
                                </td>
                                <td class="eh-precon-legal-note__mark" valign="middle" align="right" width="60">
                                    <img
                                        class="eh-precon-legal-note__img"
                                        src="<?php echo $is_pdf_render ? esc_attr($preconLegalBadgeSrc) : esc_url($preconLegalBadgeSrc); ?>"
                                        width="56"
                                        height="56"
                                        alt=""
                                    />
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                </div><?php /* .eh-precon-shell */ ?>
            </body>
            </html>
            <?php
            return;
        }

        $name = eh_report_preview_value($report, 'patient.name', 'Unknown');
        $salutation = trim(eh_report_preview_value($report, 'patient.salutation', ''));
        $displayName = $salutation !== '' ? trim($salutation . ' ' . $name) : $name;
        $gender = ucfirst(eh_report_preview_value($report, 'patient.gender', '-'));
        $age = eh_report_preview_value($report, 'patient.age', '42');
        $contact = eh_report_preview_value($report, 'patient.whatsapp', '-');
        $diagnosisTitle = eh_report_preview_value($report, 'diagnosis.title', '');
        $diagnosisSubtitle = eh_report_preview_value($report, 'diagnosis.subtitle', '');
        $basis = array_values((array) ($report['diagnosis']['basis'] ?? []));
        $scores = (array) ($report['scores'] ?? []);
        $scoreImage = eh_report_preview_asset_url('hair-health-score.png');
        $overviewImage = eh_report_preview_overview_image_src($report, eh_report_preview_asset_url('clinical-overview.jpg'));
        $regenProtocolImage = eh_report_preview_asset_url('regen-activ-protocol.png');
        $scalpBalanceImage = eh_report_preview_asset_url('scalp-balance-therapy.png');
        $logoBlack = eh_report_preview_asset_url('logo-black.png');
        $is_pdf_render = defined('EH_ASSESSMENT_REPORT_PDF_RENDER') && EH_ASSESSMENT_REPORT_PDF_RENDER;
        $pdfTemplate = isset($report['pdf_template']) && is_array($report['pdf_template']) ? $report['pdf_template'] : [];
        $riskUntreatedImage = eh_report_preview_treatment_image_src(
            trim((string) ($pdfTemplate['risk_untreated_image'] ?? '')),
            ''
        );

        $clinicalCards = isset($report['clinical_cards']) && is_array($report['clinical_cards']) ? $report['clinical_cards'] : [];
        if ($clinicalCards === []) {
            $clinicalCards = [
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
        }
        /** Fixed column headers for CLINICAL OVERVIEW (order 1–3). */
        $clinicalOverviewCardTitles = [
            1 => 'Kondisi dipengaruhi oleh:',
            2 => 'Jendela peluang:',
            3 => 'PENJELASAN KONDISI',
        ];
        $risksBlock = isset($report['risks']) && is_array($report['risks']) ? $report['risks'] : [];
        $riskDelayedItems = isset($risksBlock['delayed']) && is_array($risksBlock['delayed']) ? $risksBlock['delayed'] : [];
        $riskUntreatedItems = isset($risksBlock['untreated']) && is_array($risksBlock['untreated']) ? $risksBlock['untreated'] : [];

        $defaultTreatBodyLeft = '';
        $defaultTreatBodyRight = '';
        $treatSlots = isset($report['treatments']) && is_array($report['treatments']) ? array_values($report['treatments']) : [];
        $treatmentCards = [
            [
            'title' => '',
            'body' => $defaultTreatBodyLeft,
            'image' => $regenProtocolImage,
            ],
            [
            'title' => '',
            'body' => $defaultTreatBodyRight,
            'image' => $scalpBalanceImage,
            ],
        ];
        foreach ([0, 1] as $idx) {
            $slot = isset($treatSlots[$idx]) && is_array($treatSlots[$idx]) ? $treatSlots[$idx] : [];
            if ($slot === []) {
                continue;
            }
            $title = trim((string) ($slot['title'] ?? ''));
            $body = trim((string) ($slot['body'] ?? ''));
            if ($title !== '') {
                $treatmentCards[$idx]['title'] = $title;
            }
            if ($body !== '') {
                $treatmentCards[$idx]['body'] = $body;
            }
            $treatmentCards[$idx]['image'] = eh_report_preview_treatment_image_src((string) ($slot['image'] ?? ''), (string) $treatmentCards[$idx]['image']);
        }
        $t2 = isset($treatSlots[2]) && is_array($treatSlots[2]) ? $treatSlots[2] : [];
        if ($t2 !== []) {
            $treatmentCards[] = [
                'title' => trim((string) ($t2['title'] ?? '')),
                'body' => trim((string) ($t2['body'] ?? '')),
                'image' => eh_report_preview_treatment_image_src((string) ($t2['image'] ?? ''), ''),
            ];
        }
        $treatmentCards = array_values(array_filter(
            $treatmentCards,
            static function ($card): bool {
                if (!is_array($card)) {
                    return false;
                }

                $title = trim((string) ($card['title'] ?? ''));
                $body = trim((string) ($card['body'] ?? ''));
                $image = trim((string) ($card['image'] ?? ''));

                return $title !== '' && $body !== '' && $image !== '';
            }
        ));
        $ov = (int) ($scores['overall'] ?? 0);
        $sc = (int) ($scores['scalp'] ?? 0);
        $fc = (int) ($scores['follicle'] ?? 0);
        $th = (int) ($scores['thinning'] ?? 0);
        $overallHairHealthScore = ($ov > 0 ? (string) $ov : '60') . '/100';
        $scalpScore = ($sc > 0 ? (string) $sc : '6') . '/10';
        $follicleScore = ($fc > 0 ? (string) $fc : '6') . '/10';
        $thinningRickScore = ($th > 0 ? (string) $th : '6') . '/10';

        $scoreAssetPath = __DIR__ . '/assets/report/hair-health-score.png';
        $scoreImgMeta = @getimagesize($scoreAssetPath);
        $scoreNatW = (int) (($scoreImgMeta[0] ?? 0) > 0 ? $scoreImgMeta[0] : 1240);
        $scoreNatH = (int) (($scoreImgMeta[1] ?? 0) > 0 ? $scoreImgMeta[1] : 520);
        $scoreDisplayW = 620;
        $scoreDisplayH = (int) max(1, (int) round($scoreDisplayW * $scoreNatH / max(1, $scoreNatW)));
        // Dompdf: px top matches ~20% of rendered image height (same as browser top:20%).
        $scoreOverlayTopPx = (int) max(0, round($scoreDisplayH * 0.20));
        $scoreTopStyle = $is_pdf_render
            ? 'top:' . $scoreOverlayTopPx - 20 . 'px;'
            : 'top:50%;';
        ?>
        <!DOCTYPE html>
        <html lang="en" class="<?php echo $is_pdf_render ? 'eh-report-pdf' : ''; ?>">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Hair Diagnostics Report</title>
            <style>
                @page { size: A4 portrait; margin: 0; }
                /* Spec: Arial Black / Arial / Arial MT. Dompdf bundles Arial as practical substitute (register Arial TTF for exact match). */
                html, body {
                    margin: 0;
                    padding: 0;
                    min-height: 100%;
                    background-color: #edebdd;
                    background-image: none;
                    color: #111111;
                    font-family: "Arial MT", Arial, Helvetica, sans-serif;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                /* Dompdf: avoid leading blank pages (fixed footer before flow + table/thead wrapper). */
                html.eh-report-pdf,
                html.eh-report-pdf body {
                    min-height: 0 !important;
                    height: auto !important;
                }
                .ff-mt { font-family: "Arial MT", Arial, Helvetica, sans-serif; font-weight: normal; font-size: 13px; }
                .ff-arial { font-family: "Arial", Arial, Helvetica, sans-serif; font-weight: normal; }
                .ff-ab { font-family: "Arial", "Arial Black", Arial, Helvetica, sans-serif; font-weight: bold; }
                .pdf-shell { width: 100%; max-width: 746px; margin: 0 auto; box-sizing: border-box; background-color: #edebdd; color: #2F2B2A !important;}
                .pdf-run-top { text-align: right; font-size: 12px; color: #6f6c64; font-style: italic; padding: 6px 18px 4px; }
                .pdf-footer-fixed {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    width: 100%;
                    text-align: center;
                    font-size: 12px;
                    color: #4f4c47;
                    z-index: 5;
                }
                .pdf-footer-fixed span { margin: 0 10px; }
                .page { width: 710px; margin: 0 auto; padding: 4px 18px 28px; background-color: #edebdd; box-sizing: border-box; }
                .brand { text-align: center; line-height: 0; margin: 0 0 4px; }
                .brand-logo { display: block; margin: 30px auto; max-width: 280px; width: 30%; height: auto; border: 0; }
                .title { text-align: center; font-size: 32px; color: #1e1a17; margin-bottom: 15px; margin-top: 30px; }
                .rule { height: 1px; background: #7A7974; margin: 0 0 7px; }
                .st-arial, .st-ab { text-align: center; font-size: 16px; font-weight: bold; color: #ba9352; margin: 15px 0;}
                .profile td { font-size: 14px; line-height: 2; color: #1e1a17; padding: 0 0 2px; }
                .label { white-space: nowrap; }
                .value { }
                .note { text-align: center; font-size: 7px; color: #6d6a63; font-style: italic; margin-top: 4px; }
                .small { font-size: 8px; line-height: 1.25; color: #222; }
                .diag-wrap { margin-top: 4px; }
                .diag-left { text-align: center; width: 100px; background: #d9a85b; color: #fff; vertical-align: middle; padding: 2rem; box-sizing: border-box; border-radius: 20px 0 0 20px; }
                .diag-right { background: #EEEEEF; vertical-align: top; padding: 11px 13px 10px; border-radius: 0 20px 20px 0; }
                .diag-level-label { font-size: 14px; line-height: 1; margin-bottom: 2px; }
                .diag-level { font-size: 36px; line-height: 1; }
                .diag-pill { background: #f7f7f7; border-radius: 999px; text-align: center; font-size: 24px; color: #1e1a17; padding: 1rem; margin: 0 auto 7px; }
                .diag-subtitle { text-align: center; font-size: 13px; color: #1e1a17; margin-bottom: 4px; }
                .basis-title { text-align: center; font-size: 13px; color: #4b4b4b; margin-bottom: 2px; }
                .subsection { margin: 5px 0 30px 0; }
                .subsection-title { font-size: 16px; color: #ba9352; text-align: center; margin-bottom: 15px; margin-top: 40px; }
                .lead { font-size: 13px; line-height: 1.25; color: #222; margin-bottom: 3px; }
                .box { background: #ffffff; border: 1px solid #ece3d6; border-radius: 10px; padding: 7px 9px; }
                .box-gold { background: #d8ac62; border: 1px solid #d8ac62; border-radius: 10px; padding: 7px 9px; }
                /* Clinical overview row: card chrome on td so all cells share one row height (Dompdf has no flexbox). */
                .overview-equal-cards { table-layout: fixed; width: 100%; border-collapse: separate;margin-top: 3px;   border-spacing: 2px;}
                .overview-equal-cards > tbody > tr > td.overview-card { vertical-align: top; border-radius: 10px; padding: 20px; box-sizing: border-box; }
                .overview-equal-cards > tbody > tr > td.overview-card--white { background: #ffffff; border: 1px solid #ece3d6; }
                .overview-equal-cards > tbody > tr > td.overview-card--gold { background: #d8ac62; border: 1px solid #d8ac62; }
                .risk { background: #eab392; border-radius: 16px; padding: 20px; }
                .risk-title { font-size: 12px; color: #1e1a17; margin-bottom: 2px; }
                .treat-card { background: #ffffff; border: 1px solid #ece3d6; border-radius: 10px; padding: 20px; }
                .treat-title { font-size: 12px; color: #1e1a17; margin-bottom: 2px; }
                .treat-copy { font-size: 12px; line-height: 1.2; color: #222; margin: 15px 0; }
                /* Extra gap so the score artwork (pods / connector line) does not overlap the section heading. */
                .eh-score-section-title.st-ab {
                    margin: 30px 0 0;
                }
                /* Table wrapper: Dompdf reliably uses TD as containing block for absolute overlays (plain div.relative often breaks). */
                table.eh-score-graph-wrap {
                    width: 620px;
                    max-width: 100%;
                    margin: 0 auto;
                    border-collapse: collapse;
                    clear: both;
                    margin-top: 55px;
                }
                .eh-score-graph-cell {
                    position: relative;
                    padding: 0;
                    margin: 0;
                    vertical-align: top;
                }
                .eh-score-graph-wrap .score-image {
                    width: 100%;
                    height: auto;
                    display: block;
                    margin: 0;
                    border: 0;
                }
                .eh-score-value {
                    position: absolute;
                    width: 25%;
                    margin: 0;
                    padding: 0;
                    text-align: center;
                    color: #ffffff;
                    font-weight: bold;
                    font-size: 15px;
                    line-height: 1.05;
                    font-family: Arial, Helvetica, sans-serif;
                    z-index: 2;
                }
                .overview-image { width: 100%; max-width: 300px; display: block; margin-left: auto; margin-bottom: 2rem;}
                .treat-image { width: 100%; display: block; }
                .img-rounded { border-radius: 6px; overflow: hidden; }
                .img-fit { width: 100%; height: auto; display: block; }
                .phase-title { font-size: 18px; line-height: 1.05; color: #1e1a17; font-weight: normal; }
                .muted { color: #6d6a63; }
            </style>
        </head>
        <body class="<?php echo $is_pdf_render ? 'eh-report-pdf' : ''; ?>">
            <div class="pdf-shell">
                <div class="pdf-run-top ff-arial">Confidential Clinical Assessment</div>
            <div class="page">

                <div class="brand">
                    <img class="brand-logo" src="<?php echo esc_url($logoBlack); ?>" width="280" alt="EUROHAIRLAB by DR.SCALP" />
                </div>

                <div class="title ff-ab">HAIR DIAGNOSTICS REPORT</div>
                <div class="rule"></div>


                <div class="st-arial ff-arial">PATIENT PROFILE</div>
                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="profile ff-mt" style="border-collapse:collapse; margin-top: 4px; margin-bottom: 10px; margin-left: 20px;">
                    <tr>
                        <td width="50%">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td class="label" width="95">Name</td>
                                    <td width="12">:</td>
                                    <td class="value"><?php echo esc_html($displayName); ?></td>
                                </tr>
                                <tr>
                                    <td class="label">Gender</td>
                                    <td>:</td>
                                    <td class="value"><?php echo esc_html($gender); ?></td>
                                </tr>
                            </table>
                        </td>
                        <td width="50%">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; margin-left: 80px;">
                                
                                <tr>
                                    <td class="label">Contact Number</td>
                                    <td>:</td>
                                    <td class="value"><?php echo esc_html($contact); ?></td>
                                </tr>
                                <tr>
                                    <td class="label" width="95"></td>
                                    <td width="12"></td>
                                    <td class="value"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <div class="rule"></div>
                <div style="margin: 10px 25px;">
                    <div class="st-ab ff-ab eh-score-section-title">HAIR HEALTH SCORE (CONDITION INDICATORS)</div>

                    <table class="eh-score-graph-wrap" cellpadding="0" cellspacing="0" border="0" align="center" style="text-align:center;">
                        <tr>
                            <td class="eh-score-graph-cell" style="width:<?php echo (int) $scoreDisplayW; ?>px;max-width:100%;">
                                <img
                                    class="score-image"
                                    src="<?php echo esc_url($scoreImage); ?>"
                                    width="<?php echo (int) $scoreDisplayW; ?>"
                                    height="<?php echo (int) $scoreDisplayH; ?>"
                                    alt=""
                                />
                                <div class="eh-score-value" style="left:-1%;<?php echo esc_attr($scoreTopStyle); ?>"><?php echo esc_html($overallHairHealthScore); ?></div>
                                <div class="eh-score-value" style="left:23.5%;<?php echo esc_attr($scoreTopStyle); ?>"><?php echo esc_html($scalpScore); ?></div>
                                <div class="eh-score-value" style="left:49%;<?php echo esc_attr($scoreTopStyle); ?>"><?php echo esc_html($follicleScore); ?></div>
                                <div class="eh-score-value" style="left:73%;<?php echo esc_attr($scoreTopStyle); ?>"><?php echo esc_html($thinningRickScore); ?></div>
                            </td>
                        </tr>
                    </table>

                    <div class="subsection">
                        <div class="ff-mt" style="font-size: 13.2px; color: #1e1a17; margin-bottom: 3px;">INTERPRETATION:</div>
                        <div class="lead ff-mt">The Hair Health Score is a composite clinical index derived from key scalp and follicular parameters:</div>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; margin-top: 1px;">
                            <tr>
                                <td width="50%" valign="top">
                                    <?php eh_report_preview_render_bullet_list([
                                        'Follicular Density',
                                        'Hair Shaft Thickness',
                                        'Sebum Regulation',
                                    ]); ?>
                                </td>
                                <td width="50%" valign="top">
                                    <?php eh_report_preview_render_bullet_list([
                                        'Scalp Micro-Inflammation',
                                        'Growth Cycle Ratio (Anagen vs Telogen)',
                                    ]); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="rule" style="margin-top: 10px;"></div>
                <div class="st-ab ff-ab">CLINICAL DIAGNOSIS</div>

                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="diag-wrap" style="border-collapse:collapse;">
                    <tr>
                        <td class="diag-left">
                            <div class="diag-level-label ff-ab">LEVEL</div>
                            <div class="diag-level ff-ab"><?php echo esc_html((string) ($report['diagnosis']['level'] ?? 65)); ?></div>
                        </td>
                        <td class="diag-right">
                            <div class="diag-pill ff-ab"><?php echo esc_html($diagnosisTitle); ?></div>
                            <div class="diag-subtitle ff-mt"><?php echo esc_html($diagnosisSubtitle); ?></div>
                        </td>
                    </tr>
                </table>

                <div class="subsection" style="page-break-inside: avoid; margin-top: 4px;">
                    <div class="subsection-title ff-ab">CLINICAL OVERVIEW</div>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                        <tr>
                            <td width="28%" valign="middle" style="padding-right:10px;">
                                <div class="phase-title ff-arial">PHASE OF</div>
                                <div class="phase-title ff-arial">HAIR GROWTH</div>
                            </td>
                            <td width="72%" valign="top">
                                <img class="overview-image" src="<?php echo esc_url($overviewImage); ?>" alt="Hair growth phases" />
                            </td>
                        </tr>
                    </table>

                    <table class="overview-equal-cards" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <?php
                            $cardIndex = 0;
                            foreach ($clinicalCards as $card) {
                                if (!is_array($card)) {
                                    continue;
                                }
                                ++$cardIndex;
                                if ($cardIndex > 3) {
                                    break;
                                }
                                $cardTitle = $clinicalOverviewCardTitles[$cardIndex] ?? '';
                                $cardItems = isset($card['items']) && is_array($card['items']) ? $card['items'] : [];
                                $accent = ($card['accent'] ?? 'white') === 'gold' ? 'gold' : 'white';
                                $tdClass = $accent === 'gold' ? 'overview-card overview-card--gold' : 'overview-card overview-card--white';
                                $titleStyle = $accent === 'gold' ? 'font-size: 12px; line-height: 1.15; color:#1e1a17;' : 'font-size: 12px; line-height: 1.15;';
                                $bulletColor = '#1e1a17';
                                ?>
                            <td width="33.33%" valign="top" class="<?php echo esc_attr($tdClass); ?>">
                                <div class="ff-ab" style="<?php echo esc_attr($titleStyle); ?>"><?php echo esc_html($cardTitle); ?></div>
                                <?php eh_report_preview_render_bullet_list($cardItems, $bulletColor); ?>
                            </td>
                                <?php
                            }
                            while ($cardIndex < 3) {
                                ++$cardIndex;
                                $padTitle = $clinicalOverviewCardTitles[$cardIndex] ?? '';
                                $padTitleStyle = 'font-size: 12px; line-height: 1.15;';
                                ?>
                            <td width="33.33%" valign="top" class="overview-card overview-card--white">
                                <div class="ff-ab" style="<?php echo esc_attr($padTitleStyle); ?>"><?php echo esc_html($padTitle); ?></div>
                            </td>
                                <?php
                            }
                            ?>
                        </tr>
                    </table>

                    <div style="margin-top: 4px;" class="risk">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <tr>
                                <td width="49%" valign="top" style="padding-right: 8px;">
                                    <div class="risk-title ff-ab">RISK IF DELAYED TREATMENTS</div>
                                    <?php eh_report_preview_render_bullet_list($riskDelayedItems); ?>
                                </td>
                                <td width="2%" valign="top" style="padding:0 8px;">
                                    <div style="width:1px; height:78px; background:#f8efe6; margin: 4px auto 0;"></div>
                                </td>
                                <td width="49%" valign="top" style="padding-left: 8px;">
                                    <div class="risk-title ff-ab">RISK IF UNTREATED</div>
                                    <?php if ($riskUntreatedImage !== '') : ?>
                                        <div style="margin: 0 0 8px 0;">
                                            <img src="<?php echo esc_url($riskUntreatedImage); ?>" alt="Risk untreated" style="display:block;width:100%;max-width:100%;border-radius:12px;" />
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>

                <div class="rule" style="margin-top: 7px;"></div>
                <div class="st-ab ff-ab">TREATMENT RECOMMENDATION</div>

                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                    <?php if ($treatmentCards !== []) : ?>
                        <?php foreach (array_chunk($treatmentCards, 2) as $rowCards) : ?>
                            <tr>
                                <?php foreach ([0, 1] as $col) : ?>
                                    <?php $card = $rowCards[$col] ?? null; ?>
                                    <td width="50%" valign="top" style="<?php echo $col === 0 ? 'padding-right: 4px;' : 'padding-left: 4px;'; ?>">
                                        <?php if (is_array($card)) : ?>
                                            <div class="treat-card">
                                                <div class="treat-title ff-ab" style="text-align: center;"><?php echo esc_html((string) ($card['title'] ?? '')); ?></div>
                                                <div class="treat-copy ff-mt"><?php echo esc_html((string) ($card['body'] ?? '')); ?></div>
                                                <div class="img-rounded"><img class="treat-image" src="<?php echo esc_url((string) ($card['image'] ?? '')); ?>" alt="" /></div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>

                <div class="subsection" style="page-break-inside: avoid;">
                    <div class="subsection-title ff-ab">WHY EUROHAIRLAB by DR. SCALP?</div>
                    <div class="ff-ab" style="text-align:center; font-size: 16px; line-height: 1.15; font-style: italic; color:#1e1a17; margin-bottom: 4px;">A Clinical-First Approach to Hair Restoration</div>
                    <div class="ff-mt" style="font-size: 12px; line-height: 1.25; text-align:center; color:#1e1a17; padding: 0 8px;">
                        EUROHAIRLAB by DR. SCALP is built on SCALPFIRST&trade; SYSTEM, a diagnostics-driven medical framework, ensuring that every treatment is based on measurable scalp and follicular conditions-not assumptions or standardized packages.
                    </div>
                </div>

            </div>
            </div>
            <div class="pdf-footer-fixed ff-mt" style="background-color: #fdfdfd; padding: 5px;">
                <span>Jakarta</span> |
                <span>eurohairlab.com</span> |
                <span>WhatsApp: +628 123 4567 8900</span>
            </div>
        </body>
        </html>
        <?php
    }
}

if (!function_exists('eh_assessment_resolve_report_preview_submission')) {
    /**
     * Admin report preview: use ?submission_id= when valid; otherwise newest submission row.
     *
     * @return array<string, mixed>
     */
    function eh_assessment_resolve_report_preview_submission(): array
    {
        $requested = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
        if ($requested > 0) {
            $submission = eh_assessment_get_submission_detail_row($requested);
            if ($submission !== null) {
                return $submission;
            }
        }

        global $wpdb;
        $table = eh_assessment_table_name();
        $fallback_id = (int) $wpdb->get_var("SELECT id FROM `{$table}` ORDER BY id DESC LIMIT 1");
        if ($fallback_id <= 0) {
            wp_die('No assessment submissions yet. Create one first, or open this preview with ?submission_id=…');
        }

        $submission = eh_assessment_get_submission_detail_row($fallback_id);
        if ($submission === null) {
            wp_die('Submission not found.');
        }

        return $submission;
    }
}

if (!isset($report) || !is_array($report)) {
    $submission = eh_assessment_resolve_report_preview_submission();
    $report = eh_assessment_build_report_data($submission);
}

eh_assessment_render_report_preview_html($report);
