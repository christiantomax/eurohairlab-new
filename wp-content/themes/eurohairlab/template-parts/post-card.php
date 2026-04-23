<?php

declare(strict_types=1);
?>
<article <?php post_class('reveal overflow-hidden rounded-[2rem] border border-ink/10 bg-white text-eh-ink shadow-[0_18px_50px_rgba(32,28,32,0.08)]'); ?>>
  <a href="<?php the_permalink(); ?>" class="block">
    <?php if (has_post_thumbnail()) : ?>
      <?php the_post_thumbnail('large', ['class' => 'h-64 w-full object-cover object-center', 'loading' => 'lazy']); ?>
    <?php else : ?>
      <div class="h-64 w-full bg-[linear-gradient(135deg,#f0d6cf_0%,#f6f5f1_100%)]"></div>
    <?php endif; ?>
  </a>
  <div class="p-7">
    <p class="font-futuraBk text-xs font-normal uppercase tracking-[0.18em] text-eh-ink/46"><?php echo esc_html(get_the_date('F j, Y')); ?></p>
    <h2 class="mt-4 font-futuraHv text-2xl font-normal capitalize leading-none text-eh-ink">
      <a href="<?php the_permalink(); ?>" class="transition hover:text-eh-coral"><?php the_title(); ?></a>
    </h2>
    <p class="mt-4 font-futuraBk text-[18px] font-normal leading-[120%] text-eh-ink"><?php echo esc_html(get_the_excerpt()); ?></p>
    <a href="<?php the_permalink(); ?>" class="mt-6 inline-flex items-center gap-2 font-futuraBk text-[18px] font-normal leading-[120%] text-eh-ink transition hover:text-eh-coral">
      Read article
      <span aria-hidden="true">→</span>
    </a>
  </div>
</article>
