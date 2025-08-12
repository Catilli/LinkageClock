<?php
/**
 * Debug script for time calculation
 * Run this in the browser to test the time calculation functions
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Check if user is logged in
if (!is_user_logged_in()) {
    echo "<h2>Please log in first</h2>";
    echo "<p><a href='/wp-login.php'>Login</a></p>";
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

echo "<h2>Time Calculation Debug for User: " . esc_html($current_user->display_name) . "</h2>";

// Get current user meta values
$clock_in_time = get_user_meta($user_id, 'linkage_clock_in_time', true);
$break_start_time = get_user_meta($user_id, 'linkage_break_start_time', true);
$work_seconds = get_user_meta($user_id, 'linkage_work_seconds', true) ?: 0;
$break_seconds = get_user_meta($user_id, 'linkage_break_seconds', true) ?: 0;
$employee_status = get_user_meta($user_id, 'linkage_employee_status', true);

echo "<h3>Current User Meta Values:</h3>";
echo "<p><strong>Employee Status:</strong> " . esc_html($employee_status ?: 'Not set') . "</p>";
echo "<p><strong>Clock In Time:</strong> " . esc_html($clock_in_time ?: 'Not set') . "</p>";
echo "<p><strong>Break Start Time:</strong> " . esc_html($break_start_time ?: 'Not set') . "</p>";
echo "<p><strong>Stored Work Seconds:</strong> " . esc_html($work_seconds) . "</p>";
echo "<p><strong>Stored Break Seconds:</strong> " . esc_html($break_seconds) . "</p>";

// Test the calculation functions
echo "<h3>Time Calculation Results:</h3>";

// Include the dashboard functions
require_once('functions/dashboard-functions.php');

// Test work time calculation
$calculated_work_time = linkage_calculate_current_work_time($user_id);
$calculated_break_time = linkage_calculate_current_break_time($user_id);

echo "<p><strong>Calculated Work Time:</strong> " . esc_html(linkage_format_time_display($calculated_work_time)) . " ($calculated_work_time seconds)</p>";
echo "<p><strong>Calculated Break Time:</strong> " . esc_html(linkage_format_time_display($calculated_break_time)) . " ($calculated_break_time seconds)</p>";

// Test the full employee status function
$employee_status_obj = linkage_get_employee_status_from_database($user_id);
echo "<p><strong>Full Status Object:</strong></p>";
echo "<pre>" . print_r($employee_status_obj, true) . "</pre>";

// Show database record information
echo "<h3>Database Record Information:</h3>";
global $wpdb;
$table = $wpdb->prefix . 'linkage_attendance_logs';
$work_date = current_time('Y-m-d');

$record = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table WHERE user_id = %d AND work_date = %s ORDER BY id DESC LIMIT 1",
    $user_id,
    $work_date
));

if ($record) {
    echo "<p><strong>Record ID:</strong> " . esc_html($record->id) . "</p>";
    echo "<p><strong>Work Date:</strong> " . esc_html($record->work_date) . "</p>";
    echo "<p><strong>Time In:</strong> " . esc_html($record->time_in ?: 'Not set') . "</p>";
    echo "<p><strong>Time Out:</strong> " . esc_html($record->time_out ?: 'Not set') . "</p>";
    echo "<p><strong>Lunch Start:</strong> " . esc_html($record->lunch_start ?: 'Not set') . "</p>";
    echo "<p><strong>Lunch End:</strong> " . esc_html($record->lunch_end ?: 'Not set') . "</p>";
    echo "<p><strong>Total Hours:</strong> " . esc_html($record->total_hours ?: 'Not calculated') . "</p>";
    echo "<p><strong>Status:</strong> " . esc_html($record->status) . "</p>";
} else {
    echo "<p><strong>No attendance record found for today.</strong></p>";
}

// Show current server time
echo "<h3>Server Time:</h3>";
echo "<p><strong>Current Server Time:</strong> " . esc_html(current_time('mysql')) . "</p>";

echo "<hr>";
echo "<p><a href='/'>Back to Dashboard</a></p>";
?>
