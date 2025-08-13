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
        <a href="' . esc_url(home_url('/')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:text-blue-700 rounded-lg transition-colors duration-200">
            <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v0a2 2 0 01-2 2H10a2 0 01-2-2v0z"></path>
            </svg>
            <span class="nav-text">Dashboard</span>
        </a>
    </li>';
    
    // Payroll menu item (only for admin and payroll staff)
    $payroll_item = '';
    if (current_user_can('manage_options') || current_user_can('linkage_export_attendance')) {
        $payroll_item = '<li class="menu-item menu-item-payroll">
            <a href="' . esc_url(home_url('/payroll')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:text-blue-700 rounded-lg transition-colors duration-200">
                <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                <span class="nav-text">Payroll</span>
            </a>
        </li>';
    }
    
    // Return dashboard item + payroll item + existing items
    return $dashboard_item . $payroll_item . $items;
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
        <a href="' . esc_url(home_url('/')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:text-blue-700 rounded-lg transition-colors duration-200">
            <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v0a2 2 0 01-2 2H10a2 0 01-2-2v0z"></path>
            </svg>
            <span class="nav-text">Dashboard</span>
        </a>
    </li>';
    
    // Payroll menu item (only for admin and payroll staff)
    if (current_user_can('manage_options') || current_user_can('linkage_export_attendance')) {
        $items .= '<li class="menu-item menu-item-payroll">
            <a href="' . esc_url(home_url('/payroll')) . '" class="nav-link flex items-center px-4 py-2 text-gray-700 hover:text-blue-700 rounded-lg transition-colors duration-200">
                <svg class="nav-icon w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                <span class="nav-text">Payroll</span>
            </a>
        </li>';
    }
    
    if ($include_wrapper) {
        return '<ul id="primary-menu" class="space-y-2">' . $items . '</ul>';
    }
    
    return $items;
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
        $atts['class'] = $existing_class . 'nav-link flex items-center px-4 py-2 text-gray-700 hover:text-blue-700 rounded-lg transition-colors duration-200';
        
        // Add active state for current page
        if (in_array('current-menu-item', $item->classes)) {
            $atts['class'] .= ' text-blue-700';
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
            
            /* User dropdown menu positioning */
            #user-dropdown-menu {
                transition: all 0.3s ease-in-out;
            }
            
            /* When drawer is collapsed, position dropdown next to collapsed sidebar */
            #masthead.drawer-collapsed ~ #user-dropdown-menu {
                left: 4rem; /* 64px collapsed width */
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'linkage_add_navigation_collapse_styles');