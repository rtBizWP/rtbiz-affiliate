<?php
//------------------------------------------------------------
// PLUGING ACTIVATION
//------------------------------------------------------------

function rt_affiliate_activate() {
    global $wpdb;

    $table_rt_aff_contact_details = $wpdb->prefix . 'rt_aff_contact_details';
    $table_rt_aff_users_referals = $wpdb->prefix . 'rt_aff_users_referals';
    $table_rt_aff_payment_info = $wpdb->prefix . 'rt_aff_payment_info';
    $table_rt_aff_transactions = $wpdb->prefix . 'rt_aff_transaction';

    //detects if this is a new installation or simply an update.
    $new_installation = $wpdb->get_var("show tables like '$table_rt_aff_contact_details'") ;

    if ( !$new_installation ) {
        $sql = "CREATE TABLE " . $table_rt_aff_contact_details . " (
                id int NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `users_referal_id` INT NULL ,
                `referred_by` INT NULL ,
                `name` VARCHAR(200) NULL ,
                `email` VARCHAR(200) NULL ,
                `blog_url` VARCHAR(200) NULL ,
                `service_b2w_migration` VARCHAR(50) NULL DEFAULT 'no' ,
                `service_wp_theme` VARCHAR(50) NULL DEFAULT 'no' COMMENT 'no\nblogger\nnew' ,
                `service_hosting` VARCHAR(50) NULL DEFAULT 'no' COMMENT 'no\nhostgator\ndreamhost\nrtcamp' ,
                `cust_comment` TEXT NULL ,
                `ip_address` VARCHAR(200) NULL ,
                `browsing_history` LONGTEXT NULL ,
                `project_status` VARCHAR(200) NULL COMMENT 'Client Submitted From\nAwaiting Reply from Client\nCustom Domain Setup \nWordPress Theme \nMigration is Process\nProject Completed\nProject Cancelled\nProject Deleted' ,
                `ac_link` VARCHAR(200) NULL ,
                `invoice_link` VARCHAR(200) NULL ,
                `date_contacted` DATETIME NULL ,
                `date_update` DATETIME NULL
		);";


        $sql .= "CREATE TABLE " . $table_rt_aff_users_referals . " (
                    id int NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `user_id` INT NULL ,
                    `referred_from` VARCHAR(200) NULL ,
                    `ip_address` VARCHAR(200) NULL ,
                    `landing_page` VARCHAR(200) NULL ,
                    `date` DATETIME NULL
		);";

        $sql .= "CREATE TABLE " . $table_rt_aff_payment_info . " (
                    id int NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `user_id` INT NULL ,
                    `payment_method` VARCHAR(45) NULL DEFAULT 'paypal' COMMENT 'paypal,\ncheck,\nbank2bank' ,
                    `paypal_email` VARCHAR(200) NULL ,
                    `check_address` VARCHAR(200) NULL ,
                    `bank_details` VARCHAR(200) NULL ,
                    `min_payout` INT NULL
		);";

        $sql .= "CREATE TABLE " . $table_rt_aff_transactions . " (
                    id int NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `txn_id` VARCHAR(100) NULL ,
                    `user_id` INT NULL ,
                    `type` VARCHAR(100) NULL COMMENT 'payment\nearnnig' ,
                    `amount` INT NULL ,
                    `payment_method`  VARCHAR(100) NULL,
                    `note`  TEXT NULL,
                    `date` DATETIME NULL
		);";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

//------------------------------------------------------------
// PLUGING DEACTIVATION
//------------------------------------------------------------
function rt_affiliate_deactivate() {
    //
}

