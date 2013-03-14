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
            
            // WP 3.0+
            add_action('add_meta_boxes', array($this,'order_referer_info'));
            // backwards compatible
            add_action('admin_init', array($this,'order_referer_info'), 1);
        }

        public function menu() {
            add_menu_page('Affiliate Admin', 'Affiliate Admin', 'manage_options', 'rt-affiliate-manage-payment', '', '');
//            add_submenu_page('rt-affiliate-admin', 'Submission', 'Submission', 'manage_options', 'rt-affiliate-admin', 'rt_affiliate_admin_options_html');
//            add_submenu_page('rt-affiliate-admin', 'Email Setting', 'Email Setting', 'manage_options', 'email_setting', 'rt_affiliate_options_email_setting');
            add_submenu_page('rt-affiliate-manage-payment', 'Manage Payment', 'Manage Payment', 'manage_options', 'rt-affiliate-manage-payment', array($this, 'manage_payment'));
            add_submenu_page('rt-affiliate-manage-payment', 'Manage Banners', 'Manage Banners', 'manage_options', 'rt-affiliate-manage-banners', array($this, 'manage_banners'));


            add_menu_page('Affiliate', 'Affiliate', 'read', 'rt-affiliate-stats', '', '');
            add_submenu_page('rt-affiliate-stats', 'Stats & History', 'Stats & History', 'read', 'rt-affiliate-stats', array($this, 'affiliate_stats'));
            add_submenu_page('rt-affiliate-stats', 'Get Links & Banners', 'Get Links & Banners', 'read', 'rt-affiliate-banners', array($this, 'affiliate_banners'));
            add_submenu_page('rt-affiliate-stats', 'Payment Info', 'Payment Info', 'read', 'rt-affiliate-payment-info', array($this, 'payment_info'));
        }

        public function ui($hook) {
            if ('affiliate-admin_page_manage-payment' == $hook)
                wp_enqueue_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
            if (in_array($hook, array('affiliate-admin_page_rt-affiliate-manage-payment', 'affiliate_page_rt-affiliate-stats'))) {
                wp_enqueue_script('jquery-api', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');
                wp_enqueue_script('jquery-ui-api', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js');
                wp_enqueue_script('rt-affiliate-admin', RT_AFFILIATE_URL . 'app/assets/js/admin.js');
            }
            wp_enqueue_style('rt-affiliate-admin', RT_AFFILIATE_URL . 'app/assets/css/admin.css');
        }

        public function manage_payment() {
            ?>
            <div class="wrap">
                <div class="icon32" id="icon-options-general"></div>
                <h2>Manage Payment</h2>
                <br/>
                <p><a href="?page=rt-affiliate-manage-payment&action=list">LIST</a> | <a href="?page=rt-affiliate-manage-payment&action=add">ADD</a> </p>
                <?php
                if (isset($_GET['action']) && ( $_GET['action'] == 'add' || $_GET['action'] == 'edit'))
                    $this->manage_payment_edit();
                else
                    $this->manage_payment_list();
                ?></div><?php
        }

        public function manage_banners() {
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

        public function affiliate_stats() {
            global $wpdb, $user_ID, $rt_affiliate, $rt_user_details;

            $admin_cond = '';
            if (!current_user_can('manage_options'))
                $admin_cond = " where user_id = $user_ID";


            $sql = "SELECT * FROM " . $wpdb->prefix . "rt_aff_users_referals $admin_cond order by date DESC limit 0, 100";
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
                            foreach ($rt_affiliate->time_durations as $k => $v) {
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
                    ?>
                    <p>Number of clicks: <?php echo $rows_clicks->cnt; ?></p>
                </div>

                <h3>Details</h3>

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
                        $date = date('F j, Y, g:i a', strtotime($row->date) + (get_site_option('gmt_offset') * 1 * 3600));
                        ?>
                        <tr class="read">
                            <td><?php echo $date; ?></td>
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

        public function affiliate_banners() {
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

        public function payment_info() {
            global $wpdb, $user_ID, $rt_affiliate;

            if ($_POST) {
                $sql_pay = "SELECT id FROM " . $wpdb->prefix . "rt_aff_payment_info where user_id = $user_ID ";
                $rows_pay = $wpdb->get_row($sql_pay);

                if ( isset($rows_pay) && $rows_pay->id == NULL) {
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
            <div class="wrap">
                <div class="icon32" id="icon-options-general"></div>
                <h2>Payment Info & History</h2>
                <br/>
                <h3>Payment Info</h3>
                <form method="post" action="<?php echo "?page=rt-affiliate-payment-info"; ?>" >
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
//                if (!current_user_can('manage_options')) {
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

                    $earning = ($rows_balance_plus->plus) ? $rows_balance_plus->plus : '0';
                    $payout = ($rows_balance_minus->minus) ? $rows_balance_minus->minus : '0';
                    $available = ($rows_balance_available->avail) ? $rows_balance_available->avail - $payout : 0 - $payout;
                    $onhold = ($rows_balance_hold->hold) ? $rows_balance_hold->hold : 0;
                    ?>
                    <h3>Payment Summary</h3>
                    <table class="affiliate-payment-summary" width="25%" border="0">
                        <tr>
                            <th>Total Earning Till Date</th>
                            <td><?php echo '$' . $earning; ?></td>
                        </tr>

                        <tr>
                            <th>Total Payout Till Date</th>
                            <td><?php echo '$' . $payout; ?></td>
                        </tr>
                        <tr class="available">
                            <th>Available Balance</th>
                            <td><?php echo '$' . $available; ?></td>
                        </tr>
                        <tr>
                            <th>Earnings on Hold</th>
                            <td><?php echo '$' . $onhold; ?></td>
                        </tr>
                    </table><?php //}
                ?>

                <h3>Earning History</h3>
                <form method="get" action="">
                    <input type="hidden" name="page" value="payment_info"/>
                    <div class="tablenav">
                        <div class="alignleft actions">
                            <select name="view_type"><?php $view_type = ( isset($_GET['view_type']) ) ? $_GET['view_type'] : ''; ?>
                                <option value="show_all" <?php if ($view_type == 'show_all') echo 'selected'; ?> >Show All</option>
                                <option value="show_earning" <?php if ($view_type == 'show_earning') echo 'selected'; ?>>Show Earning only</option>
                                <option value="show_payout" <?php if ($view_type == 'show_payout') echo 'selected'; ?>>Show Payout only</option>
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
                        echo $row->date;
                        $date = date('F j, Y, g:i a', strtotime($row->date) + (get_site_option('gmt_offset') * 1 * 3600));
                        ?>
                        <tr class="read">
                            <th><?php echo $k; ?></th>
                            <td><?php echo $txn_id; ?></td>
                            <td><?php echo isset($rt_affiliate->payment_methods[$row->payment_method]) ? $rt_affiliate->payment_methods[$row->payment_method] : '--'; ?></td>
                            <td><?php echo $row->type; ?></td>
                            <td><?php echo $prefix . $row->amount; ?></td>
                            <td><?php echo $row->note; ?></td>
                            <td><?php echo $date; ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
            </div>
            <?php
        }

        public function manage_payment_list() {
            global $wpdb, $rt_affiliate;
            $cond = '';
            if (isset($_GET['user']) && $_GET['user'] != 0)
                $cond = 'WHERE user_id = ' . $_GET['user'];
            $sql = "SELECT * FROM " . $wpdb->prefix . "rt_aff_transaction $cond order by date desc ";
            $rows = $wpdb->get_results($sql);
            ?>
                                        <!--    <label for="commission"><strong>Commission</strong></label>
                                        <input type="text" value="<?php echo get_option('rt_aff_commission', 20); ?>" id="commission" name="commission">-->
            <div class="tablenav">
                <div class="alignleft actions">
                    <!--<form action="" method="get">-->
                        <!--<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>"/>-->
<!--                        Select User:
                        <select name="user">
                            <option value="0">All</option>
                            <?php
//                            $sql_user = "SELECT ID, user_login from " . $wpdb->users;
//                            $rows_user = $wpdb->get_results($sql_user);
//                            foreach ($rows_user as $row_user) {
                                ?><option value="<?php // echo $row_user->ID; ?>" <?php // if ($_GET['user'] == $row_user->ID) echo 'selected'; ?>><?php // echo $row_user->user_login; ?></option><?php
//            }
                            ?>
                        </select>-->
                        <!--<input type="submit" value="Apply Filter" name="sort_action" class="button-secondary action"/>-->
<!--                    </form>-->
                </div>
                <div class="clear"></div>
            </div>

            <table class="widefat post fixed" id="messagelist" width="90%">
                <thead>
                    <tr class="tablemenu">
                        <th width="5%">#</th>
                        <th>User Name</th>
                        <th>Transaction ID</th>
                        <th>Payment Method</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Approved</th>
                        <th>Note</th>
                        <th>Date</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <?php
                foreach ($rows as $k => $row) {
                    $prefix = '';
                    if ($row->type == 'earning') {
                        $prefix = '+$';
                    } else if ($row->type == 'payout') {
                        $prefix = '-$';
                    }
                    ?>
                    <tr class="read">
                        <th><?php echo $k+1; ?></th>
                            <?php $userdata = get_userdata($row->user_id); ?>
                        <td><?php echo '<a href="'.admin_url( 'user-edit.php?user_id=' . $row->user_id, 'http' ).'">'.$userdata->user_login.'</a><br />('.$userdata->user_email.')'; ?></td><?php
                if (isset($row->txn_id) && !empty($row->txn_id) && ('shop_order' == get_post_type($row->txn_id))) {
                    $txn_id = '<a href="' . get_edit_post_link($row->txn_id) . '" target="_blank">WC-' . $row->txn_id . '</a>';
                } else {
                    $txn_id = $row->txn_id;
                }
                $date = date('F j, Y, g:i a', strtotime($row->date) + (get_site_option('gmt_offset') * 1 * 3600));
                    ?>
                        <td><?php echo $txn_id; ?></td>
                        <td><?php echo isset($rt_affiliate->payment_methods[$row->payment_method]) ? $rt_affiliate->payment_methods[$row->payment_method] : '--'; ?></td>
                        <td><?php echo $rt_affiliate->payment_types[$row->type]; ?></td>
                        <td><?php echo $prefix . $row->amount; ?></td>
                        <td><?php echo ($row->approved) ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $row->note; ?></td>
                        <td><?php echo $date; ?></td>
                        <td><a href="?page=rt-affiliate-manage-payment&action=edit&pid=<?php echo $row->id; ?>">Edit</a> </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <?php
        }

        public function manage_payment_edit() {
            global $wpdb, $rt_affiliate;

            $txn_id = '';
            $amount = '';
            $type = 'payout';
            $approved = '';
            $method = '';
            $note = '';
            $date = date('Y-m-d');

            if (isset($_POST['action'])) {
                if ($_POST['action'] == 'add') {
                    $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_transaction ( `txn_id`, `user_id`, `type`, `amount`, `payment_method`, `note`, `date`) VALUES
                ( '" . $_POST['txn_id'] . "', '" . $_POST['user'] . "', '" . $_POST['type'] . "', '" . $_POST['amount'] . "', '" . $_POST['payment_method'] . "', '" . $_POST['note'] . "', '" . $_POST['date'] . "')";
                    $wpdb->query($sql);
                    $msg = 'Saved successfully!';
                } else if ($_POST['action'] == 'edit') {
                    $sql = "UPDATE " . $wpdb->prefix . "rt_aff_transaction SET
                `txn_id` = '" . $_POST['txn_id'] . "',
                `type` = '" . $_POST['type'] . "',
                `amount` = '" . $_POST['amount'] . "',
                `payment_method` = '" . $_POST['payment_method'] . "',
                `approved` = '" . $_POST['approved'] . "',
                `note` = '" . $_POST['note'] . "',
                `date` = '" . $_POST['date'] . "'
                WHERE id = " . $_GET['pid'];
                    $wpdb->query($sql);
                    $msg = 'Updated successfully!';
                }
            }
            if (isset($_GET['action']) && $_GET['action'] == 'edit') {
                $sql = "SELECT * from " . $wpdb->prefix . "rt_aff_transaction where id = " . $_GET['pid'];
                $row_tranx = $wpdb->get_row($sql);
                $txn_id = $row_tranx->txn_id;
                $amount = $row_tranx->amount;
                $type = $row_tranx->type;
                $approved = $row_tranx->approved;
                $method = $row_tranx->payment_method;
                $note = $row_tranx->note;
                $date = date('Y-m-d', strtotime($row_tranx->date));
            }
            if (isset($msg))
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
                                <select name="user" id="user">
                                    <?php
                                    $sql = "SELECT ID, user_login from " . $wpdb->users;
                                    $rows = $wpdb->get_results($sql);
                                    foreach ($rows as $row) {
                                        ?><option value="<?php echo $row->ID; ?>"><?php echo $row->user_login; ?></option><?php
                }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    <?php } ?>
                    <tr valign="top">
                        <th scope="row"><label for="txn_id">Contact ID/ Transaction ID</label></th>
                        <td><input type="text" value="<?php echo $txn_id; ?>" id="txn_id" name="txn_id" class="regular-text"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="amount">Amount</label></th>
                        <td><input type="text" value="<?php echo $amount; ?>" id="amount" name="amount" class="regular-text"></td>
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
                <div class="submit"><input type="submit" name="submit" value="save"></div>
            </form>
            <?php
        }

        function affiliate_summary() {
            global $wpdb, $user_ID;
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
            ?>
            <p>Number of clicks: <?php echo $rows_clicks->cnt; ?></p>
            <?php
            die();
        }
        
        public function order_referer_info($post){
            add_meta_box('rt-affiliate-referer-info', __('Customer Referer Info'), array($this,'referer_info'), 'shop_order', 'side');
        }
        
        public function referer_info($post){
            $referer = get_post_meta($post->ID, '_rt-ref-affiliate', true);
            if ( $referer ) {
                $referer = explode(',',$referer);
                echo '<p><a href="'.admin_url( 'user-edit.php?user_id=' . $referer[1], 'http' ).'">'.$referer[0].'</a></p>';
            } else {
                echo '<p>This customer was not refered.</p>';
            }
        }

    }

}
?>
