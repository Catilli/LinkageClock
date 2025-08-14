<?php
/**
 * Payroll Functions
 * Functions for payroll processing, attendance export, and payroll management
 */

/**
 * AJAX handler for generating payroll
 */
function linkage_ajax_generate_payroll() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!current_user_can('manage_options') && !current_user_can('linkage_generate_payroll_reports')) {
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
 * AJAX handler for employee search with autocomplete
 */
function linkage_ajax_search_employees() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!current_user_can('manage_options') && !current_user_can('linkage_export_attendance')) {
        wp_send_json_error('Unauthorized access');
    }
    
    $query = sanitize_text_field($_POST['query'] ?? '');
    
    if (strlen($query) < 2) {
        wp_send_json_success([]);
    }
    
    $users = get_users(array(
        'search' => '*' . $query . '*',
        'search_columns' => array('display_name', 'user_nicename', 'user_email'),
        'role__in' => array('employee', 'manager', 'accounting_payroll', 'contractor'),
        'number' => 10,
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    
    $employees = array();
    foreach ($users as $user) {
        $employees[] = array(
            'ID' => $user->ID,
            'display_name' => linkage_get_user_display_name($user->ID),
            'email' => $user->user_email,
            'position' => get_user_meta($user->ID, 'linkage_position', true) ?: 'No position set'
        );
    }
    
    wp_send_json_success($employees);
}
add_action('wp_ajax_linkage_search_employees', 'linkage_ajax_search_employees');

/**
 * AJAX handler for getting employee attendance data
 */
function linkage_ajax_get_employee_attendance() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!current_user_can('manage_options') && !current_user_can('linkage_export_attendance')) {
        wp_send_json_error('Unauthorized access');
    }
    
    $query = sanitize_text_field($_POST['query'] ?? '');
    $start_date = sanitize_text_field($_POST['start_date'] ?? '');
    $end_date = sanitize_text_field($_POST['end_date'] ?? '');
    
    if (empty($start_date) || empty($end_date)) {
        wp_send_json_error('Start date and end date are required');
    }
    
    global $wpdb;
    $attendance_table = $wpdb->prefix . 'linkage_attendance_logs';
    
    // Build query for employees
    $user_query = "
        SELECT DISTINCT u.ID, u.display_name, u.user_email
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key = '{$wpdb->prefix}capabilities'
        AND (um.meta_value LIKE '%employee%' OR um.meta_value LIKE '%manager%' 
             OR um.meta_value LIKE '%accounting_payroll%' OR um.meta_value LIKE '%contractor%')
    ";
    
    if (!empty($query)) {
        $search_term = '%' . $wpdb->esc_like($query) . '%';
        $user_query .= $wpdb->prepare(" AND (u.display_name LIKE %s OR u.user_email LIKE %s)", $search_term, $search_term);
    }
    
    $user_query .= " ORDER BY u.display_name ASC";
    
    $users = $wpdb->get_results($user_query);
    
    $employees = array();
    
    foreach ($users as $user) {
        // Get attendance summary for date range
        $attendance_summary = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as days_worked,
                SUM(total_hours) as total_hours,
                SUM(CASE WHEN total_hours > 8 THEN 8 ELSE total_hours END) as regular_hours,
                SUM(CASE WHEN total_hours > 8 THEN total_hours - 8 ELSE 0 END) as overtime_hours
            FROM $attendance_table 
            WHERE user_id = %d 
            AND work_date BETWEEN %s AND %s
            AND status = 'completed'
        ", $user->ID, $start_date, $end_date));
        
        $employees[] = array(
            'user_id' => $user->ID,
            'name' => linkage_get_user_display_name($user->ID),
            'email' => $user->user_email,
            'position' => get_user_meta($user->ID, 'linkage_position', true) ?: 'No position set',
            'days_worked' => intval($attendance_summary->days_worked),
            'total_hours' => number_format(floatval($attendance_summary->total_hours), 2) . ' hrs',
            'regular_hours' => number_format(floatval($attendance_summary->regular_hours), 2) . ' hrs',
            'overtime_hours' => number_format(floatval($attendance_summary->overtime_hours), 2) . ' hrs'
        );
    }
    
    wp_send_json_success(array('employees' => $employees));
}
add_action('wp_ajax_linkage_get_employee_attendance', 'linkage_ajax_get_employee_attendance');

