<!-- Left Side Drawer -->
<header id="masthead" class="site-header fixed left-0 top-0 h-full w-64 bg-white shadow-lg border-r border-gray-200 z-40">
    
    <!-- Drawer Header -->
    <div class="p-6">
        <div class="flex items-center justify-between">
            <!-- Logo and Site Title -->
            <div class="flex items-center">
                <!-- Collapsed State Icon (favicon or WordPress logo) -->
                <div class="site-icon hidden">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home" class="block w-8 h-8">
                        <?php 
                        // Try to get site icon (favicon) first
                        $site_icon_url = get_site_icon_url(32); // 32px favicon
                        if ($site_icon_url): ?>
                            <img src="<?php echo esc_url($site_icon_url); ?>" alt="<?php bloginfo('name'); ?>" class="w-8 h-8 rounded">
                        <?php else: ?>
                            <!-- WordPress logo fallback -->
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.158 12.786l-2.698 7.84c.806.236 1.657.365 2.54.365 1.047 0 2.051-.18 2.986-.51-.024-.037-.046-.078-.065-.123l-2.763-7.572zm-3.79-3.676c.769 0 1.31-.074 1.31-.074.268-.016.237-.424-.031-.41 0 0-.804.063-1.323.063-.487 0-1.308-.063-1.308-.063-.268-.014-.299.394-.031.41 0 0 .52.074 1.07.074l1.59 4.358-2.234 6.702-3.72-10.96c.769 0 1.31-.074 1.31-.074.268-.016.237-.424-.031-.41 0 0-.804.063-1.323.063-.093 0-.202-.002-.32-.006 1.142-1.736 3.104-2.888 5.34-2.888 1.654 0 3.158.63 4.281 1.663-.027-.002-.055-.004-.084-.004-.487 0-.832.424-.832.88 0 .408.237.753.487 1.161.188.33.406.753.406 1.364 0 .424-.164 1.040-.378 1.814l-.493 1.644-1.785-5.309zm10.632-7.11c-6.627 0-12 5.373-12 12 0 6.627 5.373 12 12 12 6.627 0 12-5.373 12-12 0-6.627-5.373-12-12-12zm.05 5.778c.456 0 .86.063.86.063.268.016.237.424-.031.41 0 0-.268-.031-.566-.047l1.831 5.448 1.109-3.703c.188-.613.33-1.057.33-1.437 0-.566-.204-1.477-.204-1.477-.188-.424-.378-.771-.378-1.198 0-.47.33-.903.8-.903z"/>
                            </svg>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Expanded State Logo/Title -->
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
        <div class="border-t border-gray-200">
            <!-- User Toggle Button -->
            <button id="user-menu-toggle" class="w-full p-6 flex items-center space-x-3 hover:bg-gray-50 transition-colors duration-200">
                <?php 
                $current_user = wp_get_current_user();
                echo linkage_get_small_avatar($current_user->user_email, linkage_get_user_display_name($current_user->ID), array('border' => false));
                ?>
                <div class="flex-1 text-left user-info-text">
                    <p class="text-sm font-medium text-gray-900">
                        <?php echo esc_html(linkage_get_user_display_name(wp_get_current_user()->ID)); ?>
                    </p>
                    <p class="text-xs text-gray-500">
                        <?php echo esc_html(linkage_get_user_role_display(wp_get_current_user()->ID)); ?>
                    </p>
                </div>
                <svg class="user-menu-arrow w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>

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

<!-- User Dropdown Menu (positioned outside sidebar) -->
<div id="user-dropdown-menu" class="fixed left-64 bottom-6 w-64 bg-white border border-gray-200 rounded-lg shadow-lg opacity-0 invisible transform translate-x-4 transition-all duration-200 ease-out z-50">
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