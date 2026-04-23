<?php

declare(strict_types=1);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta
    name="description"
    content="<?php echo esc_attr(eurohairlab_get_meta_description()); ?>"
  >
  <?php wp_head(); ?>
</head>
<body <?php body_class('bg-sand text-ink antialiased'); ?>>
<?php wp_body_open(); ?>
<div class="relative isolate overflow-x-hidden bg-white">
<?php get_template_part('template-parts/site', 'header'); ?>
