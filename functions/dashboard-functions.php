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
            'last_action_type' => $last_action_type,
            'break_start_time' => get_user_meta($user->ID, 'linkage_break_start_time', true)
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
 * Format actual time for display (instead of relative time)
 */
function linkage_format_actual_time($datetime) {
    if ($datetime === 'Never' || empty($datetime)) {
        return 'Never';
    }
    
    $time = strtotime($datetime);
    $now = current_time('timestamp');
    $diff = $now - $time;
    
    // If it's today, show time only
    if ($diff < 86400 && date('Y-m-d', $time) === date('Y-m-d', $now)) {
        return date('g:i A', $time); // e.g., "2:30 PM"
    }
    // If it's yesterday
    elseif ($diff < 172800 && date('Y-m-d', $time) === date('Y-m-d', $now - 86400)) {
        return 'Yesterday ' . date('g:i A', $time); // e.g., "Yesterday 2:30 PM"
    }
    // If it's within the last week
    elseif ($diff < 604800) {
        return date('D g:i A', $time); // e.g., "Mon 2:30 PM"
    }
    // If it's older
    else {
        return date('M j, g:i A', $time); // e.g., "Jan 15, 2:30 PM"
    }
}

/**
 * Format time difference for display (kept for backward compatibility)
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
 * AJAX handler for clock actions (clock in/out, break start/end)
 */
function linkage_ajax_clock_action() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    
    $user_id = get_current_user_id();
    $action = sanitize_text_field($_POST['action_type']);
    
    $allowed_actions = array('clock_in', 'clock_out', 'break_start', 'break_end');
    if (!in_array($action, $allowed_actions)) {
        wp_send_json_error('Invalid action');
    }
    
    // Get current employee status
    $current_status = get_user_meta($user_id, 'linkage_employee_status', true);
    
    // Handle different actions
    switch ($action) {
        case 'clock_in':
            $result = linkage_update_employee_status($user_id, 'clocked_in', 'clock_in', 'Clocked in via toolbar');
            // Store clock in time for timer calculation
            update_user_meta($user_id, 'linkage_clock_in_time', current_time('mysql'));
            update_user_meta($user_id, 'linkage_work_seconds', 0); // Reset work timer
            update_user_meta($user_id, 'linkage_break_seconds', 0); // Reset break timer
            break;
            
        case 'clock_out':
            $result = linkage_update_employee_status($user_id, 'clocked_out', 'clock_out', 'Clocked out via toolbar');
            // Clear timer data
            delete_user_meta($user_id, 'linkage_clock_in_time');
            delete_user_meta($user_id, 'linkage_break_start_time');
            delete_user_meta($user_id, 'linkage_work_seconds');
            delete_user_meta($user_id, 'linkage_break_seconds');
            break;
            
        case 'break_start':
            if ($current_status !== 'clocked_in') {
                wp_send_json_error('Must be clocked in to start break');
            }
            $result = linkage_update_employee_status($user_id, 'on_break', 'break_in', 'Started break via toolbar');
            // Store break start time and pause work timer
            update_user_meta($user_id, 'linkage_break_start_time', current_time('mysql'));
            
            // Calculate and store current work time
            $clock_in_time = get_user_meta($user_id, 'linkage_clock_in_time', true);
            $current_work_seconds = get_user_meta($user_id, 'linkage_work_seconds', true) ?: 0;
            if ($clock_in_time) {
                $work_time_since_last = strtotime(current_time('mysql')) - strtotime($clock_in_time);
                $total_work_seconds = $current_work_seconds + $work_time_since_last;
                update_user_meta($user_id, 'linkage_work_seconds', $total_work_seconds);
            }
            break;
            
        case 'break_end':
            if ($current_status !== 'on_break') {
                wp_send_json_error('Must be on break to end break');
            }
            $result = linkage_update_employee_status($user_id, 'clocked_in', 'break_out', 'Ended break via toolbar');
            
            // Calculate break time and restart work timer
            $break_start_time = get_user_meta($user_id, 'linkage_break_start_time', true);
            $current_break_seconds = get_user_meta($user_id, 'linkage_break_seconds', true) ?: 0;
            if ($break_start_time) {
                $break_duration = strtotime(current_time('mysql')) - strtotime($break_start_time);
                $total_break_seconds = $current_break_seconds + $break_duration;
                update_user_meta($user_id, 'linkage_break_seconds', $total_break_seconds);
            }
            
            // Reset clock in time for work timer continuation
            update_user_meta($user_id, 'linkage_clock_in_time', current_time('mysql'));
            delete_user_meta($user_id, 'linkage_break_start_time');
            break;
    }
    
    if ($result !== false) {
        // Get updated employee status for response
        $employee_status = linkage_get_employee_status($user_id);
        $work_seconds = get_user_meta($user_id, 'linkage_work_seconds', true) ?: 0;
        $break_seconds = get_user_meta($user_id, 'linkage_break_seconds', true) ?: 0;
        
        wp_send_json_success(array(
            'message' => 'Action completed successfully',
            'status' => $employee_status->status,
            'action' => $action,
            'work_seconds' => $work_seconds,
            'break_seconds' => $break_seconds,
            'clock_in_time' => get_user_meta($user_id, 'linkage_clock_in_time', true),
            'break_start_time' => get_user_meta($user_id, 'linkage_break_start_time', true)
        ));
    } else {
        wp_send_json_error('Failed to update status');
    }
}
add_action('wp_ajax_linkage_clock_action', 'linkage_ajax_clock_action');

