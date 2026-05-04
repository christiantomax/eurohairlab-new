<?php

declare(strict_types=1);

$theme_uri = esc_url(eurohairlab_rewrite_assessment_page_asset_url((string) get_template_directory_uri()));

// $theme_uri = esc_url(get_template_directory_uri());
$social_instagram = 'https://www.instagram.com/eurohairlab/';
$social_facebook = 'https://www.facebook.com/eurohairlab';
$social_tiktok = 'https://www.tiktok.com/@eurohairlab';

$page_id = get_queried_object_id();
$page_slug = '';
if ($page_id > 0) {
    $page_obj = get_post($page_id);
    if ($page_obj instanceof WP_Post && (string) $page_obj->post_name !== '') {
        $page_slug = (string) $page_obj->post_name;
    }
}
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
        } elseif (isset($value['full_url']) && is_string($value['full_url'])) {
            $out = $value['full_url'];
        } elseif (isset($value['url']) && is_string($value['url'])) {
            $out = $value['url'];
        }
    }

    return eurohairlab_rewrite_assessment_page_asset_url($out);
};

/** Meta Box can return a flat file array or a map of attachment id => file info. */
$normalize_rwmb_image_meta = static function ($raw) {
    if (!is_array($raw) || $raw === []) {
        return $raw;
    }
    if (isset($raw['ID']) || isset($raw['full_url']) || isset($raw['url'])) {
        return $raw;
    }
    $first = reset($raw);

    return is_array($first) ? $first : $raw;
};

/** Metabox “Assessment Landing Background Image” (`eh_assessment_landing_background_image`) — used for landing CSS bg and wizard sidebar. */
$landing_background_meta_raw = null;
if ($page_id && function_exists('eurohairlab_rwmb_page_meta')) {
    $landing_background_meta_raw = eurohairlab_rwmb_page_meta($page_id, 'eh_assessment_landing_background_image', ['size' => 'full']);
}
if (
    $landing_background_meta_raw === null
    || $landing_background_meta_raw === false
    || $landing_background_meta_raw === ''
    || $landing_background_meta_raw === []
) {
    $landing_background_meta_raw = $mb_get('eh_assessment_landing_background_image');
}
$landing_background_meta_raw = is_array($landing_background_meta_raw)
    ? $normalize_rwmb_image_meta($landing_background_meta_raw)
    : $landing_background_meta_raw;

$home_url = esc_url($resolve_link((string) $mb_get('eh_assessment_landing_back_href'), home_url('/')));
$logo_url = esc_url($resolve_link((string) $mb_get('eh_assessment_landing_logo_link_href'), home_url('/')));
$contact_url = esc_url(eurohairlab_get_primary_cta_url());

$landing = [
    'background_image' => $resolve_image($landing_background_meta_raw, $theme_uri . '/assets/images/figma/assessment-home.webp'),
    'back_text' => (string) ($mb_get('eh_assessment_landing_back_text') ?: 'Exit'),
    'title' => (string) ($mb_get('eh_assessment_landing_title') ?: 'Online Hair Assessment'),
    'intro_paragraphs' => array_values(array_filter([
        (string) ($mb_get('eh_assessment_landing_intro_paragraph_1') ?: ''),
        (string) ($mb_get('eh_assessment_landing_intro_paragraph_2') ?: ''),
        (string) ($mb_get('eh_assessment_landing_intro_paragraph_3') ?: ""),
        (string) ($mb_get('eh_assessment_landing_intro_paragraph_4') ?: ''),
    ], static fn($item) => trim($item) !== '')),
    'start_button_text' => (string) ($mb_get('eh_assessment_landing_start_button_text') ?: 'Start Your Assessment Now'),
];

/** Same metabox image as landing (`eh_assessment_landing_background_image`). */
$wizard_sidebar_image = $landing['background_image'];
$question_defaults = function_exists('eurohairlab_get_assessment_question_defaults')
    ? eurohairlab_get_assessment_question_defaults()
    : [];
