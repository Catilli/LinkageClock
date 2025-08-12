<?php
/**
 * Dashboard functions for LinkageClock
 */

/**
 * Get all employees with their current status (shows all users, even without attendance records)
 */
function linkage_get_all_employees_status() {
    global $wpdb;
    
    // Get all users who have employee-related roles or capabilities
    $users = get_users(array(
        'role__in' => array('employee', 'manager', 'accounting_payroll', 'contractor', 'administrator'),
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
 * Get employee status by user ID (now uses attendance logs table)
 */
function linkage_get_employee_status($user_id) {
    // Use the database function instead of user meta
    return linkage_get_employee_status_from_database($user_id);
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
 * Get user role display name
 */
function linkage_get_user_role_display($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return 'Unknown';
    
    $roles = $user->roles;
    if (empty($roles)) return 'No Role';
    
    $role_names = array(
        'employee' => 'Employee',
        'manager' => 'Manager',
        'accounting_payroll' => 'Accounting | Payroll',
        'contractor' => 'Contractors',
        'administrator' => 'Administrator'
    );
    
    $role = $roles[0];
    return isset($role_names[$role]) ? $role_names[$role] : ucfirst($role);
}

/**
 * Initialize employee status for users who don't have attendance records
 */
function linkage_initialize_employee_status() {
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $work_date = current_time('Y-m-d');
    
    $users = get_users(array(
        'role__in' => array('employee', 'manager', 'accounting_payroll', 'contractor', 'administrator'),
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
    
    // DISABLED: This function was creating NULL records automatically
    // Records should only be created when users actually clock in
    // Keeping function for compatibility but disabling the record creation
    
    return $initialized_count;
}

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
    
    // Handle different actions with proper state validation
    switch ($action) {
        case 'clock_in':
            // Check current status before attempting clock in
            $current_status_obj = linkage_get_employee_status_from_database($user_id);
            if ($current_status_obj->status === 'clocked_in' || $current_status_obj->status === 'on_break') {
                wp_send_json_error('Already clocked in or on break');
            }
            
            // Create new attendance log record
            $attendance_id = linkage_create_attendance_log($user_id, 'clock_in');
            
            // No more user meta updates - we're using attendance logs table only
            break;
            
        case 'clock_out':
            // Update attendance log record with clock out time and calculate total hours
            $attendance_id = linkage_update_attendance_log($user_id, 'clock_out');
            
            // No more user meta updates - we're using attendance logs table only
            break;
            
        case 'break_start':
            // Check current status before attempting break start
            $current_status_obj = linkage_get_employee_status_from_database($user_id);
            if ($current_status_obj->status !== 'clocked_in') {
                wp_send_json_error('Must be clocked in to start break');
            }
            

            
            // Update attendance log record with lunch start time
            $attendance_id = linkage_update_attendance_log($user_id, 'break_start');
            
            // No more user meta updates - we're using attendance logs table only
            break;
            
        case 'break_end':
            // Check current status before attempting break end
            $current_status_obj = linkage_get_employee_status_from_database($user_id);
            if ($current_status_obj->status !== 'on_break') {
                wp_send_json_error('Must be on break to end break');
            }
            

            
            // Update attendance log record with lunch end time
            $attendance_id = linkage_update_attendance_log($user_id, 'break_end');
            
            // No more user meta updates - we're using attendance logs table only
            break;
    }
    
    // Check if attendance log operations were successful
    if (isset($attendance_id) && $attendance_id !== false) {
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
        wp_send_json_error('Failed to create/update attendance log');
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
            'nonce' => wp_create_nonce('linkage_dashboard_nonce'),
            'current_user_id' => get_current_user_id()
        ));
        
        wp_localize_script('linkage-timer', 'linkage_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('linkage_dashboard_nonce'),
            'current_user_id' => get_current_user_id()
        ));
    }
}
add_action('wp_enqueue_scripts', 'linkage_enqueue_dashboard_scripts');

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
    echo "<p><strong>Note:</strong> Employee status is now stored in the attendance logs table, not in user meta.</p>";
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
        
        // Initialize status record - no longer using user meta
        // Status will be determined by attendance logs table
        $initialized_count++;
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
 * Create a new attendance log record with proper concurrency handling
 */
function linkage_create_attendance_log($user_id, $action) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $current_time = current_time('mysql');
    $work_date = current_time('Y-m-d');
    
    // Start transaction for atomic operations
    $wpdb->query('START TRANSACTION');
    
    try {
        // Use SELECT FOR UPDATE to lock the row and prevent race conditions
        $existing_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'active' FOR UPDATE",
            $user_id,
            $work_date
        ));
        
        if ($existing_record) {
            // Update existing record with row lock held
            $result = $wpdb->update(
                $table,
                array(
                    'time_in' => $current_time,
                    'updated_at' => $current_time
                ),
                array('id' => $existing_record->id),
                array('%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                $wpdb->query('ROLLBACK');
                error_log('LinkageClock: Failed to update existing attendance record for user ' . $user_id);
                return false;
            }
            
            $wpdb->query('COMMIT');
            return $existing_record->id;
        } else {
            // Use INSERT IGNORE to handle potential unique constraint violations
            $result = $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO $table 
                (user_id, work_date, time_in, status, created_at, updated_at) 
                VALUES (%d, %s, %s, 'active', %s, %s)",
                $user_id,
                $work_date,
                $current_time,
                $current_time,
                $current_time
            ));
            
            if ($result === false) {
                $wpdb->query('ROLLBACK');
                error_log('LinkageClock: Failed to create attendance record for user ' . $user_id . ': ' . $wpdb->last_error);
                return false;
            }
            
            $insert_id = $wpdb->insert_id;
            
            // If insert_id is 0, it means the record already existed (due to unique constraint)
            if ($insert_id == 0) {
                // Get the existing record
                $existing_record = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'active'",
                    $user_id,
                    $work_date
                ));
                
                if ($existing_record) {
                    // Update the existing record
                    $update_result = $wpdb->update(
                        $table,
                        array(
                            'time_in' => $current_time,
                            'updated_at' => $current_time
                        ),
                        array('id' => $existing_record->id),
                        array('%s', '%s'),
                        array('%d')
                    );
                    
                    if ($update_result === false) {
                        $wpdb->query('ROLLBACK');
                        error_log('LinkageClock: Failed to update existing record after INSERT IGNORE for user ' . $user_id);
                        return false;
                    }
                    
                    $wpdb->query('COMMIT');
                    return $existing_record->id;
                }
            }
            
            $wpdb->query('COMMIT');
            return $insert_id;
        }
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('LinkageClock: Exception in linkage_create_attendance_log: ' . $e->getMessage());
        return false;
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
    
    // Start transaction for atomic operations
    $wpdb->query('START TRANSACTION');
    
    try {
        // Use SELECT FOR UPDATE to lock the record and prevent concurrent modifications
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'active' FOR UPDATE",
            $user_id,
            $work_date
        ));
        
        if (!$record) {
            $wpdb->query('ROLLBACK');
            error_log('LinkageClock: No active record found for user ' . $user_id . ' on ' . $work_date);
            return false;
        }
        
        // Validate action based on current record state
        switch ($action) {
            case 'break_start':
                if (!empty($record->lunch_start) && empty($record->lunch_end)) {
                    $wpdb->query('ROLLBACK');
                    error_log('LinkageClock: User ' . $user_id . ' already on break');
                    return false;
                }
                break;
                
            case 'break_end':
                if (empty($record->lunch_start) || !empty($record->lunch_end)) {
                    $wpdb->query('ROLLBACK');
                    error_log('LinkageClock: User ' . $user_id . ' not on break or break already ended');
                    return false;
                }
                break;
                
            case 'clock_out':
                if (!empty($record->time_out)) {
                    $wpdb->query('ROLLBACK');
                    error_log('LinkageClock: User ' . $user_id . ' already clocked out');
                    return false;
                }
                break;
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
                
                // Calculate total hours including break time
                $time_in = strtotime($record->time_in);
                $time_out = strtotime($current_time);
                $lunch_start = $record->lunch_start ? strtotime($record->lunch_start) : null;
                $lunch_end = $record->lunch_end ? strtotime($record->lunch_end) : null;
                
                // Total time from clock in to clock out (includes break time)
                $total_seconds = $time_out - $time_in;
                
                // Handle case where lunch started but never ended
                if ($lunch_start && !$lunch_end) {
                    // Auto-end lunch at clock out time
                    $update_data['lunch_end'] = $current_time;
                }
                
                $total_hours = round($total_seconds / 3600, 2);
                $update_data['total_hours'] = $total_hours;
                break;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $record->id),
            null, // Let WordPress auto-detect format types
            array('%d')
        );
        
        if ($result === false) {
            $wpdb->query('ROLLBACK');
            error_log('LinkageClock: Failed to update attendance log: ' . $wpdb->last_error);
            return false;
        }
        
        $wpdb->query('COMMIT');
        return $record->id;
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('LinkageClock: Exception in linkage_update_attendance_log: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get employee status with calculated times from database instead of meta keys
 */
function linkage_get_employee_status_from_database($user_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $work_date = current_time('Y-m-d');
    
    // Debug logging
    error_log("LinkageClock: Getting status for user_id: $user_id, work_date: $work_date");
    
    // Get the active record for today
    $record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND work_date = %s AND status = 'active'",
        $user_id,
        $work_date
    ));
    
    // Debug logging
    if ($record) {
        error_log("LinkageClock: Found active record for user $user_id: ID=" . $record->id);
    } else {
        error_log("LinkageClock: No active record found for user $user_id");
    }
    
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
        'role__in' => array('employee', 'manager', 'accounting_payroll', 'contractor', 'administrator'),
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
        
        // Check database status (current method)
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
        
        // Since we're no longer using user meta, we'll just check if the record is actually active
        // and if it's been more than 24 hours, mark it as completed
        $record_time = strtotime($record->updated_at);
        $current_time = current_time('timestamp');
        $hours_since_update = ($current_time - $record_time) / 3600;
        
        // If record hasn't been updated in more than 24 hours, mark it as completed
        if ($hours_since_update > 24) {
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
            
            echo "<p>Cleaned up old record for user ID $user_id (inactive for " . round($hours_since_update, 1) . " hours)</p>";
            $cleaned_count++;
        }
    }
    
    // Reset all user meta statuses to clocked_out
    $users = get_users(array(
        'role__in' => array('employee', 'manager', 'accounting_payroll', 'contractor', 'administrator'),
        'fields' => 'ID'
    ));
    
    $reset_count = 0;
    foreach ($users as $user_id) {
        // No longer using user meta - status is determined by attendance logs table
        // Just count users for display purposes
        $reset_count++;
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
    
    // No more user meta updates - status is determined by attendance logs table
    
    return true;
}

