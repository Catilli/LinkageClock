<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <?php get_template_part('template-parts/sidebar'); ?>

    <!-- Main Content Area -->
    <div id="main-content" class="ml-64 transition-all duration-300">
        <?php get_template_part('template-parts/toolbar'); ?>

        <div id="content" class="site-content">
            <main id="main" class="site-main">