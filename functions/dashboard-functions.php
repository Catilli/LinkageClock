<?php
/**
 * Dashboard functions for LinkageClock
 */

/**
 * Get all employees with their current status
 */
function linkage_get_all_employees_status() {
    global $wpdb;
    $status_table = $wpdb->prefix . 'linkage_employee_status';
    $users_table = $wpdb->users;
    
    // Get all users who have any of our custom capabilities
    $query = "
        SELECT 
            u.ID,
            u.display_name,
            u.user_email,
            COALESCE(es.status, 'clocked_out') as current_status,
            COALESCE(es.last_action_time, 'Never') as last_action_time,
            COALESCE(es.last_action_type, 'None') as last_action_type
        FROM $users_table u
        LEFT JOIN $status_table es ON u.ID = es.user_id
        WHERE u.ID IN (
            SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
            WHERE meta_key = '{$wpdb->prefix}capabilities' 
            AND (
                meta_value LIKE '%employee%' 
                OR meta_value LIKE '%hr_manager%' 
                OR meta_value LIKE '%administrator%'
            )
        )
        ORDER BY u.display_name ASC
    ";
    
    return $wpdb->get_results($query);
}

/**
 * Update employee status
 */
function linkage_update_employee_status($user_id, $status, $action_type, $notes = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'linkage_employee_status';
    
    $data = array(
        'user_id' => $user_id,
        'status' => $status,
        'last_action_time' => current_time('mysql'),
        'last_action_type' => $action_type,
        'notes' => $notes
    );
    
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d",
        $user_id
    ));
    
    if ($existing) {
        return $wpdb->update($table, $data, array('user_id' => $user_id));
    } else {
        return $wpdb->insert($table, $data);
    }
}

/**
 * Get employee status by user ID
 */
function linkage_get_employee_status($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'linkage_employee_status';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d",
        $user_id
    ));
}

/**
 * Format time difference for display
 */
function linkage_format_time_ago($datetime) {
    if ($datetime === 'Never' || empty($datetime)) {
        return 'Never';
    }
    
    $time = strtotime($datetime);
    $now = current_time('timestamp');
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}

/**
 * Get user role display name
 */
function linkage_get_user_role_display($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return 'Unknown';
    
    $roles = $user->roles;
    if (empty($roles)) return 'No Role';
    
    $role_names = array(
        'employee' => 'Employee',
        'hr_manager' => 'HR Manager',
        'administrator' => 'Administrator'
    );
    
    $role = $roles[0];
    return isset($role_names[$role]) ? $role_names[$role] : ucfirst($role);
}

/**
 * Initialize employee status for users who don't have a status record
 */
function linkage_initialize_employee_status() {
    global $wpdb;
    $status_table = $wpdb->prefix . 'linkage_employee_status';
    $users_table = $wpdb->users;
    
    // Get users who don't have a status record
    $query = "
        SELECT u.ID, u.display_name
        FROM $users_table u
        WHERE u.ID IN (
            SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
            WHERE meta_key = '{$wpdb->prefix}capabilities' 
            AND (
                meta_value LIKE '%employee%' 
                OR meta_value LIKE '%hr_manager%' 
                OR meta_value LIKE '%administrator%'
            )
        )
        AND u.ID NOT IN (
            SELECT user_id FROM $status_table
        )
    ";
    
    $users_without_status = $wpdb->get_results($query);
    
    foreach ($users_without_status as $user) {
        linkage_update_employee_status($user->ID, 'clocked_out', 'initial', 'Initial status');
    }
    
    return count($users_without_status);
}

/**
 * AJAX handler for updating employee status
 */
function linkage_ajax_update_employee_status() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('linkage_manage_employees')) {
        wp_die('Insufficient permissions');
    }
    
    $user_id = intval($_POST['user_id']);
    $status = sanitize_text_field($_POST['status']);
    $action_type = sanitize_text_field($_POST['action_type']);
    $notes = sanitize_textarea_field($_POST['notes']);
    
    $result = linkage_update_employee_status($user_id, $status, $action_type, $notes);
    
    if ($result !== false) {
        wp_send_json_success('Status updated successfully');
    } else {
        wp_send_json_error('Failed to update status');
    }
}
add_action('wp_ajax_linkage_update_employee_status', 'linkage_ajax_update_employee_status');

/**
 * Enqueue dashboard scripts
 */
function linkage_enqueue_dashboard_scripts() {
    if (is_front_page() && is_user_logged_in()) {
        wp_enqueue_script('linkage-dashboard', get_template_directory_uri() . '/js/dashboard.js', array('jquery'), '1.0.0', true);
        wp_localize_script('linkage-dashboard', 'linkage_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('linkage_dashboard_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'linkage_enqueue_dashboard_scripts');
