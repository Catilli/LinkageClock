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
        // Use database-based status instead of user meta
        $employee_status = linkage_get_employee_status_from_database($user->ID);
        
        $employees[] = (object) array(
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'current_status' => $employee_status->status,
            'last_action_time' => $employee_status->last_action_time,
            'last_action_type' => $employee_status->last_action_type,
            'break_start_time' => $employee_status->break_start_time
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
    
    // Check if user is administrator
    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied. Administrator privileges required.');
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
    
    // Check if user has clock capabilities
    if (!current_user_can('linkage_clock_in_out') && !current_user_can('administrator')) {
        wp_send_json_error('Insufficient permissions for clock actions');
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
            
            // Create new attendance log record
            $attendance_id = linkage_create_attendance_log($user_id, 'clock_in');
            
            // Store minimal meta for backward compatibility (but don't create new ones)
            if (get_user_meta($user_id, 'linkage_employee_status', true)) {
                update_user_meta($user_id, 'linkage_employee_status', 'clocked_in');
            }
            if (get_user_meta($user_id, 'linkage_clock_in_time', true)) {
                update_user_meta($user_id, 'linkage_clock_in_time', current_time('mysql'));
            }
            break;
            
        case 'clock_out':
            $result = linkage_update_employee_status($user_id, 'clocked_out', 'clock_out', 'Clocked out via toolbar');
            
            // Update attendance log record with clock out time and calculate total hours
            $attendance_id = linkage_update_attendance_log($user_id, 'clock_out');
            
            // Clear minimal meta for backward compatibility (but don't create new ones)
            if (get_user_meta($user_id, 'linkage_employee_status', true)) {
                delete_user_meta($user_id, 'linkage_employee_status');
            }
            if (get_user_meta($user_id, 'linkage_clock_in_time', true)) {
                delete_user_meta($user_id, 'linkage_clock_in_time');
            }
            if (get_user_meta($user_id, 'linkage_break_start_time', true)) {
                delete_user_meta($user_id, 'linkage_break_start_time');
            }
            break;
            
        case 'break_start':
            if ($current_status !== 'clocked_in') {
                wp_send_json_error('Must be clocked in to start break');
            }
            
            // Check break capability
            if (!current_user_can('linkage_take_break') && !current_user_can('administrator')) {
                wp_send_json_error('Insufficient permissions for break actions');
            }
            
            $result = linkage_update_employee_status($user_id, 'on_break', 'break_in', 'Started break via toolbar');
            
            // Update attendance log record with lunch start time
            $attendance_id = linkage_update_attendance_log($user_id, 'break_start');
            
            // Store minimal meta for backward compatibility (but don't create new ones)
            if (get_user_meta($user_id, 'linkage_break_start_time', true)) {
                update_user_meta($user_id, 'linkage_break_start_time', current_time('mysql'));
            }
            break;
            
        case 'break_end':
            if ($current_status !== 'on_break') {
                wp_send_json_error('Must be on break to end break');
            }
            
            // Check break capability
            if (!current_user_can('linkage_take_break') && !current_user_can('administrator')) {
                wp_send_json_error('Insufficient permissions for break actions');
            }
            
            $result = linkage_update_employee_status($user_id, 'clocked_in', 'break_out', 'Ended break via toolbar');
            
            // Update attendance log record with lunch end time
            $attendance_id = linkage_update_attendance_log($user_id, 'break_end');
            
            // Clear minimal meta for backward compatibility (but don't create new ones)
            if (get_user_meta($user_id, 'linkage_break_start_time', true)) {
                delete_user_meta($user_id, 'linkage_break_start_time');
            }
            break;
    }
    
    if ($result !== false) {
        // Return the status that was just set, not read from database
        $response_status = '';
        switch ($action) {
            case 'clock_in':
                $response_status = 'clocked_in';
                break;
            case 'clock_out':
                $response_status = 'clocked_out';
                break;
            case 'break_start':
                $response_status = 'on_break';
                break;
            case 'break_end':
                $response_status = 'clocked_in';
                break;
            default:
                $response_status = 'clocked_out';
                break;
        }
        
        // Debug: Log what's happening
        error_log("LinkageClock: Action '$action' completed. Setting status to '$response_status' for user $user_id");
        
        // Get updated employee status with calculated times from database for time calculations
        $employee_status = linkage_get_employee_status_from_database($user_id);
        
        // Debug: Log what the database function returned
        error_log("LinkageClock: Database function returned status: " . $employee_status->status);
        
        wp_send_json_success(array(
            'message' => 'Action completed successfully',
            'status' => $response_status, // Use the status we just set
            'action' => $action,
            'work_seconds' => $employee_status->work_seconds,
            'break_seconds' => $employee_status->break_seconds,
            'clock_in_time' => $employee_status->clock_in_time,
            'break_start_time' => $employee_status->break_start_time,
            'attendance_id' => $attendance_id
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
    $work_times = array();
    $break_times = array();
    
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
        
        // Get real-time calculated work and break times
        $employee_status = linkage_get_employee_status_from_database($user_id);
        $work_times[$user_id] = $employee_status->work_seconds;
        $break_times[$user_id] = $employee_status->break_seconds;
    }
    
    wp_send_json_success(array(
        'statuses' => $statuses,
        'positions' => $positions,
        'hire_dates' => $hire_dates,
        'work_times' => $work_times,
        'break_times' => $break_times,
        'timestamp' => current_time('mysql')
    ));
}
add_action('wp_ajax_linkage_get_employee_updates', 'linkage_ajax_get_employee_updates');

/**
 * AJAX handler for getting real-time time updates
 */
function linkage_ajax_get_time_updates() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    
    // All logged-in users can get their own time updates
    $user_id = get_current_user_id();
    $employee_status = linkage_get_employee_status_from_database($user_id);
    
    wp_send_json_success(array(
        'work_seconds' => $employee_status->work_seconds,
        'break_seconds' => $employee_status->break_seconds,
        'status' => $employee_status->status,
        'work_time_display' => linkage_format_time_display($employee_status->work_seconds),
        'break_time_display' => linkage_format_time_display($employee_status->break_seconds),
        'timestamp' => current_time('mysql')
    ));
}
add_action('wp_ajax_linkage_get_time_updates', 'linkage_ajax_get_time_updates');

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
    
    // Check if attendance logs table exists
    $attendance_table = $wpdb->prefix . 'linkage_attendance_logs';
    $attendance_exists = $wpdb->get_var("SHOW TABLES LIKE '$attendance_table'") == $attendance_table;
    echo "<p><strong>Attendance Logs Table Exists:</strong> " . ($attendance_exists ? 'Yes' : 'No') . "</p>";
    
    // Check user meta for employee status
    $status_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'linkage_employee_status'");
    echo "<p><strong>Users with Employee Status:</strong> $status_count</p>";
    
    $status_records = $wpdb->get_results("SELECT * FROM {$wpdb->usermeta} WHERE meta_key = 'linkage_employee_status' LIMIT 5");
    echo "<p><strong>Sample Status Records:</strong></p>";
    echo "<pre>" . print_r($status_records, true) . "</pre>";
}

