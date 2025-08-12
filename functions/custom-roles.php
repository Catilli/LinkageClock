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
            'linkage_clock_in_out' => true,
            'linkage_take_break' => true,
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
            'linkage_clock_in_out' => true,
            'linkage_take_break' => true,
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
        $employee_role->add_cap('linkage_clock_in_out');
        $employee_role->add_cap('linkage_take_break');
    }

    // Add custom capabilities to hr_manager role
    if ($hr_manager_role) {
        $hr_manager_role->add_cap('linkage_submit_timesheet');
        $hr_manager_role->add_cap('linkage_view_own_timesheet');
        $hr_manager_role->add_cap('linkage_view_all_timesheets');
        $hr_manager_role->add_cap('linkage_approve_timesheets');
        $hr_manager_role->add_cap('linkage_manage_employees');
        $hr_manager_role->add_cap('linkage_clock_in_out');
        $hr_manager_role->add_cap('linkage_take_break');
    }

    // Add custom capabilities to administrator role
    if ($administrator_role) {
        $administrator_role->add_cap('linkage_submit_timesheet');
        $administrator_role->add_cap('linkage_view_own_timesheet');
        $administrator_role->add_cap('linkage_view_all_timesheets');
        $administrator_role->add_cap('linkage_approve_timesheets');
        $administrator_role->add_cap('linkage_manage_employees');
        $administrator_role->add_cap('linkage_clock_in_out');
        $administrator_role->add_cap('linkage_take_break');
    }
}
add_action('after_switch_theme', 'linkage_add_custom_capabilities');

/**
 * Ensure all users have necessary clock capabilities
 * This function runs when users log in to ensure they have the required permissions
 */
function linkage_ensure_user_capabilities($user_login, $user) {
    // Add clock capabilities to all users if they don't have them
    if (!user_can($user->ID, 'linkage_clock_in_out')) {
        $user->add_cap('linkage_clock_in_out');
    }
    
    if (!user_can($user->ID, 'linkage_take_break')) {
        $user->add_cap('linkage_take_break');
    }
    
    // Ensure administrator has all capabilities
    if (in_array('administrator', $user->roles)) {
        $user->add_cap('linkage_submit_timesheet');
        $user->add_cap('linkage_view_own_timesheet');
        $user->add_cap('linkage_view_all_timesheets');
        $user->add_cap('linkage_approve_timesheets');
        $user->add_cap('linkage_manage_employees');
        $user->add_cap('linkage_clock_in_out');
        $user->add_cap('linkage_take_break');
    }
}
add_action('wp_login', 'linkage_ensure_user_capabilities', 10, 2);

/**
 * Check and fix user capabilities on every page load
 * This ensures capabilities are always available for logged-in users
 */
function linkage_check_user_capabilities_on_load() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // Add clock capabilities to all users if they don't have them
    if (!user_can($current_user->ID, 'linkage_clock_in_out')) {
        $current_user->add_cap('linkage_clock_in_out');
    }
    
    if (!user_can($current_user->ID, 'linkage_take_break')) {
        $current_user->add_cap('linkage_take_break');
    }
    
    // Ensure administrator has all capabilities
    if (in_array('administrator', $current_user->roles)) {
        $capabilities = array(
            'linkage_submit_timesheet',
            'linkage_view_own_timesheet',
            'linkage_view_all_timesheets',
            'linkage_approve_timesheets',
            'linkage_manage_employees',
            'linkage_clock_in_out',
            'linkage_take_break'
        );
        
        foreach ($capabilities as $cap) {
            if (!user_can($current_user->ID, $cap)) {
                $current_user->add_cap($cap);
            }
        }
    }
}
add_action('init', 'linkage_check_user_capabilities_on_load');

/**
 * Update existing users with new capabilities
 * This function can be called manually to update all existing users
 */
