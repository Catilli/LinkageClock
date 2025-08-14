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
            'linkage_view_own_profile' => true,
            'linkage_view_own_dashboard_status' => true,
            'linkage_view_own_timesheet' => true,
        )
    );

    // Add Manager role
    add_role(
        'manager',
        'Manager',
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => true,
            'manage_options' => false,
            'linkage_view_main_dashboard' => true,
            'linkage_manage_supervised_logs' => true,
            'linkage_run_reports' => true,
            'linkage_request_corrections' => true,
            'linkage_view_own_timesheet' => true,
        )
    );

    // Add Accounting | Payroll role
    add_role(
        'accounting_payroll',
        'Accounting | Payroll',
        array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
            'manage_options' => false,
            'list_users' => true,
            'edit_users' => true,
            'create_users' => true,
            'delete_users' => true,
            'linkage_view_main_dashboard' => true,
            'linkage_export_attendance' => true,
            'linkage_view_all_attendance' => true,
            'linkage_filter_by_date' => true,
            'linkage_generate_payroll_reports' => true,
            'linkage_view_own_timesheet' => true,
        )
    );

    // Add Contractors role
    add_role(
        'contractor',
        'Contractors',
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => true,
            'manage_options' => false,
            'linkage_view_own_profile' => true,
            'linkage_view_own_dashboard_status' => true,
            'linkage_view_own_timesheet' => true,
        )
    );
}
add_action('after_switch_theme', 'linkage_create_custom_roles');

/**
 * Remove the old hr_manager role (users already manually deleted)
 */
function linkage_cleanup_old_hr_manager_role() {
    // Remove the old hr_manager role since users were manually deleted
    remove_role('hr_manager');
}
add_action('after_switch_theme', 'linkage_cleanup_old_hr_manager_role');

/**
 * Add custom capabilities
 */
function linkage_add_custom_capabilities() {
    // Get roles
    $employee_role = get_role('employee');
    $manager_role = get_role('manager');
    $accounting_role = get_role('accounting_payroll');
    $contractor_role = get_role('contractor');
    $administrator_role = get_role('administrator');

    // Add custom capabilities to employee role
    if ($employee_role) {
        $employee_role->add_cap('linkage_view_own_profile');
        $employee_role->add_cap('linkage_view_own_dashboard_status');
        $employee_role->add_cap('linkage_view_own_timesheet');
    }

    // Add custom capabilities to manager role
    if ($manager_role) {
        $manager_role->add_cap('linkage_view_main_dashboard');
        $manager_role->add_cap('linkage_manage_supervised_logs');
        $manager_role->add_cap('linkage_run_reports');
        $manager_role->add_cap('linkage_request_corrections');
        $manager_role->add_cap('linkage_view_own_timesheet');
    }

    // Add custom capabilities to accounting/payroll role
    if ($accounting_role) {
        $accounting_role->add_cap('linkage_view_main_dashboard');
        $accounting_role->add_cap('linkage_export_attendance');
        $accounting_role->add_cap('linkage_view_all_attendance');
        $accounting_role->add_cap('linkage_filter_by_date');
        $accounting_role->add_cap('linkage_generate_payroll_reports');
        $accounting_role->add_cap('linkage_view_own_timesheet');
        // WordPress core capabilities for Posts and Users access
        $accounting_role->add_cap('edit_posts');
        $accounting_role->add_cap('delete_posts');
        $accounting_role->add_cap('publish_posts');
        $accounting_role->add_cap('list_users');
        $accounting_role->add_cap('edit_users');
        $accounting_role->add_cap('create_users');
        $accounting_role->add_cap('delete_users');
    }

    // Add custom capabilities to contractor role
    if ($contractor_role) {
        $contractor_role->add_cap('linkage_view_own_profile');
        $contractor_role->add_cap('linkage_view_own_dashboard_status');
        $contractor_role->add_cap('linkage_view_own_timesheet');
    }

    // Add custom capabilities to administrator role (full access)
    if ($administrator_role) {
        $administrator_role->add_cap('linkage_full_access');
        $administrator_role->add_cap('linkage_manage_all_users');
        $administrator_role->add_cap('linkage_view_main_dashboard');
        $administrator_role->add_cap('linkage_manual_corrections');
        $administrator_role->add_cap('linkage_export_attendance');
        $administrator_role->add_cap('linkage_view_all_attendance');
        $administrator_role->add_cap('linkage_run_reports');
        $administrator_role->add_cap('linkage_view_own_timesheet');
    }
}
add_action('after_switch_theme', 'linkage_add_custom_capabilities');

