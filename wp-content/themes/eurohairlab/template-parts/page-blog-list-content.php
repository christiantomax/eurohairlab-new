<?php

declare(strict_types=1);

/**
 * Blog listing — Figma frame Blog (4035:722). Typography via Framelink MCP + diagnosis tokens:
 * hero Futura Hv 64px/1em, filters Futura Bk 18px/120%, card titles Futura Hv 24px/1em, excerpts Futura Bk 18px/120%.
 *
 * Card copy and images come from the post “Blog (front-end)” metabox. No placeholder fallbacks.
 * Category filter uses real terms only; if there are none, the filter row is omitted.
 */

$page_id = (int) get_queried_object_id();
$mb_get = static function (string $key) use ($page_id) {
    if (!$page_id || !function_exists('eurohairlab_rwmb_page_meta')) {
        return null;
    }

    return eurohairlab_rwmb_page_meta($page_id, $key, []);
};
$blog_list_url = get_permalink($page_id);
$raw_title = $mb_get('eh_blog_list_page_title');
$page_title = is_string($raw_title) ? trim($raw_title) : '';

$selected_category = isset($_GET['blog-category']) ? sanitize_title(wp_unslash((string) $_GET['blog-category'])) : 'all';
if ($selected_category !== 'all') {
    $term = get_term_by('slug', $selected_category, 'category');
    if (!$term instanceof WP_Term) {
        $selected_category = 'all';
    }
}

$blog_categories = get_terms([
    'taxonomy' => 'category',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC',
]);
$category_filters = [];
if (!is_wp_error($blog_categories) && $blog_categories !== []) {
    $category_filters[] = ['label' => __('All', 'eurohairlab'), 'slug' => 'all'];
    foreach ($blog_categories as $term) {
        if (!$term instanceof WP_Term) {
            continue;
        }
        $category_filters[] = [
            'label' => function_exists('eurohairlab_get_category_display_name') ? eurohairlab_get_category_display_name($term) : $term->name,
            'slug' => $term->slug,
        ];
    }
}

$query_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'ignore_sticky_posts' => true,
];

if ($selected_category !== 'all') {
    $query_args['tax_query'] = [
        [
            'taxonomy' => 'category',
            'field' => 'slug',
            'terms' => $selected_category,
        ],
    ];
}

$posts_query = new WP_Query($query_args);

$cards = [];
if ($posts_query->have_posts()) {
    while ($posts_query->have_posts()) {
        $posts_query->the_post();
        $pid = get_the_ID();
        $index = count($cards);
        $is_wide_slot = ($index % 7) === 0;
        $cover = eurohairlab_get_blog_post_image_url($pid, 'eh_blog_image_cover');
        $thumb = eurohairlab_get_blog_post_image_url($pid, 'eh_blog_image_thumbnail');
        $image = $is_wide_slot ? $cover : $thumb;
        $cards[] = [
            'title' => eurohairlab_get_blog_post_display_title($pid),
            'excerpt' => eurohairlab_get_blog_post_description($pid),
            'image' => $image,
            'url' => get_permalink($pid),
        ];
    }
    wp_reset_postdata();
}

