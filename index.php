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
                            <input type="text" id="employee-search" placeholder="Search by name or position..." 
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
                            <option value="Employee">Employee</option>
                            <option value="Manager">Manager</option>
                            <option value="Accounting | Payroll">Accounting | Payroll</option>
                            <option value="Contractors">Contractors</option>
                            <option value="Administrator">Administrator</option>
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
                        <?php if (current_user_can('administrator')): ?>
                            <button onclick="deleteAllTimeLogs()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                Delete All Time Logs
                            </button>
                        <?php endif; ?>
                        <button onclick="location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                            Refresh
                        </button>
                    </div>
                </div>


                
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
                                <?php if (current_user_can('manage_options') || current_user_can('linkage_export_attendance')): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
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
                                    
                                    // Get display name using reusable function
                                    $display_name = linkage_get_user_display_name($employee->ID);
                                    
                                    // Get employee position
                                    $employee_position = get_user_meta($employee->ID, 'linkage_position', true);
                            ?>
                                <tr class="employee-row hover:bg-gray-50" data-user-id="<?php echo esc_attr($employee->ID); ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php echo linkage_get_medium_avatar($employee->user_email, $display_name); ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 employee-name">
                                                    <?php echo esc_html($display_name); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 employee-position">
                                                    <?php echo !empty($employee_position) ? esc_html($employee_position) : ''; ?>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 last-action-time">
                                        <?php echo esc_html($actual_time); ?>
                                    </td>
                                    <?php if (current_user_can('manage_options') || current_user_can('linkage_export_attendance')): ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 view-employee-profile-btn" 
                                                data-employee-id="<?php echo esc_attr($employee->ID); ?>"
                                                data-employee-name="<?php echo esc_attr($display_name); ?>">
                                            View Profile
                                        </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <tr>
                                    <td colspan="<?php echo (current_user_can('manage_options') || current_user_can('linkage_export_attendance')) ? '5' : '4'; ?>" class="px-6 py-4 text-center text-gray-500">
                                        No employees found. Please add employees to see their status.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

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

        <?php endif; ?>
        
    </div>
</div>

<!-- Employee Profile Modal -->
<div id="employee-profile-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 id="profile-modal-employee-name" class="text-xl font-semibold text-gray-900">Employee Profile</h3>
            <button id="close-profile-modal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            <!-- Loading State -->
            <div id="profile-loading" class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
                <p class="text-gray-600">Loading employee profile...</p>
            </div>
            
            <!-- Profile Content -->
            <div id="profile-content" class="hidden">
                <!-- Employee Information -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Employee Information</h4>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <div id="profile-name" class="mt-1 text-sm text-gray-900"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <div id="profile-email" class="mt-1 text-sm text-gray-900"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Position</label>
                            <div id="profile-position" class="mt-1 text-sm text-gray-900"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <div id="profile-role" class="mt-1 text-sm text-gray-900"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hire Date</label>
                            <div id="profile-hire-date" class="mt-1 text-sm text-gray-900"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee ID</label>
                            <div id="profile-employee-id" class="mt-1 text-sm text-gray-900"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Current Status -->
                <div class="bg-white border rounded-lg p-6 mb-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Current Status</h4>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Status</label>
                            <div id="profile-current-status" class="mt-1"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Action</label>
                            <div id="profile-last-action" class="mt-1 text-sm text-gray-900"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Attendance -->
                <div class="bg-white border rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Recent Attendance (Last 7 Days)</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody id="profile-attendance" class="bg-white divide-y divide-gray-200">
                                <!-- Attendance records will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
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
    
    // Employee profile modal functionality
    $('.view-employee-profile-btn').on('click', function() {
        const employeeId = $(this).data('employee-id');
        const employeeName = $(this).data('employee-name');
        viewEmployeeProfile(employeeId, employeeName);
    });
    
    $('#close-profile-modal').on('click', function() {
        $('#employee-profile-modal').addClass('hidden');
    });
    
    // Close modal when clicking outside
    $('#employee-profile-modal').on('click', function(e) {
        if (e.target === this) {
            $('#employee-profile-modal').removeClass('hidden').addClass('hidden');
        }
    });
    
    // Close modal with Escape key
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27 && !$('#employee-profile-modal').hasClass('hidden')) {
            $('#employee-profile-modal').addClass('hidden');
        }
    });
});

