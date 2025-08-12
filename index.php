<?php
/**
 * The main template file - Employee Dashboard
 */

// Debug information
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "<!-- Debug: User logged in - ID: " . $current_user->ID . ", Roles: " . implode(', ', $current_user->roles) . " -->";
} else {
    echo "<!-- Debug: No user logged in -->";
}

get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">

        <?php if (is_user_logged_in()): ?>
            <!-- Search and Filter Controls -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <div class="grid md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label for="employee-search" class="block text-sm font-medium text-gray-700 mb-2">Search Employees</label>
                        <div class="relative">
                            <input type="text" id="employee-search" placeholder="Search by name or email..." 
                                   class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="status-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Status</option>
                            <option value="clocked_in">Clocked In</option>
                            <option value="clocked_out">Clocked Out</option>
                            <option value="on_break">On Break</option>
                        </select>
                    </div>
                    
                    <!-- Role Filter -->
                    <div>
                        <label for="role-filter" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select id="role-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Roles</option>
                            <option value="employee">Employee</option>
                            <option value="hr manager">HR Manager</option>
                            <option value="administrator">Administrator</option>
                        </select>
                    </div>
                </div>
                
                <!-- Employee Count -->
                <div class="mt-4 text-sm text-gray-600">
                    <span id="employee-count">Loading...</span>
                </div>
            </div>

            <!-- Employees Table -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Employee Status</h2>
                    <div class="flex space-x-2">
                        <button onclick="location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                            Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Debug Info for Current User -->
                <?php if (is_user_logged_in()): ?>
                    <?php 
                    $current_user = wp_get_current_user();
                    $current_status = linkage_get_employee_status_from_database($current_user->ID);
                    ?>
                    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <h4 class="font-semibold text-yellow-800">Debug: Current User Status</h4>
                        <p class="text-sm text-yellow-700">
                            <strong>User:</strong> <?php echo esc_html($current_user->display_name); ?> 
                            <strong>Role:</strong> <?php echo implode(', ', $current_user->roles); ?>
                        </p>
                        <p class="text-sm text-yellow-700">
                            <strong>Database Status:</strong> <?php echo esc_html($current_status->status); ?>
                            <strong>Last Action:</strong> <?php echo esc_html($current_status->last_action_time); ?>
                        </p>
                        <p class="text-sm text-yellow-700">
                            <strong>Clock Button Action:</strong> <?php echo ($current_status->status === 'clocked_in' || $current_status->status === 'on_break') ? 'Time Out' : 'Time In'; ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Employee
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Action Time
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // Initialize employee status for users who don't have status records
                            linkage_initialize_employee_status();
                            
                            // Get all employees with their status
                            $employees = linkage_get_all_employees_status();
                            
                            if (!empty($employees)):
                                foreach ($employees as $employee):
                                    // Determine status display logic
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch ($employee->current_status) {
                                        case 'clocked_in':
                                            $status_class = 'clocked-in';
                                            $status_text = 'Clocked In';
                                            break;
                                        case 'on_break':
                                            $status_class = 'break-status';
                                            $status_text = 'On Break';
                                            break;
                                        case 'clocked_out':
                                        default:
                                            $status_class = 'clocked-out';
                                            $status_text = 'Clocked Out';
                                            break;
                                    }
                                    
                                    $role_display = linkage_get_user_role_display($employee->ID);
                                    $actual_time = linkage_format_actual_time($employee->last_action_time);
                            ?>
                                <tr class="employee-row hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php echo linkage_get_medium_avatar($employee->user_email, $employee->display_name); ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 employee-name">
                                                    <?php echo esc_html($employee->display_name); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 employee-email">
                                                    <?php echo esc_html($employee->user_email); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 employee-role">
                                            <?php echo esc_html($role_display); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1">
                                            <span class="status-badge <?php echo $status_class; ?> px-2 inline-flex text-xs leading-5 font-semibold rounded-full employee-status" 
                                                  data-status="<?php echo esc_attr($employee->current_status); ?>">
                                                <?php echo esc_html($status_text); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo esc_html($actual_time); ?>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                        No employees found. Please add employees to see their status.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Debug Database Status -->
            <?php if (current_user_can('administrator')): ?>
                <div class="mt-8 bg-white shadow-md rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Database Debug Info (Admin Only)</h3>
                        <div class="flex space-x-2">
                            <button onclick="location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                Refresh
                            </button>
                            <button onclick="resetAllUsers()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                Reset All Users to Clocked Out
                            </button>
                        </div>
                    </div>
                    <?php linkage_debug_database_status(); ?>
                </div>
                
                <script>
                function resetAllUsers() {
                    if (confirm('Are you sure you want to reset all users to clocked out? This will clear any active work sessions.')) {
                        // Create a form to submit the reset action
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = window.location.href;
                        
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'reset_all_users';
                        input.value = '1';
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
                </script>
                
                <?php
                // Handle the reset action
                if (isset($_POST['reset_all_users']) && current_user_can('administrator')) {
                    $users = get_users(array(
                        'role__in' => array('employee', 'hr_manager', 'administrator'),
                        'fields' => 'ID'
                    ));
                    
                    $reset_count = 0;
                    foreach ($users as $user_id) {
                        if (linkage_reset_user_status($user_id)) {
                            $reset_count++;
                        }
                    }
                    
                    echo '<div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">';
                    echo "<p><strong>Reset $reset_count users to clocked out status!</strong></p>";
                    echo '<p>Please refresh the page to see the updated status.</p>';
                    echo '</div>';
                }
                ?>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="grid md:grid-cols-3 gap-6 mt-8">
                <?php
                $total_employees = count($employees);
                $clocked_in_count = 0;
                $clocked_out_count = 0;
                
                foreach ($employees as $employee) {
                    switch ($employee->current_status) {
                        case 'clocked_in':
                        case 'on_break':
                            $clocked_in_count++;
                            break;
                        case 'clocked_out':
                        default:
                            $clocked_out_count++;
                            break;
                    }
                }
                ?>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold"><?php echo $total_employees; ?></span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-500">Total Employees</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo $total_employees; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold"><?php echo $clocked_in_count; ?></span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-500">Clocked In</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo $clocked_in_count; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold"><?php echo $clocked_out_count; ?></span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-500">Clocked Out</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo $clocked_out_count; ?></div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Not Logged In Message -->
            <div class="text-center py-12">
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                    <p class="text-lg">Please log in to view the employee dashboard.</p>
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="inline-block mt-2 bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                        Log In
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
// Initialize employee count on page load
jQuery(document).ready(function($) {
    setTimeout(function() {
        const visibleCount = $('.employee-row:visible').length;
        const totalCount = $('.employee-row').length;
        $('#employee-count').text(`${visibleCount} of ${totalCount} employees`);
    }, 100);
});
</script>

<?php get_footer(); ?>
