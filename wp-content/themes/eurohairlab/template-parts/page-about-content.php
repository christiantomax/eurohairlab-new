<?php

declare(strict_types=1);

$theme_uri = esc_url(get_template_directory_uri());
$figma_uri = $theme_uri . '/assets/images/figma';
$page_id = get_queried_object_id();
$mb_get = static function (string $key) use ($page_id) {
    if (!$page_id || !function_exists('rwmb_meta')) {
        return null;
    }

    return rwmb_meta($key, [], $page_id);
};
$resolve_image = static function ($value, string $fallback = ''): string {
    if (is_string($value) && $value !== '') {
        return $value;
    }

    if (is_numeric($value)) {
        $attachment_url = wp_get_attachment_image_url((int) $value, 'full');
        return is_string($attachment_url) && $attachment_url !== '' ? $attachment_url : $fallback;
    }

    if (is_array($value)) {
        if (isset($value['ID']) && is_numeric($value['ID'])) {
            $attachment_url = wp_get_attachment_image_url((int) $value['ID'], 'full');
            if (is_string($attachment_url) && $attachment_url !== '') {
                return $attachment_url;
            }
        }

        if (isset($value['full_url']) && is_string($value['full_url'])) {
            return $value['full_url'];
        }

        if (isset($value['url']) && is_string($value['url'])) {
            return $value['url'];
        }

        if (isset($value[0]) && is_array($value[0])) {
            return $value[0]['full_url'] ?? $value[0]['url'] ?? $fallback;
        }
    }

    return $fallback;
};
$collect_image_urls = static function ($value) use (&$collect_image_urls, $resolve_image): array {
    if ($value === null || $value === '') {
        return [];
    }

    if (is_string($value) || is_numeric($value)) {
        $resolved = $resolve_image($value, '');
        return $resolved !== '' ? [$resolved] : [];
    }

    if (!is_array($value)) {
        return [];
    }

    if (isset($value['ID']) || isset($value['full_url']) || isset($value['url'])) {
        $resolved = $resolve_image($value, '');
        return $resolved !== '' ? [$resolved] : [];
    }

    $urls = [];
    foreach ($value as $item) {
        foreach ($collect_image_urls($item) as $url) {
            if ($url !== '') {
                $urls[] = $url;
            }
        }
    }

    return array_values(array_unique($urls));
};

