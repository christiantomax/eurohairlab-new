<?php

declare(strict_types=1);
?>
<form role="search" method="get" class="flex items-center gap-3 rounded-full border border-ink/10 bg-white px-4 py-3 shadow-[0_10px_30px_rgba(32,28,32,0.05)]" action="<?php echo esc_url(home_url('/')); ?>">
  <label class="sr-only" for="search-field">Search articles</label>
  <input id="search-field" class="min-w-0 flex-1 bg-transparent text-sm text-ink outline-none placeholder:text-ink/40" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Search scalp care articles">
  <button type="submit" class="inline-flex min-h-4 items-center justify-center rounded-full bg-ink px-4 py-2 text-xs uppercase tracking-[0.12em] text-white transition hover:bg-black">Search</button>
</form>
