<?php
/**
 * Dashboard functions for LinkageClock
 */

/**
 * Get all employees with their current status
 */
function linkage_get_all_employees_status() {
    global $wpdb;
    
    // Get all users who have employee-related roles or capabilities
    $users = get_users(array(
        'role__in' => array('employee', 'hr_manager', 'administrator'),
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    
    // If no users found with specific roles, get all users except admin
    if (empty($users)) {
        $users = get_users(array(
            'exclude' => array(1), // Exclude the main admin user
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
    }
    
    $employees = array();
    
    foreach ($users as $user) {
        $status = get_user_meta($user->ID, 'linkage_employee_status', true);
        $last_action_time = get_user_meta($user->ID, 'linkage_last_action_time', true);
        $last_action_type = get_user_meta($user->ID, 'linkage_last_action_type', true);
        
        // Set default values if not set
        if (empty($status)) {
            $status = 'clocked_out';
        }
        if (empty($last_action_time)) {
            $last_action_time = 'Never';
        }
        if (empty($last_action_type)) {
            $last_action_type = 'None';
        }
        
        $employees[] = (object) array(
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'current_status' => $status,
            'last_action_time' => $last_action_time,
            'last_action_type' => $last_action_type
        );
    }
    
    return $employees;
}

/**
 * Update employee status using WordPress user meta
 */
function linkage_update_employee_status($user_id, $status, $action_type, $notes = '') {
    // Update user meta fields
    update_user_meta($user_id, 'linkage_employee_status', $status);
    update_user_meta($user_id, 'linkage_last_action_time', current_time('mysql'));
    update_user_meta($user_id, 'linkage_last_action_type', $action_type);
    
    if (!empty($notes)) {
        update_user_meta($user_id, 'linkage_last_notes', $notes);
    }
    
    return true;
}

/**
 * Get employee status by user ID
 */
function linkage_get_employee_status($user_id) {
    $status = get_user_meta($user_id, 'linkage_employee_status', true);
    $last_action_time = get_user_meta($user_id, 'linkage_last_action_time', true);
    $last_action_type = get_user_meta($user_id, 'linkage_last_action_type', true);
    $notes = get_user_meta($user_id, 'linkage_last_notes', true);
    
    return (object) array(
        'user_id' => $user_id,
        'status' => $status ?: 'clocked_out',
        'last_action_time' => $last_action_time ?: 'Never',
        'last_action_type' => $last_action_type ?: 'None',
        'notes' => $notes
    );
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
 * Initialize employee status for users who don't have status records
 */
function linkage_initialize_employee_status() {
    $users = get_users(array(
        'role__in' => array('employee', 'hr_manager', 'administrator'),
        'fields' => 'ID'
    ));
    
    // If no users with specific roles, get all users except admin
    if (empty($users)) {
        $users = get_users(array(
            'exclude' => array(1),
            'fields' => 'ID'
        ));
    }
    
    $initialized_count = 0;
    
    foreach ($users as $user_id) {
        $status = get_user_meta($user_id, 'linkage_employee_status', true);
        
        // Only initialize if status is not already set
        if (empty($status)) {
            linkage_update_employee_status($user_id, 'clocked_out', 'initial', 'Initial status');
            $initialized_count++;
        }
    }
    
    return $initialized_count;
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
    if (is_user_logged_in()) {
        wp_enqueue_script('linkage-dashboard', get_template_directory_uri() . '/js/dashboard.js', array('jquery'), '1.0.0', true);
        wp_localize_script('linkage-dashboard', 'linkage_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('linkage_dashboard_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'linkage_enqueue_dashboard_scripts');

/**
 * Debug function to check user roles and capabilities
 */
function linkage_debug_user_roles() {
    echo "<h3>Debug: User Roles and Capabilities</h3>";
    
    // Get all users
    $users = get_users();
    
    foreach ($users as $user) {
        echo "<p><strong>User:</strong> " . $user->display_name . " (ID: " . $user->ID . ")</p>";
        echo "<p><strong>Roles:</strong> " . implode(', ', $user->roles) . "</p>";
        
        // Check employee status meta
        $status = get_user_meta($user->ID, 'linkage_employee_status', true);
        $last_action = get_user_meta($user->ID, 'linkage_last_action_time', true);
        echo "<p><strong>Employee Status:</strong> " . ($status ?: 'Not set') . "</p>";
        echo "<p><strong>Last Action:</strong> " . ($last_action ?: 'Never') . "</p>";
        
        echo "<hr>";
    }
}

/**
 * Debug function to check database tables
 */
function linkage_debug_database_tables() {
    global $wpdb;
    
    echo "<h3>Debug: Database Tables</h3>";
    
    // Check if timesheet table exists
    $timesheet_table = $wpdb->prefix . 'linkage_timesheets';
    $timesheet_exists = $wpdb->get_var("SHOW TABLES LIKE '$timesheet_table'") == $timesheet_table;
    echo "<p><strong>Timesheet Table Exists:</strong> " . ($timesheet_exists ? 'Yes' : 'No') . "</p>";
    
    // Check user meta for employee status
    $status_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'linkage_employee_status'");
    echo "<p><strong>Users with Employee Status:</strong> $status_count</p>";
    
    $status_records = $wpdb->get_results("SELECT * FROM {$wpdb->usermeta} WHERE meta_key = 'linkage_employee_status' LIMIT 5");
    echo "<p><strong>Sample Status Records:</strong></p>";
    echo "<pre>" . print_r($status_records, true) . "</pre>";
}

/**
 * Force create database tables (only for timesheets)
 */
function linkage_force_create_tables() {
    // Include the create-table.php file to ensure tables are created
    require_once get_template_directory() . '/functions/create-table.php';
    
    // Only create timesheet table, not employee status table
    linkage_create_timesheet_table();
    
    echo "<p><strong>Timesheet table created/updated successfully!</strong></p>";
    echo "<p><strong>Note:</strong> Employee status is now stored in WordPress user meta, not a separate table.</p>";
}

/**
 * Force initialize all users as employees
 */
function linkage_force_initialize_all_users() {
    // Get all users except admin
    $users = get_users(array(
        'exclude' => array(1),
        'fields' => 'ID'
    ));
    
    $initialized_count = 0;
    
    foreach ($users as $user_id) {
        // Add employee role if user doesn't have it
        $user = get_userdata($user_id);
        if ($user && !in_array('employee', $user->roles)) {
            $user->add_role('employee');
        }
        
        // Initialize status record
        $result = linkage_update_employee_status($user_id, 'clocked_out', 'force_initial', 'Force initialized');
        if ($result !== false) {
            $initialized_count++;
        }
    }
    
    echo "<p><strong>Initialized $initialized_count users as employees!</strong></p>";
    return $initialized_count;
}
