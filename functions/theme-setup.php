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
    $payroll_page = get_page_by_title('Payroll Dashboard');
    $desktop_only_page = get_page_by_title('Desktop Access Notice');

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
    
    // Create Payroll page if it doesn't exist
    if (!$payroll_page) {
        $payroll_page = wp_insert_post(array(
            'post_title'    => 'Payroll Dashboard',
            'post_content'  => 'This page allows managers to generate payroll reports and manage payroll records.',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'payroll',
            'page_template' => 'page-payroll.php'
        ));
        
        if ($payroll_page_id) {
            error_log('LinkageClock: Payroll page created with ID: ' . $payroll_page_id);
        }
    }
    
    // Create Desktop Access Notice page if it doesn't exist
    if (!$desktop_only_page) {
        $desktop_only_page = wp_insert_post(array(
            'post_title'    => 'Desktop Access Notice',
            'post_content'  => 'This page is only accessible on desktop devices.',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => 'desktop-only',
            'page_template' => 'page-desktop-only.php'
        ));
    }
}
add_action('after_switch_theme', 'linkage_create_default_pages');