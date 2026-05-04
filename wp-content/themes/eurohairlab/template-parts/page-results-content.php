<?php

declare(strict_types=1);

/**
 * Results landing — hero from page Meta Box; cards from CPT `eh_result`.
 */

$theme_uri = esc_url(get_template_directory_uri());
$figma_uri = $theme_uri . '/assets/images/figma';

$page_id = get_queried_object_id();

$hero_image = $figma_uri . '/figma-results-hero.webp';
$hero_title = '3 Million Cases Worldwide';
$hero_paragraph_html = '<p>EUROHAIRLAB by DR. SCALP has helped over 3 million people around the world take control of their hair health. With our ScalpFirst™ philosophy and diagnosis-first approach, every treatment is tailored to optimize your scalp environment and deliver results that last. Experience the care and expertise trusted by millions.</p>';

if ($page_id && function_exists('eurohairlab_rwmb_page_meta')) {
    $hero_image = eurohairlab_mb_image_url(eurohairlab_rwmb_page_meta($page_id, 'eh_results_hero_image', []), $hero_image);
    $t = eurohairlab_rwmb_page_meta($page_id, 'eh_results_hero_title', []);
    if (is_string($t) && $t !== '') {
        $hero_title = $t;
    }
    $p = eurohairlab_rwmb_page_meta($page_id, 'eh_results_hero_paragraph', []);
    if (is_string($p) && $p !== '') {
        $hero_paragraph_html = $p;
    }
}

$result_cards = [];
$eh_result_card_string = static function (mixed $v): string {
    if (is_string($v)) {
        return $v;
    }
    if (is_int($v) || is_float($v) || $v instanceof \Stringable) {
        return (string) $v;
    }

    return '';
};
$q = new WP_Query(
    [
        'post_type' => 'eh_result',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'no_found_rows' => false,
    ]
);

if ($q->have_posts()) {
    while ($q->have_posts()) {
        $q->the_post();
        $pid = get_the_ID();
        $gallery_ids = function_exists('rwmb_meta') ? rwmb_meta('eh_result_gallery', [], $pid) : [];
        if (!is_array($gallery_ids)) {
            $gallery_ids = [];
        }
        $urls = [];
        foreach ($gallery_ids as $gal_entry) {
            $aid = 0;
            if (is_numeric($gal_entry)) {
                $aid = (int) $gal_entry;
            } elseif (is_array($gal_entry) && isset($gal_entry['ID']) && is_numeric($gal_entry['ID'])) {
                $aid = (int) $gal_entry['ID'];
            }
            if ($aid <= 0) {
                continue;
            }
            $u = wp_get_attachment_image_url($aid, 'full');
            if (is_string($u) && $u !== '') {
                $urls[] = $u;
            }
        }
        $before = $urls[0] ?? ($figma_uri . '/results-1-before.webp');
        $after = isset($urls[1]) ? $urls[1] : $before;
        $mb = static function (string $key) use ($pid): mixed {
            return function_exists('eurohairlab_rwmb_page_meta') ? eurohairlab_rwmb_page_meta($pid, $key, []) : null;
        };
        $card_title = $mb('eh_result_card_title');
        if (!is_string($card_title) || trim($card_title) === '') {
            $legacy_meta_line = $mb('eh_result_meta_line');
            $fallback_title = get_the_title();
            $card_title = is_string($legacy_meta_line) && trim($legacy_meta_line) !== ''
                ? $legacy_meta_line
                : (is_string($fallback_title) ? $fallback_title : '');
        }
        $short_description = $mb('eh_result_short_description');
        if (!is_string($short_description) || trim($short_description) === '') {
            $legacy_subtitle = $mb('eh_result_subtitle');
            $short_description = is_string($legacy_subtitle) ? trim($legacy_subtitle) : '';
        }
        $testimonial = $mb('eh_result_testimonial');
        if (!is_string($testimonial) || trim($testimonial) === '') {
            $legacy_paragraph = $mb('eh_result_paragraph');
            $testimonial = is_string($legacy_paragraph) ? wp_strip_all_tags($legacy_paragraph) : '';
        }
        $subtitle = $mb('eh_result_subtitle');
        if (!is_string($subtitle) || trim($subtitle) === '') {
            $legacy_category = function_exists('rwmb_meta') ? rwmb_meta('eh_result_category', [], $pid) : '';
            $subtitle = is_string($legacy_category) && trim($legacy_category) !== '' ? $legacy_category : 'Case Studies';
        }
        $sub_description = $mb('eh_result_sub_description');
        if (!is_string($sub_description) || trim($sub_description) === '') {
            $legacy_paragraph_sub = $mb('eh_result_paragraph_subtitle');
            $sub_description = is_string($legacy_paragraph_sub) ? wp_strip_all_tags($legacy_paragraph_sub) : '';
        }

        $gallery_urls = $urls !== [] ? $urls : [$before, $after];

        $result_cards[] = [
            'before' => is_string($before) ? $before : '',
            'after' => is_string($after) ? $after : '',
            'gallery' => $gallery_urls,
            'card_title' => $eh_result_card_string($card_title),
            'short_description' => $eh_result_card_string($short_description),
            'testimonial' => trim($eh_result_card_string($testimonial)),
            'subtitle' => $eh_result_card_string($subtitle),
            'sub_description' => trim($eh_result_card_string($sub_description)),
        ];
    }
    wp_reset_postdata();
}

