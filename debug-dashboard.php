<?php
/**
 * Debug Dashboard - Temporary file to diagnose employee display issues
 */

// Include WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Include our functions
require_once get_template_directory() . '/functions/dashboard-functions.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>LinkageClock Debug Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>LinkageClock Debug Dashboard</h1>
    
    <div class="debug-section">
        <h2>Quick Fix Actions</h2>
        <p>Click these buttons to fix common issues:</p>
        
        <form method="post">
            <button type="submit" name="action" value="create_tables">1. Create/Update Database Tables</button>
            <button type="submit" name="action" value="initialize_users">2. Initialize All Users as Employees</button>
            <button type="submit" name="action" value="debug_all">3. Run Full Debug</button>
        </form>
    </div>

    <?php
    if ($_POST) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'create_tables':
                echo '<div class="debug-section success">';
                linkage_force_create_tables();
                echo '</div>';
                break;
                
            case 'initialize_users':
                echo '<div class="debug-section success">';
                linkage_force_initialize_all_users();
                echo '</div>';
                break;
                
            case 'debug_all':
                echo '<div class="debug-section">';
                linkage_debug_database_tables();
                echo '</div>';
                
                echo '<div class="debug-section">';
                linkage_debug_user_roles();
                echo '</div>';
                
                echo '<div class="debug-section">';
                echo '<h3>Employee Query Test</h3>';
                $employees = linkage_get_all_employees_status();
                echo '<p><strong>Employees Found:</strong> ' . count($employees) . '</p>';
                if (!empty($employees)) {
                    echo '<p><strong>Employee List:</strong></p>';
                    echo '<pre>' . print_r($employees, true) . '</pre>';
                } else {
                    echo '<p class="error">No employees found!</p>';
                }
                echo '</div>';
                break;
        }
    }
    ?>

    <div class="debug-section">
        <h2>Current Status</h2>
        <?php
        // Check if tables exist
        global $wpdb;
        $status_table = $wpdb->prefix . 'linkage_employee_status';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$status_table'") == $status_table;
        
        if ($table_exists) {
            echo '<p class="success">✓ Employee status table exists</p>';
        } else {
            echo '<p class="error">✗ Employee status table does not exist</p>';
        }
        
        // Check user count
        $user_count = count_users();
        echo '<p><strong>Total Users:</strong> ' . $user_count['total_users'] . '</p>';
        
        // Check employee count
        $employees = linkage_get_all_employees_status();
        echo '<p><strong>Employees Found:</strong> ' . count($employees) . '</p>';
        ?>
    </div>

    <div class="debug-section">
        <h2>Manual Fix Instructions</h2>
        <ol>
            <li><strong>Create Tables:</strong> Click "Create/Update Database Tables" above</li>
            <li><strong>Assign Roles:</strong> Go to WordPress Admin → Users and assign the "Employee" role to your users</li>
            <li><strong>Initialize Status:</strong> Click "Initialize All Users as Employees" above</li>
            <li><strong>Refresh:</strong> Go back to your main dashboard and refresh the page</li>
        </ol>
    </div>

    <div class="debug-section">
        <h2>WordPress Admin Links</h2>
        <p><a href="<?php echo admin_url('users.php'); ?>" target="_blank">Manage Users</a></p>
        <p><a href="<?php echo home_url(); ?>" target="_blank">Main Dashboard</a></p>
    </div>
</body>
</html>
