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
        $payroll_page_id = wp_insert_post(array(
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

/**
 * Set WordPress timezone automatically when theme is activated
 */
function linkage_set_automatic_timezone() {
    // Get the server's timezone
    $server_timezone = date_default_timezone_get();
    
    // If server timezone is valid, use it
    if ($server_timezone && in_array($server_timezone, timezone_identifiers_list())) {
        update_option('timezone_string', $server_timezone);
        error_log('LinkageClock: Set WordPress timezone to: ' . $server_timezone);
    } else {
        // Fallback: Try to detect timezone from server offset
        $offset = date('Z') / 3600; // Get offset in hours
        
        // Common timezone mappings based on offset
        $timezone_map = array(
            -12 => 'Pacific/Kwajalein',
            -11 => 'Pacific/Midway',
            -10 => 'Pacific/Honolulu',
            -9 => 'America/Anchorage',
            -8 => 'America/Los_Angeles',
            -7 => 'America/Denver',
            -6 => 'America/Chicago',
            -5 => 'America/New_York',
            -4 => 'America/Halifax',
            -3 => 'America/Sao_Paulo',
            -2 => 'Atlantic/South_Georgia',
            -1 => 'Atlantic/Azores',
            0 => 'UTC',
            1 => 'Europe/London',
            2 => 'Europe/Berlin',
            3 => 'Europe/Moscow',
            4 => 'Asia/Dubai',
            5 => 'Asia/Karachi',
            6 => 'Asia/Dhaka',
            7 => 'Asia/Bangkok',
            8 => 'Asia/Singapore',
            9 => 'Asia/Tokyo',
            10 => 'Australia/Sydney',
            11 => 'Pacific/Norfolk',
            12 => 'Pacific/Auckland'
        );
        
        if (isset($timezone_map[$offset])) {
            update_option('timezone_string', $timezone_map[$offset]);
            error_log('LinkageClock: Set WordPress timezone to: ' . $timezone_map[$offset] . ' (based on server offset: ' . $offset . ')');
        } else {
            // Final fallback: set a manual UTC offset
            update_option('gmt_offset', $offset);
            update_option('timezone_string', '');
            error_log('LinkageClock: Set WordPress GMT offset to: ' . $offset);
        }
    }
}
add_action('after_switch_theme', 'linkage_set_automatic_timezone');

/**
 * Add Page Template column to Pages admin
 */
function linkage_add_page_template_column($columns) {
    // Insert the template column after the title column
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['page_template'] = 'Page Template';
        }
    }
    
    return $new_columns;
}
add_filter('manage_pages_columns', 'linkage_add_page_template_column');

/**
 * Display content for Page Template column
 */
function linkage_display_page_template_column($column, $post_id) {
    if ($column === 'page_template') {
        $template = get_page_template_slug($post_id);
        
        if (empty($template) || $template === 'default') {
            echo '<span style="color: #666; font-style: italic;">Default</span>';
        } else {
            // Get template name from file
            $template_name = linkage_get_template_display_name($template);
            echo '<span style="color: #2271b1; font-weight: 500;">' . esc_html($template_name) . '</span>';
        }
    }
}
add_action('manage_pages_custom_column', 'linkage_display_page_template_column', 10, 2);

/**
 * Make Page Template column sortable
 */
function linkage_make_page_template_column_sortable($columns) {
    $columns['page_template'] = 'page_template';
    return $columns;
}
add_filter('manage_edit-page_sortable_columns', 'linkage_make_page_template_column_sortable');

/**
 * Handle sorting for Page Template column
 */
function linkage_sort_page_template_column($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('orderby') === 'page_template') {
        $query->set('meta_key', '_wp_page_template');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'linkage_sort_page_template_column');

/**
 * Get display name for page template
 */
function linkage_get_template_display_name($template_file) {
    // Handle our custom templates
    $custom_templates = array(
        'page-desktop-only.php' => 'Desktop Only Access',
        'page-employee.php' => 'Employee Profile'
    );
    
    if (isset($custom_templates[$template_file])) {
        return $custom_templates[$template_file];
    }
    
    // Try to get template name from file header
    $template_path = get_template_directory() . '/' . $template_file;
    
    if (file_exists($template_path)) {
        $file_data = get_file_data($template_path, array('Template Name' => 'Template Name'));
        
        if (!empty($file_data['Template Name'])) {
            return $file_data['Template Name'];
        }
    }
    
    // Fallback: clean up filename
    $name = basename($template_file, '.php');
    $name = str_replace(array('page-', '_', '-'), array('', ' ', ' '), $name);
    $name = ucwords($name);
    
    return $name;
}