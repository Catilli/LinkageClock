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

    <!-- Left Side Drawer -->
    <header id="masthead" class="site-header fixed left-0 top-0 h-full w-64 bg-white shadow-lg border-r border-gray-200 z-40">
        
        <!-- Drawer Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <!-- Logo and Site Title -->
                <div class="flex items-center">
                    <?php if (has_custom_logo()): ?>
                        <div class="site-logo mr-3">
                            <?php the_custom_logo(); ?>
                        </div>
                    <?php else: ?>
                        <div class="site-branding">
                            <h1 class="site-title text-xl font-bold text-gray-900">
                                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home" class="hover:text-blue-600 transition duration-200">
                                    <?php bloginfo('name'); ?>
                                </a>
                            </h1>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Drawer Body -->
        <div class="flex-1 overflow-y-auto">
            <!-- Navigation -->
            <nav id="site-navigation" class="main-navigation p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Navigation</h3>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'menu_class'     => 'space-y-2',
                    'container'      => false,
                ));
                ?>
            </nav>

            <!-- User Section -->
            <?php if (is_user_logged_in()): ?>
                <div class="p-6 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">User</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium">
                                    <?php echo strtoupper(substr(wp_get_current_user()->display_name, 0, 1)); ?>
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo esc_html(wp_get_current_user()->display_name); ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?php echo esc_html(wp_get_current_user()->user_email); ?>
                                </p>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" 
                           class="block text-sm text-red-600 hover:text-red-800 transition duration-200">
                            Logout
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Collapse Toggle -->
        <div class="p-4 border-t border-gray-200">
            <button id="drawer-toggle" class="w-full flex items-center justify-center p-3 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all duration-200 group">
                <svg class="w-5 h-5 transform transition-transform duration-200 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                </svg>
                <span class="ml-2 text-sm font-medium drawer-toggle-text">Collapse</span>
            </button>
        </div>
    </header>

    <!-- Main Content Area -->
    <div id="main-content" class="ml-64 transition-all duration-300">
        <div id="content" class="site-content">
            <main id="main" class="site-main">