if ($result_cards === []) {
    $result_cards = [
        [
            'before' => $figma_uri . '/results-1-before.webp',
            'after' => $figma_uri . '/results-1-after.webp',
            'gallery' => [$figma_uri . '/results-1-before.webp', $figma_uri . '/results-1-after.webp'],
            'card_title' => 'Female, 40s',
            'short_description' => '4 Months on Extract',
            'testimonial' => 'I had severe hair thinning after stress and hormonal imbalance. After the program, my hair density improved significantly.',
            'subtitle' => 'Case Studies',
            'sub_description' => 'This case study highlights the importance of diagnosis-first treatment and consistent scalp follow-up.',
        ],
        [
            'before' => $figma_uri . '/results-2-before.webp',
            'after' => $figma_uri . '/results-2-after.webp',
            'gallery' => [$figma_uri . '/results-2-before.webp', $figma_uri . '/results-2-after.webp'],
            'card_title' => 'Female, 25',
            'short_description' => '4 Months on Supplements & Extract',
            'testimonial' => 'The scalp-first diagnosis helped me understand the real cause of my thinning and the results followed.',
            'subtitle' => 'Case Studies',
            'sub_description' => 'A structured plan makes it easier to track progress and maintain momentum.',
        ],
        [
            'before' => $figma_uri . '/results-3-before.webp',
            'after' => $figma_uri . '/results-3-after.webp',
            'gallery' => [$figma_uri . '/results-3-before.webp', $figma_uri . '/results-3-after.webp'],
            'card_title' => 'Female, 40s',
            'short_description' => '4 Months on Extract',
            'testimonial' => 'My shedding reduced gradually and the density along my hairline started to look fuller.',
            'subtitle' => 'Case Studies',
            'sub_description' => 'Visible changes are supported by ongoing scalp correction and program adjustment.',
        ],
    ];
}

