<?php

declare(strict_types=1);

get_header();
?>
<main id="main-content" class="bg-white">
  <section class="bg-sand px-4 pb-10 pt-32 sm:px-5 sm:pb-14 sm:pt-36 lg:px-20 lg:pb-16 lg:pt-44">
    <div class="reveal max-w-4xl">
      <p class="text-sm uppercase tracking-[0.18em] text-blush">Archive</p>
      <h1 class="font-heading mt-4 text-4xl font-bold leading-[0.95] text-ink md:text-6xl"><?php the_archive_title(); ?></h1>
      <?php the_archive_description('<div class="mt-6 max-w-3xl text-lg leading-8 text-ink/72">', '</div>'); ?>
    </div>
  </section>

  <section class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
    <?php if (have_posts()) : ?>
      <div class="grid gap-6 lg:grid-cols-3">
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('template-parts/post-card');
        endwhile;
        ?>
      </div>
      <div class="mt-10">
        <?php the_posts_pagination(['mid_size' => 1]); ?>
      </div>
    <?php else : ?>
      <div class="reveal rounded-[2rem] bg-sand p-8">
        <p class="text-base leading-7 text-ink/72">No matching content found in this archive yet.</p>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php
get_footer();