/**
 * Force create database tables (attendance logs only)
 */
function linkage_force_create_tables() {
    // Include the create-table.php file to ensure tables are created
    require_once get_template_directory() . '/functions/create-table.php';
    
    // Create attendance logs table
    linkage_create_attendance_logs_table();
    
    echo "<p><strong>Attendance logs table created/updated successfully!</strong></p>";
    echo "<p><strong>✓ Attendance logs table</strong></p>";
    echo "<p><strong>Note:</strong> Employee status is stored in WordPress user meta, and detailed time tracking is in the attendance logs table.</p>";
}

/**
 * Force initialize all users as employees
 */
function linkage_force_initialize_all_users() {
    // Clean up any incorrect attendance records first
    echo "<h3>Step 1: Cleaning up attendance records...</h3>";
    $cleanup_result = linkage_cleanup_attendance_records();
    
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
    
    // Update capabilities for all users
    if (function_exists('linkage_update_existing_users_capabilities')) {
        $capabilities_updated = linkage_update_existing_users_capabilities();
        echo "<p><strong>Updated capabilities for $capabilities_updated users!</strong></p>";
    }
    
    echo "<p><strong>Initialized $initialized_count users as employees!</strong></p>";
    
    // Debug: Show current database status
    echo "<h3>Current Database Status After Cleanup:</h3>";
    linkage_debug_database_status();
    
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

/**
 * Calculate current work time for an employee (server-side) - DEPRECATED
 * This function is kept for backward compatibility but is no longer used
 * The system now uses linkage_get_employee_status_from_database() instead
 */
function linkage_calculate_current_work_time($user_id) {
    // This function is deprecated - use linkage_get_employee_status_from_database() instead
    $employee_status = linkage_get_employee_status_from_database($user_id);
    return $employee_status->work_seconds;
}

/**
 * Calculate current break time for an employee (server-side) - DEPRECATED
 * This function is kept for backward compatibility but is no longer used
 * The system now uses linkage_get_employee_status_from_database() instead
 */
function linkage_calculate_current_break_time($user_id) {
    // This function is deprecated - use linkage_get_employee_status_from_database() instead
    $employee_status = linkage_get_employee_status_from_database($user_id);
    return $employee_status->break_seconds;
}

/**
 * Get real-time employee status with calculated times - DEPRECATED
 * This function is kept for backward compatibility but is no longer used
 * The system now uses linkage_get_employee_status_from_database() instead
 */
function linkage_get_employee_status_with_times($user_id) {
    // This function is deprecated - use linkage_get_employee_status_from_database() instead
    return linkage_get_employee_status_from_database($user_id);
}

/**
 * Format time display from seconds
 */
function linkage_format_time_display($seconds) {
    if ($seconds < 0) $seconds = 0;
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    } else {
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}

/**
 * Get attendance logs for export with all required fields
 */
function linkage_get_attendance_logs_for_export($user_id = null, $start_date = null, $end_date = null) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $users_table = $wpdb->users;
    $usermeta_table = $wpdb->usermeta;
    
    $where_conditions = array();
    $where_values = array();
    
    // Filter by user if specified
    if ($user_id) {
        $where_conditions[] = "al.user_id = %d";
        $where_values[] = $user_id;
    }
    
    // Filter by date range if specified
    if ($start_date) {
        $where_conditions[] = "al.work_date >= %s";
        $where_values[] = $start_date;
    }
    
    if ($end_date) {
        $where_conditions[] = "al.work_date <= %s";
        $where_values[] = $end_date;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $query = "
        SELECT 
            u.display_name as employee_name,
            COALESCE(um_company.meta_value, u.ID) as employee_id,
            al.work_date,
            al.time_in,
            al.lunch_start,
            al.lunch_end,
            al.time_out,
            al.total_hours,
            al.status
        FROM {$table} al
        LEFT JOIN {$users_table} u ON al.user_id = u.ID
        LEFT JOIN {$usermeta_table} um_company ON u.ID = um_company.user_id AND um_company.meta_key = 'linkage_company_id'
        {$where_clause}
        ORDER BY al.work_date DESC, u.display_name ASC
    ";
    
    if (!empty($where_values)) {
        $query = $wpdb->prepare($query, $where_values);
    }
    
    return $wpdb->get_results($query, ARRAY_A);
}

/**
 * Export attendance data to CSV
 */
function linkage_export_attendance_csv($user_id = null, $start_date = null, $end_date = null) {
    $logs = linkage_get_attendance_logs_for_export($user_id, $start_date, $end_date);
    
    if (empty($logs)) {
        return false;
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, array(
        'Employee Name',
        'Employee ID', 
        'Date',
        'Time In',
        'Lunch Start',
        'Lunch End',
        'Time Out',
        'Total Hours',
        'Status'
    ));
    
    // Add data rows
    foreach ($logs as $log) {
        fputcsv($output, array(
            $log['employee_name'],
            $log['employee_id'] ?: 'N/A',
            $log['work_date'],
            $log['time_in'] ? date('H:i:s', strtotime($log['time_in'])) : '',
            $log['lunch_start'] ? date('H:i:s', strtotime($log['lunch_start'])) : '',
            $log['lunch_end'] ? date('H:i:s', strtotime($log['lunch_end'])) : '',
            $log['time_out'] ? date('H:i:s', strtotime($log['time_out'])) : '',
            $log['total_hours'],
            $log['status']
        ));
    }
    
    fclose($output);
    return true;
}

/**
 * Export attendance data to XLSX (requires PhpSpreadsheet library)
 */
function linkage_export_attendance_xlsx($user_id = null, $start_date = null, $end_date = null) {
    // Check if PhpSpreadsheet is available
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        return false;
    }
    
    $logs = linkage_get_attendance_logs_for_export($user_id, $start_date, $end_date);
    
    if (empty($logs)) {
        return false;
    }
    
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = array(
            'Employee Name',
            'Employee ID',
            'Date', 
            'Time In',
            'Lunch Start',
            'Lunch End',
            'Time Out',
            'Total Hours',
            'Status'
        );
        
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }
        
        // Add data rows
        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValueByColumnAndRow(1, $row, $log['employee_name']);
            $sheet->setCellValueByColumnAndRow(2, $row, $log['employee_id'] ?: 'N/A');
            $sheet->setCellValueByColumnAndRow(3, $row, $log['work_date']);
            $sheet->setCellValueByColumnAndRow(4, $row, $log['time_in'] ? date('H:i:s', strtotime($log['time_in'])) : '');
            $sheet->setCellValueByColumnAndRow(5, $row, $log['lunch_start'] ? date('H:i:s', strtotime($log['lunch_start'])) : '');
            $sheet->setCellValueByColumnAndRow(6, $row, $log['lunch_end'] ? date('H:i:s', strtotime($log['lunch_end'])) : '');
            $sheet->setCellValueByColumnAndRow(7, $row, $log['time_out'] ? date('H:i:s', strtotime($log['time_out'])) : '');
            $sheet->setCellValueByColumnAndRow(8, $row, $log['total_hours']);
            $sheet->setCellValueByColumnAndRow(9, $row, $log['status']);
            $row++;
        }
        
        // Auto-size columns
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        
        // Set headers for XLSX download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="attendance_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Create writer and output
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * AJAX handler for export requests
 */
