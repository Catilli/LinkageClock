<?php
/*
 * Template Name: Employee
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url() );
    exit;
}

$current_user = wp_get_current_user();

// Handle form submission
if ( isset($_POST['update_profile']) && wp_verify_nonce($_POST['profile_nonce'], 'update_profile') ) {
    $userdata = array(
        'ID'           => $current_user->ID,
        'display_name' => sanitize_text_field($_POST['display_name']),
        'user_email'   => sanitize_email($_POST['user_email']),
    );
    wp_update_user($userdata);

    if ( ! empty($_POST['password']) && $_POST['password'] === $_POST['confirm_password'] ) {
        wp_set_password($_POST['password'], $current_user->ID);
        wp_redirect( wp_login_url() ); // User will need to log in again
        exit;
    }
    
    echo '<div class="notice">Profile updated successfully!</div>';
}
?>

<?php get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">

        <!-- Success Message -->
        <?php if (isset($_POST['update_profile']) && wp_verify_nonce($_POST['profile_nonce'], 'update_profile')): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Profile updated successfully!</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-3 gap-8">
            
            <!-- Profile Information -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Profile Information
                        </h2>
                    </div>
                    
                    <form method="post" class="p-6 space-y-6">
                        <?php wp_nonce_field('update_profile', 'profile_nonce'); ?>

                        <!-- Display Name -->
                        <div>
                            <label for="display_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Display Name
                            </label>
                            <input type="text" 
                                   id="display_name"
                                   name="display_name" 
                                   value="<?php echo esc_attr($current_user->display_name); ?>"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="user_email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input type="email" 
                                   id="user_email"
                                   name="user_email" 
                                   value="<?php echo esc_attr($current_user->user_email); ?>"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            
                            <?php 
                            // Check if current email has Gravatar
                            $current_gravatar_url = get_avatar_url($current_user->user_email, array('size' => 80, 'default' => '404'));
                            $current_has_gravatar = false;
                            if (function_exists('wp_remote_get')) {
                                $response = wp_remote_get($current_gravatar_url);
                                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                                    $current_has_gravatar = true;
                                }
                            }
                            ?>
                            
                            <div class="mt-2 p-3 <?php echo $current_has_gravatar ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200'; ?> border rounded-md">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <?php if ($current_has_gravatar): ?>
                                            <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-3">
                                        <?php if ($current_has_gravatar): ?>
                                            <p class="text-sm text-green-700">
                                                <strong>Great!</strong> Your email has a Gravatar profile picture.
                                            </p>
                                        <?php else: ?>
                                            <p class="text-sm text-blue-700">
                                                <strong>Profile Picture:</strong> To add a profile picture, create a free account at 
                                                <a href="https://gravatar.com" target="_blank" class="font-medium underline hover:no-underline">Gravatar.com</a> 
                                                using this email address.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                            <p class="text-sm text-gray-600 mb-4">Leave blank if you don't want to change your password.</p>
                            
                            <!-- New Password -->
                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    New Password
                                </label>
                                <input type="password" 
                                       id="password"
                                       name="password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    Confirm New Password
                                </label>
                                <input type="password" 
                                       id="confirm_password"
                                       name="confirm_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6 border-t border-gray-200">
                            <button type="submit" 
                                    name="update_profile"
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Summary Sidebar -->
            <div class="space-y-6">
                
                <!-- User Avatar & Info -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <?php 
                        // Check if user has a Gravatar
                        $user_email = $current_user->user_email;
                        $gravatar_url = get_avatar_url($user_email, array('size' => 80, 'default' => '404'));
                        
                        // Check if Gravatar exists by trying to get a response
                        $gravatar_exists = false;
                        if (function_exists('wp_remote_get')) {
                            $response = wp_remote_get($gravatar_url);
                            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                                $gravatar_exists = true;
                            }
                        }
                        ?>
                        
                        <div class="relative w-20 h-20 mx-auto mb-4">
                            <?php if ($gravatar_exists): ?>
                                <img src="<?php echo esc_url($gravatar_url); ?>" 
                                     alt="<?php echo esc_attr($current_user->display_name); ?>'s Avatar"
                                     class="w-20 h-20 rounded-full object-cover border-2 border-gray-200">
                            <?php else: ?>
                                <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-2xl font-bold">
                                        <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Gravatar indicator -->
                            <?php if ($gravatar_exists): ?>
                                <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 rounded-full border-2 border-white flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?php echo esc_html($current_user->display_name); ?>
                        </h3>
                        <p class="text-sm text-gray-600">
                            <?php echo esc_html(linkage_get_user_role_display($current_user->ID)); ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Member since <?php echo date('M Y', strtotime($current_user->user_registered)); ?>
                        </p>
                    </div>
                </div>

                <!-- Account Status -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Account Status
                    </h3>
                    
                    <?php 
                    $employee_status = linkage_get_employee_status($current_user->ID);
                    $status_class = $employee_status->status === 'clocked_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    $status_text = $employee_status->status === 'clocked_in' ? 'Clocked In' : 'Clocked Out';
                    ?>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Current Status:</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Last Action:</span>
                            <span class="text-sm text-gray-900">
                                <?php echo linkage_format_actual_time($employee_status->last_action_time); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Quick Actions
                    </h3>
                    
                    <div class="space-y-3">
                        <a href="<?php echo esc_url(home_url('/time-tracking')); ?>" 
                           class="block w-full bg-green-500 hover:bg-green-600 text-white text-center py-2 px-4 rounded-lg transition-colors duration-200">
                            Track Time
                        </a>
                        
                        <a href="<?php echo esc_url(home_url('/')); ?>" 
                           class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-2 px-4 rounded-lg transition-colors duration-200">
                            Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    function validatePasswords() {
        if (passwordField.value && confirmPasswordField.value) {
            if (passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordField.setCustomValidity('');
            }
        }
    }
    
    passwordField.addEventListener('input', validatePasswords);
    confirmPasswordField.addEventListener('input', validatePasswords);
});
</script>

<?php get_footer(); ?>