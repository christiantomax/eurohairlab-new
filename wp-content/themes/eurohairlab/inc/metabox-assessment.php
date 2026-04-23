<?php

function eurohairlab_get_assessment_question_defaults(): array
{
    return [
        1 => ['title' => 'Form Question 1', 'type' => 'grid', 'option_count' => 6],
        2 => ['title' => 'Form Question 2', 'type' => 'list', 'option_count' => 5],
        3 => ['title' => 'Form Question 3', 'type' => 'list', 'option_count' => 5],
        4 => ['title' => 'Form Question 4', 'type' => 'list', 'option_count' => 3],
        5 => ['title' => 'Form Question 5', 'type' => 'list', 'option_count' => 5],
        6 => ['title' => 'Form Question 6', 'type' => 'list', 'option_count' => 5],
        7 => ['title' => 'Form Question 7', 'type' => 'list', 'option_count' => 5],
        8 => ['title' => 'Form Question 8', 'type' => 'list', 'option_count' => 5],
        9 => ['title' => 'Form Question 9', 'type' => 'list', 'option_count' => 4],
        10 => ['title' => 'Form Question 10', 'type' => 'form', 'option_count' => 0],
    ];
}

function eurohairlab_get_assessment_content_defaults(): array
{
    return [
        'landing' => [
            'back_text' => 'Exit',
            'back_href' => '/',
            'logo_link_href' => '/',
            'title' => 'Online Hair Assessment',
            'intro_paragraph_1' => 'This 1-minute assessment is the first step toward a personalized treatment plan and consultation.',
            'intro_paragraph_2' => 'At EUROHAIRLAB, we believe in clinically proven scalp care for everyone. Through this Hair Assessment, you can share your concerns and goals with us. Every patient journey begins with a complete scalp diagnostic session before any treatment is recommended.',
            'intro_paragraph_3' => 'Developed based on EUROHAIRLAB&apos;s clinical diagnostic methodology. Your result will be delivered directly to your WhatsApp.',
            'intro_paragraph_4' => 'This short assessment is the first step toward understanding your scalp condition and building a treatment plan that is right for you. Share your concerns and goals with us. Every recommendation at EUROHAIRLAB begins with a proper clinical assessment, never before we fully understand your condition.',
            'start_button_text' => 'Start Your Assessment Now',
        ],
        'questions' => [
            1 => [
                'title' => 'Which hair or scalp change are you noticing the most?',
                'type' => 'grid',
                'why' => 'The pattern of change helps us identify the type of hair concern and the area that needs closer analysis.',
                'options' => [
                    ['label' => 'Hairline Receding', 'value' => 'Hairline Receding'],
                    ['label' => 'Crown Thinning', 'value' => 'Crown Thinning'],
                    ['label' => 'Diffuse Thinning', 'value' => 'Diffuse Thinning'],
                    ['label' => 'Excessive Hair Fall', 'value' => 'Excessive Hair Fall'],
                    ['label' => 'Scalp Concerns (Dandruff, Itchiness, Oily Scalp)', 'value' => 'Scalp Concerns (Dandruff, Itchiness, Oily Scalp)'],
                    ['label' => 'I Want to Maintain Healthy Hair', 'value' => 'I Want to Maintain Healthy Hair'],
                ],
            ],
            2 => [
                'title' => 'What is the biggest impact you are feeling?',
                'type' => 'list',
                'why' => 'The biggest impact helps us understand the urgency of the issue and the outcome that matters most to you.',
                'options' => [
                    ['label' => 'Lower Confidence', 'value' => 'Lower Confidence'],
                    ['label' => 'Worried It Will Get Worse', 'value' => 'Worried It Will Get Worse'],
                    ['label' => 'Looking Older', 'value' => 'Looking Older'],
                    ['label' => 'Stressed About My Hair', 'value' => 'Stressed About My Hair'],
                    ['label' => 'I Want to Maintain My Current Condition', 'value' => 'I Want to Maintain My Current Condition'],
                ],
            ],
            3 => [
                'title' => 'How long have you noticed this change?',
                'type' => 'list',
                'why' => 'The duration helps us distinguish between temporary, progressive, and more chronic conditions.',
                'options' => [
                    ['label' => 'Less Than 3 Months', 'value' => 'Less Than 3 Months'],
                    ['label' => '3 to 6 Months', 'value' => '3 to 6 Months'],
                    ['label' => '6 to 12 Months', 'value' => '6 to 12 Months'],
                    ['label' => 'More Than 1 Year', 'value' => 'More Than 1 Year'],
                    ['label' => 'Not Sure', 'value' => 'Not Sure'],
                ],
            ],
            4 => [
                'title' => 'Is there a family history of a similar condition?',
                'type' => 'list',
                'why' => 'Family history helps us understand whether genetic factors may need to be considered in the evaluation.',
                'options' => [
                    ['label' => 'Yes', 'value' => 'Yes'],
                    ['label' => 'No', 'value' => 'No'],
                    ['label' => 'Not Sure', 'value' => 'Not Sure'],
                ],
            ],
            5 => [
                'title' => 'What have you tried so far?',
                'type' => 'list',
                'why' => 'We need to know what you have already tried so the next recommendation is more accurate and does not repeat what has been ineffective.',
                'options' => [
                    ['label' => 'Nothing Yet', 'value' => 'Nothing Yet'],
                    ['label' => 'Products / Serums', 'value' => 'Products / Serums'],
                    ['label' => 'Clinic Treatments', 'value' => 'Clinic Treatments'],
                    ['label' => 'Doctor-Prescribed Medication', 'value' => 'Doctor-Prescribed Medication'],
                    ['label' => 'A Combination of Methods', 'value' => 'A Combination of Methods'],
                ],
            ],
            6 => [
                'title' => 'Are you currently experiencing any of the following factors?',
                'type' => 'list',
                'why' => 'Potential trigger factors help us read possible internal and lifestyle-related causes affecting your hair and scalp.',
                'options' => [
                    ['label' => 'Prolonged Stress', 'value' => 'Prolonged Stress'],
                    ['label' => 'Lack of Sleep', 'value' => 'Lack of Sleep'],
                    ['label' => 'Dieting / Weight Loss', 'value' => 'Dieting / Weight Loss'],
                    ['label' => 'Hormonal Changes', 'value' => 'Hormonal Changes'],
                    ['label' => 'None / Not Sure', 'value' => 'None / Not Sure'],
                ],
            ],
            7 => [
                'title' => 'If left untreated, what worries you the most?',
                'type' => 'list',
                'why' => 'Your biggest concern helps us understand which outcome you most want to prevent or improve.',
                'options' => [
                    ['label' => 'The Thinning Area Will Spread', 'value' => 'The Thinning Area Will Spread'],
                    ['label' => 'I May Need a Transplant', 'value' => 'I May Need a Transplant'],
                    ['label' => 'I Will Look Older', 'value' => 'I Will Look Older'],
                    ['label' => 'I Will Lose Confidence', 'value' => 'I Will Lose Confidence'],
                    ['label' => 'I Haven\'t Thought About It Yet', 'value' => 'I Haven\'t Thought About It Yet'],
                ],
            ],
            8 => [
                'title' => 'Have you had a consultation before?',
                'type' => 'list',
                'why' => 'Consultation history gives us context on your expectations and the approach you have already received.',
                'options' => [
                    ['label' => 'Never', 'value' => 'Never'],
                    ['label' => 'Aesthetic Clinic', 'value' => 'Aesthetic Clinic'],
                    ['label' => 'Dermatologist', 'value' => 'Dermatologist'],
                    ['label' => 'Hair Transplant Consultation', 'value' => 'Hair Transplant Consultation'],
                    ['label' => 'Multiple Approaches', 'value' => 'Multiple Approaches'],
                ],
            ],
            9 => [
                'title' => 'If your condition improves, what result are you hoping for?',
                'type' => 'list',
                'why' => 'Your target result helps us understand the outcome that would be most meaningful for you.',
                'options' => [
                    ['label' => 'Looking Younger', 'value' => 'Looking Younger'],
                    ['label' => 'Feeling More Confident', 'value' => 'Feeling More Confident'],
                    ['label' => 'Less Stress About My Hair', 'value' => 'Less Stress About My Hair'],
                    ['label' => 'Thicker and Healthier Hair', 'value' => 'Thicker and Healthier Hair'],
                ],
            ],
            10 => [
                'title' => 'Your Details',
                'type' => 'form',
            ],
        ],
        'form' => [
            'name_label' => 'Name',
            'whatsapp_label' => 'WhatsApp Number (08xx / +62xx)',
            'gender_label' => 'Gender',
            'gender_placeholder' => 'Select gender',
            'gender_option_1' => 'Pria',
            'gender_option_2' => 'Wanita',
            'birthdate_label' => 'Birth date',
            'branch_office_label' => 'Branch Office',
            'branch_office_placeholder' => 'Select branch office',
            'consent_text' => 'I consent to the use of my data for hair and scalp health evaluation purposes in accordance with applicable personal data protection regulations.',
            'submit_button_text' => 'Submit',
        ],
        'complete' => [
            'title' => 'Thank You For Taking The Time To Complete Our Hair Assessment',
            'paragraph' => 'Thank you for sharing your concerns with us. To build a treatment plan that is genuinely personalized to your condition, we would like to understand it in more detail. Book your complimentary 15-minute consultation below.',
            'cta_text' => 'WhatsApp Consultation',
            'cta_href' => '/contact/#contact-form',
        ],
    ];
}

