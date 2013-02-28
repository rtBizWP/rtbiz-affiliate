<?php

/**
 *  PLUGING ACTIVATION
 */
function rt_affiliate_activate() {
    global $wpdb;

    $table_rt_aff_contact_details = $wpdb->prefix . 'rt_aff_contact_details';
    $table_rt_aff_users_referals = $wpdb->prefix . 'rt_aff_users_referals';
    $table_rt_aff_payment_info = $wpdb->prefix . 'rt_aff_payment_info';
    $table_rt_aff_transactions = $wpdb->prefix . 'rt_aff_transaction';

    /*
     * detects if this is a new installation or simply an update.
     */
    $new_installation = $wpdb->get_var("show tables like '$table_rt_aff_contact_details'");

    if (!$new_installation) {
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
                    `payment_method` VARCHAR(45) NULL ,
                    `paypal_email` VARCHAR(200) NULL ,
                    `check_address` VARCHAR(200) NULL ,
                    `bank_details` VARCHAR(200) NULL ,
                    `min_payout` INT NULL
		);";

        $sql .= "CREATE TABLE " . $table_rt_aff_transactions . " (
                    id int NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                    `txn_id` VARCHAR(100) NULL ,
                    `user_id` INT NULL ,
                    `type` VARCHAR(100) NULL ,
                    `amount` float(5,2) NULL ,
                    `payment_method`  VARCHAR(100) NULL,
                    `approved` BOOLEAN NOT NULL DEFAULT  '1', 
                    `note`  TEXT NULL,
                    `date` DATETIME NULL
		);";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
}

/**
 *  PLUGING DEACTIVATION
 */
function rt_affiliate_deactivate() {
    //
}

/**
 *   PLUGING UNINSTALLATION
 */
function rt_affiliate_uninstall() {
    global $wpdb;

    if (( get_option('rt_affiliate_version') == RT_AFFILIATE_VERSION)) {

        $table_rt_aff_contact_details = $wpdb->prefix . 'rt_aff_contact_details';
        $table_rt_aff_users_referals = $wpdb->prefix . 'rt_aff_users_referals';
        $table_rt_aff_payment_info = $wpdb->prefix . 'rt_aff_payment_info';
        $table_rt_aff_transactions = $wpdb->prefix . 'rt_aff_transaction';

        if ($wpdb->get_var("SHOW TABLES LIKE '" . $table_rt_aff_contact_details . "'") == $table_rt_aff_contact_details) {
            $wpdb->query("DROP TABLE " . $table_rt_aff_contact_details . ", " . $table_rt_aff_users_referals . ", " . $table_rt_aff_payment_info . ", " . $table_rt_aff_transactions);
        }
    }
}

//add_action('admin_menu', 'rt_affiliate_menu', 12);
/**
 * CREATE ADMIN MENU
 */
function rt_affiliate_menu() {
    add_menu_page('Affiliate Admin', 'Affiliate Admin', 'manage_options', RT_AFFILIATE_HANDLER, '', '');
    add_submenu_page(RT_AFFILIATE_HANDLER, 'Submission', 'Submission', 'manage_options', RT_AFFILIATE_HANDLER, 'rt_affiliate_admin_options_html');
    add_submenu_page(RT_AFFILIATE_HANDLER, 'Email Setting', 'Email Setting', 'manage_options', 'email_setting', 'rt_affiliate_options_email_setting');
    echo $payment_page = add_submenu_page(RT_AFFILIATE_HANDLER, 'Manage Payment', 'Manage Payment', 'manage_options', 'manage_payment', 'rt_affiliate_options_manage_payment');
    add_submenu_page(RT_AFFILIATE_HANDLER, 'Manage Banners', 'Manage Banners', 'manage_options', 'manage_banners', 'rt_affiliate_manage_banners');
    add_action('admin_print_styles-' . $payment_page, 'rt_affiliate_options_load_payment_css');
    add_action('admin_print_styles', 'rt_affiliate_options_load_admin_css');

    add_menu_page('Affiliate', 'Affiliate', 'read', RT_AFFILIATE_HANDLER_USER, '', '');
    add_submenu_page(RT_AFFILIATE_HANDLER_USER, 'Stats & History', 'Stats & History', 'read', RT_AFFILIATE_HANDLER_USER, 'rt_affiliate_stats');
    add_submenu_page(RT_AFFILIATE_HANDLER_USER, 'Get Links & Banners', 'Get Links & Banners', 'read', 'links_banners', 'rt_affiliate_links_banners');
    add_submenu_page(RT_AFFILIATE_HANDLER_USER, 'Payment Info', 'Payment Info', 'read', 'payment_info', 'rt_affiliate_payment_info');
}

