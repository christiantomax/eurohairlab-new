<?php

declare(strict_types=1);

/**
 * Diagnosis landing — content from Meta Box (page) with fallbacks to bundled assets.
 */

$theme_uri = esc_url(get_template_directory_uri());
$figma_uri = $theme_uri . '/assets/images/figma';

$hero_fallback = $figma_uri . '/diagnosis-hero-alt.png';
$intro_fallback = $figma_uri . '/diagnosis-intro.png';

$page_id = get_queried_object_id();

$dx_meta = static function (string $key) use ($page_id) {
    if (!$page_id || !function_exists('rwmb_meta')) {
        return null;
    }

    return rwmb_meta($key, [], $page_id);
};

$dx_str = static function (string $key, string $default = '') use ($dx_meta): string {
    $v = $dx_meta($key);
    if ($v === null || $v === '') {
        return $default;
    }

    return is_string($v) ? $v : (string) $v;
};

$resolve_step_image_url = static function ($value, string $fallback = ''): string {
    if (is_array($value) && $value !== []) {
        if (isset($value['ID']) || isset($value['full_url']) || isset($value['url'])) {
            return eurohairlab_mb_image_url($value, $fallback);
        }

        foreach ($value as $candidate) {
            $url = eurohairlab_mb_image_url($candidate, '');
            if ($url !== '') {
                return $url;
            }
        }
    }

    return eurohairlab_mb_image_url($value, $fallback);
};

$overlay_key = $dx_str('eh_diagnosis_hero_overlay', 'dark_51');
$overlay_class = [
    'dark_51' => 'bg-black/[0.51]',
    'dark_42' => 'bg-black/42',
    'none' => '',
][$overlay_key] ?? 'bg-black/[0.51]';

$hero_image_id = $dx_meta('eh_diagnosis_hero_image');
$hero_image = eurohairlab_mb_image_url($hero_image_id, eurohairlab_diagnosis_resolve_asset('hero-bg.png', $hero_fallback));

$intro_image = eurohairlab_mb_image_url($dx_meta('eh_diagnosis_why_image'), eurohairlab_diagnosis_resolve_asset('intro.png', $intro_fallback));
$why_alt = $dx_str('eh_diagnosis_why_image_alt', esc_attr__('Clinical scalp imaging review during a precision diagnosis consultation at EUROHAIRLAB.', 'eurohairlab'));

$hero_title = $dx_str('eh_diagnosis_hero_title', esc_html__('Understand Your Scalp First', 'eurohairlab'));
$hero_paragraph_html = $dx_str('eh_diagnosis_hero_paragraph', '');
if ($hero_paragraph_html === '') {
    $hero_paragraph_html = '<p>' . esc_html__('Effective treatment begins with an accurate diagnosis. At EUROHAIRLAB, every patient begins with a complete clinical evaluation before any treatment is considered so that what we recommend is built around what we actually find.', 'eurohairlab') . '</p>';
}

$why_kicker = $dx_str('eh_diagnosis_why_kicker', esc_html__('The First Step in Scalp Care: Precision Diagnosis', 'eurohairlab'));
$why_title = $dx_str('eh_diagnosis_why_title', esc_html__('Why Scalp', 'eurohairlab') . "\n" . esc_html__(' Diagnosis ?', 'eurohairlab'));
$why_body_html = $dx_str('eh_diagnosis_why_body', '');
if ($why_body_html === '') {
    $why_body_html = '';
    foreach (
        [
            esc_html__('To identify the causes of hair loss and scalp issues at EUROHAIRLAB, a thorough and accurate diagnosis is essential.', 'eurohairlab'),
            esc_html__('While some people are familiar with their scalp condition, such as whether it is dry or oily, many are not aware of their scalp\'s actual state.', 'eurohairlab'),
            esc_html__('At EUROHAIRLAB, we conduct an 11-step precision diagnosis to thoroughly examine the scalp and hair condition.', 'eurohairlab'),
            esc_html__('Through this process, we analyze hair loss causes, scalp keratin, hair density, hair thickness, heavy metals in hair, scalp oil and moisture balance, and scalp toxins, among other factors.', 'eurohairlab'),
            esc_html__('Based on these results, we design a personalized 1:1 care program. If you are currently concerned about hair loss or scalp issues, visit EUROHAIRLAB now.', 'eurohairlab'),
        ] as $p
    ) {
        $why_body_html .= '<p>' . $p . '</p>';
    }
}

