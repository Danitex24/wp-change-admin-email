<?php
namespace WpChangeAdminEmailPlugin;
$WpChangeAdminEmailPlugin = new WpChangeAdminEmailPlugin;
$WpChangeAdminEmailPlugin->run();

class WpChangeAdminEmailPlugin{

    // Verify nonce for security
    public function verify_nonce() {
        if (!wp_verify_nonce($_POST['change-admin-email-test-email-nonce'], 'change-admin-email')) {
            wp_die('Invalid nonce detected.');
        }
    }
    // Remove any pending email change requests
    public function remove_pending_email() {
        delete_option("adminhash");
        delete_option("new_admin_email");
    }
    // Initialize the plugin
    public function run() {
        add_action('init', array($this, 'remove_pending_email'));
        add_action('admin_notices', [new AdminNotice(), 'display_admin_notice']);
        remove_action('add_option_new_admin_email', 'update_option_new_admin_email');
        remove_action('update_option_new_admin_email', 'update_option_new_admin_email');
        add_filter('send_site_admin_email_change_email', function(){return FALSE;}, 10, 3);

        // Check if the nonce is submitted and add necessary actions
        if (isset($_POST['change-admin-email-test-email-nonce'])) {
            add_action('init', array($this, 'verify_nonce'));
            add_action('init', array($this, 'test_email'));
        }

        add_action('add_option_new_admin_email', array($this, 'update_option_admin_email'), 10, 2);
        add_action('update_option_new_admin_email', array($this, 'update_option_admin_email'), 10, 2);
        add_action('wp_after_admin_bar_render', array($this, 'modify_options_general_form'));
    }

    // Test sending email
    public function test_email() {
        $email = $_POST['new_admin_email'];
        
        // let us Create the email body as an array
        $email_data = array(
            'to' => $email, // Email address where the test email will be sent
            'subject' => 'Test Email Subject', // Subject of the test email
            'message' => 'This is a test email.'. '<br>'.' If you can see this email, this means that you have successfully change your site admin email.', // Body of the test email
            'headers' => 'Content-Type: text/html; charset=UTF-8', // Set the content type to HTML
        );

        // Send the test email to a fixed URL
        $response = wp_mail($email_data['to'], $email_data['subject'], $email_data['message'], $email_data['headers']);

        if ($response) {
            AdminNotice::display_success(__('Check your email inbox. A test message has been sent to your inbox.'));
        } else {
            AdminNotice::display_error(__('Failed to send the test email. Please check your email settings.'));
        }
    }


    // Update admin email option
    public function update_option_admin_email($old_value, $value) {
        update_option('admin_email', $value);
    }

    // Modify the options-general.php form
    public function modify_options_general_form() {
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen->base == "options-general") {
                add_filter('gettext', array($this, 'filter_text'), 10, 3);
                echo ($this->return_jquery());
            }
        }
    }

    // Add jQuery for UI enhancements
    public function return_jquery() {
        $nonce = wp_create_nonce('change-admin-email');
        $output = <<<OUTPUT
        <script>
        jQuery(document).ready(function(){
            var insertInputButton = "<input type='submit' class='button button-primary' name='changeAdminEmailSubmit' id='changeAdminEmailSubmitButton' value='Test Email Now' />";
            jQuery(insertInputButton).insertAfter("#new-admin-email-description");
            
            jQuery("#changeAdminEmailSubmitButton").click(function(event) {
                event.preventDefault();
                var insertThisNonce = "<input type='hidden' name='changeAdminEmailAction' value='changeEmail' /><input type='hidden' name='change-admin-email-test-email-nonce' value='$nonce' />";
                jQuery(insertThisNonce).insertAfter("#new-admin-email-description");
                jQuery("#submit").click();
            });
        });
        </script>
        OUTPUT;
        return $output;
    }

     // Filter English text for UI
    public function filter_text($translated, $original, $domain) {
        if ($translated == "This address is used for admin purposes. If you change this, an email will be sent to your new address to confirm it. <strong>The new address will not become active until confirmed.") {
            $translated = __("This address is used for admin purposes.");
        }
        return $translated;
    }
}
class AdminNotice {
    const NOTICE_FIELD = 'my_admin_notice_message';

    // Display admin notice
    public function display_admin_notice() {
        $option = get_option(self::NOTICE_FIELD);
        $message = isset($option['message']) ? $option['message'] : false;
        $noticeLevel = !empty($option['notice-level']) ? $option['notice-level'] : 'notice-error';

        if ($message) {
            echo "<div class='notice {$noticeLevel} is-dismissible'><p>{$message}</p></div>";
            delete_option(self::NOTICE_FIELD);
        }
    }

     // Display error notice
    public static function display_error($message) {
        self::update_option($message, 'notice-error');
    }

    // Display warning notice
    public static function display_warning($message) {
        self::update_option($message, 'notice-warning');
    }

    // Display info notice
    public static function display_info($message) {
        self::update_option($message, 'notice-info');
    }

    // Display success notice
    public static function display_success($message) {
        self::update_option($message, 'notice-success');
    }

    // Update admin notice
    protected static function update_option($message, $noticeLevel) {
        update_option(self::NOTICE_FIELD, [
            'message' => $message,
            'notice-level' => $noticeLevel
        ]);
    }
}