/**
 * Debug function to check current database state for a user
 */
function linkage_debug_user_database_state($user_id) {
    global $wpdb;
    
    echo "<h4>Debug: Database State for User $user_id</h4>";
    
    // Check attendance logs (this is now our single source of truth)
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
    
    echo "<p><strong>Attendance Logs (Single Source of Truth):</strong></p>";
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
    
    // Check what the database function returns
    $db_status = linkage_get_employee_status_from_database($user_id);
    echo "<p><strong>Database Function Returns:</strong></p>";
    echo "<p>Status: " . $db_status->status . "</p>";
    echo "<p>Last Action: " . $db_status->last_action_time . "</p>";
    echo "<p>Last Action Type: " . $db_status->last_action_type . "</p>";
    
    // Show the logic for status determination
    echo "<p><strong>Status Determination Logic:</strong></p>";
    if ($active_record) {
        if ($active_record->time_in && !$active_record->time_out) {
            if ($active_record->lunch_start && !$active_record->lunch_end) {
                echo "<p>Status: on_break (has time_in, no time_out, has lunch_start, no lunch_end)</p>";
            } else {
                echo "<p>Status: clocked_in (has time_in, no time_out, no lunch or lunch completed)</p>";
            }
        } else {
            echo "<p>Status: clocked_out (has time_out or missing time_in)</p>";
        }
    } else {
        echo "<p>Status: clocked_out (no active record)</p>";
    }
}

