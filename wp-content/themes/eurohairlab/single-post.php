<?php

declare(strict_types=1);

get_header();

while (have_posts()) :
    the_post();
    get_template_part('template-parts/page-blog-detail-content');
endwhile;

get_footer();
