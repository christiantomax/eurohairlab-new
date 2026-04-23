<?php
/**
 * EUROHAIRLAB assessment: answer normalization, decision tree, scoring, lead fields.
 *
 * @noinspection PhpConditionAlreadyCheckedInspection
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string, list<string>>
 */
function eh_assessment_answer_keyword_letter_map(): array
{
    return [
        'q1_focus_area' => [
            'A' => ['hairline', 'mundur', 'receding', 'garis rambut'],
            'B' => ['crown', 'vertex', 'mahkota'],
            'C' => ['diffuse', 'merata', 'thinning evenly', 'evenly'],
            'D' => ['massive', 'heavy', 'falling', 'rontok', 'banyak', 'lebih banyak'],
            'E' => ['scalp', 'dandruff', 'itch', 'oily', 'kulit kepala', 'ketombe', 'gatal', 'berminyak'],
            'F' => ['maintain', 'healthy', 'prevent', 'menjaga', 'kesehatan rambut', 'ingin menjaga'],
        ],
        'q2_main_impact' => [
            'A' => ['confidence', 'percaya diri'],
            'B' => ['worry', 'thinning', 'khawatir', 'menipis'],
            'C' => ['older', 'age', 'tua'],
            'D' => ['stress'],
            'E' => ['proactive', 'prevent', 'menjaga kondisi', 'ingin menjaga'],
        ],
        'q3_duration' => [
            'A' => ['<3', 'under 3', 'kurang dari 3', 'dibawah 3', 'less than 3', 'less than 3 months'],
            'B' => ['3–6', '3-6', '3 to 6'],
            'C' => ['6–12', '6-12', '6 to 12'],
            'D' => ['>1', 'more than 1', 'lebih dari 1', 'satu tahun', '1 tahun'],
            'E' => ['not sure', 'unsure', 'tidak yakin'],
        ],
        'q4_family_history' => [
            'A' => ['yes', 'ya', 'ada'],
            'B' => ['no', 'tidak'],
            'C' => ['not sure', 'tidak yakin'],
        ],
        'q5_previous_attempts' => [
            'A' => ['never', 'belum', 'not tried', 'nothing yet'],
            'B' => ['product', 'serum', 'produk'],
            'C' => ['clinic', 'treatment', 'klinik'],
            'D' => ['doctor', 'dokter', 'obat'],
            'E' => ['combination', 'kombinasi', 'beberapa'],
        ],
        'q6_trigger_factors' => [
            'A' => ['stress', 'stres'],
            'B' => ['sleep', 'tidur', 'kurang tidur'],
            'C' => ['diet', 'weight', 'berat badan'],
            'D' => ['hormon', 'hormonal'],
            'E' => ['none', 'no factor', 'tidak ada', 'tidak yakin'],
        ],
        'q7_biggest_worry' => [
            'A' => ['spread', 'wider', 'luas', 'menipis semakin'],
            'B' => ['transplant', 'transp'],
            'C' => ['older', 'tua'],
            'D' => ['confidence', 'percaya diri'],
            'E' => ['not thought', 'belum', 'pikir'],
        ],
        'q8_previous_consultation' => [
            'A' => ['never', 'belum pernah'],
            'B' => ['aesthetic', 'estetik'],
            'C' => ['dermatologist', 'dokter kulit', 'skin doctor'],
            'D' => ['transplant', 'transp', 'konsultasi transplant'],
            'E' => ['various', 'berbagai'],
        ],
        'q9_expected_result' => [
            'A' => ['younger', 'muda'],
            'B' => ['confidence', 'percaya diri'],
            'C' => ['stress', 'stres'],
            'D' => ['thicker', 'tebal', 'healthier', 'sehat'],
        ],
    ];
}

/**
 * @param array<string, array{question: string, answer: string}> $answers
 * @return array<string, string> question keys → single letter A–F
 */
function eh_assessment_extract_answer_letters(array $answers): array
{
    $keys = eh_assessment_question_key_map();
    $maps = eh_assessment_answer_keyword_letter_map();
    $out = [];

    foreach ($keys as $num => $key) {
        $block = is_array($answers[$key] ?? null) ? $answers[$key] : [];
        $raw = trim((string) ($block['answer'] ?? ''));
        $letter = eh_assessment_parse_single_answer_letter($key, $raw, $maps[$key] ?? []);
        $out[$key] = $letter ?? 'Z';
    }

    return $out;
}

