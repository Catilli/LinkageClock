<?php
/**
 * Payroll Functions
 * Functions for payroll processing, attendance export, and payroll management
 */

/**
 * AJAX handler for exporting attendance records
 */
function linkage_ajax_export_attendance() {
    if (!wp_verify_nonce($_GET['nonce'] ?? $_POST['nonce'] ?? '', 'linkage_dashboard_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    $start_date = sanitize_text_field($_GET['start_date'] ?? '');
    $end_date = sanitize_text_field($_GET['end_date'] ?? '');
    $employee_id = intval($_GET['employee_id'] ?? 0);
    
    if (empty($start_date) || empty($end_date)) {
        wp_die('Start date and end date are required');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    
    // Build query
    $where_conditions = ["work_date BETWEEN %s AND %s"];
    $query_params = [$start_date, $end_date];
    
    if ($employee_id > 0) {
        $where_conditions[] = "user_id = %d";
        $query_params[] = $employee_id;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $query = "SELECT * FROM $table WHERE $where_clause ORDER BY work_date DESC, user_id ASC";
    $results = $wpdb->get_results($wpdb->prepare($query, ...$query_params));
    
    // Generate CSV
    $filename = 'attendance_export_' . $start_date . '_to_' . $end_date . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Employee Name',
        'Work Date',
        'Time In',
        'Time Out',
        'Lunch Start',
        'Lunch End',
        'Total Hours',
        'Status',
        'Notes'
    ]);
    
    // CSV Data
    foreach ($results as $record) {
        $user = get_userdata($record->user_id);
        $employee_name = $user ? linkage_get_user_display_name($record->user_id) : 'Unknown User';
        
        fputcsv($output, [
            $employee_name,
            $record->work_date,
            $record->time_in ?: '',
            $record->time_out ?: '',
            $record->lunch_start ?: '',
            $record->lunch_end ?: '',
            $record->total_hours ?: '0.00',
            $record->status,
            $record->notes ?: ''
        ]);
    }
    
    fclose($output);
    exit;
}
add_action('wp_ajax_linkage_export_attendance', 'linkage_ajax_export_attendance');

/**
 * AJAX handler for generating payroll
 */
function linkage_ajax_generate_payroll() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }
    
    $period_start = sanitize_text_field($_POST['period_start'] ?? '');
    $period_end = sanitize_text_field($_POST['period_end'] ?? '');
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $overtime_type = sanitize_text_field($_POST['overtime_type'] ?? '1.5x');
    
    if (empty($period_start) || empty($period_end) || $employee_id <= 0) {
        wp_send_json_error('All fields are required');
    }
    
    // Check if payroll already exists for this period and employee
    global $wpdb;
    $payroll_table = $wpdb->prefix . 'linkage_payroll';
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $payroll_table WHERE user_id = %d AND period_start = %s AND period_end = %s",
        $employee_id, $period_start, $period_end
    ));
    
    if ($existing) {
        wp_send_json_error('Payroll already exists for this employee and period');
    }
    
    // Calculate hours from attendance logs
    $attendance_table = $wpdb->prefix . 'linkage_attendance_logs';
    
    $attendance_records = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $attendance_table WHERE user_id = %d AND work_date BETWEEN %s AND %s AND status = 'completed'",
        $employee_id, $period_start, $period_end
    ));
    
    $total_hours = 0;
    $overtime_hours = 0;
    $regular_hours = 0;
    
    foreach ($attendance_records as $record) {
        $daily_hours = floatval($record->total_hours);
        $total_hours += $daily_hours;
        
        // Calculate overtime (over 8 hours per day)
        if ($daily_hours > 8) {
            $overtime_hours += ($daily_hours - 8);
            $regular_hours += 8;
        } else {
            $regular_hours += $daily_hours;
        }
    }
    
    // Get employee hourly rate (from user meta or default)
    $hourly_rate = floatval(get_user_meta($employee_id, 'linkage_hourly_rate', true)) ?: 15.00;
    
    // Calculate pay
    $overtime_multiplier = ($overtime_type === '2x') ? 2.0 : 1.5;
    $regular_pay = $regular_hours * $hourly_rate;
    $overtime_pay = $overtime_hours * $hourly_rate * $overtime_multiplier;
    $gross_pay = $regular_pay + $overtime_pay;
    
    // Calculate deductions (placeholder - you can customize this)
    $tax_rate = 0.15; // 15% tax rate
    $deductions = $gross_pay * $tax_rate;
    $net_pay = $gross_pay - $deductions;
    
    // Insert payroll record
    $result = $wpdb->insert(
        $payroll_table,
        [
            'user_id' => $employee_id,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'total_regular_hours' => number_format($regular_hours, 2),
            'total_overtime_hours' => number_format($overtime_hours, 2),
            'overtime_type' => $overtime_type,
            'gross_pay' => number_format($gross_pay, 2),
            'deductions' => number_format($deductions, 2),
            'net_pay' => number_format($net_pay, 2),
            'status' => 'pending'
        ],
        ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to create payroll record');
    }
    
    wp_send_json_success([
        'message' => 'Payroll generated successfully',
        'payroll_id' => $wpdb->insert_id,
        'regular_hours' => $regular_hours,
        'overtime_hours' => $overtime_hours,
        'gross_pay' => $gross_pay,
        'net_pay' => $net_pay
    ]);
}
add_action('wp_ajax_linkage_generate_payroll', 'linkage_ajax_generate_payroll');

