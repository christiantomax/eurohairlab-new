<?php

declare(strict_types=1);

$theme_uri = esc_url(get_template_directory_uri());
$social_instagram = 'https://www.instagram.com/eurohairlab/';
$social_facebook = 'https://www.facebook.com/eurohairlab';
$social_tiktok = 'https://www.tiktok.com/@eurohairlab';
$footer_nav_groups = eurohairlab_get_footer_nav_groups();
$free_scalp_analysis_url = eurohairlab_resolve_free_scalp_analysis_href('');
$free_scalp_analysis_link_attrs = eurohairlab_free_scalp_analysis_link_attributes($free_scalp_analysis_url);
$is_figma_pages = is_page([]);
?>
<?php if (!$is_figma_pages) : ?>
<footer class="relative flex min-h-[100vh] flex-col overflow-hidden bg-black text-white">
  <div class="relative z-10 flex w-full flex-1 flex-col px-4 pt-16 sm:px-5 sm:pt-20 lg:px-20 lg:pt-20">
    <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:items-start lg:gap-14">
      <?php foreach ($footer_nav_groups as $group) : ?>
        <nav aria-label="Footer" class="min-w-0">
          <ul class="space-y-4 text-sm font-semibold text-white/92">
            <?php foreach ($group as $item) : ?>
              <li><a class="footer-link inline-block" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </nav>
      <?php endforeach; ?>

      <div class="min-w-0 space-y-6 text-sm font-semibold text-white/92 lg:justify-self-end">
        <div class="space-y-4">
          <a class="footer-link inline-block" href="<?php echo esc_url(eurohairlab_get_blog_list_page_url()); ?>">Blog</a>
          <div class="flex items-center gap-4">
            <a href="<?php echo esc_url($social_instagram); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="inline-flex items-center transition hover:opacity-80">
              <img
                src="<?php echo $theme_uri; ?>/assets/images/icons/instagram.webp"
                alt="Instagram"
                class="h-5 w-5 object-contain"
                width="28"
                height="28"
                loading="lazy"
                decoding="async"
              >
              <span class="sr-only">Instagram</span>
            </a>
            <a href="<?php echo esc_url($social_facebook); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="inline-flex items-center transition hover:opacity-80">
              <img
                src="<?php echo $theme_uri; ?>/assets/images/icons/facebook.webp"
                alt="Facebook"
                class="h-5 w-5 object-contain"
                width="28"
                height="28"
                loading="lazy"
                decoding="async"
              >
              <span class="sr-only">Facebook</span>
            </a>
            <a href="<?php echo esc_url($social_tikktok); ?>" target="_blank" rel="noopener noreferrer" aria-label="Tiktok" class="inline-flex items-center transition hover:opacity-80">
              <img
                src="<?php echo $theme_uri; ?>/assets/images/icons/tiktok.webp"
                alt="Facebook"
                class="h-5 w-5 object-contain"
                width="28"
                height="28"
                loading="lazy"
                decoding="async"
              >
              <span class="sr-only">TikTok</span>
            </a>
          </div>
        </div>

        <a
          href="<?php echo esc_url($free_scalp_analysis_url); ?>"
          class="uppercase site-header__cta inline-flex min-h-11 w-full items-center justify-center whitespace-nowrap border border-white/85 px-4 py-2 text-[12px] text-white transition hover:bg-white hover:text-black sm:px-5 sm:text-sm lg:w-[13rem]"
          <?php echo $free_scalp_analysis_link_attrs; ?>
        >
          Start Online Hair Assessment
        </a>
      </div>
    </div>

    <div class="pb-10 pt-16 text-center sm:pt-20 lg:pb-12 lg:pt-28">
      <img
        src="<?php echo $theme_uri; ?>/assets/images/logo.webp"
        alt="Eurohairlab by Dr.Scalp"
        class="mx-auto h-auto w-full max-w-[18rem] opacity-95 sm:max-w-[24rem] lg:max-w-[36rem]"
        width="736"
        height="143"
        loading="lazy"
        decoding="async"
      >
      <p class="mt-8 text-sm text-white/72 sm:text-base">&copy; 2026 Eurohairlab. All Rights Reserved.</p>
    </div>
  </div>

  <div class="mt-auto">
    <img
      src="<?php echo $theme_uri; ?>/assets/images/footer-image.webp"
      alt=""
      class="h-24 w-full object-cover object-top sm:h-28 lg:h-[18vh]"
      loading="lazy"
      decoding="async"
    >
  </div>
