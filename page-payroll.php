<?php
/**
 * Template Name: Payroll Dashboard
 * 
 * Payroll dashboard for processing payroll and exporting attendance records
 */

// Security check - Only allow access to payroll staff and administrators
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Payroll Dashboard</h1>
            <p class="text-gray-600 mt-2">Manage payroll processing and export attendance records</p>
        </div>

        <!-- Quick Actions -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Export Attendance</div>
                        <div class="text-lg font-semibold text-gray-900">Generate Reports</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Process Payroll</div>
                        <div class="text-lg font-semibold text-gray-900">Calculate Pay</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-500">Payroll Records</div>
                        <div class="text-lg font-semibold text-gray-900">View History</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Export Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export Attendance Records
            </h2>
            
            <form id="export-form" class="grid md:grid-cols-4 gap-4">
                <!-- Date Range -->
                <div>
                    <label for="start-date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" id="start-date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="end-date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" id="end-date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <!-- Employee Filter -->
                <div>
                    <label for="employee-filter" class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                    <select id="employee-filter" name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Employees</option>
                        <?php
                        $employees = get_users(array(
                            'role__in' => array('employee', 'manager', 'accounting_payroll', 'contractor'),
                            'orderby' => 'display_name',
                            'order' => 'ASC'
                        ));
                        foreach ($employees as $employee) {
                            echo '<option value="' . esc_attr($employee->ID) . '">' . esc_html(linkage_get_user_display_name($employee->ID)) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Export Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors duration-200 font-medium">
                        Export CSV
                    </button>
                </div>
            </form>
        </div>

        <!-- Payroll Processing Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                Generate Payroll
            </h2>
            
            <form id="payroll-form" class="grid md:grid-cols-5 gap-4">
                <!-- Pay Period -->
                <div>
                    <label for="period-start" class="block text-sm font-medium text-gray-700 mb-2">Period Start</label>
                    <input type="date" id="period-start" name="period_start" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                
                <div>
                    <label for="period-end" class="block text-sm font-medium text-gray-700 mb-2">Period End</label>
                    <input type="date" id="period-end" name="period_end" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                </div>
                
                <!-- Employee Selection -->
                <div>
                    <label for="payroll-employee" class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                    <select id="payroll-employee" name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="">Select Employee</option>
                        <?php
                        foreach ($employees as $employee) {
                            echo '<option value="' . esc_attr($employee->ID) . '">' . esc_html(linkage_get_user_display_name($employee->ID)) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Overtime Type -->
                <div>
                    <label for="overtime-type" class="block text-sm font-medium text-gray-700 mb-2">Overtime Rate</label>
                    <select id="overtime-type" name="overtime_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="1.5x">1.5x</option>
                        <option value="2x">2x</option>
                    </select>
                </div>
                
                <!-- Generate Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition-colors duration-200 font-medium">
                        Calculate
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Payroll Records -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Recent Payroll Records
            </h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regular Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="payroll-records">
                        <!-- Payroll records will be loaded here via AJAX -->
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                No payroll records found. Generate your first payroll above.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Set default dates (current month)
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    $('#start-date, #period-start').val(firstDay.toISOString().split('T')[0]);
    $('#end-date, #period-end').val(lastDay.toISOString().split('T')[0]);
    
    // Export form handler
    $('#export-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'linkage_export_attendance');
        formData.append('nonce', linkage_ajax.nonce);
        
        // Create download link
        const params = new URLSearchParams(formData);
        window.open(linkage_ajax.ajax_url + '?' + params.toString(), '_blank');
    });
    
    // Payroll form handler
    $('#payroll-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'linkage_generate_payroll');
        formData.append('nonce', linkage_ajax.nonce);
        
        $.ajax({
            url: linkage_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Payroll generated successfully!');
                    loadPayrollRecords();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while processing payroll.');
            }
        });
    });
    
    // Load payroll records
    function loadPayrollRecords() {
        $.ajax({
            url: linkage_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'linkage_get_payroll_records',
                nonce: linkage_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#payroll-records').html(response.data.html);
                }
            }
        });
    }
    
    // Load initial records
    loadPayrollRecords();
});
</script>

<?php get_footer(); ?>