// Function to view employee profile
function viewEmployeeProfile(employeeId, employeeName) {
    $('#profile-modal-employee-name').text(employeeName + ' - Employee Profile');
    $('#profile-loading').show();
    $('#profile-content').hide();
    $('#employee-profile-modal').removeClass('hidden');
    
    // Load employee profile data
    $.ajax({
        url: linkage_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'linkage_get_employee_profile',
            employee_id: employeeId,
            nonce: linkage_ajax.nonce
        },
        success: function(response) {
            $('#profile-loading').hide();
            if (response.success) {
                populateEmployeeProfile(response.data);
                $('#profile-content').show();
            } else {
                alert('Error loading employee profile: ' + (response.data || 'Unknown error'));
                $('#employee-profile-modal').addClass('hidden');
            }
        },
        error: function() {
            $('#profile-loading').hide();
            alert('Network error occurred while loading employee profile.');
            $('#employee-profile-modal').addClass('hidden');
        }
    });
}

// Function to populate employee profile data
function populateEmployeeProfile(data) {
    $('#profile-name').text(data.name);
    $('#profile-email').text(data.email);
    $('#profile-position').text(data.position || 'Not set');
    $('#profile-role').text(data.role);
    $('#profile-hire-date').text(data.hire_date);
    $('#profile-employee-id').text(data.employee_id);
    
    // Current status with badge
    let statusBadge = '';
    switch(data.current_status) {
        case 'clocked_in':
            statusBadge = '<span class="status-badge clocked-in">Clocked In</span>';
            break;
        case 'on_break':
            statusBadge = '<span class="status-badge break-status">On Break</span>';
            break;
        case 'clocked_out':
        default:
            statusBadge = '<span class="status-badge clocked-out">Clocked Out</span>';
            break;
    }
    $('#profile-current-status').html(statusBadge);
    $('#profile-last-action').text(data.last_action || 'Never');
    
    // Recent attendance
    const $attendanceBody = $('#profile-attendance');
    $attendanceBody.empty();
    
    if (data.recent_attendance && data.recent_attendance.length > 0) {
        data.recent_attendance.forEach(function(record) {
            const statusText = record.status === 'completed' ? 'Completed' : 'Active';
            const statusClass = record.status === 'completed' ? 'text-green-600' : 'text-blue-600';
            
            $attendanceBody.append(`
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${record.work_date}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${record.time_in || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${record.time_out || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${record.total_hours || '0.00'} hrs</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm ${statusClass}">${statusText}</td>
                </tr>
            `);
        });
    } else {
        $attendanceBody.append(`
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No recent attendance records found.</td>
            </tr>
        `);
    }
});

// Delete all time logs function
function deleteAllTimeLogs() {
    if (confirm('⚠️ WARNING: This will DELETE ALL time logs from the attendance logs table!\n\nThis action cannot be undone and will reset all employee statuses to "clocked out".\n\nAre you sure you want to continue?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_all_time_logs';
        input.value = '1';
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php 
// Handle the delete all time logs action
if (isset($_POST['delete_all_time_logs']) && current_user_can('administrator')) {
    $result = linkage_delete_all_time_logs();
    echo '<div class="fixed top-4 right-4 z-50 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow-lg" id="delete-success-message">';
    echo "<p><strong>$result</strong></p>";
    echo '<p>All time logs have been deleted. The attendance logs table is now empty.</p>';
    echo '<p>All employees will now show as "clocked out". Please refresh the page to see the updated status.</p>';
    echo '</div>';
    echo '<script>setTimeout(function() { document.getElementById("delete-success-message").style.display = "none"; }, 5000);</script>';
}

get_footer(); ?>
