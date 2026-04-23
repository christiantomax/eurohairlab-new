<?php

declare(strict_types=1);

$page = $args['page'] ?? [];

if (!$page) {
    return;
}

$theme_classes = [
    'light' => 'bg-white text-ink',
    'white' => 'bg-white text-ink',
    'sand' => 'bg-sand text-ink',
    'blush' => 'bg-blush/70 text-ink',
    'dark' => 'bg-ink text-white',
];
?>
<main id="main-content">
  <section class="relative overflow-hidden bg-ink text-white">
    <div class="absolute inset-0">
      <img
        src="<?php echo esc_url(eurohairlab_get_image_uri((string) $page['hero_image'])); ?>"
        alt="<?php echo esc_attr((string) $page['hero_alt']); ?>"
        class="h-full w-full object-cover object-center"
        width="1656"
        height="1032"
        fetchpriority="high"
        decoding="async"
      >
      <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(18,16,18,.75)_0%,rgba(18,16,18,.46)_55%,rgba(18,16,18,.3)_100%)]"></div>
    </div>

    <div class="relative px-4 pb-18 pt-32 sm:px-5 sm:pb-20 sm:pt-36 lg:px-20 lg:pb-24 lg:pt-44">
      <div class="reveal max-w-3xl">
        <p class="text-sm uppercase tracking-[0.2em] text-white/70"><?php echo esc_html((string) $page['eyebrow']); ?></p>
        <h1 class="font-heading mt-5 text-4xl font-bold leading-[0.92] md:text-6xl lg:max-w-[12ch] lg:text-7xl">
          <?php echo esc_html((string) $page['title']); ?>
        </h1>
        <p class="mt-7 max-w-2xl text-base leading-7 text-white/80 sm:text-lg sm:leading-8">
          <?php echo esc_html((string) $page['description']); ?>
        </p>
      </div>
    </div>
  </section>

  <?php foreach ((array) $page['sections'] as $section) : ?>
    <?php $section_theme = $theme_classes[$section['theme'] ?? 'light'] ?? $theme_classes['light']; ?>
    <?php if (($section['type'] ?? '') === 'split') : ?>
      <section class="<?php echo esc_attr($section_theme); ?>">
        <div class="grid gap-0 lg:grid-cols-[1.05fr_.95fr]">
          <div class="flex items-center px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
            <div class="reveal max-w-2xl">
              <p class="text-sm font-semibold uppercase tracking-[0.18em] <?php echo str_contains($section_theme, 'text-white') ? 'text-white/70' : 'text-blush'; ?>"><?php echo esc_html((string) $section['eyebrow']); ?></p>
              <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] md:text-5xl"><?php echo esc_html((string) $section['title']); ?></h2>
              <?php foreach ((array) $section['body'] as $paragraph) : ?>
                <p class="mt-6 text-lg leading-8 <?php echo str_contains($section_theme, 'text-white') ? 'text-white/76' : 'text-ink/76'; ?>"><?php echo esc_html((string) $paragraph); ?></p>
              <?php endforeach; ?>
              <?php if (!empty($section['list'])) : ?>
                <ul class="mt-8 space-y-4">
                  <?php foreach ((array) $section['list'] as $item) : ?>
                    <li class="flex items-start gap-3 text-base leading-7 <?php echo str_contains($section_theme, 'text-white') ? 'text-white/84' : 'text-ink/82'; ?>">
                      <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-blush"></span>
                      <span><?php echo esc_html((string) $item); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
          <figure class="reveal min-h-[22rem] overflow-hidden bg-black/10 lg:min-h-full">
            <img
              src="<?php echo esc_url(eurohairlab_get_image_uri((string) $section['media']['image'])); ?>"
              alt="<?php echo esc_attr((string) $section['media']['alt']); ?>"
              class="h-full w-full object-cover object-center"
              width="793"
              height="709"
              loading="lazy"
              decoding="async"
            >
          </figure>
        </div>
      </section>
    <?php elseif (($section['type'] ?? '') === 'cards') : ?>
      <section class="<?php echo esc_attr($section_theme); ?>">
        <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
          <div class="reveal max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] <?php echo str_contains($section_theme, 'text-white') ? 'text-white/70' : 'text-blush'; ?>"><?php echo esc_html((string) $section['eyebrow']); ?></p>
            <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] md:text-5xl"><?php echo esc_html((string) $section['title']); ?></h2>
          </div>
          <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ((array) $section['cards'] as $card) : ?>
              <article class="reveal rounded-[2rem] border border-black/8 bg-white/70 p-8 shadow-[0_18px_50px_rgba(32,28,32,0.08)] <?php echo str_contains($section_theme, 'text-white') ? 'border-white/10 bg-white/6' : ''; ?>">
                <h3 class="font-heading text-2xl font-semibold"><?php echo esc_html((string) $card['title']); ?></h3>
                <p class="mt-4 text-base leading-7 <?php echo str_contains($section_theme, 'text-white') ? 'text-white/72' : 'text-ink/72'; ?>"><?php echo esc_html((string) $card['text']); ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php elseif (($section['type'] ?? '') === 'timeline') : ?>
      <section class="<?php echo esc_attr($section_theme); ?>">
        <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
          <div class="reveal max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] <?php echo str_contains($section_theme, 'text-white') ? 'text-white/70' : 'text-blush'; ?>"><?php echo esc_html((string) $section['eyebrow']); ?></p>
            <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] md:text-5xl"><?php echo esc_html((string) $section['title']); ?></h2>
          </div>
          <div class="mt-10 grid gap-5 lg:grid-cols-4">
            <?php foreach ((array) $section['steps'] as $step) : ?>
              <article class="reveal rounded-[2rem] border p-7 <?php echo str_contains($section_theme, 'text-white') ? 'border-white/10 bg-white/5' : 'border-ink/10 bg-white'; ?>">
                <h3 class="font-heading text-xl font-semibold"><?php echo esc_html((string) $step['title']); ?></h3>
                <p class="mt-4 text-base leading-7 <?php echo str_contains($section_theme, 'text-white') ? 'text-white/72' : 'text-ink/72'; ?>"><?php echo esc_html((string) $step['text']); ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php elseif (($section['type'] ?? '') === 'faq') : ?>
      <section class="<?php echo esc_attr($section_theme); ?>">
        <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
          <div class="reveal max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] <?php echo str_contains($section_theme, 'text-white') ? 'text-white/70' : 'text-blush'; ?>"><?php echo esc_html((string) $section['eyebrow']); ?></p>
            <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] md:text-5xl"><?php echo esc_html((string) $section['title']); ?></h2>
          </div>
          <div class="mt-10 grid gap-4">
            <?php foreach ((array) $section['items'] as $index => $item) : ?>
              <article class="reveal overflow-hidden rounded-[1.75rem] border <?php echo str_contains($section_theme, 'text-white') ? 'border-white/10 bg-white/5' : 'border-ink/10 bg-white'; ?>">
                <button type="button" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left" data-accordion-button aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                  <span class="font-heading text-xl font-semibold"><?php echo esc_html((string) $item['question']); ?></span>
                  <span class="text-2xl leading-none"><?php echo $index === 0 ? '−' : '+'; ?></span>
                </button>
                <div class="px-6 pb-6 text-base leading-7 <?php echo str_contains($section_theme, 'text-white') ? 'text-white/74' : 'text-ink/74'; ?> <?php echo $index === 0 ? '' : 'hidden'; ?>" data-accordion-panel>
                  <?php echo esc_html((string) $item['answer']); ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php elseif (($section['type'] ?? '') === 'cta') : ?>
      <section class="<?php echo esc_attr($section_theme); ?>">
        <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
          <div class="reveal rounded-[2.2rem] border px-7 py-10 md:px-10 <?php echo str_contains($section_theme, 'text-white') ? 'border-white/10 bg-white/5' : 'border-ink/10 bg-sand'; ?>">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] <?php echo str_contains($section_theme, 'text-white') ? 'text-white/70' : 'text-blush'; ?>"><?php echo esc_html((string) $section['eyebrow']); ?></p>
            <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] md:text-5xl lg:max-w-3xl"><?php echo esc_html((string) $section['title']); ?></h2>
            <p class="mt-6 max-w-2xl text-lg leading-8 <?php echo str_contains($section_theme, 'text-white') ? 'text-white/74' : 'text-ink/74'; ?>"><?php echo esc_html((string) $section['text']); ?></p>
            <a href="<?php echo esc_url((string) $section['cta']['url']); ?>" class="mt-8 inline-flex min-h-4 items-center justify-center border border-current px-6 py-3 text-sm transition hover:bg-white hover:text-ink">
              <?php echo esc_html((string) $section['cta']['label']); ?>
            </a>
          </div>
        </div>
      </section>
    <?php elseif (($section['type'] ?? '') === 'metrics') : ?>
      <section class="<?php echo esc_attr($section_theme); ?>">
        <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
          <div class="reveal max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/70"><?php echo esc_html((string) $section['eyebrow']); ?></p>
            <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] md:text-5xl"><?php echo esc_html((string) $section['title']); ?></h2>
          </div>
          <div class="mt-10 grid gap-5 md:grid-cols-3">
            <?php foreach ((array) $section['items'] as $item) : ?>
              <div class="reveal rounded-[2rem] border border-white/10 bg-white/5 p-8">
                <p class="font-heading text-4xl font-bold"><?php echo esc_html((string) $item['value']); ?></p>
                <p class="mt-3 max-w-[18ch] text-base leading-7 text-white/72"><?php echo esc_html((string) $item['label']); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php elseif (($section['type'] ?? '') === 'contact') : ?>
      <section class="<?php echo esc_attr($section_theme); ?>">
        <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
          <div class="reveal max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blush"><?php echo esc_html((string) $section['eyebrow']); ?></p>
            <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] md:text-5xl"><?php echo esc_html((string) $section['title']); ?></h2>
          </div>
          <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <?php foreach ((array) $section['details'] as $detail) : ?>
              <article class="reveal rounded-[1.8rem] border border-ink/10 bg-sand p-7">
                <p class="text-xs uppercase tracking-[0.18em] text-ink/48"><?php echo esc_html((string) $detail['label']); ?></p>
                <?php if (!empty($detail['href'])) : ?>
                  <a href="<?php echo esc_url((string) $detail['href']); ?>" class="mt-4 block text-lg leading-8 text-ink transition hover:text-blush"><?php echo esc_html((string) $detail['value']); ?></a>
                <?php else : ?>
                  <p class="mt-4 text-lg leading-8 text-ink"><?php echo esc_html((string) $detail['value']); ?></p>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php elseif (($section['type'] ?? '') === 'form') : ?>
      <section class="<?php echo esc_attr($section_theme); ?>">
        <div class="grid gap-8 px-4 py-14 sm:px-5 sm:py-16 lg:grid-cols-[.85fr_1.15fr] lg:px-20 lg:py-20">
          <div class="reveal max-w-xl">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blush"><?php echo esc_html((string) $section['eyebrow']); ?></p>
            <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] md:text-5xl"><?php echo esc_html((string) $section['title']); ?></h2>
            <p class="mt-6 text-lg leading-8 text-ink/72"><?php echo esc_html((string) $section['form_intro']); ?></p>
          </div>
          <div class="reveal rounded-[2rem] bg-white p-6 shadow-[0_18px_50px_rgba(32,28,32,0.08)] sm:p-8">
            <form id="contact-form" class="grid gap-5" method="post" action="#">
              <div class="grid gap-5 md:grid-cols-2">
                <label class="grid gap-2">
                  <span class="text-sm font-medium text-ink/72">Full name</span>
                  <input class="rounded-2xl border border-ink/10 px-4 py-3 outline-none transition focus:border-ink" type="text" name="name" autocomplete="name">
                </label>
                <label class="grid gap-2">
                  <span class="text-sm font-medium text-ink/72">Email address</span>
                  <input class="rounded-2xl border border-ink/10 px-4 py-3 outline-none transition focus:border-ink" type="email" name="email" autocomplete="email">
                </label>
              </div>
              <div class="grid gap-5 md:grid-cols-2">
                <label class="grid gap-2">
                  <span class="text-sm font-medium text-ink/72">Phone number</span>
                  <input class="rounded-2xl border border-ink/10 px-4 py-3 outline-none transition focus:border-ink" type="tel" name="phone" autocomplete="tel">
                </label>
                <label class="grid gap-2">
                  <span class="text-sm font-medium text-ink/72">Main concern</span>
                  <select class="rounded-2xl border border-ink/10 px-4 py-3 outline-none transition focus:border-ink" name="concern">
                    <option>Hair thinning</option>
                    <option>Hair shedding</option>
                    <option>Scalp irritation</option>
                    <option>Program review</option>
                  </select>
                </label>
              </div>
              <label class="grid gap-2">
                <span class="text-sm font-medium text-ink/72">Tell us more</span>
                <textarea class="min-h-36 rounded-2xl border border-ink/10 px-4 py-3 outline-none transition focus:border-ink" name="message"></textarea>
              </label>
              <button class="inline-flex min-h-4 items-center justify-center rounded-full bg-ink px-6 py-3 text-sm text-white transition hover:bg-black" type="submit">Send Enquiry</button>
            </form>
          </div>
        </div>
      </section>
    <?php endif; ?>
  <?php endforeach; ?>

  <section class="bg-white">
    <div class="px-4 py-14 sm:px-5 sm:py-16 lg:px-20 lg:py-20">
      <div class="reveal rounded-[2.2rem] bg-[linear-gradient(135deg,#f8ebe7_0%,#f6f5f1_45%,#ffffff_100%)] px-7 py-10 md:px-10 lg:flex lg:items-end lg:justify-between">
        <div class="max-w-3xl">
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blush">Next Step</p>
          <h2 class="font-heading mt-4 text-3xl font-bold leading-[0.95] text-ink md:text-5xl">Need a tailored recommendation instead of a generic answer?</h2>
          <p class="mt-6 text-lg leading-8 text-ink/72">Book a consultation so the next action is based on scalp condition, treatment history, and realistic recovery goals.</p>
        </div>
        <a href="<?php echo esc_url(eurohairlab_get_primary_cta_url()); ?>" class="mt-8 inline-flex min-h-4 items-center justify-center rounded-full border border-ink/80 px-6 py-3 text-sm text-ink transition hover:bg-ink hover:text-white lg:mt-0">
          Start Consultation
        </a>
      </div>
    </div>
  </section>
</main>
