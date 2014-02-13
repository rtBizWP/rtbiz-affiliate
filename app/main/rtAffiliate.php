<?php
/**
 * 
 * 
 */
if ( !class_exists ( 'rtAffiliate' ) ) {
        /**
         * 
         */
        class rtAffiliate {
                var
                        $payment_methods=array (
                        '--'    =>'--' ,
                        'paypal'=>'Paypal' ,
                        'bacs'  =>'Direct Bank Transfer' ,
                        'cheque'=>'Cheque Payment'
                        ) ;
                var
                        $payment_types  =array (
                        'earning'=>'Earning' ,
                        'payout' =>'Payout'
                        ) ;
                var
                        $time_durations =array (
                        'today'     =>'Today' ,
                        'yesterday' =>'Yesterday' ,
                        'this_week' =>'This Week' ,
                        'last_week' =>'Last Week' ,
                        'this_month'=>'This Month' ,
                        'last_month'=>'Last Month' ,
                        'this_year' =>'This Year' ,
                        'last_year' =>'Last Year'
                        ) ;
                var
                        $tables         =array (
                        'rt_aff_users_referals' ,
                        'rt_aff_buy_summary' ,
                        'rt_aff_payment_info' ,
                        'rt_aff_transaction'
                        ) ;
                var
                        $currency_types =array (
                        'USD' ,
                        'INR'
                        ) ;
                public
                        function __construct () {
                        register_activation_hook ( RT_AFFILIATE_PATH . 'index.php' , array ( $this , 'create_tables' ) ) ;
                        add_action ( 'init' , array ( $this , 'create_tables' ) ) ;
                        add_action ( 'init' , array ( $this , 'set_referer_cookie' ) ) ;
                        add_action ( 'woocommerce_checkout_update_order_meta' , array ( $this , 'store_order_meta_referer_info' ) , 1 , 2 ) ;
                        add_action('wp_dashboard_setup',  array ( $this , 'my_custom_dashboard_widgets'));
                        add_action( 'prefix_daily_event_hook', array ( $this , 'prefix_daily_send_mail_ofclick' )  );
                        register_deactivation_hook(RT_AFFILIATE_PATH . 'index.php' , array ( $this , 'prefix_deactivation_cron' )   );
                        global $rtAffiliateAdmin ;
                        $rtAffiliateAdmin=new rtAffiliateAdmin() ;
                }
                public function my_custom_dashboard_widgets() {
                    wp_add_dashboard_widget('custom_help_widget', 'rtAffiliate: Monthly revenue', array ( $this , 'custom_dashboard_rtaff_widget') );
                }

                public function custom_dashboard_rtaff_widget() {                    
                    $rtp = new rtAffiliateAdmin();
                    $rtp->monthly_visit_report('540', '300');
                }
                public
                        function create_tables () {
                        global $wpdb ;
                        $users_referals=$wpdb->prefix . 'rt_aff_users_referals' ;
                        $payment_info  =$wpdb->prefix . 'rt_aff_payment_info' ;
                        $transactions  =$wpdb->prefix . 'rt_aff_transaction' ;
                        $rt_db_update  =new RTDBUpdate ( false , trailingslashit ( RT_AFFILIATE_PATH ) . 'index.php' , trailingslashit ( RT_AFFILIATE_PATH ) . 'app/schema/' ) ;
                        $rt_db_update->do_upgrade ();
                        
                        if ( ! wp_next_scheduled( 'prefix_daily_event_hook' ) ) {
                           wp_schedule_event( time()  , 'daily', 'prefix_daily_event_hook' );
                        }
                }
                function prefix_deactivation_cron(){
                    wp_clear_scheduled_hook( 'prefix_daily_event_hook' );
                }
                function prefix_daily_send_mail_ofclick() {
                        // do this daily
                        global $wpdb;
                        // Daily on buying
                        $sql_pay  = $wpdb -> prepare ( "SELECT buy_notify,frequently FROM " . $wpdb -> prefix . "rt_aff_payment_info ",''  ) ;
                        $rows_pay = $wpdb -> get_results ( $sql_pay ) ;
                        
                        foreach ($rows_pay as $buyer_notify_row ) {
                                                
                            if( $buyer_notify_row->buy_notify == 1 && $buyer_notify_row->frequently == 2 ){

                                $sql_user_buy  = $wpdb -> prepare ( "SELECT user_id FROM " . $wpdb -> prefix . "rt_aff_buy_summary where date(`date`)= date(now()) group by user_id", '' ) ;
                                $rows_user_buy = $wpdb -> get_results( $sql_user_buy ) ;  

                                foreach ($rows_user_buy as $buyer_row) {                                        

                                    $sql_user_buy_row  = $wpdb -> prepare ( "SELECT * FROM " . $wpdb -> prefix . "rt_aff_buy_summary where date(`date`)= date(now()) and user_id= %d" , $buyer_row->user_id ) ;
                                    $rows_user_buy_row = $wpdb -> get_results( $sql_user_buy_row ) ;

                                    $msg = '';
                                    $total_user_amount = 0;
                                    $total_user_commision = 0;
                                    
                                    $msg = "<table>
                                                <tr>
                                                    <th> Cart Amount</th>
                                                    <th> Your Commission </th>
                                                </tr>";
                                    foreach($rows_user_buy_row as $user_buy){
                                        $buyer_id = $user_buy->user_id;
                                        $msg .= "<tr>
                                                    <td>". $user_buy->cart_amount ."</td>
                                                    <td>". $user_buy->commision ."</td>
                                                </tr>";
                                        $total_user_amount += $user_buy->cart_amount;
                                        $total_user_commision += $user_buy->commision; 
                                       
                                      
                                                                                
                                    }                
                                    $msg .= "   <tr>
                                                    <td> Total Cart Amount: ". $total_user_amount . "</td>
                                                    <td> Total Commission: ". $total_user_commision ."</td>                                                        
                                                </tr>
                                            </table>";
                                    
                                    $sql_user  = $wpdb -> prepare ( "SELECT user_login, user_email  FROM " . $wpdb -> prefix . "users where ID = %d " , $buyer_id ) ;
                                    $rows_user = $wpdb -> get_row ( $sql_user ) ;  
                                    
                                    if(!empty($rows_user->user_email)){
                                        $to = $rows_user->user_email;
                                        $subject = get_site_option('rt_aff_email_buy_daily_subject');
                                        $message = get_site_option('rt_aff_email_buy_daily_message');

                                        $message = str_replace( '{username}' , $rows_user->user_login , $message );

                                        $now = new DateTime();
                                        $message = str_replace( '{today}' , $now->format('d-m-Y') , $message );

                                        $message = str_replace( '{summary}' , $msg , $message );
                                        
                                        add_filter( 'wp_mail_content_type', array ( $this , 'set_content_type' )  );
                                        wp_mail( $to, $subject, $message );
                                        remove_filter( 'wp_mail_content_type', array ( $this , 'set_content_type' ) );        
                                    }
                                }
                            }
                        }
                }
                
                public
                        function set_referer_cookie () {
                        global $wpdb  ;

                        if ( !isset ( $_SESSION ) ) {
                                session_start () ;
                        }

                        /*
                         * if this is from affiliate referer
                         */
                        if ( isset ( $_GET[ 'ref' ] ) ) {
                                $landing_page=(is_ssl () ? 'https://' : 'http://') . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] ;

                                $redirect_link=remove_query_arg ( 'ref' , $landing_page ) ;

                                /*
                                 * check referer's usermname is valid
                                 */
                                $sql=$wpdb->prepare ( "SELECT ID FROM  $wpdb->users WHERE user_login = %s" , trim ( $_GET[ 'ref' ] ) ) ;
                                $row=$wpdb->get_row ( $sql ) ;

                                /*
                                 * if user name found in users table
                                 */
                                if ( $row ) {

                                        $set_cookies_flag=true ;
                                        if ( isset ( $_SERVER[ 'HTTP_REFERER' ] ) ) {
                                                $url_data   =parse_url ( $landing_page ) ;
                                                $domain_name=$url_data[ "host" ] ;
                                                $sql        =$wpdb->prepare ( "SELECT * FROM  {$wpdb->prefix}rt_aff_users_domain WHERE domain_name = %s and user_id = %d" , trim ( $domain_name ) , $row->ID ) ;
                                                $d_row      =$wpdb->get_row ( $sql ) ;
                                                if ( !$d_row ) {
                                                        $wpdb->insert ( $wpdb->prefix . "rt_aff_users_domain" , array ( "user_id"=>$row->ID , "domain_name"=>$domain_name , "status"=>'y' ) , array ( '%d' , '%s' , '%s' ) ) ;
                                                }
                                                else {
                                                        if ( isset ( $d_row->status )&&( $d_row->status=="n" ) ) {
                                                                $set_cookies_flag=false ;
                                                        }
                                                        else {
                                                                $wpdb->update ( $wpdb->prefix . "rt_aff_users_domain" , array ( 'count'=>$d_row->count+1 ) , array ( "user_id"=>$row->ID , "domain_name"=>$domain_name ) , array ( '%d' ) , array ( '%d' , '%s' ) ) ;
                                                        }
                                                }
                                        }
                                        else {
                                                //To stop direct access
                                        }
                                        if ( $set_cookies_flag ) {
                                                /*
                                                 * set cookies
                                                 */
                                                setcookie ( 'rt_aff_username' , $_GET[ 'ref' ] , time ()+( 30*24*3600 ) , SITECOOKIEPATH ) ; //, "/", str_replace('http://www','',get_bloginfo('url')));
                                                setcookie ( 'rt_aff_user_id' , $row->ID , time ()+( 30*24*3600 ) , SITECOOKIEPATH ) ;
                                                //setcookie ($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null) ;

                                                /*
                                                 * save referer's user_id in session also
                                                 */
                                                $_SESSION[ 'rt_aff_user_id' ]=$row->ID ;

                                                    $wpdb->insert ( $wpdb->prefix . "rt_aff_users_referals" , array ( "user_id"      =>$row->ID ,
                                                            "referred_from"=>$_SERVER[ 'HTTP_REFERER' ] ,
                                                            'ip_address'   =>$_SERVER[ 'REMOTE_ADDR' ] ,
                                                            'landing_page' =>$landing_page ,
                                                            'date'         =>current_time ( 'mysql' ) ) , array ( '%d' , '%s' , '%s' , '%s' , '%s' ) ) ;
                                                    /*
                                                     * save referal's id in session also
                                                 */
                                                $_SESSION[ 'rt_aff_referal_id' ]=$wpdb->insert_id ;
                                                
                                        }
                                        /* Sending mail on click/visit if referal user has set option*/
                                        $sql_pay  = $wpdb -> prepare ( "SELECT click_notify,frequently  FROM " . $wpdb -> prefix . "rt_aff_payment_info where user_id = %d " , $row->ID ) ;
                                        $rows_pay = $wpdb -> get_row ( $sql_pay ) ; 
                                        
                                        if( $rows_pay->click_notify == 1 && $rows_pay->frequently == 1 ){
                                            
                                           $sql_user  = $wpdb -> prepare ( "SELECT user_login, user_email  FROM " . $wpdb -> prefix . "users where ID = %d " , $row->ID ) ;
                                           $rows_user = $wpdb -> get_row ( $sql_user ) ;  
                                            
                                           if(!empty($rows_user->user_email)){
                                                $to = $rows_user->user_email;
                                                $subject = get_site_option('rt_aff_email_click_subject');
                                                $message = get_site_option('rt_aff_email_click_message');
                                                
                                                $message = str_replace( '{username}' , $rows_user->user_login , $message );
                                                
                                                if( $_SERVER[ 'HTTP_REFERER' ] == '' ){
                                                    $message = str_replace( 'from' , '' , $message );
                                                    $message = str_replace( '{referred_link}' , '' , $message );
                                                }
                                                else
                                                    $message = str_replace( '{referred_link}' , $_SERVER[ 'HTTP_REFERER' ] , $message );
                                                
                                                $message = str_replace( '{ip_address}' , $_SERVER[ 'REMOTE_ADDR' ] , $message );
                                                $message = str_replace( '{date}' , current_time ( 'mysql' ) , $message );
                                                
                                                add_filter( 'wp_mail_content_type', array ( $this , 'set_content_type' )  );
                                                wp_mail( $to, $subject, $message );
                                                remove_filter( 'wp_mail_content_type', array ( $this , 'set_content_type' ) );
                                            }                                                
                                        }
                                        header ( "Location: " . $redirect_link ) ;
                                        exit ;
                                }
                        }
                        
                }
                
                public
                        function store_order_meta_referer_info ( $order_id , $detail ) {
                        
                        global $wpdb , $woocommerce ;
                        $rt_ref_affiliate='' ;
                        $commision = '';
                        $total_cart_amount = round($woocommerce->cart->total);
                        $plan_type = 0;
                        if( is_user_logged_in() )
                            $currentuserid = get_current_user_id();
                        
                        if ( isset ( $_COOKIE[ 'rt_aff_username' ] ) )
                                $rt_ref_affiliate .= $_COOKIE[ 'rt_aff_username' ] . ', ' ;
                        $affiliate_user =  $this->get_affiliate_user_for_commision();
                        //get affilate user from cookie or meta 
                        if ( $affiliate_user ) {
                                $rt_ref_affiliate .= $affiliate_user ;
                                $comment    = 'Order #' . $order_id;
                                
                                $sql_pay  = $wpdb -> prepare ( "SELECT affiliate_plan, buy_notify,frequently FROM " . $wpdb -> prefix . "rt_aff_payment_info where user_id = %d " , $affiliate_user ) ;
                                $rows_pay = $wpdb -> get_row ( $sql_pay ) ;
                                
                                // if Recurring 
                                if( $rows_pay->affiliate_plan == 2 ){
                                    $plan_type = 2;
                                    update_user_meta($currentuserid, 'rt_aff_referred_by', $affiliate_user);
                                
                                    $commision = round ( $woocommerce->cart->total*(get_option ( 'rt_aff_plan_commision' , 20 )/100) , 2 );
                                } else { // One Time Plan
                                    $plan_type = 1;
                                    $commision = round ( $woocommerce->cart->total*(get_option ( 'rt_aff_onetime_commission' , 50 )/100) , 2 );
                                }
                                                               
                                $result = $wpdb->insert (
                                        $wpdb->prefix . "rt_aff_transaction" , array (
                                        'txn_id'        =>$order_id ,
                                        'user_id'       =>$affiliate_user ,
                                        'type'          =>'earning' ,
                                        'approved'      =>0 ,
                                        'amount'        => $commision ,
                                        'payment_method'=>$detail[ 'payment_method' ] ,
                                        'note'          =>$comment ,
                                        'date'          => get_post_field ( 'post_date_gmt' , $order_id ))
                                        , array ( '%d' , '%d' , '%s' , '%d' , '%s' , '%s' , '%s' , '%s' )
                                ) ;                                
                        
                                $result1 = $wpdb->insert (
                                        $wpdb->prefix . "rt_aff_buy_summary" , array (
                                        'user_id'       =>$affiliate_user ,
                                        'cart_amount'      => $total_cart_amount ,
                                        'commision'        => $commision ,
                                        'date'          => get_post_field ( 'post_date_gmt' , $order_id ))
                                        , array ( '%d' , '%d' , '%d' , '%s' )
                                ) ;
                                
                                /*
                                // Immediately on buying 
                                if( $rows_pay->buy_notify == 1 && $rows_pay->frequently == 1 ){

                                   $sql_user  = $wpdb -> prepare ( "SELECT user_login, user_email  FROM " . $wpdb -> prefix . "users where ID = %d " , $affiliate_user ) ;
                                   $rows_user = $wpdb -> get_row ( $sql_user ) ;  
                                   
                                   if(!empty($rows_user->user_email)){
                                        $to = $rows_user->user_email;
                                        $subject = get_site_option('rt_aff_email_buy_subject');
                                        $message = get_site_option('rt_aff_email_buy_message');

                                        $message = str_replace( '{username}' , $rows_user->user_login , $message );
                                        $message = str_replace( '{currency}' , get_woocommerce_currency_symbol() , $message );                                        
                                        $message = str_replace( '{total_cart_amount}' , $total_cart_amount , $message );
                                        
                                        if( $plan_type == 1 ){
                                            $percent = get_option( 'rt_aff_onetime_commission');
                                            $plan = '"One Time Plan ('. $percent  .'%)"';
                                        }
                                        else{
                                            $percent = get_option( 'rt_aff_plan_commision');
                                            $plan = '"Recurring Plan ('. $percent  .'%)"';
                                        }                                            
                                        
                                        $message = str_replace( '{plan_type}' , $plan , $message );                                        
                                        $message = str_replace( '{commision}' , $commision , $message );

                                        add_filter( 'wp_mail_content_type', array ( $this , 'set_content_type' )  );
                                        wp_mail( $to, $subject, $message );
                                        remove_filter( 'wp_mail_content_type', array ( $this , 'set_content_type' ) );        
                                    }                                                
                                }
                                */
                                
                                /*
                                // Daily on buying
                                if( $rows_pay->buy_notify == 1 && $rows_pay->frequently == 2 ){

                                    $sql_user_buy  = $wpdb -> prepare ( "SELECT user_id FROM " . $wpdb -> prefix . "rt_aff_buy_summary where date(`date`)= date(now()) group by user_id" ) ;
                                    $rows_user_buy = $wpdb -> get_results( $sql_user_buy ) ;  
                                                                            
                                    foreach ($rows_user_buy as $buyer_row) {                                        
                                        
                                        $sql_user_buy_row  = $wpdb -> prepare ( "SELECT * FROM " . $wpdb -> prefix . "rt_aff_buy_summary where date(`date`)= date(now()) and user_id= %d" , $buyer_row->user_id ) ;
                                        $rows_user_buy_row = $wpdb -> get_results( $sql_user_buy_row ) ;  
                                        
                                        
                                        $msg = '';
                                        $buyer_id = 0;
                                        $total_user_amount = 0;
                                        $total_user_commision = 0;
                                        
                                        $msg = "<table>
                                                    <tr>
                                                        <th> Cart Amount</th>
                                                        <th> Your Commission </th>
                                                    </tr>";
                                        foreach($rows_user_buy_row as $user_buy){
                                            $buyer_id = $user_buy->user_id;
                                            $msg += "<tr>
                                                        <td>". $user_buy->cart_amount ."</td>
                                                        <td>". $user_buy->commision ."</td>
                                                    </tr>";
                                            $total_user_amount += $user_buy->cart_amount;
                                            $total_user_commision += $user_buy->commision;                                            
                                        }                
                                        $msg += "   <tr>
                                                        <td> Total Cart Amount:". $total_user_amount . " </td>
                                                        <td> Total Commission:". $total_user_commision . " </td>
                                                    </tr>
                                                </table>";
                                        
                                        $sql_user  = $wpdb -> prepare ( "SELECT user_login, user_email  FROM " . $wpdb -> prefix . "users where ID = %d " , $buyer_id ) ;
                                        $rows_user = $wpdb -> get_row ( $sql_user ) ;  

                                        if(!empty($rows_user->user_email)){
                                            $to = $rows_user->user_email;
                                            $subject = get_site_option('rt_aff_email_buy_daily_subject');
                                            $message = get_site_option('rt_aff_email_buy_daily_message');

                                            $message = str_replace( '{username}' , $rows_user->user_login , $message );

                                            $now = new DateTime();
                                            $message = str_replace( '{today}' , $now->format('d-m-Y') , $message );
                                            
                                            $message = str_replace( '{summary}' , $msg , $message );

                                            add_filter( 'wp_mail_content_type', array ( $this , 'set_content_type' )  );
                                            wp_mail( $to, $subject, $message );
                                            remove_filter( 'wp_mail_content_type', array ( $this , 'set_content_type' ) );        
                                        }
                                    }
                                                                                   
                                }
                                */
                                
                                // Removing Cookie
                                setcookie('rt_aff_username', "", time() - 3600, SITECOOKIEPATH);
                                setcookie('rt_aff_user_id', "", time() - 3600, SITECOOKIEPATH);
                                
                                if ( $rt_ref_affiliate )
                                    update_post_meta ( $order_id , '_rt-ref-affiliate' , $rt_ref_affiliate ) ;
                        }
                }
                
                function set_content_type( $content_type ){
                        return 'text/html';
                }
                
                function get_affiliate_user_for_commision(){
                    
                    if( isset ( $_COOKIE[ 'rt_aff_user_id' ] ) ){                        
                        return  $_COOKIE[ 'rt_aff_user_id' ] ;
                    }else{
                        // User meta
                        if( is_user_logged_in() ){
                            
                            $referredid = get_user_meta(get_current_user_id(),'rt_aff_referred_by', true);
                        
                            if( $referredid != NULL )
                                return $referredid;
                            else                     
                                return false;
                        }
                    }                    
                }
                
        }
}
?>
