<?php
/* 
 *     Plugin Name:  RT Affiliate
 *     Plugin URI:   http://rtcamp.com
 *     Description:  RT Affiliate
 *     Version:      1.0
 *     Author:       Santosh, rtcamp
 *     Author URI:   http://rtcamp.com
*/

/*
 *  define Plugin constants
 */
/*
 *  Plugin Database Version: Change this value every time you make changes to your Plugin.
 */
define( 'RT_AFFILIATE_VERSION', "1.0");

define( 'RT_AFFILIATE_FILE', basename(__FILE__) );
define( 'RT_AFFILIATE_PATH', str_replace( '\\', '/', trailingslashit(dirname(__FILE__)) ) );
define( 'RT_AFFILIATE_URL', plugins_url('', __FILE__) );
define( 'RT_AFFILIATE_HANDLER', 'rt-affiliate-admin' );
define( 'RT_AFFILIATE_HANDLER_USER', 'rt-affiliate-user' );
define( 'RT_AFFILIATE_COMMISSION_B2W', 25 );
define( 'RT_AFFILIATE_COMMISSION_THEME', 25 );
define( 'RT_AFFILIATE_BANNER_PATH', RT_AFFILIATE_PATH.'/banners/' );
define( 'RT_AFFILIATE_BANNER_URL', RT_AFFILIATE_URL.'/banners/' );

/*
 * define global variables
 */
global $rt_status, $payment_method, $payment_type, $rt_time_duration, $rt_user_details, $rt_aff_error;
$rt_status =array( 'contact_submitted' => 'Client Submitted From',
            'awaiting_reply' => 'Awaiting Reply from Client',
            'custom_domain_setup' => 'Custom Domain Setup',
            'wp_theme' => 'WordPress Theme',
            'migration_in_process' => 'Migration in Process',
            'completed' => 'Project Completed',
            'completed_refunded' => 'Project Completed, but refunded',
            'canceled' => 'Project Canceled',
            'deleted' => 'Project Deleted',
            'spam' => 'Spam'
    );

$payment_method = array( '--' =>'--', 'paypal' => 'Paypal', 'bacs' => 'Direct Bank Transfer', 'cheque' => 'Cheque Payment');
$payment_type = array('earning' => 'Earning', 'payout' => 'Payout');
$rt_time_duration = array('today' => 'Today', 'yesterday' => 'Yesterday', 'this_week' => 'This Week', 'last_week' => 'Last Week', 'this_month' => 'This Month', 'last_month' => 'Last Month', 'this_year' => 'This Year', 'last_year' => 'Last Year' );
$rt_user_details = array('clicks_100' => 'Last 100 Clicks', 'enquiries' => 'Enquiries');

require_once( RT_AFFILIATE_PATH . '/rt-functions.php' );
require_once( RT_AFFILIATE_PATH . '/rt-admin-options.php' );
require_once( RT_AFFILIATE_PATH . '/rt-user-options.php' );

/*
 *  WordPress Hook that executes the installation
 */
register_activation_hook(__FILE__,'rt_affiliate_activate');

/*
 *  WordPress Hook that executes at deactivation
 */
register_deactivation_hook(__FILE__,'rt_affiliate_deactivate');

/*
 *  WordPress Hook that handles uninstallation of the Plugin.
 */
register_uninstall_hook( __FILE__, 'rt_affiliate_uninstall' );

/*
 *   CREATE ADMIN MENU
 */
add_action('admin_menu', 'rt_affiliate_menu', 12);

/*
 *   INCLUDE CSS ON FRONT SIDE
 */
add_action( 'wp_print_styles', 'rt_affiliate_options_load_css' );

/*
 *   INCLUDE JS ON FRONT SIDE
 */
add_action( 'wp_print_scripts', 'rt_affiliate_options_load_js' );

/*
 *   CONTACT FORM AND ITS SHORT CODE
 */
add_shortcode('rt_affiliate_contact_form', 'rt_affiliate_contact_form');

/**
 * ADD FILTER TO REDIRECT SUBSCRIBER TO STATS PAGE
 */
add_filter('login_redirect','rt_affiliate_login_redirect', 1, 3);

add_action('woocommerce_checkout_update_order_meta','rt_affiliate_woocommerce_add_refferral_info','', 2);

?>
