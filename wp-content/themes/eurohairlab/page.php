<?php

declare(strict_types=1);

get_header();

while (have_posts()) :
    the_post();

    $page_config = eurohairlab_get_page_content(get_post_field('post_name', get_the_ID()));

    if (is_array($page_config)) {
        get_template_part('template-parts/internal-page', null, ['page' => $page_config]);
        continue;
    }
    ?>
    <main id="main-content" class="bg-white">
      <article <?php post_class(); ?>>
        <header class="bg-sand px-4 pb-10 pt-32 sm:px-5 sm:pb-14 sm:pt-36 lg:px-20 lg:pb-16 lg:pt-44">
          <div class="reveal max-w-4xl">
            <h1 class="font-heading text-4xl font-bold leading-[0.95] text-ink md:text-6xl"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
              <p class="mt-6 max-w-3xl text-lg leading-8 text-ink/72"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>
          </div>
        </header>
        <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
          <div class="content-rich max-w-4xl">
            <?php the_content(); ?>
          </div>
        </div>
      </article>
    </main>
    <?php
endwhile;

get_footer();