</footer>
<?php else : ?>
<footer class="relative flex min-h-[100vh] flex-col overflow-hidden bg-black text-white">
  <div class="relative z-10 flex w-full flex-1 flex-col px-4 pt-14 sm:px-5 sm:pt-16 lg:px-20 lg:pt-24">
    <div class="grid grid-cols-2 gap-x-8 gap-y-10 lg:grid-cols-5 lg:items-start lg:gap-x-14 lg:gap-y-12">
      <?php foreach ($footer_nav_groups as $group) : ?>
        <nav aria-label="Footer" class="min-w-0">
          <ul class="font-heading space-y-8 text-sm font-semibold text-white/92">
            <?php foreach ($group as $item) : ?>
              <li><a class="footer-link inline-block" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </nav>
      <?php endforeach; ?>

      <div class="min-w-0 space-y-8 text-sm font-semibold text-white/92 lg:space-y-8">
        <a class="footer-link font-heading inline-block" href="<?php echo esc_url(eurohairlab_get_blog_list_page_url()); ?>">Blog</a>
        <div class="flex items-center gap-5">
          <a href="<?php echo esc_url($social_instagram); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="inline-flex items-center transition hover:opacity-80">
            <img
              src="<?php echo $theme_uri; ?>/assets/images/icons/instagram.webp"
              alt="Instagram"
              class="h-5 w-5 object-contain"
              width="28"
              height="28"
              loading="lazy"
              decoding="async"
            >
            <span class="sr-only">Instagram</span>
          </a>
          <a href="<?php echo esc_url($social_facebook); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="inline-flex items-center transition hover:opacity-80">
            <img
              src="<?php echo $theme_uri; ?>/assets/images/icons/facebook.webp"
              alt="Facebook"
              class="h-5 w-5 object-contain"
              width="28"
              height="28"
              loading="lazy"
              decoding="async"
            >
            <span class="sr-only">Facebook</span>
          </a>
          <a href="<?php echo esc_url($social_tiktok); ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok" class="inline-flex items-center transition hover:opacity-80">
            <img
              src="<?php echo $theme_uri; ?>/assets/images/icons/tiktok.webp"
              alt="Tiktok"
              class="h-5 w-5 object-contain"
              width="28"
              height="28"
              loading="lazy"
              decoding="async"
            >
            <span class="sr-only">Tiktok</span>
          </a>
        </div>
      </div>

      <div class="col-span-2 lg:col-span-1 flex items-start lg:justify-self-end lg:self-start">
        <a
          href="<?php echo esc_url($free_scalp_analysis_url); ?>"
          class="uppercase site-header__cta inline-flex min-h-4 w-full items-center justify-center whitespace-nowrap border border-white/90 px-4 py-2 text-[10px] text-white transition hover:bg-white hover:text-black sm:px-5 lg:w-full lg:max-w-[11rem]"
          <?php echo $free_scalp_analysis_link_attrs; ?>
        >
          Start Online Hair Assessment
        </a>
      </div>
    </div>

    <div class="pb-10 pt-20 text-center sm:pt-24 lg:pb-12 lg:pt-36">
      <img
        src="<?php echo $theme_uri; ?>/assets/images/logo.webp"
        alt="Eurohairlab by Dr.Scalp"
        class="mx-auto h-auto w-full max-w-[18rem] opacity-95 sm:max-w-[24rem] lg:max-w-[30rem]"
        width="736"
        height="143"
        loading="lazy"
        decoding="async"
      >
      <p class="mt-10 text-[14px] text-white/72">&copy; 2026 Euroharilab. All Rights Reserved.</p>
    </div>
  </div>

  <div class="mt-auto">
    <img
      src="<?php echo $theme_uri; ?>/assets/images/footer-image.webp"
      alt=""
      class="h-24 w-full object-cover object-top sm:h-28 lg:h-[35vh]"
      loading="lazy"
      decoding="async"
    >
  </div>
</footer>
<?php endif; ?>