function linkage_ajax_export_attendance() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_export_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is administrator
    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied. Administrator privileges required.');
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
    $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
    
    // Validate date format
    if ($start_date && !strtotime($start_date)) {
        wp_send_json_error('Invalid start date');
    }
    if ($end_date && !strtotime($end_date)) {
        wp_send_json_error('Invalid end date');
    }
    
    // Perform export
    $result = false;
    if ($format === 'xlsx') {
        $result = linkage_export_attendance_xlsx($user_id, $start_date, $end_date);
    } else {
        $result = linkage_export_attendance_csv($user_id, $start_date, $end_date);
    }
    
    if ($result) {
        wp_die(); // Exit to prevent additional output
    } else {
        wp_send_json_error('Export failed');
    }
}
add_action('wp_ajax_linkage_export_attendance', 'linkage_ajax_export_attendance');

/**
 * Create a new attendance log record
 */
function linkage_create_attendance_log($user_id, $action) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $current_time = current_time('mysql');
    $work_date = current_time('Y-m-d');
    
    // Check if a record already exists for today
    $existing_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'active'",
        $user_id,
        $work_date
    ));
    
    if ($existing_record) {
        // Update existing record
        $wpdb->update(
            $table,
            array(
                'time_in' => $current_time,
                'updated_at' => $current_time
            ),
            array('id' => $existing_record->id),
            array('%s', '%s'),
            array('%d')
        );
        return $existing_record->id;
    } else {
        // Create new record
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'work_date' => $work_date,
                'time_in' => $current_time,
                'status' => 'active',
                'created_at' => $current_time,
                'updated_at' => $current_time
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
}

