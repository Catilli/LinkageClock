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