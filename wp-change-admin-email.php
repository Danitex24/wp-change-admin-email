<?php

/**
 * Plugin Name: Wp Change Admin Email
 * Description: WP Change Admin Email: Modify site admin email without email confirmation. Ideal for testing and localhost setups.
 * Plugin URI:  https://www.codeable.io/developers/daniel-abughdyer
 * Version:     1.0.0
 * Author:      Daniel Abughdyer
 * Author URI:  https://www.codeable.io/developers/daniel-abughdyer
 */


if (!defined('ABSPATH')) {

  exit; // Exit if accessed directly.

}

// enable error logging and track all errors

ini_set('log_errors', 1);
ini_set('error_log', plugin_dir_path(__FILE__) . 'includes/plugin-error.log');

//require plugin files
require_once(plugin_dir_path(__FILE__).'/includes/change_mail_function.php');