$hero_default = [
    'image' => $figma_uri . '/about-hero.webp',
    'title' => "The 1st Korean Scalp\nClinic In Jakarta",
    'body_text' => '',
];
$foundation_default = [
    'kicker' => 'Our Foundation',
    'title' => 'World-Proven Built for You',
    'body_text' => 'EUROHAIRLAB is the authorised Indonesian franchisee of DR.SCALP Korea, a global scalp care institution with over 17 years of clinical experience, more than 3 million patients treated, 360 clinics across 20+ countries.',
    'image_left' => $figma_uri . '/about-story-main.webp',
    'image_right' => $figma_uri . '/about-story-side.webp',
];
$science_default = [
    'kicker' => 'Korean Scalp Science',
    'title' => 'ScalpFirst™',
    'body_text' => "ScalpFirst™ is EUROHAIRLAB's structured approach that places the scalp at the centre of every treatment decision. Every step is guided by what your diagnostic assessment reveals.\n\nAt EUROHAIRLAB, every decision follows this principle. Assessment before recommendation. Diagnosis before treatment.",
    'image' => $figma_uri . '/about-korean-science.webp',
];
$partnership_default = [
    'kicker' => 'The DR.SCALP Korea Partnership',
    'title' => 'Guided By Experts',
    'members' => [
        [
            'name' => 'Eliza Ennio Gunawan M',
            'title' => 'Dokter Spesialis',
            'bio' => "EUROHAIRLAB Is Administered By Licensed Medical Doctors With Dedicated Training In Scalp Medicine.\n\nThe Expertise Is Global.\n\nThe Care Is Personal.",
            'image' => $theme_uri . '/assets/about-partnership-team.webp',
        ],
    ],
];
$technology_slides_default = [
    [
        'title' => 'Scalp Imaging System',
        'description' => 'At Eurohairlab, every treatment begins with precise clinical analysis. We combine modern diagnostic tools, scalp imaging technology, and regenerative treatment platforms to accurately identify the root cause of hair loss and deliver targeted solutions.',
        'image' => $figma_uri . '/about-technology.webp',
        'alt' => 'Clinical scalp imaging consultation in progress',
    ],
    [
        'title' => 'Density Mapping Review',
        'description' => 'High-visibility imaging helps our team compare scalp zones, monitor density shifts, and define treatment priorities with less guesswork.',
        'image' => $figma_uri . '/diagnosis-density.webp',
        'alt' => 'Hair density review visual',
    ],
    [
        'title' => 'Follicle Condition Check',
        'description' => 'Close scalp review supports a more accurate understanding of follicle behavior, scalp sensitivity, and the condition behind visible thinning.',
        'image' => $figma_uri . '/diagnosis-hero.webp',
        'alt' => 'Specialist performing a follicle condition check',
    ],
    [
        'title' => 'Structured Diagnostic Support',
        'description' => 'Each consultation combines observation, device-assisted review, and symptom history so treatment recommendations are based on evidence.',
        'image' => $figma_uri . '/diagnosis-intro.webp',
        'alt' => 'Structured diagnostic support during consultation',
    ],
    [
        'title' => 'Targeted Treatment Planning',
        'description' => 'Once the scalp condition is defined, we build a treatment roadmap that aligns in-clinic procedures with realistic recovery milestones.',
        'image' => $figma_uri . '/treatment-technology.webp',
        'alt' => 'Treatment planning in a clinical setting',
    ],
];
$premium_default = [
    'kicker' => 'Premium Clinic Experience',
    'title' => 'Designed For Comfort And Privacy',
    'slides' => [
        [
            'image' => $figma_uri . '/about-privacy-1.webp',
            'alt' => 'Exterior view of the Eurohairlab clinic',
            'title' => '',
            'description' => '',
        ],
        [
            'image' => $figma_uri . '/about-privacy-2.webp',
            'alt' => 'Reception and waiting room inside Eurohairlab',
            'title' => '',
            'description' => '',
        ],
        [
            'image' => $figma_uri . '/about-privacy-3.webp',
            'alt' => 'Private consultation room at Eurohairlab',
            'title' => '',
            'description' => '',
        ],
    ],
];

$hero = array_merge($hero_default, array_filter([
    'title' => $mb_get('eh_about_hero_title'),
    'body_text' => $mb_get('eh_about_hero_body_text'),
], static fn($value) => $value !== null && $value !== ''));
$hero['image'] = $resolve_image($mb_get('eh_about_hero_image'), $hero_default['image']);

$foundation = array_merge($foundation_default, array_filter([
    'kicker' => $mb_get('eh_about_foundation_kicker'),
    'title' => $mb_get('eh_about_foundation_title'),
    'body_text' => $mb_get('eh_about_foundation_body_text'),
], static fn($value) => $value !== null && $value !== ''));
$foundation['image_left'] = $resolve_image($mb_get('eh_about_foundation_image_left'), $foundation_default['image_left']);
$foundation['image_right'] = $resolve_image($mb_get('eh_about_foundation_image_right'), $foundation_default['image_right']);

$science = array_merge($science_default, array_filter([
    'kicker' => $mb_get('eh_about_science_kicker'),
    'title' => $mb_get('eh_about_science_title'),
    'body_text' => $mb_get('eh_about_science_body_text'),
], static fn($value) => $value !== null && $value !== ''));
$science['image'] = $resolve_image($mb_get('eh_about_science_image'), $science_default['image']);

