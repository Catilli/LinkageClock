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

    <header id="masthead" class="site-header drawer bg-white shadow-sm border-b border-gray-200">
        <div class="drawer__content container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                
                <!-- Logo and Site Title -->
                <div class="flex drawer__header items-center">
                    <?php if (has_custom_logo()): ?>
                        <div class="site-logo mr-4">
                            <?php the_custom_logo(); ?>
                        </div>
                    <?php else: ?>
                        <div class="site-branding">
                            <h1 class="site-title text-2xl font-bold text-gray-900">
                                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home" class="hover:text-blue-600 transition duration-200">
                                    <?php bloginfo('name'); ?>
                                </a>
                            </h1>
                            <?php
                            $linkage_description = get_bloginfo('description', 'display');
                            if ($linkage_description || is_customize_preview()):
                            ?>
                                <p class="site-description text-gray-600 text-sm mt-1">
                                    <?php echo $linkage_description; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Navigation -->
                <nav id="site-navigation" class="drawer__body main-navigation">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'menu_class'     => 'flex space-x-6 text-gray-700',
                        'container'      => false,
                        'fallback_cb'    => 'linkage_fallback_menu',
                    ));
                    ?>
                </nav>

                <!-- User Menu (if logged in) -->
                <?php if (is_user_logged_in()): ?>
                    <div class="drawer__footer flex items-center space-x-4">
                        <span class="text-sm text-gray-600">
                            Welcome, <?php echo esc_html(wp_get_current_user()->display_name); ?>
                        </span>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" 
                           class="text-sm text-red-600 hover:text-red-800 transition duration-200">
                            Logout
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </header>

    <div id="content" class="site-content">
        <main id="main" class="site-main">