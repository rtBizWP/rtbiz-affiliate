<?php

/**
 * Shows stats and history to affiliae user
 * @global <type> $wpdb
 * @global <type> $user_ID
 * @global <type> $rt_time_duration
 * @global <type> $rt_user_details
 * @global <type> $rt_status
 */
function rt_affiliate_stats() {
    global $wpdb, $user_ID, $rt_time_duration, $rt_user_details, $rt_status;

    $admin_cond = '';
    if (!current_user_can('manage_options'))
        $admin_cond = " where user_id = $user_ID";


    if ($_POST && $_POST['rt_show'] == 'enquiries') {
        $sql = "SELECT a.* FROM " . $wpdb->prefix . "rt_aff_contact_details b LEFT JOIN " . $wpdb->prefix . "rt_aff_users_referals a on a.id = b.users_referal_id $admin_cond order by a.date DESC"; // limit 0, 100";
    } else {
        $sql = "SELECT * FROM " . $wpdb->prefix . "rt_aff_users_referals $admin_cond order by date DESC limit 0, 100";
    }
    $rows = $wpdb->get_results($sql);
    ?>
    <div class="wrap">
        <div class="icon32" id="icon-options-general"></div>
        <h2>Affiliate Stats & History</h2>
        <br/>

        <h3>Summary</h3>
        <div class="tablenav">
            <div class="alignleft actions">
                Time Duration
                <select name="time_duration" id="time_duration">
                    <?php
                    foreach ($rt_time_duration as $k => $v) {
                        ?><option value="<?php echo $k; ?>" <?php if (isset($_GET['status']) && $_GET['status'] == $k) echo 'selected'; ?>><?php echo $v; ?></option><?php
            }
            ?>
                </select>
                <input type="submit" value="Apply" name="time_action" class="button-secondary action" id="time_action"/>
            </div>
            <div class="clear"></div>
        </div>
        <div id="rt_stats">
            <?php
            $admin_cond = '';
            $admin_ref_cond = '';
            $admin_ref_cond2 = '';
            if (!current_user_can('manage_options')) {
                $admin_cond = " WHERE user_id = $user_ID";
                $admin_ref_cond = " WHERE referred_by = $user_ID";
                $admin_ref_cond2 = " AND referred_by = $user_ID";
            }

            $sql_clicks = "SELECT count(id) as cnt FROM " . $wpdb->prefix . "rt_aff_users_referals $admin_cond";
            $rows_clicks = $wpdb->get_row($sql_clicks);
            $sql_enq = "SELECT count(id) as cnt FROM " . $wpdb->prefix . "rt_aff_contact_details $admin_ref_cond";
            $rows_enq = $wpdb->get_row($sql_enq);
            ?>
            <p>Number of clicks: <?php echo $rows_clicks->cnt; ?></p>
            <p>Total Enquiries:<?php echo $rows_enq->cnt; ?> </p>
        </div>

        <h3>Details</h3>
        <div class="tablenav">
            <div class="alignleft actions">
                <form action="" method="post">
                    Show
                    <select name="rt_show">
                        <?php
                        foreach ($rt_user_details as $k => $v) {
                            ?><option value="<?php echo $k; ?>" <?php if (isset($_POST['rt_show']) && $_POST['rt_show'] == $k) echo'selected'; ?>><?php echo $v; ?></option><?php
                }
                ?>
                    </select>
                    <input type="submit" value="Apply" name="show_action" class="button-secondary action">
                </form>
            </div>
            <div class="clear"></div>
        </div>

        <table class="widefat post fixed" id="messagelist" width="90%">
            <thead>
                <tr class="tablemenu">
                    <th width="20%">Date & Time</th>
                    <th width="25%">Referred From</th>
                    <th width="25%">Landing Page</th>
                </tr>
            </thead>

            <?php
            foreach ($rows as $k => $row) {
                ?>
                <tr class="read">
                    <td><?php echo date("F j, Y, g:i a", strtotime($row->date)); ?></td>
                    <td><?php if ($row->referred_from != '') echo '<a target="_blank" href="' . $row->referred_from . '">' . $row->referred_from . '</a>'; else echo 'No Link'; ?></td>
                    <td><?php if ($row->landing_page != '') echo '<a target="_blank" href="' . $row->landing_page . '">' . $row->landing_page . '</a>'; else echo 'No Link'; ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
    <?php
}

//ajax
add_action('wp_ajax_rt_affiliate_summary', 'rt_affiliate_summary');

/**
 * show summary of affiliate users referal
 * @global  $wpdb
 * @global  $user_ID
 * @global  $rt_time_duration
 */
function rt_affiliate_summary() {
    global $wpdb, $user_ID, $rt_time_duration;
    $cond1 = "";
    $cond2 = "";
    //DATE_FORMAT('1900-10-04 22:23:00','%D %y %a %d %m %b %j');

    switch ($_POST['time_duration']) {
        case 'today':
            $cond1 = " DATE_FORMAT(`date`, '%D %y %a') = DATE_FORMAT(now() , '%D %y %a')";
            $cond2 = " DATE_FORMAT(`date_update`, '%D %y %a') = DATE_FORMAT(now() , '%D %y %a')";
            break;
        case 'yesterday':
            $cond1 = " DATE_FORMAT(`date`, '%D %y %a') = DATE_FORMAT(DATE_SUB(now(), INTERVAL 1 day ), '%D %y %a')";
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
    $sql_enq = "SELECT count(id) as cnt FROM " . $wpdb->prefix . "rt_aff_contact_details WHERE $admin_ref_cond" . $cond2;
    $rows_enq = $wpdb->get_row($sql_enq);
    ?>
    <p>Number of clicks: <?php echo $rows_clicks->cnt; ?></p>
    <p>Total Enquiries:<?php echo $rows_enq->cnt; ?> </p>
    <?php
    die();
}

/**
 * show avalable banners
 * @global  $user_ID
 */
function rt_affiliate_links_banners() {
    global $user_ID;
    $username = get_userdata($user_ID)->user_login;
    ?>
    <div class="wrap">
        <div class="icon32" id="icon-options-general"></div>
        <h2>Get Links & Banners</h2>
        <br/>
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

/**
 * shows payment log
 * @global  $wpdb
 * @global  $user_ID 
 */
function rt_affiliate_payment_info() {
    global $wpdb, $user_ID, $payment_method;

    if ($_POST) {
        $sql_pay = "SELECT id FROM " . $wpdb->prefix . "rt_aff_payment_info where user_id = $user_ID ";
        $rows_pay = $wpdb->get_row($sql_pay);

        if ($rows_pay->id == NULL) {
            $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_payment_info
                     ( `user_id`, `payment_method`, `paypal_email`, `min_payout` )   VALUES
                    ( $user_ID, 'paypal', '" . $_POST['paypal_email'] . "', '" . $_POST['min_payout'] . "')";
            $wpdb->query($sql);
        } else {
            $sql = "UPDATE " . $wpdb->prefix . "rt_aff_payment_info SET
                `paypal_email` = '" . $_POST['paypal_email'] . "',
                `min_payout` = '" . $_POST['min_payout'] . "'
                WHERE user_id = $user_ID";
            $wpdb->query($sql);
        }
    }

    $cond = '';
    if (isset($_GET['view_type'])) {
        if ($_GET['view_type'] == 'show_earning') {
            $cond = " WHERE (type = 'earning' or type = 'payment_cancel') ";
        } else if ($_GET['view_type'] == 'show_payment') {
            $cond = " WHERE (type = 'payment' or type = 'client_refunded') ";
        } else {
            $cond = " WHERE 1 ";
        }
    } else {
        $cond = " WHERE 1 ";
    }

    $admin_cond = '';
    if (!current_user_can('manage_options')) {
        $admin_cond = " AND user_id = $user_ID";
    }

    $sql_pay = "SELECT * FROM " . $wpdb->prefix . "rt_aff_payment_info  $cond " . $admin_cond;
    $rows_pay = $wpdb->get_row($sql_pay);
    ?>
    <div class="wrap">
        <div class="icon32" id="icon-options-general"></div>
        <h2>Payment Info & History</h2>
        <br/>
        <h3>Payment Info</h3>
        <form method="post" action="<?php echo "?page=payment_info"; ?>" >
            <table class="form-table" border="0">
                <tr>
                    <td width="20%" class="label"><label id="lpaypal_email" for="paypal_email">Paypal Email Address</label></td>
                    <td class="field"><input id="paypal_email" name="paypal_email" type="text" value="<?php if ($_POST) echo $_POST['paypal_email']; else if (isset($rows_pay->paypal_email)) echo $rows_pay->paypal_email; ?>" /></td>
                </tr>

                <tr>
                    <td class="label"><label id="lmin_payout" for="min_payout">Minimum Payout</label></td>
                    <td class="field"><input id="min_payout" name="min_payout" size="4" type="text" value="<?php if ($_POST) echo $_POST['min_payout']; else if (isset($rows_pay->min_payout)) echo $rows_pay->min_payout; ?>" />USD</td>
                </tr>
                <tr>
                    <td class="label"></td>
                    <td class="field">There is no restriction on this from our side. This just for your convenience.</td>
                </tr>
            </table>
            <div class="submit"><input type="submit" value="save" name="submit"/></div>
        </form>

    <?php
    if (!current_user_can('manage_options')) {
        $sql_balance_plus = "SELECT SUM(amount) as plus FROM " . $wpdb->prefix . "rt_aff_transaction  WHERE type = 'earning' " . $admin_cond . " AND approved = 1";
        $rows_balance_plus = $wpdb->get_row($sql_balance_plus);

        $sql_balance_minus = "SELECT SUM(amount) as minus FROM " . $wpdb->prefix . "rt_aff_transaction  WHERE type = 'payout' " . $admin_cond . " AND approved = 1";
        $rows_balance_minus = $wpdb->get_row($sql_balance_minus);
        $balance = $rows_balance_plus->plus - $rows_balance_minus->minus;
    
        $cond1 = " AND `date` < DATE_SUB(CURDATE(), INTERVAL 60 DAY )";
        $sql_balance_available = "SELECT SUM(amount) as avail FROM " . $wpdb->prefix . "rt_aff_transaction  WHERE type = 'earning' " . $admin_cond . $cond1 . " AND approved = 1";
        $rows_balance_available = $wpdb->get_row($sql_balance_available);
        
        $cond2 = " AND `date` > DATE_SUB(CURDATE(), INTERVAL 60 DAY )";
        $sql_balance_hold = "SELECT SUM(amount) as hold FROM " . $wpdb->prefix . "rt_aff_transaction  WHERE type = 'earning' " . $admin_cond . $cond2 . " AND approved = 1";
        $rows_balance_hold = $wpdb->get_row($sql_balance_hold);
    ?>
        <h3>Payment Summary</h3>
        <table class="affiliate-payment-summary" width="25%" border="0">
            <tr>
                <th>Total Earning Till Date</th>
                <td><?php echo ($rows_balance_plus->plus)?'$'.$rows_balance_plus->plus:'$0'; ?></td>
            </tr>

            <tr>
                <th>Total Payout Till Date</th>
                <td><?php echo ($rows_balance_minus->minus)?'$'.$rows_balance_minus->minus:'$0'; ?></td>
            </tr>
            <tr class="available">
                <th>Available Balance</th>
                <td><?php echo ($rows_balance_available->avail)?'$'.$rows_balance_available->avail:'$0'; ?></td>
            </tr>
            <tr>
                <th>Earnings on Hold</th>
                <td><?php echo ($rows_balance_hold->hold)?'$'.$rows_balance_hold->hold:'$0'; ?></td>
            </tr>
        </table><?php 
    } ?>

        <h3>Earning History</h3>
        <form method="get" action="">
            <input type="hidden" name="page" value="payment_info"/>
            <div class="tablenav">
                <div class="alignleft actions">
                    <select name="view_type"><?php $view_type = ( isset($_GET['view_type']) ) ? $_GET['view_type'] : ''; ?>
                        <option value="show_all" <?php if ($view_type == 'show_all') echo 'selected'; ?> >Show All</option>
                        <option value="show_earning" <?php if ($view_type == 'show_earning') echo 'selected'; ?>>Show Earning only</option>
                        <option value="show_payment" <?php if ($view_type == 'show_payment') echo 'selected'; ?>>Show Payment only</option>
                    </select>
                    <input type="submit" value="Apply" name="doaction" class="button-secondary action">
                </div>
                <div class="clear"></div>
            </div>
        </form>

        <table class="widefat post fixed" id="messagelist" width="90%">
            <thead>
                <tr class="tablemenu">
                    <th width="5%">#</th>
                    <th>Transaction ID</th>
                    <th>Payment Method</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Note</th>
                    <th>Date</th>
                </tr>
            </thead>
    <?php
    $sql = "SELECT * FROM " . $wpdb->prefix . "rt_aff_transaction  $cond AND approved = 1" . $admin_cond;
    $rows = $wpdb->get_results($sql);
    foreach ($rows as $k => $row) {
        $prefix = '';
        if ($row->type == 'earning') {
            $prefix = '+$';
        } else if ($row->type == 'payout') {
            $prefix = '-$';
        }

        if (isset($row->txn_id) && !empty($row->txn_id) && ('shop_order' == get_post_type($row->txn_id))) {
            $txn_id = 'WC-' . $row->txn_id;
        } else {
            $txn_id = $row->txn_id;
        }
        ?>
                <tr class="read">
                    <th><?php echo $k; ?></th>
                    <td><?php echo $txn_id; ?></td>
                    <td><?php echo isset($payment_method[$row->payment_method]) ? $payment_method[$row->payment_method] : '--'; ?></td>
                    <td><?php echo $row->type; ?></td>
                    <td><?php echo $prefix . $row->amount; ?></td>
                    <td><?php echo $row->note; ?></td>
                    <td><?php echo date("F j, Y, g:i a", strtotime($row->date)); ?></td>
                </tr>
        <?php
    }
    ?>
        </table>
    </div>
    <?php
}
?>