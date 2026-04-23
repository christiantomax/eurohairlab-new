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


if (!function_exists('eh_assessment_render_report_preview_html')) {
    function eh_assessment_render_report_preview_html(array $report): void
    {
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

if (!isset($report) || !is_array($report)) {
    $submission_id = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 8;
    $submission = eh_assessment_get_submission_detail_row($submission_id);

    if (!$submission) {
        wp_die('Submission not found.');
    }

    $report = eh_assessment_build_report_data($submission);
}

eh_assessment_render_report_preview_html($report);