$result_count = count($result_cards);
?>
<main id="main-content" class="bg-white text-eh-ink antialiased">
  <section class="relative isolate overflow-hidden bg-eh-panel pt-[125px]" aria-labelledby="results-hero-heading">
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
      <div class="absolute inset-0 bg-black/[0.51]"></div>
    </div>

    <div class="relative mx-auto flex min-h-[503px] w-full max-w-[90rem] items-center justify-center px-4 py-16 sm:px-6 lg:px-10">
      <div class="reveal reveal--hero max-w-[61rem] text-center">
        <h1 id="results-hero-heading" class="font-display text-[2rem] font-bold uppercase leading-none text-white">
          <?php echo nl2br(esc_html($hero_title)); ?>
        </h1>
        <div class="mx-auto mt-6 max-w-[61rem] text-center font-futuraBk text-[14px] font-normal leading-[1] text-white">
          <?php echo wp_kses_post($hero_paragraph_html); ?>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-white px-4 py-14 sm:px-6 lg:px-10 lg:py-16">
    <div class="mx-auto w-full max-w-[90rem]">
      <div class="reveal border-b border-eh-sand-num pb-4">
        <p class="font-sans text-[14px] font-light uppercase leading-[120%] tracking-normal text-eh-ink"><?php echo esc_html(sprintf(__('%d Results', 'eurohairlab'), $result_count)); ?></p>
      </div>

      <div class="mt-8 grid gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($result_cards as $index => $card) : ?>
          <?php
          $gallery_for_attr = isset($card['gallery']) && is_array($card['gallery']) ? $card['gallery'] : [$card['before'], $card['after']];
          $gallery_json = wp_json_encode(array_values($gallery_for_attr), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
          ?>
          <article class="reveal">
            <button
              type="button"
              class="result-card-trigger block w-full text-left"
              data-result-index="<?php echo esc_attr((string) $index); ?>"
              data-result-before="<?php echo esc_attr($card['before']); ?>"
              data-result-after="<?php echo esc_attr($card['after']); ?>"
              data-result-gallery="<?php echo esc_attr($gallery_json); ?>"
              data-result-card-title="<?php echo esc_attr($card['card_title']); ?>"
              data-result-short-description="<?php echo esc_attr($card['short_description']); ?>"
              data-result-testimonial="<?php echo esc_attr($card['testimonial']); ?>"
              data-result-subtitle="<?php echo esc_attr($card['subtitle']); ?>"
              data-result-sub-description="<?php echo esc_attr($card['sub_description']); ?>"
            >
              <div class="overflow-hidden bg-[#f3f1ee]">
                <div class="relative grid h-[19rem] grid-cols-2 lg:h-[21rem]">
                  <figure class="relative h-full">
                    <img
                      src="<?php echo esc_url($card['before']); ?>"
                      alt="<?php echo esc_attr(sprintf(__('Before — %s', 'eurohairlab'), $card['card_title'])); ?>"
                      class="h-full w-full object-cover object-center"
                      width="420"
                      height="560"
                      loading="<?php echo $index < 6 ? 'eager' : 'lazy'; ?>"
                      decoding="async"
                    >
                    <figcaption class="pointer-events-none absolute bottom-4 left-4 font-display text-2xl font-bold uppercase leading-none text-white"><?php echo esc_html__('Before', 'eurohairlab'); ?></figcaption>
                  </figure>
                  <figure class="relative h-full">
                    <img
                      src="<?php echo esc_url($card['after']); ?>"
                      alt="<?php echo esc_attr(sprintf(__('After — %s', 'eurohairlab'), $card['card_title'])); ?>"
                      class="h-full w-full object-cover object-center"
                      width="420"
                      height="560"
                      loading="<?php echo $index < 6 ? 'eager' : 'lazy'; ?>"
                      decoding="async"
                    >
                    <figcaption class="pointer-events-none absolute bottom-4 left-4 font-display text-2xl font-bold uppercase leading-none text-white"><?php echo esc_html__('After', 'eurohairlab'); ?></figcaption>
                  </figure>
                </div>
              </div>
              <div class="border-b border-eh-sand-num bg-white px-4 py-4 text-left flex justify-between justify-between items-end">
   <div>
                    <p class="font-futuraHv text-[1rem] font-normal leading-[1] text-eh-ink"><?php echo esc_html($card['card_title']); ?></p>
                  <?php if ($card['short_description'] !== '') : ?>
                    <p class="mt-2 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink"><?php echo esc_html($card['short_description']); ?></p>
                  <?php endif; ?>
                </div>
                <span class="mt-4 inline-block font-sans text-xs font-bold uppercase leading-[120%] text-eh-sand-num">See More</span>
              </div>
            </button>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <div id="results-modal" class="fixed inset-0 z-50 hidden bg-black/70 p-4 backdrop-blur-sm sm:p-6">
    <div class="mx-auto flex min-h-full max-w-6xl items-center justify-center">
      <div class="flex max-h-[calc(100vh-2rem)] w-full flex-col overflow-hidden bg-white shadow-[0_24px_80px_rgba(0,0,0,0.28)] sm:max-h-[calc(100vh-3rem)]">
        <div class="flex-1 overflow-hidden p-4 sm:p-5 lg:p-6">
          <div class="grid h-full gap-0 overflow-hidden lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="relative grid h-fit lg:h-full grid-cols-2 bg-[#f3f1ee] sm:h-[28rem] lg:h-[31rem]">
              <figure class="relative h-fit lg:h-full">
                <img id="results-modal-before" src="" alt="Before result" class="h-fit lg:h-full w-full object-cover object-center">
                <figcaption class="absolute bottom-4 left-4 font-display text-2xl font-bold uppercase leading-none text-white">Before</figcaption>
              </figure>
              <figure class="relative h-fit lg:h-full">
                <img id="results-modal-after" src="" alt="After result" class="h-fit lg:h-full w-full object-cover object-center">
                <figcaption class="absolute bottom-4 left-4 font-display text-2xl font-bold uppercase leading-none text-white">After</figcaption>
              </figure>
              <button type="button" id="results-modal-image-prev" class="absolute left-4 top-1/2 flex h-14 w-14 -translate-y-1/2 items-center justify-center text-white/90 transition hover:text-white disabled:pointer-events-none disabled:opacity-35" aria-label="<?php echo esc_attr__('Previous images in this result', 'eurohairlab'); ?>">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                  <path d="M15 5 8 12l7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
                </svg>
              </button>
              <button type="button" id="results-modal-image-next" class="absolute right-4 top-1/2 flex h-14 w-14 -translate-y-1/2 items-center justify-center text-white/90 transition hover:text-white disabled:pointer-events-none disabled:opacity-35" aria-label="<?php echo esc_attr__('Next images in this result', 'eurohairlab'); ?>">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                  <path d="m9 5 7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
                </svg>
              </button>
            </div>

            <div class="flex min-h-[20rem] flex-col border-l border-eh-ink/10 px-5 py-5 sm:min-h-[28rem] sm:px-6 lg:min-h-[31rem] lg:px-5">
              <div class="flex-1 overflow-y-auto pr-1">
                <h3 id="results-modal-card-title" class="font-futuraHv text-[1rem] font-normal leading-none text-eh-ink sm:text-2xl lg:text-[28px]"></h3>
                <p id="results-modal-testimonial" class="mt-8 font-futuraBk text-[14px] font-normal italic leading-[1] text-eh-ink"></p>
                <p id="results-modal-subtitle" class="mt-8 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink underline underline-offset-4"></p>
                <p id="results-modal-sub-description" class="mt-8 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink"></p>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between border-t border-eh-ink/10 px-5 py-4 sm:px-6">
          <button type="button" id="results-modal-prev" class="font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink transition hover:text-eh-coral"><?php echo esc_html__('Previous', 'eurohairlab'); ?></button>
          <button type="button" id="results-modal-close" class="font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink transition hover:text-eh-coral"><?php echo esc_html__('Close', 'eurohairlab'); ?></button>
          <button type="button" id="results-modal-next" class="font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink transition hover:text-eh-coral"><?php echo esc_html__('Next', 'eurohairlab'); ?></button>
        </div>
      </div>
    </div>
  </div>
</main>
