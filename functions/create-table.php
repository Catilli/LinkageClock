<?php
function linkage_create_timesheet_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'linkage_timesheets';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        work_date DATE NOT NULL,
        hours_worked DECIMAL(5,2) NOT NULL,
        notes TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function linkage_create_employee_status_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'linkage_employee_status';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        status ENUM('clocked_in', 'clocked_out') NOT NULL,
        last_action_time DATETIME NOT NULL,
        last_action_type ENUM('clock_in', 'clock_out') NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create tables when theme is activated
register_activation_hook(__FILE__, 'linkage_create_timesheet_table');
register_activation_hook(__FILE__, 'linkage_create_employee_status_table');

// Also create tables on theme load to ensure they exist
function linkage_ensure_tables_exist() {
    global $wpdb;
    
    // Check if employee status table exists
    $status_table = $wpdb->prefix . 'linkage_employee_status';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$status_table'") == $status_table;
    
    if (!$table_exists) {
        linkage_create_employee_status_table();
    }
    
    // Check if timesheet table exists
    $timesheet_table = $wpdb->prefix . 'linkage_timesheets';
    $timesheet_exists = $wpdb->get_var("SHOW TABLES LIKE '$timesheet_table'") == $timesheet_table;
    
    if (!$timesheet_exists) {
        linkage_create_timesheet_table();
    }
}

// Run table check on theme load
add_action('after_setup_theme', 'linkage_ensure_tables_exist');