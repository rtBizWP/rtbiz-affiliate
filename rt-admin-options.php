<?php
function rt_affiliate_admin_options_html() {
    ?>
    <div class="wrap">
        <div class="icon32" id="icon-options-general"></div>
        <h2>Contact Form Submissions</h2>
        <?php
        switch ( $_GET['action'] ) {
            case 'list_contacts':
                rt_affiliate_contact_list();
                break;

            case 'edit_contacts':
                rt_affiliate_contact_edit();
                break;
            
            case 'delete_contacts':
                rt_affiliate_contact_delete();
                break;

            //---------------------------------
            case 'setting':
                rt_affiliate_setting();
                break;

            default:
                rt_affiliate_contact_list();
                break;
        }
        echo '</div>';
}

function rt_affiliate_contact_list() {
    global $wpdb, $rt_status;

    if ($_POST) {
        $ids = implode( ',', $_POST['record'] );
        $cond = '';
        if ( $_POST['project_status'] != 'completed' ) {
            $cond = " and project_status!= 'completed'";
        }
        if ( $_POST['project_status'] == 'completed_refunded' ) {
            $cond = " and project_status = 'completed'";
        }
        $sql = "UPDATE " . $wpdb->prefix . "rt_aff_contact_details SET
            `project_status` = '" . $_POST['project_status'] . "',
            `date_update` = now()
            WHERE id in ($ids)".$cond;
        $wpdb->query( $sql );

        //---------------------
        
        if ( $_POST['project_status'] == 'completed' ) {
            foreach ( $_POST['record'] as $id ) {
                //check if there is already record for this txn_id in transaction table
                $sql = "SELECT id FROM ".$wpdb->prefix."rt_aff_transaction WHERE txn_id = $id";
                $row = $wpdb->get_row( $sql );

                //if record not exist then make entry in transaction table
                if ( $row->id == NULL ) {
                    $sql_contact = "SELECT referred_by, service_b2w_migration, service_wp_theme FROM ".$wpdb->prefix."rt_aff_contact_details WHERE id = $id";
                    $row_contact = $wpdb->get_row( $sql_contact );

                    $amt = 0;
                    if ( $row_contact->service_b2w_migration == 'yes' ) $amt += RT_AFFILIATE_COMMISSION_B2W;
                    if ( $row_contact->service_wp_theme == 'yes' ) $amt += RT_AFFILIATE_COMMISSION_THEME;

                    $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_transaction
                         ( `txn_id`, `user_id`, `type`, `amount`, `date`)  VALUES
                        ( $id, '" . $row_contact->referred_by . "', 'earning', '$amt', now() )";
                    $wpdb->query( $sql );
                }
                //else do nothing
            }
       }
       if ($_POST['project_status'] == 'completed_refunded' ) {
            foreach ( $_POST['record'] as $id ) {
                //check if there is already record for this txn_id in transaction table
                $sql = "SELECT id, amount FROM ".$wpdb->prefix."rt_aff_transaction WHERE txn_id = $id";
                $row = $wpdb->get_row($sql);

                //if record exist then make entry in transaction table for refund
                if ( $row->id != NULL ) {
                    $sql_contact = "SELECT referred_by FROM ".$wpdb->prefix."rt_aff_contact_details WHERE id = $id";
                    $row_contact = $wpdb->get_row($sql_contact);

                    $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_transaction
                         ( `txn_id`, `user_id`, `type`, `amount`, `date`)  VALUES
                        ( $id, '" . $row_contact->referred_by . "', 'client_refunded', '$row->amount', now() )";
                    $wpdb->query( $sql );
                }
            }
       }
        //else if status other than complete
        //and there is already record exist in transaction table for that txn_id
        //then delete it
//        else{
//            $sql = "DELETE FROM ". $wpdb->prefix . "rt_aff_transaction WHERE type = 'earning' and txn_id in ( $ids)";
//            $wpdb->query($sql);
//        }
        //------------------------------------------
        ?><div class="updated"><p><strong>Changes applied successfully!</strong></p></div><?php
    }

    $staus_sql = " project_status!='deleted'";
    $sort_sql = '';
    
    if ( isset ($_GET['status'] ) && $_GET['status'] != '' ) {
        $staus_sql = " project_status = '" .$_GET['status']. "' ";
    }
    if ( isset ($_GET['sort']) && $_GET['sort'] != '' ) {
        switch ( $_GET['sort'] ) {
            case 'name_a':
                $sort_sql = " order by name ASC ";
                break;

            case 'name_d':
                $sort_sql = " order by name DESC ";
                break;

            case 'email_a':
                $sort_sql = " order by email ASC ";
                break;

            case 'email_d':
                $sort_sql = " order by email DESC ";
                break;

            case 'date_a':
                $sort_sql = " order by date_contacted  ASC ";
                break;

            case 'date_d':
                $sort_sql = " order by date_contacted  DESC ";
                break;
        }
    }
    $sql = "SELECT * FROM ".$wpdb->prefix."rt_aff_contact_details where ".$staus_sql.$sort_sql;
    $rows = $wpdb->get_results( $sql );
    ?>
        <h3>Customize View</h3>
        <div class="tablenav">

            <div class="alignleft actions">
                <form action="" method="get">
                    <input type="hidden" name="page" value="<?php echo $_GET['page'];?>"/>
                    <select name="status">
                        <option value="">Select Status</option>
                        <?php
                        foreach ( $rt_status as $k => $v ) {
                            ?><option value="<?php echo $k;?>" <?php if ( $_GET['status'] == $k ) echo 'selected';?>><?php echo $v;?></option><?php
                        }
                        ?>
                    </select>

                    <select name="sort">
                        <option value="">Sort By</option>
                        <option value="name_a" <?php if ( $_GET['sort'] == 'name_a' ) echo 'selected';?>>Name: Ascending</option>
                        <option value="name_d" <?php if ( $_GET['sort'] == 'name_d' ) echo 'selected';?>>Name: Descending</option>
                        <option value="email_a" <?php if ( $_GET['sort'] == 'email_a' ) echo 'selected';?>>Email: Ascending</option>
                        <option value="email_d" <?php if ( $_GET['sort'] == 'email_d' ) echo 'selected';?>>Email: Descending</option>
                        <option value="date_a" <?php if ( $_GET['sort'] == 'date_a' ) echo 'selected';?>>Date: Ascending</option>
                        <option value="date_d" <?php if ( $_GET['sort'] == 'date_d' ) echo 'selected';?>>Date: Descending</option>
                    </select>
                    
                    <input type="submit" value="Apply Filter" name="sort_action" class="button-secondary action"/>
                </form>
            </div>
            <div class="clear"></div>
        </div>

        <h3>Submissions</h3>
        <form action="" method="post">
            <div class="tablenav">
                <div class="alignleft actions">
                    <select name="project_status">
                        <option value="-1" selected="selected">Bulk Actions</option>
                        <?php
                        foreach ( $rt_status as $k => $v ) {
                            ?><option value="<?php echo $k;?>"><?php echo $v;?></option><?php
                        }
                        ?>
                    </select>
                     
                    <input type="submit" value="Apply Bulk Action" name="doaction" class="button-secondary action">
                </div>
                <div class="clear"></div>
            </div>

    <table class="widefat post fixed" id="messagelist">
        <thead>
            <tr class="tablemenu">
                <th width="2%"><input type="checkbox"> </th>
                <th width="8%">Name</th>
                <th width="20%">Email</th>
                <th width="5%">Blog URL</th>
                <th width="5%">Referred By</th>
                <th width="10%">IP Address</th>
                <th width="15%">Status</th>
                <th width="15%">Date</th>
                <th width="5%">Project Link in AC</th>
                <th width="5%">Invoice Link</th>
                <th width="5%">Edit/ View</th>
                <th width="5%">Delete</th>
            </tr>
        </thead>
            <?php
            foreach ( $rows as $k => $row ) {
                //list services
                $services_list = '';
                if ( $row->service_b2w_migration == 'yes' )
                    $services_list .= '"Blogger to WordPress Migration",';
                if ( $row->service_wp_theme == 'blog_layout' )
                    $services_list .= '"Theme matching my blog layout",';
                if ( $row->service_wp_theme == 'new_theme' )
                    $services_list .= '"New WordPress theme",';
                if ( $row->service_hosting == 'yes' )
                    $services_list .= '"Webhosting"';
                ?>

        <tr class="read">
            <th class="check-column"><input type="checkbox" value="<?php echo $row->id;?>" name="record[]"></th>
            <td><?php echo $row->name;?></td>
            <td><?php echo $row->email;?></td>
            <td><?php if ( $row->blog_url != '' ) echo '<a target="blank" href="'.$row->blog_url.'">URL</a>'; else echo 'No Link'; ?></td>
            <td><?php if ( $row->referred_by == 0 ) echo 'No Referer'; else echo '<a href="user-edit.php?user_id='.$row->referred_by.'">'.get_userdata($row->referred_by)->user_login.'</a>' ;?></td>
            <td><?php echo $row->ip_address;?></td>
            <td><?php echo $rt_status[$row->project_status];?></td>
            <td><?php echo date( "F j, Y, g:i a", strtotime( $row->date_update ) );?></td>
            <td><?php if($row->ac_link != '') echo '<a target="blank" href="'.$row->ac_link.'">Link</a>'; else echo 'No Link'; ?></td>
            <td><?php if($row->invoice_link != '') echo '<a target="blank" href="'.$row->invoice_link.'">Link</a>'; else echo 'No Link'; ?></td>
            <td><a href="?page=<?php echo RT_AFFILIATE_HANDLER;?>&action=edit_contacts&cid=<?php echo $row->id;?>">Edit/ View</a></td>
            <td><a href="?page=<?php echo RT_AFFILIATE_HANDLER;?>&action=delete_contacts&cid=<?php echo $row->id;?>">Delete</a></td>
        </tr>
                <?php
            }
            ?>
    </table>

    </form>
 <?php
}

