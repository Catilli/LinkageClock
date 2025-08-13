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
                <?php linkage_display_navigation(); ?>
            </nav>

            <!-- Spacer to push user section to bottom -->
            <div class="flex-1"></div>

            <!-- User Section -->
            <?php if (is_user_logged_in()): ?>
                <div class="relative border-t border-gray-200">
                    <!-- User Toggle Button -->
                    <button id="user-menu-toggle" class="w-full p-6 flex items-center space-x-3 hover:bg-gray-50 transition-colors duration-200">
                        <?php 
                        $current_user = wp_get_current_user();
                        echo linkage_get_small_avatar($current_user->user_email, linkage_get_user_display_name($current_user->ID), array('border' => false));
                        ?>
                        <div class="flex-1 text-left">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo esc_html(linkage_get_user_display_name(wp_get_current_user()->ID)); ?>
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
                            <a href="<?php echo esc_url(home_url('/account')); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>Your account settings</span>
                                </div>
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" 
                               class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Sign out</span>
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