/**
 * @param array<string, list<string>> $keywordMap letter → keywords
 */
function eh_assessment_parse_single_answer_letter(string $key, string $raw, array $keywordMap): ?string
{
    $t = trim($raw);
    if ($t === '') {
        return null;
    }

    $u = strtoupper($t);
    $max = $key === 'q1_focus_area' ? 'F' : ($key === 'q9_expected_result' ? 'D' : 'E');

    if (preg_match('/^([A-F])([\.\)\:\s\-]|$)/i', $u, $m)) {
        $c = strtoupper($m[1]);
        if ($key !== 'q1_focus_area' && $c === 'F') {
            $c = 'Z';
        }
        if ($c > $max && $c <= 'F') {
            return null;
        }
        if ($c >= 'A' && $c <= $max) {
            return $c;
        }
    }

    $lower = strtolower($t);
    foreach ($keywordMap as $letter => $keywords) {
        foreach ($keywords as $kw) {
            if ($kw !== '' && str_contains($lower, strtolower($kw))) {
                return $letter;
            }
        }
    }

    return null;
}

/**
 * @param array<string, string> $letters question_key → letter
 */
function eh_assessment_q3_normalized(string $q3): string
{
    $q3 = strtoupper($q3);

    return $q3 === 'E' ? 'C' : $q3;
}

/**
 * @param array<string, string> $L
 */
function eh_assessment_decision_base_report(array $L): int
{
    $q1 = strtoupper($L['q1_focus_area'] ?? 'Z');
    $q3 = strtoupper($L['q3_duration'] ?? 'Z');
    $q4 = strtoupper($L['q4_family_history'] ?? 'Z');
    $q6 = strtoupper($L['q6_trigger_factors'] ?? 'Z');

    if ($q1 === 'E') {
        return 5;
    }

    if (in_array($q1, ['A', 'B', 'C'], true) && $q4 === 'A' && in_array($q3, ['A', 'B', 'C', 'E'], true)) {
        return 2;
    }

    if (in_array($q1, ['A', 'B', 'C'], true) && $q4 === 'A' && $q3 === 'D') {
        return 3;
    }

    if ($q1 === 'D' && in_array($q6, ['A', 'B', 'C', 'D'], true)) {
        return 4;
    }

    if ($q1 === 'D' && $q6 === 'E') {
        return 1;
    }

    if (in_array($q1, ['A', 'B', 'C'], true) && in_array($q3, ['A', 'B'], true) && in_array($q4, ['B', 'C'], true)) {
        return 1;
    }

    if (in_array($q1, ['A', 'B', 'C'], true) && in_array($q3, ['C', 'D', 'E'], true) && in_array($q4, ['B', 'C'], true)) {
        return 1;
    }

    return 8;
}

/**
 * @param array<string, string> $L
 */
function eh_assessment_decision_final_report(int $base, int $score, array $L): int
{
    $q3 = strtoupper($L['q3_duration'] ?? 'Z');
    $q5 = strtoupper($L['q5_previous_attempts'] ?? 'Z');
    $q7 = strtoupper($L['q7_biggest_worry'] ?? 'Z');
    $q8 = strtoupper($L['q8_previous_consultation'] ?? 'Z');

    if ($base === 5 || $base === 8) {
        return $base;
    }

    if ($q3 === 'D' && $q7 === 'B') {
        return 7;
    }

    if (in_array($base, [1, 2, 3, 4], true)) {
        $q5_hit = in_array($q5, ['C', 'D', 'E'], true);
        $q8_hit = in_array($q8, ['D', 'E'], true);
        $low_score_hit = ($q7 === 'B' && $score < 50);
        if ($q5_hit || $q8_hit || $low_score_hit) {
            return 6;
        }
    }

    return $base;
}

/**
 * @param array<string, string> $L
 */
