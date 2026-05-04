<?php

declare(strict_types=1);

$theme_uri = esc_url(get_template_directory_uri());
$nav_items = eurohairlab_get_primary_nav_items();
$free_scalp_analysis_url = eurohairlab_resolve_free_scalp_analysis_href('');
$free_scalp_analysis_link_attrs = eurohairlab_free_scalp_analysis_link_attributes($free_scalp_analysis_url);
$is_homepage = is_front_page();

/** White bar + dark nav; desktop CTA hover = black bg / white text. */
$marketing_header_slugs = [
    'about',
    'diagnosis',
    'treatments',
    'treatment-programs',
    'results',
    'promo',
    'contact',
    'blog-list',
];
$is_blog_posts_index = is_home() && !is_front_page();
$is_marketing_header = is_page($marketing_header_slugs) || is_singular('post') || $is_blog_posts_index;

$header_text_class = $is_homepage ? 'text-white' : 'text-ink';
$header_bg_class = $is_homepage ? 'bg-transparent border-white' : 'bg-white border-black/10';
$logo_filter_class = $is_homepage ? '' : 'brightness-0';
$nav_list_class = $is_homepage ? 'text-white/82' : 'text-ink/82';
$cta_class = $is_homepage
    ? 'border-white/90 text-white hover:bg-black hover:text-white hover:border-black'
    : 'border-ink/85 text-ink hover:bg-black hover:text-white hover:border-black';
$toggle_class = $is_homepage
    ? 'text-white'
    : 'text-ink';

if ($is_marketing_header) {
    $header_text_class = 'text-ink';
    $header_bg_class = 'bg-white border-black/10';
    $logo_filter_class = 'brightness-0';
    $nav_list_class = 'text-ink';
    $cta_class = 'border-ink/85 text-ink hover:bg-black hover:text-white hover:border-black';
    $toggle_class = 'text-ink';
}
?>
<header id="site-header" data-force-dark-logo="<?php echo $is_homepage ? 'false' : 'true'; ?>" class="site-header fixed inset-x-0 top-0 z-50 border-b <?php echo esc_attr($header_bg_class); ?>">
  <div class="site-header__inner relative z-10 flex w-full items-center justify-between gap-3 px-4 py-5 <?php echo esc_attr($header_text_class); ?> sm:gap-4 sm:px-5 lg:grid lg:grid-cols-[1fr_auto_1fr] lg:items-center lg:gap-4 lg:px-20 lg:py-6">
    <nav aria-label="Primary" class="hidden lg:block lg:justify-self-start">
      <ul class="uppercase flex items-center gap-8 text-sm <?php echo esc_attr($nav_list_class); ?>">
        <?php foreach (array_slice($nav_items, 0, 4) as $item) : ?>
          <li><a class="site-header__link nav-link-animated" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <a href="<?php echo esc_url(home_url('/')); ?>" class="site-header__brand shrink-0 lg:justify-self-center" aria-label="Eurohairlab home">
      <img
        src="<?php echo $theme_uri; ?>/assets/images/logo.webp"
        alt="Eurohairlab"
        class="site-header__logo h-9 w-auto <?php echo esc_attr($logo_filter_class); ?> lg:h-14"
        width="293"
        height="57"
        fetchpriority="high"
        decoding="async"
      >
    </a>

    <div class="flex shrink-0 items-center gap-3 lg:justify-self-end lg:gap-4">
      <?php get_template_part('template-parts/site-header', 'lang'); ?>
      <div class="hidden lg:block">
        <a
          href="<?php echo esc_url($free_scalp_analysis_url); ?>"
          class="uppercase site-header__cta inline-flex min-h-4 items-center justify-center border px-5 py-2 text-sm transition <?php echo esc_attr($cta_class); ?>"
          <?php echo $free_scalp_analysis_link_attrs; ?>
        >
          Start Online Hair Assessment
        </a>
      </div>
      <button
        id="menu-toggle"
        class="site-header__toggle inline-flex h-11 w-11 items-center justify-center lg:hidden <?php echo esc_attr($toggle_class); ?>"
        aria-expanded="false"
        aria-controls="mobile-menu"
      >
        <span class="sr-only">Toggle menu</span>
        <span class="site-header__toggle-lines" aria-hidden="true">
          <span></span>
          <span></span>
          <span></span>
        </span>
      </button>
    </div>
  </div>

  <nav
    id="mobile-menu"
    aria-label="Mobile"
    class="fixed inset-0 hidden bg-white px-6 pb-8 pt-28 text-ink lg:hidden"
  >
    <ul class="flex flex-col gap-6 text-[2rem] leading-none text-ink">
      <?php foreach ($nav_items as $item) : ?>
        <li><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li>
      <?php endforeach; ?>
      <li>
        <a href="<?php echo esc_url($free_scalp_analysis_url); ?>" class="mt-4 inline-flex min-h-4 w-full items-center justify-center border border-ink/70 px-5 py-4 text-center text-base text-ink transition hover:bg-black hover:text-white hover:border-black"<?php echo $free_scalp_analysis_link_attrs; ?>>
          Start Online Hair Diagnosis
        </a>
      </li>
    </ul>
  </nav>
</header>
