<?php
/**
 * Template Name: Payroll Dashboard
 * 
 * Payroll dashboard for processing payroll and exporting attendance records
 */

// Security check - Only allow access to payroll staff and administrators
if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('linkage_export_attendance'))) {
    wp_redirect(home_url());
    exit;
}

get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">

        <!-- Employee Search and Filter Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Search employees and view detailed attendance logs for payroll processing
            </h2>
            
            <div class="grid md:grid-cols-12 gap-4 mb-6">
                <!-- Employee Search with Autocomplete -->
                <div class="md:col-span-4">
                    <label for="employee-search" class="block text-sm font-medium text-gray-700 mb-2">Search Employee</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="employee-search" 
                            placeholder="Type employee name..." 
                            class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            autocomplete="off">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <!-- Search suggestions dropdown -->
                        <div id="search-suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 hidden max-h-60 overflow-y-auto">
                            <!-- Suggestions will be populated here -->
                        </div>
                    </div>
                </div>
                
                <!-- Date Range Start -->
                <div class="md:col-span-2">
                    <label for="date-start" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" id="date-start" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <!-- Date Range End -->
                <div class="md:col-span-2">
                    <label for="date-end" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" id="date-end" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <!-- Quick Date Presets -->
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quick Presets</label>
                    <div class="flex space-x-2">
                        <button type="button" id="preset-biweekly" class="px-3 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors text-sm font-medium">
                            Biweekly
                        </button>
                        <button type="button" id="preset-monthly" class="px-3 py-2 bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors text-sm font-medium">
                            Monthly
                        </button>
                    </div>
                </div>
                
                <!-- Search Button -->
                <div class="md:col-span-1 flex items-end">
                    <button type="button" id="search-employees" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors font-medium">
                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Employee List Results -->
        <div class="bg-white rounded-lg shadow-md">
            <!-- Loading State -->
            <div id="loading-state" class="p-8 text-center hidden">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
                <p class="text-gray-600">Loading employee data...</p>
            </div>
            
            <!-- Results Header -->
            <div id="results-header" class="px-6 py-4 border-b border-gray-200 hidden">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Employee Attendance Summary</h3>
                    <div id="results-count" class="text-sm text-gray-500"></div>
                </div>
            </div>
            
            <!-- Employee List Table -->
            <div id="employee-results" class="hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regular Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Worked</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="employee-list" class="bg-white divide-y divide-gray-200">
                            <!-- Employee rows will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Empty State -->
            <div id="empty-state" class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Search for Employees</h3>
                <p class="text-gray-600">Use the search box above to find employees and view their attendance records.</p>
            </div>
        </div>

    </div>
</div>