$symptoms_heading_html = $dx_str('eh_diagnosis_symptoms_heading', '');
if ($symptoms_heading_html === '') {
    $symptoms_heading_html = '<p>' . esc_html__('If you experience any of these symptoms,', 'eurohairlab');
    $symptoms_heading_html .= ' <br class="hidden sm:inline" /> ';
    $symptoms_heading_html .= esc_html__(' it\'s recommended to get a ', 'eurohairlab');
    $symptoms_heading_html .= '<span class="underline decoration-2 underline-offset-4">' . esc_html__('scalp diagnosis at EUROHAIRLAB', 'eurohairlab') . '</span></p>';
}

$symptom_lines = $dx_meta('eh_diagnosis_symptom_lines');
if (!is_array($symptom_lines) || $symptom_lines === []) {
    $symptom_lines = [
        'Sudden pimples or inflammation on the scalp',
        'Increased hair loss during shampooing.',
        'Scalp remains itchy and oily even after washing.',
        'Increased dandruff.',
        'Hair becomes thinner, and falls out easily when touched.',
        'Frequent perming or dyeing.',
        'Excessive use of hair products like wax or spray.',
        'Experiencing stress from social interactions, studies, or work.',
        'Excessive scalp sweating.',
        'Scalp pain when touched.',
        'Tingling scalp and stiffness in the back of the neck.',
    ];
}

$step_png_cycle = [
    'scalp-diagnosis-1.png',
    'scalp-diagnosis-2.png',
    'scalp-diagnosis-3.png',
    'scalp-diagnosis-4.png',
    'scalp-diagnosis-5.png',
];

$steps_meta_default = [
    ['title' => 'Scalp Diagnosis', 'description' => 'We use various lenses to analyze scalp issues that are not visible to the naked eye in detail.'],
    ['title' => 'Scalp Type Diagnosis', 'description' => 'We check your scalp condition, compare it with photo data, and guide you on the current state.'],
    ['title' => 'Hair Density Check', 'description' => 'We check the number of hairs per pore, from the early stages of hair loss to the advanced stages, with precision.'],
    ['title' => 'Dead Skin Cell Condition', 'description' => 'We check the number of hairs per pore, from the early stages of hair loss to the advanced stages, with precision.'],
    ['title' => 'Scalp Trouble', 'description' => 'We check the number of hairs per pore, from the early stages of hair loss to the advanced stages, with precision.'],
    ['title' => 'Sebum Regulation', 'description' => 'We assess how much oil is being produced and how it affects the scalp surface condition.'],
    ['title' => 'Scalp Micro Inflammation', 'description' => 'We look for subtle inflammation that may be contributing to discomfort and shedding.'],
    ['title' => 'Keratin Build Up', 'description' => 'We check for excess keratin and debris that can affect scalp balance and follicle health.'],
    ['title' => 'Hair Density Check', 'description' => 'We compare density across the scalp to identify thinning patterns and progression.'],
    ['title' => 'Follicle Activity', 'description' => 'We evaluate active and dormant follicles to understand the current growth condition.'],
    ['title' => 'Diagnosis Summary', 'description' => 'We summarize all findings into a personalized care plan and next-step recommendation.'],
];

