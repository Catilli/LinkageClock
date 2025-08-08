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
 * Add informational notice for custom page templates
 */
function linkage_add_template_notice() {
    global $post;
    
    if (!$post || $post->post_type !== 'page') {
        return;
    }
    
    $page_template = get_page_template_slug($post->ID);
    
    if ($page_template === 'page-time-tracking.php') {
        ?>
        <div class="notice notice-warning" style="margin: 20px 0; border-left-color: #ffb900;">
            <p style="margin: 0; padding: 12px 0;">
                <strong>Time Tracking Template</strong><br>
                This page uses a custom time tracking form. The content below will be replaced by the time tracking interface when viewed on the frontend.
            </p>
        </div>
        <?php
    } elseif ($page_template === 'page-approve-timesheets.php') {
        ?>
        <div class="notice notice-warning" style="margin: 20px 0; border-left-color: #ffb900;">
            <p style="margin: 0; padding: 12px 0;">
                <strong>Approve Timesheets Template</strong><br>
                This page uses a custom timesheet approval interface. The content below will be replaced by the approval system when viewed on the frontend.
            </p>
        </div>
        <?php
    }
}
add_action('edit_form_after_title', 'linkage_add_template_notice');

/**
 * Add notice using Gutenberg-compatible approach
 */
function linkage_block_editor_notice() {
    global $post;
    
    if (!$post || $post->post_type !== 'page') {
        return;
    }
    
    $page_template = get_page_template_slug($post->ID);
    
    if ($page_template === 'page-time-tracking.php' || $page_template === 'page-approve-timesheets.php') {
        ?>
        <script>
        (function() {
            function addNotice() {
                // Wait for Gutenberg to be fully loaded
                if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
                    wp.data.subscribe(function() {
                        var editor = wp.data.select('core/editor');
                        if (editor && editor.getCurrentPostType() === 'page') {
                            var message = '';
                            if ('<?php echo $page_template; ?>' === 'page-time-tracking.php') {
                                message = '<strong>Time Tracking Template</strong><br>This page uses a custom time tracking form. The content below will be replaced by the time tracking interface when viewed on the frontend.';
                            } else {
                                message = '<strong>Approve Timesheets Template</strong><br>This page uses a custom timesheet approval interface. The content below will be replaced by the approval system when viewed on the frontend.';
                            }
                            
                            // Create notice element
                            var notice = document.createElement('div');
                            notice.className = 'notice notice-warning is-dismissible';
                            notice.style.margin = '20px 0';
                            notice.style.borderLeftColor = '#ffb900';
                            notice.innerHTML = '<p style="margin: 0; padding: 12px 0;">' + message + '</p>';
                            
                            // Find the editor area and insert notice
                            var editorArea = document.querySelector('.editor-styles-wrapper') || document.querySelector('.block-editor-writing-flow');
                            if (editorArea) {
                                var existingNotice = editorArea.querySelector('.linkage-template-notice');
                                if (!existingNotice) {
                                    notice.className += ' linkage-template-notice';
                                    editorArea.insertBefore(notice, editorArea.firstChild);
                                }
                            }
                        }
                    });
                } else {
                    // Fallback for when Gutenberg data is not available
                    setTimeout(addNotice, 100);
                }
            }
            
            // Start the process
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', addNotice);
            } else {
                addNotice();
            }
        })();
        </script>
        <?php
    }
}
add_action('admin_footer', 'linkage_block_editor_notice');