add_filter('rwmb_meta_boxes', 'eurohairlab_assessment_register_meta_boxes');

function eurohairlab_assessment_register_meta_boxes($meta_boxes)
{
    $meta_boxes[] = [
        'title'      => esc_html__('Assessment Landing', 'eurohairlab'),
        'id'         => 'eh_assessment_landing_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'single_image', 'name' => esc_html__('Assessment Landing Background Image', 'eurohairlab'), 'id' => 'eh_assessment_landing_background_image', 'std' => 0],
            ['type' => 'text', 'name' => esc_html__('Back Text', 'eurohairlab'), 'id' => 'eh_assessment_landing_back_text', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Back Href', 'eurohairlab'), 'id' => 'eh_assessment_landing_back_href', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Logo Link Href', 'eurohairlab'), 'id' => 'eh_assessment_landing_logo_link_href', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Title', 'eurohairlab'), 'id' => 'eh_assessment_landing_title', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Intro Paragraph 1', 'eurohairlab'), 'id' => 'eh_assessment_landing_intro_paragraph_1', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Intro Paragraph 2', 'eurohairlab'), 'id' => 'eh_assessment_landing_intro_paragraph_2', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Intro Paragraph 3', 'eurohairlab'), 'id' => 'eh_assessment_landing_intro_paragraph_3', 'std' => ''],
            ['type' => 'textarea', 'name' => esc_html__('Intro Paragraph 4', 'eurohairlab'), 'id' => 'eh_assessment_landing_intro_paragraph_4', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Start Button Text', 'eurohairlab'), 'id' => 'eh_assessment_landing_start_button_text', 'std' => ''],
        ],
    ];

    $question_defaults = eurohairlab_get_assessment_question_defaults();

    foreach ($question_defaults as $question_number => $question_config) {
        $fields = [
            ['type' => 'textarea', 'name' => esc_html__('Question Title', 'eurohairlab'), 'id' => "eh_assessment_q{$question_number}_title", 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('Question Type', 'eurohairlab'), 'id' => "eh_assessment_q{$question_number}_type", 'std' => $question_config['type']],
        ];

        if ($question_number < 10) {
            $fields[] = ['type' => 'textarea', 'name' => esc_html__('Why Text', 'eurohairlab'), 'id' => "eh_assessment_q{$question_number}_why_text", 'std' => ''];

            for ($option_number = 1; $option_number <= $question_config['option_count']; $option_number++) {
                if ($question_number === 1) {
                    $fields[] = ['type' => 'single_image', 'name' => sprintf(esc_html__('Option %d Icon', 'eurohairlab'), $option_number), 'id' => "eh_assessment_q{$question_number}_option_{$option_number}_icon", 'std' => 0];
                }

                if ($option_number === 5) {
                    $fields[] = [
                        'type' => 'textarea',
                        'name' => sprintf(esc_html__('Option %d Label', 'eurohairlab'), $option_number),
                        'id' => "eh_assessment_q{$question_number}_option_{$option_number}_label",
                        'std' => '',
                        'rows' => 4,
                    ];
                } else {
                    $fields[] = ['type' => 'text', 'name' => sprintf(esc_html__('Option %d Label', 'eurohairlab'), $option_number), 'id' => "eh_assessment_q{$question_number}_option_{$option_number}_label", 'std' => ''];
                }
                $fields[] = ['type' => 'text', 'name' => sprintf(esc_html__('Option %d Value', 'eurohairlab'), $option_number), 'id' => "eh_assessment_q{$question_number}_option_{$option_number}_value", 'std' => ''];
            }
        } else {
            $fields = array_merge($fields, [
                ['type' => 'text', 'name' => esc_html__('Name Label', 'eurohairlab'), 'id' => 'eh_assessment_q10_name_label', 'std' => ''],
                ['type' => 'text', 'name' => esc_html__('WhatsApp Label', 'eurohairlab'), 'id' => 'eh_assessment_q10_whatsapp_label', 'std' => ''],
                ['type' => 'text', 'name' => esc_html__('Gender Label', 'eurohairlab'), 'id' => 'eh_assessment_q10_gender_label', 'std' => ''],
                ['type' => 'text', 'name' => esc_html__('Gender Placeholder', 'eurohairlab'), 'id' => 'eh_assessment_q10_gender_placeholder', 'std' => ''],
                ['type' => 'text', 'name' => esc_html__('Gender Option 1', 'eurohairlab'), 'id' => 'eh_assessment_q10_gender_option_1', 'std' => ''],
                ['type' => 'text', 'name' => esc_html__('Gender Option 2', 'eurohairlab'), 'id' => 'eh_assessment_q10_gender_option_2', 'std' => ''],
                ['type' => 'text', 'name' => esc_html__('Birth date label', 'eurohairlab'), 'id' => 'eh_assessment_q10_birthdate_label', 'std' => ''],
                ['type' => 'text', 'name' => esc_html__('Branch Office label', 'eurohairlab'), 'id' => 'eh_assessment_q10_branch_office_label', 'std' => ''],
                ['type' => 'text', 'name' => esc_html__('Branch Office placeholder', 'eurohairlab'), 'id' => 'eh_assessment_q10_branch_office_placeholder', 'std' => ''],
                ['type' => 'textarea', 'name' => esc_html__('Consent Text', 'eurohairlab'), 'id' => 'eh_assessment_q10_consent_text', 'std' => ''],
                ['type' => 'text', 'name' => esc_html__('Submit Button Text', 'eurohairlab'), 'id' => 'eh_assessment_q10_submit_button_text', 'std' => ''],
            ]);
        }

        $meta_boxes[] = [
            'title'      => esc_html__($question_config['title'], 'eurohairlab'),
            'id'         => "eh_assessment_q{$question_number}_section",
            'post_types' => ['page'],
            'context'    => 'normal',
            'autosave'   => true,
            'priority'   => 'high',
            'closed'     => true,
            'fields'     => $fields,
        ];
    }

    $meta_boxes[] = [
        'title'      => esc_html__('Complete Assessment', 'eurohairlab'),
        'id'         => 'eh_assessment_complete_section',
        'post_types' => ['page'],
        'context'    => 'normal',
        'autosave'   => true,
        'priority'   => 'high',
        'closed'     => true,
        'fields'     => [
            ['type' => 'textarea', 'name' => esc_html__('Title', 'eurohairlab'), 'id' => 'eh_assessment_complete_title', 'std' => ''],
            ['type' => 'wysiwyg', 'name' => esc_html__('Paragraph', 'eurohairlab'), 'id' => 'eh_assessment_complete_paragraph', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('CTA Text', 'eurohairlab'), 'id' => 'eh_assessment_complete_cta_text', 'std' => ''],
            ['type' => 'text', 'name' => esc_html__('CTA Href', 'eurohairlab'), 'id' => 'eh_assessment_complete_cta_href', 'std' => ''],
            ['type' => 'single_image', 'name' => esc_html__('Visual Image', 'eurohairlab'), 'id' => 'eh_assessment_complete_visual_image', 'std' => 0],
        ],
    ];

    return $meta_boxes;
}

