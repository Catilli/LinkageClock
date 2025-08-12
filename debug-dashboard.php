<?php
/**
 * Debug Dashboard - Temporary file to diagnose employee display issues
 */

// Include WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Include our functions
require_once get_template_directory() . '/functions/dashboard-functions.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>LinkageClock Debug Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>LinkageClock Debug Dashboard</h1>
    
    <div class="debug-section">
        <h2>Quick Fix Actions</h2>
        <p>Click these buttons to fix common issues:</p>
        
        <form method="post">
            <button type="submit" name="action" value="create_tables">1. Create Attendance Logs Table</button>
            <button type="submit" name="action" value="initialize_users">2. Initialize All Users as Employees</button>
            <button type="submit" name="action" value="debug_all">3. Run Full Debug</button>
        </form>
    </div>

    <?php
    if ($_POST) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'create_tables':
                echo '<div class="debug-section success">';
                linkage_force_create_tables();
                echo '</div>';
                break;
                
            case 'initialize_users':
                echo '<div class="debug-section success">';
                linkage_force_initialize_all_users();
                echo '</div>';
                break;
                
            case 'debug_all':
                echo '<div class="debug-section">';
                linkage_debug_database_tables();
                echo '</div>';
                
                echo '<div class="debug-section">';
                linkage_debug_user_roles();
                echo '</div>';
                
                echo '<div class="debug-section">';
                echo '<h3>Employee Query Test</h3>';
                $employees = linkage_get_all_employees_status();
                echo '<p><strong>Employees Found:</strong> ' . count($employees) . '</p>';
                if (!empty($employees)) {
                    echo '<p><strong>Employee List:</strong></p>';
                    echo '<pre>' . print_r($employees, true) . '</pre>';
                } else {
                    echo '<p class="error">No employees found!</p>';
                }
                echo '</div>';
                
                echo '<div class="debug-section">';
                echo '<h3>Time Button Debug</h3>';
                linkage_debug_time_button();
                echo '</div>';
                break;
                
            case 'test_clock_in':
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $result = linkage_update_employee_status($user_id, 'clocked_in', 'clock_in', 'Test clock in via debug');
                    update_user_meta($user_id, 'linkage_clock_in_time', current_time('mysql'));
                    echo '<div class="debug-section success">✓ Test Clock In completed. Status updated to: clocked_in</div>';
                }
                break;
                
            case 'test_clock_out':
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $result = linkage_update_employee_status($user_id, 'clocked_out', 'clock_out', 'Test clock out via debug');
                    delete_user_meta($user_id, 'linkage_clock_in_time');
                    delete_user_meta($user_id, 'linkage_break_start_time');
                    echo '<div class="debug-section success">✓ Test Clock Out completed. Status updated to: clocked_out</div>';
                }
                break;
                
            case 'test_break_start':
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $result = linkage_update_employee_status($user_id, 'on_break', 'break_in', 'Test break start via debug');
                    update_user_meta($user_id, 'linkage_break_start_time', current_time('mysql'));
                    echo '<div class="debug-section success">✓ Test Break Start completed. Status updated to: on_break</div>';
                }
                break;
                
            case 'test_break_end':
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $result = linkage_update_employee_status($user_id, 'clocked_in', 'break_out', 'Test break end via debug');
                    delete_user_meta($user_id, 'linkage_break_start_time');
                    update_user_meta($user_id, 'linkage_clock_in_time', current_time('mysql'));
                    echo '<div class="debug-section success">✓ Test Break End completed. Status updated to: clocked_in</div>';
                }
                break;
        }
    }
    ?>

    <div class="debug-section">
        <h2>Current Status</h2>
        <?php
        // Check if attendance logs table exists
        global $wpdb;
        $attendance_table = $wpdb->prefix . 'linkage_attendance_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$attendance_table'") == $attendance_table;
        
        if ($table_exists) {
            echo '<p class="success">✓ Attendance logs table exists</p>';
        } else {
            echo '<p class="error">✗ Attendance logs table does not exist</p>';
        }
        
        // Check user count
        $user_count = count_users();
        echo '<p><strong>Total Users:</strong> ' . $user_count['total_users'] . '</p>';
        
        // Check if user is logged in
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            echo '<p class="success">✓ User logged in: ' . $current_user->display_name . '</p>';
            
            // Check employee status
            $employee_status = linkage_get_employee_status($current_user->ID);
            echo '<p><strong>Current Status:</strong> ' . $employee_status->status . '</p>';
            echo '<p><strong>Last Action:</strong> ' . $employee_status->last_action_time . '</p>';
            
            // Check if clock button should be visible
            $is_clocked_in = $employee_status->status === 'clocked_in';
            $is_on_break = $employee_status->status === 'on_break';
            $button_should_show = true; // Always true now
            
            echo '<p><strong>Clock Button Should Show:</strong> ' . ($button_should_show ? 'Yes' : 'No') . '</p>';
            echo '<p><strong>Current Status:</strong> ' . $employee_status->status . '</p>';
            
        } else {
            echo '<p class="error">✗ No user logged in</p>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>Test Clock Functionality</h2>
        <?php if (is_user_logged_in()): ?>
            <p>Test the clock functionality:</p>
            <form method="post">
                <button type="submit" name="action" value="test_clock_in">Test Clock In</button>
                <button type="submit" name="action" value="test_clock_out">Test Clock Out</button>
                <button type="submit" name="action" value="test_break_start">Test Break Start</button>
                <button type="submit" name="action" value="test_break_end">Test Break End</button>
            </form>
            
            <div style="margin-top: 20px;">
                <h3>JavaScript DOM Test</h3>
                <button onclick="testButtonVisibility()">Test Button Visibility in DOM</button>
                <div id="dom-test-results" style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 3px;"></div>
            </div>
        <?php else: ?>
            <p>Please log in to test clock functionality.</p>
        <?php endif; ?>
    </div>
    
    <script>
    function testButtonVisibility() {
        const resultsDiv = document.getElementById('dom-test-results');
        const clockBtn = document.getElementById('clock-toggle-btn');
        const breakBtn = document.getElementById('break-toggle-btn');
        const workTimer = document.getElementById('work-timer');
        const breakTimer = document.getElementById('break-timer');
        
        let results = '<h4>DOM Element Visibility Test:</h4>';
        
        if (clockBtn) {
            const computedStyle = window.getComputedStyle(clockBtn);
            const display = computedStyle.display;
            const visibility = computedStyle.visibility;
            const opacity = computedStyle.opacity;
            
            results += '<p><strong>Clock Button:</strong></p>';
            results += '<ul>';
            results += '<li>Element exists: ✓</li>';
            results += '<li>Display: ' + display + '</li>';
            results += '<li>Visibility: ' + visibility + '</li>';
            results += '<li>Opacity: ' + opacity + '</li>';
            results += '<li>Offset dimensions: ' + clockBtn.offsetWidth + 'x' + clockBtn.offsetHeight + '</li>';
            results += '</ul>';
        } else {
            results += '<p><strong>Clock Button:</strong> ✗ Element not found in DOM</p>';
        }
        
        if (breakBtn) {
            const computedStyle = window.getComputedStyle(breakBtn);
            const display = computedStyle.display;
            results += '<p><strong>Break Button:</strong> Display: ' + display + '</p>';
        } else {
            results += '<p><strong>Break Button:</strong> ✗ Element not found in DOM</p>';
        }
        
        if (workTimer) {
            const computedStyle = window.getComputedStyle(workTimer);
            const display = computedStyle.display;
            results += '<p><strong>Work Timer:</strong> Display: ' + display + '</p>';
        } else {
            results += '<p><strong>Work Timer:</strong> ✗ Element not found in DOM</p>';
        }
        
        if (breakTimer) {
            const computedStyle = window.getComputedStyle(breakTimer);
            const display = computedStyle.display;
            results += '<p><strong>Break Timer:</strong> Display: ' + display + '</p>';
        } else {
            results += '<p><strong>Break Timer:</strong> ✗ Element not found in DOM</p>';
        }
        
        resultsDiv.innerHTML = results;
    }
    </script>

    <div class="debug-section">
        <h2>Manual Fix Instructions</h2>
        <ol>
            <li><strong>Create Attendance Logs Table:</strong> Click "Create Attendance Logs Table" above (needed for time tracking and payroll exports)</li>
            <li><strong>Assign Roles:</strong> Go to WordPress Admin → Users and assign the "Employee" role to your users</li>
            <li><strong>Initialize Status:</strong> Click "Initialize All Users as Employees" above</li>
            <li><strong>Refresh:</strong> Go back to your main dashboard and refresh the page</li>
        </ol>
    </div>

    <div class="debug-section">
        <h2>Export Testing</h2>
        <p>Test the export functionality (requires attendance logs table to exist):</p>
        
        <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" name="action" value="linkage_export_attendance">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('linkage_export_nonce'); ?>">
            
            <div style="margin: 10px 0;">
                <label>Employee (optional):</label>
                <select name="user_id">
                    <option value="">All Employees</option>
                    <?php
                    $users = get_users(array('role__in' => array('employee', 'administrator')));
                    foreach ($users as $user) {
                        echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div style="margin: 10px 0;">
                <label>Start Date:</label>
                <input type="date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
            </div>
            
            <div style="margin: 10px 0;">
                <label>End Date:</label>
                <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div style="margin: 10px 0;">
                <label>Format:</label>
                <select name="format">
                    <option value="csv">CSV</option>
                    <option value="xlsx">XLSX (requires PhpSpreadsheet)</option>
                </select>
            </div>
            
            <button type="submit" class="button button-primary">Export Attendance Data</button>
        </form>
        
        <p><strong>Note:</strong> XLSX export requires the PhpSpreadsheet library to be installed.</p>
    </div>

    <div class="debug-section">
        <h2>Server-Side Time Calculation Test</h2>
        <p>Test the new server-side time calculation functions:</p>
        
        <?php
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $employee_status = linkage_get_employee_status_from_database($current_user->ID);
            
            echo '<p><strong>Current User:</strong> ' . esc_html($current_user->display_name) . '</p>';
            echo '<p><strong>Status:</strong> ' . esc_html($employee_status->status) . '</p>';
            echo '<p><strong>Clock In Time:</strong> ' . ($employee_status->clock_in_time ? esc_html($employee_status->clock_in_time) : 'Not set') . '</p>';
            echo '<p><strong>Break Start Time:</strong> ' . ($employee_status->break_start_time ? esc_html($employee_status->break_start_time) : 'Not set') . '</p>';
            echo '<p><strong>Calculated Work Time:</strong> ' . esc_html(linkage_format_time_display($employee_status->work_seconds)) . '</p>';
            echo '<p><strong>Calculated Break Time:</strong> ' . esc_html(linkage_format_time_display($employee_status->break_seconds)) . '</p>';
            
            // Test the calculation functions directly
            echo '<h3>Direct Function Tests:</h3>';
            echo '<p><strong>Work Time Calculation:</strong> ' . esc_html(linkage_format_time_display(linkage_calculate_current_work_time($current_user->ID))) . '</p>';
            echo '<p><strong>Break Time Calculation:</strong> ' . esc_html(linkage_format_time_display(linkage_calculate_current_break_time($current_user->ID))) . '</p>';
            
            // Show database record information
            echo '<h3>Database Record Information:</h3>';
            global $wpdb;
            $table = $wpdb->prefix . 'linkage_attendance_logs';
            $work_date = current_time('Y-m-d');
            
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d AND work_date = %s ORDER BY id DESC LIMIT 1",
                $current_user->ID,
                $work_date
            ));
            
            if ($record) {
                echo '<p><strong>Record ID:</strong> ' . esc_html($record->id) . '</p>';
                echo '<p><strong>Work Date:</strong> ' . esc_html($record->work_date) . '</p>';
                echo '<p><strong>Time In:</strong> ' . esc_html($record->time_in ?: 'Not set') . '</p>';
                echo '<p><strong>Time Out:</strong> ' . esc_html($record->time_out ?: 'Not set') . '</p>';
                echo '<p><strong>Lunch Start:</strong> ' . esc_html($record->lunch_start ?: 'Not set') . '</p>';
                echo '<p><strong>Lunch End:</strong> ' . esc_html($record->lunch_end ?: 'Not set') . '</p>';
                echo '<p><strong>Total Hours:</strong> ' . esc_html($record->total_hours ?: 'Not calculated') . '</p>';
                echo '<p><strong>Status:</strong> ' . esc_html($record->status) . '</p>';
            } else {
                echo '<p><strong>No attendance record found for today.</strong></p>';
            }
        } else {
            echo '<p>Please log in to test time calculations.</p>';
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>WordPress Admin Links</h2>
        <p><a href="<?php echo admin_url('users.php'); ?>" target="_blank">Manage Users</a></p>
        <p><a href="<?php echo home_url(); ?>" target="_blank">Main Dashboard</a></p>
    </div>

    <div class="debug-section">
        <h2>How It Works Now</h2>
        <p><strong>Employee Status Storage:</strong> Employee status is now stored in WordPress user meta fields:</p>
        <ul>
            <li><code>linkage_employee_status</code> - Current status (clocked_in/clocked_out)</li>
            <li><code>linkage_last_action_time</code> - Timestamp of last action</li>
            <li><code>linkage_last_action_type</code> - Type of last action (clock_in/clock_out)</li>
            <li><code>linkage_last_notes</code> - Notes from last action</li>
        </ul>
        <p><strong>Benefits:</strong></p>
        <ul>
            <li>Uses WordPress's built-in user system</li>
            <li>No additional database tables needed</li>
            <li>Easier to manage and backup</li>
            <li>Better integration with WordPress user management</li>
        </ul>
    </div>
</body>
</html>
