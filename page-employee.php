<?php
/*
 * Template Name: Employee
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url() );
    exit;
}

$current_user = wp_get_current_user();
$current_user_id = $current_user->ID;

// Check if user is viewing their own profile or has admin access
$viewing_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_id;
$can_view_all = current_user_can('administrator') || current_user_can('payroll_admin');

// If not admin and trying to view someone else's profile, redirect to own profile
if (!$can_view_all && $viewing_user_id !== $current_user_id) {
    wp_redirect(home_url('/account'));
    exit;
}

$viewing_user = get_userdata($viewing_user_id);
if (!$viewing_user) {
    wp_redirect(home_url('/account'));
    exit;
}

// Get employee data
$employee_status = linkage_get_employee_status($viewing_user_id);
$company_id = get_user_meta($viewing_user_id, 'linkage_company_id', true) ?: '';
$position = get_user_meta($viewing_user_id, 'linkage_position', true) ?: 'Employee';
$hire_date = get_user_meta($viewing_user_id, 'linkage_hire_date', true) ?: $viewing_user->user_registered;

// Handle form submission for profile updates
if ( isset($_POST['update_profile']) && wp_verify_nonce($_POST['profile_nonce'], 'update_profile') && $viewing_user_id === $current_user_id ) {
    $userdata = array(
        'ID'           => $current_user_id,
        'display_name' => sanitize_text_field($_POST['display_name']),
        'user_email'   => sanitize_email($_POST['user_email']),
    );
    wp_update_user($userdata);

    // Update employee-specific fields
    if (isset($_POST['company_id'])) {
        update_user_meta($current_user_id, 'linkage_company_id', sanitize_text_field($_POST['company_id']));
    }
    if (isset($_POST['position'])) {
        update_user_meta($current_user_id, 'linkage_position', sanitize_text_field($_POST['position']));
    }
    if (isset($_POST['hire_date'])) {
        update_user_meta($current_user_id, 'linkage_hire_date', sanitize_text_field($_POST['hire_date']));
    }

    if ( ! empty($_POST['password']) && $_POST['password'] === $_POST['confirm_password'] ) {
        wp_set_password($_POST['password'], $current_user_id);
        wp_redirect( wp_login_url() ); // User will need to log in again
        exit;
    }
    
    // Redirect to refresh the page and show updated values
    wp_redirect(add_query_arg('updated', '1', $_SERVER['REQUEST_URI']));
    exit;
}

// Get attendance data for the selected period
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'current_month';
$start_date = '';
$end_date = '';

switch ($period) {
    case 'current_week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'last_month':
        $start_date = date('Y-m-d', strtotime('first day of last month'));
        $end_date = date('Y-m-d', strtotime('last day of last month'));
        break;
    case 'last_3_months':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        $end_date = date('Y-m-d');
        break;
    default: // current_month
        $start_date = date('Y-m-d', strtotime('first day of this month'));
        $end_date = date('Y-m-d');
        break;
}

// Get attendance logs (this would need to be implemented in your database)
$attendance_logs = array(); // Placeholder - implement based on your database structure
$total_hours = 0;
$days_worked = 0;
$average_daily_hours = 0;
$overtime_hours = 0;

// Calculate summary statistics
if (!empty($attendance_logs)) {
    foreach ($attendance_logs as $log) {
        $total_hours += $log['hours'] ?? 0;
        $days_worked++;
    }
    
    if ($days_worked > 0) {
        $average_daily_hours = round($total_hours / $days_worked, 2);
    }
    
    // Calculate overtime (assuming 8 hours per day is standard)
    $standard_hours = $days_worked * 8;
    $overtime_hours = max(0, $total_hours - $standard_hours);
}
?>

<?php get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">

        <!-- Success Message -->
        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Profile and employee information updated successfully!</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Employee Profile</h1>
                    <p class="text-gray-600 mt-2">
                        <?php if ($viewing_user_id === $current_user_id): ?>
                            Your profile and attendance information
                        <?php else: ?>
                            Profile for <?php echo esc_html($viewing_user->display_name); ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Period Selector -->
                <div class="flex items-center space-x-4">
                    <label for="period-select" class="text-sm font-medium text-gray-700">Time Period:</label>
                    <select id="period-select" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="current_week" <?php selected($period, 'current_week'); ?>>This Week</option>
                        <option value="current_month" <?php selected($period, 'current_month'); ?>>This Month</option>
                        <option value="last_month" <?php selected($period, 'last_month'); ?>>Last Month</option>
                        <option value="last_3_months" <?php selected($period, 'last_3_months'); ?>>Last 3 Months</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-4 gap-8">
            
            <!-- Left Sidebar - Employee Info & Quick Stats -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Employee Profile Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <?php 
                        echo linkage_get_large_avatar(
                            $viewing_user->user_email, 
                            $viewing_user->display_name,
                            array(
                                'wrapper_class' => 'mx-auto mb-4',
                                'show_indicator' => true,
                                'fallback_bg' => 'bg-blue-500',
                                'fallback_text_color' => 'text-white'
                            )
                        );
                        ?>
                        <h3 class="text-xl font-semibold text-gray-900">
                            <?php echo esc_html($viewing_user->display_name); ?>
                        </h3>
                        <p class="text-sm text-gray-600">
                            <?php echo esc_html($position); ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Company ID: <?php echo esc_html($company_id ?: 'Not set'); ?>
                        </p>
                        <p class="text-xs text-gray-500">
                            Hired: <?php echo $hire_date ? date('M Y', strtotime($hire_date)) : 'Not set'; ?>
                        </p>
                    </div>
                </div>

                <!-- Current Status -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Current Status
                    </h3>
                    
                    <?php 
                    $status_class = $employee_status->status === 'clocked_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    $status_text = $employee_status->status === 'clocked_in' ? 'Clocked In' : 'Clocked Out';
                    ?>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Last Action:</span>
                            <span class="text-sm text-gray-900">
                                <?php echo linkage_format_actual_time($employee_status->last_action_time); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Summary (<?php echo ucfirst(str_replace('_', ' ', $period)); ?>)
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Hours:</span>
                            <span class="text-lg font-semibold text-gray-900"><?php echo number_format($total_hours, 1); ?>h</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Days Worked:</span>
                            <span class="text-lg font-semibold text-gray-900"><?php echo $days_worked; ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Avg Daily:</span>
                            <span class="text-lg font-semibold text-gray-900"><?php echo number_format($average_daily_hours, 1); ?>h</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Overtime:</span>
                            <span class="text-lg font-semibold text-orange-600"><?php echo number_format($overtime_hours, 1); ?>h</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <?php if ($viewing_user_id === $current_user_id): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Quick Actions
                    </h3>
                    
                    <div class="space-y-3">
                        <a href="<?php echo esc_url(home_url('/')); ?>" 
                           class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-2 px-4 rounded-lg transition-colors duration-200">
                            Dashboard
                        </a>
                        
                        <a href="<?php echo esc_url(home_url('/time-tracking')); ?>" 
                           class="block w-full bg-green-500 hover:bg-green-600 text-white text-center py-2 px-4 rounded-lg transition-colors duration-200">
                            Track Time
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Main Content Area -->
            <div class="lg:col-span-3 space-y-6">
                
                <!-- Profile Information (Editable for own profile) -->
                <?php if ($viewing_user_id === $current_user_id): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Profile Information
                        </h2>
                    </div>
                    
                    <form method="post" class="p-6 space-y-6">
                        <?php wp_nonce_field('update_profile', 'profile_nonce'); ?>

                        <!-- Display Name -->
                        <div>
                            <label for="display_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Display Name
                            </label>
                            <input type="text" 
                                   id="display_name"
                                   name="display_name" 
                                   value="<?php echo esc_attr($viewing_user->display_name); ?>"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="user_email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input type="email" 
                                   id="user_email"
                                   name="user_email" 
                                   value="<?php echo esc_attr($viewing_user->user_email); ?>"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            
                            <?php 
                            $current_has_gravatar = linkage_has_gravatar($viewing_user->user_email);
                            ?>
                            
                            <div class="mt-2 p-3 <?php echo $current_has_gravatar ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200'; ?> border rounded-md">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <?php if ($current_has_gravatar): ?>
                                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-3">
                                        <?php if ($current_has_gravatar): ?>
                                            <p class="text-sm text-green-700">
                                                <strong>Great!</strong> Your email has a Gravatar profile picture.
                                            </p>
                                        <?php else: ?>
                                            <p class="text-sm text-blue-700">
                                                <strong>Profile Picture:</strong> To add a profile picture, create a free account at 
                                                <a href="https://gravatar.com" target="_blank" class="font-medium underline hover:no-underline">Gravatar.com</a> 
                                                using this email address.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Information Section -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Information</h3>
                            
                            <!-- Company ID -->
                            <div class="mb-4">
                                <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Company ID
                                </label>
                                <input type="text" 
                                       id="company_id"
                                       name="company_id" 
                                       value="<?php echo esc_attr($company_id); ?>"
                                       placeholder="Enter your company ID"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-sm text-gray-500">Your unique company identifier</p>
                            </div>

                            <!-- Position -->
                            <div class="mb-4">
                                <label for="position" class="block text-sm font-medium text-gray-700 mb-2">
                                    Position
                                </label>
                                <input type="text" 
                                       id="position"
                                       name="position" 
                                       value="<?php echo esc_attr($position); ?>"
                                       placeholder="Enter your job position"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-sm text-gray-500">Your job title or occupation</p>
                            </div>

                            <!-- Hire Date -->
                            <div class="mb-4">
                                <label for="hire_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Hire Date
                                </label>
                                <input type="date" 
                                       id="hire_date"
                                       name="hire_date" 
                                       value="<?php echo esc_attr($hire_date ? date('Y-m-d', strtotime($hire_date)) : ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-sm text-gray-500">The date you were hired</p>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                            <p class="text-sm text-gray-600 mb-4">Leave blank if you don't want to change your password.</p>
                            
                            <!-- New Password -->
                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    New Password
                                </label>
                                <input type="password" 
                                       id="password"
                                       name="password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    Confirm New Password
                                </label>
                                <input type="password" 
                                       id="confirm_password"
                                       name="confirm_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6 border-t border-gray-200">
                            <button type="submit" 
                                    name="update_profile"
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Attendance Logs -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Attendance Logs
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Detailed time tracking for <?php echo ucfirst(str_replace('_', ' ', $period)); ?>
                        </p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <?php if (!empty($attendance_logs)): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lunch Start</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lunch End</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($attendance_logs as $log): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($log['date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $log['time_in'] ? date('g:i A', strtotime($log['time_in'])) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $log['time_out'] ? date('g:i A', strtotime($log['time_out'])) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $log['lunch_start'] ? date('g:i A', strtotime($log['lunch_start'])) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $log['lunch_end'] ? date('g:i A', strtotime($log['lunch_end'])) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="font-medium"><?php echo number_format($log['hours'], 1); ?>h</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        $status_class = $log['status'] === 'complete' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                                        $status_text = $log['status'] === 'complete' ? 'Complete' : 'In Progress';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="p-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No attendance records</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                No attendance logs found for the selected period.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Detailed Statistics Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Work Hours Trend
                    </h2>
                    
                    <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                        <p class="text-gray-500">Chart visualization would go here</p>
                        <p class="text-sm text-gray-400">(Implement with Chart.js or similar library)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (passwordField && confirmPasswordField) {
        function validatePasswords() {
            if (passwordField.value && confirmPasswordField.value) {
                if (passwordField.value !== confirmPasswordField.value) {
                    confirmPasswordField.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordField.setCustomValidity('');
                }
            }
        }
        
        passwordField.addEventListener('input', validatePasswords);
        confirmPasswordField.addEventListener('input', validatePasswords);
    }

    // Period selector change handler
    const periodSelect = document.getElementById('period-select');
    if (periodSelect) {
        periodSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('period', this.value);
            window.location.href = currentUrl.toString();
        });
    }
});
</script>

<?php get_footer(); ?>