<?php

declare(strict_types=1);

$title = $args['title'] ?? 'Guidance for scalp health, diagnosis, and recovery planning.';
$eyebrow = $args['eyebrow'] ?? 'Eurohairlab Journal';
$description = $args['description'] ?? 'Use the journal to explore scalp education, treatment planning frameworks, and practical maintenance guidance written for stronger decision-making.';
?>
<main id="main-content">
  <section class="relative overflow-hidden bg-ink text-white">
    <div class="absolute inset-0">
      <img
        src="<?php echo esc_url(eurohairlab_get_image_uri('hero-bg.webp')); ?>"
        alt="Editorial beauty portrait for Eurohairlab journal"
        class="h-full w-full object-cover object-center"
        width="1656"
        height="1032"
        fetchpriority="high"
        decoding="async"
      >
      <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(18,16,18,.8)_0%,rgba(18,16,18,.48)_55%,rgba(18,16,18,.28)_100%)]"></div>
    </div>
    <div class="relative px-4 pb-18 pt-32 sm:px-5 sm:pb-20 sm:pt-36 lg:px-20 lg:pb-24 lg:pt-44">
      <div class="reveal max-w-3xl">
        <p class="text-sm uppercase tracking-[0.2em] text-white/70"><?php echo esc_html((string) $eyebrow); ?></p>
        <h1 class="font-heading mt-5 text-4xl font-bold leading-[0.92] md:text-6xl lg:max-w-[12ch] lg:text-7xl"><?php echo esc_html((string) $title); ?></h1>
        <p class="mt-7 max-w-2xl text-base leading-7 text-white/80 sm:text-lg sm:leading-8"><?php echo esc_html((string) $description); ?></p>
      </div>
    </div>
  </section>

  <section class="bg-white">
    <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="reveal max-w-3xl">
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blush">Latest articles</p>
          <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] text-ink md:text-5xl">Practical content for people who want clarity before action.</h2>
        </div>
        <div class="reveal w-full max-w-md">
          <?php get_search_form(); ?>
        </div>
      </div>

      <?php if (have_posts()) : ?>
        <div class="mt-10 grid gap-6 lg:grid-cols-3">
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
        <div class="reveal mt-10 rounded-[2rem] bg-sand p-8">
          <h2 class="font-heading text-2xl font-semibold text-ink">No articles published yet.</h2>
          <p class="mt-4 text-base leading-7 text-ink/72">Publish your first post and it will appear here automatically.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