/**
 * AJAX handler for getting employee updates (for AJAX refresh)
 */
function linkage_ajax_get_employee_updates() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    
    // Get all employees with their current status
    $employees = linkage_get_all_employees_status();
    
    $statuses = array();
    $positions = array();
    $hire_dates = array();
    
    foreach ($employees as $employee) {
        $user_id = $employee->ID;
        
        // Get status information
        $statuses[$user_id] = array(
            'status' => $employee->current_status,
            'last_action_time' => $employee->last_action_time,
            'last_action_type' => $employee->last_action_type
        );
        
        // Get position
        $position = get_user_meta($user_id, 'linkage_position', true) ?: 'Employee';
        $positions[$user_id] = $position;
        
        // Get hire date
        $hire_date = get_user_meta($user_id, 'linkage_hire_date', true);
        if ($hire_date) {
            $hire_dates[$user_id] = $hire_date;
        }
    }
    
    wp_send_json_success(array(
        'statuses' => $statuses,
        'positions' => $positions,
        'hire_dates' => $hire_dates,
        'timestamp' => current_time('mysql')
    ));
}
add_action('wp_ajax_linkage_get_employee_updates', 'linkage_ajax_get_employee_updates');

/**
 * Enqueue dashboard scripts
 */
function linkage_enqueue_dashboard_scripts() {
    if (is_user_logged_in()) {
        wp_enqueue_script('linkage-dashboard', get_template_directory_uri() . '/js/dashboard.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('linkage-timer', get_template_directory_uri() . '/js/timer.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('linkage-dashboard', 'linkage_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('linkage_dashboard_nonce')
        ));
        
        wp_localize_script('linkage-timer', 'linkage_ajax', array(
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

/**
 * Debug function to check time button visibility
 */
function linkage_debug_time_button() {
    if (!is_user_logged_in()) {
        echo "<p><strong>Debug:</strong> User not logged in</p>";
        return;
    }
    
    $current_user = wp_get_current_user();
    $employee_status = linkage_get_employee_status($current_user->ID);
    
    echo "<h3>Debug: Time Button Visibility</h3>";
    echo "<p><strong>User ID:</strong> " . $current_user->ID . "</p>";
    echo "<p><strong>User Name:</strong> " . $current_user->display_name . "</p>";
    echo "<p><strong>Employee Status:</strong> " . $employee_status->status . "</p>";
    echo "<p><strong>Last Action Time:</strong> " . $employee_status->last_action_time . "</p>";
    echo "<p><strong>Last Action Type:</strong> " . $employee_status->last_action_type . "</p>";
    
    // Check clock in time
    $clock_in_time = get_user_meta($current_user->ID, 'linkage_clock_in_time', true);
    echo "<p><strong>Clock In Time:</strong> " . ($clock_in_time ?: 'Not set') . "</p>";
    
    // Check break start time
    $break_start_time = get_user_meta($current_user->ID, 'linkage_break_start_time', true);
    echo "<p><strong>Break Start Time:</strong> " . ($break_start_time ?: 'Not set') . "</p>";
    
    // Calculate display logic
    $is_clocked_in = $employee_status->status === 'clocked_in';
    $is_on_break = $employee_status->status === 'on_break';
    $is_working = $is_clocked_in || $is_on_break;
    
    echo "<p><strong>Is Clocked In:</strong> " . ($is_clocked_in ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Is On Break:</strong> " . ($is_on_break ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Is Working:</strong> " . ($is_working ? 'Yes' : 'No') . "</p>";
    
    // Check button display logic
    $clock_button_display = 'flex'; // We changed this to always show
    $break_button_display = ($is_clocked_in || $is_on_break) ? 'flex' : 'none';
    
    echo "<p><strong>Clock Button Display:</strong> " . $clock_button_display . "</p>";
    echo "<p><strong>Break Button Display:</strong> " . $break_button_display . "</p>";
    
    // Check if button should be visible
    $button_should_be_visible = true; // Always true now
    echo "<p><strong>Button Should Be Visible:</strong> " . ($button_should_be_visible ? 'Yes' : 'No') . "</p>";
    
    echo "<hr>";
}