//add_action( 'wp_print_styles', 'rt_affiliate_options_load_css' );
/**
 *  INCLUDE CSS
 */
function rt_affiliate_options_load_css() {
    if (!is_admin())
        wp_enqueue_style('rt_style', RT_AFFILIATE_URL . '/css/rt_style.css');
}

function rt_affiliate_options_load_payment_css() {
    wp_enqueue_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
}

function rt_affiliate_options_load_admin_css() {
    wp_enqueue_style('rt-affiliate-admin', RT_AFFILIATE_URL . '/css/admin.css');
}

//add_action( 'wp_print_scripts', 'rt_affiliate_options_load_js' );
/**
 *  INCLUDE JS ON FRONT SIDE
 */
function rt_affiliate_options_load_js() {
    /*
     * for only admin's manage_payment page
     */
    if (strpos($_SERVER['REQUEST_URI'], 'rt-affiliate-user') !== false || strpos($_SERVER['REQUEST_URI'], 'manage_payment') !== false) {
        //wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-api', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');
        wp_enqueue_script('jquery-ui-api', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js');
        wp_enqueue_script('rt-affiliate-js-admin', RT_AFFILIATE_URL . '/js/rt-affiliate-admin.js');
    }
    if (!is_admin()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('rt-jquery-validate', RT_AFFILIATE_URL . '/js/jquery.validate.js');
        wp_enqueue_script('rt-affiliate-js', RT_AFFILIATE_URL . '/js/rt-affiliate.js');
    }
}

//add_shortcode('rt_affiliate_contact_form', 'rt_affiliate_contact_form');
/**
 *  CONTACT FORM AND ITS SHORT CODE
 */
function rt_affiliate_contact_form() {
    global $rt_aff_error;
    ?>
    <form id="rt_aff_contact" action="<?php echo get_permalink(); ?>" method="post">
        <h2> Contact </h2>
        <div id="rt_aff_msg">
            <?php
            if (!empty($_POST) && isset($_SESSION['rt_msg'])) {
                echo $_SESSION['rt_msg'];
            }
            ?>
        </div>

        <fieldset>
            <ul id="rt_aff_list">
                <li>
                    <label for="clientname">Your Name</label>
                    <input type="text" class="regular-text" value="<?php if (isset($_POST['rt_aff_clientname'])) echo $_POST['rt_aff_clientname'] ?>" id="clientname" name="rt_aff_clientname">
                    <?php if (isset($rt_aff_error['name_error'])) echo $rt_aff_error['name_error']; ?>
                </li>
                <li>
                    <label for="email">Your Email</label>
                    <input type="text" class="regular-text" value="<?php if (isset($_POST['rt_aff_email'])) echo $_POST['rt_aff_email'] ?>" id="email" name="rt_aff_email">
                    <?php if (isset($rt_aff_error['email_error'])) echo $rt_aff_error['email_error']; ?>
                </li>
                <li>
                    <label for="blog_url">Blog URL</label>
                    <input type="text" class="regular-text" value="<?php if (isset($_POST['rt_aff_blog_url'])) echo $_POST['rt_aff_blog_url'] ?>" id="blog_url" name="rt_aff_blog_url">
                    <?php if (isset($rt_aff_error['blog_url_error'])) echo $rt_aff_error['blog_url_error']; ?>

                </li>
                <li><label>Services</label>
                    <ul>
                        <li>
                            <input type="checkbox" class="checkbox" value="yes" <?php if (isset($_POST['rt_aff_b2w'])) echo 'checked'; ?> id="b2w" name="rt_aff_b2w">
                            <label for="b2w">Blogger to WordPress Migration</label>
                        </li>
                        <li>
                            <input type="checkbox" class="checkbox" value="yes" <?php if (isset($_POST['rt_aff_wp_theme'])) echo 'checked'; ?> id="wp_theme" name="rt_aff_wp_theme">
                            <label for="wp_theme">WordPress Theme</label>
                            <ul id="show_hide">
                                <li>
                                    <input type="radio" class="radio" value="blog_layout" <?php if ($_POST['rt_aff_theme'] == 'blog_layout') echo 'checked'; ?> id="blog_layout" name="rt_aff_theme">
                                    <label for="blog_layout"> Matching my Blogger layout</label>
                                </li>
                                <li>
                                    <input type="radio" class="radio" value="new_theme" <?php if ($_POST['rt_aff_theme'] == 'new_theme') echo 'checked'; ?> id="new_theme" name="rt_aff_theme">
                                    <label for="new_theme">New WordPress theme</label>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <input type="checkbox" class="checkbox" value="yes" <?php if (isset($_POST['rt_aff_webhosting'])) echo 'checked'; ?> id="webhosting" name="rt_aff_webhosting">
                            <label for="webhosting">Webhosting</label>
                        </li>
                    </ul>
                </li>
                <li>
                    <label id="for_commnet" for="comment">Comment  </label>
    <!--                <small>(any questions)</small> -->
                    <textarea class="textarea" id="comment" name="rt_aff_comment"><?php if (isset($_POST['rt_aff_comment'])) echo $_POST['rt_aff_comment']; ?></textarea>
                </li>
                <li id="lrt_aff_full_name_field">
                    <label id="lrt_aff_full_name" for="rt_aff_full_name">Keep this field blank</label>
                    <input type="text" name="rt_aff_full_name" value="" id="rt_aff_full_name">
                </li>
                <?php
                if (!isset($_SESSION['rt_aff_user_id'])) {
                    if (isset($_COOKIE['rt_aff_user_id']))
                        $referar = $_COOKIE['rt_aff_user_id'];
                    if (isset($_COOKIE['rt_aff_username']))
                        $referar_username = $_COOKIE['rt_aff_username'];
                }
                else {
                    $referar = $_SESSION['rt_aff_user_id'];
                    $referar_username = $_COOKIE['rt_aff_username'];
                }
                ?>
                <li>
                    <label id="for_referred_by" for="referred_by">Referred By</label>
                    <input type="text" class="regular-text" value="<?php if (isset($_POST['rt_aff_referred_by'])) echo $_POST['rt_aff_referred_by']; else if (isset($referar_username)) echo $referar_username; ?>" id="referred_by" name="rt_aff_referred_by">
                </li>
                <input type="hidden" value="<?php echo $_SESSION['rt_aff_referal_id']; ?>" name="rt_aff_referal_id"/>
                <input type="hidden" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" name="rt_aff_ip_address"/>
                <input type="hidden" value="<?php echo urlencode(serialize($_SESSION['browser_history'])); ?>" name="rt_aff_browser_history" />
                <?php wp_nonce_field('rtaff123'); ?>
                <li>
                    <input type="submit" name="rt_affiliate_contact" value="Submit" id="rt_affiliate_submit"/>
                </li>
            </ul>
        </fieldset>
    </form>
    <?php
}

//add_action('init', 'rt_affiliate_referer');
/**
 *  START SESSION AND TRACK BROWSER HISTORY IN SESSION
 *  ALSO SAVE REFERAL DATA TO COOKIES
 *  HANDLE CONTACT FORM'S POST DATA
 */
function rt_affiliate_referer() {
    global $wpdb, $rt_aff_error;
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['browser_history']) || !is_array($_SESSION['browser_history'])) {
        $_SESSION['browser_history'] = array();
        if (isset($_SERVER['HTTP_REFERER']))
            $_SESSION['browser_history'][] = $_SERVER['HTTP_REFERER'];
    }
    if (end($_SESSION['browser_history']) != "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) {
        $_SESSION['browser_history'][] = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }

    /*
     * if this is from affiliate referer
     */
    if (isset($_GET['ref'])) {
        $landing_page = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        /*
         * code to get page-redirect link
         */
//        if( count( $_GET ) == 1 ) {
//            $needle = '?ref';
//        }else {
//            $needle = '&ref';
//        }

        $needle = '?ref';
        $redirect_link = substr($landing_page, 0, strpos($landing_page, $needle));

        /*
         * check refrrer's usermname is valid
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


    /*
     * HANDLE CONTACT FORM DETAILS
     */
    if ($_POST && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'rtaff123')) {

        //if this is spam submission
        preg_match('@^(?:http://)?([^/]+)@i', $_SERVER['HTTP_REFERER'], $aff_http_ref_domain);

        if ($_POST['rt_aff_full_name'] != '' || $aff_http_ref_domain[0] != get_bloginfo('url')) {
            $_SESSION['rt_msg'] = 'I am sorry, but this message appears to be spam.';
            //send spam mail to developer, santosh
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\r\n";
            $headers .= 'From: b2w <b2w@blogerrstowp.com>' . "\r\n";
            wp_mail('santosh.kamble@rtcamp.com', 'B2W:spam', http_build_query($_POST), $headers);
            return;
        }

        $error = 0;
        //if ( !isset( $_POST['rt_aff_email'] ) || !eregi("^[a-zA-Z ]", $_POST['rt_aff_clientname'])) {
        if (!isset($_POST['rt_aff_clientname']) || trim($_POST['rt_aff_clientname']) == '') {
            $rt_aff_error['name_error'] = '<label for="clientname" generated="true" class="error">Please enter a valid name.</label>';
            $error = 1;
        }
        //if ( !isset( $_POST['rt_aff_email'] ) || !eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $_POST['rt_aff_email'])) {
        if (!isset($_POST['rt_aff_email']) || !filter_var($_POST['rt_aff_email'], FILTER_VALIDATE_EMAIL)) {
            $rt_aff_error['email_error'] = '<label for="email" generated="true" class="error">Please enter a valid email address.</label>';
            $error = 1;
        }

        $pattern = "/^(?#Protocol)(?:(?:ht|f)tp(?:s?)\:\/\/|~\/|\/)?(?#Username:Password)(?:\w+:\w+@)?(?#Subdomains)(?:(?:[-\w]+\.)+(?#TopLevel Domains)(?:com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum|travel|[a-z]{2}))(?#Port)(?::[\d]{1,5})?(?#Directories)(?:(?:(?:\/(?:[-\w~!$+|.,=]|%[a-f\d]{2})+)+|\/)+|\?|#)?(?#Query)(?:(?:\?(?:[-\w~!$+|.,*:]|%[a-f\d{2}])+=?(?:[-\w~!$+|.,*:=]|%[a-f\d]{2})*)(?:&(?:[-\w~!$+|.,*:]|%[a-f\d{2}])+=?(?:[-\w~!$+|.,*:=]|%[a-f\d]{2})*)*)*(?#Anchor)(?:#(?:[-\w~!$+|.,*:=]|%[a-f\d]{2})*)?$/i";

//        if ( !isset( $_POST['rt_aff_blog_url'] ) || !eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $_POST['rt_aff_blog_url'])) {
//        if ( !isset( $_POST['rt_aff_blog_url'] ) || !filter_var( $_POST['rt_aff_blog_url'], FILTER_VALIDATE_URL ) ) {
//        if ( !isset( $_POST['rt_aff_blog_url'] ) || !eregi($pattern, $_POST['rt_aff_blog_url']) ) {
        if (!isset($_POST['rt_aff_blog_url']) || !preg_match_all($pattern, $_POST['rt_aff_blog_url'], $r)) {
            $rt_aff_error['blog_url_error'] = '<label for="blog_url" generated="true" class="error">Please enter a valid URL.</label>';
            $error = 1;
        }
        if (!$error) {
            if (!isset($_POST['rt_aff_b2w']))
                $_POST['rt_aff_b2w'] = 'no';
            if (!isset($_POST['rt_aff_theme']))
                $_POST['rt_aff_theme'] = 'no';
            if (!isset($_POST['rt_aff_webhosting']))
                $_POST['rt_aff_webhosting'] = 'no';

            /*
             * check refrrer's usermname is valid
             */
            $sql_ref_user = "SELECT ID FROM " . $wpdb->users . " WHERE user_login = '" . trim($_POST['rt_aff_referred_by']) . "'";
            $row_ref_user = $wpdb->get_row($sql_ref_user);

            $uid = 0;
            if ($row_ref_user != NULL)
                $uid = $row_ref_user->ID;

            $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_contact_details
                (`users_referal_id`, `referred_by`, `name`, `email`, `blog_url`, `service_b2w_migration`, `service_wp_theme`, `service_hosting`, `cust_comment`, `ip_address`, `browsing_history`, `project_status`, `date_contacted`, `date_update`) VALUES
                ( '" . $_POST['rt_aff_referal_id'] . "', '" . $uid . "', '" . $_POST['rt_aff_clientname'] . "', '" . $_POST['rt_aff_email'] . "', '" . $_POST['rt_aff_blog_url'] . "', '" . $_POST['rt_aff_b2w'] . "', '" . $_POST['rt_aff_theme'] . "', '" . $_POST['rt_aff_webhosting'] . "', '" . $_POST['rt_aff_comment'] . "', '" . $_POST['rt_aff_ip_address'] . "', '" . $_POST['rt_aff_browser_history'] . "', 'contact_submitted', now(), now() )";
            $wpdb->query($sql);
            echo $track_id = $wpdb->insert_id;

            /*
             * list services
             */
            $services_list = '';
            if ($_POST['rt_aff_b2w'] == 'yes')
                $services_list .= '"Blogger to WordPress Migration",';
            if ($_POST['rt_aff_theme'] == 'blog_layout')
                $services_list .= '"Theme matching my blog layout",';
            if ($_POST['rt_aff_theme'] == 'new_theme')
                $services_list .= '"New WordPress theme",';
            if ($_POST['rt_aff_webhosting'] == 'yes')
                $services_list .= '"Webhosting"';

            /*
             * send mail to rtcamp sales
             */
            rt_affiliate_send_mail('to_sales', '', $_POST['rt_aff_clientname'], $_POST['rt_aff_blog_url'], $_SERVER['HTTP_REFERER'], $services_list, $track_id, $_POST['rt_aff_email'], $_POST['rt_aff_comment']);

            /*
             * send mail to client
             */
            rt_affiliate_send_mail('to_client', $_POST['rt_aff_email'], $_POST['rt_aff_clientname'], $_POST['rt_aff_blog_url'], $_SERVER['HTTP_REFERER'], $services_list, $track_id);

            /*
             * if refral is set, send email to him
             */
            if ($uid > 0) {
                rt_affiliate_send_mail('to_affiliate_user', $uid, $_POST['rt_aff_clientname'], $_POST['rt_aff_blog_url'], $_SERVER['HTTP_REFERER'], $services_list, $track_id);
            }

            $_SESSION['rt_msg'] = 'Form submitted successfully!';
        }
    }
}