/**
 * Update an existing attendance log record
 */
function linkage_update_attendance_log($user_id, $action) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $current_time = current_time('mysql');
    $work_date = current_time('Y-m-d');
    
    // Get the active record for today
    $record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'active'",
        $user_id,
        $work_date
    ));
    
    if (!$record) {
        return false;
    }
    
    $update_data = array('updated_at' => $current_time);
    
    switch ($action) {
        case 'break_start':
            $update_data['lunch_start'] = $current_time;
            break;
            
        case 'break_end':
            $update_data['lunch_end'] = $current_time;
            break;
            
        case 'clock_out':
            $update_data['time_out'] = $current_time;
            $update_data['status'] = 'completed';
            
            // Calculate total hours
            $time_in = strtotime($record->time_in);
            $time_out = strtotime($current_time);
            $lunch_start = $record->lunch_start ? strtotime($record->lunch_start) : null;
            $lunch_end = $record->lunch_end ? strtotime($record->lunch_end) : null;
            
            $total_seconds = $time_out - $time_in;
            
            // Subtract lunch time if lunch was taken
            if ($lunch_start && $lunch_end) {
                $lunch_seconds = $lunch_end - $lunch_start;
                $total_seconds -= $lunch_seconds;
            }
            
            $total_hours = round($total_seconds / 3600, 2);
            $update_data['total_hours'] = $total_hours;
            break;
    }
    
    $wpdb->update(
        $table,
        $update_data,
        array('id' => $record->id),
        null,
        array('%d')
    );
    
    return $record->id;
}