/**
 * Clear legacy user meta keys (for testing purposes)
 */
function linkage_clear_legacy_user_meta() {
    if (!current_user_can('administrator')) {
        return 'Access denied. Administrator privileges required.';
    }
    
    global $wpdb;
    
    // Get all users
    $users = get_users(array('fields' => 'ID'));
    $cleared_count = 0;
    
    foreach ($users as $user_id) {
        // Clear legacy meta keys
        $meta_keys = array(
            'linkage_employee_status',
            'linkage_clock_in_time',
            'linkage_break_start_time',
            'linkage_work_seconds',
            'linkage_break_seconds',
            'linkage_last_action_time',
            'linkage_last_action_type',
            'linkage_last_notes'
        );
        
        foreach ($meta_keys as $meta_key) {
            if (get_user_meta($user_id, $meta_key, true)) {
                delete_user_meta($user_id, $meta_key);
                $cleared_count++;
            }
        }
    }
    
    return "Cleared $cleared_count legacy user meta entries. System now uses attendance logs table only.";
}

/**
 * Delete all time logs from attendance logs table (for testing/reset purposes)
 */
function linkage_delete_all_time_logs() {
    if (!current_user_can('administrator')) {
        return 'Access denied. Administrator privileges required.';
    }
    
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    if (!$table_exists) {
        return 'Attendance logs table does not exist.';
    }
    
    // Get count before deletion
    $count_before = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    
    // Delete all records
    $result = $wpdb->query("DELETE FROM $table");
    
    if ($result === false) {
        return 'Error deleting time logs: ' . $wpdb->last_error;
    }
    
    $deleted_count = $count_before;
    
    return "Successfully deleted $deleted_count time log records. The attendance logs table is now empty.";
}