function rt_affiliate_contact_edit() {
    global $wpdb, $rt_status;

    if($_POST) {
        if($_POST['project_status'] != 'completed'){
            $cond = " and project_status!= 'completed'";
        }
        if($_POST['project_status'] != 'completed_refund'){
            $cond = " and project_status = 'completed'";
        }
        $sql = "UPDATE " . $wpdb->prefix . "rt_aff_contact_details SET
            `project_status` = '" . $_POST['project_status'] . "',
            `ac_link` = '" . $_POST['ac_link'] . "',
            `invoice_link` = '" . $_POST['invoice_link'] . "',
            `date_update` = now()
            WHERE id = ".$_GET['cid'].$cond;
        $wpdb->query($sql);

        //to make entry in transaction table
        if($_POST['project_status'] == 'completed'){
            //check if there is already record for this txn_id in transaction table
            $sql = "SELECT id FROM ".$wpdb->prefix."rt_aff_transaction WHERE txn_id = ".$_GET['cid'];
            $row = $wpdb->get_row($sql);

            //if record not exist then make entry in transaction table
            if($row->id == NULL){
                $sql_contact = "SELECT referred_by, service_b2w_migration, service_wp_theme FROM ".$wpdb->prefix."rt_aff_contact_details WHERE id = ".$_GET['cid'];
                $row_contact = $wpdb->get_row($sql_contact);

                $amt = 0;
                if($row_contact->service_b2w_migration == 'yes') $amt += RT_AFFILIATE_COMMISSION_B2W;
                if($row_contact->service_wp_theme == 'yes') $amt += RT_AFFILIATE_COMMISSION_THEME;

                $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_transaction
                     ( `txn_id`, `user_id`, `type`, `amount`, `date`)  VALUES
                    ( ".$_GET['cid'].", '" . $_POST['referred_by'] . "', 'earning', '$amt' , now() )";
                $wpdb->query($sql);
            }
            //else do nothing
            else{
                $sql = "UPDATE " . $wpdb->prefix . "rt_aff_transaction SET
                        date = now() where txn_id = ".$_GET['cid'];
                $wpdb->query($sql);
            }
        }
        
       if($_POST['project_status'] == 'completed_refunded'){
            //check if there is already record for this txn_id in transaction table
            $sql = "SELECT id, amount FROM ".$wpdb->prefix."rt_aff_transaction WHERE txn_id = ".$_GET['cid'];
            $row = $wpdb->get_row($sql);

            //if record exist then make entry in transaction table for refund
            if($row->id != NULL){
                $sql_contact = "SELECT referred_by FROM ".$wpdb->prefix."rt_aff_contact_details WHERE id = ".$_GET['cid'];
                $row_contact = $wpdb->get_row($sql_contact);

                $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_transaction
                     ( `txn_id`, `user_id`, `type`, `amount`, `date`)  VALUES
                    ( ".$_GET['cid'].", '" . $row_contact->referred_by . "', 'client_refunded', '$row->amount', now() )";
                $wpdb->query($sql);
            }
       }
        //else if status other than complete
        //and there is already record exist in transaction table for that txn_id
        //then delete it
//        else{
//            $sql = "DELETE FROM ". $wpdb->prefix . "rt_aff_transaction WHERE type = 'earning' and txn_id = ".$_GET['cid'];;
//            $wpdb->query($sql);
//        }
        echo '<div class="updated"><p><strong>Changes saved successfully!</strong></p></div>';
    }

    $sql = "SELECT * FROM ".$wpdb->prefix."rt_aff_contact_details WHERE id = ".$_GET['cid'];
    $row = $wpdb->get_row($sql);
    $services_list = '';
    if($row->service_b2w_migration == 'yes')
        $services_list .= 'Blogger to WordPress Migration<br/>';
    if($row->service_wp_theme == 'blog_layout')
        $services_list .= 'Theme matching my blog layout<br/>';
    if($row->service_wp_theme == 'new_theme')
        $services_list .= 'New WordPress theme<br/>';
    if($row->service_hosting == 'yes')
        $services_list .= 'Webhosting';
    ?>

<form name="affiliate_form" id="affiliate_form"  method="post" action="<?php echo "?page=".RT_AFFILIATE_HANDLER."&action=".$_GET['action']."&cid=".$_GET['cid']; ?>" >
<table class="form-table" border="0">
    <tr>
        <th width="20%" class="label"><label id="lproject_status" for="project_status">Project Status</label></th>
        <td width="80%" class="field">
            <select name="project_status">
                <?php
                foreach($rt_status as $k => $v){
                    ?><option value="<?php echo $k;?>" <?php if( ($_POST && $_POST['project_status'] == $k) || $row->project_status == $k) echo 'selected';?> ><?php echo $v;?></option><?php
                }
                ?>
            </select>
        </td>
    </tr>
    <tr>
        <th class="label" valign="top"><label id="lac_link" for="ac_link">Project Link in AC:</label></th>
        <td class="field"><input size="40" id="ac_link" name="ac_link" type="text" value="<?php if($_POST) echo $_POST['ac_link']; else echo $row->ac_link?>" /></td>
    </tr>

    <tr>
        <th class="label" valign="top"><label id="linvoice_link" for="invoice_link">Invoice Link in AC:</label></th>
        <td class="field"><input size="40" id="invoice_link" name="invoice_link" type="text" value="<?php if($_POST) echo $_POST['invoice_link']; else echo $row->invoice_link?>" /></td>
    </tr>
    <tr><td colspan="2"><div class="submit"><input type="submit" value="save" name="submit"/></div></td></tr>
    <tr>
        <th class="label" valign="top"><label>Name:</label></th>
        <td class="field"><?php echo $row->name;?></td>
    </tr>
    <tr>
        <th class="label" valign="top"><label>Email:</label></th>
        <td class="field"><?php echo $row->email;?></td>
    </tr>
    <tr>
        <th class="label" valign="top"><label>Blog URL:</label></th>
        <td class="field"><?php echo $row->blog_url;?></td>
    </tr>
    
    <tr>
        <th class="label" valign="top"><label>Services:</label></th>
        <td class="field"><?php echo $services_list;?></td>
    </tr>
    <tr>
        <th class="label" valign="top"><label>Customer's Comment:</label></th>
        <td class="field"><?php echo $row->comment;?></td>
    </tr>
    <tr>
        <th class="label" valign="top"><label>IP Address:</label></th>
        <td class="field"><?php echo $row->ip_address;?></td>
    </tr>
    <tr>
        <th class="label" valign="top"><label>Referred By:</label></th>
        <td class="field"><?php if($row->referred_by == 0) echo 'No Referer'; else echo '<a href="user-edit.php?user_id='.$row->referred_by.'">'.get_userdata($row->referred_by)->user_login.'</a>' ;?></td>
    </tr>
    <tr>
        <th class="label" valign="top"><label>Date Contacted:</label></th>
        <td class="field"><?php echo date("F j, Y, g:i a", strtotime($row->date_contacted));?></td>
        </tr>
    <tr>
        <th class="label" valign="top"><label>Date Updated:</label></th>
        <td class="field"><?php echo date("F j, Y, g:i a", strtotime($row->date_update));?></td>
    </tr>
    <tr>
        <th class="label" valign="top"><label>Browser's History:</label></th>
        <td class="field"><?php 
        foreach (unserialize(urldecode($row->browsing_history)) as $k=>$v){
            echo $v.'<br/>';
        }
        ?></td>

    </tr>

</table>
    <input type="hidden" name="referred_by" value="<?php echo $row->referred_by;?>"/>
<div class="submit"><input type="submit" value="save" name="submit"/></div>
</form>
<?php
}

