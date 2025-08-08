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

    <header id="masthead" class="site-header drawer bg-white shadow-sm">
        <div class="drawer__content">
            <div class="drawer__header">
                <h1 class="drawer__title">
                </h1>
            </div>
            <div class="drawer__body">
                <nav id="site-navigation" class="main-navigation">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'menu_class'     => 'flex space-x-4',
                    ));
                    ?>
                </nav>
            </div>
            <div class="drawer__footer"></div>
        </div>
    </header>

    <div id="content" class="site-content">
        <main id="main" class="site-main">