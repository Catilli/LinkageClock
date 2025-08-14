<?php
/**
 * Disable WordPress Comments Functionality Completely
 * 
 * This file contains all functions to completely disable and remove
 * WordPress comments functionality from both frontend and admin areas.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove comment support from all post types
 */
function linkage_disable_comments_post_types_support() {
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
}
add_action('admin_init', 'linkage_disable_comments_post_types_support');

/**
 * Close comments on the front-end
 */
function linkage_disable_comments_status() {
    return false;
}
add_filter('comments_open', 'linkage_disable_comments_status', 20, 2);
add_filter('pings_open', 'linkage_disable_comments_status', 20, 2);

/**
 * Hide existing comments
 */
function linkage_disable_comments_hide_existing_comments($comments) {
    $comments = array();
    return $comments;
}
add_filter('comments_array', 'linkage_disable_comments_hide_existing_comments', 10, 2);

/**
 * Remove comments page from admin menu
 */
function linkage_disable_comments_admin_menu() {
    remove_menu_page('edit-comments.php');
}
add_action('admin_menu', 'linkage_disable_comments_admin_menu');

/**
 * Redirect any user trying to access comments page
 */
function linkage_disable_comments_admin_menu_redirect() {
    global $pagenow;
    if ($pagenow === 'edit-comments.php') {
        wp_redirect(admin_url());
        exit;
    }
}
add_action('admin_init', 'linkage_disable_comments_admin_menu_redirect');

/**
 * Remove comments metabox from dashboard
 */
function linkage_disable_comments_dashboard() {
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}
add_action('admin_init', 'linkage_disable_comments_dashboard');

/**
 * Remove comments links from admin bar
 */
function linkage_disable_comments_admin_bar() {
    if (is_admin_bar_showing()) {
        remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
    }
}
add_action('init', 'linkage_disable_comments_admin_bar');

/**
 * Remove comment-reply script for themes that include it
 */
function linkage_disable_comments_reply_script() {
    wp_deregister_script('comment-reply');
}
add_action('wp_enqueue_scripts', 'linkage_disable_comments_reply_script');

/**
 * Remove comments from admin bar
 */
function linkage_remove_comments_admin_bar($wp_admin_bar) {
    $wp_admin_bar->remove_node('comments');
}
add_action('admin_bar_menu', 'linkage_remove_comments_admin_bar', 999);

/**
 * Disable comments REST API endpoints
 */
function linkage_disable_comments_rest_api() {
    // Remove comments from REST API
    add_filter('rest_endpoints', function($endpoints) {
        if (isset($endpoints['/wp/v2/comments'])) {
            unset($endpoints['/wp/v2/comments']);
        }
        if (isset($endpoints['/wp/v2/comments/(?P<id>[\d]+)'])) {
            unset($endpoints['/wp/v2/comments/(?P<id>[\d]+)']);
        }
        return $endpoints;
    });
}
add_action('rest_api_init', 'linkage_disable_comments_rest_api');

/**
 * Remove comment feeds
 */
function linkage_disable_comment_feeds() {
    // Remove comment feeds
    remove_action('wp_head', 'feed_links_extra', 3);
    
    // Redirect comment feed requests
    if (is_comment_feed()) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('template_redirect', 'linkage_disable_comment_feeds');

/**
 * Remove comment form from pages/posts
 */
function linkage_remove_comment_form() {
    if (is_single() || is_page()) {
        return '';
    }
}
add_filter('comments_template', 'linkage_remove_comment_form');

/**
 * Update comment count to zero
 */
function linkage_disable_comment_count() {
    return 0;
}
add_filter('get_comments_number', 'linkage_disable_comment_count');

/**
 * Remove comment columns from post/page admin lists
 */
function linkage_remove_comment_columns($columns) {
    unset($columns['comments']);
    return $columns;
}

/**
 * Apply comment column removal to all post types
 */
function linkage_remove_comment_columns_all_post_types() {
    $post_types = get_post_types(array('public' => true));
    foreach ($post_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'linkage_remove_comment_columns');
    }
    
    // Also apply to pages specifically
    add_filter('manage_pages_columns', 'linkage_remove_comment_columns');
}
add_action('admin_init', 'linkage_remove_comment_columns_all_post_types');

/**
 * Remove comment quick edit option
 */
function linkage_remove_comment_quick_edit($column_name, $post_type) {
    if ($column_name == 'comments') {
        return false;
    }
}
add_action('quick_edit_show_taxonomy', 'linkage_remove_comment_quick_edit', 10, 2);

/**
 * Remove comment status from bulk edit
 */
function linkage_remove_comment_bulk_edit() {
    echo '<style>
        .inline-edit-col-right .comment-status-div,
        .bulk-edit .comment-status-div,
        #bulk-edit .comment-status-div {
            display: none !important;
        }
    </style>';
}
add_action('admin_head-edit.php', 'linkage_remove_comment_bulk_edit');

/**
 * Remove comment metabox from post/page edit screens
 */
function linkage_remove_comment_metaboxes() {
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        remove_meta_box('commentstatusdiv', $post_type, 'normal');
        remove_meta_box('commentsdiv', $post_type, 'normal');
        remove_meta_box('trackbacksdiv', $post_type, 'normal');
    }
}
add_action('admin_menu', 'linkage_remove_comment_metaboxes');

/**
 * Remove comment options from Screen Options
 */
function linkage_remove_comment_screen_options() {
    add_filter('screen_options_show_screen', function($show_screen, $screen) {
        if (isset($screen->id) && (strpos($screen->id, 'edit-') === 0 || $screen->id === 'edit')) {
            echo '<style>
                #comments-hide,
                label[for="comments-hide"] {
                    display: none !important;
                }
            </style>';
        }
        return $show_screen;
    }, 10, 2);
}
add_action('admin_head', 'linkage_remove_comment_screen_options');