function rt_affiliate_contact_delete() {
    global $wpdb;
    $sql = "UPDATE " . $wpdb->prefix . "rt_aff_contact_details SET
            `project_status` = 'deleted',
            `date_update` = now()
            WHERE id = ".$_GET['cid'];
        $wpdb->query($sql);
    ?>
    <script type="text/javascript">
    location.href = '?page=<?php echo RT_AFFILIATE_HANDLER;?>&action=list_contacts&msg=Record deleted successfully !';
    </script>
    <?php
}

//------------------------------------------------------------
//  For Email Setting
//------------------------------------------------------------

function rt_affiliate_setting(){
    if($_POST) {
        $affiliate = array( 'paypal_api_user_name' =>$_POST['paypal_api_user_name'], 'paypal_api_password' =>$_POST['paypal_api_password'], 'paypal_api_signature' =>$_POST['paypal_api_signature'], 'tnc' => $_POST['tnc'], 'refund_period' => $_POST['refund_period'] );

        update_option('rt_affiliate', $affiliate);
        ?><div class="updated"><p><strong>Setting saved successfully!</strong></p></div><?php
    }
    $affiliate = get_option('rt_affiliate');
    //print_r($affiliate);
?>

<form name="affiliate_form_setting" id="affiliate_form_setting"  method="post" action="" >
<table class="form-table" border="0">
    <tr>
        <td class="label" width="20%"><label id="lpaypal_api_user_name" for="paypal_api_user_name">Paypal API User Name</label></td>
        <td class="field" width="80%"><input id="paypal_api_user_name" name="paypal_api_user_name" type="text" value="<?php echo $affiliate['paypal_api_user_name'];?>"  /></td>
    </tr>
    <tr>
        <td class="label"><label id="lpaypal_api_password" for="paypal_api_password">Paypal API Password</label></td>
        <td class="field"><input id="paypal_api_password" name="paypal_api_password" type="text" value="<?php echo $affiliate['paypal_api_password'];?>"  /></td>
    </tr>
    <tr>
        <td class="label"><label id="lpaypal_api_signature" for="paypal_api_signature">Paypal API Signature</label></td>
        <td class="field"><input id="paypal_api_signature" name="paypal_api_signature" type="text" value="<?php echo $affiliate['paypal_api_signature'];?>"  /></td>
    </tr>

    <tr>
        <td class="label"><label id="lrefund_period" for="refund_period">Refund Period </label></td>
        <td class="field"><input id="refund_period" name="refund_period" type="text" value="<?php echo $affiliate['refund_period'];?>"  /></td>
    </tr>
    <tr>
        <td class="label"><label id="ltnc" for="tnc">Terms and condition Text</label></td>
        <td class="field"><textarea id="tnc" name="tnc" cols="60" rows="10" ><?php echo $affiliate['tnc'];?></textarea></td>
    </tr>
</table>
<div class="submit"><input type="submit" value="save" name="submit"/></div>
</form>
<?php
}