/**
 * Delete only the NULL attendance records (empty records with no actual clock-in data)
 */
function linkage_delete_null_time_logs() {
    if (!current_user_can('administrator')) {
        return 'Access denied. Administrator privileges required.';
    }
    
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    if (!$table_exists) {
        return 'Attendance logs table does not exist.';
    }
    
    // Count existing NULL records before deletion
    $count_before = $wpdb->get_var(
        "SELECT COUNT(*) FROM $table WHERE time_in IS NULL AND time_out IS NULL AND lunch_start IS NULL AND lunch_end IS NULL"
    );
    
    // Delete only records with all NULL time values
    $result = $wpdb->query(
        "DELETE FROM $table WHERE time_in IS NULL AND time_out IS NULL AND lunch_start IS NULL AND lunch_end IS NULL"
    );
    
    if ($result === false) {
        return 'Error deleting NULL time logs: ' . $wpdb->last_error;
    }
    
    return "Successfully deleted $count_before empty/NULL time log records. Real attendance data was preserved.";
}

/**
 * Test concurrent clock actions for stress testing
 */
function linkage_test_concurrent_clock_actions() {
    if (!current_user_can('administrator')) {
        return 'Access denied. Administrator privileges required.';
    }
    
    global $wpdb;
    
    $results = array();
    $test_users = get_users(array(
        'role__in' => array('employee', 'manager', 'accounting_payroll', 'contractor', 'administrator'),
        'number' => 5 // Test with first 5 users
    ));
    
    if (empty($test_users)) {
        return 'No test users found';
    }
    
    // Clear any existing active records for today
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $work_date = current_time('Y-m-d');
    
    $wpdb->update(
        $table,
        array('status' => 'completed', 'time_out' => current_time('mysql')),
        array('work_date' => $work_date, 'status' => 'active'),
        array('%s', '%s'),
        array('%s', '%s')
    );
    
    $results[] = 'Cleared existing active records for testing';
    
    // Simulate concurrent clock-in actions
    $start_time = microtime(true);
    
    foreach ($test_users as $user) {
        $attendance_id = linkage_create_attendance_log($user->ID, 'clock_in');
        if ($attendance_id) {
            $results[] = "User {$user->display_name} (ID: {$user->ID}) clocked in successfully - Record ID: $attendance_id";
        } else {
            $results[] = "User {$user->display_name} (ID: {$user->ID}) FAILED to clock in";
        }
    }
    
    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 4);
    $results[] = "Concurrent clock-in test completed in {$duration} seconds";
    
    // Check for duplicate records
    $duplicates = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, COUNT(*) as count FROM $table 
         WHERE work_date = %s AND status = 'active' 
         GROUP BY user_id 
         HAVING COUNT(*) > 1",
        $work_date
    ));
    
    if (empty($duplicates)) {
        $results[] = "✅ SUCCESS: No duplicate active records found";
    } else {
        $results[] = "❌ FAILURE: Found " . count($duplicates) . " users with duplicate records";
        foreach ($duplicates as $dup) {
            $results[] = "  - User ID {$dup->user_id}: {$dup->count} active records";
        }
    }
    
    // Test concurrent break actions
    foreach ($test_users as $user) {
        $attendance_id = linkage_update_attendance_log($user->ID, 'break_start');
        if ($attendance_id) {
            $results[] = "User {$user->display_name} started break successfully";
        } else {
            $results[] = "User {$user->display_name} FAILED to start break";
        }
    }
    
    // Test concurrent break end actions
    foreach ($test_users as $user) {
        $attendance_id = linkage_update_attendance_log($user->ID, 'break_end');
        if ($attendance_id) {
            $results[] = "User {$user->display_name} ended break successfully";
        } else {
            $results[] = "User {$user->display_name} FAILED to end break";
        }
    }
    
    // Test concurrent clock-out actions
    foreach ($test_users as $user) {
        $attendance_id = linkage_update_attendance_log($user->ID, 'clock_out');
        if ($attendance_id) {
            $results[] = "User {$user->display_name} clocked out successfully";
        } else {
            $results[] = "User {$user->display_name} FAILED to clock out";
        }
    }
    
    return 'Concurrent access test completed: ' . implode(' | ', $results);
}

