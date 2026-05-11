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

$landing_bg_url = $resolve_image($landing_background_meta_raw, $theme_uri . '/assets/images/figma/assessment-home.webp');

$assessment_force_id_lang = function_exists('eurohairlab_assessment_force_id_lang_enabled')
    && eurohairlab_assessment_force_id_lang_enabled((int) $page_id);

$assessment_i18n_bundles = [
    'en' => null,
    'id' => null,
];
if (function_exists('eurohairlab_assessment_build_lang_specific_vars')) {
    foreach (['en', 'id'] as $forced_lang) {
        $lang_filter = static function () use ($forced_lang): string {
            return $forced_lang;
        };
        add_filter('eurohairlab_public_lang_override', $lang_filter, PHP_INT_MAX);
        $assessment_i18n_bundles[$forced_lang] = eurohairlab_assessment_build_lang_specific_vars((int) $page_id, $theme_uri, $landing_bg_url);
        remove_filter('eurohairlab_public_lang_override', $lang_filter, PHP_INT_MAX);
    }
}

$assessment_active_lang = (function_exists('eurohairlab_get_public_lang') && eurohairlab_get_public_lang() === 'id') ? 'id' : 'en';
$assessment_data = $assessment_i18n_bundles[$assessment_active_lang] ?? $assessment_i18n_bundles['en'];
$landing = $assessment_data['landing'];
$wizard_sidebar_image = $assessment_data['wizard_sidebar_image'];
$complete = $assessment_data['complete'];
$assessment_steps = $assessment_data['assessment_steps'];
$form_labels = $assessment_data['form_labels'];
$assessment_ui = $assessment_data['assessment_ui'];