/**
 * Ensure all users have necessary capabilities
 * This function runs when users log in to ensure they have the required permissions
 */
function linkage_ensure_user_capabilities($user_login, $user) {
    // Ensure administrator has all capabilities
    if (in_array('administrator', $user->roles)) {
        $user->add_cap('linkage_submit_timesheet');
        $user->add_cap('linkage_view_own_timesheet');
        $user->add_cap('linkage_view_all_timesheets');
        $user->add_cap('linkage_approve_timesheets');
        $user->add_cap('linkage_manage_employees');
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
    
    // Ensure administrator has all capabilities
    if (in_array('administrator', $current_user->roles)) {
        $capabilities = array(
            'linkage_submit_timesheet',
            'linkage_view_own_timesheet',
            'linkage_view_all_timesheets',
            'linkage_approve_timesheets',
            'linkage_manage_employees'
        );
        
        foreach ($capabilities as $cap) {
            if (!user_can($current_user->ID, $cap)) {
                $current_user->add_cap($cap);
            }
        }
    }
    
    // Ensure payroll users have Posts and Users access
    if (in_array('accounting_payroll', $current_user->roles)) {
        $payroll_capabilities = array(
            'edit_posts',
            'delete_posts',
            'publish_posts',
            'list_users',
            'edit_users',
            'create_users',
            'delete_users'
        );
        
        foreach ($payroll_capabilities as $cap) {
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
        
        // Ensure administrator has all capabilities
        if (in_array('administrator', $user->roles)) {
            $capabilities = array(
                'linkage_submit_timesheet',
                'linkage_view_own_timesheet',
                'linkage_view_all_timesheets',
                'linkage_approve_timesheets',
                'linkage_manage_employees'
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
    
    // Ensure administrator has all capabilities
    if (in_array('administrator', $current_user->roles)) {
        $capabilities = array(
            'linkage_submit_timesheet',
            'linkage_view_own_timesheet',
            'linkage_view_all_timesheets',
            'linkage_approve_timesheets',
            'linkage_manage_employees'
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
 * Restrict WordPress admin access to administrators and payroll users only
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
        // Allow administrators and payroll users to access admin
        if (in_array('administrator', $current_user->roles) || in_array('accounting_payroll', $current_user->roles)) {
            return;
        }
        
        // Redirect all other roles to frontend
        wp_redirect(home_url('/'));
        exit;
    }
}
add_action('init', 'linkage_restrict_admin_access');

/**
 * Show admin bar for administrators and payroll users only
 * Hide admin bar for all other roles to maintain clean frontend
 */
function linkage_manage_admin_bar_visibility() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // Show admin bar for administrators and payroll users
    if (in_array('administrator', $current_user->roles) || in_array('accounting_payroll', $current_user->roles)) {
        show_admin_bar(true);
    } else {
        // Hide admin bar for all other roles to maintain clean frontend
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'linkage_manage_admin_bar_visibility');

/**
 * Redirect non-administrators and non-payroll users away from admin pages
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
        // Allow administrators and payroll users to access admin
        if (in_array('administrator', $current_user->roles) || in_array('accounting_payroll', $current_user->roles)) {
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
 * Remove admin menu items for payroll users (administrators get full access)
 */
function linkage_remove_admin_menu_items() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // Administrators get full access to everything
    if (in_array('administrator', $current_user->roles)) {
        return;
    }
    
    // For payroll users, remove restricted menu items
    if (in_array('accounting_payroll', $current_user->roles)) {
        // Remove restricted menu items for payroll users
        remove_menu_page('edit.php?post_type=page'); // Pages
        remove_menu_page('themes.php'); // Appearance
        remove_menu_page('plugins.php'); // Plugins
        remove_menu_page('tools.php'); // Tools
        remove_menu_page('options-general.php'); // Settings
        
        // Remove submenu items under Settings
        remove_submenu_page('options-general.php', 'options-general.php');
        remove_submenu_page('options-general.php', 'options-writing.php');
        remove_submenu_page('options-general.php', 'options-reading.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');
        remove_submenu_page('options-general.php', 'options-media.php');
        remove_submenu_page('options-general.php', 'options-permalink.php');
        
        return;
    }
    
    // For all other non-administrator roles, remove all menu items
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
    
    // Administrators get default footer
    if (in_array('administrator', $current_user->roles)) {
        return;
    }
    
    // Payroll users get a custom footer
    if (in_array('accounting_payroll', $current_user->roles)) {
        echo '<p id="footer-left" class="alignleft">';
        echo 'LinkageClock Payroll Dashboard - Limited admin access granted.';
        echo '</p>';
        return;
    }
    
    // All other roles get restricted message
    if (!in_array('administrator', $current_user->roles)) {
        echo '<p id="footer-left" class="alignleft">';
        echo 'Access restricted. Contact an administrator for assistance.';
        echo '</p>';
    }
}
add_action('admin_footer_text', 'linkage_customize_admin_footer');

/**
 * Prevent non-administrators and non-payroll users from accessing admin-ajax.php for admin functions
 */
function linkage_restrict_admin_ajax_access() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // Allow administrators and payroll users access to admin AJAX functions
    if (in_array('administrator', $current_user->roles) || in_array('accounting_payroll', $current_user->roles)) {
        return;
    }
    
    // List of admin-only AJAX actions
    $admin_only_actions = array(
        'linkage_export_attendance', // Export functionality
        'linkage_force_create_tables', // Database management
        'linkage_force_initialize_all_users' // User initialization
    );
    
    // Check if current AJAX action is admin-only
    if (isset($_POST['action']) && in_array($_POST['action'], $admin_only_actions)) {
        wp_die('Access denied. Administrator or payroll privileges required.');
    }
}
add_action('wp_ajax_linkage_export_attendance', 'linkage_restrict_admin_ajax_access', 1);
add_action('wp_ajax_linkage_force_create_tables', 'linkage_restrict_admin_ajax_access', 1);
add_action('wp_ajax_linkage_force_initialize_all_users', 'linkage_restrict_admin_ajax_access', 1);

/**
 * Secure login redirect for users
 */
function linkage_secure_login_redirect($redirect_to, $requested_redirect_to, $user) {
    // If no user, return default redirect
    if (!isset($user->ID)) {
        return $redirect_to;
    }
    
    // Get user roles
    $user_roles = $user->roles;
    
    // If user is administrator or payroll user, redirect to WordPress admin
    if (in_array('administrator', $user_roles) || in_array('accounting_payroll', $user_roles)) {
        return admin_url();
    }
    
    // For all other roles, redirect to frontend
    return home_url('/');
}
add_filter('login_redirect', 'linkage_secure_login_redirect', 10, 3);

/**
 * Prevent non-administrators and non-payroll users from accessing wp-admin directly via URL
 */
function linkage_block_admin_url_access() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    
    // Allow administrators and payroll users to access wp-admin
    if (in_array('administrator', $current_user->roles) || in_array('accounting_payroll', $current_user->roles)) {
        return;
    }
    
    // Block all other roles from accessing wp-admin URLs
    $current_url = $_SERVER['REQUEST_URI'];
    if (strpos($current_url, '/wp-admin/') !== false || $current_url === '/wp-admin') {
        wp_redirect(home_url('/'));
        exit;
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
 * Hide administrator users from Users list for payroll users
 */
function linkage_hide_admin_users_from_payroll($query) {
    // Only apply in admin area
    if (!is_admin()) {
        return;
    }
    
    // Only apply to user queries
    if (!isset($query->query_vars['role']) && !isset($query->query_vars['meta_key'])) {
        // Check if this is a user query by looking at the query object
        global $pagenow;
        if ($pagenow !== 'users.php') {
            return;
        }
    }
    
    // Get current user
    $current_user = wp_get_current_user();
    
    // Only apply to payroll users (administrators should see everyone)
    if (!in_array('accounting_payroll', $current_user->roles)) {
        return;
    }
    
    // If payroll user is viewing users list, exclude administrators
    if (is_admin() && $pagenow === 'users.php') {
        // Get all user roles except administrator
        $all_roles = wp_roles()->get_names();
        unset($all_roles['administrator']);
        
        // Set the query to only show non-admin roles
        $query->set('role__not_in', array('administrator'));
    }
}
add_action('pre_get_users', 'linkage_hide_admin_users_from_payroll');

/**
 * Hide administrator role from role dropdown for payroll users
 */
function linkage_hide_admin_role_dropdown() {
    $current_user = wp_get_current_user();
    
    // Only apply to payroll users
    if (!in_array('accounting_payroll', $current_user->roles)) {
        return;
    }
    
    // Add JavaScript to hide administrator option in role dropdown
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Hide administrator option from role filter dropdown
        $('#role option[value="administrator"]').remove();
        
        // Hide administrator option from bulk role change dropdown
        $('#new_role option[value="administrator"]').remove();
        
        // Hide administrator option from user edit role dropdown
        $('#role option[value="administrator"]').remove();
    });
    </script>
    <?php
}
add_action('admin_footer-users.php', 'linkage_hide_admin_role_dropdown');