/**
 * Get employee status with calculated times from database instead of meta keys
 */
function linkage_get_employee_status_from_database($user_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $work_date = current_time('Y-m-d');
    
    // Get the active record for today
    $record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'active'",
        $user_id,
        $work_date
    ));
    
    if (!$record) {
        // Check if there's a completed record for today
        $completed_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'completed' ORDER BY id DESC LIMIT 1",
            $user_id,
            $work_date
        ));
        
        if ($completed_record) {
            return (object) array(
                'user_id' => $user_id,
                'status' => 'clocked_out',
                'last_action_time' => $completed_record->updated_at,
                'last_action_type' => 'clock_out',
                'notes' => '',
                'work_seconds' => 0,
                'break_seconds' => 0,
                'clock_in_time' => null,
                'break_start_time' => null
            );
        }
        
        // No record found, user is clocked out
        return (object) array(
            'user_id' => $user_id,
            'status' => 'clocked_out',
            'last_action_time' => 'Never',
            'last_action_type' => 'None',
            'notes' => '',
            'work_seconds' => 0,
            'break_seconds' => 0,
            'clock_in_time' => null,
            'break_start_time' => null
        );
    }
    
    // Calculate current work and break times
    $current_time = current_time('mysql');
    $work_seconds = 0;
    $break_seconds = 0;
    
    if ($record->time_in) {
        $time_in = strtotime($record->time_in);
        $time_now = strtotime($current_time);
        
        if ($record->lunch_start && !$record->lunch_end) {
            // On break - calculate work time up to lunch start
            $lunch_start = strtotime($record->lunch_start);
            $work_seconds = $lunch_start - $time_in;
            
            // Calculate break time from lunch start to now
            $break_seconds = $time_now - $lunch_start;
        } elseif ($record->lunch_start && $record->lunch_end) {
            // Lunch ended - calculate total work time
            $lunch_start = strtotime($record->lunch_start);
            $lunch_end = strtotime($record->lunch_end);
            $lunch_duration = $lunch_end - $lunch_start;
            
            $work_seconds = ($lunch_start - $time_in) + ($time_now - $lunch_end);
            $break_seconds = $lunch_duration;
        } else {
            // No lunch - calculate work time from clock in to now
            $work_seconds = $time_now - $time_in;
        }
    }
    
    // Determine status
    $status = 'clocked_out'; // Default to clocked out
    
    if ($record->time_in && !$record->time_out) {
        // User has clocked in but not out
        if ($record->lunch_start && !$record->lunch_end) {
            $status = 'on_break';
        } else {
            $status = 'clocked_in';
        }
    }
    
    return (object) array(
        'user_id' => $user_id,
        'status' => $status,
        'last_action_time' => $record->updated_at,
        'last_action_type' => $status === 'on_break' ? 'break_start' : ($status === 'clocked_in' ? 'clock_in' : 'clock_out'),
        'notes' => '',
        'work_seconds' => max(0, $work_seconds),
        'break_seconds' => max(0, $break_seconds),
        'clock_in_time' => $record->time_in,
        'break_start_time' => $record->lunch_start
    );
}

