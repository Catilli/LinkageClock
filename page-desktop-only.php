<?php
/**
 * Template Name: Desktop Only Access
 * 
 * A custom page template for desktop-only access.
 * Prevents mobile users from accessing clock-in functionality.
 * No header, sidebar, or footer - just centered content.
 */

// Check if user is on mobile device
function linkage_is_mobile_device() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $mobile_agents = array(
        'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 
        'IEMobile', 'Opera Mini', 'Opera Mobi', 'webOS', 'Windows Phone'
    );
    
    foreach ($mobile_agents as $agent) {
        if (stripos($user_agent, $agent) !== false) {
            return true;
        }
    }
    
    return false;
}

// Check if user has custom role (employee, manager, etc.) - not administrator
function linkage_user_has_custom_role() {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $user = wp_get_current_user();
    $custom_roles = array('employee', 'manager', 'accounting_payroll', 'contractor');
    
    foreach ($custom_roles as $role) {
        if (in_array($role, $user->roles)) {
            return true;
        }
    }
    
    return false;
}

// Redirect mobile users with custom roles
if (linkage_is_mobile_device() && linkage_user_has_custom_role()) {
    // Redirect to a mobile restriction page or home
    wp_redirect(home_url('/'));
    exit;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
    
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .desktop-only-container {
            max-width: 800px;
            width: 90%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
        }
        
        .desktop-only-content {
            width: 100%;
        }
        
        .desktop-only-content h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .desktop-only-content h2 {
            font-size: 2rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
        }
        
        .desktop-only-content h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.75rem;
        }
        
        .desktop-only-content p {
            font-size: 1.125rem;
            color: #6b7280;
            margin-bottom: 1rem;
            line-height: 1.7;
        }
        
        .desktop-only-content a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .desktop-only-content a:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        
        .desktop-only-content ul, .desktop-only-content ol {
            text-align: left;
            margin: 1rem 0;
            padding-left: 2rem;
        }
        
        .desktop-only-content li {
            margin-bottom: 0.5rem;
            color: #6b7280;
        }
        
        .desktop-only-content .wp-block-button {
            margin: 1rem 0;
        }
        
        .desktop-only-content .wp-block-button__link {
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        
        .desktop-only-content .wp-block-button__link:hover {
            background-color: #2563eb;
            color: white;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .desktop-only-container {
                width: 95%;
                padding: 2rem;
            }
            
            .desktop-only-content h1 {
                font-size: 2rem;
            }
            
            .desktop-only-content h2 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>

<body <?php body_class('desktop-only-page'); ?>>
<?php wp_body_open(); ?>

<div class="desktop-only-container">
    <div class="desktop-only-content">
        <?php
        // Start the loop
        if (have_posts()) :
            while (have_posts()) : the_post();
                // Display the page content (editable in WordPress editor)
                the_content();
            endwhile;
        else :
            // Default content if no content is set
            ?>
            <h1>Desktop Only Access</h1>
            <p>This page is designed for desktop access only. Please use a desktop computer to access the time tracking system.</p>
            <p>Mobile access is restricted for security and usability reasons.</p>
            <?php
        endif;
        ?>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