/**
 * send mail to provide email in argument list
 * @global <type> $wpdb
 * @param <type> $type
 * @param <type> $to
 * @param <type> $customer_name
 * @param <type> $blog_url
 * @param <type> $ref_url
 * @param <type> $services_list
 * @param <type> $track_id
 * @param <type> $customer_email
 * @param <type> $customer_comment
 * @return <type>
 */
function rt_affiliate_send_mail($type, $to, $customer_name, $blog_url, $ref_url, $services_list, $track_id, $customer_email = '', $customer_comment = '') {
    global $wpdb;
    $rt_options = get_option('rt_affiliate_options');

    if ($type == 'to_affiliate_user') {
        $affiliate_user = get_userdata($to);
        $to = $affiliate_user->user_email;
        $affiliate_name = $affiliate_user->user_login;
        $rt_options = $rt_options['user'];
    } else if ($type == 'to_client') {
        $rt_options = $rt_options['customer'];
    } else if ($type == 'to_sales') {
        $rt_options = $rt_options['sales'];
        $to = $rt_options['rt_aff_to'];
    }

    /*
     * if send mail option is not enabled from admin then return
     * else continue
     */
    if ($rt_options['rt_aff_enable'] != 1)
        return;

    //$message = '<html><head><title></title></head><body>';
    $message = $rt_options['rt_aff_msg'];
    $message = str_replace('%customer_name%', $customer_name, $message);
    $message = str_replace('%affiliate_name%', $affiliate_name, $message);
    $message = str_replace('%blog_url%', $blog_url, $message);
    $message = str_replace('%ref_url%', $ref_url, $message);
    $message = str_replace('%services_list%', $services_list, $message);
    $message = str_replace('%track_id%', $track_id, $message);

    if ($type == 'to_sales') {
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

    if ($type == 'to_sales') {
        $subject = str_replace('%customer_email%', $customer_email, $subject);
        $subject = str_replace('%customer_comment%', $customer_comment, $subject);
    }

    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: ' . $rt_options['rt_aff_fromname'] . ' <' . $rt_options['rt_aff_from'] . '>' . "\r\n";
    if ($type == 'to_sales') {
        $headers .= 'Reply-To: ' . $customer_email . "\r\n";
        if ($rt_options['rt_aff_cc'] != '')
            $headers .= 'Cc: ' . $rt_options['rt_aff_cc'] . "\r\n";
        if ($rt_options['rt_aff_bcc'] != '')
            $headers .= 'Bcc: ' . $rt_options['rt_aff_bcc'] . "\r\n";
    }
    wp_mail($to, $subject, $message, $headers);
}

/**
 * redirect users of role subscriber, to affiliate stats page
 * @param <type> $redirect_to
 * @param <type> $request_redirect_to
 * @param <type> $user
 * @return <type>
 */
function rt_affiliate_login_redirect($redirect_to, $request_redirect_to, $user) {
    if ($user->caps['subscriber']) {
        return bloginfo('url') . '/wp-admin/admin.php?page=rt-affiliate-user';
    } else {
        return $redirect_to;
    }
}

function rt_affiliate_akismet_check($id) {
    global $wpdb, $akismet_api_host, $akismet_api_port;

    $sql = "Select name, email, blog_url, cust_comment from " . $wpdb->prefix . "rt_aff_contact_details where id = " . $id;
    $row = $wpdb->get_row($sql);
    $comment['user_ip'] = preg_replace('/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR']);
    $comment['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $comment['referrer'] = $_SERVER['HTTP_REFERER'];
    $comment['blog'] = get_option('home');
    $comment['blog_lang'] = get_locale();
    $comment['blog_charset'] = get_option('blog_charset');
    $comment['permalink'] = get_option('home');
    $comment['comment_type'] = 'comment';
    $comment['comment_author'] = $row->name;
    $comment['comment_author_email'] = $row->email;
    $comment['comment_author_url'] = $row->blog_url;
    $comment['comment_content'] = $row->cust_comment;

    $ignore = array('HTTP_COOKIE');

    foreach ($_SERVER as $key => $value)
        if (!in_array($key, $ignore) && is_string($value))
            $comment["$key"] = $value;

    $query_string = '';
    foreach ($comment as $key => $data)
        $query_string .= $key . '=' . urlencode(stripslashes($data)) . '&';

    $response = akismet_http_post($query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port);
    //akismet_http_post($request, $host, $path, $port = 80, $ip=null)
    return $response[1];
}

function rt_affiliate_woocommerce_add_refferral_info($order_id,$detail) {
    global $wpdb, $woocommerce;
    $rt_ref_affiliate = null;
    if (isset($_COOKIE['rt_aff_username']))
        $rt_ref_affiliate .= $_COOKIE['rt_aff_username'] . ', ';
    if (isset($_COOKIE['rt_aff_user_id'])) {
        $rt_ref_affiliate .= $_COOKIE['rt_aff_user_id'];
        $order_items = (array) maybe_unserialize( get_post_meta($order_id, '_order_items', true) );
        $comment = NULL;
        foreach ( $order_items as $item ) {
            $comment .= $item['name'].'<br /><br />';
        }
        $wpdb->insert(
                $wpdb->prefix . "rt_aff_transaction", array(
            'txn_id' => $order_id,
            'user_id' => $_COOKIE['rt_aff_user_id'],
            'type' => 'earning',
            'approved' => 0,
            'amount' => round($woocommerce->cart->total*(get_option('rt_aff_commission', 20)/100), 2),
            'payment_method' => $detail['payment_method'],
            'note' => $comment,
            'date' => date( "Y-m-d g:i:s" )
                )
        );
    }
    if ($rt_ref_affiliate)
        update_post_meta($order_id, 'rt-ref-affiliate', $rt_ref_affiliate);
}

?>