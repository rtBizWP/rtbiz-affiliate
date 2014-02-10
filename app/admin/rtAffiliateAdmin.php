<?php
/**
 * Description of rtAffiliateAdmin
 *
 * @author Joshua Abenazer <joshua.abenazer@rtcamp.com>
 */
if (!class_exists('rtAffiliateAdmin')) {

    class rtAffiliateAdmin {

        public function __construct() {
            add_action('admin_enqueue_scripts', array($this, 'ui'));
            add_action('admin_menu', array($this, 'menu'), 12);
            add_action('wp_ajax_rt_affiliate_summary', array($this, 'affiliate_summary'));
            add_action('wp_ajax_rt_aff_users_lookup', array($this, 'users_lookup'));
            // WP 3.0+
            add_action('add_meta_boxes', array($this, 'order_referer_info'));
            // backwards compatible
            add_action('admin_init', array($this, 'order_referer_info'), 1);
            add_action('admin_init', array($this, 'payment_history_delete_check'), 1);
            add_action('admin_bar_menu', array($this, 'admin_nav'), 1);
        }
        function payment_history_delete_check(){
            if(isset($_REQUEST["page"]) && $_REQUEST["page"] == 'rt-affiliate-manage-payment'){
                global $wpdb;
                if(isset($_GET['action']) && $_GET['action'] == 'delete'){
                    $sql = "update {$wpdb->prefix}rt_aff_transaction set deleted='y', deleted_date=now() where id = " . $_GET['pid'];
                    $wpdb->get_row($sql);
                    $userid = "select distinct user_id from {$wpdb->prefix}rt_aff_transaction  where id = " . $_GET['pid'] ;
                    $user_ids = $wpdb->get_results($userid);
                    global $rtAffiliateAdmin;
                    foreach($user_ids as $uid){
                        var_dump($rtAffiliateAdmin->update_user_earning($uid->user_id));
                    }
                    if($_SERVER["HTTP_REFERER"]){
                        wp_safe_redirect($_SERVER["HTTP_REFERER"]);
                    } else{
                        wp_safe_redirect(  admin_url ("admin.php?page=rt-affiliate-manage-payment"));
                    }
                    exit;
                }
            }
        }
        function admin_nav () {
            global $wp_admin_bar ;
            // Bail if this is an ajax request
            if ( defined ( 'DOING_AJAX' ) )
                return ;
            // Only add menu for logged in user
            if ( is_user_logged_in () ) {
                // Add secondary parent item for all BuddyPress components
                $earning = $this->  get_user_earning(get_current_user_id());
                foreach($earning as $currency => $er){
                    if(intval($er["available"]) > 0 || strtolower($currency) == 'usd'){
                        $wp_admin_bar -> add_menu ( array (
                            'parent' => 'my-account' ,
                            'id'     => 'my-account-affiliater_'. $currency  ,
                            'title'  => strtoupper($currency) . " Balance: " .number_format($er["available"],2) .' '. $currency ,
                            'href'   => admin_url ( "admin.php?page=rt-affiliate-payment-info")
                        ) ) ;

                        }
                }

            }
        }

        public function menu() {
            //add_menu_page('Affiliate Admin', 'Affiliate Admin', 'manage_options', 'rt-affiliate-manage-payment', '', '');
//            add_submenu_page('rt-affiliate-admin', 'Submission', 'Submission', 'manage_options', 'rt-affiliate-admin', 'rt_affiliate_admin_options_html');
//            add_submenu_page('rt-affiliate-admin', 'Email Setting', 'Email Setting', 'manage_options', 'email_setting', 'rt_affiliate_options_email_setting');
            /*add_submenu_page('rt-affiliate-manage-payment', 'Manage Payment', 'Manage Payment', 'manage_options', 'rt-affiliate-manage-payment', array($this, 'manage_payment'));
            add_submenu_page('rt-affiliate-manage-payment', 'Manage Banners', 'Manage Banners', 'manage_options', 'rt-affiliate-manage-banners', array($this, 'manage_banners'));
            add_submenu_page('rt-affiliate-manage-payment', 'Settings', 'Settings', 'manage_options', 'rt-affiliate-manage-settings', array($this, 'manage_settings'));
            */

            add_menu_page('Affiliate', 'Affiliate', 'read', 'rt-affiliate-stats', '', '');
            add_submenu_page('rt-affiliate-stats', 'Stats & History', 'Stats & History', 'read', 'rt-affiliate-stats', array($this, 'affiliate_stats'));
            add_submenu_page('rt-affiliate-stats', 'Links & Banners', 'Links & Banners', 'read', 'rt-affiliate-banners', array($this, 'banners'));
            add_submenu_page('rt-affiliate-stats', 'Payment Info', 'Payment Info', 'read', 'rt-affiliate-payment-info', array($this, 'payment_info'));
            add_submenu_page('rt-affiliate-stats', 'My Setting', 'My Setting', 'read', 'rt-affiliate-payment-setting', array($this, 'payment_setting'));
            add_submenu_page('rt-affiliate-stats', 'Reports', 'Reports', 'read', 'rt-affiliate-payment-reports', array($this, 'payment_reports'));
            add_submenu_page('rt-affiliate-stats', 'Manage Payment', 'Manage Payment', 'manage_options', 'rt-affiliate-manage-payment', array($this, 'manage_payment'));
            //add_submenu_page('rt-affiliate-stats', 'Manage Banners', 'Manage Banners', 'manage_options', 'rt-affiliate-manage-banners', array($this, 'manage_banners'));
            add_submenu_page('rt-affiliate-stats', 'Admin Settings', 'Admin Settings', 'manage_options', 'rt-affiliate-manage-settings', array($this, 'manage_settings'));

        }
        function payment_reports(){ ?>
            <div class="wrap">
                <div class="icon32" id="icon-edit"></div>
                <h2>Reports</h2>
                <br/>
                <ul class="subsubsub">
                    <li><a href="?page=rt-affiliate-payment-reports" class="<?php if(!isset($_GET["type"])) echo "current" ?>">Current Monthly Visits</a> | </li>
                    <li><a href="?page=rt-affiliate-payment-reports&type=domain" class="<?php if(isset($_GET["type"]) && $_GET["type"] == "domain") echo "current" ?>">Domain</a> </li>
                </ul>
                <br/> 
                    <br/> <?php 
                    if(!isset($_GET["type"]))
                        $this->monthly_visit_report();
                    else if(isset($_GET["type"]) == "domain"){
                        $this->domain_visit_report();
                    }
                
            ?></div>
        <?php 
        }
        function domain_visit_report(){
            global $wpdb;   
            $sql = "SELECT domain_name as `key`, `count` as `count` FROM {$wpdb->prefix}rt_aff_users_domain where user_id = " . get_current_user_id () . " ";
            $data = $wpdb->get_results($sql);
            $graph_data = array( array("key","val") );
            foreach($data as $row){
                $graph_data[] = array($row->key, intval($row->count)); 
            }
            $reports = new rtReports();
            
            $reports->draw_chart("Domain",$graph_data,false);
        }
        
        function monthly_visit_report(){
            global $wpdb;   
            $sql = "SELECT date(`date`) as date,count(*) as count FROM  {$wpdb->prefix}rt_aff_users_referals where user_id = " . get_current_user_id () . " and  month(date) = month(now()) and year(date) =  year(now()) group by date(`date`) order by date(`date`)";
            $data = $wpdb->get_results($sql);
            $today_date = date("d");
            $k = 0;
            $graph_data = array( array("x","Visits") );
            for($i = 1 ; $i <= $today_date; $i++ ){
                $r_date = explode("-" , $data[$k]->date);
                if(intval($r_date[2]) == $i ){
                   $date_obj = date_create_from_format("Y-m-j", $data[$k]->date );
                   $graph_data[] = array($date_obj->format("dS"), intval($data[$k]->count)); 
                   $k++;
                }else{
                    $date_obj = date_create_from_format("Y-m-j", date("Y"). "-" . date("m") . "-" .  $i  );
                    $graph_data[] = array($date_obj->format("dS"), intval(0)); 
                }
            }
            $reports = new rtReports();
            
            $reports->draw_chart(date("M") . " " . date("Y"),$graph_data,'line',array("pointSize" => 5));
        }
        public function ui($hook) {
            if ('toplevel_page_rt-affiliate-manage-payment' == $hook)
                wp_enqueue_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
            wp_register_script('jquery-ui-timepicker', RT_AFFILIATE_URL . 'app/assets/js/jquery-ui-timepicker-addon.js');
            wp_enqueue_script('jquery-ui-autocomplete');
            if (in_array($hook, array('toplevel_page_rt-affiliate-manage-payment', 'toplevel_page_rt-affiliate-stats'))) {
                wp_enqueue_script('rt-affiliate-admin', RT_AFFILIATE_URL . 'app/assets/js/admin.js', array('jquery', 'jquery-ui-slider', 'jquery-ui-datepicker', 'jquery-ui-timepicker'));
            }
            wp_enqueue_style('rt-affiliate-admin', RT_AFFILIATE_URL . 'app/assets/css/admin.css');
        }
        function manage_settings(){ 
            if( isset($_POST["rt_aff_onetime_commission"])){
                if( ! is_numeric($_POST["rt_aff_onetime_commission"])){
                    $_POST["rt_aff_onetime_commission"]= 0;
                }
                update_site_option("rt_aff_onetime_commission" , $_POST["rt_aff_onetime_commission"]);
            }
            if( isset($_POST["rt_aff_plan_commision"])){
                if( ! is_numeric($_POST["rt_aff_plan_commision"])){
                    $_POST["rt_aff_plan_commision"]= 0;
                }
                update_site_option("rt_aff_plan_commision" , $_POST["rt_aff_plan_commision"]);
            }
            ?>
            <div class="wrap">
                <div class="icon32" id="icon-options-general"></div>
                <h2>Affiliate Settings</h2>
                <br/>
                <h3>WooCommerce</h3>
                <form method="post">
                    <div class="tablenav">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><label for="rt_aff_onetime_commission">One time Commission in (%)</label></th>
                                <td ><input type="number" required id="rt_aff_onetime_commission" name="rt_aff_onetime_commission" value='<?php echo get_site_option('rt_aff_onetime_commission',20) ?>' /></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="rt_aff_plan_commision">Recurring Commission in (%)</label></th>
                                <td ><input type="number" required id="rt_aff_plan_commision" name="rt_aff_plan_commision" value='<?php echo get_site_option('rt_aff_plan_commision',5) ?>' /></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"></th>
                                <td ><input type="submit" class='button button-primary' value='Save' /></td>
                            </tr>
                        </table>
                    </div>
                </form>
            </div> <?php
        }
        public function manage_payment() {
            if(isset($_GET["action"])){
                $current= $_REQUEST["action"];
            }else{
                $current = "list";
            }
            ?>
            <div class="wrap">
                <div class="icon32" id="icon-options-general"></div>
                <h2>Manage Payment</h2>
                <br/>
                <ul class="subsubsub">
                    <li><a href="?page=rt-affiliate-manage-payment&action=list" class="<?php echo ($current =="list")? "current" : ""; ?>">List</a> | </li>
                    <li><a href="?page=rt-affiliate-manage-payment&action=add" class="<?php echo ($current =="add")? "current" : ""; ?>">Add</a> </li>
                </ul>
                <?php
                if (isset($_GET['action']) && ( $_GET['action'] == 'add' || $_GET['action'] == 'edit'))
                    $this->manage_payment_edit();
                else
                    $this->manage_payment_list();
                ?></div><?php
        }

        public function banners(){ 
            
            if(isset($_GET["action"])){
                $current= $_REQUEST["action"];
            }else{
                $current = "affiliatebanner";
            }?>
            
            <div class="wrap">
                <div class="icon32" id="icon-options-general"></div>
                <h2>Banners</h2>
                <br/>
                <ul class="subsubsub">
                    <li><a href="?page=rt-affiliate-banners&action=affiliatebanner" class="<?php echo ($current =="affiliatebanner")? "current" : ""; ?>">Affiliate Banner</a> | </li>                                        
                    <?php if( is_admin() ) {?>
                        <li><a href="?page=rt-affiliate-banners&action=managebanner" class="<?php echo ($current =="managebanner")? "current" : ""; ?>">Manage Banner</a></li>
                    <?php } ?>
                </ul>
                <br/><br/>
                <?php
                if (isset($_GET['action']) && ( $_GET['action'] == 'managebanner' ))
                    $this->manage_banners();
                else
                    $this->affiliate_banners();
            ?></div> <?php
            
        }
        
        public function manage_banners() {
            
            if( is_admin() ){

                if ($_POST) {
                    update_option('rt_affiliate_banners', $_POST['banners']);
                }
                    ?>
                <form action="" method="post">
                    <table class="form-table">
                        <tr valign="top">
                            <th width="10%" scope="row"><label for="banners:">Add Banners: </label></th>
                            <td width="90%"><textarea id="banners" name="banners" cols="80" rows="15"><?php echo get_option('rt_affiliate_banners') ?></textarea></td>
                        </tr>
                    </table>
                    <div class="submit"><input type="submit" name="submit" value="save"></div>
                </form>
                <?php
            
            }
        }

        public function affiliate_stats() {
            global $wpdb, $user_ID, $rt_affiliate, $rt_user_details;

            $admin_cond = '';
            if ( ! current_user_can('manage_options'))
                $admin_cond = " where user_id = $user_ID";


            $sql = "SELECT * FROM " . $wpdb->prefix . "rt_aff_users_referals $admin_cond order by date DESC limit 0, 100";
            $rows = $wpdb->get_results($sql);
            ?>
            <div class="wrap">
                <div class="icon32" id="icon-options-general"></div>
                <h2>Affiliate Stats & History</h2>
                <br/>

                <h3>Summary</h3>
                <form method="post">
                <div class="tablenav">
                    <div class="alignleft actions">
                        Time Duration
                        <select name="time_duration" id="time_duration">
                            <option value="">All Time</option>
                            <?php
                            foreach ($rt_affiliate->time_durations as $k => $v) {
                                ?><option value="<?php echo $k; ?>" <?php if (isset($_POST['time_duration']) && $_POST['time_duration'] == $k) echo 'selected'; ?>><?php echo $v; ?></option><?php
                            }
                            ?>
                        </select>
                        <input type="submit" value="Apply" name="time_action" class="button-secondary action"/>
                    </div>
                    <div class="clear"></div>
                </div>
                </form>
                
                <h3>Details</h3>
                <?php 
                    $rtAffStates = new rtAffiliateStates();
                    $rtAffStates->  prepare_items ();  
                    $rtAffStates->  display ();
                ?>
            </div>
            <?php
        }

        public function affiliate_banners() {
            global $user_ID;
            $username = get_userdata($user_ID)->user_login;
            ?>
            <div class="wrap">
                <div class="icon32" id="icon-options-general"></div>
                <h4>Get Links & Banners</h4>
                <h3>Notes</h3>
                <ol>
                    <li>Below is list of banners/links with HTML code and direct link adjacent to them.</li>
                    <li>Your affiliate ID is already inserted in them.</li>
                    <li>( for geek ) You can target any page on this site in your affiliate link using your affiliate ID like below.<br/> <?php echo bloginfo('url') . '/services/?ref=' . $username; ?></li>
                </ol>
                <br/>
                <h3>Links and codes</h3>
                <p><strong>Text link for Email/IM: <?php echo '<a href="' . get_bloginfo('url') . '/?ref=' . $username . '">' . get_bloginfo('url') . '/?ref=' . $username . '</a>'; ?></strong></p>
                <p><strong>OR</strong> </p>
                <p>You can use any of banner code below</p>

                <table class="widefat post fixed" id="messagelist" width="90%">
                    <thead>
                        <tr class="tablemenu">
                            <th width="5%">#</th>
                            <th width="40%">Image</th>
                            <th width="10%">Size</th>
                            <th width="45%">HTML Code</th>
                        </tr>
                    </thead>
                    <?php
                    $banners_info = get_option('rt_affiliate_banners');
                    $banners_info = explode("\n", $banners_info);

                    foreach ($banners_info as $k => $v) {
                        $banner = explode(' ', $v);
                        $size = explode('x', $banner[0]);
                        ?>
                        <tr class="read">
                            <th><?php echo $k; ?></th>
                            <td><img src="<?php if (isset($banner[1])) echo $banner[1]; ?>" alt="Blogger to WordPress Migration"/></td>
                            <td><?php echo $banner[0]; ?></td>
                            <td><textarea name="banner_code" cols="50" rows="5"><a href="<?php echo bloginfo('url') . '/?ref=' . $username; ?>" target="_blank" title="Blogger To WordPress Migration Service"><img src="<?php if (isset($banner[1])) echo trim($banner[1]); ?>" alt="Bogger To WordPress Migration Service" width="<?php if (isset($size[0])) echo $size[0]; ?>" height="<?php if (isset($size[1])) echo $size[1]; ?>"/></a></textarea></td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
            </div>
            <?php
        }
        function payment_setting(){
            
            if(isset($_GET["action"])){
                $current= $_REQUEST["action"];
            }else{
                $current = "paymentinfo";
            }?>
            
            <div class="wrap">
                <div class="icon32" id="icon-options-general"></div>
                <h2>Payment Info</h2>
                <br/>
                <ul class="subsubsub">
                    <li><a href="?page=rt-affiliate-payment-setting&action=paymentinfo" class="<?php echo ($current =="paymentinfo")? "current" : ""; ?>">Payment Info</a> | </li>
                    <li><a href="?page=rt-affiliate-payment-setting&action=affiliateplan" class="<?php echo ($current =="affiliateplan")? "current" : ""; ?>">My Affiliate Plan</a> </li>
                </ul>
                <?php
                if (isset($_GET['action']) && ( $_GET['action'] == 'affiliateplan' ))
                    $this->payment_affiliateplan();
                else
                    $this->my_payment_settings();
                ?></div> <?php
        }
        
        public function payment_affiliateplan(){

            global $wpdb, $user_ID;
            
            $currentuserid = get_current_user_id();
            
            if ( isset($_POST["my-affiliate-plan"]) ) {
                
                $sql_pay  = $wpdb -> prepare ( "SELECT id FROM " . $wpdb -> prefix . "rt_aff_payment_info where user_id = %d " , $user_ID ) ;
                $rows_pay = $wpdb -> get_row ( $sql_pay ) ;
                if ( empty ( $rows_pay ) ) {
                   $result = $wpdb -> insert ( $wpdb -> prefix . "rt_aff_payment_info" , array ( 'user_id'        => $user_ID ,                        
                        'affiliate_plan' => $_POST[ 'rt_aff_my_plan' ]  ) , array ( '%d' , '%d' ) ) ;
                }else
                    $result =  $wpdb -> update ( $wpdb -> prefix . "rt_aff_payment_info" , array ( 'affiliate_plan' => $_POST[ 'rt_aff_my_plan' ]   ) , array (  'user_id' => $currentuserid ) ,  array ( '%d' ,'%d' ) ) ;
                
                //Message
                if( $result == 1 ){
                    echo '<br><br><div class="updated settings-error" id="setting-error-settings_updated"> 
                                <p><strong>Settings saved.</strong></p>
                        </div>';
                }
                
            }

            $sql_pay  = $wpdb -> prepare ( "SELECT affiliate_plan FROM " . $wpdb -> prefix . "rt_aff_payment_info where user_id = %d " , $currentuserid ) ;
            $rows_pay = $wpdb -> get_row ( $sql_pay ) ; 
            
            ?>
            <form method="post" action="<?php echo "?page=rt-affiliate-payment-setting&action=affiliateplan"; ?>" >
                    <table class="form-table" border="0">
                        <tr>
                            <td width="20%" class="label"><label id="rt_aff_my_plan" for="paypal_details">Affiliate Plan</label></td>
                            <td class="field">
                                <input type="radio" <?php if ( $rows_pay->affiliate_plan == 1  ) echo ' checked="checked" '; if ( $rows_pay->affiliate_plan != 0  ) echo ' disabled ';?> value="1" name="rt_aff_my_plan" id="rt_aff_my_plan" /><?php _e( '  One Time Plan', 'rtaffiliate' ); ?>
                                <input type="radio" <?php if( $rows_pay->affiliate_plan == 2  ) echo ' checked="checked" '; if ( $rows_pay->affiliate_plan != 0  ) echo ' disabled '; ?>  value="2" name="rt_aff_my_plan" id="rt_aff_my_plan" /><?php _e( '  Recurring', 'rtaffiliate' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%"></td>
                            <td class="field">
                                <p class="description">Please note that you will have only one chance to select your affiliate plan.</p>
                            </td>
                        </tr>
                    </table>
                    <div class="submit"><input type="submit" <?php if ( $rows_pay->affiliate_plan != 0  ) echo ' disabled '; ?> class="button button-primary" value="Save" name="my-affiliate-plan"/></div>
            </form><?php
        }

        public function my_payment_settings(){
            global $wpdb, $user_ID, $rt_affiliate;

            if ( isset($_POST["pay-info-submit"]) ) {
                $sql_pay  = $wpdb -> prepare ( "SELECT id FROM " . $wpdb -> prefix . "rt_aff_payment_info where user_id = %d " , $user_ID ) ;
                $rows_pay = $wpdb -> get_row ( $sql_pay ) ;
                $type_col = $_POST[ 'paypal_detail' ];
                if ( empty ( $rows_pay ) ) {
                   $result = $wpdb -> insert ( $wpdb -> prefix . "rt_aff_payment_info" , array ( 'user_id'        => $user_ID ,
                        'payment_method' => $_POST["payment_method"] ,
                        'payment_details'   => $_POST[ 'payment_details' ] ,
                        'min_payout'     => $_POST[ 'min_payout' ] ) , array ( '%d' , '%s' , '%s' , '%s' ) ) ;
                }
                else {
                   $result =  $wpdb -> update ( $wpdb -> prefix . "rt_aff_payment_info" , array ( 'payment_details' => $_POST[ 'payment_details' ] ,
                        'min_payout'   => $_POST[ 'min_payout' ],'payment_method' => $_POST["payment_method"]  ) , array ( 'user_id' => $user_ID ) , array ( '%s' , '%s', '%s' ) , array ( '%d' ) ) ;
                
                    
                }
                //Message
                 if( $result == 1 ){
                     echo '<br><br><div class="updated settings-error" id="setting-error-settings_updated"> 
                                 <p><strong>Settings saved.</strong></p>
                         </div>';
                 }
            }
            $cond = '';
            if (isset($_GET['view_type'])) {
                if ($_GET['view_type'] == 'show_earning') {
                    $cond = " WHERE type = 'earning' ";
                } else if ($_GET['view_type'] == 'show_payout') {
                    $cond = " WHERE type = 'payout' ";
                } else {
                    $cond = " WHERE 1 ";
                }
            } else {
                $cond = " WHERE 1 ";
            }

//            $admin_cond = '';
//            if (!current_user_can('manage_options')) {
            $admin_cond = " AND user_id = $user_ID";
//            }

            $sql_pay = "SELECT * FROM " . $wpdb->prefix . "rt_aff_payment_info  $cond " . $admin_cond;
            $rows_pay = $wpdb->get_row($sql_pay);            
            
            ?>
                <form method="post" action="<?php echo "?page=rt-affiliate-payment-setting&action=paymentinfo"; ?>" >
                    <table class="form-table" border="0">
                        <tr>
                            <td width="20%" class="label"><label id="lpaypal_email" for="paypal_email">Payment Method</label></td>
                            <td class="field">
                                <select id='payment_type' name='payment_method'>
                                     <?php foreach ($rt_affiliate->payment_methods as $k => $v) { 
                                         if($k == "--") continue;
                                         ?>
                                        <option value="<?php echo $k; ?>" <?php if (isset($rows_pay) &&  $rows_pay->payment_method == $k) echo 'selected'; ?>><?php echo $v; ?></option>
                                <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="label"><label id="lpaypal_email" for="paypal_details">Details</label></td>
                            <td class="field">
                                <textarea id="paypal_details" name="payment_details"><?php if (isset($_POST["pay-info-submit"]) ) echo $_POST['payment_details']; else if (isset($rows_pay->payment_details)) echo $rows_pay->payment_details; ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label id="lmin_payout" for="min_payout">Minimum Payout</label></td>
                            <td class="field"><input id="min_payout" name="min_payout" size="4" type="text" value="<?php if (isset($_POST["pay-info-submit"])) echo $_POST['min_payout']; else if (isset($rows_pay->min_payout)) echo $rows_pay->min_payout; ?>" /></td>
                        </tr>
                        <tr>
                            <td class="label"></td>
                            <td class="field">There is no restriction on this from our side. This just for your convenience.</td>
                        </tr>
                    </table>
                    <div class="submit"><input type="submit" class="button button-primary" value="Save" name="pay-info-submit"/></div>
                </form><?php
        }
        public function payment_info() {
            global $user_ID, $rt_affiliate;
            ?>
            <div class="wrap">
                <div class="icon32" id="icon-options-general"></div>
                <h2>Payment History</h2>
                <br/>
                <h3>Payment Summary</h3>
                <div id="aff-payment-summary">
                <?php
//                  if (!current_user_can('manage_options')) {
                $user_earnings = $this->get_user_earning($user_ID);
                foreach($user_earnings as $currency=>$u_earning){ ?>
                    <table class="affiliate-payment-summary" width="25%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <th>Total Earning Till Date</th>
                            <td><?php echo $u_earning["earning"] . ' ' .  $currency ; ?></td>
                        </tr>

                        <tr>
                            <th>Total Payout Till Date</th>
                            <td><?php echo $u_earning["payout"] . ' ' .  $currency; ?></td>
                        </tr>
                        <tr class="available">
                            <th>Available Balance</th>
                            <td><?php echo $u_earning["available"] . ' ' .  $currency; ?></td>
                        </tr>
                        <tr>
                            <th>Earnings on Hold</th>
                            <td><?php echo  $u_earning["onhold"] . ' ' .  $currency; ?></td>
                        </tr>
                    </table>
                <?php } ?>
                </div>
                <br />
                <h3 class="aff_earning_title">Earning History</h3>
                 <?php 
                    $rtmedia_moderation_list = new rtAffiliateEarningHistory();
                    $rtmedia_moderation_list->prepare_items();
                    echo "<form id='rtmedia-moderation-form' action='' method='post'>"; ?>
                    <div class="tablenav">
                        <div class="alignleft actions">
                            <select name="view_type"><?php $view_type = ( isset($_POST['view_type']) ) ? $_POST['view_type'] : ''; ?>
                                <option value="show_all" <?php if ($view_type == 'show_all') echo 'selected'; ?> >Show All</option>
                                <option value="show_earning" <?php if ($view_type == 'show_earning') echo 'selected'; ?>>Show Earning only</option>
                                <option value="show_payout" <?php if ($view_type == 'show_payout') echo 'selected'; ?>>Show Payout only</option>
                            </select>
                            <input type="submit" value="Apply" name="" class="button-secondary action">
                        </div>
                        <div class="clear"></div>
                    </div>
                        <?php
                    echo "</form><div class='wrap'>";
                    $rtmedia_moderation_list->display();
                    echo "</div> ";
                ?>
            </div>
            <?php
        }
        function update_user_earning($user_id){
            global $wpdb,$rt_affiliate;
            $user_earning  = array();
            $admin_cond = " AND user_id = $user_id";
            foreach ( $rt_affiliate -> currency_types as $currency ) {
                $sql_balance_plus  = "SELECT SUM(amount) as plus FROM " . $wpdb -> prefix . "rt_aff_transaction  WHERE  deleted is null and type = 'earning' " . $admin_cond . " AND currency='" . $currency . "' AND approved = 1" ;
                $rows_balance_plus = $wpdb -> get_row ( $sql_balance_plus ) ;

                $sql_balance_minus  = "SELECT SUM(amount) as minus FROM " . $wpdb -> prefix . "rt_aff_transaction  WHERE deleted is null and type = 'payout' " . $admin_cond . " AND currency='" . $currency . "' AND approved = 1" ;
                $rows_balance_minus = $wpdb -> get_row ( $sql_balance_minus ) ;
                $balance            = $rows_balance_plus -> plus - $rows_balance_minus -> minus ;

                $cond1                  = " AND `date` < DATE_SUB(CURDATE(), INTERVAL 60 DAY )";
                $sql_balance_available  = "SELECT SUM(amount) as avail FROM " . $wpdb -> prefix . "rt_aff_transaction  WHERE deleted is null and  type = 'earning' " . $admin_cond . $cond1 . " AND currency='" . $currency . "' AND approved = 1" ;
                $rows_balance_available = $wpdb -> get_row ( $sql_balance_available ) ;

                $cond2             = " AND `date` > DATE_SUB(CURDATE(), INTERVAL 60 DAY )";
                $sql_balance_hold  = "SELECT SUM(amount) as hold FROM " . $wpdb -> prefix . "rt_aff_transaction  WHERE deleted is null  and type = 'earning' " . $admin_cond . $cond2 . " AND currency='" . $currency . "' AND approved = 0" ;
                $rows_balance_hold = $wpdb -> get_row ( $sql_balance_hold ) ;

                $earning   = ($rows_balance_plus -> plus) ? $rows_balance_plus -> plus : '0' ;
                $payout    = ($rows_balance_minus -> minus) ? $rows_balance_minus -> minus : '0' ;
                $available = ($rows_balance_available -> avail) ? $rows_balance_available -> avail - $payout : 0 - $payout ;
                $onhold    = ($rows_balance_hold -> hold) ? $rows_balance_hold -> hold : 0 ;   
                $user_earning[$currency] = array( "earning" => $earning, "payout" => $payout, "available" => $available, "onhold" => $onhold);
            }
            update_user_meta($user_id , 'rt_affiliate_earning' , $user_earning);
            return $user_earning;
        }
        function get_user_earning($user_id){
            $user_earning = get_user_meta($user_id , 'rt_affiliate_earning' , true);
            if($user_earning)
                return $user_earning;
            else{
                return $this->update_user_earning($user_id);
            }
        }
        public function manage_payment_list() {?>
            <div class="tablenav">
                <div class="alignleft actions">
                    <form action="" method="get">
                        <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>"/>
                        Select User:
                        <input type="text" name="user" id="user">
                        <input type="hidden" name="user_id" id="user_id">
                        <input type="submit" value="Apply Filter" name="sort_action" class="button-secondary action"/>
                    </form>
                </div><?php if (isset($_GET['user_id']) && $_GET['user_id'] != 0) { ?>
                    <a class="rt-aff-show-all" href="<?php echo admin_url('admin.php?page=rt-affiliate-manage-payment'); ?>">Show All</a><?php }
            ?>
                <div class="clear"></div>
            </div>
            <?php
            global $plugin_page;
            echo "<form id='rtmedia-moderation-form' action='' method='POST'>";
            echo '<input type="hidden" name="page" value="'.esc_attr( $plugin_page ).'" />';
            $rtAffPaylist = new rtAffiliatePaymentList();
            $rtAffPaylist ->  prepare_items();
            $rtAffPaylist ->  display();
            echo "</form>";
        }

        public function manage_payment_edit() {
            global $wpdb, $rt_affiliate;

            $txn_id = '';
            $amount = '';
            $type = 'payout';
            $approved = '';
            $method = '';
            $note = '';
            $date = date('Y-m-d h:i:s', time() + (get_site_option('gmt_offset') * 1 * 3600));

            if (isset($_POST['action'])) {
                if ($_POST['action'] == 'add') {
                    $wpdb->insert($wpdb->prefix . "rt_aff_transaction",
                            array('txn_id'=>$_POST['txn_id'],
                                'user_id'=>$_POST['user_id'],
                                 'type'=>$_POST['type'] ,
                                 'currency'=>$_POST['currency'] ,
                                 'amount'=> $_POST['amount'],
                                 'payment_method'=> $_POST['payment_method'],
                                 'approved'=> $_POST['approved'],
                                 'note'=> $_POST['note'],
                                 'date'=>$_POST['date']
                            ),array('%s','%d','%s','%s','%s','%s','%s'));
                    $msg = 'Saved successfully!';
                } else if ($_POST['action'] == 'edit') {
                    $wpdb->update($wpdb->prefix . "rt_aff_transaction",
                            array('txn_id'=>$_POST['txn_id'],
                                 'type'=>$_POST['type'] ,
                                 'currency'=> $_POST['currency'],
                                 'amount'=> $_POST['amount'],
                                 'payment_method'=> $_POST['payment_method'],
                                 'approved'=> $_POST['approved'],
                                 'note'=> $_POST['note'],
                                 'date'=>$_POST['date']
                            ),array('id' => $_GET['pid']),array('%s','%s','%s','%s','%s','%s','%s'),array('%d'));
                    $msg = 'Updated successfully!';
                }
            }
            if (isset($_GET['action']) && $_GET['action'] == 'edit') {
                $sql = "SELECT * from " . $wpdb->prefix . "rt_aff_transaction where id = " . $_GET['pid'];
                $row_tranx = $wpdb->get_row($sql);
                $txn_id = $row_tranx->txn_id;
                $currency = $row_tranx->currency;
                $amount = $row_tranx->amount;
                $type = $row_tranx->type;
                $approved = $row_tranx->approved;
                $method = $row_tranx->payment_method;
                $note = $row_tranx->note;
                $date = date('Y-m-d H:i:s', strtotime($row_tranx->date) + (get_site_option('gmt_offset') * 1 * 3600));
            }  else  {
                $currency = '';
                        
            }
            if(isset($_POST['action']) && $_POST['action'] == 'edit'){                    
                    $this->  update_user_earning($row_tranx->user_id);
            } else if(isset($_POST['user_id'])){
                $this->  update_user_earning($_POST['user_id']);
            }
            if (isset($msg) )
                echo '<div class="updated"><p><strong>' . $msg . '</strong></p></div>'
                ?>
            <form action="" method="post">
                <table class="form-table">
                    <?php
                    if (isset($_GET['action']) && $_GET['action'] == 'edit') {
                        ?>
                        <input type="hidden" name="action" value="edit"/>
                        <input type="hidden" name="user" value="<?php echo isset($_GET['pid']) ? $_GET['pid'] : '' ?>"/>
                        <tr valign="top">
                            <th scope="row"><label for="user_name">User Name</label></th>
                            <td><?php echo get_userdata($row_tranx->user_id)->user_login; ?></td>
                        </tr>
                        <?php
                    } else {
                        ?>
                        <input type="hidden" name="action" value="add"/>
                        <tr valign="top">
                            <th scope="row"><label for="user">Select User</label></th>
                            <td>
                                <input type="text" name="user" id="user" class="regular-text" required />
                                <input type="hidden" name="user_id" id="user_id" />
                            </td>
                        </tr>
                    <?php } ?>
                    <tr valign="top">
                        <th scope="row"><label for="txn_id">Contact ID/ Transaction ID</label></th>
                        <td><input type="text" value="<?php echo $txn_id; ?>" id="txn_id" name="txn_id" class="regular-text"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="amount">Currency</label></th>
                        <td>
                             <select name="currency" id="currency">
                                <?php foreach ($rt_affiliate->currency_types as  $v) { ?>
                                    <option value="<?php echo $v; ?>" <?php if ($currency == $v) echo 'selected'; ?>><?php echo $v; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="amount">Amount</label></th>
                        <td><input type="text" value="<?php echo $amount; ?>" id="amount" name="amount" required class="regular-text"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="type">Payment Type</label></th>
                        <td>
                            <select name="type" id="type">
                                <?php foreach ($rt_affiliate->payment_types as $k => $v) { ?>
                                    <option value="<?php echo $k; ?>" <?php if ($type == $k) echo 'selected'; ?>><?php echo $v; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="approved">Approved</label></th>
                        <td>
                            <select name="approved" id="approved">
                                <option value="1" <?php if ($approved == 1) echo 'selected'; ?>>Yes</option>
                                <option value="0" <?php if ($approved == 0) echo 'selected'; ?>>No</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="payment_method">Payment Method</label></th>
                        <td>
                            <select name="payment_method" id="payment_method">
                                <?php foreach ($rt_affiliate->payment_methods as $k => $v) { ?>
                                    <option value="<?php echo $k; ?>" <?php if ($method == $k) echo 'selected'; ?>><?php echo $v; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="note">Note</label></th>
                        <td><textarea id="note" name="note" cols="30" rows="4" ><?php echo $note; ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="date">Date</label></th>
                        <td><input type="text" value="<?php echo $date; ?>" id="date" name="date" class="regular-text"></td>
                    </tr>
                </table>
                <div class="submit"><input type="submit" class="button button-primary" name="submit" value="save"></div>
            </form>
            <?php
        }

        function affiliate_summary() {
            global $wpdb, $user_ID;
            $cond1 = " 1=1 ";
            $cond2 = "";
            //DATE_FORMAT('1900-10-04 22:23:00','%D %y %a %d %m %b %j');

            switch ($_POST['time_duration']) {
                case 'today':
                    $cond1 = " DATE_FORMAT(`date`, '%D %y %a') = DATE_FORMAT(now() , '%D %y %a')";
                    $cond2 = " DATE_FORMAT(`date_update`, '%D %y %a') = DATE_FORMAT(now() , '%D %y %a')";
                    break;
                case 'yesterday':
                    $cond1 = " DATE_FORMAT(`date`, '%Y/%m/%d') = DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 day ), '%Y/%m/%d')";
                    $cond2 = " DATE_FORMAT(`date_update`, '%D %y %a') = DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 day ), '%D %y %a')";
                    break;
                case 'this_week':
                    $cond1 = " YEARWEEK(`date`) = YEARWEEK(CURRENT_DATE)";
                    $cond2 = " YEARWEEK(`date_update`) = YEARWEEK(CURRENT_DATE)";
                    break;
                case 'last_week':
                    $cond1 = " YEARWEEK(`date`) = YEARWEEK(CURRENT_DATE- INTERVAL 7 DAY)";
                    $cond2 = " YEARWEEK(`date_update`) = YEARWEEK(CURRENT_DATE- INTERVAL 7 DAY)";
                    break;
                case 'this_month':
                    $cond1 = " DATE_FORMAT(`date`, '%y %m') = DATE_FORMAT(now(), '%y %m')";
                    $cond2 = " DATE_FORMAT(`date_update`, '%y %m') = DATE_FORMAT(now(), '%y %m')";
                    break;
                case 'last_month':
                    $cond1 = " DATE_FORMAT(`date`, '%y %m') = DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 month ), '%y %m')";
                    $cond2 = " DATE_FORMAT(`date_update`, '%y %m') = DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 month ), '%y %m')";
                    break;
                case 'this_year':
                    $cond1 = " DATE_FORMAT(`date`, '%y') = DATE_FORMAT(now(), '%y')";
                    $cond2 = " DATE_FORMAT(`date_update`, '%y') = DATE_FORMAT(now(), '%y')";
                    break;
                case 'last_year':
                    $cond1 = " DATE_FORMAT(`date`, '%y') = DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 year ), '%y')";
                    $cond2 = " DATE_FORMAT(`date_update`, '%y') = DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 year ), '%y')";
                    break;
            }

            $admin_cond = '';
            $admin_ref_cond = '';
            if (!current_user_can('manage_options')) {
                $admin_cond = " user_id = $user_ID AND ";
                $admin_ref_cond = " referred_by = $user_ID AND";
            }

            $sql_clicks = "SELECT count(id) as cnt FROM " . $wpdb->prefix . "rt_aff_users_referals WHERE $admin_cond" . $cond1;
            $rows_clicks = $wpdb->get_row($sql_clicks);
            ?>
            <p>Number of clicks: <?php echo $rows_clicks->cnt; ?></p>
            <?php
            die();
        }

        public function order_referer_info($post) {
            add_meta_box('rt-affiliate-referer-info', __('Customer Referer Info'), array($this, 'referer_info'), 'shop_order', 'side');
        }

        public function referer_info($post) {
            $referer = get_post_meta($post->ID, '_rt-ref-affiliate', true);
            if ($referer) {
                $referer = explode(',', $referer);
                echo '<p><a href="' . admin_url('user-edit.php?user_id=' . $referer[1], 'http') . '">' . $referer[0] . '</a></p>';
            } else {
                echo '<p>This customer was not refered.</p>';
            }
        }

        public function users_lookup() {
            global $wpdb;

            $search = like_escape($_REQUEST['query']);

            $query = 'SELECT ID,user_login,user_email,display_name FROM ' . $wpdb->users . '
        WHERE user_login LIKE \'' . $search . '%\'
            OR user_nicename LIKE \'' . $search . '%\'
            OR display_name LIKE \'' . $search . '%\'
            LIMIT ' . $_REQUEST['maxRows'];
            $response = array();
            foreach ($wpdb->get_results($query) as $row) {
                $response[] = array("name" => $row->display_name, "id" => $row->ID, "login_name" => $row->user_login, "imghtml" => get_avatar($row->user_email, 64, '', 'gravatar'));
            }
            ob_get_clean();
            echo json_encode($response);
            die();
        }

    }

}
?>
