<?php

declare(strict_types=1);
?>
<main id="main-content" class="bg-white">
  <article <?php post_class(); ?>>
    <header class="relative overflow-hidden bg-ink text-white">
      <div class="absolute inset-0">
        <?php if (has_post_thumbnail()) : ?>
          <?php the_post_thumbnail('full', ['class' => 'h-full w-full object-cover object-center', 'fetchpriority' => 'high']); ?>
        <?php else : ?>
          <div class="h-full w-full bg-[linear-gradient(135deg,#201c20_0%,#8d5f56_100%)]"></div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(18,16,18,.44)_0%,rgba(18,16,18,.82)_100%)]"></div>
      </div>
      <div class="relative px-4 pb-16 pt-32 sm:px-5 sm:pb-18 sm:pt-36 lg:px-20 lg:pb-20 lg:pt-44">
        <div class="reveal max-w-4xl">
          <p class="text-sm uppercase tracking-[0.18em] text-white/70"><?php echo esc_html(get_the_date('F j, Y')); ?></p>
          <h1 class="font-heading mt-5 text-4xl font-bold leading-[0.94] md:text-6xl"><?php the_title(); ?></h1>
          <div class="mt-7 flex flex-wrap items-center gap-4 text-sm text-white/72">
            <span>By <?php the_author(); ?></span>
            <span><?php
                $eh_cats = get_the_category();
                $eh_cat_parts = [];
                if (is_array($eh_cats)) {
                    foreach ($eh_cats as $eh_cat) {
                        if (!$eh_cat instanceof WP_Term) {
                            continue;
                        }
                        $eh_label = function_exists('eurohairlab_get_category_display_name')
                            ? eurohairlab_get_category_display_name($eh_cat)
                            : (is_string($eh_cat->name) ? $eh_cat->name : '');
                        $eh_cat_parts[] = '<a href="' . esc_url(get_category_link($eh_cat)) . '">' . esc_html($eh_label) . '</a>';
                    }
                }
                echo wp_kses_post(implode(', ', $eh_cat_parts));
                ?></span>
            <span><?php echo esc_html((string) max(1, (int) ceil(str_word_count(wp_strip_all_tags(get_the_content())) / 220))); ?> min read</span>
          </div>
          <?php if (has_excerpt()) : ?>
            <p class="mt-7 max-w-3xl text-lg leading-8 text-white/80"><?php echo esc_html((string) (get_the_excerpt() ?? '')); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <div class="grid gap-12 px-4 py-14 sm:px-5 sm:py-16 lg:grid-cols-[minmax(0,1fr)_20rem] lg:px-20 lg:py-20">
      <div class="min-w-0">
        <div class="content-rich max-w-none">
          <?php the_content(); ?>
        </div>
      </div>
      <aside class="reveal rounded-[2rem] bg-sand p-8 lg:sticky lg:top-28 lg:self-start">
        <p class="text-sm uppercase tracking-[0.18em] text-ink/46">Need help applying this?</p>
        <h2 class="font-heading mt-4 text-2xl font-semibold text-ink">Talk to Eurohairlab.</h2>
        <p class="mt-4 text-base leading-7 text-ink/72">If the article matches your current concern, the next step is a consultation tailored to your scalp history and goals.</p>
        <a href="<?php echo esc_url(eurohairlab_get_primary_cta_url()); ?>" class="mt-6 inline-flex min-h-4 items-center justify-center rounded-full bg-ink px-6 py-3 text-sm text-white transition hover:bg-black">Book consultation</a>
      </aside>
    </div>

    <nav class="border-t border-ink/8 px-4 py-10 sm:px-5 lg:px-20" aria-label="Post navigation">
      <div class="grid gap-4 md:grid-cols-2">
        <div><?php previous_post_link('%link', '← %title'); ?></div>
        <div class="md:text-right"><?php next_post_link('%link', '%title →'); ?></div>
      </div>
    </nav>
  </article>
</main>
