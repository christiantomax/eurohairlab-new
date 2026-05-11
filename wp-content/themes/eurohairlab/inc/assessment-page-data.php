<?php

declare(strict_types=1);

/**
 * Build assessment landing / wizard / form copy for the active public language
 * (respects {@see eurohairlab_public_lang_override} when set).
 *
 * @return array{
 *   landing: array<string, mixed>,
 *   wizard_sidebar_image: string,
 *   complete: array<string, mixed>,
 *   assessment_steps: list<array<string, mixed>>,
 *   form_labels: array<string, string>,
 *   assessment_ui: array<string, string>,
 * }
 */
function eurohairlab_assessment_build_lang_specific_vars(int $page_id, string $theme_uri, string $landing_background_url): array
{
    $mb_get = static function (string $key) use ($page_id) {
        if (!$page_id || !function_exists('eurohairlab_rwmb_page_meta')) {
            return null;
        }

        return eurohairlab_rwmb_page_meta($page_id, $key, []);
    };

    $resolve_link = static function ($value, string $fallback = ''): string {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            return $fallback;
        }

        if (
            str_starts_with($value, 'http://')
            || str_starts_with($value, 'https://')
            || str_starts_with($value, 'mailto:')
            || str_starts_with($value, 'tel:')
            || str_starts_with($value, '#')
        ) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return home_url($value);
        }

        return home_url('/' . ltrim($value, '/'));
    };

    $resolve_image = static function ($value, string $fallback = ''): string {
        $out = $fallback;

        if (is_string($value) && $value !== '') {
            $out = $value;
        } elseif (is_numeric($value)) {
            $attachment_url = wp_get_attachment_image_url((int) $value, 'full');
            if (is_string($attachment_url) && $attachment_url !== '') {
                $out = $attachment_url;
            }
        } elseif (is_array($value)) {
            if (isset($value['ID']) && is_numeric($value['ID'])) {
                $attachment_url = wp_get_attachment_image_url((int) $value['ID'], 'full');
                if (is_string($attachment_url) && $attachment_url !== '') {
                    $out = $attachment_url;
                }
            } elseif (isset($value['full_url']) && is_string($value['full_url']) && $value['full_url'] !== '') {
                $out = $value['full_url'];
            } elseif (isset($value['url']) && is_string($value['url']) && $value['url'] !== '') {
                $out = $value['url'];
            }
        }

        return eurohairlab_rewrite_assessment_page_asset_url($out);
    };

    $localize = static function (string $text): string {
        return function_exists('eurohairlab_assessment_localize_seed_text') ? eurohairlab_assessment_localize_seed_text($text) : $text;
    };

    $landing = [
        'background_image' => $landing_background_url,
        'back_text' => $localize((string) ($mb_get('eh_assessment_landing_back_text') ?: 'Exit')),
        'title' => $localize((string) ($mb_get('eh_assessment_landing_title') ?: 'Online Hair Assessment')),
        'intro_paragraphs' => array_values(array_filter(array_map(
            static function (string $p) use ($localize): string {
                return $localize($p);
            },
            [
                (string) ($mb_get('eh_assessment_landing_intro_paragraph_1') ?: ''),
                (string) ($mb_get('eh_assessment_landing_intro_paragraph_2') ?: ''),
                (string) ($mb_get('eh_assessment_landing_intro_paragraph_3') ?: ''),
                (string) ($mb_get('eh_assessment_landing_intro_paragraph_4') ?: ''),
            ]
        ), static fn(string $item) => trim($item) !== '')),
        'start_button_text' => $localize((string) ($mb_get('eh_assessment_landing_start_button_text') ?: 'Start Your Assessment Now')),
    ];

    $wizard_sidebar_image = $landing['background_image'];

    $complete = [
        'title' => $localize((string) ($mb_get('eh_assessment_complete_title') ?: 'Thank You For Taking The Time To Complete Our Hair Assessment')),
        'paragraph' => $localize((string) ($mb_get('eh_assessment_complete_paragraph') ?: 'Thank you for sharing your concerns with us. To build a treatment plan that is genuinely personalised to your condition, we would like to understand it in more detail. Book your complimentary 15-minute consultation below.')),
        'cta_text' => $localize((string) ($mb_get('eh_assessment_complete_cta_text') ?: 'WhatsApp Consultation')),
        'cta_href' => esc_url($resolve_link((string) $mb_get('eh_assessment_complete_cta_href'), eurohairlab_get_primary_cta_url())),
        'visual_image' => $resolve_image($mb_get('eh_assessment_complete_visual_image'), $theme_uri . '/assets/images/figma/assessment-complete-bg.webp'),
    ];

    $question_defaults = function_exists('eurohairlab_get_assessment_question_defaults')
        ? eurohairlab_get_assessment_question_defaults()
        : [];

    $assessment_question_keys = [
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

    $assessment_steps = [];
    for ($question_number = 1; $question_number <= 10; $question_number++) {
        $step = [
            'key' => $assessment_question_keys[$question_number] ?? 'q10_contact_details',
            'title' => (string) $mb_get("eh_assessment_q{$question_number}_title"),
            'type' => (string) $mb_get("eh_assessment_q{$question_number}_type"),
        ];

        if ($question_number < 10) {
            $step['why'] = (string) $mb_get("eh_assessment_q{$question_number}_why_text");
            $options = [];
            $option_count = (int) ($question_defaults[$question_number]['option_count'] ?? 0);

            for ($option_number = 1; $option_number <= $option_count; $option_number++) {
                $label = (string) $mb_get("eh_assessment_q{$question_number}_option_{$option_number}_label");
                $value = (string) $mb_get("eh_assessment_q{$question_number}_option_{$option_number}_value");

                if ($label === '' && $value === '') {
                    continue;
                }

                $icon = '';
                if ($question_number === 1) {
                    $icon = $resolve_image($mb_get("eh_assessment_q{$question_number}_option_{$option_number}_icon"));
                }

                $options[] = [
                    'value' => $value !== '' ? $value : $label,
                    'label' => $label !== '' ? $label : $value,
                    'icon' => $icon,
                ];
            }

            $step['options'] = $options;
        } else {
            $step['consent'] = (string) ($mb_get('eh_assessment_q10_consent_text') ?: '');
        }

        $assessment_steps[] = $step;
    }

    foreach ($assessment_steps as $idx => $step_row) {
        if (isset($step_row['title']) && is_string($step_row['title'])) {
            $assessment_steps[$idx]['title'] = $localize($step_row['title']);
        }
        if (isset($step_row['why']) && is_string($step_row['why'])) {
            $assessment_steps[$idx]['why'] = $localize($step_row['why']);
        }
        if (!empty($step_row['options']) && is_array($step_row['options'])) {
            foreach ($step_row['options'] as $oi => $opt) {
                if (isset($opt['label']) && is_string($opt['label'])) {
                    $assessment_steps[$idx]['options'][$oi]['label'] = $localize($opt['label']);
                }
            }
        }
        if (isset($step_row['consent']) && is_string($step_row['consent'])) {
            $assessment_steps[$idx]['consent'] = $localize($step_row['consent']);
        }
    }

    $form_labels = [
        'name' => $localize((string) ($mb_get('eh_assessment_q10_name_label') ?: 'Name')),
        'whatsapp' => $localize((string) ($mb_get('eh_assessment_q10_whatsapp_label') ?: 'WhatsApp Number (08xx / +62xx)')),
        'gender' => $localize((string) ($mb_get('eh_assessment_q10_gender_label') ?: 'Gender')),
        'gender_placeholder' => $localize((string) ($mb_get('eh_assessment_q10_gender_placeholder') ?: 'Select gender')),
        'gender_option_1' => $localize((string) ($mb_get('eh_assessment_q10_gender_option_1') ?: 'Pria')),
        'gender_option_2' => $localize((string) ($mb_get('eh_assessment_q10_gender_option_2') ?: 'Wanita')),
        'branch_office' => $localize((string) ($mb_get('eh_assessment_q10_branch_office_label') ?: 'Branch Office')),
        'branch_office_placeholder' => $localize((string) ($mb_get('eh_assessment_q10_branch_office_placeholder') ?: 'Select branch office')),
        'consent' => $localize((string) ($mb_get('eh_assessment_q10_consent_text') ?: 'I consent to the use of my data for hair and scalp health evaluation purposes in accordance with applicable personal data protection regulations.')),
        'submit' => $localize((string) ($mb_get('eh_assessment_q10_submit_button_text') ?: 'Submit')),
    ];

    $assessment_ui = [
        'why_short' => function_exists('eurohairlab_public_ui_text') ? eurohairlab_public_ui_text('Why do we ask?', 'Mengapa kami bertanya?') : 'Why do we ask?',
        'why_title' => function_exists('eurohairlab_public_ui_text') ? eurohairlab_public_ui_text('Why Do We Ask?', 'Mengapa Kami Bertanya?') : 'Why Do We Ask?',
        'modal_close' => function_exists('eurohairlab_public_ui_text') ? eurohairlab_public_ui_text('Close modal', 'Tutup jendela') : 'Close modal',
        'wizard_back' => function_exists('eurohairlab_public_ui_text') ? eurohairlab_public_ui_text('Go back to the previous question', 'Kembali ke pertanyaan sebelumnya') : 'Go back to the previous question',
        'wizard_close' => function_exists('eurohairlab_public_ui_text') ? eurohairlab_public_ui_text('Return to the assessment landing page', 'Kembali ke halaman awal penilaian') : 'Return to the assessment landing page',
        'home_aria' => function_exists('eurohairlab_public_ui_text') ? eurohairlab_public_ui_text('Eurohairlab home', 'Beranda Eurohairlab') : 'Eurohairlab home',
        'complete_visual_alt' => function_exists('eurohairlab_public_ui_text') ? eurohairlab_public_ui_text('Eurohairlab consultation visual', 'Visual konsultasi Eurohairlab') : 'Eurohairlab consultation visual',
        'submit_loading_title' => function_exists('eurohairlab_public_ui_text') ? eurohairlab_public_ui_text('Sending your answers…', 'Mengirim jawaban Anda…') : 'Mengirim jawaban Anda…',
        'submit_loading_hint' => function_exists('eurohairlab_public_ui_text') ? eurohairlab_public_ui_text('Please wait. Do not close or refresh this page.', 'Mohon tunggu sebentar. Jangan tutup atau segarkan halaman ini.') : 'Mohon tunggu sebentar. Jangan tutup atau segarkan halaman ini.',
    ];

    return [
        'landing' => $landing,
        'wizard_sidebar_image' => $wizard_sidebar_image,
        'complete' => $complete,
        'assessment_steps' => $assessment_steps,
        'form_labels' => $form_labels,
        'assessment_ui' => $assessment_ui,
    ];
}