$complete = [
    'title' => (string) ($mb_get('eh_assessment_complete_title') ?: 'Thank You For Taking The Time To Complete Our Hair Assessment'),
    'paragraph' => (string) ($mb_get('eh_assessment_complete_paragraph') ?: 'Thank you for sharing your concerns with us. To build a treatment plan that is genuinely personalised to your condition, we would like to understand it in more detail. Book your complimentary 15-minute consultation below.'),
    'cta_text' => (string) ($mb_get('eh_assessment_complete_cta_text') ?: 'WhatsApp Consultation'),
    'cta_href' => esc_url($resolve_link((string) $mb_get('eh_assessment_complete_cta_href'), eurohairlab_get_primary_cta_url())),
    'visual_image' => $resolve_image($mb_get('eh_assessment_complete_visual_image'), $theme_uri . '/assets/images/figma/assessment-complete-bg.webp'),
];

$assessment_steps = [];
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

$form_labels = [
    'name' => (string) ($mb_get('eh_assessment_q10_name_label') ?: 'Name'),
    'whatsapp' => (string) ($mb_get('eh_assessment_q10_whatsapp_label') ?: 'WhatsApp Number (08xx / +62xx)'),
    'gender' => (string) ($mb_get('eh_assessment_q10_gender_label') ?: 'Gender'),
    'gender_placeholder' => (string) ($mb_get('eh_assessment_q10_gender_placeholder') ?: 'Select gender'),
    'gender_option_1' => (string) ($mb_get('eh_assessment_q10_gender_option_1') ?: 'Pria'),
    'gender_option_2' => (string) ($mb_get('eh_assessment_q10_gender_option_2') ?: 'Wanita'),
    'branch_office' => (string) ($mb_get('eh_assessment_q10_branch_office_label') ?: 'Branch Office'),
    'branch_office_placeholder' => (string) ($mb_get('eh_assessment_q10_branch_office_placeholder') ?: 'Select branch office'),
    'consent' => (string) ($mb_get('eh_assessment_q10_consent_text') ?: 'I consent to the use of my data for hair and scalp health evaluation purposes in accordance with applicable personal data protection regulations.'),
    'submit' => (string) ($mb_get('eh_assessment_q10_submit_button_text') ?: 'Submit'),
];

$branch_office_rows = function_exists('eh_assessment_get_active_branch_outlet_options')
    ? eh_assessment_get_active_branch_outlet_options()
    : [];
$assessment_input_limits = function_exists('eh_assessment_get_frontend_input_limits')
    ? eh_assessment_get_frontend_input_limits()
    : [
        'max_name_utf8_bytes' => 191,
        'max_answer_utf8_bytes' => 500,
        'max_question_utf8_bytes' => 500,
        'whatsapp_digits_min' => 8,
        'whatsapp_digits_max' => 20,
    ];
$max_name_attr = (int) ($assessment_input_limits['max_name_utf8_bytes'] ?? 191);
if ($max_name_attr < 1) {
    $max_name_attr = 191;
}
?>
<main
  id="assessment-page"
  class="assessment-page relative min-h-screen bg-white text-[#231f20]"
  data-home-url="<?php echo $home_url; ?>"
  data-contact-url="<?php echo $contact_url; ?>"
  data-gender-placeholder="<?php echo esc_attr($form_labels['gender_placeholder']); ?>"
  data-source-page-slug="<?php echo esc_attr($page_slug); ?>"