/**
 * AJAX handler for getting detailed employee logs
 */
function linkage_ajax_get_employee_detailed_logs() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!current_user_can('manage_options') && !current_user_can('linkage_export_attendance')) {
        wp_send_json_error('Unauthorized access');
    }
    
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $start_date = sanitize_text_field($_POST['start_date'] ?? '');
    $end_date = sanitize_text_field($_POST['end_date'] ?? '');
    
    if ($employee_id <= 0 || empty($start_date) || empty($end_date)) {
        wp_send_json_error('Employee ID, start date and end date are required');
    }
    
    global $wpdb;
    $attendance_table = $wpdb->prefix . 'linkage_attendance_logs';
    
    $logs = $wpdb->get_results($wpdb->prepare("
        SELECT 
            work_date,
            time_in,
            lunch_start,
            lunch_end,
            time_out,
            total_hours,
            notes
        FROM $attendance_table 
        WHERE user_id = %d 
        AND work_date BETWEEN %s AND %s
        ORDER BY work_date DESC
    ", $employee_id, $start_date, $end_date));
    
    // Format the data
    $formatted_logs = array();
    foreach ($logs as $log) {
        $formatted_logs[] = array(
            'work_date' => date('M j, Y', strtotime($log->work_date)),
            'time_in' => $log->time_in ? date('g:i A', strtotime($log->time_in)) : null,
            'lunch_start' => $log->lunch_start ? date('g:i A', strtotime($log->lunch_start)) : null,
            'lunch_end' => $log->lunch_end ? date('g:i A', strtotime($log->lunch_end)) : null,
            'time_out' => $log->time_out ? date('g:i A', strtotime($log->time_out)) : null,
            'total_hours' => number_format(floatval($log->total_hours), 2) . ' hrs',
            'notes' => $log->notes
        );
    }
    
    $employee_name = linkage_get_user_display_name($employee_id);
    
    wp_send_json_success(array(
        'employee_name' => $employee_name,
        'logs' => $formatted_logs
    ));
}
add_action('wp_ajax_linkage_get_employee_detailed_logs', 'linkage_ajax_get_employee_detailed_logs');

/**
 * AJAX handler for exporting employee attendance data
 */
function linkage_ajax_export_employee_attendance() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_options') && !current_user_can('linkage_export_attendance')) {
        wp_die('Unauthorized access');
    }
    
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $start_date = sanitize_text_field($_POST['start_date'] ?? '');
    $end_date = sanitize_text_field($_POST['end_date'] ?? '');
    $format = sanitize_text_field($_POST['format'] ?? 'csv');
    
    if ($employee_id <= 0 || empty($start_date) || empty($end_date)) {
        wp_die('Employee ID, start date and end date are required');
    }
    
    global $wpdb;
    $attendance_table = $wpdb->prefix . 'linkage_attendance_logs';
    
    $logs = $wpdb->get_results($wpdb->prepare("
        SELECT 
            work_date,
            time_in,
            lunch_start,
            lunch_end,
            time_out,
            total_hours,
            notes
        FROM $attendance_table 
        WHERE user_id = %d 
        AND work_date BETWEEN %s AND %s
        ORDER BY work_date ASC
    ", $employee_id, $start_date, $end_date));
    
    $employee_name = linkage_get_user_display_name($employee_id);
    $filename = sanitize_file_name($employee_name . '_attendance_' . $start_date . '_to_' . $end_date);
    
    if ($format === 'xlsx') {
        // For XLSX, we'll still output CSV for now but could be enhanced with a library like PhpSpreadsheet
        $format = 'csv';
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add header row
    fputcsv($output, array(
        'Employee',
        'Date',
        'Time In',
        'Lunch Start',
        'Lunch End',
        'Time Out',
        'Total Hours',
        'Notes'
    ));
    
    // Add data rows
    foreach ($logs as $log) {
        fputcsv($output, array(
            $employee_name,
            date('M j, Y', strtotime($log->work_date)),
            $log->time_in ? date('g:i A', strtotime($log->time_in)) : '',
            $log->lunch_start ? date('g:i A', strtotime($log->lunch_start)) : '',
            $log->lunch_end ? date('g:i A', strtotime($log->lunch_end)) : '',
            $log->time_out ? date('g:i A', strtotime($log->time_out)) : '',
            number_format(floatval($log->total_hours), 2),
            $log->notes ?: ''
        ));
    }
    
    fclose($output);
    exit;
}
add_action('wp_ajax_linkage_export_employee_attendance', 'linkage_ajax_export_employee_attendance');

/**
 * AJAX handler for generating payslips
 */
function linkage_ajax_generate_payslip() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_options') && !current_user_can('linkage_export_attendance')) {
        wp_die('Unauthorized access');
    }
    
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $start_date = sanitize_text_field($_POST['start_date'] ?? '');
    $end_date = sanitize_text_field($_POST['end_date'] ?? '');
    
    if ($employee_id <= 0 || empty($start_date) || empty($end_date)) {
        wp_die('Employee ID, start date and end date are required');
    }
    
    global $wpdb;
    $attendance_table = $wpdb->prefix . 'linkage_attendance_logs';
    
    // Get employee information
    $employee = get_userdata($employee_id);
    if (!$employee) {
        wp_die('Employee not found');
    }
    
    $employee_name = linkage_get_user_display_name($employee_id);
    $employee_position = get_user_meta($employee_id, 'linkage_position', true) ?: 'Employee';
    $employee_email = $employee->user_email;
    $hourly_rate = floatval(get_user_meta($employee_id, 'linkage_hourly_rate', true)) ?: 15.00;
    
    // Get attendance data
    $logs = $wpdb->get_results($wpdb->prepare("
        SELECT 
            work_date,
            time_in,
            lunch_start,
            lunch_end,
            time_out,
            total_hours,
            notes
        FROM $attendance_table 
        WHERE user_id = %d 
        AND work_date BETWEEN %s AND %s
        AND status = 'completed'
        ORDER BY work_date ASC
    ", $employee_id, $start_date, $end_date));
    
    // Calculate totals
    $total_hours = 0;
    $regular_hours = 0;
    $overtime_hours = 0;
    $days_worked = count($logs);
    
    foreach ($logs as $log) {
        $daily_hours = floatval($log->total_hours);
        $total_hours += $daily_hours;
        
        if ($daily_hours > 8) {
            $overtime_hours += ($daily_hours - 8);
            $regular_hours += 8;
        } else {
            $regular_hours += $daily_hours;
        }
    }
    
    // Calculate pay
    $overtime_multiplier = 1.5; // 1.5x overtime rate
    $regular_pay = $regular_hours * $hourly_rate;
    $overtime_pay = $overtime_hours * $hourly_rate * $overtime_multiplier;
    $gross_pay = $regular_pay + $overtime_pay;
    
    // Calculate deductions (simplified)
    $tax_rate = 0.15; // 15% tax rate
    $tax_deduction = $gross_pay * $tax_rate;
    $net_pay = $gross_pay - $tax_deduction;
    
    // Generate HTML payslip
    $company_name = get_bloginfo('name');
    $payslip_date = current_time('F j, Y');
    $period_start_formatted = date('F j, Y', strtotime($start_date));
    $period_end_formatted = date('F j, Y', strtotime($end_date));
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Payslip - ' . esc_html($employee_name) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0066cc; padding-bottom: 20px; }
            .company-name { font-size: 24px; font-weight: bold; color: #0066cc; margin-bottom: 5px; }
            .payslip-title { font-size: 18px; color: #666; }
            .employee-info { margin-bottom: 30px; }
            .info-table { width: 100%; border-collapse: collapse; }
            .info-table td { padding: 8px; border: 1px solid #ddd; }
            .info-table .label { background-color: #f5f5f5; font-weight: bold; width: 150px; }
            .earnings-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .earnings-table th, .earnings-table td { padding: 10px; border: 1px solid #ddd; text-align: right; }
            .earnings-table th { background-color: #0066cc; color: white; }
            .earnings-table .description { text-align: left; }
            .totals { background-color: #f9f9f9; font-weight: bold; }
            .net-pay { background-color: #e8f5e8; font-weight: bold; font-size: 16px; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
            @media print { 
                body { margin: 0; } 
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="company-name">' . esc_html($company_name) . '</div>
            <div class="payslip-title">PAYSLIP</div>
        </div>
        
        <div class="employee-info">
            <table class="info-table">
                <tr>
                    <td class="label">Employee Name:</td>
                    <td>' . esc_html($employee_name) . '</td>
                    <td class="label">Employee ID:</td>
                    <td>' . esc_html($employee_id) . '</td>
                </tr>
                <tr>
                    <td class="label">Position:</td>
                    <td>' . esc_html($employee_position) . '</td>
                    <td class="label">Email:</td>
                    <td>' . esc_html($employee_email) . '</td>
                </tr>
                <tr>
                    <td class="label">Pay Period:</td>
                    <td>' . esc_html($period_start_formatted) . ' - ' . esc_html($period_end_formatted) . '</td>
                    <td class="label">Payslip Date:</td>
                    <td>' . esc_html($payslip_date) . '</td>
                </tr>
                <tr>
                    <td class="label">Hourly Rate:</td>
                    <td>$' . number_format($hourly_rate, 2) . '</td>
                    <td class="label">Days Worked:</td>
                    <td>' . $days_worked . '</td>
                </tr>
            </table>
        </div>
        
        <table class="earnings-table">
            <thead>
                <tr>
                    <th class="description">Description</th>
                    <th>Hours</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="description">Regular Hours</td>
                    <td>' . number_format($regular_hours, 2) . '</td>
                    <td>$' . number_format($hourly_rate, 2) . '</td>
                    <td>$' . number_format($regular_pay, 2) . '</td>
                </tr>
                <tr>
                    <td class="description">Overtime Hours (1.5x)</td>
                    <td>' . number_format($overtime_hours, 2) . '</td>
                    <td>$' . number_format($hourly_rate * $overtime_multiplier, 2) . '</td>
                    <td>$' . number_format($overtime_pay, 2) . '</td>
                </tr>
                <tr class="totals">
                    <td class="description">GROSS PAY</td>
                    <td>' . number_format($total_hours, 2) . '</td>
                    <td>-</td>
                    <td>$' . number_format($gross_pay, 2) . '</td>
                </tr>
            </tbody>
        </table>
        
        <table class="earnings-table">
            <thead>
                <tr>
                    <th class="description">Deductions</th>
                    <th colspan="2">Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="description">Federal Tax</td>
                    <td colspan="2">' . ($tax_rate * 100) . '%</td>
                    <td>$' . number_format($tax_deduction, 2) . '</td>
                </tr>
                <tr class="net-pay">
                    <td class="description">NET PAY</td>
                    <td colspan="2">-</td>
                    <td>$' . number_format($net_pay, 2) . '</td>
                </tr>
            </tbody>
        </table>';
    
    // Add detailed attendance log if there are records
    if (!empty($logs)) {
        $html .= '
        <h3 style="margin-top: 30px; color: #0066cc;">Attendance Details</h3>
        <table class="earnings-table">
            <thead>
                <tr>
                    <th class="description">Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Hours</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($logs as $log) {
            $html .= '
                <tr>
                    <td class="description">' . esc_html(date('M j, Y', strtotime($log->work_date))) . '</td>
                    <td>' . esc_html($log->time_in ? date('g:i A', strtotime($log->time_in)) : '-') . '</td>
                    <td>' . esc_html($log->time_out ? date('g:i A', strtotime($log->time_out)) : '-') . '</td>
                    <td>' . esc_html(number_format(floatval($log->total_hours), 2)) . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>';
    }
    
    $html .= '
        <div class="footer">
            <p><strong>Note:</strong> This payslip is generated electronically and is valid without signature.</p>
            <p>Generated on ' . esc_html($payslip_date) . ' by ' . esc_html($company_name) . ' Payroll System</p>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>';
    
    // Set headers for HTML output
    header('Content-Type: text/html; charset=utf-8');
    
    echo $html;
    exit;
}
add_action('wp_ajax_linkage_generate_payslip', 'linkage_ajax_generate_payslip');

/**
 * AJAX handler for getting payroll records
 */
function linkage_ajax_get_payroll_records() {
    if (!wp_verify_nonce($_POST['nonce'], 'linkage_dashboard_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!current_user_can('manage_options') && !current_user_can('linkage_view_all_attendance')) {
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
    
    if (!current_user_can('manage_options') && !current_user_can('linkage_generate_payroll_reports')) {
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