function eh_assessment_compute_weighted_score(array $L): int
{
    $q1 = strtoupper($L['q1_focus_area'] ?? 'Z');
    if ($q1 === 'F') {
        return 85;
    }

    $score = 100;
    $q3 = strtoupper($L['q3_duration'] ?? 'Z');
    $q3eff = eh_assessment_q3_normalized($q3);
    $q4 = strtoupper($L['q4_family_history'] ?? 'Z');
    $q5 = strtoupper($L['q5_previous_attempts'] ?? 'Z');
    $q6 = strtoupper($L['q6_trigger_factors'] ?? 'Z');
    $q7 = strtoupper($L['q7_biggest_worry'] ?? 'Z');
    $q8 = strtoupper($L['q8_previous_consultation'] ?? 'Z');

    if (in_array($q1, ['A', 'B', 'C'], true)) {
        $score -= 15;
    } elseif ($q1 === 'D') {
        $score -= 20;
    }

    if ($q3eff === 'C' || $q3 === 'E') {
        $score -= 10;
    } elseif ($q3 === 'D') {
        $score -= 25;
    }

    if ($q4 === 'A') {
        $score -= 15;
    }

    if (in_array($q5, ['C', 'D', 'E'], true)) {
        $score -= 10;
    }

    if (in_array($q6, ['A', 'B', 'C', 'D'], true)) {
        $score -= 10;
    }

    if (in_array($q7, ['A', 'B'], true)) {
        $score -= 10;
    }

    if (in_array($q8, ['C', 'D', 'E'], true)) {
        $score -= 5;
    }

    return max(30, min(100, $score));
}

function eh_assessment_band_label_id(int $score): string
{
    if ($score >= 80) {
        return 'Kondisi Optimal';
    }
    if ($score >= 60) {
        return 'Perubahan Awal';
    }
    if ($score >= 40) {
        return 'Hair Loss Aktif';
    }

    return 'Kondisi Lanjut';
}

function eh_assessment_maintenance_path_label(int $score): string
{
    if ($score < 50) {
        return 'Jalur Penyelamatan';
    }
    if ($score <= 80) {
        return 'Jalur Edukasi';
    }

    return 'Jalur Elite';
}

function eh_assessment_condition_title_id(int $reportType): string
{
    $m = [
        1 => 'Penipisan Rambut Awal',
        2 => 'Hair Loss Genetik',
        3 => 'Hair Loss Progresif',
        4 => 'Hair Loss Stres / Gaya Hidup',
        5 => 'Kondisi Kulit Kepala',
        6 => 'Hair Loss Multifaktor',
        7 => 'Hair Loss Lanjut',
        8 => 'Optimasi Kondisi Rambut',
    ];

    return $m[$reportType] ?? 'Tidak diketahui';
}

/**
 * @param array<string, string> $L
 */
function eh_assessment_genetic_clinical_text(string $gender, array $L): string
{
    if (strtolower($gender) === 'female' || $gender === 'ibu') {
        return 'Genetic hair loss in women is generally characterized by diffuse thinning and widening of the hair part, following the Ludwig/Savin pattern.';
    }

    return 'Genetic hair loss in men typically begins at the hairline and crown area, progressing gradually following the Norwood pattern.';
}

/**
 * @param array<string, string> $L
 */
function eh_assessment_urgency_text_id(int $score): string
{
    if ($score < 50) {
        return 'Berdasarkan evaluasi ini, intervensi dalam 1–3 bulan ke depan sangat disarankan untuk menjaga viabilitas folikel.';
    }
    if ($score < 80) {
        return 'Evaluasi klinis langsung disarankan untuk menentukan strategi terbaik sebelum kondisi berkembang lebih jauh.';
    }

    return 'Kondisi Anda saat ini masih dalam rentang yang dapat dijaga dengan perawatan preventif yang tepat.';
}

/**
 * @param array<string, string> $L
 * @return list<string>
 */
function eh_assessment_clinical_warning_labels(array $L): array
{
    $q5 = strtoupper($L['q5_previous_attempts'] ?? 'Z');
    $q6 = strtoupper($L['q6_trigger_factors'] ?? 'Z');
    $q7 = strtoupper($L['q7_biggest_worry'] ?? 'Z');
    $q8 = strtoupper($L['q8_previous_consultation'] ?? 'Z');

    $w = [];
    if (in_array($q5, ['C', 'D', 'E'], true)) {
        $w[] = 'RIWAYAT PERAWATAN';
    }
    if ($q7 === 'B' || $q8 === 'D') {
        $w[] = 'PERTIMBANGAN TRANSPLANT';
    }
    if ($q6 === 'D') {
        $w[] = 'FAKTOR HORMONAL';
    }

    return $w;
}

/**
 * @param array<string, string> $L
 */