/**
 * AJAX handler for getting payroll records
 */
function linkage_ajax_get_payroll_records() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }
    
    global $wpdb;
    $payroll_table = $wpdb->prefix . 'linkage_payroll';
    
    $records = $wpdb->get_results(
        "SELECT * FROM $payroll_table ORDER BY created_at DESC LIMIT 20"
    );
    
    if (empty($records)) {
        wp_send_json_success([
            'html' => '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No payroll records found.</td></tr>'
        ]);
        return;
    }
    
    $html = '';
    foreach ($records as $record) {
        $user = get_userdata($record->user_id);
        $employee_name = $user ? linkage_get_user_display_name($record->user_id) : 'Unknown User';
        
        $status_class = '';
        switch ($record->status) {
            case 'pending':
                $status_class = 'bg-yellow-100 text-yellow-800';
                break;
            case 'approved':
                $status_class = 'bg-blue-100 text-blue-800';
                break;
            case 'paid':
                $status_class = 'bg-green-100 text-green-800';
                break;
        }
        
        $html .= '<tr>';
        $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . esc_html($employee_name) . '</td>';
        $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . esc_html($record->period_start . ' to ' . $record->period_end) . '</td>';
        $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . esc_html($record->total_regular_hours) . '</td>';
        $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . esc_html($record->total_overtime_hours) . '</td>';
        $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$' . esc_html($record->gross_pay) . '</td>';
        $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$' . esc_html($record->net_pay) . '</td>';
        $html .= '<td class="px-6 py-4 whitespace-nowrap">';
        $html .= '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">' . ucfirst($record->status) . '</span>';
        $html .= '</td>';
        $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">';
        $html .= '<button class="text-blue-600 hover:text-blue-900 mr-2" onclick="viewPayroll(' . $record->id . ')">View</button>';
        if ($record->status === 'pending') {
            $html .= '<button class="text-green-600 hover:text-green-900" onclick="approvePayroll(' . $record->id . ')">Approve</button>';
        }
        $html .= '</td>';
        $html .= '</tr>';
    }
    
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_linkage_get_payroll_records', 'linkage_ajax_get_payroll_records');

/**
 * AJAX handler for approving payroll
 */
function linkage_ajax_approve_payroll() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }
    
    $payroll_id = intval($_POST['payroll_id'] ?? 0);
    
    if ($payroll_id <= 0) {
        wp_send_json_error('Invalid payroll ID');
    }
    
    global $wpdb;
    $payroll_table = $wpdb->prefix . 'linkage_payroll';
    
    $result = $wpdb->update(
        $payroll_table,
        ['status' => 'approved'],
        ['id' => $payroll_id],
        ['%s'],
        ['%d']
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to approve payroll');
    }
    
    wp_send_json_success('Payroll approved successfully');
}
add_action('wp_ajax_linkage_approve_payroll', 'linkage_ajax_approve_payroll');
