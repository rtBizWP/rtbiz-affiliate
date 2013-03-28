<?php

/**
 * Description of rtAffiliate
 *
 * @author Joshua Abenazer <joshua.abenazer@rtcamp.com>
 */
if (!class_exists('rtAffiliate')) {

    class rtAffiliate {

        var $payment_methods = array(
            '--' => '--',
            'paypal' => 'Paypal',
            'bacs' => 'Direct Bank Transfer',
            'cheque' => 'Cheque Payment'
        );
        var $payment_types = array(
            'earning' => 'Earning',
            'payout' => 'Payout'
        );
        var $time_durations = array(
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
            'last_year' => 'Last Year'
        );
        var $tables = array(
            'rt_aff_users_referals',
            'rt_aff_payment_info',
            'rt_aff_transaction'
        );

        public function __construct() {
            register_activation_hook(RT_AFFILIATE_PATH.'index.php', array($this, 'create_tables'));
            add_action('init', array($this,'set_referer_cookie'));
            add_action('woocommerce_checkout_update_order_meta', array($this,'store_order_meta_referer_info'), '', 2);
            new rtAffiliateAdmin();
        }

        public function create_tables() {
            global $wpdb;

            $users_referals = $wpdb->prefix . 'rt_aff_users_referals';
            $payment_info = $wpdb->prefix . 'rt_aff_payment_info';
            $transactions = $wpdb->prefix . 'rt_aff_transaction';

            /*
             * detects if this is a new installation or simply an update.
             */
            $new_installation = $wpdb->get_var("show tables like '$users_referals'");
            
            if (!$new_installation) {
                $sql = "CREATE TABLE " . $users_referals . " (
                    id int NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `user_id` INT NULL ,
                    `referred_from` VARCHAR(200) NULL ,
                    `ip_address` VARCHAR(200) NULL ,
                    `landing_page` VARCHAR(200) NULL ,
                    `date` DATETIME NULL
		);";

                $sql .= "CREATE TABLE " . $payment_info . " (
                    id int NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `user_id` INT NULL ,
                    `payment_method` VARCHAR(45) NULL ,
                    `paypal_email` VARCHAR(200) NULL ,
                    `check_address` VARCHAR(200) NULL ,
                    `bank_details` VARCHAR(200) NULL ,
                    `min_payout` INT NULL
		);";

                $sql .= "CREATE TABLE " . $transactions . " (
                    id int NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `txn_id` VARCHAR(100) NULL ,
                    `user_id` INT NULL ,
                    `type` VARCHAR(100) NULL ,
                    `amount` float(10,2) NULL ,
                    `payment_method`  VARCHAR(100) NULL,
                    `approved` BOOLEAN NOT NULL DEFAULT  '1', 
                    `note`  TEXT NULL,
                    `date` DATETIME NULL
		);";

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta($sql);
            }
        }

        public function set_referer_cookie() {
            global $wpdb, $rt_aff_error;

            if (!isset($_SESSION)) {
                session_start();
            }

            /*
             * if this is from affiliate referer
             */
            if (isset($_GET['ref'])) {
                $landing_page = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

                $redirect_link = remove_query_arg('ref', $landing_page);

                /*
                 * check referer's usermname is valid
                 */
                $sql = "SELECT ID FROM " . $wpdb->users . " WHERE user_login = '" . trim($_GET['ref']) . "'";
                $row = $wpdb->get_row($sql);

                /*
                 * if user name found in users table
                 */
                if ($row) {
                    /*
                     * set cookies
                     */
                    setcookie('rt_aff_username', $_GET['ref'], time() + ( 30 * 24 * 3600 ), SITECOOKIEPATH); //, "/", str_replace('http://www','',get_bloginfo('url')));
                    setcookie('rt_aff_user_id', $row->ID, time() + ( 30 * 24 * 3600 ), SITECOOKIEPATH);
                    //setcookie ($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null) ;

                    /*
                     * save referer's user_id in session also
                     */
                    $_SESSION['rt_aff_user_id'] = $row->ID;

                    $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_users_referals
                (`user_id`, `referred_from`, `ip_address`, `landing_page`, `date`)  VALUES
                ( $row->ID, '" . $_SERVER['HTTP_REFERER'] . "', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $landing_page . "', now() )";
                    $wpdb->query($sql);

                    /*
                     * save referal's id in session also
                     */
                    $_SESSION['rt_aff_referal_id'] = $wpdb->insert_id;

                    header("Location: " . $redirect_link);
                }
            }
        }

        public function store_order_meta_referer_info($order_id, $detail) {
            global $wpdb, $woocommerce;
            $rt_ref_affiliate = null;
            if (isset($_COOKIE['rt_aff_username']))
                $rt_ref_affiliate .= $_COOKIE['rt_aff_username'] . ', ';
            if (isset($_COOKIE['rt_aff_user_id'])) {
                $rt_ref_affiliate .= $_COOKIE['rt_aff_user_id'];
                $order_items = (array) maybe_unserialize(get_post_meta($order_id, '_order_items', true));
                $comment = NULL;
                foreach ($order_items as $item) {
                    $comment .= $item['name'] . '<br /><br />';
                }
                $wpdb->insert(
                        $wpdb->prefix . "rt_aff_transaction", array(
                    'txn_id' => $order_id,
                    'user_id' => $_COOKIE['rt_aff_user_id'],
                    'type' => 'earning',
                    'approved' => 0,
                    'amount' => round($woocommerce->cart->total * (get_option('rt_aff_commission', 20) / 100), 2),
                    'payment_method' => $detail['payment_method'],
                    'note' => $comment,
                    'date' => get_post_field('post_date_gmt',$order_id)
                        )
                );
            }
            if ($rt_ref_affiliate)
                update_post_meta($order_id, '_rt-ref-affiliate', $rt_ref_affiliate);
        }

    }

}
?>