function rt_affiliate_options_email_setting() {
    global $wpdb;
    if(isset($_POST['submit'])) {
//        print_r($_POST);
        update_option('rt_affiliate_options', $_POST);
    }
    $rt_option = get_option('rt_affiliate_options');
//    print_r($rt_option);
    ?>
        <div class="wrap">
        <div class="icon32" id="icon-options-general"></div>
        <h2>Email Setting</h2>

        
<form name="email_setting" id="email_setting" class="omlist" method="post" action="<?php echo "?page=email_setting"; ?>">
    <h3>Customer Email Template</h3>
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_cust_enable">Enable Template</label></th>
                <td><input type="checkbox" name="customer[rt_aff_enable]" id="rt_aff_cust_enable" value="1" <?php if($rt_option['customer']['rt_aff_enable'] == 1) echo 'checked';?> /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_cust_from">From Email Address</label></th>
                <td><input type="text" class="regular-text" name="customer[rt_aff_from]" id="rt_aff_cust_from" value="<?php echo $rt_option['customer']['rt_aff_from'];?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_cust_fromname">From Name</label></th>
                <td><input type="text" class="regular-text" name="customer[rt_aff_fromname]" id="rt_aff_cust_fromname" value="<?php echo $rt_option['customer']['rt_aff_fromname'];?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_cust_subject">Subject</label></th>
                <td><input type="text" class="regular-text" name="customer[rt_aff_subject]" id="rt_aff_cust_subject" value="<?php echo $rt_option['customer']['rt_aff_subject'];?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_cust_msg">Message Template</label></th>
                <td>
                    <p><strong>Replacement Keys:</strong><br />%customer_name% | %blog_url% | %ref_url% | %services_list% | %track_id% </p>
                    <textarea name="customer[rt_aff_msg]" id="rt_aff_cust_msg" cols="50" rows="8" ><?php echo $rt_option['customer']['rt_aff_msg'];?></textarea>					</td>
            </tr>
        </tbody>
    </table>
    <div class="submit"><input type="submit" value="save" name="submit"/></div>
    <h3>Affiliate User Email Template</h3>
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_user_enable">Enable Template</label></th>
                <td><input type="checkbox" name="user[rt_aff_enable]" id="rt_aff_user_enable" value="1" <?php if($rt_option['user']['rt_aff_enable'] == 1) echo 'checked';?> /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_user_from">From Email Address</label></th>
                <td><input type="text" class="regular-text" name="user[rt_aff_from]" id="rt_aff_user_from" value="<?php echo $rt_option['user']['rt_aff_from'];?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_user_fromname">From Name</label></th>
                <td><input type="text" class="regular-text" name="user[rt_aff_fromname]" id="rt_aff_user_fromname" value="<?php echo $rt_option['user']['rt_aff_fromname'];?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_user_subject">Subject</label></th>
                <td><input type="text" class="regular-text" name="user[rt_aff_subject]" id="rt_aff_user_subject" value="<?php echo $rt_option['user']['rt_aff_subject'];?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="rt_aff_user_msg">Message Template</label></th>
                <td>
                    <p><strong>Replacement Keys:</strong><br />%affiliate_name% | %blog_url% | %ref_url% | %services_list% | %track_id% </p>
                    <textarea name="user[rt_aff_msg]" id="rt_aff_user_msg"  cols="50" rows="8"><?php echo $rt_option['user']['rt_aff_msg'];?></textarea>
                </td>
            </tr>
        </tbody>
    </table>
    <div class="submit"><input type="submit" value="save" name="submit"/></div>
</form>
        </div>
        <?php

}

