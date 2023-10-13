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