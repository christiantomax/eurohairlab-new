<?php

declare(strict_types=1);

/**
 * Promo — hero from page Meta Box; rows from CPT `eh_promo`.
 */

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
    }

    return $fallback;
};
$hero = [
    'image' => $resolve_image($mb_get('eh_promo_hero_image'), ''),
    'title' => trim((string) ($mb_get('eh_promo_hero_title') ?? '')),
];
$show_hero = $hero['image'] !== '' || $hero['title'] !== '';

$promo_items = [];
$pq = new WP_Query(
    [
        'post_type' => 'eh_promo',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'no_found_rows' => true,
    ]
);

if ($pq->have_posts()) {
    while ($pq->have_posts()) {
        $pq->the_post();
        $pid = get_the_ID();
        $thumb = get_the_post_thumbnail_url($pid, 'full');
        $img = is_string($thumb) && $thumb !== '' ? $thumb : '';
        $desc = function_exists('rwmb_meta') ? rwmb_meta('eh_promo_item_description', [], $pid) : '';
        $desc_html = is_string($desc) ? $desc : '';
        $btn_label = function_exists('rwmb_meta') ? rwmb_meta('eh_promo_item_button_label', [], $pid) : '';
        if (!is_string($btn_label) || trim($btn_label) === '') {
            $btn_label = 'View More';
        }
        $btn_href_raw = function_exists('rwmb_meta') ? rwmb_meta('eh_promo_item_button_href', [], $pid) : '';
        $btn_href_raw = is_string($btn_href_raw) ? trim($btn_href_raw) : '';
        $btn_url = $btn_href_raw !== '' ? eurohairlab_resolve_marketing_href($btn_href_raw, '') : eurohairlab_get_primary_cta_url();

        $promo_items[] = [
            'title' => get_the_title(),
            'body_html' => $desc_html,
            'image' => $img,
            'alt' => get_the_title(),
            'button_label' => $btn_label,
            'button_href' => $btn_url,
        ];
    }
    wp_reset_postdata();
}
?>
<main id="main-content" class="bg-white text-eh-ink antialiased">
  <?php if ($show_hero) : ?>
  <section class="relative overflow-hidden bg-white pt-28 sm:pt-32 lg:pt-[6rem]">
    <?php if ($hero['image'] !== '') : ?>
    <div class="relative">
      <img
        src="<?php echo esc_url($hero['image']); ?>"
        alt=""
        class="reveal reveal--hero h-[15.6rem] w-full object-cover object-center sm:h-[20rem] lg:h-[31.2rem]"
        width="1440"
        height="499"
        fetchpriority="high"
        decoding="async"
      >
      <?php if ($hero['title'] !== '') : ?>
      <div class="reveal reveal--hero absolute inset-0 flex items-center justify-center">
        <h1 class="px-4 text-center font-futuraHv text-3xl font-normal capitalize leading-none text-eh-ink sm:text-4xl md:text-5xl lg:text-[64px]"><?php echo esc_html($hero['title']); ?></h1>
      </div>
      <?php endif; ?>
    </div>
    <?php elseif ($hero['title'] !== '') : ?>
    <div class="reveal flex justify-center px-4 py-16 sm:py-20 lg:py-28">
      <h1 class="text-center font-futuraHv text-3xl font-normal capitalize leading-none text-eh-ink sm:text-4xl md:text-5xl lg:text-[64px]"><?php echo esc_html($hero['title']); ?></h1>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($promo_items !== []) : ?>
  <section class="bg-white">
    <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
      <div class="divide-y divide-eh-ink/10 border-y border-eh-ink/10">
        <?php foreach ($promo_items as $index => $item) : ?>
          <?php
            $item_has_image = $item['image'] !== '';
            $article_grid = $item_has_image
                ? 'lg:grid-cols-[30rem_minmax(0,1fr)]'
                : 'lg:grid-cols-1';
            ?>
          <article class="grid gap-8 py-10 sm:gap-10 <?php echo esc_attr($article_grid); ?> lg:items-start lg:gap-16 lg:py-10">
            <?php if ($item_has_image) : ?>
            <figure class="reveal h-[18rem] overflow-hidden sm:h-[24rem] lg:h-[31.2rem]">
              <img
                src="<?php echo esc_url($item['image']); ?>"
                alt="<?php echo esc_attr($item['alt']); ?>"
                class="h-full w-full object-cover object-center"
                width="486"
                height="640"
                loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                decoding="async"
              >
            </figure>
            <?php endif; ?>
            <div class="reveal lg:pt-3">
              <h2 class="max-w-[20ch] font-futuraHv text-3xl font-normal capitalize leading-none text-eh-ink sm:max-w-none sm:text-4xl md:text-5xl lg:max-w-[15ch] lg:text-[64px]">
                <?php echo esc_html($item['title']); ?>
              </h2>
              <div class="mt-6 max-w-[41rem] font-futuraBk text-[18px] font-normal leading-[120%] text-eh-ink">
                <?php echo wp_kses_post($item['body_html']); ?>
              </div>
              <a href="<?php echo esc_url($item['button_href']); ?>" class="mt-8 inline-flex font-futuraHv text-2xl font-normal capitalize leading-none text-eh-coral transition hover:text-eh-ink">
                <?php echo esc_html($item['button_label']); ?>
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
</main>
