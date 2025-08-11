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

<div class="profile-container">
    <h1>My Profile</h1>
    <form method="post">
        <?php wp_nonce_field('update_profile', 'profile_nonce'); ?>

        <label>Display Name</label>
        <input type="text" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>">

        <label>Email</label>
        <input type="email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>">

        <label>New Password</label>
        <input type="password" name="password">

        <label>Confirm Password</label>
        <input type="password" name="confirm_password">

        <button type="submit" name="update_profile">Update Profile</button>
    </form>
</div>

<?php get_footer(); ?>