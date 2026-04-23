<?php

declare(strict_types=1);

get_header();

while (have_posts()) :
    the_post();
    get_template_part('template-parts/blog-article');
endwhile;

get_footer();