//------------------------------------------------------------
//  PLUGING UNINSTALLATION
//------------------------------------------------------------
function rt_affiliate_uninstall() {
    global $wpdb;

    if ((get_option( 'rt_affiliate_version' ) == RT_AFFILIATE_VERSION )) {

        $table_rt_aff_contact_details = $wpdb->prefix . 'rt_aff_contact_details';
        $table_rt_aff_users_referals = $wpdb->prefix . 'rt_aff_users_referals';
        $table_rt_aff_payment_info = $wpdb->prefix . 'rt_aff_payment_info';
        $table_rt_aff_transactions = $wpdb->prefix . 'rt_aff_transaction';

        if ($wpdb->get_var("SHOW TABLES LIKE '".$table_rt_aff_contact_details."'") == $table_rt_aff_contact_details) {
            $wpdb->query( "DROP TABLE ".$table_rt_aff_contact_details.", ".$table_rt_aff_users_referals.", ".$table_rt_aff_payment_info.", ".$table_rt_aff_transactions );
        }
    }
}

//------------------------------------------------------------
//  CREATE ADMIN MENU
//------------------------------------------------------------
//add_action('admin_menu', 'rt_affiliate_menu', 12);
function rt_affiliate_menu() {
    add_menu_page('Contact Form', 'Contact Form', 'manage_options', RT_AFFILIATE_HANDLER , '', '');
    add_submenu_page(RT_AFFILIATE_HANDLER, 'Submission', 'Submission', 'manage_options', RT_AFFILIATE_HANDLER, 'rt_affiliate_admin_options_html');
    add_submenu_page(RT_AFFILIATE_HANDLER, 'Email Setting', 'Email Setting', 'manage_options', 'email_setting', 'rt_affiliate_options_email_setting');
    $payment_page = add_submenu_page(RT_AFFILIATE_HANDLER, 'Manage Payment', 'Manage Payment', 'manage_options', 'manage_payment', 'rt_affiliate_options_manage_payment');
    add_action('admin_print_styles-' . $payment_page, 'rt_affiliate_options_load_payment_css');

    add_menu_page('Affiliate', 'Affiliate', 'read', RT_AFFILIATE_HANDLER_USER , '', '');
    add_submenu_page(RT_AFFILIATE_HANDLER_USER, 'Stats & History', 'Stats & History', 'read', RT_AFFILIATE_HANDLER_USER, 'rt_affiliate_stats');
    add_submenu_page(RT_AFFILIATE_HANDLER_USER, 'Get Links & Banners', 'Get Links & Banners', 'read', 'links_banners', 'rt_affiliate_links_banners');
    add_submenu_page(RT_AFFILIATE_HANDLER_USER, 'Payment Info', 'Payment Info', 'read', 'payment_info', 'rt_affiliate_payment_info');
}

//------------------------------------------------------------
//  INCLUDE CSS
//------------------------------------------------------------
//add_action( 'wp_print_styles', 'rt_affiliate_options_load_css' );
function rt_affiliate_options_load_css() {
    if(!is_admin())
        wp_enqueue_style('rt_style', RT_AFFILIATE_URL.'/css/rt_style.css');
}

