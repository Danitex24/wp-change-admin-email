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