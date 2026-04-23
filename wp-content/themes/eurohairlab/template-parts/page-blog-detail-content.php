<?php

declare(strict_types=1);

/**
 * Single post layout — Figma frame Blog Detail (4035:875).
 * Copy and cover image from Blog (front-end) metabox; body from the editor.
 */

$pid = get_the_ID();
$display_title = eurohairlab_get_blog_post_display_title($pid);
$display_date = eurohairlab_get_blog_post_display_date($pid);
$cover_url = eurohairlab_get_blog_post_image_url($pid, 'eh_blog_image_cover');
$description = eurohairlab_get_blog_post_description($pid);
$categories = get_the_category($pid);
$category_names = [];
if (is_array($categories)) {
    foreach ($categories as $cat) {
        if (isset($cat->name) && is_string($cat->name) && $cat->name !== '') {
            $category_names[] = $cat->name;
        }
    }
}
$category_line = $category_names !== [] ? implode(', ', $category_names) : '';
?>
<main id="main-content" class="bg-white text-eh-ink antialiased">
  <article <?php post_class(); ?>>
    <section class="bg-white pt-28 sm:pt-32 lg:pt-[6rem]">
      <div class="grid gap-10 px-4 sm:px-5 lg:grid-cols-[30rem_1fr] lg:gap-0 lg:px-20">
        <header class="reveal border-b border-[#BABABA] lg:border-b-0 lg:border-r lg:border-[#BABABA] lg:pr-[3.1rem]">
          <?php if ($display_date !== '') : ?>
          <p class="font-futuraBk text-[18px] font-normal capitalize leading-[120%] text-eh-sand-num lg:mt-10"><?php echo esc_html($display_date); ?></p>
          <?php endif; ?>
          <?php if ($display_title !== '') : ?>
          <h1 class="font-futuraHv <?php echo $display_date !== '' ? 'mt-8' : 'lg:mt-10'; ?> max-w-[24rem] text-3xl font-normal capitalize leading-none text-eh-ink sm:text-4xl md:text-5xl lg:mt-12 lg:text-[64px]">
            <?php echo esc_html($display_title); ?>
          </h1>
          <?php endif; ?>
          <?php if ($category_line !== '') : ?>
          <p class="<?php echo ($display_date === '' && $display_title === '') ? 'lg:mt-10' : 'mt-8'; ?> font-futuraBk text-[18px] font-normal capitalize leading-[120%] text-eh-coral">
            <?php echo esc_html($category_line); ?>
          </p>
          <?php endif; ?>
        </header>

        <div class="reveal lg:pl-[3.9rem]">
          <?php if ($cover_url !== '') : ?>
          <figure class="overflow-hidden">
            <img
              src="<?php echo esc_url($cover_url); ?>"
              alt="<?php echo esc_attr($display_title); ?>"
              class="mb-5 h-[16rem] w-full object-cover object-center sm:h-[22rem] lg:h-[31.2rem]"
              width="778"
              height="499"
              fetchpriority="high"
              decoding="async"
            >
          </figure>
          <?php endif; ?>
          <?php if ($description !== '') : ?>
          <div class="content-rich eh-blog-detail-body mt-8 max-w-[48.6rem] font-futuraBk text-[18px] font-normal leading-[120%] text-eh-ink">
            <?php echo wp_kses_post(wpautop(esc_html($description))); ?>
          </div>
          <?php endif; ?>
          <?php $content_margin = ($cover_url !== '' || $description !== '') ? 'mt-8' : ''; ?>
          <div class="content-rich eh-blog-detail-body<?php echo $content_margin !== '' ? ' ' . esc_attr($content_margin) : ''; ?> max-w-[48.6rem] pb-24">
            <?php the_content(); ?>
          </div>
        </div>
      </div>
    </section>
  </article>
</main>