/**
 * Debug function to check actual database status vs displayed status
 */
function linkage_debug_database_status() {
    global $wpdb;
    
    echo "<h3>Debug: Database Status vs Displayed Status</h3>";
    
    // Get all users
    $users = get_users(array(
        'role__in' => array('employee', 'hr_manager', 'administrator'),
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    
    if (empty($users)) {
        $users = get_users(array(
            'exclude' => array(1),
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
    }
    
    foreach ($users as $user) {
        echo "<h4>User: " . $user->display_name . " (ID: " . $user->ID . ")</h4>";
        
        // Check user meta status (old method)
        $meta_status = get_user_meta($user->ID, 'linkage_employee_status', true);
        $meta_last_action = get_user_meta($user->ID, 'linkage_last_action_time', true);
        echo "<p><strong>User Meta Status:</strong> " . ($meta_status ?: 'Not set') . "</p>";
        echo "<p><strong>User Meta Last Action:</strong> " . ($meta_last_action ?: 'Never') . "</p>";
        
        // Check database status (new method)
        $db_status = linkage_get_employee_status_from_database($user->ID);
        echo "<p><strong>Database Status:</strong> " . $db_status->status . "</p>";
        echo "<p><strong>Database Last Action:</strong> " . $db_status->last_action_time . "</p>";
        
        // Check attendance logs table
        $table = $wpdb->prefix . 'linkage_attendance_logs';
        $work_date = current_time('Y-m-d');
        
        $active_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'active'",
            $user->ID,
            $work_date
        ));
        
        $completed_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'completed' ORDER BY id DESC LIMIT 1",
            $user->ID,
            $work_date
        ));
        
        echo "<p><strong>Active Record Today:</strong> " . ($active_record ? 'Yes (ID: ' . $active_record->id . ')' : 'No') . "</p>";
        echo "<p><strong>Completed Record Today:</strong> " . ($completed_record ? 'Yes (ID: ' . $completed_record->id . ')' : 'No') . "</p>";
        
        if ($active_record) {
            echo "<p><strong>Time In:</strong> " . $active_record->time_in . "</p>";
            echo "<p><strong>Lunch Start:</strong> " . ($active_record->lunch_start ?: 'Not set') . "</p>";
            echo "<p><strong>Lunch End:</strong> " . ($active_record->lunch_end ?: 'Not set') . "</p>";
        }
        
        echo "<hr>";
    }
}

/**
 * Clean up incorrect attendance records and reset user statuses
 */
