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
register_activation_hook(__FILE__, 'linkage_create_timesheet_table');