$steps_images = $dx_meta('eh_diagnosis_steps_images');
$steps_titles = $dx_meta('eh_diagnosis_steps_titles');
$steps_descriptions = $dx_meta('eh_diagnosis_steps_descriptions');
$legacy_steps_rows = $dx_meta('eh_diagnosis_steps');
$diagnosis_steps = [];
if (
    is_array($steps_images) && $steps_images !== []
    || is_array($steps_titles) && $steps_titles !== []
    || is_array($steps_descriptions) && $steps_descriptions !== []
) {
    $steps_images_list = is_array($steps_images) ? array_values($steps_images) : [];
    $steps_titles_list = is_array($steps_titles) ? array_values($steps_titles) : [];
    $steps_descriptions_list = is_array($steps_descriptions) ? array_values($steps_descriptions) : [];

    $steps_count = max(
        count($steps_images_list),
        count($steps_titles_list),
        count($steps_descriptions_list),
        count($steps_meta_default)
    );

    for ($index = 0; $index < $steps_count; $index++) {
        $img_url = $resolve_step_image_url($steps_images_list[$index] ?? null, '');
        if ($img_url === '') {
            $file = $step_png_cycle[$index % count($step_png_cycle)];
            $img_url = eurohairlab_diagnosis_resolve_asset($file, $figma_uri . '/scalp-diagnosis-3.png');
        }
        $meta = $steps_meta_default[$index] ?? end($steps_meta_default);
        $diagnosis_steps[] = [
            'step' => sprintf(__('Step %d', 'eurohairlab'), $index + 1),
            'title' => trim((string) ($steps_titles_list[$index] ?? '')) !== '' ? (string) $steps_titles_list[$index] : (string) ($meta['title'] ?? ''),
            'description' => trim((string) ($steps_descriptions_list[$index] ?? '')) !== '' ? (string) $steps_descriptions_list[$index] : (string) ($meta['description'] ?? ''),
            'image' => $img_url,
        ];
    }
} elseif (is_array($legacy_steps_rows) && $legacy_steps_rows !== []) {
    foreach ($legacy_steps_rows as $index => $row) {
        if (is_string($row) && $row !== '') {
            $decoded = json_decode($row, true);
            if (is_array($decoded)) {
                $row = $decoded;
            }
        }
        if (!is_array($row)) {
            continue;
        }
        $img_url = $resolve_step_image_url($row['step_image'] ?? null, '');
        if ($img_url === '') {
            $file = $step_png_cycle[$index % count($step_png_cycle)];
            $img_url = eurohairlab_diagnosis_resolve_asset($file, $figma_uri . '/scalp-diagnosis-3.png');
        }
        $diagnosis_steps[] = [
            'step' => sprintf(__('Step %d', 'eurohairlab'), $index + 1),
            'title' => (string) ($row['step_title'] ?? ''),
            'description' => (string) ($row['step_description'] ?? ''),
            'image' => $img_url,
        ];
    }
} else {
    foreach ($steps_meta_default as $index => $meta) {
        $file = $step_png_cycle[$index % count($step_png_cycle)];
        $diagnosis_steps[] = array_merge($meta, [
            'step' => sprintf(__('Step %d', 'eurohairlab'), $index + 1),
            'image' => eurohairlab_diagnosis_resolve_asset($file, $figma_uri . '/scalp-diagnosis-3.png'),
        ]);
    }
}

$steps_heading = $dx_str('eh_diagnosis_steps_heading', esc_html__('11-step precision diagnosis', 'eurohairlab'));

$analysis_title = $dx_str('eh_diagnosis_analysis_title', esc_html__('Analysis and Management of Hair Loss Causes', 'eurohairlab'));
$analysis_intro_html = $dx_str('eh_diagnosis_analysis_intro', '');
if ($analysis_intro_html === '') {
    $analysis_intro_html = '<p>' . esc_html__('The causes of hair loss are quite diverse, and in many cases, hair loss occurs due to a combination of factors. It is important to accurately analyze the causes of hair loss, such as stress, genetics, dieting, postpartum conditions, fine dust, poor dietary habits, scalp issues, or chemical damage from perms or dyeing, and provide tailored care for each type.', 'eurohairlab') . '</p>';
}

