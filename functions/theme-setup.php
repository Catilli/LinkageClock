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
 * Disable Gutenberg editor for specific page templates
 */
function linkage_disable_gutenberg_for_templates($use_block_editor, $post) {
    // Get the page template
    $page_template = get_page_template_slug($post->ID);
    
    // Disable Gutenberg for specific templates
    $templates_to_disable = array(
        'page-time-tracking.php',
        'page-approve-timesheets.php'
    );
    
    if (in_array($page_template, $templates_to_disable)) {
        return false;
    }
    
    return $use_block_editor;
}
add_filter('use_block_editor_for_post', 'linkage_disable_gutenberg_for_templates', 10, 2);

/**
 * Add custom meta box for page templates that don't use Gutenberg
 */
function linkage_add_template_meta_box() {
    add_meta_box(
        'linkage_template_info',
        'Template Information',
        'linkage_template_info_callback',
        'page',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'linkage_add_template_meta_box');

/**
 * Meta box callback function
 */
function linkage_template_info_callback($post) {
    $page_template = get_page_template_slug($post->ID);
    
    if ($page_template === 'page-time-tracking.php') {
        echo '<div class="notice notice-info">';
        echo '<p><strong>Time Tracking Template</strong></p>';
        echo '<p>This page uses a custom time tracking form. The content editor is disabled for this template.</p>';
        echo '</div>';
    } elseif ($page_template === 'page-approve-timesheets.php') {
        echo '<div class="notice notice-info">';
        echo '<p><strong>Approve Timesheets Template</strong></p>';
        echo '<p>This page uses a custom timesheet approval interface. The content editor is disabled for this template.</p>';
        echo '</div>';
    }
}