/**
 * Whether the page uses the Online Assessment template (slug or template file).
 */
function eurohairlab_is_assessment_template_page_id(int $post_id): bool
{
    if ($post_id <= 0) {
        return false;
    }

    $tpl = (string) get_page_template_slug($post_id);
    if ($tpl !== '' && (str_ends_with($tpl, 'page-assessment.php') || $tpl === 'page-assessment.php')) {
        return true;
    }

    $post = get_post($post_id);

    return $post instanceof WP_Post
        && $post->post_type === 'page'
        && isset($post->post_name)
        && (string) $post->post_name === 'assessment';
}

/**
 * RWMB: force entire assessment + report PDF flow to Indonesian for this page.
 */
function eurohairlab_assessment_force_id_lang_enabled(int $page_id): bool
{
    if ($page_id <= 0 || !function_exists('rwmb_meta')) {
        return false;
    }

    $v = rwmb_meta('eh_assessment_force_id_lang', [], $page_id);

    return $v === 1 || $v === '1' || $v === true;
}

add_filter('eurohairlab_public_lang_override', static function ($override) {
    if (is_admin()) {
        return $override;
    }
    if (!is_singular('page')) {
        return $override;
    }
    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return $override;
    }
    $pid = (int) $post->ID;
    if (!eurohairlab_is_assessment_template_page_id($pid)) {
        return $override;
    }
    if (!eurohairlab_assessment_force_id_lang_enabled($pid)) {
        return $override;
    }

    return 'id';
}, 10);
