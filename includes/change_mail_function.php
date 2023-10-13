<?php


namespace WpChangeAdminEmail;

class WpChangeAdminEmail{

    // Verify nonce for security
    public function verify_nonce() {
        if (!wp_verify_nonce($_POST['wp-change-admin-email-test-email-nonce'], 'wp-change-admin-email')) {
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
        $domain = site_url();
        $url = "https://generalchicken.guru/wp-json/change-admin-email-plugin/v1/test-email";
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'body' => array(
                'email' => $email,
                'domain' => $domain
            ),
        ));
        AdminNotice::display_success(__('Check your email inbox. A test message has been sent to your inbox.'));
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
            var insertInputButton = "<input type='submit' class='button button-primary' name='changeAdminEmailSubmit' id='changeAdminEmailSubmitButton' value='Test Email' />";
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