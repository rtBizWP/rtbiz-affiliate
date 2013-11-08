<?php

/**
 * Description of rtAffiliate
 *
 * @author Joshua Abenazer <joshua.abenazer@rtcamp.com>
 */
if ( ! class_exists ( 'rtAffiliate' ) ) {

    class rtAffiliate {

        var $payment_methods = array (
            '--'     => '--' ,
            'paypal' => 'Paypal' ,
            'bacs'   => 'Direct Bank Transfer' ,
            'cheque' => 'Cheque Payment'
        ) ;
        var $payment_types   = array (
            'earning' => 'Earning' ,
            'payout'  => 'Payout'
        ) ;
        var $time_durations  = array (
            'today'      => 'Today' ,
            'yesterday'  => 'Yesterday' ,
            'this_week'  => 'This Week' ,
            'last_week'  => 'Last Week' ,
            'this_month' => 'This Month' ,
            'last_month' => 'Last Month' ,
            'this_year'  => 'This Year' ,
            'last_year'  => 'Last Year'
        ) ;
        var $tables          = array (
            'rt_aff_users_referals' ,
            'rt_aff_payment_info' ,
            'rt_aff_transaction'
        ) ;
        var $currency_types =  array (
            'USD',
            'INR'
        );

        public function __construct () {
            register_activation_hook ( RT_AFFILIATE_PATH . 'index.php' , array ( $this , 'create_tables' ) ) ;
            add_action ( 'init' , array ( $this , 'create_tables' ) ) ;
            add_action ( 'init' , array ( $this , 'set_referer_cookie' ) ) ;
            add_action ( 'woocommerce_checkout_update_order_meta' , array ( $this , 'store_order_meta_referer_info' ) , '' , 2 ) ;
            global $rtAffiliateAdmin;
            $rtAffiliateAdmin = new rtAffiliateAdmin() ;
        }
        
        public function create_tables () {
            global $wpdb ;
            $users_referals = $wpdb -> prefix . 'rt_aff_users_referals' ;
            $payment_info   = $wpdb -> prefix . 'rt_aff_payment_info' ;
            $transactions   = $wpdb -> prefix . 'rt_aff_transaction' ;
            $rt_db_update = new RTDBUpdate(false, trailingslashit( RT_AFFILIATE_PATH ) . 'index.php' , trailingslashit( RT_AFFILIATE_PATH )  . 'app/schema/');
            $rt_db_update->  do_upgrade();
        }

        public function set_referer_cookie () {
            global $wpdb , $rt_aff_error ;

            if ( ! isset ( $_SESSION ) ) {
                session_start () ;
            }

            /*
             * if this is from affiliate referer
             */
            if ( isset ( $_GET[ 'ref' ] ) ) {
                $landing_page = (is_ssl () ? 'https://' : 'http://') . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] ;

                $redirect_link = remove_query_arg ( 'ref' , $landing_page ) ;

                /*
                 * check referer's usermname is valid
                 */
                $sql = $wpdb -> prepare ( "SELECT ID FROM  $wpdb->users WHERE user_login = %s" , trim ( $_GET[ 'ref' ] ) );
                $row = $wpdb -> get_row ( $sql ) ;

                /*
                 * if user name found in users table
                 */
                if ( $row ) {
                    /*
                     * set cookies
                     */
                    setcookie ( 'rt_aff_username' , $_GET[ 'ref' ] , time () + ( 30 * 24 * 3600 ) , SITECOOKIEPATH ) ; //, "/", str_replace('http://www','',get_bloginfo('url')));
                    setcookie ( 'rt_aff_user_id' , $row -> ID , time () + ( 30 * 24 * 3600 ) , SITECOOKIEPATH ) ;
                    //setcookie ($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null) ;

                    /*
                     * save referer's user_id in session also
                     */
                    $_SESSION[ 'rt_aff_user_id' ] = $row -> ID ;

                   $wpdb -> insert ( $wpdb -> prefix . "rt_aff_users_referals" , array ( "user_id"       => $row -> ID ,
                        "referred_from" => $_SERVER[ 'HTTP_REFERER' ] ,
                        'ip_address'    => $_SERVER[ 'REMOTE_ADDR' ] ,
                        'landing_page'  => $landing_page ,
                        'date'          => current_time ( 'mysql' ) ) , array ( '%d' , '%s' , '%s' , '%s' , '%s' ) ) ;
                    

                    /*
                     * save referal's id in session also
                     */
                    $_SESSION[ 'rt_aff_referal_id' ] = $wpdb -> insert_id ;

                    header ( "Location: " . $redirect_link ) ;
                    exit;
                }
            }
        }

        public function store_order_meta_referer_info ( $order_id , $detail ) {
            global $wpdb , $woocommerce ;
            $rt_ref_affiliate = null ;
            if ( isset ( $_COOKIE[ 'rt_aff_username' ] ) )
                $rt_ref_affiliate .= $_COOKIE[ 'rt_aff_username' ] . ', ' ;
            if ( isset ( $_COOKIE[ 'rt_aff_user_id' ] ) ) {
                $rt_ref_affiliate .= $_COOKIE[ 'rt_aff_user_id' ] ;
                $order_items = ( array ) maybe_unserialize ( get_post_meta ( $order_id , '_order_items' , true ) ) ;
                $comment     = NULL ;
                foreach ( $order_items as $item ) {
                    $comment .= $item[ 'name' ] . '<br /><br />' ;
                }
                $wpdb -> insert (
                        $wpdb -> prefix . "rt_aff_transaction" , array (
                    'txn_id'         => $order_id ,
                    'user_id'        => $_COOKIE[ 'rt_aff_user_id' ] ,
                    'type'           => 'earning' ,
                    'approved'       => 0 ,
                    'amount'         => round ( $woocommerce -> cart -> total * (get_option ( 'rt_aff_woo_commission' , 20 ) / 100) , 2 ) ,
                    'payment_method' => $detail[ 'payment_method' ] ,
                    'note'           => $comment ,
                    'date'           => get_post_field ( 'post_date_gmt' , $order_id )
                     ,array('%d','%d','%s','%d','%s','%s','%s','%s')   )
                ) ;
            }
            if ( $rt_ref_affiliate )
                update_post_meta ( $order_id , '_rt-ref-affiliate' , $rt_ref_affiliate ) ;
        }

    }

}
?>