function linkage_update_existing_users_capabilities() {
    $users = get_users();
    $updated_count = 0;
    
    foreach ($users as $user) {
        $user_obj = get_user_by('ID', $user->ID);
        
        // Add clock capabilities to all users
        if (!user_can($user->ID, 'linkage_clock_in_out')) {
            $user_obj->add_cap('linkage_clock_in_out');
            $updated_count++;
        }
        
        if (!user_can($user->ID, 'linkage_take_break')) {
            $user_obj->add_cap('linkage_take_break');
            $updated_count++;
        }
        
        // Ensure administrator has all capabilities
        if (in_array('administrator', $user->roles)) {
            $capabilities = array(
                'linkage_submit_timesheet',
                'linkage_view_own_timesheet',
                'linkage_view_all_timesheets',
                'linkage_approve_timesheets',
                'linkage_manage_employees',
                'linkage_clock_in_out',
                'linkage_take_break'
            );
            
            foreach ($capabilities as $cap) {
                if (!user_can($user->ID, $cap)) {
                    $user_obj->add_cap($cap);
                    $updated_count++;
                }
            }
        }
    }
    
    return $updated_count;
}

/**
 * Manual function to check and fix current user capabilities
 * This can be called to ensure the current user has all necessary capabilities
 */
function linkage_fix_current_user_capabilities() {
    if (!is_user_logged_in()) {
        return 'No user logged in';
    }
    
    $current_user = wp_get_current_user();
    $fixed_count = 0;
    
    // Add clock capabilities
    if (!user_can($current_user->ID, 'linkage_clock_in_out')) {
        $current_user->add_cap('linkage_clock_in_out');
        $fixed_count++;
    }
    
    if (!user_can($current_user->ID, 'linkage_take_break')) {
        $current_user->add_cap('linkage_take_break');
        $fixed_count++;
    }
    
    // Ensure administrator has all capabilities
    if (in_array('administrator', $current_user->roles)) {
        $capabilities = array(
            'linkage_submit_timesheet',
            'linkage_view_own_timesheet',
            'linkage_view_all_timesheets',
            'linkage_approve_timesheets',
            'linkage_manage_employees',
            'linkage_clock_in_out',
            'linkage_take_break'
        );
        
        foreach ($capabilities as $cap) {
            if (!user_can($current_user->ID, $cap)) {
                $current_user->add_cap($cap);
                $fixed_count++;
            }
        }
    }
    
    return "Fixed $fixed_count capabilities for user: " . $current_user->display_name;
}

/**
 * Restrict WordPress admin access to administrators only
 * All other roles will be redirected to the frontend
 */
function linkage_restrict_admin_access() {
    // Only apply this restriction if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    // Get current user
    $current_user = wp_get_current_user();
    
    // Check if user is trying to access admin area
    if (is_admin() && !wp_doing_ajax()) {
        // Allow administrators to access admin
        if (in_array('administrator', $current_user->roles)) {
            return;
        }
        
        // Redirect all other roles to frontend
        wp_redirect(home_url('/'));
        exit;
    }
}
add_action('init', 'linkage_restrict_admin_access');

/**
 * Hide admin bar for all users (including administrators)
 * This ensures a clean frontend experience for everyone
 */
function linkage_hide_admin_bar_for_all_users() {
    if (!is_user_logged_in()) {
        return;
    }
    
    // Hide admin bar for all users to maintain clean frontend
    show_admin_bar(false);
}
add_action('after_setup_theme', 'linkage_hide_admin_bar_for_all_users');

/**
 * Redirect non-administrators away from admin pages
 */
function linkage_redirect_non_admin_users() {
    // Only apply this restriction if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    // Get current user
    $current_user = wp_get_current_user();
    
    // Check if user is trying to access admin area
    if (is_admin() && !wp_doing_ajax()) {
        // Allow administrators to access admin
        if (in_array('administrator', $current_user->roles)) {
            return;
        }
        
        // Redirect all other roles to frontend
        wp_redirect(home_url('/'));
        exit;
    }
}
add_action('admin_init', 'linkage_redirect_non_admin_users');

/**
 * Display admin restriction message on frontend
 */
