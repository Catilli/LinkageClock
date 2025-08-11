<?php
/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function linkage_theme_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title.
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages.
    add_theme_support('post-thumbnails');

    // Add theme support for selective refresh for widgets.
    add_theme_support('customize-selective-refresh-widgets');

    // Add support for responsive embeds.
    add_theme_support('responsive-embeds');

    // Add support for HTML5 markup.
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    // Add support for full and wide align images.
    add_theme_support('align-wide');

    // Add support for editor styles.
    add_theme_support('editor-styles');
    add_editor_style('style.css');

    // Add custom logo support
    add_theme_support( 'custom-logo' );
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'linkageclock'),
    ));
}
add_action('after_setup_theme', 'linkage_theme_setup');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 */
function linkage_content_width() {
    $GLOBALS['content_width'] = apply_filters('linkage_content_width', 1200);
}
add_action('after_setup_theme', 'linkage_content_width');

/**
 * Enqueue scripts and styles.
 */
function linkage_scripts() {
    // Enqueue jQuery (WordPress built-in)
    wp_enqueue_script('jquery');
    
    // Enqueue Tailwind CSS CDN
    wp_enqueue_script(
        'tailwind-css',
        'https://cdn.tailwindcss.com',
        array(),
        null,
        false
    );
    
    // Add custom Tailwind configuration
    wp_add_inline_script('tailwind-css', '
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#3B82F6",
                        secondary: "#6B7280",
                    }
                }
            }
        }
    ');

    // Add theme stylesheet
    wp_enqueue_style('linkage-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));
    
    // Add comment reply script
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'linkage_scripts');

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function linkage_pingback_header() {
    if (is_singular() && pings_open()) {
        printf('<link rel="pingback" href="%s">', esc_url(get_bloginfo('pingback_url')));
    }
}
add_action('wp_head', 'linkage_pingback_header');

/**
 * Create default pages when theme is activated
 */
function linkage_create_default_pages() {
    // Check if pages already exist to avoid duplicates
    $account_page = get_page_by_title('Your Account');
    $clocking_portal_page = get_page_by_title('Clocking Portal');
    $time_tracking_page = get_page_by_title('Time Tracking');
    $approve_timesheets_page = get_page_by_title('Approve Timesheets');

    // Create Account page if it doesn't exist
    if (!$account_page) {
        $account_page_id = wp_insert_post(array(
            'post_title'    => 'Your Account',
            'post_content'  => 'This is the account page for employees to manage their account.',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'account',
            'page_template' => 'page-employee.php'
        ));
        
        if ($account_page_id) {
            error_log('LinkageClock: Account page created with ID: ' . $account_page_id);
        }
    }

    // Create Clocking Portal page if it doesn't exist
    if (!$clocking_portal_page) {
        $clocking_portal_page_id = wp_insert_post(array(
            'post_title'    => 'Clocking Portal',
            'post_content'  => 'This is the clocking portal page for employees to clock in and out.',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'clocking-portal',
            'page_template' => 'page-clocking-portal.php'
        ));
        
        if ($clocking_portal_page_id) {
            error_log('LinkageClock: Account page created with ID: ' . $clocking_portal_page_id);
        }
    }
    
    // Create Time Tracking page if it doesn't exist
    if (!$time_tracking_page) {
        $time_tracking_page_id = wp_insert_post(array(
            'post_title'    => 'Time Tracking',
            'post_content'  => 'This is the time tracking page for employees to log their hours.',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'time-tracking',
            'page_template' => 'page-time-tracking.php'
        ));
        
        if ($time_tracking_page_id) {
            error_log('LinkageClock: Time Tracking page created with ID: ' . $time_tracking_page_id);
        }
    }
    
    // Create Approve Timesheets page if it doesn't exist
    if (!$approve_timesheets_page) {
        $approve_timesheets_page_id = wp_insert_post(array(
            'post_title'    => 'Approve Timesheets',
            'post_content'  => 'This page allows managers to approve employee timesheets.',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'approve-timesheets',
            'page_template' => 'page-approve-timesheets.php'
        ));
        
        if ($approve_timesheets_page_id) {
            error_log('LinkageClock: Approve Timesheets page created with ID: ' . $approve_timesheets_page_id);
        }
    } 
}
add_action('after_switch_theme', 'linkage_create_default_pages');

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
        <a href="' . esc_url(home_url('/')) . '" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v0a2 2 0 01-2 2H10a2 2 0 01-2-2v0z"></path>
            </svg>
            <span>Dashboard</span>
        </a>
    </li>';
    
    // Time Tracking menu item
    $time_tracking_item = '<li class="menu-item menu-item-time-tracking">
        <a href="' . esc_url(home_url('/time-tracking')) . '" class="flex items-center px-4 py-2 text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-lg transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Time Tracking</span>
        </a>
    </li>';
    
    // Approve Timesheets menu item (only for managers)
    $approve_item = '';
    if (current_user_can('linkage_approve_timesheets')) {
        $approve_item = '<li class="menu-item menu-item-approve-timesheets">
            <a href="' . esc_url(home_url('/approve-timesheets')) . '" class="flex items-center px-4 py-2 text-gray-700 hover:bg-orange-50 hover:text-orange-700 rounded-lg transition-colors duration-200">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Approve Timesheets</span>
            </a>
        </li>';
    }
    
    // Combine custom items and add them before existing menu items
    $custom_items = $dashboard_item . $time_tracking_item . $approve_item;
    
    return $custom_items . $items;
}
add_filter('wp_nav_menu_items', 'linkage_add_custom_menu_items', 10, 2);