<?php
/**
 * Create attendance logs table for detailed time tracking
 * This table stores one record per shift/day per employee
 */
function linkage_create_attendance_logs_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'linkage_attendance_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        work_date DATE NOT NULL,
        time_in DATETIME,
        time_out DATETIME,
        lunch_start DATETIME,
        lunch_end DATETIME,
        total_hours DECIMAL(5,2) DEFAULT 0.00,
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_shift (user_id, work_date),
        KEY idx_user_id (user_id),
        KEY idx_work_date (work_date),
        KEY idx_status (status)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create tables when theme is activated
register_activation_hook(__FILE__, 'linkage_create_attendance_logs_table');

// Also create tables on theme load to ensure they exist
function linkage_ensure_tables_exist() {
    global $wpdb;
    
    // Check if attendance logs table exists
    $attendance_table = $wpdb->prefix . 'linkage_attendance_logs';
    $attendance_exists = $wpdb->get_var("SHOW TABLES LIKE '$attendance_table'") == $attendance_table;
    
    if (!$attendance_exists) {
        linkage_create_attendance_logs_table();
    }
}

// Run table check on theme load
add_action('after_setup_theme', 'linkage_ensure_tables_exist');