$pairs_rows = $dx_meta('eh_diagnosis_analysis_labels');
$cause_items = [];
if (is_array($pairs_rows) && $pairs_rows !== []) {
    foreach ($pairs_rows as $row) {
        if (is_string($row)) {
            $label = trim($row);
            if ($label !== '') {
                $cause_items[] = ['label' => $label];
            }
            continue;
        }

        if (is_array($row)) {
            $legacy_label = trim((string) ($row['pair_label'] ?? ''));
            if ($legacy_label !== '') {
                $cause_items[] = ['label' => $legacy_label];
            }
        }
    }
}
if ($cause_items === []) {
    $legacy_pairs_rows = $dx_meta('eh_diagnosis_analysis_pairs');
    if (is_array($legacy_pairs_rows) && $legacy_pairs_rows !== []) {
        foreach ($legacy_pairs_rows as $row) {
            if (is_string($row) && $row !== '') {
                $decoded = json_decode($row, true);
                if (is_array($decoded)) {
                    $row = $decoded;
                }
            }
            if (!is_array($row)) {
                continue;
            }

            $legacy_label = trim((string) ($row['pair_label'] ?? ''));
            if ($legacy_label !== '') {
                $cause_items[] = ['label' => $legacy_label];
            }
        }
    }
}
if ($cause_items === []) {
    $cause_items = [
        ['label' => 'Stress'],
        ['label' => 'Genetics'],
        ['label' => 'Dieting'],
        ['label' => 'Postpartum'],
        ['label' => 'Poor dietary habits'],
        ['label' => 'Scalp problems'],
        ['label' => 'Perming, Dyeing'],
        ['label' => 'Fine dust'],
    ];
}

$delivers_title = $dx_str('eh_diagnosis_delivers_title', esc_html__('EUROHAIRLAB Delivers Results', 'eurohairlab'));
$delivers_intro_html = $dx_str('eh_diagnosis_delivers_intro', '');
if ($delivers_intro_html === '') {
    $delivers_intro_html = '<p>' . esc_html__('It\'s difficult to talk about your hair loss worries to anyone! We strive to offer services that will not only care for your scalp health but also bring comfort to your body and mind.', 'eurohairlab') . '</p>';
}

