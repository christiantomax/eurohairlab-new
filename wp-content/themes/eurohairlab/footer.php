<?php get_template_part('template-parts/site', 'footer'); ?>
</div>
  <div id="image-lightbox" class="fixed inset-0 z-50 hidden bg-black/80 p-6 backdrop-blur-sm">
    <div class="mx-auto flex h-full max-w-6xl flex-col">
      <div class="flex items-center justify-between py-2 text-white">
        <p id="lightbox-title" class="text-sm uppercase tracking-[0.24em] text-white/70"></p>
        <button id="lightbox-close" class="rounded-full border border-white/20 px-4 py-2 text-xs uppercase tracking-[0.24em] text-white">Close</button>
      </div>
      <div class="flex min-h-0 flex-1 items-center justify-center">
        <img id="lightbox-image" src="" alt="" class="max-h-full max-w-full rounded-[1.5rem] object-contain shadow-soft">
      </div>
    </div>
  </div>
<?php wp_footer(); ?>
</body>
</html>