function linkage_cleanup_attendance_records() {
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $work_date = current_time('Y-m-d');
    
    echo "<h3>Cleaning up attendance records...</h3>";
    
    // Get all active records for today
    $active_records = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE work_date = %s AND status = 'active'",
        $work_date
    ));
    
    $cleaned_count = 0;
    
    foreach ($active_records as $record) {
        // Check if user should actually be clocked in
        $user_id = $record->user_id;
        
        // Get user meta status
        $meta_status = get_user_meta($user_id, 'linkage_employee_status', true);
        
        // If user meta shows clocked out but has active record, mark record as completed
        if ($meta_status !== 'clocked_in' && $meta_status !== 'on_break') {
            $wpdb->update(
                $table,
                array(
                    'status' => 'completed',
                    'time_out' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $record->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            echo "<p>Cleaned up record for user ID $user_id (status: $meta_status)</p>";
            $cleaned_count++;
        }
    }
    
    // Reset all user meta statuses to clocked_out
    $users = get_users(array(
        'role__in' => array('employee', 'hr_manager', 'administrator'),
        'fields' => 'ID'
    ));
    
    $reset_count = 0;
    foreach ($users as $user_id) {
        $meta_status = get_user_meta($user_id, 'linkage_employee_status', true);
        if ($meta_status && $meta_status !== 'clocked_out') {
            update_user_meta($user_id, 'linkage_employee_status', 'clocked_out');
            delete_user_meta($user_id, 'linkage_clock_in_time');
            delete_user_meta($user_id, 'linkage_break_start_time');
            $reset_count++;
        }
    }
    
    echo "<p><strong>Cleaned up $cleaned_count attendance records</strong></p>";
    echo "<p><strong>Reset $reset_count user statuses to clocked out</strong></p>";
    
    return array('cleaned' => $cleaned_count, 'reset' => $reset_count);
}

/**
 * Reset a specific user's status to clocked out
 */
function linkage_reset_user_status($user_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $work_date = current_time('Y-m-d');
    
    // Mark any active records as completed
    $wpdb->update(
        $table,
        array(
            'status' => 'completed',
            'time_out' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array(
            'user_id' => $user_id,
            'work_date' => $work_date,
            'status' => 'active'
        ),
        array('%s', '%s', '%s'),
        array('%d', '%s', '%s')
    );
    
    // Reset user meta
    update_user_meta($user_id, 'linkage_employee_status', 'clocked_out');
    delete_user_meta($user_id, 'linkage_clock_in_time');
    delete_user_meta($user_id, 'linkage_break_start_time');
    
    return true;
}

/**
 * Debug function to check current database state for a user
 */
function linkage_debug_user_database_state($user_id) {
    global $wpdb;
    
    echo "<h4>Debug: Database State for User $user_id</h4>";
    
    // Check user meta
    $meta_status = get_user_meta($user_id, 'linkage_employee_status', true);
    $meta_clock_in = get_user_meta($user_id, 'linkage_clock_in_time', true);
    $meta_break_start = get_user_meta($user_id, 'linkage_break_start_time', true);
    
    echo "<p><strong>User Meta:</strong></p>";
    echo "<p>Status: " . ($meta_status ?: 'Not set') . "</p>";
    echo "<p>Clock In Time: " . ($meta_clock_in ?: 'Not set') . "</p>";
    echo "<p>Break Start Time: " . ($meta_break_start ?: 'Not set') . "</p>";
    
    // Check attendance logs
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $work_date = current_time('Y-m-d');
    
    $active_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'active'",
        $user_id,
        $work_date
    ));
    
    $completed_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'completed' ORDER BY id DESC LIMIT 1",
        $user_id,
        $work_date
    ));
    
    echo "<p><strong>Attendance Logs:</strong></p>";
    if ($active_record) {
        echo "<p>Active Record: Yes (ID: {$active_record->id})</p>";
        echo "<p>Time In: " . ($active_record->time_in ?: 'Not set') . "</p>";
        echo "<p>Time Out: " . ($active_record->time_out ?: 'Not set') . "</p>";
        echo "<p>Lunch Start: " . ($active_record->lunch_start ?: 'Not set') . "</p>";
        echo "<p>Lunch End: " . ($active_record->lunch_end ?: 'Not set') . "</p>";
        echo "<p>Status: " . $active_record->status . "</p>";
    } else {
        echo "<p>Active Record: No</p>";
    }
    
    if ($completed_record) {
        echo "<p>Completed Record: Yes (ID: {$completed_record->id})</p>";
        echo "<p>Time In: " . ($completed_record->time_in ?: 'Not set') . "</p>";
        echo "<p>Time Out: " . ($completed_record->time_out ?: 'Not set') . "</p>";
        echo "<p>Status: " . $completed_record->status . "</p>";
    } else {
        echo "<p>Completed Record: No</p>";
    }
    
    // Check what the database function would return
    $db_status = linkage_get_employee_status_from_database($user_id);
    echo "<p><strong>Database Function Returns:</strong></p>";
    echo "<p>Status: " . $db_status->status . "</p>";
    echo "<p>Last Action: " . $db_status->last_action_time . "</p>";
    echo "<p>Last Action Type: " . $db_status->last_action_type . "</p>";
}
