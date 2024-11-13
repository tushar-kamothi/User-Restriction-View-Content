<?php 
/**
* Plugin Name:  User Restriction
* Plugin URI: https://wordpress.org/plugins/
* Description: Add Custom Css & Js File To Your Wordpress Website.
* Version: 1.0
* Author: Tushar Kamothi
* Author URI: https://wordpress.org/
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function myplugin_activate() {
    // Flush rewrite rules on activation
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'myplugin_activate');

function create_frontend_only_role()
{
    add_role(
        'frontend_user',
        'Frontend User',
        array(
            'read' => true,
        )
    );
}
add_action('init', 'create_frontend_only_role');

function hide_admin_bar_for_frontend_user($show)
{
    if (current_user_can('frontend_user')) {
        return false; // Do not show the admin bar
    }
    return $show;
}
add_filter('show_admin_bar', 'hide_admin_bar_for_frontend_user');


function redirect_frontend_user_to_frontend()
{
    if (current_user_can('frontend_user') && is_admin()) {
        wp_redirect(home_url()); 
        exit;
    }
}
add_action('admin_init', 'redirect_frontend_user_to_frontend');

function custom_login_redirect($redirect_to, $request, $user)
{
    if (isset($user->roles) && is_array($user->roles)) {
 
        if (in_array('frontend_user', $user->roles)) {
            return home_url();
        }
    }
    
    return $redirect_to;
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);


function add_frontend_user_visibility_metabox()
{
    add_meta_box(
        'frontend_user_visibility',
        'Frontend User Visibility',
        'render_frontend_user_visibility_metabox',
        'post', // Change 'post' to your custom post type if needed
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_frontend_user_visibility_metabox');

function render_frontend_user_visibility_metabox($post)
{
    $value = get_post_meta($post->ID, '_frontend_user_visibility', true);
    echo '<label for="frontend_user_visibility">Visible to Frontend Users</label>';
    echo '<input type="checkbox" id="frontend_user_visibility" name="frontend_user_visibility" value="1" ' . checked(1, $value, false) . '/>';
}

function save_frontend_user_visibility_metabox($post_id)
{
    if (isset($_POST['frontend_user_visibility'])) {
        update_post_meta($post_id, '_frontend_user_visibility', $_POST['frontend_user_visibility']);
    } else {
        delete_post_meta($post_id, '_frontend_user_visibility');
    }
}
add_action('save_post', 'save_frontend_user_visibility_metabox');


function add_frontend_user_posts_field($user)
{
    if (in_array('frontend_user', (array) $user->roles)) {
        $selected_posts = get_user_meta($user->ID, '_frontend_user_selected_posts', true);
        $args = array(
            'numberposts' => -1,
            'post_type'   => 'post', // Adjust this if you're using a custom post type
            'post_status' => 'publish',
        );
        $all_posts = get_posts($args);
?>
        <h3>Selected Posts for Frontend User</h3>
        <table class="form-table">
            <tr>
                <th><label for="frontend_user_posts">Select Posts</label></th>
                <td>
                    <select name="frontend_user_posts[]" id="frontend_user_posts" multiple="multiple" style="width: 100%;">
                        <?php foreach ($all_posts as $post) { ?>
                            <option value="<?php echo esc_attr($post->ID); ?>" <?php echo in_array($post->ID, (array) $selected_posts) ? 'selected="selected"' : ''; ?>>
                                <?php echo esc_html($post->post_title); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <p class="description">Select posts that this user can view.</p>
                </td>
            </tr>
        </table>
    <?php
    }
}
add_action('show_user_profile', 'add_frontend_user_posts_field');
add_action('edit_user_profile', 'add_frontend_user_posts_field');

function save_frontend_user_posts_field($user_id)
{
    if (isset($_POST['frontend_user_posts'])) {
        delete_user_meta($user_id, '_frontend_user_selected_posts'); // Clear cache
        update_user_meta($user_id, '_frontend_user_selected_posts', $_POST['frontend_user_posts']);
    } else {
        delete_user_meta($user_id, '_frontend_user_selected_posts');
    }
}
add_action('personal_options_update', 'save_frontend_user_posts_field');
add_action('edit_user_profile_update', 'save_frontend_user_posts_field');

function show_content_for_frontend_user($atts, $content = null)
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $user_id = $user->ID;

        // Add logging to verify user role and post access
        error_log('User ID: ' . $user_id . ' is logged in.');
        error_log('User Roles: ' . implode(', ', (array) $user->roles));

        // Check if the user has the 'frontend_user' or 'administrator' role
        if (in_array('frontend_user', (array) $user->roles) || in_array('administrator', (array) $user->roles)) {
            // Admin can see all content, frontend_user sees selected posts
            if (in_array('administrator', (array) $user->roles)) {
                error_log('User is admin, showing content.');
                return $content; // Admin can see all content
            }

            // For 'frontend_user', check if the current post is in their selected posts
            $current_post_id = get_the_ID();
            $selected_posts = get_user_meta($user_id, '_frontend_user_selected_posts', true);

            error_log('Selected Posts for User ' . $user_id . ': ' . print_r($selected_posts, true));
            error_log('Current Post ID: ' . $current_post_id);

            if (is_array($selected_posts) && in_array($current_post_id, $selected_posts)) {
            	
                return $content; // Show content if the post is selected by the admin for this user
            } else {
                error_log('Frontend user does not have access to this post.');
                return '<p>This content is not available to you.</p>';
            }
        } else {
            // If the user has a different role, restrict access
            error_log('User does not have access role, showing restriction message.');
            return '<p>This content is restricted to specific users.</p>';
        }
    } else {
        // Log if user is not logged in
        error_log('User is not logged in, showing login prompt.');
        return '<p>Please click here to <a href="' . wp_login_url() . '">log in</a> to view this content.</p>';
    }
}
add_shortcode('show_frontend_user_contents', 'show_content_for_frontend_user');



function custom_auto_logout_script()
{
    // Check if the user has the 'frontend_user' role
    if (is_user_logged_in() && current_user_can('frontend_user')) {
    ?>
        <script type="text/javascript">
            var inactivityTime = function() {
                var time;
                window.onload = resetTimer;
                document.onmousemove = resetTimer;
                document.onkeypress = resetTimer;

                function logout() {
                    window.location.href = "<?php echo wp_logout_url(); ?>";
                }

                function resetTimer() {
                    clearTimeout(time);
                    time = setTimeout(logout, 5 * 60 * 1000); // Logout after 5 minutes of inactivity
                }
            };

            inactivityTime();
        </script>
    <?php
    }
}
add_action('wp_footer', 'custom_auto_logout_script');


function track_login_attempts($user_login, $user)
{
    if (in_array('frontend_user', (array) $user->roles)) {
        $login_attempts = (int) get_user_meta($user->ID, '_login_attempts', true);
        update_user_meta($user->ID, '_login_attempts', $login_attempts + 1);
    }
}
add_action('wp_login', 'track_login_attempts', 10, 2);


function restrict_login_for_frontend_users($user)
{
    if (in_array('frontend_user', (array) $user->roles)) {
        $login_attempts = (int) get_user_meta($user->ID, '_login_attempts', true);

        // Check if the user has exceeded login attempts
        if ($login_attempts >= 4) {
            wp_logout(); // Log the user out if already logged in
            return new WP_Error('login_attempts_exceeded', __('You have exceeded the maximum number of login attempts. Please contact the administrator.'));
        }
    }
    return $user;
}
add_filter('wp_authenticate_user', 'restrict_login_for_frontend_users', 10, 1);




// function reset_login_attempts_on_success($user)
// {
//     if (in_array('frontend_user', (array) $user->roles)) {
//         update_user_meta($user->ID, '_login_attempts', 0);
//     }
//     return $user;
// }
// add_action('wp_login', 'reset_login_attempts_on_success', 10, 1);


function track_login_attempts_and_notify($user_login, $user)
{
    if (in_array('frontend_user', (array) $user->roles)) {
        error_log('Tracking login attempts for user: ' . $user_login);

        $login_attempts = (int) get_user_meta($user->ID, '_login_attempts', true);
        $login_attempts++;
        update_user_meta($user->ID, '_login_attempts', $login_attempts);

        if ($login_attempts > 4) {
            $admin_email = "tushar.codezee@gmail.com";
            $subject = 'Frontend User Exceeded Login Attempts';
            $message = sprintf(
                'User %s has exceeded the maximum number of login attempts. Total attempts: %d.',
                $user_login,
                $login_attempts
            );
            $headers = array('Content-Type: text/html; charset=UTF-8');

            if (wp_mail($admin_email, $subject, $message, $headers)) {
                error_log('Notification email sent successfully.');
            } else {
                error_log('Notification email failed.');
            }
        }
    }
}
// add_action('wp_login', 'track_login_attempts_and_notify', 10, 2);




function reset_login_attempts_on_success($user)
{
    if (in_array('frontend_user', (array) $user->roles)) {
        update_user_meta($user->ID, '_login_attempts', 0);
    }
    return $user;
}
// add_action('wp_login', 'reset_login_attempts_on_success', 10, 1);



function test_email_sending()
{
    $admin_email = get_option('admin_email');
    $subject = 'Test Email';
    $message = 'This is a test email to check if wp_mail function is working.';
    wp_mail($admin_email, $subject, $message);
}
// add_action('wp_footer', 'test_email_sending'); // This will run when the footer is loaded


// Add a custom button to the user profile page
function add_reset_login_attempts_button($user)
{
    // Check if the current user is an admin
    if (current_user_can('manage_options')) {
    ?>
        <h3><?php _e('Reset Login Attempts', 'textdomain'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="reset_login_attempts"><?php _e('Reset Login Attempts', 'textdomain'); ?></label></th>
                <td>
                    <form method="post" action="">
                        <?php wp_nonce_field('reset_login_attempts_action', 'reset_login_attempts_nonce'); ?>
                        <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>" />
                        <input type="submit" name="reset_login_attempts" class="button button-secondary" value="<?php _e('Reset Login Attempts', 'textdomain'); ?>" />
                    </form>
                </td>
            </tr>
        </table>
<?php
    }
}
add_action('show_user_profile', 'add_reset_login_attempts_button');
add_action('edit_user_profile', 'add_reset_login_attempts_button');

// Handle the reset button click
function handle_reset_login_attempts_action()
{
    // Check if the form is submitted and user has permissions
    if (isset($_POST['reset_login_attempts']) && current_user_can('manage_options')) {
        if (!isset($_POST['reset_login_attempts_nonce']) || !wp_verify_nonce($_POST['reset_login_attempts_nonce'], 'reset_login_attempts_action')) {
            return;
        }

        // Get user ID
        $user_id = intval($_POST['user_id']);

        // Reset login attempts for the user
        if (in_array('frontend_user', (array) get_userdata($user_id)->roles)) {
            update_user_meta($user_id, '_login_attempts', 0);
            // Optional: Add an admin notice
            add_action('admin_notices', function () {
                echo '<div class="updated notice is-dismissible"><p>' . __('Login attempts have been reset for this user.', 'textdomain') . '</p></div>';
            });
        }
    }
}
add_action('admin_init', 'handle_reset_login_attempts_action');

// function refresh_frontend_user_cache_on_login($user_login, $user)
// {
//     if (in_array('frontend_user', (array) $user->roles)) {
//         delete_user_meta($user->ID, '_frontend_user_selected_posts');
//     }
// }
// add_action('wp_login', 'refresh_frontend_user_cache_on_login', 10, 2);

// function refresh_frontend_user_cache_on_logout()
// {
//     $user = wp_get_current_user();
//     if (in_array('frontend_user', (array) $user->roles)) {
//         delete_user_meta($user->ID, '_frontend_user_selected_posts');
//     }
// }
// add_action('wp_logout', 'refresh_frontend_user_cache_on_logout');