function eh_assessment_determine_patient_type(int $finalReport, int $score, array $L): int
{
    $q2 = strtoupper($L['q2_main_impact'] ?? 'Z');
    $q3 = strtoupper($L['q3_duration'] ?? 'Z');
    $q4 = strtoupper($L['q4_family_history'] ?? 'Z');
    $q5 = strtoupper($L['q5_previous_attempts'] ?? 'Z');
    $q6 = strtoupper($L['q6_trigger_factors'] ?? 'Z');
    $q7 = strtoupper($L['q7_biggest_worry'] ?? 'Z');
    $q3n = eh_assessment_q3_normalized($q3);

    $t6 = ($finalReport === 7) || ($q3 === 'D' && $q7 === 'B');
    if ($t6) {
        return 6;
    }

    if ($score < 50) {
        return 1;
    }

    if (in_array($q5, ['C', 'D', 'E'], true)) {
        return 2;
    }

    if ($q4 === 'A' && in_array($q3n, ['C', 'D'], true)) {
        return 3;
    }

    if (in_array($q6, ['A', 'B', 'C', 'D'], true)) {
        return 4;
    }

    if ($score > 80 && $q2 === 'E') {
        return 5;
    }

    return 1;
}

function eh_assessment_communication_strategy_for_type(int $tipe): string
{
    $m = [
        1 => 'Urgensi + Otoritas',
        2 => 'Bangun Kembali Kepercayaan',
        3 => 'Edukasi + Manajemen Jangka Panjang',
        4 => 'Keyakinan + Harapan',
        5 => 'Optimasi + Jangka Panjang',
        6 => 'Otoritas + Kejujuran',
    ];

    return $m[$tipe] ?? 'Urgensi + Otoritas';
}

/**
 * Visual score cards (0–10 scale stored as integer).
 *
 * @param array<string, string> $L
 * @return array{scalp: int, follicle: int, thinning_risk: int}
 */
function eh_assessment_visual_score_cards(int $finalReport, int $score, array $L): array
{
    $q1 = strtoupper($L['q1_focus_area'] ?? 'Z');
    $q3 = strtoupper($L['q3_duration'] ?? 'Z');
    $q4 = strtoupper($L['q4_family_history'] ?? 'Z');
    $q6 = strtoupper($L['q6_trigger_factors'] ?? 'Z');

    if ($finalReport === 8) {
        $scalp = 8;
    } elseif ($q1 === 'E' || in_array($q6, ['A', 'B'], true)) {
        $scalp = 4;
    } elseif ($q3 === 'D' || $q4 === 'A') {
        $scalp = 5;
    } else {
        $scalp = 7;
    }

    if ($score > 80) {
        $fol = 8;
        $thin = 2;
    } elseif ($score >= 50) {
        $fol = 6;
        $thin = 4;
    } else {
        $fol = 4;
        $thin = 7;
    }

    return ['scalp' => $scalp, 'follicle' => $fol, 'thinning_risk' => $thin];
}

function eh_assessment_salutation_id(string $genderNorm): string
{
    $g = strtolower(trim($genderNorm));

    return ($g === 'female' || $g === 'f' || $g === 'wanita' || $g === 'ibu') ? 'Ibu' : 'Bapak';
}

/**
 * @param array<string, array{question: string, answer: string}> $answers
 * @return array<string, mixed>
 */
function eh_assessment_compute_submission_outcomes(array $answers, string $respondentGender): array
{
    $letters = eh_assessment_extract_answer_letters($answers);
    $base = eh_assessment_decision_base_report($letters);
    $scorePreOverride = eh_assessment_compute_weighted_score($letters);
    $final = eh_assessment_decision_final_report($base, $scorePreOverride, $letters);
    $score = eh_assessment_compute_weighted_score($letters);
    $band = eh_assessment_band_label_id($score);
    $path = eh_assessment_maintenance_path_label($score);
    $warnings = eh_assessment_clinical_warning_labels($letters);
    $warningsStr = $warnings === [] ? 'Tidak ada peringatan' : implode(', ', $warnings);
    $tipe = eh_assessment_determine_patient_type($final, $score, $letters);
    $strategy = eh_assessment_communication_strategy_for_type($tipe);
    $title = eh_assessment_condition_title_id($final);
    $urgency = eh_assessment_urgency_text_id($score);
    $genetic = eh_assessment_genetic_clinical_text($respondentGender, $letters);
    $visual = eh_assessment_visual_score_cards($final, $score, $letters);

    return [
        'answer_letters' => $letters,
        'base_report_type' => $base,
        'computed_report_type' => $final,
        'computed_score' => $score,
        'computed_band' => $band,
        'computed_maintenance_path' => $path,
        'computed_patient_type' => $tipe,
        'computed_communication_strategy' => $strategy,
        'computed_condition_title' => $title,
        'computed_clinical_warnings' => $warningsStr,
        'computed_urgency_text' => $urgency,
        'computed_genetic_clinical_text' => $genetic,
        'score_visual_scalp' => $visual['scalp'],
        'score_visual_follicle' => $visual['follicle'],
        'score_visual_thinning_risk' => $visual['thinning_risk'],
        'computed_salutation' => eh_assessment_salutation_id($respondentGender),
    ];
}