<!-- Employee Details Modal -->
<div id="employee-details-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 id="modal-employee-name" class="text-xl font-semibold text-gray-900">Employee Details</h3>
            <button id="close-modal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            <!-- Export Buttons -->
            <div class="flex items-center space-x-3 mb-6">
                <button id="export-csv" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition-colors font-medium flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </button>
                <button id="export-xlsx" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors font-medium flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export Excel
                </button>
                <div class="text-sm text-gray-600" id="modal-date-range"></div>
            </div>
            
            <!-- Detailed Logs Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lunch Start</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lunch End</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody id="detailed-logs" class="bg-white divide-y divide-gray-200">
                        <!-- Detailed logs will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
        
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Set default dates to current month
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    $('#date-start').val(firstDay.toISOString().split('T')[0]);
    $('#date-end').val(lastDay.toISOString().split('T')[0]);
    
    let searchTimeout;
    let currentEmployeeId = null;
    let currentDateRange = null;
    
    // Employee search with autocomplete
    $('#employee-search').on('input', function() {
        const query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            $('#search-suggestions').addClass('hidden');
            return;
        }
        
        searchTimeout = setTimeout(function() {
            searchEmployees(query);
        }, 300);
    });
    
    // Date preset handlers
    $('#preset-biweekly').on('click', function() {
        const today = new Date();
        const dayOfWeek = today.getDay();
        const daysToSubtract = (dayOfWeek === 0 ? 6 : dayOfWeek - 1); // Monday = 0
        const startDate = new Date(today);
        startDate.setDate(today.getDate() - daysToSubtract - 7); // Previous Monday
        
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 13); // Two weeks
        
        $('#date-start').val(startDate.toISOString().split('T')[0]);
        $('#date-end').val(endDate.toISOString().split('T')[0]);
    });
    
    $('#preset-monthly').on('click', function() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        $('#date-start').val(firstDay.toISOString().split('T')[0]);
        $('#date-end').val(lastDay.toISOString().split('T')[0]);
    });
    
    // Search employees button
    $('#search-employees').on('click', function() {
        performEmployeeSearch();
    });
    
    // Enter key on search field
    $('#employee-search').on('keypress', function(e) {
        if (e.which === 13) {
            performEmployeeSearch();
        }
    });
    
    // Hide suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#employee-search, #search-suggestions').length) {
            $('#search-suggestions').addClass('hidden');
        }
    });
    
    // Modal handlers
    $('#close-modal').on('click', function() {
        $('#employee-details-modal').addClass('hidden');
    });
    
    // Export handlers
    $('#export-csv').on('click', function() {
        exportEmployeeData('csv');
    });
    
    $('#export-xlsx').on('click', function() {
        exportEmployeeData('xlsx');
    });
    

    
    // Functions
    function searchEmployees(query) {
        $.ajax({
            url: linkage_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'linkage_search_employees',
                query: query,
                nonce: linkage_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displaySuggestions(response.data);
                }
            }
        });
    }
    
    function displaySuggestions(employees) {
        const $suggestions = $('#search-suggestions');
        $suggestions.empty();
        
        if (employees.length === 0) {
            $suggestions.addClass('hidden');
            return;
        }
        
        employees.forEach(function(employee) {
            const $item = $(`
                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0" data-employee-id="${employee.ID}">
                    <div class="font-medium text-gray-900">${employee.display_name}</div>
                    <div class="text-sm text-gray-600">${employee.position || 'No position set'}</div>
                </div>
            `);
            
            $item.on('click', function() {
                selectEmployee(employee);
            });
            
            $suggestions.append($item);
        });
        
        $suggestions.removeClass('hidden');
    }
    
    function selectEmployee(employee) {
        $('#employee-search').val(employee.display_name);
        $('#search-suggestions').addClass('hidden');
        // Auto-trigger search when employee is selected
        setTimeout(performEmployeeSearch, 100);
    }
    
    function performEmployeeSearch() {
        const query = $('#employee-search').val().trim();
        const startDate = $('#date-start').val();
        const endDate = $('#date-end').val();
        
        if (!startDate || !endDate) {
            alert('Please select both start and end dates.');
            return;
        }
        
        if (startDate > endDate) {
            alert('Start date cannot be after end date.');
            return;
        }
        
        showLoading();
        
        $.ajax({
            url: linkage_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'linkage_get_employee_attendance',
                query: query,
                start_date: startDate,
                end_date: endDate,
                nonce: linkage_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displayEmployeeResults(response.data);
                } else {
                    showError(response.data || 'An error occurred while searching.');
                }
            },
            error: function() {
                hideLoading();
                showError('Network error occurred.');
            }
        });
    }
    
    function showLoading() {
        $('#empty-state').addClass('hidden');
        $('#employee-results').addClass('hidden');
        $('#results-header').addClass('hidden');
        $('#loading-state').removeClass('hidden');
    }
    
    function hideLoading() {
        $('#loading-state').addClass('hidden');
    }
    
    function displayEmployeeResults(data) {
        const $employeeList = $('#employee-list');
        $employeeList.empty();
        
        if (data.employees.length === 0) {
            $('#employee-results').addClass('hidden');
            $('#results-header').addClass('hidden');
            $('#empty-state').removeClass('hidden');
            return;
        }
        
        data.employees.forEach(function(employee) {
            const $row = $(`
                <tr class="hover:bg-gray-50 cursor-pointer" data-employee-id="${employee.user_id}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                    <span class="text-sm font-medium text-white">${employee.name.substring(0, 2).toUpperCase()}</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${employee.name}</div>
                                <div class="text-sm text-gray-500">${employee.email}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${employee.position || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${employee.total_hours}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${employee.regular_hours}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${employee.overtime_hours}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${employee.days_worked}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button class="text-blue-600 hover:text-blue-900 view-details-btn" data-employee-id="${employee.user_id}">
                            View Details
                        </button>
                    </td>
                </tr>
            `);
            
            $employeeList.append($row);
        });
        
        // Bind click events
        $('.view-details-btn').on('click', function(e) {
            e.stopPropagation();
            const employeeId = $(this).data('employee-id');
            viewEmployeeDetails(employeeId);
        });
        

        
        $employeeList.find('tr').on('click', function() {
            const employeeId = $(this).data('employee-id');
            viewEmployeeDetails(employeeId);
        });
        
        $('#results-count').text(`Found ${data.employees.length} employee(s)`);
        $('#empty-state').addClass('hidden');
        $('#results-header').removeClass('hidden');
        $('#employee-results').removeClass('hidden');
    }
    
    function viewEmployeeDetails(employeeId) {
        currentEmployeeId = employeeId;
        currentDateRange = {
            start: $('#date-start').val(),
            end: $('#date-end').val()
        };
        
        $.ajax({
            url: linkage_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'linkage_get_employee_detailed_logs',
                employee_id: employeeId,
                start_date: currentDateRange.start,
                end_date: currentDateRange.end,
                nonce: linkage_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayDetailedLogs(response.data);
                } else {
                    alert('Error loading detailed logs: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Network error occurred while loading detailed logs.');
            }
        });
    }
    
    function displayDetailedLogs(data) {
        $('#modal-employee-name').text(data.employee_name + ' - Detailed Attendance Logs');
        $('#modal-date-range').text(`${currentDateRange.start} to ${currentDateRange.end}`);
        
        const $detailedLogs = $('#detailed-logs');
        $detailedLogs.empty();
        
        if (data.logs.length === 0) {
            $detailedLogs.append(`
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        No attendance records found for this period.
                    </td>
                </tr>
            `);
        } else {
            data.logs.forEach(function(log) {
                $detailedLogs.append(`
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.work_date}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.time_in || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.lunch_start || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.lunch_end || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.time_out || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${log.total_hours}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">${log.notes || '-'}</td>
                    </tr>
                `);
            });
        }
        
        $('#employee-details-modal').removeClass('hidden');
    }
    
    function exportEmployeeData(format) {
        if (!currentEmployeeId || !currentDateRange) {
            alert('No employee data to export.');
            return;
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = linkage_ajax.ajax_url;
        form.target = '_blank';
        
        const fields = {
            action: 'linkage_export_employee_attendance',
            employee_id: currentEmployeeId,
            start_date: currentDateRange.start,
            end_date: currentDateRange.end,
            format: format,
            nonce: linkage_ajax.nonce
        };
        
        Object.keys(fields).forEach(function(key) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    

    
    function showError(message) {
        alert('Error: ' + message);
    }
});
</script>

<?php get_footer(); ?>
