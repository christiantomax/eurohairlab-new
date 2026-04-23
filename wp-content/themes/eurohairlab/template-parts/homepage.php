<?php

declare(strict_types=1);

$theme_uri = esc_url(get_template_directory_uri());
$page_id = get_queried_object_id();
if (is_front_page()) {
    $page_id = (int) get_option('page_on_front');

    if (!$page_id) {
        $home_page = get_page_by_path('home', OBJECT, 'page');
        $page_id = $home_page instanceof WP_Post ? (int) $home_page->ID : 0;
    }
}
$mb_get = static function (string $key) use ($page_id) {
    if (!$page_id || !function_exists('rwmb_meta')) {
        return null;
    }

    return rwmb_meta($key, [], $page_id);
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
/**
 * Normalize hero slide copy paragraphs from cloned textarea strings or legacy group rows.
 *
 * @param mixed $group
 * @return list<string>
 */
$normalize_hero_slide_copy_paragraphs = static function ($group): array {
    if (is_string($group)) {
        $group = trim($group);
        if ($group === '') {
            return [];
        }

        $paragraphs = preg_split('/\R\R+/', $group) ?: [];

        return array_values(array_filter(
            array_map(static fn ($line): string => trim((string) $line), $paragraphs),
            static fn (string $line): bool => $line !== ''
        ));
    }

    if (!is_array($group)) {
        return [];
    }

    $rows = $group['eh_home_hero_desc_rows'] ?? null;
    if (is_array($rows) && $rows !== []) {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $text = isset($row['eh_home_hero_desc_text']) ? trim((string) $row['eh_home_hero_desc_text']) : '';
            if ($text !== '') {
                $out[] = $text;
            }
        }

        if ($out !== []) {
            return $out;
        }
    }

    $raw = $group['eh_home_hero_copy_paragraph'] ?? null;
    if (is_string($raw)) {
        $raw = trim($raw);

        return $raw === '' ? [] : [$raw];
    }

    if (is_array($raw)) {
        return array_values(array_filter(
            array_map(static fn ($line): string => trim((string) $line), $raw),
            static fn (string $line): bool => $line !== ''
        ));
    }

    $lines = [];
    foreach ($group as $key => $value) {
        if ($key === '_state' || $key === 'rwmb_placeholder') {
            continue;
        }
        if (is_string($value)) {
            $t = trim($value);
            if ($t !== '') {
                $lines[] = $t;
            }
        }
    }

    return $lines;
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

$hero_slides_default = [
    [
        'image_url' => $theme_uri . '/assets/images/hero-bg.webp',
        'title' => 'ScalpFirst™',
        'description_paragraphs' => [
            'ScalpFirst™ is our signature approach that puts the scalp at the center of every hair treatment. It starts with a detailed scalp assessment to uncover underlying imbalances that cause thinning, shedding, or weak hair.',
            'By diagnosing the root cause first, ScalpFirst™ allows us to deliver targeted, effective treatments that restore follicle function and support long-term hair health.',
        ],
        'button_text' => 'Start Your Free Scalp Analysis',
        'button_href' => eurohairlab_get_free_scalp_analysis_default_url(),
        'overlay' => 'bg-[linear-gradient(90deg,rgba(18,16,18,.22)_0%,rgba(18,16,18,.1)_36%,rgba(18,16,18,.68)_100%)]',
        'position' => 'object-left-center',
    ],
    [
        'image_url' => $theme_uri . '/assets/images/scalp-analysis.webp',
        'title' => "Clinical Scalp\nDiagnosis",
        'description_paragraphs' => [
            'With our diagnose-based approach, we address hair issues at their origin. Thinning, shedding, and fragility all begin at the scalp, reflecting underlying physiological or biochemical imbalances.',
        ],
        'button_text' => 'Explore Diagnosis',
        'button_href' => eurohairlab_get_page_url('diagnosis', '/diagnosis/'),
        'overlay' => 'bg-[linear-gradient(90deg,rgba(18,16,18,.22)_0%,rgba(18,16,18,.1)_36%,rgba(18,16,18,.68)_100%)]',
        'position' => 'object-center',
    ],
    [
        'image_url' => $theme_uri . '/assets/images/journey-after.webp',
        'title' => "Visible Hair\nRecovery",
        'description_paragraphs' => [
            'Visible recovery is built on consistent scalp care and evidence-led protocols tailored to your stage of hair change.',
        ],
        'button_text' => 'See Real Results',
        'button_href' => eurohairlab_get_page_url('results', '/results/'),
        'overlay' => 'bg-[linear-gradient(90deg,rgba(18,16,18,.22)_0%,rgba(18,16,18,.1)_36%,rgba(18,16,18,.68)_100%)]',
        'position' => 'object-center',
    ],
    [
        'image_url' => $theme_uri . '/assets/images/real-transformations.webp',
        'title' => "Measured\nHair Results",
        'description_paragraphs' => [
            'Measured outcomes help you understand progress with clarity—density, comfort, and scalp balance tracked over time.',
        ],
        'button_text' => 'View Real Results',
        'button_href' => eurohairlab_get_page_url('results', '/results/'),
        'overlay' => 'bg-[linear-gradient(90deg,rgba(18,16,18,.22)_0%,rgba(18,16,18,.1)_36%,rgba(18,16,18,.68)_100%)]',
        'position' => 'object-center',
    ],
];
$foundation_default = [
    'kicker' => 'The Foundation',
    'title' => "Great Hair Starts at the Scalp",
    'body_text' => "The scalp is the foundation of every treatment and clinical solution.\n\nMost hair concerns don't begin with the hair. They begin with the scalp that go undiagnosed for years.\n\nAt EUROHAIRLAB, we focus on understand your condition, diagnosing the root before recommending anything",
    'button_text' => 'Start Your Free Scalp Analysis',
    'button_href' => eurohairlab_get_free_scalp_analysis_default_url(),
    'image' => $theme_uri . '/assets/images/scalp-analysis.webp',
    'video_url' => '',
];
$difference_default = [
    'kicker' => 'See the Difference',
    'title' => "3 Million Clinical\nTrials Worldwide,\nProven Success!",
    'body_text' => 'Thousands of clients have experienced improved hair density and scalp health through our treatment programs. We deliver more than treatments; we provide clinical clarity, a high-precision diagnosis, and a personalised regenerative roadmap.',
    'button_text' => 'View Real Results',
    'button_href' => eurohairlab_get_page_url('results', '/results/'),
    'before_image' => $theme_uri . '/assets/images/journey-before.webp',
    'after_image' => $theme_uri . '/assets/images/journey-after.webp',
    'before_label' => 'Before',
    'after_label' => 'After',
];
$treatments_page_url = eurohairlab_get_treatments_page_url();
$technology_default = [
    'kicker' => 'EUROHAIRLAB Technology',
    'title' => 'The Science Behind Your Results',
    'cards' => [
        [
            'title' => 'Scalp Detox',
            'duration' => '65 minutes',
            'description' => "A scalp detox treatment using DR. SCALP's special technique with an 8-step Korean method. It deeply cleanses dirt and dead skin cells, improves circulation, leaving the scalp feeling fresh and clean, and the hair lighter and ready for further treatments.\n\nSuitable for:\nOily scalp\nHair exposed to chemical processes (coloring/perming)\nClogged scalp\nPreparation for advanced hair treatments",
            'image' => $theme_uri . '/assets/images/figma/treatment-program-1.webp',
            'lightbox_title' => 'Scalp Detox',
            'href' => $treatments_page_url . '#program-korean',
        ],
        [
            'title' => 'Scalp Revival',
            'duration' => '60 minutes',
            'description' => "An intensive treatment designed to nourish weak and thinning hair, restore scalp health, and provide full relaxation. It leaves the scalp feeling healthy and relaxed, while the hair appears thicker, stronger, and more radiant.\n\nSuitable for:\nNormal to oily scalp\nDamaged hair\nScalp with buildup of dirt or residue\nA preparatory step before further treatments",
            'image' => $theme_uri . '/assets/images/figma/treatment-program-2.webp',
            'lightbox_title' => 'Scalp Revival',
            'href' => $treatments_page_url . '#program-korean',
        ],
        [
            'title' => 'Regen Activ™ Hair Loss Treatment',
            'duration' => '75–90 minutes',
            'description' => "An advanced scalp and hair loss treatment using the SCALPFIRST™ System with a structured 19-step clinical protocol. It targets hair loss at the root by reactivating follicles, balancing the scalp environment, and improving microcirculation—supporting stronger, healthier, and long-term hair growth.\n\nSuitable for:\nEarly-stage hair loss\nThinning hair / reduced hair density\nImbalanced or inflamed scalp\nPrevention of Androgenetic Alopecia (AGA)",
            'image' => $theme_uri . '/assets/images/figma/treatment-technology.webp',
            'lightbox_title' => 'Regen Activ™',
            'href' => $treatments_page_url . '#program-regan',
        ],
        [
            'title' => 'Regen Boost™ Hormonal Hair Loss Control',
            'duration' => '75–90 minutes',
            'description' => "An advanced treatment designed to control hormonally driven hair loss using the SCALPFIRST™ System with a precise 20-step clinical protocol. It works by regulating DHT activity, protecting hair follicles from miniaturization, and stabilizing the scalp—helping to slow hair loss progression and maintain stronger, healthier hair over time.\n\nSuitable for:\nHormonal hair loss (DHT-related)\nReceding hairline or thinning crown\nEarly to moderate Androgenetic Alopecia (AGA)\nMen experiencing progressive hair thinning",
            'image' => $theme_uri . '/assets/images/figma/treatment-program-3.webp',
            'lightbox_title' => 'Regen Boost™',
            'href' => $treatments_page_url . '#program-booster',
        ],
    ],
];
$programs_default = [
    'kicker' => 'Treatment Programs',
    'title' => 'Personalized Hair Recovery',
    'body_text' => 'At EUROHAIRLAB, every program is structured around the findings of your personal assessment. Ensuring that what you receive is clinically designed around your diagnosis, personalised to your condition, and adjusted as your scalp responds.',
    'button_text' => 'Learn More Treatment Programs',
    'button_href' => $treatments_page_url,
    'cards' => array_map(
        static function (array $tech_card): array {
            return [
                'title' => $tech_card['title'],
                'duration' => $tech_card['duration'] ?? '',
                'description' => $tech_card['description'],
                'image' => $tech_card['image'],
                'href' => $tech_card['href'],
            ];
        },
        $technology_default['cards']
    ),
];
$testimonials_default = [
    ['body_text' => "I don't go anywhere without the Clean Hand Sanitizer. It's my peace of mind.", 'name' => 'Natasha'],
    ['body_text' => 'I had severe hair thinning after stress and hormonal imbalance. After the 90-day program, my hair density improved significantly.', 'name' => 'Angel'],
    ['body_text' => "I don't go anywhere without the Clean Hand Sanitizer. It's my peace of mind.", 'name' => 'Natasha'],
    ['body_text' => 'I had severe hair thinning after stress and hormonal imbalance. After the 90-day program, my hair density improved significantly.', 'name' => 'Angel'],
];
$consultation_default = [
    'title' => 'Start Your Great Hair With Us',
    'button_text' => 'Start Consultation',
    'button_href' => 'mailto:hello@eurohairlab.com',
    'background_image' => $theme_uri . '/assets/images/journey-before.webp',
];

$hero_images = $mb_get('eh_home_hero_images');
$hero_titles = $mb_get('eh_home_hero_titles');
$hero_slide_copy_groups = $mb_get('eh_home_hero_slide_copy');
$hero_button_texts = $mb_get('eh_home_hero_button_texts');
$hero_button_hrefs = $mb_get('eh_home_hero_button_hrefs');
$hero_slides = [];
if (is_array($hero_images) && !empty($hero_images)) {
    $hero_images = array_values($hero_images);
    $hero_slide_copy_groups = is_array($hero_slide_copy_groups) ? array_values($hero_slide_copy_groups) : [];
    $hero_slides = array_values(array_filter(array_map(
        static function ($image, int $index) use ($resolve_image, $resolve_link, $hero_titles, $hero_slide_copy_groups, $hero_button_texts, $hero_button_hrefs, $normalize_hero_slide_copy_paragraphs) {
            $title = is_array($hero_titles) ? ($hero_titles[$index] ?? '') : '';
            if ($title === '') {
                return null;
            }

            $copy_group = $hero_slide_copy_groups[$index] ?? null;
            $description_paragraphs = $normalize_hero_slide_copy_paragraphs($copy_group);

            return [
                'image_url' => $resolve_image($image, ''),
                'title' => (string) $title,
                'description_paragraphs' => $description_paragraphs,
                'button_text' => (string) (is_array($hero_button_texts) ? ($hero_button_texts[$index] ?? '') : ''),
                'button_href' => $resolve_link(
                    (string) (is_array($hero_button_hrefs) ? ($hero_button_hrefs[$index] ?? '') : ''),
                    (string) ($hero_slides_default[$index]['button_href'] ?? '#')
                ),
                'overlay' => 'bg-[linear-gradient(90deg,rgba(18,16,18,.22)_0%,rgba(18,16,18,.1)_36%,rgba(18,16,18,.68)_100%)]',
                'position' => 'object-center',
            ];
        },
        $hero_images,
        array_keys($hero_images)
    )));
}

if (empty($hero_slides)) {
    $hero_slides = $hero_slides_default;
}

if ($hero_slides !== [] && (is_array($hero_button_texts) || is_array($hero_button_hrefs))) {
    /**
     * Apply CTA from the Home Hero meta box. Required when the theme default slides
     * are used (no custom hero images), and safe to merge when custom slides are used
     * because values come from the same keys.
     */
    foreach ($hero_slides as $h_idx => &$hero_slide) {
        if (is_array($hero_button_texts) && array_key_exists($h_idx, $hero_button_texts) && trim((string) $hero_button_texts[$h_idx]) !== '') {
            $hero_slide['button_text'] = (string) $hero_button_texts[$h_idx];
        }
        if (is_array($hero_button_hrefs) && array_key_exists($h_idx, $hero_button_hrefs) && trim((string) $hero_button_hrefs[$h_idx]) !== '') {
            $hero_slide['button_href'] = $resolve_link(
                (string) $hero_button_hrefs[$h_idx],
                (string) ($hero_slide['button_href'] ?? '#')
            );
        }
    }
    unset($hero_slide);
}

$foundation = array_merge($foundation_default, array_filter([
    'kicker' => $mb_get('eh_home_foundation_kicker'),
    'title' => $mb_get('eh_home_foundation_title'),
    'body_text' => $mb_get('eh_home_foundation_body_text'),
    'button_text' => $mb_get('eh_home_foundation_button_text'),
    'button_href' => $resolve_link((string) $mb_get('eh_home_foundation_button_href'), $foundation_default['button_href']),
    'video_url' => $mb_get('eh_home_foundation_video_url'),
], static fn($value) => $value !== null && $value !== ''));
$foundation['image'] = $resolve_image($mb_get('eh_home_foundation_image'), $foundation_default['image']);
$foundation_video_embed = '';
if (is_string($foundation['video_url']) && trim($foundation['video_url']) !== '') {
    $foundation_video_embed = wp_oembed_get(trim($foundation['video_url'])) ?: '';
}

$difference = array_merge($difference_default, array_filter([
    'kicker' => $mb_get('eh_home_difference_kicker'),
    'title' => $mb_get('eh_home_difference_title'),
    'body_text' => $mb_get('eh_home_difference_body_text'),
    'button_text' => $mb_get('eh_home_difference_button_text'),
    'button_href' => $resolve_link((string) $mb_get('eh_home_difference_button_href'), $difference_default['button_href']),
    'before_label' => $mb_get('eh_home_difference_before_label'),
    'after_label' => $mb_get('eh_home_difference_after_label'),
], static fn($value) => $value !== null && $value !== ''));
$difference['before_image'] = $resolve_image($mb_get('eh_home_difference_before_image'), $difference_default['before_image']);
$difference['after_image'] = $resolve_image($mb_get('eh_home_difference_after_image'), $difference_default['after_image']);

$technology = array_merge($technology_default, array_filter([
    'kicker' => $mb_get('eh_home_technology_kicker'),
    'title' => $mb_get('eh_home_technology_title'),
], static fn($value) => $value !== null && $value !== ''));
if (is_array($mb_get('eh_home_technology_card_titles')) && !empty($mb_get('eh_home_technology_card_titles'))) {
    $technology_titles = $mb_get('eh_home_technology_card_titles');
    $technology_descriptions = $mb_get('eh_home_technology_card_descriptions');
    $technology_images = array_values((array) $mb_get('eh_home_technology_card_images'));
    $technology_lightbox_titles = $mb_get('eh_home_technology_card_lightbox_titles');
    $technology['cards'] = array_values(array_filter(array_map(
        static function ($title, int $index) use ($resolve_image, $technology_descriptions, $technology_images, $technology_lightbox_titles) {
            if (empty($title)) {
                return null;
            }

            return [
                'title' => (string) $title,
                'description' => (string) (is_array($technology_descriptions) ? ($technology_descriptions[$index] ?? '') : ''),
                'image' => $resolve_image(is_array($technology_images) ? ($technology_images[$index] ?? []) : [], ''),
                'lightbox_title' => (string) (is_array($technology_lightbox_titles) ? ($technology_lightbox_titles[$index] ?? $title) : $title),
            ];
        },
        $technology_titles,
        array_keys($technology_titles)
    )));
    if (empty($technology['cards'])) {
        $technology['cards'] = $technology_default['cards'];
    }
}

foreach ($technology['cards'] as $ti => &$technology_card) {
    $fallback = $technology_default['cards'][$ti] ?? null;
    if (!is_array($fallback)) {
        continue;
    }
    $technology_card['href'] = isset($technology_card['href']) && is_string($technology_card['href']) && $technology_card['href'] !== ''
        ? $technology_card['href']
        : $fallback['href'];
    $technology_card['duration'] = isset($technology_card['duration']) && is_string($technology_card['duration']) && $technology_card['duration'] !== ''
        ? $technology_card['duration']
        : ($fallback['duration'] ?? '');
    if (empty($technology_card['lightbox_title'])) {
        $technology_card['lightbox_title'] = $fallback['lightbox_title'] ?? $technology_card['title'];
    }
}
unset($technology_card);

$programs_home = array_merge($programs_default, array_filter([
    'kicker' => $mb_get('eh_home_programs_kicker'),
    'title' => $mb_get('eh_home_programs_title'),
    'body_text' => $mb_get('eh_home_programs_body_text'),
    'button_text' => $mb_get('eh_home_programs_button_text'),
    'button_href' => $resolve_link((string) $mb_get('eh_home_programs_button_href'), $programs_default['button_href']),
], static fn($value) => $value !== null && $value !== ''));
if (is_array($mb_get('eh_home_programs_card_titles')) && !empty($mb_get('eh_home_programs_card_titles'))) {
    $program_titles = $mb_get('eh_home_programs_card_titles');
    $program_descriptions = $mb_get('eh_home_programs_card_descriptions');
    $program_durations = $mb_get('eh_home_programs_card_durations');
    $program_images = array_values((array) $mb_get('eh_home_programs_card_images'));
    $programs_home['cards'] = array_values(array_filter(array_map(
        static function ($title, int $index) use ($resolve_image, $program_descriptions, $program_durations, $program_images) {
            if (empty($title)) {
                return null;
            }

            return [
                'title' => (string) $title,
                'description' => (string) (is_array($program_descriptions) ? ($program_descriptions[$index] ?? '') : ''),
                'duration' => (string) (is_array($program_durations) ? ($program_durations[$index] ?? '') : ''),
                'image' => $resolve_image(is_array($program_images) ? ($program_images[$index] ?? []) : [], ''),
            ];
        },
        $program_titles,
        array_keys($program_titles)
    )));
    if (empty($programs_home['cards'])) {
        $programs_home['cards'] = $programs_default['cards'];
    }
}

foreach ($programs_home['cards'] as $pci => &$programs_card) {
    $pfb = $programs_default['cards'][$pci] ?? null;
    $tech_fb = $technology_default['cards'][$pci] ?? null;
    if (!isset($programs_card['href']) || !is_string($programs_card['href']) || $programs_card['href'] === '') {
        $programs_card['href'] = is_array($pfb) && isset($pfb['href']) ? (string) $pfb['href'] : $treatments_page_url;
    }
    $desc = isset($programs_card['description']) ? trim((string) $programs_card['description']) : '';
    if ($desc === '' && is_array($tech_fb) && !empty($tech_fb['description'])) {
        $programs_card['description'] = (string) $tech_fb['description'];
    }
    if (empty($programs_card['duration']) && is_array($tech_fb) && !empty($tech_fb['duration'])) {
        $programs_card['duration'] = (string) $tech_fb['duration'];
    }
    if (empty($programs_card['duration']) && is_array($pfb) && !empty($pfb['duration'])) {
        $programs_card['duration'] = (string) $pfb['duration'];
    }
}
unset($programs_card);

$testimonials = $testimonials_default;
if (is_array($mb_get('eh_home_testimonial_texts')) && !empty($mb_get('eh_home_testimonial_texts'))) {
    $testimonial_texts = $mb_get('eh_home_testimonial_texts');
    $testimonial_names = $mb_get('eh_home_testimonial_names');
    $testimonials = array_values(array_filter(array_map(
        static function ($text, int $index) use ($testimonial_names) {
            $name = is_array($testimonial_names) ? ($testimonial_names[$index] ?? '') : '';
            if (empty($text) && empty($name)) {
                return null;
            }

            return [
                'body_text' => (string) $text,
                'name' => (string) $name,
            ];
        },
        $testimonial_texts,
        array_keys($testimonial_texts)
    )));
    if (empty($testimonials)) {
        $testimonials = $testimonials_default;
    }
}

$consultation = array_merge($consultation_default, array_filter([
    'title' => $mb_get('eh_home_consultation_title'),
    'button_text' => $mb_get('eh_home_consultation_button_text'),
    'button_href' => $resolve_link((string) $mb_get('eh_home_consultation_button_href'), $consultation_default['button_href']),
], static fn($value) => $value !== null && $value !== ''));
$consultation['background_image'] = $resolve_image($mb_get('eh_home_consultation_background_image'), $consultation_default['background_image']);
?>
  <main id="main-content" class="bg-white text-eh-ink antialiased">
    <section aria-labelledby="hero-title" class="homepage-hero relative min-h-screen bg-ink">
      <div class="homepage-hero-slider" data-homepage-hero-slider>
        <?php foreach ($hero_slides as $index => $slide) : ?>
          <article class="homepage-hero-slide relative min-h-screen overflow-hidden">
            <div class="absolute inset-0">
              <img
                src="<?php echo esc_url($slide['image_url']); ?>"
                alt="<?php echo esc_attr(wp_strip_all_tags(str_replace("\n", ' ', $slide['title']))); ?>"
                class="h-full w-full object-cover <?php echo esc_attr($slide['position']); ?>"
                width="1656"
                height="1032"
                <?php echo $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
                decoding="async"
              >
              <div class="absolute inset-0 <?php echo esc_attr($slide['overlay']); ?>"></div>
            </div>

            <div class="relative flex min-h-screen w-full items-end justify-end px-4 pb-24 pt-32 sm:px-5 sm:pb-28 sm:pt-36 lg:px-10 lg:pb-20 xl:px-16">
              <div class="w-full lg:w-[45%] text-left text-white lg:mb-28 lg:mr-[2rem] xl:mr-[3rem]">
                <?php if ($index === 0) : ?>
                  <h1 id="hero-title" class="font-futuraHv text-3xl font-normal capitalize leading-none text-white sm:text-4xl md:text-5xl lg:text-[64px]">
                    <?php echo nl2br(esc_html($slide['title'])); ?>
                  </h1>
                <?php else : ?>
                  <h2 class="font-futuraHv text-3xl font-normal capitalize leading-none text-white sm:text-4xl md:text-5xl lg:text-[64px]">
                    <?php echo nl2br(esc_html($slide['title'])); ?>
                  </h2>
                <?php endif; ?>

                <?php
                $hero_desc = isset($slide['description_paragraphs']) && is_array($slide['description_paragraphs'])
                    ? $slide['description_paragraphs']
                    : [];
                ?>
                <?php if (!empty($hero_desc)) : ?>
                  <div class="mt-11 space-y-4">
                    <?php foreach ($hero_desc as $para) : ?>
                      <p class="font-futuraBk whitespace-pre-line text-[14px] lg:text-[1rem] font-normal leading-[1] text-white">
                        <?php echo esc_html($para); ?>
                      </p>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <div class="mt-12 flex flex-col gap-4 sm:flex-row">
                  <a
                    href="<?php echo esc_url($slide['button_href']); ?>"
                    class="homepage-cta homepage-cta--light text-[14px] lg:text-[1rem]"
                    <?php if (eurohairlab_url_matches_free_scalp_analysis_default($slide['button_href'])) : ?>
                      <?php echo eurohairlab_free_scalp_analysis_link_attributes($slide['button_href']); ?>
                    <?php endif; ?>
                  >
                    <span><?php echo esc_html($slide['button_text']); ?></span>
                    <img
                      src="<?php echo $theme_uri; ?>/assets/images/icons/arrow-button.webp"
                      alt=""
                      aria-hidden="true"
                      class="homepage-cta__arrow"
                      width="18"
                      height="18"
                      decoding="async"
                    >
                  </a>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?php if (count($hero_slides) > 1) : ?>
        <div class="homepage-hero-dots-shell pointer-events-none absolute inset-x-0 bottom-0 z-[4] flex min-h-[2.5rem] items-end justify-end px-4 pb-[2rem] sm:px-5 sm:pb-28 lg:px-10 lg:pb-20 xl:px-16">
          <div class="pointer-events-auto mb-6 flex min-h-[1.5rem] w-full justify-start md:mb-[-3rem] sm:mb-8 lg:w-[45%] lg:mb-10 lg:mr-[2rem] xl:mr-[3rem]">
            <div id="homepage-hero-dots" class="homepage-hero-dots-host"></div>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <section id="about" aria-labelledby="about-title" class="bg-white">
      <div class="grid w-full items-stretch gap-0 lg:h-screen lg:grid-cols-[45%_55%]">
        <article class="flex items-center px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-16">
          <div class="max-w-xl reveal">
            <p class="font-futuraHv text-[24px] font-normal leading-none text-eh-coral"><?php echo esc_html($foundation['kicker']); ?></p>
            <h2 id="about-title" class="font-futuraHv mt-4 text-3xl font-normal leading-none text-eh-ink sm:text-4xl md:text-5xl lg:text-[64px]">
              <?php echo nl2br(esc_html($foundation['title'])); ?>
            </h2>
            <div class="mt-8 space-y-6 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink">
              <?php foreach (preg_split("/\r\n|\n|\r/", (string) $foundation['body_text']) ?: [] as $paragraph) : ?>
                <?php if (trim($paragraph) === '') : ?>
                  <?php continue; ?>
                <?php endif; ?>
                <p><?php echo esc_html($paragraph); ?></p>
              <?php endforeach; ?>
            </div>
            <a
              href="<?php echo esc_url($foundation['button_href']); ?>"
              class="homepage-cta homepage-cta--dark mt-10 text-[14px]"
              <?php if (eurohairlab_url_matches_free_scalp_analysis_default($foundation['button_href'])) : ?>
                <?php echo eurohairlab_free_scalp_analysis_link_attributes($foundation['button_href']); ?>
              <?php endif; ?>
            >
              <span><?php echo esc_html($foundation['button_text']); ?></span>
              <img
                src="<?php echo $theme_uri; ?>/assets/images/icons/arrow-button.webp"
                alt=""
                aria-hidden="true"
                class="homepage-cta__arrow"
                width="18"
                height="18"
                decoding="async"
              >
            </a>
          </div>
        </article>

        <figure class="reveal relative min-h-[24rem] overflow-hidden bg-sand lg:h-screen">
          <?php if ($foundation_video_embed !== '') : ?>
            <div class="h-full w-full [&_iframe]:h-full [&_iframe]:w-full">
              <?php echo $foundation_video_embed; ?>
            </div>
          <?php else : ?>
            <img
              src="<?php echo esc_url($foundation['image']); ?>"
              alt="<?php echo esc_attr(wp_strip_all_tags(str_replace("\n", ' ', $foundation['title']))); ?>"
              class="h-full w-full object-cover object-center"
              width="793"
              height="709"
              loading="lazy"
              decoding="async"
            >
          <?php endif; ?>
        </figure>
      </div>
    </section>

    <section id="results" aria-labelledby="results-title" class="border-b border-[#dea093] bg-white">
      <div class="grid w-full gap-0 lg:h-screen lg:grid-cols-[45%_55%]">
        <figure class="order-2 lg:order-1 reveal relative min-h-[32rem] overflow-hidden bg-white lg:h-screen">
          <div class="relative h-full min-h-[32rem] lg:h-screen">
            <img
              src="<?php echo esc_url($difference['after_image']); ?>"
              alt="Hair after Eurohairlab treatment"
              class="absolute inset-0 h-full w-full object-cover object-center"
              width="225"
              height="300"
              loading="lazy"
              decoding="async"
            >
            <div class="pointer-events-none absolute right-[12%] top-[54%] z-[1] border border-white/70 px-4 py-12 font-futuraHv text-[24px] font-normal uppercase leading-none text-white"><?php echo esc_html($difference['after_label']); ?></div>
            <div class="before-after-overlay absolute inset-0 z-[2] overflow-hidden" style="clip-path: inset(0 50% 0 0);">
              <img
                src="<?php echo esc_url($difference['before_image']); ?>"
                alt="Hair before Eurohairlab treatment"
                class="h-full w-full object-cover object-center"
                width="225"
                height="300"
                loading="lazy"
                decoding="async"
              >
              <div class="pointer-events-none absolute left-[22%] top-[34%] border border-white/80 px-4 py-16 font-futuraHv text-[24px] font-normal uppercase leading-none text-white"><?php echo esc_html($difference['before_label']); ?></div>
            </div>
            <input
              id="comparison-slider"
              type="range"
              min="1"
              max="100"
              value="50"
              class="before-after-range absolute inset-0 z-20 h-full w-full cursor-ew-resize opacity-0"
              aria-label="Drag to compare before and after results"
            >
            <div class="before-after-line absolute inset-y-0 left-1/2 z-10 w-[3px] -translate-x-1/2 bg-white/80">
              <div class="before-after-handle absolute bottom-10 left-1/2 flex h-16 w-16 -translate-x-1/2 items-center justify-center rounded-full border-2 border-black/40 bg-white text-ink shadow-soft">
                <img
                  src="<?php echo $theme_uri; ?>/assets/images/icons/before-after-handle.webp"
                  alt=""
                  aria-hidden="true"
                  class="h-8 w-8 object-contain"
                  width="34"
                  height="31"
                  decoding="async"
                >
              </div>
            </div>
          </div>
        </figure>

        <article class="order-1 lg:order-2  flex items-center px-4 py-14 sm:px-5 sm:py-16 lg:h-screen lg:px-20 lg:py-16">
          <div class="max-w-xl reveal">
            <p class="font-futuraHv text-[24px] font-normal leading-none text-eh-coral"><?php echo esc_html($difference['kicker']); ?></p>
            <h2 id="results-title" class="font-futuraHv mt-4 text-3xl font-normal leading-none text-eh-ink sm:text-4xl md:text-5xl lg:text-[64px]">
              <?php echo nl2br(esc_html($difference['title'])); ?>
            </h2>
            <p class="mt-10 max-w-md font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink">
               <?php echo esc_html($difference['body_text']); ?>
            </p>
          </div>
        </article>
      </div>
    </section>

    <section id="diagnosis" aria-labelledby="diagnosis-title" class="bg-white my-[4rem]">
      <div class="w-full px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-16">
        <header class="reveal mx-auto max-w-3xl text-center">
          <p class="font-futuraHv text-[1rem] font-normal leading-none text-eh-coral"><?php echo esc_html($technology['kicker']); ?></p>
          <h2 id="diagnosis-title" class="font-futuraHv mt-4 text-center text-[2rem] font-normal leading-none text-eh-ink"><?php echo nl2br(esc_html($technology['title'])); ?></h2>
        </header>

        <?php
        $technology_accordion_label = __('EUROHAIRLAB technology treatments', 'eurohairlab');
        ?>

        <div class="mt-10 space-y-5 lg:hidden" role="list" aria-label="<?php echo esc_attr($technology_accordion_label); ?>">
          <?php foreach ($technology['cards'] as $index => $card) : ?>
            <?php $card_href = isset($card['href']) ? (string) $card['href'] : $treatments_page_url; ?>
            <article class="reveal overflow-hidden rounded-sm" role="listitem">
              <a href="<?php echo esc_url($card_href); ?>" class="block focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-eh-coral">
                <div class="relative h-52 w-full overflow-hidden sm:h-60">
                  <img
                    src="<?php echo esc_url($card['image']); ?>"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover object-center"
                    width="640"
                    height="480"
                    loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                    decoding="async"
                  >
                  <div class="absolute inset-0 flex items-center justify-center px-4 text-center">
                    <h3 class="font-futuraHv text-[24px] font-normal capitalize leading-none text-white"><?php echo esc_html($card['title']); ?></h3>
                  </div>
                </div>
                <?php if (!empty($card['description'])) : ?>
                  <div class="border-t border-ink/8 px-4 py-4">
                    <p class="whitespace-pre-line font-futuraBk text-[15px] font-normal leading-[140%] text-eh-muted"><?php echo esc_html($card['description']); ?></p>
                  </div>
                <?php endif; ?>
              </a>
            </article>
          <?php endforeach; ?>
        </div>

        <div
          class="eh-tech-accordion reveal mt-12 hidden lg:flex"
          role="list"
          aria-label="<?php echo esc_attr($technology_accordion_label); ?>"
        >
          <?php foreach ($technology['cards'] as $index => $card) : ?>
            <?php $card_href = isset($card['href']) ? (string) $card['href'] : $treatments_page_url; ?>
            <div class="eh-tech-accordion__cell min-h-0 min-w-0" role="listitem">
              <a
                href="<?php echo esc_url($card_href); ?>"
                class="eh-tech-accordion__panel group relative block h-full min-h-0 overflow-hidden focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-eh-coral"
                aria-label="<?php echo esc_attr($card['title']); ?>"
              >
                <span class="eh-tech-accordion__media pointer-events-none absolute inset-0 z-0 overflow-hidden" aria-hidden="true">
                  <img
                    src="<?php echo esc_url($card['image']); ?>"
                    alt=""
                    class="eh-tech-accordion__media-img"
                    width="1920"
                    height="1080"
                    loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                    decoding="async"
                  >
                </span>
                <div class="eh-tech-accordion__overlay pointer-events-none absolute inset-0 z-[2] flex items-center justify-center px-4 py-6">
                  <div class="eh-tech-accordion__overlay-inner flex max-w-md flex-col items-center justify-center gap-0 text-center">
                    <div class="eh-tech-accordion__title-wrap">
                      <span class="eh-tech-accordion__title font-futuraHv text-[24px] font-normal capitalize leading-none text-white"><?php echo esc_html($card['title']); ?></span>
                    </div>
                    <?php if (!empty($card['description'])) : ?>
                      <div class="eh-tech-accordion__body">
                        <p class="whitespace-pre-line font-futuraBk text-[13px] font-normal leading-[140%] text-white/95 lg:text-sm [text-shadow:0_1px_10px_rgba(0,0,0,0.65)]"><?php echo nl2br(esc_html($card['description'])); ?></p>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section id="programs" aria-labelledby="programs-title" class="relative overflow-x-hidden bg-white text-eh-ink my-[4rem]">
      <div class="relative w-full px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-16">
        <header class="reveal mx-auto max-w-4xl text-center">
          <p class="font-futuraHv text-[24px] font-normal leading-none text-eh-coral"><?php echo esc_html($programs_home['kicker']); ?></p>
          <h2 id="programs-title" class="font-futuraHv mt-2 text-center text-[2rem] font-normal leading-none text-eh-ink">
            <?php echo nl2br(esc_html($programs_home['title'])); ?>
          </h2>
          <p class="mx-auto mt-6 max-w-3xl text-center font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink">
            <?php echo esc_html($programs_home['body_text']); ?>
          </p>
        </header>
      </div>

      <div class="programs-home-slider-stage relative z-10 w-screen max-w-[100vw] -translate-x-1/2 left-1/2">
        <button
          type="button"
          class="programs-home-slider__arrow programs-home-slider__arrow--prev"
          data-programs-home-prev
          aria-label="<?php echo esc_attr__('Previous treatment program slide', 'eurohairlab'); ?>"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="19" height="33" viewBox="0 0 19 33" fill="none" aria-hidden="true">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M17.4277 17.8388L3.48505 32.1104L0 28.5431L12.2001 16.0552L0 3.56726L3.48505 0L17.4277 14.2716C17.8898 14.7447 18.1493 15.3862 18.1493 16.0552C18.1493 16.7242 17.8898 17.3657 17.4277 17.8388Z" fill="white" fill-opacity="0.66"></path>
          </svg>
        </button>
        <button
          type="button"
          class="programs-home-slider__arrow programs-home-slider__arrow--next"
          data-programs-home-next
          aria-label="<?php echo esc_attr__('Next treatment program slide', 'eurohairlab'); ?>"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="19" height="33" viewBox="0 0 19 33" fill="none" aria-hidden="true">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M17.4277 17.8388L3.48505 32.1104L0 28.5431L12.2001 16.0552L0 3.56726L3.48505 0L17.4277 14.2716C17.8898 14.7447 18.1493 15.3862 18.1493 16.0552C18.1493 16.7242 17.8898 17.3657 17.4277 17.8388Z" fill="white" fill-opacity="0.66"></path>
          </svg>
        </button>
        <div class="programs-home-slider min-h-unset h-fit" data-programs-home-slider>
          <?php foreach ($programs_home['cards'] as $card) : ?>
            <?php $card_href = isset($card['href']) ? (string) $card['href'] : $treatments_page_url; ?>
            <div class="programs-home-slide-col h-full">
              <article class="programs-home-slide group flex h-full min-h-0 flex-col overflow-hidden border border-ink/12 bg-white shadow-[0_8px_28px_rgba(32,28,32,0.06)]">
                <a
                  href="<?php echo esc_url($card_href); ?>"
                  class="programs-home-slide__link flex min-h-[19rem] flex-1 flex-col focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-eh-coral sm:min-h-[21rem] lg:min-h-[23rem]"
                  aria-label="<?php echo esc_attr($card['title']); ?>"
                >
                  <div class="programs-home-slide__media relative aspect-[16/9] w-full shrink-0 overflow-hidden bg-ink/5 sm:aspect-[2/1]">
                    <img
                      src="<?php echo esc_url($card['image']); ?>"
                      alt=""
                      class="h-full w-full object-cover object-center"
                      width="1656"
                      height="1032"
                      loading="lazy"
                      decoding="async"
                    >
                  </div>
                  <div class="programs-home-slide__panel grid min-h-[12rem] flex-1 grid-cols-1 gap-y-4 bg-[#dcc1a4] px-4 py-5 sm:min-h-[13rem] sm:grid-cols-[minmax(10.5rem,14rem)_minmax(0,1fr)] sm:items-stretch sm:gap-x-6 sm:gap-y-0 sm:px-5 sm:py-6 lg:min-h-[14rem] lg:px-6 lg:py-7">
                    <div class="programs-home-slide__rail flex flex-col justify-between gap-6 pb-4 sm:pb-0">
                      <p class="programs-home-slide__card-title font-futuraHv text-[1rem] font-normal capitalize leading-none text-eh-ink"><?php echo esc_html($card['title']); ?></p>
                      <?php if (!empty($card['duration'])) : ?>
                        <p class="programs-home-slide__duration font-futuraBk text-[14px] font-normal capitalize leading-[1] text-eh-ink"><?php echo esc_html($card['duration']); ?></p>
                      <?php endif; ?>
                    </div>
                    <div class="programs-home-slide__copy flex min-w-0 flex-col justify-start gap-4">
                      <?php if (!empty($card['description'])) : ?>
                        <p class="programs-home-slide__description whitespace-pre-line font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink"><?php echo esc_html($card['description']); ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                </a>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="relative z-10 px-4 pb-14 pt-10 text-center sm:px-5 sm:pb-16 lg:px-20 lg:pb-20 lg:pt-10">
        <a
          href="<?php echo esc_url($programs_home['button_href']); ?>"
          class="inline-flex min-h-4 items-center justify-center border border-ink/45 px-6 py-2 font-futuraBk text-[14px] font-normal capitalize leading-[1] text-eh-ink transition hover:bg-ink hover:text-white"
        >
          <?php echo esc_html($programs_home['button_text']); ?>
        </a>
      </div>
    </section>

    <section aria-label="Client testimonials" class="overflow-x-hidden bg-white">
      <div class="overflow-hidden py-10 sm:py-12 lg:py-14">
        <div class="testimonial-marquee">
          <div class="testimonial-track">
            <?php foreach ($testimonials as $item) : ?>
              <article class="testimonial-card text-center text-ink">
                <p class="text-2xl tracking-[0.24em] text-eh-coral" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</p>
                <blockquote class="mt-5 px-5 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink">
                  <p><?php echo esc_html($item['body_text']); ?></p>
                </blockquote>
                <p class="mt-5 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink"><?php echo esc_html($item['name']); ?></p>
              </article>
            <?php endforeach; ?>
            <?php foreach ($testimonials as $item) : ?>
              <article class="testimonial-card testimonial-card--marquee-clone text-center text-ink" aria-hidden="true">
                <p class="text-2xl tracking-[0.24em] text-eh-coral" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</p>
                <blockquote class="mt-5 px-5 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink">
                  <p><?php echo esc_html($item['body_text']); ?></p>
                </blockquote>
                <p class="mt-5 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink"><?php echo esc_html($item['name']); ?></p>
              </article>
            <?php endforeach; ?>
            <?php foreach ($testimonials as $item) : ?>
              <article class="testimonial-card testimonial-card--marquee-clone text-center text-ink" aria-hidden="true">
                <p class="text-2xl tracking-[0.24em] text-eh-coral" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</p>
                <blockquote class="mt-5 px-5 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink">
                  <p><?php echo esc_html($item['body_text']); ?></p>
                </blockquote>
                <p class="mt-5 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink"><?php echo esc_html($item['name']); ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <section id="consultation" aria-labelledby="consultation-title" class="overflow-hidden">
      <div class="relative min-h-[22rem] sm:min-h-[28rem] md:h-[60vh] md:min-h-0">
        <img
          src="<?php echo esc_url($consultation['background_image']); ?>"
          alt="Three women with healthy skin and hair smiling together"
          class="consultation-image absolute inset-0 block h-full w-full object-cover object-center opacity-90"
          width="793"
          height="709"
          loading="lazy"
          decoding="async"
        >
        <div class="absolute inset-0"></div>
        <div class="absolute inset-x-0 bottom-0 z-10 px-4 pb-6 text-center sm:px-5 sm:pb-8 md:inset-0 md:flex md:items-center md:justify-center md:px-8 md:pb-0 lg:px-20">
          <div class="reveal flex w-full flex-col items-center justify-center text-center md:max-w-5xl">
            <h2 id="consultation-title" class="font-futuraHv mx-auto text-center text-[2rem] font-normal capitalize leading-none text-white  md:max-w-none md:whitespace-nowrap">
              <?php echo esc_html($consultation['title']); ?>
            </h2>
            <a
              href="<?php echo esc_url($consultation['button_href']); ?>"
              class="mt-5 inline-flex min-h-4 items-center justify-center self-center border border-white/80 px-8 py-2 font-futuraBk text-[14px] font-normal capitalize leading-[1] text-white transition hover:bg-white hover:text-eh-ink sm:mt-6 sm:px-12"
            >
              <?php echo esc_html($consultation['button_text']); ?>
            </a>
          </div>
        </div>
      </div>
    </section>
  </main>
