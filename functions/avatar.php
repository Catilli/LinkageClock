<?php
/**
 * Avatar functions for LinkageClock theme
 * Handles Gravatar detection and fallback avatars
 */

/**
 * Check if a user has a Gravatar
 * 
 * @param string $email User's email address
 * @return bool True if Gravatar exists, false otherwise
 */
function linkage_has_gravatar($email) {
    if (empty($email)) {
        return false;
    }
    
    $gravatar_url = get_avatar_url($email, array('size' => 80, 'default' => '404'));
    
    // Check if Gravatar exists by trying to get a response
    if (function_exists('wp_remote_get')) {
        $response = wp_remote_get($gravatar_url);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get user avatar HTML (Gravatar or fallback)
 * 
 * @param string $email User's email address
 * @param string $display_name User's display name
 * @param int $size Avatar size in pixels (default: 40)
 * @param array $args Additional arguments for customization
 * @return string HTML for the avatar
 */
function linkage_get_avatar_html($email, $display_name, $size = 40, $args = array()) {
    // Default arguments
    $defaults = array(
        'class' => '',
        'fallback_bg' => 'bg-gray-300',
        'fallback_text_color' => 'text-gray-700',
        'border' => true,
        'alt_text' => ''
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Generate classes
    $img_classes = "w-{$size} h-{$size} rounded-full object-cover";
    $fallback_classes = "w-{$size} h-{$size} rounded-full flex items-center justify-center {$args['fallback_bg']}";
    
    // Convert pixel size to Tailwind classes for common sizes
    $size_classes = array(
        32 => 'w-8 h-8',
        40 => 'w-10 h-10', 
        64 => 'w-16 h-16',
        80 => 'w-20 h-20'
    );
    
    if (isset($size_classes[$size])) {
        $size_class = $size_classes[$size];
        $img_classes = "{$size_class} rounded-full object-cover";
        $fallback_classes = "{$size_class} rounded-full flex items-center justify-center {$args['fallback_bg']}";
    }
    
    // Add border if requested
    if ($args['border']) {
        $img_classes .= ' border border-gray-200';
    }
    
    // Add custom classes
    if (!empty($args['class'])) {
        $img_classes .= ' ' . $args['class'];
        $fallback_classes .= ' ' . $args['class'];
    }
    
    // Check if Gravatar exists
    $has_gravatar = linkage_has_gravatar($email);
    
    if ($has_gravatar) {
        $gravatar_url = get_avatar_url($email, array('size' => $size));
        $alt_text = !empty($args['alt_text']) ? $args['alt_text'] : esc_attr($display_name) . "'s Avatar";
        
        return sprintf(
            '<img src="%s" alt="%s" class="%s">',
            esc_url($gravatar_url),
            $alt_text,
            esc_attr($img_classes)
        );
    } else {
        // Fallback to initial-based avatar
        $initial = strtoupper(substr($display_name, 0, 1));
        $text_size = $size >= 64 ? 'text-2xl' : ($size >= 40 ? 'text-sm' : 'text-xs');
        
        return sprintf(
            '<div class="%s"><span class="%s %s font-medium">%s</span></div>',
            esc_attr($fallback_classes),
            esc_attr($args['fallback_text_color']),
            esc_attr($text_size),
            esc_html($initial)
        );
    }
}

/**
 * Display user avatar (echo version of linkage_get_avatar_html)
 * 
 * @param string $email User's email address
 * @param string $display_name User's display name
 * @param int $size Avatar size in pixels (default: 40)
 * @param array $args Additional arguments for customization
 */
function linkage_the_avatar($email, $display_name, $size = 40, $args = array()) {
    echo linkage_get_avatar_html($email, $display_name, $size, $args);
}

/**
 * Get avatar with wrapper div (useful for specific layouts)
 * 
 * @param string $email User's email address
 * @param string $display_name User's display name
 * @param int $size Avatar size in pixels (default: 40)
 * @param array $args Additional arguments
 * @return string HTML with wrapper div
 */
function linkage_get_avatar_with_wrapper($email, $display_name, $size = 40, $args = array()) {
    $defaults = array(
        'wrapper_class' => 'flex-shrink-0',
        'show_indicator' => false, // Show green checkmark for Gravatar users
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $size_classes = array(
        32 => 'w-8 h-8',
        40 => 'w-10 h-10',
        64 => 'w-16 h-16', 
        80 => 'w-20 h-20'
    );
    
    $wrapper_size = isset($size_classes[$size]) ? $size_classes[$size] : "w-{$size} h-{$size}";
    
    $html = sprintf('<div class="%s %s">', esc_attr($args['wrapper_class']), esc_attr($wrapper_size));
    
    // Add relative positioning if showing indicator
    if ($args['show_indicator'] && linkage_has_gravatar($email)) {
        $html = sprintf('<div class="%s %s relative">', esc_attr($args['wrapper_class']), esc_attr($wrapper_size));
    }
    
    $html .= linkage_get_avatar_html($email, $display_name, $size, $args);
    
    // Add Gravatar indicator if requested
    if ($args['show_indicator'] && linkage_has_gravatar($email)) {
        $indicator_size = $size >= 64 ? 'w-6 h-6' : 'w-4 h-4';
        $icon_size = $size >= 64 ? 'w-3 h-3' : 'w-2 h-2';
        
        $html .= sprintf(
            '<div class="absolute -bottom-1 -right-1 %s bg-green-500 rounded-full border-2 border-white flex items-center justify-center">
                <svg class="%s text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            </div>',
            esc_attr($indicator_size),
            esc_attr($icon_size)
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Quick helper for common avatar scenarios
 */

/**
 * Get small avatar for sidebar/navigation (32px)
 */
function linkage_get_small_avatar($email, $display_name, $args = array()) {
    $defaults = array('fallback_bg' => 'bg-blue-500', 'fallback_text_color' => 'text-white');
    $args = wp_parse_args($args, $defaults);
    return linkage_get_avatar_html($email, $display_name, 32, $args);
}

/**
 * Get medium avatar for lists/tables (40px)
 */
function linkage_get_medium_avatar($email, $display_name, $args = array()) {
    return linkage_get_avatar_html($email, $display_name, 40, $args);
}

/**
 * Get large avatar for profiles (80px)
 */
function linkage_get_large_avatar($email, $display_name, $args = array()) {
    $defaults = array('show_indicator' => true);
    $args = wp_parse_args($args, $defaults);
    return linkage_get_avatar_with_wrapper($email, $display_name, 80, $args);
}