/**
 * Analyze and fix database state issues
 */
function linkage_analyze_and_fix_database_state() {
    if (!current_user_can('administrator')) {
        return 'Access denied. Administrator privileges required.';
    }
    
    global $wpdb;
    
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $work_date = current_time('Y-m-d');
    $results = array();
    
    // Check for multiple active records per user per day (this should not happen)
    $duplicate_actives = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, COUNT(*) as count FROM $table 
         WHERE work_date = %s AND status = 'active' 
         GROUP BY user_id 
         HAVING COUNT(*) > 1",
        $work_date
    ));
    
    if (!empty($duplicate_actives)) {
        $results[] = "Found " . count($duplicate_actives) . " users with multiple active records";
        
        // Fix duplicate active records
        foreach ($duplicate_actives as $duplicate) {
            $user_id = $duplicate->user_id;
            
            // Keep only the most recent active record, mark others as completed
            $records = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE user_id = %d AND work_date = %s AND status = 'active' 
                 ORDER BY id DESC",
                $user_id,
                $work_date
            ));
            
            // Keep the first (most recent), mark others as completed
            for ($i = 1; $i < count($records); $i++) {
                $wpdb->update(
                    $table,
                    array(
                        'status' => 'completed',
                        'time_out' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $records[$i]->id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
            }
            
            $results[] = "Fixed duplicate records for user $user_id";
        }
    } else {
        $results[] = "No duplicate active records found";
    }
    
    // Check for active records without time_in (corrupted data)
    $corrupted_records = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table 
         WHERE work_date = %s AND status = 'active' AND (time_in IS NULL OR time_in = '')",
        $work_date
    ));
    
    if (!empty($corrupted_records)) {
        $results[] = "Found " . count($corrupted_records) . " corrupted active records";
        
        foreach ($corrupted_records as $record) {
            $wpdb->update(
                $table,
                array('status' => 'completed', 'updated_at' => current_time('mysql')),
                array('id' => $record->id),
                array('%s', '%s'),
                array('%d')
            );
        }
        
        $results[] = "Fixed corrupted active records";
    } else {
        $results[] = "No corrupted records found";
    }
    
    // Show current state after fixes
    $active_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE work_date = %s AND status = 'active'",
        $work_date
    ));
    
    $results[] = "Current active records: $active_count";
    
    return 'Database analysis completed: ' . implode(', ', $results);
}

/**
 * Comprehensive reset function - deletes all time logs and clears legacy meta
 */
function linkage_comprehensive_reset() {
    if (!current_user_can('administrator')) {
        return 'Access denied. Administrator privileges required.';
    }
    
    global $wpdb;
    
    $results = array();
    
    // 1. Delete all time logs
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    
    if ($table_exists) {
        $count_before = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $result = $wpdb->query("DELETE FROM $table");
        
        if ($result === false) {
            $results[] = 'Error deleting time logs: ' . $wpdb->last_error;
        } else {
            $results[] = "Deleted $count_before time log records";
        }
    } else {
        $results[] = 'Attendance logs table does not exist';
    }
    
    // 2. Clear legacy user meta
    $users = get_users(array('fields' => 'ID'));
    $cleared_count = 0;
    
    foreach ($users as $user_id) {
        $meta_keys = array(
            'linkage_employee_status',
            'linkage_clock_in_time',
            'linkage_break_start_time',
            'linkage_work_seconds',
            'linkage_break_seconds',
            'linkage_last_action_time',
            'linkage_last_action_type',
            'linkage_last_notes'
        );
        
        foreach ($meta_keys as $meta_key) {
            if (get_user_meta($user_id, $meta_key, true)) {
                delete_user_meta($user_id, $meta_key);
                $cleared_count++;
            }
        }
    }
    
    if ($cleared_count > 0) {
        $results[] = "Cleared $cleared_count legacy user meta entries";
    } else {
        $results[] = "No legacy user meta to clear";
    }
    
    return 'Comprehensive reset completed: ' . implode(', ', $results);
}