function eh_assessment_normalize_lead_source(string $raw): string
{
    $s = strtolower(trim($raw));
    $aliases = [
        'instagram' => 'ig',
        'insta' => 'ig',
        'whatsapp' => 'wa',
        'wa_direct' => 'wa_direct_inquiry',
        'wa-direct' => 'wa_direct_inquiry',
        'wa_direct_inquiry' => 'wa_direct_inquiry',
        'direct_inquiry' => 'wa_direct_inquiry',
        'redirect' => 'website',
        'web' => 'website',
        'site' => 'website',
        'tiktok' => 'tiktok',
        'tt' => 'tiktok',
    ];
    if (isset($aliases[$s])) {
        $s = $aliases[$s];
    }

    $allowed = ['wa', 'ig', 'website', 'tiktok', 'direct', 'wa_direct_inquiry'];

    return in_array($s, $allowed, true) ? $s : 'direct';
}

/**
 * @param array<string, mixed> $submissionIn submission slice from payload
 */
function eh_assessment_lead_source_from_submission(array $submissionIn): string
{
    $candidates = [
        (string) ($submissionIn['source'] ?? ''),
        (string) ($submissionIn['utm_source'] ?? ''),
        (string) ($submissionIn['utm_medium'] ?? ''),
    ];
    foreach ($candidates as $c) {
        if (trim($c) !== '') {
            return eh_assessment_normalize_lead_source($c);
        }
    }

    return 'direct';
}

/**
 * Computed DB columns only (after q1–q9 in table schema).
 *
 * @param array<string, mixed> $comp Output of {@see eh_assessment_compute_submission_outcomes()}.
 * @return array<string, int|string>
 */
function eh_assessment_computed_metrics_for_db(array $comp): array
{
    return [
        'computed_report_type' => (int) ($comp['computed_report_type'] ?? 8),
        'computed_score' => (int) ($comp['computed_score'] ?? 85),
        'computed_band' => (string) ($comp['computed_band'] ?? ''),
        'computed_maintenance_path' => (string) ($comp['computed_maintenance_path'] ?? ''),
        'computed_patient_type' => (int) ($comp['computed_patient_type'] ?? 1),
        'computed_communication_strategy' => (string) ($comp['computed_communication_strategy'] ?? ''),
        'computed_condition_title' => (string) ($comp['computed_condition_title'] ?? ''),
        'computed_clinical_warnings' => (string) ($comp['computed_clinical_warnings'] ?? ''),
        'computed_urgency_text' => (string) ($comp['computed_urgency_text'] ?? ''),
        'computed_genetic_clinical_text' => (string) ($comp['computed_genetic_clinical_text'] ?? ''),
        'score_visual_scalp' => (int) ($comp['score_visual_scalp'] ?? 7),
        'score_visual_follicle' => (int) ($comp['score_visual_follicle'] ?? 6),
        'score_visual_thinning_risk' => (int) ($comp['score_visual_thinning_risk'] ?? 4),
    ];
}

/**
 * Mutates $sanitized: sets computed + submission.report_type.
 *
 * @param array<string, mixed> $sanitized
 * @return array{lead_source: string, metrics: array<string, int|string>}
 */
function eh_assessment_attach_computed_to_sanitized(array &$sanitized): array
{
    $submission = is_array($sanitized['submission'] ?? null) ? $sanitized['submission'] : [];
    $lead = eh_assessment_lead_source_from_submission($submission);
    $comp = eh_assessment_compute_submission_outcomes(
        is_array($sanitized['answers'] ?? null) ? $sanitized['answers'] : [],
        (string) ($sanitized['respondent']['gender'] ?? '')
    );
    $sanitized['computed'] = $comp;
    if (!isset($sanitized['submission']) || !is_array($sanitized['submission'])) {
        $sanitized['submission'] = [];
    }
    $sanitized['submission']['report_type'] = (int) $comp['computed_report_type'];

    return [
        'lead_source' => $lead,
        'metrics' => eh_assessment_computed_metrics_for_db($comp),
    ];
}
