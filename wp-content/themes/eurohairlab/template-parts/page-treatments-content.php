<?php

declare(strict_types=1);

/**
 * Treatments landing — hero from page Meta Box; programs from CPT `eh_treatment_program`.
 */

$theme_uri = esc_url(get_template_directory_uri());
$figma_uri = $theme_uri . '/assets/images/figma';

$page_id = get_queried_object_id();

$hero_image = $figma_uri . '/figma-treatment-hero.webp';
$hero_title = 'HAIR TREATMENT PROGRAM';
$hero_paragraph_html = '<p>A comprehensive, clinically structured hair and scalp care program built on a diagnose-first approach. Every treatment begins with a detailed scalp analysis to identify root causes before selecting the most precise solution. Combining advanced Korean techniques with the SCALPFIRST™ System, this program delivers targeted care from scalp correction to long-term hair regeneration and maintenance.</p>';

if ($page_id && function_exists('eurohairlab_rwmb_page_meta')) {
    $hero_image = eurohairlab_mb_image_url(eurohairlab_rwmb_page_meta($page_id, 'eh_treatments_hero_image', []), $hero_image);
    $t = eurohairlab_rwmb_page_meta($page_id, 'eh_treatments_hero_title', []);
    if (is_string($t) && $t !== '') {
        $hero_title = $t;
    }
    $p = eurohairlab_rwmb_page_meta($page_id, 'eh_treatments_hero_paragraph', []);
    if (is_string($p) && $p !== '') {
        $hero_paragraph_html = $p;
    }
}

$programs = [];
$q = new WP_Query(
    [
        'post_type' => 'eh_treatment_program',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'no_found_rows' => true,
    ]
);

if ($q->have_posts()) {
    while ($q->have_posts()) {
        $q->the_post();
        $pid = get_the_ID();
        $slug = get_post_field('post_name', $pid);
        $anchor = $slug !== '' ? $slug : 'program-' . (string) $pid;
        if (!str_starts_with($anchor, 'program-')) {
            $anchor = 'program-' . $anchor;
        }
        $thumb = get_the_post_thumbnail_url($pid, 'full');
        $img = is_string($thumb) && $thumb !== '' ? $thumb : $figma_uri . '/treatment-program-1.webp';
        $detail = function_exists('eurohairlab_rwmb_page_meta') ? eurohairlab_rwmb_page_meta($pid, 'eh_tp_detail_includes', []) : '';
        $detail_html = is_string($detail) ? $detail : '';
        $para = function_exists('eurohairlab_rwmb_page_meta') ? eurohairlab_rwmb_page_meta($pid, 'eh_tp_paragraph', []) : '';
        $para_html = is_string($para) ? $para : '';
        $prog_title_raw = function_exists('eurohairlab_rwmb_page_meta') ? eurohairlab_rwmb_page_meta($pid, 'eh_tp_program_title', []) : '';
        $prog_title = is_string($prog_title_raw) && trim($prog_title_raw) !== '' ? trim($prog_title_raw) : get_the_title();
        $programs[] = [
            'id' => $anchor,
            'title' => $prog_title,
            'image' => $img,
            'copy_html' => $para_html,
            'detail_html' => $detail_html,
        ];
    }
    wp_reset_postdata();
}

if ($programs === []) {
    $programs = [
        [
            'id' => 'program-korean',
            'title' => 'Korean Scalp Ritual',
            'image' => $figma_uri . '/treatment-program-1.webp',
            'copy_html' => '<p>A foundational treatment series focused on deep cleansing and scalp reset using advanced Korean techniques.</p>',
            'detail_html' => '<p class="underline underline-offset-4">Includes:</p><ul class="mt-1 list-none space-y-0.5 p-0"><li>Scalp Detox</li><li>Scalp Revival</li></ul>',
        ],
        [
            'id' => 'program-scalpfirst',
            'title' => 'ScalpFirst™ Therapy',
            'image' => $figma_uri . '/treatment-program-2.webp',
            'copy_html' => '<p>A targeted therapy range designed to restore and balance specific scalp conditions with clinical precision.</p>',
            'detail_html' => '<p class="underline underline-offset-4">Includes:</p><ul class="mt-1 list-none space-y-0.5 p-0"><li>Scalp Balance</li><li>Scalp Relief</li><li>Scalp Calm</li><li>Scalp Defense</li></ul>',
        ],
        [
            'id' => 'program-regan',
            'title' => 'Hair Regan Protocol',
            'image' => $figma_uri . '/treatment-technology.webp',
            'copy_html' => '<p>A clinical-grade protocol designed to treat hair loss at the root and support long-term regeneration based on diagnosis.</p>',
            'detail_html' => '<p class="underline underline-offset-4">Includes:</p><ul class="mt-1 list-none space-y-0.5 p-0"><li>ReGen Activ</li><li>ReGen Clear</li><li>ReGen Boost</li></ul>',
        ],
        [
            'id' => 'program-booster',
            'title' => 'Regan Booster',
            'image' => $figma_uri . '/treatment-program-3.webp',
            'copy_html' => '<p>A targeted therapy range designed to restore and balance specific scalp conditions with clinical precision.</p>',
            'detail_html' => '<p class="underline underline-offset-4">Includes:</p><ul class="mt-1 list-none space-y-0.5 p-0"><li>Booster Exoscalp</li><li>Booster Secretome</li></ul>',
        ],
    ];
}