function rt_affiliate_options_manage_payment() {
    ?>
    <div class="wrap">
        <div class="icon32" id="icon-options-general"></div>
        <h2>Manage Payment</h2>
        <br/>
        <p><a href="?page=manage_payment&action=list">LIST</a> | <a href="?page=manage_payment&action=add">ADD</a> </p>
    <?php
    if($_GET['action'] == 'add' || $_GET['action'] == 'edit') rt_affiliate_options_manage_payment_edit();
    else rt_affiliate_options_manage_payment_list();
    ?></div><?php
}
function rt_affiliate_options_manage_payment_list() {
    global $wpdb, $payment_type;
    $sql = "SELECT * FROM ".$wpdb->prefix."rt_aff_transaction order by date desc ";
    $rows = $wpdb->get_results($sql);
    ?>
        <div class="tablenav">
            <div class="alignleft actions">
                <form action="" method="get">
                    <input type="hidden" name="page" value="<?php echo $_GET['page'];?>"/>
                    Select User:
                    <select name="user">
                        <option value="0">All</option>
                        <?php
                        $sql_user = "SELECT ID, user_login from ".$wpdb->prefix."users";
                        $rows_user = $wpdb->get_results($sql_user);
                        foreach($rows_user as $row_user){
                            ?><option value="<?php echo $row_user->ID;?>" <?php if($_GET['status'] == $row_user->ID) echo 'selected';?>><?php echo $row_user->user_login;?></option><?php
                        }
                    ?>
                    </select>
                    <input type="submit" value="Apply Filter" name="sort_action" class="button-secondary action"/>
                </form>
            </div>
            <div class="clear"></div>
        </div>

    <table class="widefat post fixed" id="messagelist" width="90%">
        <thead>
            <tr class="tablemenu">
                <th width="5%">#</th>
                <th>User Name</th>
                <th>Contact ID/ Transaction /Chq ID</th>
                <th>Payment Method</th>
                <th>Type</th>
                <th>Withdraw</th>
                <th>Deposit</th>
                <th>Note</th>
                <th>Date</th>
                <th>Edit</th>
            </tr>
        </thead>
        <?php
        foreach ($rows as $k => $row){
        ?>
             <tr class="read">
                <th><?php echo $k;?></th>
                <td><?php echo get_userdata($row->user_id)->user_login;?></td>
                <td><?php echo $row->txn_id?></td>
                <td><?php echo $row->payment_method?></td>
                <td><?php echo $payment_type[$row->type];?></td>
                <td><?php if($row->type == 'payment' || $row->type == 'client_refunded') echo $row->amount;?></td>
                <td><?php if($row->type == 'earning' || $row->type == 'payment_cancel') echo $row->amount;?></td>
                <td><?php echo $row->note;?></td>
                <td><?php echo date("F j, Y, g:i a", strtotime($row->date));?></td>
                <td><a href="?page=manage_payment&action=edit&pid=<?php echo $row->id;?>">Edit</a> </td>
            </tr>
            <?php
        }
            ?>
        </table>
 
        <?php
}