$assessment_i18n_client = [];
foreach (['en', 'id'] as $lc) {
    $row = $assessment_i18n_bundles[$lc] ?? null;
    if (!is_array($row)) {
        continue;
    }
    $cp = (string) ($row['complete']['paragraph'] ?? '');
    if ($cp !== '' && str_contains($cp, '&lt;') && !preg_match('/<[a-z][^>]*>/i', $cp)) {
        $cp = html_entity_decode($cp, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $assessment_i18n_client[$lc] = [
        'steps' => $row['assessment_steps'],
        'landing' => [
            'back_text' => (string) ($row['landing']['back_text'] ?? ''),
            'title' => (string) ($row['landing']['title'] ?? ''),
            'intro_paragraphs' => $row['landing']['intro_paragraphs'] ?? [],
            'start_button_text' => (string) ($row['landing']['start_button_text'] ?? ''),
        ],
        'complete' => [
            'title' => (string) ($row['complete']['title'] ?? ''),
            'paragraph_html' => wp_kses_post($cp),
            'cta_text' => (string) ($row['complete']['cta_text'] ?? ''),
        ],
        'form_labels' => $row['form_labels'],
        'ui' => $row['assessment_ui'],
    ];
}

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
  data-assessment-lang="<?php echo esc_attr($assessment_active_lang); ?>"
  data-assessment-force-id-lang="<?php echo $assessment_force_id_lang ? '1' : '0'; ?>"
  data-home-url="<?php echo $home_url; ?>"
  data-contact-url="<?php echo $contact_url; ?>"
  data-gender-placeholder="<?php echo esc_attr($form_labels['gender_placeholder']); ?>"
  data-source-page-slug="<?php echo esc_attr($page_slug); ?>"
>
  <script id="assessment-config" type="application/json"><?php echo wp_json_encode($assessment_steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
  <script id="assessment-i18n-bundles" type="application/json"><?php echo wp_json_encode($assessment_i18n_client, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

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
            <span data-assessment-i18n-landing="back"><?php echo esc_html($landing['back_text']); ?></span>
          </a>

          <a href="<?php echo $logo_url; ?>" class="assessment-brand" data-assessment-i18n-aria="home_aria" aria-label="<?php echo esc_attr($assessment_ui['home_aria']); ?>">
            <img
              src="<?php echo $theme_uri; ?>/assets/images/logo.webp"
              alt="Eurohairlab by Dr.Scalp"
              width="293"
              height="57"
              decoding="async"
            >
          </a>

          <?php if (!$assessment_force_id_lang) : ?>
            <?php get_template_part('template-parts/site-header', 'lang', ['id_suffix' => 'assessment-landing']); ?>
          <?php endif; ?>
        </header>

        <div class="assessment-landing__body flex flex-col justify-center mt-6 sm:mt-10 lg:mt-0">
          <h1 class="assessment-title assessment-title--landing text-[1.2rem] md:text-[3vw]" data-assessment-i18n-landing="title"><?php echo esc_html($landing['title']); ?></h1>

          <div class="assessment-copy" data-assessment-i18n-landing="intro-wrap">
            <?php foreach ($landing['intro_paragraphs'] as $paragraph) : ?>
                <h1 class="assessment-title assessment-title--landing text-[1.2rem] md:text-[3vw]"><?php echo esc_html($paragraph); ?></h1>
            <?php endforeach; ?>
          </div>

          <button type="button" class="assessment-outline-button assessment-start-button w-fit" data-assessment-start>
            <span data-assessment-i18n-landing="start"><?php echo esc_html($landing['start_button_text']); ?></span>
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
          <button type="button" class="assessment-icon-button" data-assessment-back data-assessment-i18n-aria="wizard_back" aria-label="<?php echo esc_attr($assessment_ui['wizard_back']); ?>">
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

          <a href="<?php echo $logo_url; ?>" class="assessment-brand" data-assessment-i18n-aria="home_aria" aria-label="<?php echo esc_attr($assessment_ui['home_aria']); ?>">
            <img
              src="<?php echo $theme_uri; ?>/assets/images/logo.webp"
              alt="Eurohairlab by Dr.Scalp"
              width="293"
              height="57"
              decoding="async"
            >
          </a>

          <div class="assessment-wizard-header__end">
            <?php if (!$assessment_force_id_lang) : ?>
              <?php get_template_part('template-parts/site-header', 'lang', ['id_suffix' => 'assessment-wizard']); ?>
            <?php endif; ?>
            <button type="button" class="assessment-icon-button" data-assessment-close data-assessment-i18n-aria="wizard_close" aria-label="<?php echo esc_attr($assessment_ui['wizard_close']); ?>">X</button>
          </div>
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
                <span class="assessment-field__label" data-assessment-i18n-form="name"><?php echo esc_html($form_labels['name']); ?><span class="assessment-field__required" aria-hidden="true">*</span></span>
                <input type="text" name="name" autocomplete="section-contact name" class="assessment-field__control" data-assessment-input="name" maxlength="<?php echo esc_attr((string) $max_name_attr); ?>" id="assessment-respondent-name">
                <p class="assessment-field__error" data-assessment-error-for="name" hidden></p>
              </label>

              <label class="assessment-field">
                <span class="assessment-field__label" data-assessment-i18n-form="whatsapp"><?php echo esc_html($form_labels['whatsapp']); ?><span class="assessment-field__required" aria-hidden="true">*</span></span>
                <input type="tel" name="whatsapp" autocomplete="section-contact tel" class="assessment-field__control" data-assessment-input="whatsapp" maxlength="32" inputmode="tel" id="assessment-respondent-tel">
                <p class="assessment-field__error" data-assessment-error-for="whatsapp" hidden></p>
              </label>

              <label class="assessment-field assessment-field--select">
                <span class="assessment-field__label" data-assessment-i18n-form="gender"><?php echo esc_html($form_labels['gender']); ?><span class="assessment-field__required" aria-hidden="true">*</span></span>
                <div class="assessment-select" data-assessment-select>
                  <input type="hidden" name="gender" value="" data-assessment-input="gender">
                  <button type="button" class="assessment-select__trigger" data-assessment-select-trigger aria-expanded="false" aria-haspopup="listbox">
                    <span data-assessment-select-label><?php echo esc_html($form_labels['gender_placeholder']); ?></span>
                  </button>
                  <div class="assessment-select__menu hidden" data-assessment-select-menu>
                    <button type="button" class="assessment-select__option is-selected" data-assessment-select-option="" data-assessment-i18n-form="gender_placeholder" role="option" aria-selected="true"><?php echo esc_html($form_labels['gender_placeholder']); ?></button>
                    <button type="button" class="assessment-select__option" data-assessment-select-option="male" data-assessment-i18n-form="gender_option_1" role="option" aria-selected="false"><?php echo esc_html($form_labels['gender_option_1']); ?></button>
                    <button type="button" class="assessment-select__option" data-assessment-select-option="female" data-assessment-i18n-form="gender_option_2" role="option" aria-selected="false"><?php echo esc_html($form_labels['gender_option_2']); ?></button>
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
                <span class="assessment-field__label" data-assessment-i18n-form="branch_office"><?php echo esc_html($form_labels['branch_office']); ?><?php if (!empty($branch_office_rows)) : ?><span class="assessment-field__required" aria-hidden="true">*</span><?php endif; ?></span>
                <select name="branch_office_masking_id" class="assessment-field__control assessment-field__control--select" autocomplete="off" data-assessment-input="branchOffice" data-assessment-i18n-aria="branch_office" aria-label="<?php echo esc_attr($form_labels['branch_office']); ?>" id="assessment-branch-office">
                  <option value="" data-assessment-i18n-form="branch_office_placeholder"><?php echo esc_html($form_labels['branch_office_placeholder']); ?></option>
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
                <span><span class="assessment-field__required" aria-hidden="true">*</span> <span data-assessment-i18n-form="consent"><?php echo esc_html($form_labels['consent']); ?></span></span>
              </label>
              <p class="assessment-field__error assessment-consent__error" data-assessment-error-for="consent" hidden></p>
            </div>

            <p class="assessment-form__submit-error" data-assessment-submit-error hidden></p>

            <button type="button" class="assessment-outline-button assessment-submit-button" data-assessment-submit disabled>
              <span data-assessment-i18n-form="submit"><?php echo esc_html($form_labels['submit']); ?></span>
            </button>
            </form>
          </div>

          <button type="button" class="assessment-why hidden" data-assessment-why-button>
            <span class="assessment-why__icon" aria-hidden="true">i</span>
            <span data-assessment-i18n-ui="why_short"><?php echo esc_html($assessment_ui['why_short']); ?></span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="assessment-page__screen hidden" data-assessment-screen="complete">
    <div class="assessment-complete mt-[4rem]">
      <div class="assessment-complete__hero">
        <div class="assessment-complete__copy">
          <h2 class="assessment-title assessment-title--complete mt-0 lg:mt-[6rem]" data-assessment-i18n-complete="title"><?php echo esc_html($complete['title']); ?></h2>
          <?php
          $complete_paragraph_html = (string) ($complete['paragraph'] ?? '');
          if ($complete_paragraph_html !== '' && str_contains($complete_paragraph_html, '&lt;') && !preg_match('/<[a-z][^>]*>/i', $complete_paragraph_html)) {
              $complete_paragraph_html = html_entity_decode($complete_paragraph_html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
          }
          ?>
          <div class="assessment-complete__body" data-assessment-i18n-complete="body">
            <?php echo wp_kses_post($complete_paragraph_html); ?>
          </div>
          <a href="<?php echo $complete['cta_href']; ?>" class="assessment-outline-link assessment-outline-link--wide" data-assessment-i18n-complete="cta"><?php echo esc_html($complete['cta_text']); ?></a>
        </div>

        <div class="assessment-complete__visual">
          <img
            src="<?php echo esc_url($complete['visual_image']); ?>"
            data-assessment-i18n-aria="complete_visual_alt"
            alt="<?php echo esc_attr($assessment_ui['complete_visual_alt']); ?>"
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
      <button type="button" class="assessment-modal__close" data-assessment-modal-close data-assessment-i18n-aria="modal_close" aria-label="<?php echo esc_attr($assessment_ui['modal_close']); ?>">X</button>
      <h3 id="assessment-modal-title" class="assessment-modal__title" data-assessment-i18n-ui="why_title"><?php echo esc_html($assessment_ui['why_title']); ?></h3>
      <p class="assessment-modal__description" data-assessment-modal-description></p>
    </div>
  </div>

  <div class="assessment-submit-loading hidden" data-assessment-submit-loading aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="assessment-submit-loading-title" aria-busy="true">
    <div class="assessment-submit-loading__backdrop" aria-hidden="true"></div>
    <div class="assessment-submit-loading__dialog">
      <div class="assessment-submit-loading__spinner" aria-hidden="true"></div>
      <p id="assessment-submit-loading-title" class="assessment-submit-loading__title" data-assessment-i18n-ui="submit_loading_title"><?php echo esc_html($assessment_ui['submit_loading_title']); ?></p>
      <p class="assessment-submit-loading__hint" data-assessment-i18n-ui="submit_loading_hint"><?php echo esc_html($assessment_ui['submit_loading_hint']); ?></p>
    </div>
  </div>
</main>
