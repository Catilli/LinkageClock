<?php
/**
 * Site Navigation functions for LinkageClock theme
 * Handles custom menu items and navigation structure with proper collapse behavior
 */

/**
 * Add custom menu items to primary navigation
 */
function linkage_add_custom_menu_items($items, $args) {
    // Only add to primary menu
    if ($args->theme_location !== 'primary') {
        return $items;
    }
    
    // Only show for logged in users
    if (!is_user_logged_in()) {
        return $items;
    }
    
    // Dashboard menu item (first item)
    $dashboard_item = '<li class="menu-item menu-item-dashboard">
        <a href="' . esc_url(home_url('/')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors duration-200">
            <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v0a2 2 0 01-2 2H10a2 0 01-2-2v0z"></path>
            </svg>
            <span class="nav-text">Dashboard</span>
        </a>
    </li>';
    
    // Time Tracking menu item
    $time_tracking_item = '<li class="menu-item menu-item-time-tracking">
        <a href="' . esc_url(home_url('/time-tracking')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-lg transition-colors duration-200">
            <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="nav-text">Time Tracking</span>
        </a>
    </li>';
    
    // Approve Timesheets menu item (only for managers)
    $approve_item = '';
    if (current_user_can('linkage_approve_timesheets')) {
        $approve_item = '<li class="menu-item menu-item-approve-timesheets">
            <a href="' . esc_url(home_url('/approve-timesheets')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-700 rounded-lg transition-colors duration-200">
                <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="nav-text">Approve Timesheets</span>
            </a>
        </li>';
    }
    
    // Combine custom items and add them before existing menu items
    $custom_items = $dashboard_item . $time_tracking_item . $approve_item;
    
    return $custom_items . $items;
}
add_filter('wp_nav_menu_items', 'linkage_add_custom_menu_items', 10, 2);

/**
 * Generate custom navigation menu items HTML
 * Used when no WordPress menu is assigned to primary location
 * 
 * @param bool $include_wrapper Whether to include the <ul> wrapper
 * @return string HTML for navigation menu items
 */
function linkage_get_custom_navigation_items($include_wrapper = true) {
    if (!is_user_logged_in()) {
        return linkage_get_logged_out_navigation();
    }
    
    $items = '';
    
    // Dashboard menu item
    $items .= '<li class="menu-item menu-item-dashboard">
        <a href="' . esc_url(home_url('/')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors duration-200">
            <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v0a2 2 0 01-2 2H10a2 0 01-2-2v0z"></path>
            </svg>
            <span class="nav-text">Dashboard</span>
        </a>
    </li>';
    
    // Time Tracking menu item (commented out as per user preference)
    // $items .= '<li class="menu-item menu-item-time-tracking">
    //     <a href="' . esc_url(home_url('/time-tracking')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-lg transition-colors duration-200">
    //         <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    //             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    //         </svg>
    //         <span class="nav-text">Time Tracking</span>
    //     </a>
    // </li>';
    
    // Approve Timesheets menu item (only for managers) - commented out as per user preference
    // if (current_user_can('linkage_approve_timesheets')) {
    //     $items .= '<li class="menu-item menu-item-approve-timesheets">
    //         <a href="' . esc_url(home_url('/approve-timesheets')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-700 rounded-lg transition-colors duration-200">
    //             <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    //                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    //             </svg>
    //             <span class="nav-text">Approve Timesheets</span>
    //         </a>
    //     </li>';
    // }
    
    if ($include_wrapper) {
        return '<ul id="primary-menu" class="space-y-2">' . $items . '</ul>';
    }
    
    return $items;
}

/**
 * Get navigation for logged out users
 * 
 * @return string HTML for logged out navigation
 */
function linkage_get_logged_out_navigation() {
    return '<div class="text-center text-gray-500">
        <p class="text-sm mb-4">Please log in to access navigation</p>
        <a href="' . esc_url(wp_login_url()) . '" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors duration-200">
            Log In
        </a>
    </div>';
}

/**
 * Display the complete navigation
 * Handles both WordPress assigned menus and fallback custom navigation
 */
function linkage_display_navigation() {
    if (has_nav_menu('primary')) {
        // If menu exists, use wp_nav_menu which will trigger our filter
        wp_nav_menu(array(
            'theme_location' => 'primary',
            'menu_id'        => 'primary-menu',
            'menu_class'     => 'space-y-2',
            'container'      => false,
        ));
    } else {
        // If no menu assigned, show our custom menu items directly
        echo linkage_get_custom_navigation_items(true);
    }
}

/**
 * Add navigation menu item classes for active states
 */
function linkage_add_navigation_classes($classes, $item, $args) {
    if ($args->theme_location === 'primary') {
        // Add custom classes based on current page
        $current_url = home_url($_SERVER['REQUEST_URI']);
        $item_url = $item->url;
        
        if ($current_url === $item_url || 
            (is_front_page() && $item_url === home_url('/'))) {
            $classes[] = 'current-menu-item';
        }
    }
    
    return $classes;
}
add_filter('nav_menu_css_class', 'linkage_add_navigation_classes', 10, 3);

/**
 * Customize navigation menu link attributes
 */
function linkage_nav_menu_link_attributes($atts, $item, $args) {
    if ($args->theme_location === 'primary') {
        // Add consistent classes to all navigation links
        $existing_class = isset($atts['class']) ? $atts['class'] . ' ' : '';
        $atts['class'] = $existing_class . 'nav-link flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors duration-200';
        
        // Add active state for current page
        if (in_array('current-menu-item', $item->classes)) {
            $atts['class'] .= ' bg-blue-50 text-blue-700';
        }
    }
    
    return $atts;
}
add_filter('nav_menu_link_attributes', 'linkage_nav_menu_link_attributes', 10, 3);

/**
 * Add navigation collapse styles to dashboard CSS
 */
function linkage_add_navigation_collapse_styles() {
    if (is_user_logged_in()) {
        ?>
        <style>
            /* Navigation collapse styles */
            .nav-link {
                transition: all 0.3s ease-in-out;
            }
            
            .nav-icon {
                transition: margin 0.3s ease-in-out;
            }
            
            .nav-text {
                transition: all 0.3s ease-in-out;
                white-space: nowrap;
            }
            
            /* When drawer is collapsed */
            #masthead.drawer-collapsed .nav-link {
                justify-content: center;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            
            #masthead.drawer-collapsed .nav-icon {
                margin-right: 0 !important;
            }
            
            #masthead.drawer-collapsed .nav-text {
                opacity: 0;
                visibility: hidden;
                width: 0;
                overflow: hidden;
            }
            
            /* User section avatar and text */
            #masthead.drawer-collapsed .employee-name,
            #masthead.drawer-collapsed .employee-email,
            #masthead.drawer-collapsed .employee-role {
                opacity: 0;
                visibility: hidden;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'linkage_add_navigation_collapse_styles');

/**
 * Helper function to check if current page matches menu item
 */
function linkage_is_current_page($url) {
    $current_url = home_url($_SERVER['REQUEST_URI']);
    return $current_url === $url || (is_front_page() && $url === home_url('/'));
}

/**
 * Get navigation menu configuration
 */
function linkage_get_nav_menu_config() {
    return array(
        'theme_location' => 'primary',
        'menu_id'        => 'primary-menu',
        'menu_class'     => 'space-y-2',
        'container'      => false,
        'fallback_cb'    => 'linkage_get_custom_navigation_items',
    );
}