function linkage_display_admin_restriction_message() {
    if (isset($_GET['message']) && $_GET['message'] === 'admin_restricted') {
        echo '<div class="admin-restriction-notice bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">';
        echo '<div class="flex">';
        echo '<div class="flex-shrink-0">';
        echo '<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">';
        echo '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />';
        echo '</svg>';
        echo '</div>';
        echo '<div class="ml-3">';
        echo '<p class="text-sm font-medium">Access Restricted</p>';
        echo '<p class="text-sm">Only administrators can access the WordPress admin area. You have been redirected to the frontend.</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}
add_action('wp_body_open', 'linkage_display_admin_restriction_message');

/**
 * Remove admin menu items for non-administrators
 */
function linkage_remove_admin_menu_items() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // Only remove menu items for non-administrators
    if (!in_array('administrator', $current_user->roles)) {
        // Remove admin menu items
        remove_menu_page('index.php'); // Dashboard
        remove_menu_page('edit.php'); // Posts
        remove_menu_page('upload.php'); // Media
        remove_menu_page('edit.php?post_type=page'); // Pages
        remove_menu_page('edit-comments.php'); // Comments
        remove_menu_page('themes.php'); // Appearance
        remove_menu_page('plugins.php'); // Plugins
        remove_menu_page('users.php'); // Users
        remove_menu_page('tools.php'); // Tools
        remove_menu_page('options-general.php'); // Settings
        
        // Remove submenu items
        remove_submenu_page('index.php', 'index.php');
        remove_submenu_page('index.php', 'update-core.php');
    }
}
add_action('admin_menu', 'linkage_remove_admin_menu_items', 999);

/**
 * Customize admin footer for non-administrators
 */
function linkage_customize_admin_footer() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // Only customize footer for non-administrators
    if (!in_array('administrator', $current_user->roles)) {
        echo '<p id="footer-left" class="alignleft">';
        echo 'Access restricted. Contact an administrator for assistance.';
        echo '</p>';
    }
}
add_action('admin_footer_text', 'linkage_customize_admin_footer');

/**
 * Prevent non-administrators from accessing admin-ajax.php for admin functions
 */
function linkage_restrict_admin_ajax_access() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // Only restrict if not administrator
    if (!in_array('administrator', $current_user->roles)) {
        // List of admin-only AJAX actions
        $admin_only_actions = array(
            'linkage_export_attendance', // Export functionality
            'linkage_update_employee_status', // Employee management
            'linkage_force_create_tables', // Database management
            'linkage_force_initialize_all_users' // User initialization
        );
        
        // Check if current AJAX action is admin-only
        if (isset($_POST['action']) && in_array($_POST['action'], $admin_only_actions)) {
            wp_die('Access denied. Administrator privileges required.');
        }
    }
}
add_action('wp_ajax_linkage_export_attendance', 'linkage_restrict_admin_ajax_access', 1);
add_action('wp_ajax_linkage_update_employee_status', 'linkage_restrict_admin_ajax_access', 1);
add_action('wp_ajax_linkage_force_create_tables', 'linkage_restrict_admin_ajax_access', 1);
add_action('wp_ajax_linkage_force_initialize_all_users', 'linkage_restrict_admin_ajax_access', 1);

/**
 * Secure login redirect for non-administrators
 */
function linkage_secure_login_redirect($redirect_to, $requested_redirect_to, $user) {
    // If no user, return default redirect
    if (!isset($user->ID)) {
        return $redirect_to;
    }
    
    // Get user roles
    $user_roles = $user->roles;
    
    // If user is administrator, redirect to WordPress admin
    if (in_array('administrator', $user_roles)) {
        return admin_url();
    }
    
    // For all other roles, redirect to frontend
    return home_url('/');
}
add_filter('login_redirect', 'linkage_secure_login_redirect', 10, 3);

/**
 * Prevent non-administrators from accessing wp-admin directly via URL
 */
function linkage_block_admin_url_access() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // Only block if not administrator
    if (!in_array('administrator', $current_user->roles)) {
        // Check if trying to access wp-admin URLs
        $current_url = $_SERVER['REQUEST_URI'];
        if (strpos($current_url, '/wp-admin/') !== false || $current_url === '/wp-admin') {
            wp_redirect(home_url('/'));
            exit;
        }
    }
}
add_action('template_redirect', 'linkage_block_admin_url_access');

/**
 * Add custom CSS for admin restriction notice
 */
function linkage_admin_restriction_styles() {
    if (isset($_GET['message']) && $_GET['message'] === 'admin_restricted') {
        echo '<style>
            .admin-restriction-notice {
                position: relative;
                margin: 1rem 0;
                border-radius: 0.375rem;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            }
            .admin-restriction-notice .flex {
                align-items: flex-start;
            }
            .admin-restriction-notice svg {
                margin-top: 0.125rem;
            }
        </style>';
    }
}
add_action('wp_head', 'linkage_admin_restriction_styles');

/**
 * Ensure ALL users have clock capabilities (time tracking is essential)
 * This function runs on every page load to guarantee clock functionality works
 */