function rt_affiliate_options_load_payment_css() {
    wp_enqueue_style('jquery-ui-css','http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
}
//------------------------------------------------------------
//  INCLUDE JS ON FRONT SIDE
//------------------------------------------------------------
//add_action( 'wp_print_scripts', 'rt_affiliate_options_load_js' );
function rt_affiliate_options_load_js() {

    //for only admin's manage_payment page
    if ( strpos($_SERVER['REQUEST_URI'], 'rt-affiliate-user' ) !== false || strpos($_SERVER['REQUEST_URI'], 'manage_payment' ) !== false ) {
        //wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-api','http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');
        wp_enqueue_script('jquery-ui-api','http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js');

        wp_enqueue_script('rt-affiliate-js-admin',RT_AFFILIATE_URL.'/js/rt-affiliate-admin.js');
    }
    if(!is_admin()){
        wp_enqueue_script('jquery');
        wp_enqueue_script('rt-jquery-validate',RT_AFFILIATE_URL.'/js/jquery.validate.js');
        wp_enqueue_script('rt-affiliate-js',RT_AFFILIATE_URL.'/js/rt-affiliate.js');
    }
}

//************************************************************************************

//------------------------------------------------------------
//  CONTACT FORM AND ITS SHORT CODE
//------------------------------------------------------------
//add_shortcode('rt_affiliate_contact_form', 'rt_affiliate_contact_form');
function rt_affiliate_contact_form() {
    ?>
<form id="rt_aff_contact" action="<?php echo get_permalink();?>" method="post">
    <h2> Contact </h2>
     <div id="rt_aff_msg">
        <?php
        if(!empty($_POST) && isset($_SESSION['rt_msg'])){
            echo $_SESSION['rt_msg'];
        }
        ?>
    </div>
   
    <fieldset>

        <ul id="rt_aff_list">
            <li>
                <label for="clientname">Your Name</label>
                <input type="text" class="regular-text" value="" id="clientname" name="clientname">
                <span class="error"></span>
            </li>
            <li>
                <label for="email">Your Email</label>
                <input type="text" class="regular-text" value="" id="email" name="email">
                <span class="error"></span>
            </li>
            <li>
                <label for="blog_url">Blog URL</label>
                <input type="text" class="regular-text" value="" id="blog_url" name="blog_url">
                <span class="error"></span>
            </li>
            <li><label>Services</label>
                <ul>
                    <li>
                        <input type="checkbox" class="checkbox" value="yes" id="b2w" name="b2w">
                        <label for="b2w">Blogger to WordPress Migration</label>
                    </li>
                    <li>
                        <input type="checkbox" class="checkbox" value="yes" id="wp_theme" name="wp_theme">
                        <label for="wp_theme">WordPress Theme</label>
                        <ul id="show_hide">
                            <li>
                                <input type="radio" class="radio" value="blog_layout" id="blog_layout" checked name="theme">
                                <label for="blog_layout"> Matching my Blogger layout</label>
                            </li>
                            <li>
                                <input type="radio" class="radio" value="new_theme" id="new_theme" name="theme">
                                <label for="new_theme">New WordPress theme</label>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <input type="checkbox" class="checkbox" value="yes" id="webhosting" name="webhosting">
                        <label for="webhosting">Webhosting</label>
                    </li>
                </ul>
            </li>
            <li>
                <label id="for_commnet" for="comment">Comment  </label>
<!--                <small>(any questions)</small> -->
                <textarea class="textarea" id="comment" name="comment"></textarea>
            </li>
            <?php
            if(!isset($_SESSION['rt_aff_user_id'])) {
                $referar = $_COOKIE['rt_aff_user_id'];
                $referar_username = $_COOKIE['rt_aff_username'];
            }
            else {
                $referar = $_SESSION['rt_aff_user_id'];
                $referar_username = $_COOKIE['rt_aff_username'];
            }
            ?>
            <li>
                <label id="for_referred_by" for="referred_by">Referred By</label>
                <input type="text" class="regular-text" value="<?php echo $referar_username;?>" id="referred_by" name="referred_by">
            </li>
            <input type="hidden" value="<?php echo $_SESSION['rt_aff_referal_id'];?>" name="rt_aff_referal_id"/>
            <input type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR'];?>" name="ip_address"/>
            <input type="hidden" value="<?php echo urlencode(serialize($_SESSION['browser_history']));?>" name="browser_history" />
            <?php wp_nonce_field('rtaff123');?>
            <li>
                <input type="submit" name="rt_affiliate_contact" value="Submit" id="rt_affiliate_submit"/>
            </li>
    </ul>
    </fieldset>
</form>
    <?php
}

//------------------------------------------------------------
//  START SESSION AND TRACK BROWSER HISTORY IN SESSION
//  ALSO SAVE REFERAL DATA TO COOKIES
//  HANDLE CONTACT FORM'S POST DATA
//------------------------------------------------------------
//add_action('init', 'rt_affiliate_referer');

function rt_affiliate_referer() {
    global $wpdb;
    if (! isset($_SESSION) ) {
        session_start();
    }
    if (! isset($_SESSION['browser_history']) || ! is_array($_SESSION['browser_history']) ) {
        $_SESSION['browser_history'] = array();
        if(isset ($_SERVER['HTTP_REFERER']))
            $_SESSION['browser_history'][] = $_SERVER['HTTP_REFERER'];
    }
    if (end($_SESSION['browser_history']) != "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']) {
        $_SESSION['browser_history'][] = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    }

    //if this is from affiliate referer
    if(isset($_GET['ref'])){
        $landing_page = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        //code to get page-redirect link
        if( count($_GET) == 1 ) {
            $needle = '?ref';
        }else {
            $needle = '&ref';
        }
        $redirect_link = substr($landing_page, 0, strpos($landing_page, $needle));

        //check refrrer's usermname is valid
        $sql = "SELECT ID FROM ".$wpdb->prefix."users WHERE user_login = '".trim($_GET['ref'])."'";
        $row = $wpdb->get_row($sql);

        //if user name found in users table
        if($row){
            //set cookies
            setcookie('rt_aff_username', $_GET['ref'], time()+(30*24*3600)); //, "/", str_replace('http://www','',get_bloginfo('url')));
            setcookie('rt_aff_user_id', $row->ID, time()+(30*24*3600));

            //save referer's user_id in session also
            $_SESSION['rt_aff_user_id'] = $row->ID;

            $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_users_referals
                (`user_id`, `referred_from`, `ip_address`, `landing_page`, `date`)  VALUES
                ( $row->ID, '" . $_SERVER['HTTP_REFERER'] . "', '" .$_SERVER['REMOTE_ADDR']. "', '" .$landing_page. "', now() )";
            $wpdb->query($sql);

            //save referer's user_id in session also
            $_SESSION['rt_aff_referal_id'] = $wpdb->insert_id;

            header("Location: ".$redirect_link);
        }
    }

    //check refrrer's usermname is valid
        $sql_ref_user = "SELECT ID FROM ".$wpdb->prefix."users WHERE user_login = '".trim($_POST['referred_by'])."'";
        $row_ref_user = $wpdb->get_row($sql_ref_user);

        $uid = 0;
        if ( $row_ref_user != NULL ) $uid = $row_ref_user->ID;


    //  HANDLE CONTACT FORM DETAILS
    if($_POST && wp_verify_nonce( $_POST['_wpnonce'], 'rtaff123')){
        if(!isset($_POST['b2w'])) $_POST['b2w'] = 'no';
        if(!isset($_POST['theme'])) $_POST['theme'] = 'no';
        if(!isset($_POST['webhosting'])) $_POST['webhosting'] = 'no';

        $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_contact_details
            (`users_referal_id`, `referred_by`, `name`, `email`, `blog_url`, `service_b2w_migration`, `service_wp_theme`, `service_hosting`, `cust_comment`, `ip_address`, `browsing_history`, `project_status`, `date_contacted`, `date_update`) VALUES
            ( '" . $_POST['rt_aff_referal_id'] . "', '" . $uid . "', '" . $_POST['clientname'] . "', '" . $_POST['email'] . "', '" . $_POST['blog_url'] . "', '" . $_POST['b2w'] . "', '" . $_POST['theme'] . "', '" . $_POST['webhosting'] . "', '" . $_POST['comment'] . "', '" . $_POST['ip_address'] . "', '" . $_POST['browser_history'] . "', 'contact_submitted', now(), now() )";
        $wpdb->query($sql);
        $track_id = $wpdb->insert_id;

        //list services
        $services_list = '';
        if($_POST['b2w'] == 'yes')
            $services_list .= '"Blogger to WordPress Migration",';
        if($_POST['theme'] == 'blog_layout')
            $services_list .= '"Theme matching my blog layout",';
        if($_POST['theme'] == 'new_theme')
            $services_list .= '"New WordPress theme",';
        if($_POST['webhosting'] == 'yes')
            $services_list .= '"Webhosting"';

        //send mail to rtcamp sales
        rt_affiliate_send_mail('to_sales', '', $_POST['clientname'], $_POST['blog_url'], $_SERVER['HTTP_REFERER'], $services_list, $track_id, $_POST['email'], $_POST['comment'] );

        //send mail to client
        rt_affiliate_send_mail('to_client', $_POST['email'], $_POST['clientname'], $_POST['blog_url'], $_SERVER['HTTP_REFERER'], $services_list, $track_id);

        //if refral is set, send email to him
        if($_POST['referred_by_id']!=''){
            rt_affiliate_send_mail('to_affiliate_user', $_POST['referred_by_id'], $_POST['clientname'], $_POST['blog_url'], $_SERVER['HTTP_REFERER'], $services_list, $track_id);
        }

        $_SESSION['rt_msg'] = 'Form submitted successfully!';
    }
}

function rt_affiliate_send_mail($type, $to, $customer_name, $blog_url, $ref_url, $services_list, $track_id, $customer_email = '', $customer_comment = '' ) {
    global $wpdb;
    $rt_options = get_option('rt_affiliate_options');

    if($type == 'to_affiliate_user'){
        $affiliate_user = get_userdata($to);
        $to = $affiliate_user->user_email;
        $affiliate_name = $affiliate_user->user_login;
        $rt_options = $rt_options['user'];
    }
    else if($type == 'to_client'){
        $rt_options = $rt_options['customer'];
    }
    else if($type == 'to_sales'){
        $rt_options = $rt_options['sales'];
        $to = $rt_options['rt_aff_to'];

    }

    //if send mail option is not enabled from admin then return
    //else continue
    if($rt_options['rt_aff_enable'] != 1) return;

    //$message = '<html><head><title></title></head><body>';
    $message = $rt_options['rt_aff_msg'];
    $message = str_replace('%customer_name%', $customer_name, $message);
    $message = str_replace('%affiliate_name%', $affiliate_name, $message);
    $message = str_replace('%blog_url%', $blog_url, $message);
    $message = str_replace('%ref_url%', $ref_url, $message);
    $message = str_replace('%services_list%', $services_list, $message);
    $message = str_replace('%track_id%', $track_id, $message);

    if($type == 'to_sales'){
        $message = str_replace('%customer_email%', $customer_email, $message);
        $message = str_replace('%customer_comment%', $customer_comment, $message);
    }

    //$message .= '</body></html>';

    //$message = nl2br($message);

    $subject = $rt_options['rt_aff_subject'];
    $subject = str_replace('%customer_name%', $customer_name, $subject);
    $subject = str_replace('%affiliate_name%', $affiliate_name, $subject);
    $subject = str_replace('%blog_url%', $blog_url, $subject);
    $subject = str_replace('%ref_url%', $ref_url, $subject);
    $subject = str_replace('%services_list%', $services_list, $subject);
    $subject = str_replace('%track_id%', $track_id, $subject);

    if($type == 'to_sales'){
        $subject = str_replace('%customer_email%', $customer_email, $subject);
        $subject = str_replace('%customer_comment%', $customer_comment, $subject);
    }

    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: '.$rt_options['rt_aff_fromname'].' <'.$rt_options['rt_aff_from'].'>' . "\r\n";
    if($type == 'to_sales'){
        $headers .= 'Reply-To: '.$customer_email. "\r\n";
        if ( $rt_options['rt_aff_cc'] != '' )
            $headers .= 'Cc: '.$rt_options['rt_aff_cc'] . "\r\n";
        if ( $rt_options['rt_aff_bcc'] != '' )
        $headers .= 'Bcc: '.$rt_options['rt_aff_bcc'] . "\r\n";
    }
    wp_mail($to, $subject, $message, $headers);
}
?>