>
  <script id="assessment-config" type="application/json"><?php echo wp_json_encode($assessment_steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

  <section class="assessment-page__screen" data-assessment-screen="landing" >
    <div class="assessment-landing-shell">
      <div class="assessment-layout__content assessment-layout__content--landing">
        <header class="assessment-landing__header">
          <a href="<?php echo $home_url; ?>" class="assessment-inline-link">
            <img
              src="<?php echo $theme_uri; ?>/assets/images/icons/back-arrow.webp"
              alt=""
              aria-hidden="true"
              class="assessment-inline-link__arrow"
              width="5"
              height="12"
              decoding="async"
            >
            <span><?php echo esc_html($landing['back_text']); ?></span>
          </a>

          <a href="<?php echo $logo_url; ?>" class="assessment-brand  absolute lg:relative mt-[6%] lg:mt-0" aria-label="Eurohairlab home">
            <img
              src="<?php echo $theme_uri; ?>/assets/images/logo.webp"
              alt="Eurohairlab by Dr.Scalp"
              width="293"
              height="57"
              decoding="async"
            >
          </a>
        </header>

        <div class="assessment-landing__body flex flex-col justify-center mt-[8rem] lg:mt-0">
          <h1 class="assessment-title assessment-title--landing text-[1.2rem] md:text-[3vw]"><?php echo esc_html($landing['title']); ?></h1>

          <div class="assessment-copy">
            <?php foreach ($landing['intro_paragraphs'] as $paragraph) : ?>
                <h1 class="assessment-title assessment-title--landing text-[1.2rem] md:text-[3vw]"><?php echo esc_html($paragraph); ?></h1>
            <?php endforeach; ?>
          </div>

          <button type="button" class="assessment-outline-button assessment-start-button w-fit" data-assessment-start>
            <span><?php echo esc_html($landing['start_button_text']); ?></span>
            <img
              src="<?php echo $theme_uri; ?>/assets/images/icons/arrow-button.webp"
              alt=""
              aria-hidden="true"
              class="assessment-outline-button__arrow"
              width="18"
              height="18"
              decoding="async"
            >
          </button>

          <div class="mt-[4rem] flex flex-col lg:flex-row">
            <a href="<?php echo esc_url($social_instagram); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="mr-8 mb-4 lg:mb-0 inline-flex items-center transition hover:opacity-80">
              <img
                src="<?php echo $theme_uri; ?>/assets/images/icons/instagram-black.webp"
                alt="Instagram"
                class="h-5 w-5 object-contain mr-2"
                width="28"
                height="28"
                loading="lazy"
                decoding="async"
              >
              <span class="text-[12px]">@EUROHAIRLAB</span>
            </a>
            <a href="<?php echo esc_url($social_tiktok); ?>" target="_blank" rel="noopener noreferrer" aria-label="Tiktok" class="inline-flex items-center transition hover:opacity-80">
              <img
                src="<?php echo $theme_uri; ?>/assets/images/icons/tiktok-black.webp"
                alt="Facebook"
                class="h-5 w-5 object-contain mr-2"
                width="28"
                height="28"
                loading="lazy"
                decoding="async"
              >
              <span class="text-[12px]">@EUROHAIRLAB</span>
            </a>
          </div>
        </div>
      </div>

      <div class="assessment-landing__photo-wrap" aria-hidden="true">
        <img
          class="assessment-landing__photo"
          src="<?php echo esc_url($landing['background_image']); ?>"
          alt=""
          width="1440"
          height="1024"
          fetchpriority="high"
          decoding="async"
        >
      </div>
    </div>
  </section>

  <section class="assessment-page__screen hidden" data-assessment-screen="wizard" aria-live="polite">
    <div class="assessment-layout assessment-layout--wizard">
      <div class="assessment-layout__media">
        <img
          src="<?php echo esc_url($wizard_sidebar_image); ?>"
          alt=""
          class="assessment-layout__photo"
          width="1025"
          height="2051"
          decoding="async"
        >
      </div>

      <div class="assessment-layout__content assessment-layout__content--wizard">
        <header class="assessment-wizard-header">
          <button type="button" class="assessment-icon-button" data-assessment-back aria-label="Go back to the previous question">
            <img
              src="<?php echo $theme_uri; ?>/assets/images/icons/back-arrow.webp"
              alt=""
              aria-hidden="true"
              class="assessment-icon-button__icon"
              width="12"
              height="12"
              decoding="async"
            >
          </button>

          <a href="<?php echo $logo_url; ?>" class="assessment-brand" aria-label="Eurohairlab home">
            <img
              src="<?php echo $theme_uri; ?>/assets/images/logo.webp"
              alt="Eurohairlab by Dr.Scalp"
              width="293"
              height="57"
              decoding="async"
            >
          </a>

          <button type="button" class="assessment-icon-button" data-assessment-close aria-label="Return to the assessment landing page">X</button>
        </header>

        <div class="assessment-progress" aria-hidden="true">
          <?php for ($index = 0; $index < 10; $index++) : ?>
            <span class="assessment-progress__item"></span>
          <?php endfor; ?>
        </div>

        <div class="assessment-stage">
          <h2 class="assessment-title assessment-title--wizard" data-assessment-title></h2>

          <div class="assessment-options" data-assessment-options></div>

          <div class="assessment-form hidden" data-assessment-form-wrap>
            <form class="assessment-form__native" autocomplete="on" novalidate data-assessment-details-form>
            <div class="assessment-form__fields">
              <p class="assessment-field__error" data-assessment-error-for="quiz" hidden></p>
              <label class="assessment-field">
                <span class="assessment-field__label"><?php echo esc_html($form_labels['name']); ?><span class="assessment-field__required" aria-hidden="true">*</span></span>
                <input type="text" name="name" autocomplete="section-contact name" class="assessment-field__control" data-assessment-input="name" maxlength="<?php echo esc_attr((string) $max_name_attr); ?>" id="assessment-respondent-name">
                <p class="assessment-field__error" data-assessment-error-for="name" hidden></p>
              </label>

              <label class="assessment-field">
                <span class="assessment-field__label"><?php echo esc_html($form_labels['whatsapp']); ?><span class="assessment-field__required" aria-hidden="true">*</span></span>
                <input type="tel" name="whatsapp" autocomplete="section-contact tel" class="assessment-field__control" data-assessment-input="whatsapp" maxlength="32" inputmode="tel" id="assessment-respondent-tel">
                <p class="assessment-field__error" data-assessment-error-for="whatsapp" hidden></p>
              </label>

              <label class="assessment-field assessment-field--select">
                <span class="assessment-field__label"><?php echo esc_html($form_labels['gender']); ?><span class="assessment-field__required" aria-hidden="true">*</span></span>
                <div class="assessment-select" data-assessment-select>
                  <input type="hidden" name="gender" value="" data-assessment-input="gender">
                  <button type="button" class="assessment-select__trigger" data-assessment-select-trigger aria-expanded="false" aria-haspopup="listbox">
                    <span data-assessment-select-label><?php echo esc_html($form_labels['gender_placeholder']); ?></span>
                  </button>
                  <div class="assessment-select__menu hidden" data-assessment-select-menu>
                    <button type="button" class="assessment-select__option is-selected" data-assessment-select-option="" role="option" aria-selected="true"><?php echo esc_html($form_labels['gender_placeholder']); ?></button>
                    <button type="button" class="assessment-select__option" data-assessment-select-option="male" role="option" aria-selected="false"><?php echo esc_html($form_labels['gender_option_1']); ?></button>
                    <button type="button" class="assessment-select__option" data-assessment-select-option="female" role="option" aria-selected="false"><?php echo esc_html($form_labels['gender_option_2']); ?></button>
                  </div>
                </div>
                <p class="assessment-field__error" data-assessment-error-for="gender" hidden></p>
              </label>

              <?php
              // Hidden default birthdate for Cekat/API; not shown to respondents.
              $eh_assessment_default_birthdate = function_exists('eh_assessment_default_placeholder_birthdate')
                  ? eh_assessment_default_placeholder_birthdate()
                  : '1990-01-01';
              ?>
              <input type="hidden" name="birthdate" value="<?php echo esc_attr($eh_assessment_default_birthdate); ?>" data-assessment-input="birthdate" id="assessment-respondent-birthdate" autocomplete="off" aria-hidden="true">

              <label class="assessment-field assessment-field--branch-office">
                <span class="assessment-field__label"><?php echo esc_html($form_labels['branch_office']); ?><?php if (!empty($branch_office_rows)) : ?><span class="assessment-field__required" aria-hidden="true">*</span><?php endif; ?></span>
                <select name="branch_office_masking_id" class="assessment-field__control assessment-field__control--select" autocomplete="off" data-assessment-input="branchOffice" aria-label="<?php echo esc_attr($form_labels['branch_office']); ?>" id="assessment-branch-office">
                  <option value=""><?php echo esc_html($form_labels['branch_office_placeholder']); ?></option>
                  <?php foreach ($branch_office_rows as $bo_row) :
                      $mid = function_exists('eh_assessment_normalize_submission_branch_masking_id')
                          ? eh_assessment_normalize_submission_branch_masking_id((string) ($bo_row['cekat_masking_id'] ?? ''))
                          : trim(sanitize_text_field((string) ($bo_row['cekat_masking_id'] ?? '')));
                      if ($mid === '') {
                          continue;
                      }
                      $bo_option_label = function_exists('eh_assessment_branch_outlet_display_label')
                          ? eh_assessment_branch_outlet_display_label($bo_row)
                          : (string) ($bo_row['cekat_name'] ?? '');
                      ?>
                    <option value="<?php echo esc_attr($mid); ?>"><?php echo esc_html($bo_option_label); ?></option>
                  <?php endforeach; ?>
                </select>
                <p class="assessment-field__error" data-assessment-error-for="branchOffice" hidden></p>
              </label>
            </div>

            <div class="assessment-consent-block">
              <label class="assessment-consent">
                <input type="checkbox" value="1" data-assessment-input="consent">
                <span><span class="assessment-field__required" aria-hidden="true">*</span> <?php echo esc_html($form_labels['consent']); ?></span>
              </label>
              <p class="assessment-field__error assessment-consent__error" data-assessment-error-for="consent" hidden></p>
            </div>

            <p class="assessment-form__submit-error" data-assessment-submit-error hidden></p>

            <button type="button" class="assessment-outline-button assessment-submit-button" data-assessment-submit disabled>
              <span><?php echo esc_html($form_labels['submit']); ?></span>
            </button>
            </form>
          </div>

          <button type="button" class="assessment-why hidden" data-assessment-why-button>
            <span class="assessment-why__icon" aria-hidden="true">i</span>
            <span>Why do we ask?</span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="assessment-page__screen hidden" data-assessment-screen="complete">
    <div class="assessment-complete mt-[4rem]">
      <div class="assessment-complete__hero">
        <div class="assessment-complete__copy">
          <h2 class="assessment-title assessment-title--complete mt-0 lg:mt-[6rem]"><?php echo esc_html($complete['title']); ?></h2>
          <?php
          $complete_paragraph_html = (string) ($complete['paragraph'] ?? '');
          if ($complete_paragraph_html !== '' && str_contains($complete_paragraph_html, '&lt;') && !preg_match('/<[a-z][^>]*>/i', $complete_paragraph_html)) {
              $complete_paragraph_html = html_entity_decode($complete_paragraph_html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
          }
          ?>
          <div class="assessment-complete__body">
            <?php echo wp_kses_post($complete_paragraph_html); ?>
          </div>
          <a href="<?php echo $complete['cta_href']; ?>" class="assessment-outline-link assessment-outline-link--wide"><?php echo esc_html($complete['cta_text']); ?></a>
        </div>

        <div class="assessment-complete__visual">
          <img
            src="<?php echo esc_url($complete['visual_image']); ?>"
            alt="Eurohairlab consultation visual"
            width="1440"
            height="732"
            decoding="async"
          >
        </div>
      </div>
    </div>
  </section>

  <div class="assessment-modal hidden" data-assessment-modal aria-hidden="true">
    <div class="assessment-modal__backdrop" data-assessment-modal-close></div>
    <div class="assessment-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="assessment-modal-title">
      <button type="button" class="assessment-modal__close" data-assessment-modal-close aria-label="Close modal">X</button>
      <h3 id="assessment-modal-title" class="assessment-modal__title">Why Do We Ask?</h3>
      <p class="assessment-modal__description" data-assessment-modal-description></p>
    </div>
  </div>

  <div class="assessment-submit-loading hidden" data-assessment-submit-loading aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="assessment-submit-loading-title" aria-busy="true">
    <div class="assessment-submit-loading__backdrop" aria-hidden="true"></div>
    <div class="assessment-submit-loading__dialog">
      <div class="assessment-submit-loading__spinner" aria-hidden="true"></div>
      <p id="assessment-submit-loading-title" class="assessment-submit-loading__title">Mengirim jawaban Anda…</p>
      <p class="assessment-submit-loading__hint">Mohon tunggu sebentar. Jangan tutup atau segarkan halaman ini.</p>
    </div>
  </div>
</main>