function eurohairlab_seed_assessment_meta_box_defaults(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $assessment_page = get_page_by_path('assessment', OBJECT, 'page');
    $assessment_page_id = $assessment_page instanceof WP_Post ? (int) $assessment_page->ID : 0;

    if (!$assessment_page_id || get_post_meta($assessment_page_id, '_eh_assessment_meta_seeded', true)) {
        return;
    }

    $landing_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-home.webp', $assessment_page_id);
    $complete_visual_image_id = (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-complete-bg.webp', $assessment_page_id);
    $q1_icon_ids = [
        1 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-hairline.png', $assessment_page_id),
        2 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-crown.png', $assessment_page_id),
        3 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-diffuse.png', $assessment_page_id),
        4 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-fallout.png', $assessment_page_id),
        5 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-scalp.png', $assessment_page_id),
        6 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-healthy.png', $assessment_page_id),
    ];
    $content = eurohairlab_get_assessment_content_defaults();

    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_background_image', $landing_image_id);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_back_text', $content['landing']['back_text']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_back_href', $content['landing']['back_href']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_logo_link_href', $content['landing']['logo_link_href']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_title', $content['landing']['title']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_intro_paragraph_1', $content['landing']['intro_paragraph_1']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_intro_paragraph_2', $content['landing']['intro_paragraph_2']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_intro_paragraph_3', $content['landing']['intro_paragraph_3']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_intro_paragraph_4', $content['landing']['intro_paragraph_4']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_start_button_text', $content['landing']['start_button_text']);

    foreach ($content['questions'] as $question_number => $question) {
        rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_title", $question['title']);
        rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_type", $question['type']);

        if ($question_number < 10) {
            rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_why_text", $question['why']);

            foreach ($question['options'] as $option_index => $option) {
                $option_number = $option_index + 1;

                if ($question_number === 1) {
                    rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_option_{$option_number}_icon", (int) $q1_icon_ids[$option_number]);
                }

                rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_option_{$option_number}_label", $option['label']);
                rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_option_{$option_number}_value", $option['value']);
            }

            continue;
        }

        delete_post_meta($assessment_page_id, 'eh_assessment_q10_why_text');
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_name_label', $content['form']['name_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_whatsapp_label', $content['form']['whatsapp_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_gender_label', $content['form']['gender_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_gender_placeholder', $content['form']['gender_placeholder']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_gender_option_1', $content['form']['gender_option_1']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_gender_option_2', $content['form']['gender_option_2']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_birthdate_label', $content['form']['birthdate_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_branch_office_label', $content['form']['branch_office_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_branch_office_placeholder', $content['form']['branch_office_placeholder']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_consent_text', $content['form']['consent_text']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_submit_button_text', $content['form']['submit_button_text']);
    }

    rwmb_set_meta($assessment_page_id, 'eh_assessment_complete_title', $content['complete']['title']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_complete_paragraph', $content['complete']['paragraph']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_complete_cta_text', $content['complete']['cta_text']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_complete_cta_href', $content['complete']['cta_href']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_complete_visual_image', $complete_visual_image_id);

    update_post_meta($assessment_page_id, '_eh_assessment_meta_seeded', '1');
}
add_action('admin_init', 'eurohairlab_seed_assessment_meta_box_defaults');

function eurohairlab_migrate_assessment_meta_box_schema(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $assessment_page = get_page_by_path('assessment', OBJECT, 'page');
    $assessment_page_id = $assessment_page instanceof WP_Post ? (int) $assessment_page->ID : 0;

    if (!$assessment_page_id || get_post_meta($assessment_page_id, '_eh_assessment_meta_v2_migrated', true)) {
        return;
    }

    $q1_icon_ids = [
        1 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-hairline.png', $assessment_page_id),
        2 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-crown.png', $assessment_page_id),
        3 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-diffuse.png', $assessment_page_id),
        4 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-fallout.png', $assessment_page_id),
        5 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-scalp.png', $assessment_page_id),
        6 => (int) eurohairlab_import_theme_asset_to_media_library('assets/images/figma/assessment-option-healthy.png', $assessment_page_id),
    ];

    foreach ($q1_icon_ids as $option_number => $attachment_id) {
        if ($attachment_id > 0) {
            rwmb_set_meta($assessment_page_id, "eh_assessment_q1_option_{$option_number}_icon", $attachment_id);
        }
    }

    $question_defaults = eurohairlab_get_assessment_question_defaults();
    foreach ($question_defaults as $question_number => $question_config) {
        if ($question_number === 10) {
            delete_post_meta($assessment_page_id, 'eh_assessment_q10_why_text');
            continue;
        }

        for ($option_number = $question_config['option_count'] + 1; $option_number <= 6; $option_number++) {
            delete_post_meta($assessment_page_id, "eh_assessment_q{$question_number}_option_{$option_number}_label");
            delete_post_meta($assessment_page_id, "eh_assessment_q{$question_number}_option_{$option_number}_value");
            delete_post_meta($assessment_page_id, "eh_assessment_q{$question_number}_option_{$option_number}_icon");
        }
    }

    update_post_meta($assessment_page_id, '_eh_assessment_meta_v2_migrated', '1');
}
add_action('admin_init', 'eurohairlab_migrate_assessment_meta_box_schema');

function eurohairlab_migrate_assessment_meta_box_language(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $assessment_page = get_page_by_path('assessment', OBJECT, 'page');
    $assessment_page_id = $assessment_page instanceof WP_Post ? (int) $assessment_page->ID : 0;

    if (!$assessment_page_id || get_post_meta($assessment_page_id, '_eh_assessment_meta_v3_language_migrated', true)) {
        return;
    }

    $content = eurohairlab_get_assessment_content_defaults();

    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_back_text', $content['landing']['back_text']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_back_href', $content['landing']['back_href']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_logo_link_href', $content['landing']['logo_link_href']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_title', $content['landing']['title']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_intro_paragraph_1', $content['landing']['intro_paragraph_1']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_intro_paragraph_2', $content['landing']['intro_paragraph_2']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_intro_paragraph_3', $content['landing']['intro_paragraph_3']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_intro_paragraph_4', $content['landing']['intro_paragraph_4']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_landing_start_button_text', $content['landing']['start_button_text']);

    foreach ($content['questions'] as $question_number => $question) {
        rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_title", $question['title']);
        rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_type", $question['type']);

        if ($question_number < 10) {
            rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_why_text", $question['why']);
            foreach ($question['options'] as $option_index => $option) {
                $option_number = $option_index + 1;
                rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_option_{$option_number}_label", $option['label']);
                rwmb_set_meta($assessment_page_id, "eh_assessment_q{$question_number}_option_{$option_number}_value", $option['value']);
            }
            continue;
        }

        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_name_label', $content['form']['name_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_whatsapp_label', $content['form']['whatsapp_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_gender_label', $content['form']['gender_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_gender_placeholder', $content['form']['gender_placeholder']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_gender_option_1', $content['form']['gender_option_1']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_gender_option_2', $content['form']['gender_option_2']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_birthdate_label', $content['form']['birthdate_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_branch_office_label', $content['form']['branch_office_label']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_branch_office_placeholder', $content['form']['branch_office_placeholder']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_consent_text', $content['form']['consent_text']);
        rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_submit_button_text', $content['form']['submit_button_text']);
    }

    rwmb_set_meta($assessment_page_id, 'eh_assessment_complete_title', $content['complete']['title']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_complete_paragraph', $content['complete']['paragraph']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_complete_cta_text', $content['complete']['cta_text']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_complete_cta_href', $content['complete']['cta_href']);

    update_post_meta($assessment_page_id, '_eh_assessment_meta_v3_language_migrated', '1');
}
add_action('admin_init', 'eurohairlab_migrate_assessment_meta_box_language');

function eurohairlab_migrate_assessment_q10_branch_birthdate_labels(): void
{
    if (!is_admin() || !function_exists('rwmb_set_meta')) {
        return;
    }

    $assessment_page = get_page_by_path('assessment', OBJECT, 'page');
    $assessment_page_id = $assessment_page instanceof WP_Post ? (int) $assessment_page->ID : 0;

    if (!$assessment_page_id || get_post_meta($assessment_page_id, '_eh_assessment_meta_v4_form_fields', true)) {
        return;
    }

    $content = eurohairlab_get_assessment_content_defaults();
    rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_birthdate_label', $content['form']['birthdate_label']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_branch_office_label', $content['form']['branch_office_label']);
    rwmb_set_meta($assessment_page_id, 'eh_assessment_q10_branch_office_placeholder', $content['form']['branch_office_placeholder']);

    update_post_meta($assessment_page_id, '_eh_assessment_meta_v4_form_fields', '1');
}
add_action('admin_init', 'eurohairlab_migrate_assessment_q10_branch_birthdate_labels');
