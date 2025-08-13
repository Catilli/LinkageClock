<?php
/**
 * Custom login page styling for LinkageClock theme
 * Matches the design of page-desktop-only.php
 */

/**
 * Customize login page styles
 */
function linkage_custom_login_styles() {
    ?>
    <style type="text/css">
        /* Reset and base styles */
        body.login {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif !important;
            background-color: #f8fafc !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        
        /* Login form container */
        #login {
            width: 100% !important;
            max-width: 400px !important;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 3rem 2rem;
            margin: 0 auto;
            position: relative;
        }
        
        /* Logo/Header area */
        .login h1 {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }
        
        .login h1 a {
            background-image: none !important;
            width: auto !important;
            height: auto !important;
            text-indent: 0 !important;
            font-size: 2rem !important;
            font-weight: bold !important;
            color: #1f2937 !important;
            text-decoration: none !important;
            display: block !important;
        }
        
        /* Form styling */
        .login form {
            margin-top: 0;
            padding: 0 1rem !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        /* Input fields */
        .login input[type="text"],
        .login input[type="password"],
        .login input[type="email"] {
            width: 100% !important;
            padding: 12px 16px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 6px !important;
            font-size: 16px !important;
            line-height: 1.5 !important;
            background-color: #ffffff !important;
            transition: border-color 0.2s, box-shadow 0.2s !important;
            box-sizing: border-box !important;
        }
        
        .login input[type="text"]:focus,
        .login input[type="password"]:focus,
        .login input[type="email"]:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            outline: none !important;
        }
        
        /* Labels */
        .login label {
            font-size: 14px !important;
            font-weight: 500 !important;
            color: #374151 !important;
            margin-bottom: 6px !important;
            display: block !important;
        }

        /* Checkbox */
        .login .forgetmenot {
            display:flex;
            align-items: center;
        }
        
        /* Submit button */
        .login .button-primary {
            width: 100% !important;
            background-color: #3b82f6 !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 12px 24px !important;
            font-size: 16px !important;
            font-weight: 500 !important;
            color: white !important;
            cursor: pointer !important;
            transition: background-color 0.2s !important;
            margin-top: 1rem !important;
            text-align: center !important;
        }
        
        .login .button-primary:hover {
            background-color: #2563eb !important;
        }
        
        .login .button-primary:focus {
            background-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
        }
        
        /* Links */
        .login #nav,
        .login #backtoblog {
            text-align: center !important;
            margin: 1rem 0 0 0 !important;
            padding: 0 !important;
        }
        
        .login #nav a,
        .login #backtoblog a {
            color: #6b7280 !important;
            text-decoration: none !important;
            font-size: 14px !important;
            transition: color 0.2s !important;
        }
        
        .login #nav a:hover,
        .login #backtoblog a:hover {
            color: #3b82f6 !important;
        }
        
        /* Hide back to blog link */
        .login #backtoblog {
            display: none !important;
        }
        
        /* Error messages */
        .login #login_error,
        .login .message {
            background: #fef2f2 !important;
            border: 1px solid #fecaca !important;
            color: #dc2626 !important;
            border-radius: 6px !important;
            padding: 12px 16px !important;
            margin-bottom: 1rem !important;
            font-size: 14px !important;
        }
        
        .login .message {
            background: #f0f9ff !important;
            border-color: #93c5fd !important;
            color: #1d4ed8 !important;
        }
        
        /* Responsive design */
        @media (max-width: 480px) {
            #login {
                padding: 2rem 1.5rem !important;
                margin: 0 10px !important;
            }
            
            .login h1 a {
                font-size: 1.75rem !important;
            }
        }
        
        /* Loading state */
        .login .button-primary[disabled] {
            background-color: #9ca3af !important;
            cursor: not-allowed !important;
        }
        
        /* Focus improvements for accessibility */
        .login input:focus,
        .login .button:focus {
            outline: 2px solid #3b82f6 !important;
            outline-offset: 2px !important;
        }
    </style>
    <?php
}
add_action('login_head', 'linkage_custom_login_styles');

/**
 * Change login logo URL to external link
 */
function linkage_custom_login_logo_url() {
    return 'https://linkage.ph';
}
add_filter('login_headerurl', 'linkage_custom_login_logo_url');

/**
 * Change login logo title to site name
 */
function linkage_custom_login_logo_title() {
    return get_bloginfo('name');
}
add_filter('login_headertitle', 'linkage_custom_login_logo_title');

/**
 * Replace WordPress logo with company logo/site name
 */
function linkage_custom_login_logo() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var logoLink = document.querySelector('.login h1 a');
            if (logoLink) {
                // Check if custom logo exists
                <?php if (has_custom_logo()) : ?>
                    // Use custom logo
                    var customLogo = '<?php echo esc_js(wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'medium')); ?>';
                    logoLink.innerHTML = '<img src="' + customLogo + '" alt="<?php echo esc_js(get_bloginfo('name')); ?>" style="max-width: 200px; max-height: 80px; width: auto; height: auto;">';
                <?php else : ?>
                    // Use site name
                    logoLink.innerHTML = '<?php echo esc_js(get_bloginfo('name')); ?>';
                <?php endif; ?>
                
                logoLink.style.backgroundImage = 'none';
                logoLink.style.width = 'auto';
                logoLink.style.height = 'auto';
            }
        });
    </script>
    <?php
}
add_action('login_head', 'linkage_custom_login_logo');

/**
 * Remove "Go to [Site Name]" link below login form
 */
function linkage_remove_login_back_link() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var backLink = document.getElementById('backtoblog');
            if (backLink) {
                backLink.style.display = 'none';
            }
        });
    </script>
    <?php
}
add_action('login_footer', 'linkage_remove_login_back_link');

/**
 * Redirect after successful login
 */
function linkage_custom_login_redirect($redirect_to, $request, $user) {
    // If no redirect URL is set, redirect to homepage (dashboard)
    if (empty($redirect_to) || $redirect_to === admin_url()) {
        return home_url();
    }
    
    return $redirect_to;
}
add_filter('login_redirect', 'linkage_custom_login_redirect', 10, 3);
