<?php
/**
 * Create custom roles on theme activation
 */
function linkage_create_custom_roles() {
    // Add Employee role
    add_role(
        'employee',
        'Employee',
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => true,
            'linkage_submit_timesheet' => true,
            'linkage_view_own_timesheet' => true,
        )
    );

    // Add HR Manager role
    add_role(
        'hr_manager',
        'HR Manager',
        array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'upload_files' => true,
            'manage_options' => false,
            'linkage_submit_timesheet' => true,
            'linkage_view_own_timesheet' => true,
            'linkage_view_all_timesheets' => true,
            'linkage_approve_timesheets' => true,
            'linkage_manage_employees' => true,
        )
    );
}
add_action('after_switch_theme', 'linkage_create_custom_roles');

/**
 * Add custom capabilities
 */
function linkage_add_custom_capabilities() {
    // Get roles
    $employee_role = get_role('employee');
    $hr_manager_role = get_role('hr_manager');
    $administrator_role = get_role('administrator');

    // Add custom capabilities to employee role
    if ($employee_role) {
        $employee_role->add_cap('linkage_submit_timesheet');
        $employee_role->add_cap('linkage_view_own_timesheet');
    }

    // Add custom capabilities to hr_manager role
    if ($hr_manager_role) {
        $hr_manager_role->add_cap('linkage_submit_timesheet');
        $hr_manager_role->add_cap('linkage_view_own_timesheet');
        $hr_manager_role->add_cap('linkage_view_all_timesheets');
        $hr_manager_role->add_cap('linkage_approve_timesheets');
        $hr_manager_role->add_cap('linkage_manage_employees');
    }

    // Add custom capabilities to administrator role
    if ($administrator_role) {
        $administrator_role->add_cap('linkage_submit_timesheet');
        $administrator_role->add_cap('linkage_view_own_timesheet');
        $administrator_role->add_cap('linkage_view_all_timesheets');
        $administrator_role->add_cap('linkage_approve_timesheets');
        $administrator_role->add_cap('linkage_manage_employees');
    }
}
add_action('after_switch_theme', 'linkage_add_custom_capabilities');
