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
        <div class="p-6">
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
        <div class="flex-1 overflow-y-auto flex flex-col">
            <!-- Navigation -->
            <nav id="site-navigation" class="main-navigation p-6">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'menu_class'     => 'space-y-2',
                    'container'      => false,
                ));
                ?>
            </nav>

            <!-- Spacer to push user section to bottom -->
            <div class="flex-1"></div>

            <!-- User Section -->
            <?php if (is_user_logged_in()): ?>
                <div class="relative border-t border-gray-200">
                    <!-- User Toggle Button -->
                    <button id="user-menu-toggle" class="w-full p-6 flex items-center space-x-3 hover:bg-gray-50 transition-colors duration-200">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-medium">
                                <?php echo strtoupper(substr(wp_get_current_user()->display_name, 0, 1)); ?>
                            </span>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo esc_html(wp_get_current_user()->display_name); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo esc_html(linkage_get_user_role_display(wp_get_current_user()->ID)); ?>
                            </p>
                        </div>
                        <svg class="user-menu-arrow w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <!-- User Dropdown Menu -->
                    <div id="user-dropdown-menu" class="absolute bottom-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg mb-2 opacity-0 invisible transform translate-y-2 transition-all duration-200 ease-out z-50">
                        <div class="py-2">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>Profile</span>
                                </div>
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>Settings</span>
                                </div>
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" 
                               class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Logout</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Collapse Toggle -->
        <div class="border-t border-gray-200">
            <button id="drawer-toggle" class="w-full flex items-center justify-center p-3 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all duration-200 group">
                <svg class="arrow-icon w-5 h-5 transform transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                <span class="ml-2 text-sm font-medium drawer-toggle-text">COLLAPSE</span>
            </button>
        </div>
    </header>

    <!-- Main Content Area -->
    <div id="main-content" class="ml-64 transition-all duration-300">
        <header id="toolbar" class="toolbar">
            <div class="container mx-auto px-4 py-8">
                <h1><?php echo get_the_title(); ?></h1>
                <div class="clock-buttons">
                    <div class="timer">
                        <span class="current">00:00:00</span>
                    </div>
                    <div class="clock-panels">
                        <button>Clock In</button>
                        <button>Start a Break</button>
                    </div>
                </div>
            </div>
        </header>

        <div id="content" class="site-content">
            <main id="main" class="site-main">