$partnership = array_merge($partnership_default, array_filter([
    'kicker' => $mb_get('eh_about_partnership_kicker'),
    'title' => $mb_get('eh_about_partnership_title'),
], static fn($value) => $value !== null && $value !== ''));
$partnership_names = $mb_get('eh_about_partnership_member_names');
$partnership_titles = $mb_get('eh_about_partnership_member_titles');
$partnership_bios = $mb_get('eh_about_partnership_member_bios');
$partnership_images = $collect_image_urls($mb_get('eh_about_partnership_member_images'));
$partnership_hover_images = $collect_image_urls($mb_get('eh_about_partnership_member_hover_images'));
if (is_array($partnership_names) && !empty($partnership_names)) {
    $partnership['members'] = array_values(array_filter(array_map(
        static function ($name, int $index) use ($resolve_image, $partnership_titles, $partnership_bios, $partnership_images, $partnership_hover_images) {
            if (empty($name)) {
                return null;
            }

            return [
                'name' => (string) $name,
                'title' => (string) (is_array($partnership_titles) ? ($partnership_titles[$index] ?? '') : ''),
                'bio' => (string) (is_array($partnership_bios) ? ($partnership_bios[$index] ?? '') : ''),
                'image' => (string) ($partnership_images[$index] ?? ''),
                'hover_image' => (string) ($partnership_hover_images[$index] ?? ($partnership_images[$index] ?? '')),
            ];
        },
        $partnership_names,
        array_keys($partnership_names)
    )));
    if (empty($partnership['members'])) {
        $partnership['members'] = $partnership_default['members'];
    }
}
$active_partnership_member = $partnership['members'][0] ?? $partnership_default['members'][0];

$clinical = [
    'kicker' => (string) ($mb_get('eh_about_clinical_kicker') ?: 'Clinical Technology'),
    'title' => (string) ($mb_get('eh_about_clinical_title') ?: 'Precision Technology for Your Condition'),
    'body_text' => (string) ($mb_get('eh_about_clinical_body_text') ?: 'Every technology at EUROHAIRLAB is sourced from DR.SCALP Korea\'s clinical platform. We combine modern diagnostic tools, scalp imaging technology, and regenerative treatment platforms to accurately identify the root cause of hair loss and deliver targeted solutions.'),
];
$technology_slides = $technology_slides_default;
$clinical_titles = $mb_get('eh_about_clinical_slide_titles');
$clinical_descriptions = $mb_get('eh_about_clinical_slide_descriptions');
$clinical_images = array_values((array) $mb_get('eh_about_clinical_slide_images'));
if (is_array($clinical_titles) && !empty($clinical_titles)) {
    $technology_slides = array_values(array_filter(array_map(
        static function ($title, int $index) use ($resolve_image, $clinical_descriptions, $clinical_images) {
            if (empty($title)) {
                return null;
            }

            return [
                'title' => (string) $title,
                'description' => (string) (is_array($clinical_descriptions) ? ($clinical_descriptions[$index] ?? '') : ''),
                'image' => $resolve_image($clinical_images[$index] ?? [], ''),
                'alt' => (string) $title,
            ];
        },
        $clinical_titles,
        array_keys($clinical_titles)
    )));
    if (empty($technology_slides)) {
        $technology_slides = $technology_slides_default;
    }
}