$card_groups = array_chunk($cards, 7);
?>
<main id="main-content" class="bg-white text-eh-ink antialiased">
  <section class="bg-white py-28 sm:py-32 lg:py-[11.5rem]">
    <div class="px-4 sm:px-5 lg:px-20">
      <?php if ($page_title !== '') : ?>
      <header class="reveal text-center">
        <h1 class="font-futuraHv text-3xl font-normal uppercase leading-none text-eh-ink sm:text-4xl md:text-5xl lg:text-[64px]"><?php echo esc_html($page_title); ?></h1>
      </header>
      <?php endif; ?>

      <?php if ($category_filters !== []) : ?>
      <nav class="reveal blog-filter-rail mt-7 border-b border-[#bababa] pb-3" aria-label="<?php echo esc_attr__('Blog categories', 'eurohairlab'); ?>">
        <ul class="blog-filter-rail__list mx-auto flex min-w-max items-center justify-center gap-5 sm:gap-6 lg:gap-8" data-blog-filter-slider>
          <?php foreach ($category_filters as $filter) : ?>
            <?php
            $is_active = $selected_category === $filter['slug'];
            $filter_url = add_query_arg('blog-category', $filter['slug'], $blog_list_url);
            if ($filter['slug'] === 'all') {
                $filter_url = remove_query_arg('blog-category', $blog_list_url);
            }
            ?>
            <li class="blog-filter-rail__item">
              <a href="<?php echo esc_url($filter_url); ?>" class="blog-filter-link font-futuraBk text-[18px] font-normal capitalize leading-[120%] <?php echo $is_active ? 'is-active' : ''; ?>">
                <?php echo esc_html($filter['label']); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <?php endif; ?>

      <div class="mt-6 space-y-3">
        <?php foreach ($card_groups as $group_index => $group) : ?>
          <?php
          $feature_cards = array_slice($group, 0, 3);
          $big_card = $feature_cards[0] ?? null;
          $small_cards = array_slice($feature_cards, 1);
          $regular_cards = array_slice($group, 3, 4);
          $big_on_left = ($group_index % 2) === 0;
          ?>
          <div class="space-y-3">
            <div class="grid gap-3 lg:grid-cols-2">
              <?php if ($big_on_left && $big_card) : ?>
                <article class="reveal border border-[#bababa] bg-white">
                  <?php if ($big_card['image'] !== '') : ?>
                  <a href="<?php echo esc_url($big_card['url']); ?>" class="block overflow-hidden">
                    <img
                      src="<?php echo esc_url($big_card['image']); ?>"
                      alt="<?php echo esc_attr($big_card['title']); ?>"
                      class="h-[15rem] w-full object-cover object-center sm:h-[18rem] lg:h-[23.5rem]"
                      width="634"
                      height="308"
                      loading="<?php echo $group_index === 0 ? 'eager' : 'lazy'; ?>"
                      decoding="async"
                    >
                  </a>
                  <?php endif; ?>
                  <div class="p-5 lg:p-6">
                    <?php if ($big_card['title'] !== '') : ?>
                    <h2 class="mb-4 font-futuraHv text-2xl font-normal capitalize leading-none text-eh-ink lg:mb-5">
                      <a href="<?php echo esc_url($big_card['url']); ?>" class="transition hover:text-eh-coral"><?php echo esc_html($big_card['title']); ?></a>
                    </h2>
                    <?php endif; ?>
                    <?php if ($big_card['excerpt'] !== '') : ?>
                    <p class="overflow-hidden font-futuraBk text-[18px] font-normal leading-[120%] text-eh-ink [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:3] lg:[-webkit-line-clamp:4]"><?php echo esc_html($big_card['excerpt']); ?></p>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endif; ?>

              <div class="space-y-3">
                <?php foreach ($small_cards as $small_index => $card) : ?>
                  <article class="reveal border border-[#bababa] bg-white lg:grid lg:grid-cols-[19rem_minmax(0,1fr)]">
                    <?php if ($card['image'] !== '') : ?>
                    <a href="<?php echo esc_url($card['url']); ?>" class="block overflow-hidden">
                      <img
                        src="<?php echo esc_url($card['image']); ?>"
                        alt="<?php echo esc_attr($card['title']); ?>"
                        class="h-[19.5rem] w-full object-cover object-center lg:h-[23.5rem]"
                        width="246"
                        height="246"
                        loading="<?php echo ($group_index === 0 && $small_index === 0) ? 'eager' : 'lazy'; ?>"
                        decoding="async"
                      >
                    </a>
                    <?php endif; ?>
                    <div class="p-5">
                      <?php if ($card['title'] !== '') : ?>
                      <h2 class="mb-3 font-futuraHv text-2xl font-normal capitalize leading-none text-eh-ink lg:mb-4">
                        <a href="<?php echo esc_url($card['url']); ?>" class="transition hover:text-eh-coral"><?php echo esc_html($card['title']); ?></a>
                      </h2>
                      <?php endif; ?>
                      <?php if ($card['excerpt'] !== '') : ?>
                      <p class="overflow-hidden font-futuraBk text-[18px] font-normal leading-[120%] text-eh-ink [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:3]"><?php echo esc_html($card['excerpt']); ?></p>
                      <?php endif; ?>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>

              <?php if (!$big_on_left && $big_card) : ?>
                <article class="reveal border border-[#bababa] bg-white lg:col-start-2 lg:row-start-1">
                  <?php if ($big_card['image'] !== '') : ?>
                  <a href="<?php echo esc_url($big_card['url']); ?>" class="block overflow-hidden">
                    <img
                      src="<?php echo esc_url($big_card['image']); ?>"
                      alt="<?php echo esc_attr($big_card['title']); ?>"
                      class="h-[15rem] w-full object-cover object-center sm:h-[18rem] lg:h-[23.5rem]"
                      width="634"
                      height="308"
                      loading="lazy"
                      decoding="async"
                    >
                  </a>
                  <?php endif; ?>
                  <div class="p-5 lg:p-6">
                    <?php if ($big_card['title'] !== '') : ?>
                    <h2 class="mb-4 font-futuraHv text-2xl font-normal capitalize leading-none text-eh-ink lg:mb-5">
                      <a href="<?php echo esc_url($big_card['url']); ?>" class="transition hover:text-eh-coral"><?php echo esc_html($big_card['title']); ?></a>
                    </h2>
                    <?php endif; ?>
                    <?php if ($big_card['excerpt'] !== '') : ?>
                    <p class="overflow-hidden font-futuraBk text-[18px] font-normal leading-[120%] text-eh-ink [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:3] lg:[-webkit-line-clamp:4]"><?php echo esc_html($big_card['excerpt']); ?></p>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endif; ?>
            </div>

            <?php if (!empty($regular_cards)) : ?>
              <div class="grid gap-3 lg:grid-cols-2">
                <?php foreach ($regular_cards as $regular_index => $card) : ?>
                  <article class="reveal border border-[#bababa] bg-white lg:grid lg:grid-cols-[19rem_minmax(0,1fr)]">
                    <?php if ($card['image'] !== '') : ?>
                    <a href="<?php echo esc_url($card['url']); ?>" class="block overflow-hidden">
                      <img
                        src="<?php echo esc_url($card['image']); ?>"
                        alt="<?php echo esc_attr($card['title']); ?>"
                        class="h-[19.5rem] w-full object-cover object-center lg:h-[19.5rem]"
                        width="246"
                        height="246"
                        loading="lazy"
                        decoding="async"
                      >
                    </a>
                    <?php endif; ?>
                    <div class="p-5">
                      <?php if ($card['title'] !== '') : ?>
                      <h2 class="mb-3 font-futuraHv text-2xl font-normal capitalize leading-none text-eh-ink lg:mb-4">
                        <a href="<?php echo esc_url($card['url']); ?>" class="transition hover:text-eh-coral"><?php echo esc_html($card['title']); ?></a>
                      </h2>
                      <?php endif; ?>
                      <?php if ($card['excerpt'] !== '') : ?>
                      <p class="overflow-hidden font-futuraBk text-[18px] font-normal leading-[120%] text-eh-ink [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:3]"><?php echo esc_html($card['excerpt']); ?></p>
                      <?php endif; ?>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