function rt_affiliate_options_manage_payment_edit() {
    global $wpdb, $payment_method, $payment_type;
    if($_POST['action'] == 'add'){
        $sql = "INSERT INTO " . $wpdb->prefix . "rt_aff_transaction ( `txn_id`, `user_id`, `type`, `amount`, `payment_method`, `note`, `date`) VALUES
                ( '" . $_POST['txn_id'] . "', '" . $_POST['user'] . "', '" . $_POST['type'] . "', '" . $_POST['amount'] . "', '" . $_POST['payment_method'] . "', '" . $_POST['note'] . "', '" . $_POST['date'] . "')";
        $wpdb->query($sql);
        $msg = 'Saved successfully!';
    }
    else if($_POST['action'] == 'edit'){
        $sql = "UPDATE " . $wpdb->prefix . "rt_aff_transaction SET
                `txn_id` = '" . $_POST['txn_id'] . "',
                `type` = '" . $_POST['type'] . "',
                `amount` = '" . $_POST['amount'] . "',
                `payment_method` = '" . $_POST['payment_method'] . "',
                `note` = '" . $_POST['note'] . "',
                `date` = '" . $_POST['date'] . "'
                WHERE id = ".$_GET['pid'];
        $wpdb->query($sql);
        $msg = 'Updated successfully!';
    }
    if($_GET['action'] == 'edit'){
        $sql = "SELECT * from ".$wpdb->prefix."rt_aff_transaction where id = ".$_GET['pid'];
        $row_tranx = $wpdb->get_row($sql);
    }
    if( isset ($msg)) echo '<div class="updated"><p><strong>'.$msg.'</strong></p></div>'
    ?>
        <form action="" method="post">
        <table class="form-table">
            <?php
            if($_GET['action'] == 'edit'){
                ?>
                <input type="hidden" name="action" value="edit"/>
                <input type="hidden" name="user" value="<?php echo $_GET['pid']?>"/>
                <tr valign="top">
                    <th scope="row"><label for="user_name">User Name</label></th>
                    <td><?php echo get_userdata($row_tranx->user_id)->user_login; ?></td>
                </tr>
            <?php
            }
            else{
            ?>
                <input type="hidden" name="action" value="add"/>
            <tr valign="top">
                <th scope="row"><label for="user">Select User</label></th>
                <td>
                    <select name="user" id="user">
                        <?php
                        $sql = "SELECT ID, user_login from ".$wpdb->prefix."users";
                        $rows = $wpdb->get_results($sql);
                        foreach($rows as $row){
                            ?><option value="<?php echo $row->ID;?>"><?php echo $row->user_login;?></option><?php
                        }
                    ?>
                    </select>
                </td>
            </tr>
            <?php } ?>
            <tr valign="top">
                <th scope="row"><label for="txn_id">Contact ID/ Transaction ID</label></th>
                <td><input type="text" value="<?php echo $row_tranx->txn_id; ?>" id="txn_id" name="txn_id" class="regular-text"></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="amount">Amount</label></th>
                <td><input type="text" value="<?php echo $row_tranx->amount; ?>" id="amount" name="amount" class="regular-text"></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="type">Payment Method</label></th>
                <td>
                    <select name="type" id="type">
                        <?php foreach($payment_type as $k=>$v){ ?>
                        <option value="<?php echo $k;?>" <?php if( $row_tranx->type == $k) echo 'selected'; ?>><?php echo $v;?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="payment_method">Payment Method</label></th>
                <td>
                    <select name="payment_method" id="payment_method">
                        <?php foreach($payment_method as $k=>$v){ ?>
                        <option value="<?php echo $k;?>" <?php if( $row_tranx->payment_method == $k) echo 'selected'; ?>><?php echo $v;?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="note">Note</label></th>
                <td><textarea id="note" name="note" cols="30" rows="4" ><?php echo $row_tranx->note; ?></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="date">Date</label></th>
                <td><input type="text" value="<?php if($row_tranx->date != '') echo date('Y-m-d', strtotime($row_tranx->date)); ?>" id="date" name="date" class="regular-text"></td>
            </tr>
        </table>
        <div class="submit"><input type="submit" name="submit" value="save"></div>
        </form>
    <?php
}
?>