$premium = [
    'kicker' => (string) ($mb_get('eh_about_premium_kicker') ?: $premium_default['kicker']),
    'title' => (string) ($mb_get('eh_about_premium_title') ?: $premium_default['title']),
];
$privacy_slides = $premium_default['slides'];
$premium_titles = $mb_get('eh_about_premium_slide_titles');
$premium_descriptions = $mb_get('eh_about_premium_slide_descriptions');
$premium_images = array_values((array) $mb_get('eh_about_premium_slide_images'));
if (is_array($premium_images) && !empty($premium_images)) {
    $privacy_slides = array_values(array_filter(array_map(
        static function ($image, int $index) use ($resolve_image, $premium_titles, $premium_descriptions, $premium_default) {
            $fallback_slide = $premium_default['slides'][$index] ?? ['alt' => 'Premium clinic experience', 'title' => '', 'description' => '', 'image' => ''];

            return [
                'image' => $resolve_image($image, $fallback_slide['image']),
                'alt' => $fallback_slide['alt'],
                'title' => (string) (is_array($premium_titles) ? ($premium_titles[$index] ?? '') : ''),
                'description' => (string) (is_array($premium_descriptions) ? ($premium_descriptions[$index] ?? '') : ''),
            ];
        },
        $premium_images,
        array_keys($premium_images)
    )));
    if (empty($privacy_slides)) {
        $privacy_slides = $premium_default['slides'];
    }
}
$privacy_slides = array_merge($privacy_slides, $privacy_slides, $privacy_slides);
?>
<main id="main-content" class="bg-white text-ink">
  <section id="about-hero" aria-labelledby="about-hero-heading" data-section="about-hero" class="relative bg-white pb-16 pt-20 lg:pb-40">
    <div class="relative">
      <img
        src="<?php echo esc_url($hero['image']); ?>"
        alt="Close-up portrait of a woman representing healthy hair"
        class="reveal reveal--hero h-72 w-full object-cover object-left lg:object-top sm:h-[30rem] lg:h-[78vh]"
        width="1440"
        height="503"
        fetchpriority="high"
        decoding="async"
      >
      <div class="reveal reveal--hero mx-4 -mt-12 bg-[#d5bb9f] px-6 py-8 sm:mx-5 sm:max-w-[50rem] sm:px-10 sm:py-10 lg:absolute lg:bottom-[-4rem] lg:right-20 lg:mx-0 lg:mt-0 lg:w-fit lg:px-10 lg:py-20 lg:pb-16 xl:right-20">
        <h1 id="about-hero-heading" class="font-heading text-[2rem] font-bold leading-[1] tracking-[-0.03em] text-[#231f20] sm:text-[3.4rem] lg:text-[3rem]">
          <?php echo nl2br(esc_html($hero['title'])); ?>
        </h1>
        <?php if (!empty($hero['body_text'] ?? null)): ?>
          <p class="mt-6 max-w-[27rem] text-base leading-[1.35] text-[#231f20]/92 sm:text-lg lg:mt-8 lg:text-[1.05rem] lg:leading-[1.35]">
            <?php echo esc_html($hero['body_text']); ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section id="about-story" aria-labelledby="about-story-heading" data-section="our-story" class="bg-white lg:pt-20">
    <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-28">
      <p id="about-story-kicker" class="reveal text-[1rem] font-semibold leading-none text-[#dea093] lg:text-3xl"><?php echo esc_html($foundation['kicker']); ?></p>
      <div class="relative mt-3 xl:min-h-[45rem]">
        <div class="reveal">
          <h2 id="about-story-heading" class="font-heading text-[2rem] font-bold leading-[1] text-[#231f20] lg:max-w-[32rem] lg:text-[4rem]">
            <?php echo esc_html($foundation['title']); ?>
          </h2>
        </div>

        <article class="reveal mt-8 xl:max-w-[18rem] xl:pt-6">
          <div class="max-w-[18rem] space-y-7 text-[14px] leading-1 text-[#231f20]/86">
            <?php foreach (preg_split("/\r\n|\n|\r/", (string) $foundation['body_text']) ?: [] as $paragraph) : ?>
              <?php if (trim($paragraph) === '') : ?>
                <?php continue; ?>
              <?php endif; ?>
              <p><?php echo esc_html($paragraph); ?></p>
            <?php endforeach; ?>
          </div>
        </article>

        <div class="mt-10 grid gap-6 sm:grid-cols-[minmax(0,1fr)_18rem] sm:items-start xl:absolute xl:right-0 xl:top-5 xl:mt-0 xl:flex xl:w-[65vw] xl:max-w-[60rem] xl:items-start xl:justify-end xl:gap-4">
          <figure class="reveal h-[20rem] overflow-hidden sm:mt-8 sm:h-[26rem] xl:mt-[10rem] xl:h-[38rem] xl:w-[calc(100%-21rem)] ">
            <img
              src="<?php echo esc_url($foundation['image_left']); ?>"
              alt="Portrait of a woman illustrating Eurohairlab beauty campaign"
              class="h-full w-full object-cover object-center"
              width="470"
              height="497"
              loading="lazy"
              decoding="async"
            >
          </figure>

          <figure class="reveal relative h-[18rem] overflow-hidden sm:h-[22rem] sm:w-full xl:h-[29rem] xl:w-[20rem] xl:flex-none">
            <img
              src="<?php echo esc_url($foundation['image_right']); ?>"
              alt="Video thumbnail of a woman with healthy hair"
              class="h-full w-full object-cover object-center"
              width="331"
              height="497"
              loading="lazy"
              decoding="async"
            >
          </figure>
        </div>
      </div>
    </div>
  </section>

  <section id="korean-hair-science" aria-labelledby="korean-hair-science-heading" data-section="korean-hair-science" class="bg-white my-[4rem] lg:my-0">
    <div class="px-4 pb-14 sm:px-5 sm:pb-16 lg:px-0 lg:pt-32 lg:pb-72">
      <div class="grid items-center gap-10 xl:grid-cols-[minmax(0,40rem)_minmax(0,1fr)] xl:gap-20">
        <figure class="order-2 lg:order-1 reveal relative overflow-hidden">
          <img
            src="<?php echo esc_url($science['image']); ?>"
            alt="Woman holding scalp-care product"
            class="h-full w-full object-cover object-center"
            width="634"
            height="717"
            loading="lazy"
            decoding="async"
          >
        </figure>

        <article class="order-1 lg:order-2 reveal xl:pt-4">
          <p id="korean-hair-science-kicker" class="text-[1rem] font-semibold leading-none text-[#dea093] lg:text-3xl"><?php echo esc_html($science['kicker']); ?></p>
          <h2 id="korean-hair-science-heading" class="font-heading mt-3 text-[2rem] font-bold leading-[1] text-[#231f20] lg:max-w-[15ch] lg:text-[4rem]">
            <?php echo esc_html($science['title']); ?>
          </h2>
          <div class="mt-8 max-w-prose space-y-5 text-[14px] leading-[1] text-[#231f20]/86">
            <?php foreach (preg_split("/\r\n|\n|\r/", (string) $science['body_text']) ?: [] as $paragraph) : ?>
              <?php if (trim($paragraph) === '') : ?>
                <?php continue; ?>
              <?php endif; ?>
              <p><?php echo esc_html($paragraph); ?></p>
            <?php endforeach; ?>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section id="guided-by-experts" aria-labelledby="guided-by-experts-heading" data-section="guided-by-experts" data-about-partnership-section class="bg-white">
    <div>
      <div class="px-4 sm:px-5 lg:px-20">
        <p id="guided-by-experts-kicker" class="text-[1rem] font-semibold leading-none text-[#dea093] lg:text-3xl"><?php echo esc_html($partnership['kicker']); ?></p>
        <h2 id="guided-by-experts-heading" class="font-heading mt-3 text-[2rem] font-bold leading-[1] text-[#231f20] lg:text-[4rem]"><?php echo esc_html($partnership['title']); ?></h2>
      </div>

      <div class="relative mt-8 px-4 sm:px-5 lg:px-0">
        <div class="absolute inset-x-0 bottom-0 h-[71%] bg-[#d5bb9f] lg:h-[100%]"></div>

        <div class="relative sm:px-5 lg:px-20 lg:py-0">
          <div class="grid gap-10 lg:grid-cols-[minmax(0,18rem)_minmax(0,20rem)_minmax(0,1fr)] lg:items-start lg:gap-10 lg:pt-[5.5rem]">
            <article class="reveal relative z-10">
              <div data-partnership-active-bio class="space-y-5 text-[14px] leading-[1] text-[#231f20]/92">
                <?php foreach (preg_split("/\r\n|\n|\r/", (string) $active_partnership_member['bio']) ?: [] as $paragraph) : ?>
                  <?php if (trim($paragraph) === '') : ?>
                    <?php continue; ?>
                  <?php endif; ?>
                  <p><?php echo esc_html($paragraph); ?></p>
                <?php endforeach; ?>
              </div>
            </article>

            <article class="reveal relative z-10">
              <h3 data-partnership-active-name class="font-heading text-[1rem] font-bold leading-[1] text-[#d96f73] lg:text-[2rem]"><?php echo esc_html($active_partnership_member['name']); ?></h3>
              <p data-partnership-active-title class="mt-3 text-[14px] leading-[1] text-black/92"><?php echo esc_html($active_partnership_member['title']); ?></p>
            </article>

            <div class="reveal relative z-10 lg:self-end">
              <div class="grid gap-0 grid-cols-2 lg:grid-cols-3 lg:gap-0 lg:items-end">
                <?php foreach ($partnership['members'] as $index => $member) : ?>
                  <button
                    type="button"
                    class="about-partnership-card group relative aspect-[0.86] overflow-visible bg-transparent lg:overflow-visible h-full"
                    data-partnership-card
                    data-partnership-index="<?php echo esc_attr((string) $index); ?>"
                    data-partnership-name="<?php echo esc_attr((string) $member['name']); ?>"
                    data-partnership-title="<?php echo esc_attr((string) $member['title']); ?>"
                    data-partnership-bio="<?php echo esc_attr(trim((string) $member['bio'])); ?>"
                    data-partnership-main-src="<?php echo esc_url((string) $member['image']); ?>"
                    data-partnership-hover-src="<?php echo esc_url((string) ($member['hover_image'] ?? $member['image'])); ?>"
                    aria-label="<?php echo esc_attr((string) $member['name']); ?>"
                  >
                    <img
                      src="<?php echo esc_url((string) $member['image']); ?>"
                      alt="<?php echo esc_attr((string) $member['name']); ?>"
                      class="about-partnership-card__image h-full w-full object-contain object-center"
                      loading="lazy"
                      decoding="async"
                      data-partnership-card-image
                    >
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="clinical-technology" aria-labelledby="clinical-technology-heading" data-section="clinical-technology" class="bg-white">
    <div class="lg:border-y lg:border-black/12 py-[4rem] lg:py-0">
      <div class="grid min-w-0 lg:grid-cols-5">
        <div class="min-w-0 px-4 py-12 sm:px-5 lg:relative lg:col-span-2 lg:px-16 lg:py-20 xl:px-20 flex flex-col justify-center">
          <p id="clinical-technology-kicker" class="reveal text-[1rem] font-semibold leading-none text-[#dea093] lg:text-3xl"><?php echo esc_html($clinical['kicker']); ?></p>
          <article class="reveal mt-3">
            <h2 id="clinical-technology-heading" class="font-heading text-[2rem] font-bold leading-[1] text-[#231f20] lg:max-w-[15ch] lg:text-[4rem]">
              <?php echo esc_html($clinical['title']); ?>
            </h2>
            <p class="mt-9 max-w-xl text-[14px] leading-[1] text-[#231f20]/86 lg:block">
              <?php echo esc_html($clinical['body_text']); ?>
            </p>
          </article>

          <div class="about-slider-nav reveal hidden items-center gap-6 lg:absolute lg:bottom-0 lg:right-10 lg:mb-10 lg:mt-16 lg:flex lg:justify-end">
            <button type="button" class="about-slider-nav__button" data-about-tech-prev aria-label="Previous clinical technology slide">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="M15 5 8 12l7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"></path>
              </svg>
            </button>
            <p class="font-heading text-[1rem] leading-none text-[#231f20]/82">
              <span data-about-tech-current>1</span>/<span data-about-tech-total><?php echo esc_html((string) count($technology_slides)); ?></span>
            </p>
            <button type="button" class="about-slider-nav__button" data-about-tech-next aria-label="Next clinical technology slide">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="m9 5 7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"></path>
              </svg>
            </button>
          </div>
        </div>

        <div class="min-w-0 overflow-hidden border-t border-black/12 lg:col-span-3 lg:overflow-visible lg:border-l lg:border-t-0">
          <div class="about-tech-slider" data-about-tech-slider>
            <?php foreach ($technology_slides as $slide) : ?>
              <article class="about-tech-slide min-h-0 lg:min-h-[90vh]">
                <figure class="w-screen lg:w-fit h-72 overflow-hidden sm:h-96 lg:h-[35rem]">
                  <img
                    src="<?php echo esc_url($slide['image']); ?>"
                    alt="<?php echo esc_attr($slide['alt']); ?>"
                    class="h-full w-full object-cover object-center"
                    width="841"
                    height="438"
                    loading="lazy"
                    decoding="async"
                  >
                </figure>
                <div class="px-4 py-5 sm:px-5 lg:px-8 lg:py-7">
                  <h3 class="font-heading text-[1.5rem] font-semibold leading-none text-[#231f20] lg:text-4xl"><?php echo esc_html($slide['title']); ?></h3>
                  <p class="mt-4 max-w-4xl text-[14px] leading-[1] text-[#231f20]/86">
                    <?php echo esc_html($slide['description']); ?>
                  </p>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
          <div class="about-slider-nav reveal flex items-center justify-center gap-6 px-4 py-6 sm:px-5 lg:hidden">
            <button type="button" class="about-slider-nav__button" data-about-tech-prev aria-label="Previous clinical technology slide">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="M15 5 8 12l7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"></path>
              </svg>
            </button>
            <p class="font-heading text-[1rem] leading-none text-[#231f20]/82">
              <span data-about-tech-current>1</span>/<span data-about-tech-total><?php echo esc_html((string) count($technology_slides)); ?></span>
            </p>
            <button type="button" class="about-slider-nav__button" data-about-tech-next aria-label="Next clinical technology slide">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="m9 5 7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="premium-clinic-experience" aria-labelledby="premium-clinic-experience-heading" data-section="premium-clinic-experience" class="bg-white">
    <div class="my-[4rem] lg:my-0 px-4 py-14 sm:px-5 sm:py-16 lg:px-16 lg:py-32 xl:px-20">
      <div class="reveal mx-auto max-w-6xl text-center">
        <p id="premium-clinic-experience-kicker" class="text-[1rem] font-semibold leading-none text-[#dea093]"><?php echo esc_html($premium['kicker']); ?></p>
        <h2 id="premium-clinic-experience-heading" class="font-heading mt-3 text-[2rem] font-bold leading-[1] text-[#231f20]"><?php echo esc_html($premium['title']); ?></h2>
      </div>

      <div class="about-privacy-stage mt-10 lg:mr-[calc(50%-50vw)]">
        <div class="about-privacy-slider" data-about-privacy-slider>
          <?php foreach ($privacy_slides as $slide) : ?>
            <div class="about-privacy-slide">
              <figure class="overflow-hidden">
                <img
                  src="<?php echo esc_url($slide['image']); ?>"
                  alt="<?php echo esc_attr($slide['alt']); ?>"
                  class="h-60 w-full object-cover object-center sm:h-72 lg:h-96"
                  width="492"
                  height="369"
                  loading="lazy"
                  decoding="async"
                >
              </figure>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
</main>