$tabs = [];
foreach ($programs as $program) {
    $tabs[] = [
        'label' => $program['title'],
        'href' => '#' . $program['id'],
    ];
}
?>
<main id="main-content" class="bg-white text-eh-ink antialiased">
  <section class="relative isolate overflow-hidden bg-[#2f251c] pt-[125px]">
    <div class="absolute inset-0">
      <img
        src="<?php echo esc_url($hero_image); ?>"
        alt="Hair treatment program hero"
        class="h-full w-full object-cover object-center brightness-[0.58]"
        width="1920"
        height="540"
        fetchpriority="high"
        decoding="async"
      >
      <div class="absolute inset-0 bg-black/42"></div>
    </div>

    <div class="relative mx-auto flex h-[503px] w-full max-w-[90rem] items-center justify-center px-4 py-16 sm:px-6 lg:px-10">
      <div class="reveal reveal--hero max-w-[61rem] text-center">
        <h1 class="font-display text-[2rem] font-bold uppercase leading-none text-white">
          <?php echo nl2br(esc_html($hero_title)); ?>
        </h1>
        <div class="mx-auto mt-6 max-w-[61rem] text-center font-futuraBk text-[14px] font-normal leading-[1] text-white drop-shadow-[0_1px_6px_rgba(0,0,0,0.25)]">
          <?php echo wp_kses_post($hero_paragraph_html); ?>
        </div>
      </div>
    </div>
  </section>

  <section class="relative z-30 -mt-4 px-4 sm:px-5 lg:-mt-6 lg:px-20">
    <div class="reveal bg-[#d5bba0] shadow-[0_10px_30px_rgba(0,0,0,0.08)]">
      <div class="treatments-mobile-nav lg:hidden px-4 py-6 text-eh-ink flex >
        <span class="treatments-mobile-nav__label font-futuraBk text-[14px] font-normal leading-[1]">Treatmen</span>
        <div class="treatments-mobile-nav__select-wrap w-full">
          <label for="treatments-mobile-select" class="sr-only"><?php echo esc_html__('Choose treatment', 'eurohairlab'); ?></label>
          <select
            id="treatments-mobile-select"
            class="treatments-mobile-nav__select font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink"
            data-treatment-anchor-select
          >
            <?php foreach ($tabs as $index => $tab) : ?>
              <option value="<?php echo esc_attr($tab['href']); ?>" <?php selected($index === 0); ?>>
                <?php echo esc_html($tab['label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="hidden items-center gap-4 py-2 text-eh-ink sm:px-8 lg:grid" style="grid-template-columns: 11rem repeat(<?php echo (int) max(count($tabs), 1); ?>, minmax(0, 1fr));">
        <span class="border-r-2 border-white/45 py-4 text-center font-futuraBk text-[14px] font-normal leading-[1]">Treatments :</span>
        <?php foreach ($tabs as $index => $tab) : ?>
          <div class="flex justify-center <?php echo $index === 0 ? 'pl-10' : 'pl-4'; ?>">
            <a
              href="<?php echo esc_url($tab['href']); ?>"
              class="nav-link-animated inline-block font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink transition-colors hover:text-black"
            >
              <?php echo esc_html($tab['label']); ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php foreach ($programs as $index => $program) : ?>
    <?php $reverse = $index % 2 === 1; ?>
    <section id="<?php echo esc_attr($program['id']); ?>" class="bg-white px-4 py-16 sm:px-5 lg:px-20 lg:py-24">
      <div class="grid w-full gap-8 lg:items-center <?php echo $reverse ? 'lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]' : 'lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]'; ?>">
        <?php if (!$reverse) : ?>
          <figure class="reveal overflow-hidden rounded-[0.35rem]">
            <img
              src="<?php echo esc_url($program['image']); ?>"
              alt="<?php echo esc_attr($program['title']); ?>"
              class="h-[22rem] w-full object-cover object-center sm:h-[26rem] lg:h-[36rem]"
              width="900"
              height="900"
              loading="lazy"
              decoding="async"
            >
          </figure>
        <?php endif; ?>

        <div class="order-2 lg:order-2 reveal max-w-[34rem] <?php echo $reverse ? 'lg:justify-self-start' : 'lg:pl-4'; ?>">
          <h2 class="font-futuraHv text-[2rem] font-normal uppercase leading-none text-eh-ink">
            <?php echo esc_html($program['title']); ?>
          </h2>
          <div class="mt-6 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink">
            <?php echo wp_kses_post($program['copy_html']); ?>
          </div>
          <?php if (trim($program['detail_html']) !== '') : ?>
            <div class="mt-5 font-futuraBk text-[14px] font-normal leading-[1] text-eh-ink">
              <?php echo wp_kses_post($program['detail_html']); ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($reverse) : ?>
          <figure class="order-1 lg:order-2 reveal overflow-hidden rounded-[0.35rem]">
            <img
              src="<?php echo esc_url($program['image']); ?>"
              alt="<?php echo esc_attr($program['title']); ?>"
              class="h-[22rem] w-full object-cover object-center sm:h-[26rem] lg:h-[36rem]"
              width="900"
              height="900"
              loading="lazy"
              decoding="async"
            >
          </figure>
        <?php endif; ?>
      </div>
    </section>
  <?php endforeach; ?>
</main>