$deliver_images = $dx_meta('eh_diagnosis_delivers_images');
$deliver_titles = $dx_meta('eh_diagnosis_delivers_titles');
$deliver_descriptions = $dx_meta('eh_diagnosis_delivers_descriptions');
$deliver_rows = $dx_meta('eh_diagnosis_delivers_cards');
$result_highlights = [];
if (
    (is_array($deliver_images) && $deliver_images !== [])
    || (is_array($deliver_titles) && $deliver_titles !== [])
    || (is_array($deliver_descriptions) && $deliver_descriptions !== [])
) {
    $deliver_images_list = is_array($deliver_images) ? array_values($deliver_images) : [];
    $deliver_titles_list = is_array($deliver_titles) ? array_values($deliver_titles) : [];
    $deliver_descriptions_list = is_array($deliver_descriptions) ? array_values($deliver_descriptions) : [];
    $deliver_count = max(count($deliver_images_list), count($deliver_titles_list), count($deliver_descriptions_list));
    for ($index = 0; $index < $deliver_count; $index++) {
        $img_url = eurohairlab_mb_image_url($deliver_images_list[$index] ?? null, '');
        if ($img_url === '') {
            $fallbacks = [
                'result-effective-care.png',
                'result-safety.png',
                'result-personalized.png',
            ];
            $fallbackFile = $fallbacks[$index] ?? 'result-effective-care.png';
            $figmaFallbacks = [
                $figma_uri . '/delivery-results-1.png',
                $figma_uri . '/delivery-results-2.png',
                $figma_uri . '/delivery-results-3.png',
            ];
            $img_url = eurohairlab_diagnosis_resolve_asset($fallbackFile, $figmaFallbacks[$index] ?? $figma_uri . '/delivery-results-1.png');
        }
        $result_highlights[] = [
            'title' => trim((string) ($deliver_titles_list[$index] ?? '')),
            'image' => $img_url,
            'description' => trim((string) ($deliver_descriptions_list[$index] ?? '')),
        ];
    }
} elseif (is_array($deliver_rows) && $deliver_rows !== []) {
    foreach ($deliver_rows as $row) {
        if (is_string($row) && $row !== '') {
            $decoded = json_decode($row, true);
            if (is_array($decoded)) {
                $row = $decoded;
            }
        }
        if (!is_array($row)) {
            continue;
        }
        $img_url = eurohairlab_mb_image_url($row['card_image'] ?? null, '');
        if ($img_url === '') {
            $img_url = eurohairlab_diagnosis_resolve_asset('result-effective-care.png', $figma_uri . '/delivery-results-1.png');
        }
        $result_highlights[] = [
            'title' => (string) ($row['card_title'] ?? ''),
            'image' => $img_url,
            'description' => (string) ($row['card_description'] ?? ''),
        ];
    }
}
if ($result_highlights === []) {
    $result_highlights = [
        [
            'title' => 'Effective Care',
            'image' => eurohairlab_diagnosis_resolve_asset('result-effective-care.png', $figma_uri . '/delivery-results-1.png'),
            'description' => 'With over 17 years of clinical data from universities and field studies, we use the most effective care methods and products based on each customer\'s scalp type.',
        ],
        [
            'title' => 'Safety',
            'image' => eurohairlab_diagnosis_resolve_asset('result-safety.png', $figma_uri . '/delivery-results-2.png'),
            'description' => 'We ensure safety and trust by using specialized products that are clinically proven to be harmless to the scalp in medical universities and research centers in the U.S., Germany, and Italy, along with our own developed products.',
        ],
        [
            'title' => 'Personalized Care Service',
            'image' => eurohairlab_diagnosis_resolve_asset('result-personalized.png', $figma_uri . '/delivery-results-3.png'),
            'description' => 'Based on 17 years of accumulated clinical data, we provide tailored care according to each customer\'s scalp condition, effectively addressing a variety of concerns.',
        ],
    ];
}

$cta_bg_image = eurohairlab_mb_image_url($dx_meta('eh_diagnosis_free_scalp_image'), eurohairlab_diagnosis_resolve_asset('cta-bg.png', $hero_fallback));
$free_scalp_title = $dx_str('eh_diagnosis_free_scalp_title', esc_html__('Free Scalp Diagnosis by Experts', 'eurohairlab'));
$free_scalp_paragraph_html = $dx_str('eh_diagnosis_free_scalp_paragraph', '');
if ($free_scalp_paragraph_html === '') {
    $free_scalp_paragraph_html = '<p>' . esc_html__('Diagnosing scalp condition, sebum levels, keratin, pore status, hair loss type, hair density, damage level, and thickness.', 'eurohairlab') . '</p>';
}
$free_scalp_button_label = $dx_str('eh_diagnosis_free_scalp_button_label', esc_html__('Start Your Free Scalp Analysis', 'eurohairlab'));

$diagnosis_bottom_cta_href_raw = '';
if ($page_id && function_exists('rwmb_meta')) {
    $diagnosis_bottom_cta_href_raw = (string) rwmb_meta('eh_diagnosis_bottom_cta_button_href', [], $page_id);
}
$free_scalp_href_raw = $dx_str('eh_diagnosis_free_scalp_button_href', '');
if (trim($free_scalp_href_raw) === '') {
    $free_scalp_href_raw = $diagnosis_bottom_cta_href_raw;
}
$diagnosis_bottom_cta_url = eurohairlab_resolve_free_scalp_analysis_href($free_scalp_href_raw);
$diagnosis_bottom_cta_link_attrs = trim($free_scalp_href_raw) === ''
    ? eurohairlab_free_scalp_analysis_link_attributes($diagnosis_bottom_cta_url)
    : '';

$page_url = get_permalink() ?: home_url('/diagnosis/');
$page_title = wp_get_document_title();
$page_desc = function_exists('eurohairlab_get_meta_description') ? eurohairlab_get_meta_description() : '';

