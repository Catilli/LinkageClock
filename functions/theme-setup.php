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
    add_theme_support('custom-logo', array(
        'height'      => 0, // Auto height
        'width'       => 240,
        'flex-height' => true,
        'flex-width'  => true,
        'header-text' => array('site-title', 'site-description'),
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
 * Fallback menu function
 */
function linkage_fallback_menu() {
    echo '<ul class="flex space-x-6 text-gray-700">';
    echo '<li><a href="' . esc_url(home_url('/')) . '" class="hover:text-blue-600 transition duration-200">Home</a></li>';
    if (is_user_logged_in()) {
        echo '<li><a href="' . esc_url(home_url('/time-tracking')) . '" class="hover:text-blue-600 transition duration-200">Time Tracking</a></li>';
        if (current_user_can('linkage_approve_timesheets')) {
            echo '<li><a href="' . esc_url(home_url('/approve-timesheets')) . '" class="hover:text-blue-600 transition duration-200">Approve Timesheets</a></li>';
        }
    }
    echo '</ul>';
}

/**
 * Custom logo styling
 */
function linkage_custom_logo_setup() {
    $defaults = array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
        'header-text' => array('site-title', 'site-description'),
    );
    add_theme_support('custom-logo', $defaults);
}
add_action('after_setup_theme', 'linkage_custom_logo_setup');