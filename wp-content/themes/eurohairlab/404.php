<?php

declare(strict_types=1);

get_header();
?>
<main id="main-content" class="bg-white">
  <section class="px-4 pb-20 pt-32 sm:px-5 sm:pt-36 lg:px-20 lg:pt-44">
    <div class="reveal max-w-3xl rounded-[2.4rem] bg-sand p-8 sm:p-10">
      <p class="text-sm uppercase tracking-[0.18em] text-blush">404</p>
      <h1 class="font-heading mt-4 text-4xl font-bold leading-[0.95] text-ink md:text-6xl">This page could not be found.</h1>
      <p class="mt-6 text-lg leading-8 text-ink/72">The URL may have changed, the page may not exist yet, or the content may have been moved to another section.</p>
      <div class="mt-8 flex flex-wrap gap-4">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex min-h-4 items-center justify-center rounded-full bg-ink px-6 py-3 text-sm text-white transition hover:bg-black">Go to homepage</a>
        <a href="<?php echo esc_url(eurohairlab_get_blog_list_page_url()); ?>" class="inline-flex min-h-4 items-center justify-center rounded-full border border-ink/70 px-6 py-3 text-sm text-ink transition hover:bg-ink hover:text-white">Browse articles</a>
      </div>
    </div>
  </section>
</main>
<?php
get_footer();