$schema = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebPage',
            '@id' => $page_url . '#webpage',
            'url' => $page_url,
            'name' => $page_title,
            'description' => $page_desc,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
            ],
            'about' => [
                '@type' => 'Thing',
                'name' => 'Scalp diagnosis and hair loss assessment',
            ],
        ],
        [
            '@type' => 'BreadcrumbList',
            '@id' => $page_url . '#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => home_url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Diagnosis',
                    'item' => $page_url,
                ],
            ],
        ],
    ],
];
?>
<main id="main-content" class="bg-white text-eh-ink antialiased">
  <section class="relative isolate overflow-hidden bg-eh-panel pt-[125px]" aria-labelledby="diagnosis-hero-heading">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <img
        src="<?php echo esc_url($hero_image); ?>"
        alt=""
        class="h-full min-h-[503px] w-full object-cover object-center"
        width="1920"
        height="540"
        fetchpriority="high"
        decoding="async"
      >
      <?php if ($overlay_class !== '') : ?>
        <div class="absolute inset-0 <?php echo esc_attr($overlay_class); ?>"></div>
      <?php endif; ?>
    </div>

    <div class="relative mx-auto flex min-h-[503px] w-full max-w-[90rem] items-center justify-center px-4 py-16 sm:px-6 lg:px-10">
      <div class="reveal reveal--hero max-w-[61rem] text-center">
        <h1 id="diagnosis-hero-heading" class="font-display text-[2rem] font-bold capitalize leading-none text-white">
          <?php echo nl2br(esc_html($hero_title)); ?>
        </h1>
        <div class="mx-auto mt-6 max-w-[61rem] text-center font-futuraBk text-[14px] font-normal leading-[1] text-white">
          <?php echo wp_kses_post($hero_paragraph_html); ?>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-white py-16 lg:py-24" aria-labelledby="diagnosis-intro-heading">
    <div class="mx-auto grid w-full max-w-[90rem] gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,567px)_minmax(0,1fr)] lg:items-stretch lg:gap-16 lg:px-10">
      <figure class="reveal min-h-0 overflow-hidden bg-eh-panel lg:flex lg:h-full lg:min-h-0 lg:max-w-[567px] lg:flex-col">
        <img
          src="<?php echo esc_url($intro_image); ?>"
          alt="<?php echo esc_attr($why_alt); ?>"
          class="aspect-square h-auto w-full object-cover object-center lg:aspect-auto lg:min-h-0 lg:w-full lg:flex-1 lg:object-cover"
          width="800"
          height="800"
          fetchpriority="high"
          decoding="async"
        >
      </figure>

      <div class="reveal eh-diagnosis-intro max-w-[36rem] min-h-0 lg:max-w-none lg:pl-2 flex flex-col justify-center">
        <p class="eh-diagnosis-intro__kicker">
          <?php echo esc_html($why_kicker); ?>
        </p>
        <h2 id="diagnosis-intro-heading" class="eh-diagnosis-intro__title">
          <?php echo nl2br(esc_html($why_title)); ?>
        </h2>
        <div class="eh-diagnosis-intro__body">
          <?php echo wp_kses_post($why_body_html); ?>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-eh-panel px-4 py-14 sm:px-6 lg:px-10 lg:py-24" aria-labelledby="diagnosis-symptoms-heading">
    <div class="mx-auto w-full max-w-[90rem]">
      <div id="diagnosis-symptoms-heading" class="reveal mx-auto max-w-[61rem] text-center font-futuraHv text-[24px] font-normal leading-none text-eh-ink" role="heading" aria-level="2">
        <?php echo wp_kses_post($symptoms_heading_html); ?>
      </div>

      <ul class="mt-10 grid list-none gap-4 p-0 md:grid-cols-2 xl:grid-cols-3" role="list">
        <?php foreach ($symptom_lines as $symptom) : ?>
          <?php
          if (!is_string($symptom) || trim($symptom) === '') {
              continue;
          }
          ?>
          <li class="reveal flex min-h-[4.25rem] items-start gap-4 rounded-[5px] bg-white px-5 py-4 items-center ">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center text-eh-ink" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="39" height="39" viewBox="0 0 39 39" fill="none" class="h-9 w-9" focusable="false">
                <path d="M15.5187 24.6188L29.2906 10.8469C29.6156 10.5219 29.9948 10.3594 30.4281 10.3594C30.8614 10.3594 31.2406 10.5219 31.5656 10.8469C31.8906 11.1719 32.0531 11.5581 32.0531 12.0055C32.0531 12.4529 31.8906 12.8386 31.5656 13.1625L16.6562 28.1125C16.3312 28.4375 15.9521 28.6 15.5187 28.6C15.0854 28.6 14.7062 28.4375 14.3812 28.1125L7.39374 21.125C7.06874 20.8 6.91274 20.4143 6.92574 19.968C6.93874 19.5217 7.10828 19.1355 7.43436 18.8094C7.76045 18.4833 8.14665 18.3208 8.59299 18.3219C9.03932 18.323 9.42499 18.4855 9.74999 18.8094L15.5187 24.6188Z" fill="currentColor"/>
              </svg>
            </span>
            <span class="font-futuraBk pt-0.5 text-[18px] font-normal leading-[120%] text-eh-ink"><?php echo esc_html($symptom); ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="bg-white px-4 py-14 sm:px-6 lg:px-10 lg:py-16" aria-labelledby="diagnosis-steps-heading">
    <div class="mx-auto w-full max-w-[90rem]">
      <h2 id="diagnosis-steps-heading" class="reveal text-center font-futuraHv text-[24px] font-normal leading-none text-eh-ink">
        <?php echo esc_html($steps_heading); ?>
      </h2>

      <div class="diagnosis-steps-shell diagnosis-steps-shell--fullbleed reveal mt-8 lg:mt-10">
        <div class="diagnosis-steps-rail" aria-hidden="true"></div>
        <div
          class="diagnosis-steps-slider diagnosis-steps-slider--wide"
          data-diagnosis-steps-slider
          role="region"
          aria-roledescription="<?php echo esc_attr__('carousel', 'eurohairlab'); ?>"
          aria-label="<?php echo esc_attr__('Eleven-step precision scalp diagnosis overview', 'eurohairlab'); ?>"
        >
          <?php foreach ($diagnosis_steps as $index => $step) : ?>
            <div class="diagnosis-step-slide">
              <article class="h-full bg-white text-center" aria-label="<?php echo esc_attr($step['step'] . ': ' . $step['title']); ?>">
                <span class="diagnosis-step-card__dot" aria-hidden="true"></span>
                <img
                  src="<?php echo esc_url($step['image']); ?>"
                  alt="<?php echo esc_attr($step['title']); ?>"
                  class="aspect-[280/192] w-full rounded-[10px] object-cover object-center"
                  width="280"
                  height="192"
                  loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                  decoding="async"
                >
                <div class="px-1 pt-4 lg:px-2">
                  <p class="font-futuraHv text-[18px] font-normal leading-none text-eh-coral"><?php echo esc_html($step['step']); ?></p>
                  <h3 class="mt-2 font-futuraHv text-[24px] font-normal leading-none text-black"><?php echo esc_html($step['title']); ?></h3>
                  <div class="mx-auto mt-4 h-px w-full max-w-[17.5rem] bg-eh-muted/60" aria-hidden="true"></div>
                  <p class="mx-auto mt-4 max-w-[17.5rem] font-futuraBk text-[14px] font-normal leading-[1] text-eh-muted">
                    <?php echo esc_html($step['description']); ?>
                  </p>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-eh-panel px-4 py-14 sm:px-6 lg:px-10 lg:py-24" aria-labelledby="diagnosis-causes-heading">
    <div class="mx-auto w-full max-w-[90rem]">
      <h2 id="diagnosis-causes-heading" class="reveal mx-auto max-w-[48rem] text-center font-futuraHv text-[24px] font-normal leading-none text-eh-ink">
        <?php echo esc_html($analysis_title); ?>
      </h2>
      <div class="reveal mx-auto mt-4 max-w-[52rem] text-center font-futuraBk text-[14px] font-normal leading-[120%] text-eh-muted">
        <?php echo wp_kses_post($analysis_intro_html); ?>
      </div>

      <ol class="mt-10 grid list-none gap-4 p-0 md:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($cause_items as $index => $item) : ?>
          <li class="reveal flex min-h-16 items-center gap-4 rounded-[5px] bg-white px-5 py-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center text-center font-futuraHv text-[24px] font-normal leading-none text-eh-sand-num" aria-hidden="true"><?php echo esc_html((string) ($index + 1)); ?></span>
            <span class="font-futuraBk text-[18px] font-normal leading-[120%] text-eh-ink"><?php echo esc_html($item['label']); ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <section class="bg-white px-4 py-14 sm:px-6 lg:px-10 lg:py-16" aria-labelledby="diagnosis-results-heading">
    <div class="mx-auto w-full max-w-[90rem]">
      <header class="reveal text-center">
        <h2 id="diagnosis-results-heading" class="font-futuraHv text-[24px] font-normal leading-none text-eh-ink">
          <?php echo esc_html($delivers_title); ?>
        </h2>
        <div class="mx-auto mt-4 max-w-[36rem] font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink">
          <?php echo wp_kses_post($delivers_intro_html); ?>
        </div>
      </header>

      <div class="mt-10 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($result_highlights as $highlight) : ?>
          <article class="reveal flex flex-col overflow-hidden bg-white">
            <img
              src="<?php echo esc_url($highlight['image']); ?>"
              alt="<?php echo esc_attr($highlight['title']); ?>"
              class="aspect-[470/323] w-full object-cover object-center"
              width="470"
              height="323"
              loading="lazy"
              decoding="async"
            >
            <div class="flex flex-1 flex-col px-6 py-6 text-center sm:px-7 sm:py-7">
              <h3 class="font-futuraHv text-[24px] font-normal leading-none text-black"><?php echo esc_html($highlight['title']); ?></h3>
              <p class="mt-4 font-futuraBk text-[14px] font-normal leading-[1] text-eh-muted">
                <?php echo esc_html($highlight['description']); ?>
              </p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="relative overflow-hidden bg-black text-white" aria-labelledby="diagnosis-cta-heading">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <img
        src="<?php echo esc_url($cta_bg_image); ?>"
        alt=""
        class="h-full w-full object-cover object-center"
        width="1920"
        height="500"
        loading="lazy"
        decoding="async"
      >
    </div>

    <div class="relative mx-auto w-full max-w-[90rem] px-4 py-16 sm:px-6 lg:px-10 lg:py-20">
      <div class="reveal reveal--hero mx-auto max-w-[61rem] text-center">
        <h2 id="diagnosis-cta-heading" class="text-center font-futuraHv text-[2rem] font-normal capitalize leading-none text-white">
          <?php echo nl2br(esc_html($free_scalp_title)); ?>
        </h2>
        <div class="mx-auto mt-5 max-w-[60rem] text-center font-futuraBk text-[14px] font-normal leading-[1] text-white">
          <?php echo wp_kses_post($free_scalp_paragraph_html); ?>
        </div>
        <a
          href="<?php echo esc_url($diagnosis_bottom_cta_url); ?>"
          class="mt-8 inline-flex min-h-11 items-center justify-center rounded-sm border border-white px-7 py-3 text-center font-futuraBk text-[14px] font-normal capitalize leading-[1] text-white transition hover:bg-white hover:text-eh-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
          <?php echo $diagnosis_bottom_cta_link_attrs; ?>
        >
          <?php echo esc_html($free_scalp_button_label); ?>
        </a>
      </div>
    </div>
  </section>

  <script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
</main>