function linkage_ensure_all_users_have_clock_capabilities() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // EVERY user needs these basic capabilities for time tracking
    $essential_capabilities = array(
        'linkage_clock_in_out',  // Clock in/out
        'linkage_take_break'     // Take breaks
    );
    
    $added_count = 0;
    foreach ($essential_capabilities as $cap) {
        if (!user_can($current_user->ID, $cap)) {
            $current_user->add_cap($cap);
            $added_count++;
        }
    }
    
    // If capabilities were added, log it for debugging
    if ($added_count > 0) {
        error_log("LinkageClock: Added $added_count missing capabilities for user {$current_user->display_name} (ID: {$current_user->ID})");
    }
    
    // Also ensure role-based capabilities are present
    if (in_array('administrator', $current_user->roles)) {
        $admin_capabilities = array(
            'linkage_submit_timesheet',
            'linkage_view_own_timesheet',
            'linkage_view_all_timesheets',
            'linkage_approve_timesheets',
            'linkage_manage_employees'
        );
        
        foreach ($admin_capabilities as $cap) {
            if (!user_can($current_user->ID, $cap)) {
                $current_user->add_cap($cap);
            }
        }
    }
    
    if (in_array('hr_manager', $current_user->roles)) {
        $hr_capabilities = array(
            'linkage_submit_timesheet',
            'linkage_view_own_timesheet',
            'linkage_view_all_timesheets',
            'linkage_approve_timesheets',
            'linkage_manage_employees'
        );
        
        foreach ($hr_capabilities as $cap) {
            if (!user_can($current_user->ID, $cap)) {
                $current_user->add_cap($cap);
            }
        }
    }
    
    if (in_array('employee', $current_user->roles)) {
        $employee_capabilities = array(
            'linkage_submit_timesheet',
            'linkage_view_own_timesheet'
        );
        
        foreach ($employee_capabilities as $cap) {
            if (!user_can($current_user->ID, $cap)) {
                $current_user->add_cap($cap);
            }
        }
    }
}
add_action('init', 'linkage_ensure_all_users_have_clock_capabilities', 5); // Run early

/**
 * Force update ALL existing users with clock capabilities
 * This ensures every user can perform time tracking actions
 */
function linkage_force_update_all_users_capabilities() {
    $users = get_users();
    $updated_count = 0;
    
    foreach ($users as $user) {
        $user_obj = get_user_by('ID', $user->ID);
        $user_updated = false;
        
        // EVERY user needs these basic capabilities for time tracking
        $essential_capabilities = array(
            'linkage_clock_in_out',  // Clock in/out
            'linkage_take_break'     // Take breaks
        );
        
        foreach ($essential_capabilities as $cap) {
            if (!user_can($user->ID, $cap)) {
                $user_obj->add_cap($cap);
                $user_updated = true;
            }
        }
        
        // Add role-specific capabilities
        if (in_array('administrator', $user->roles)) {
            $admin_capabilities = array(
                'linkage_submit_timesheet',
                'linkage_view_own_timesheet',
                'linkage_view_all_timesheets',
                'linkage_approve_timesheets',
                'linkage_manage_employees'
            );
            
            foreach ($admin_capabilities as $cap) {
                if (!user_can($user->ID, $cap)) {
                    $user_obj->add_cap($cap);
                    $user_updated = true;
                }
            }
        }
        
        if (in_array('hr_manager', $user->roles)) {
            $hr_capabilities = array(
                'linkage_submit_timesheet',
                'linkage_view_own_timesheet',
                'linkage_view_all_timesheets',
                'linkage_approve_timesheets',
                'linkage_manage_employees'
            );
            
            foreach ($hr_capabilities as $cap) {
                if (!user_can($user->ID, $cap)) {
                    $user_obj->add_cap($cap);
                    $user_updated = true;
                }
            }
        }
        
        if (in_array('employee', $user->roles)) {
            $employee_capabilities = array(
                'linkage_submit_timesheet',
                'linkage_view_own_timesheet'
            );
            
            foreach ($employee_capabilities as $cap) {
                if (!user_can($user->ID, $cap)) {
                    $user_obj->add_cap($cap);
                    $user_updated = true;
                }
            }
        }
        
        if ($user_updated) {
            $updated_count++;
        }
    }
    
    return $updated_count;
}
