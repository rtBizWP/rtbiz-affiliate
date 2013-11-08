<?php
class rtAffiliateStates extends WP_List_Table {
    public function __construct() {

	    // Define singular and plural labels, as well as whether we support AJAX.
	    parent::__construct( array(
		    'ajax'     => true,
		    'plural'   => 'stateshistory',
		    'singular' => 'statesthistory',
	    ) );
    }

    function prepare_items() {
	global $wpdb, $_wp_column_headers;
	$screen = get_current_screen();
	/* -- Preparing your query -- */
        $cond1 = " 1=1 ";
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
        $user_id =get_current_user_id ();
        if(current_user_can('manage_options') && isset($_GET["user_id"])){
            $user_id = $_GET["user_id"];
            $admin_cond = " user_id = $user_id AND ";
        }else{
            if (!current_user_can('manage_options')) {
                $admin_cond = " user_id = $user_id AND ";
            }else{
                $admin_cond= "";
            }
        }

	$query = "SELECT * FROM " . $wpdb->prefix . "rt_aff_users_referals  where $cond1 " . $admin_cond;

        if(isset($_REQUEST["orderby"])){
            $order_by = $_REQUEST["orderby"];
        }else{
            $order_by = "date";
        }
        
        if(isset($_REQUEST["order"])){
            $order = $_REQUEST["order"];
        }else{
            $order = "desc";
        }
        $query .= " order by " . $order_by . " " . $order;
	/* -- Pagination parameters -- */
	//Number of elements in your table?
	$totalitems = $wpdb->query($query);
	//return the total number of affected rows
	//How many to display per page?
	$perpage = 10;
	//Which page is this?
	$paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
	//Page Number
	if(empty($paged) || !is_numeric($paged) || $paged<=0 ) { $paged=1; }
	//How many pages do we have in total?
	$totalpages = ceil($totalitems/$perpage);
	//adjust the query to take pagination into account
	if(!empty($paged) && !empty($perpage)) {
		$offset=($paged-1)*$perpage; $query.=' LIMIT '.(int)$offset.','.(int)$perpage;
	}
	/* -- Register the pagination -- */
	$this->set_pagination_args( array(
		"total_items" => $totalitems,
		"total_pages" => $totalpages,
		"per_page" => $perpage,
	) );
	//The pagination links are automatically built according to those parameters

	/* -- Register the Columns -- */
	$columns = $this->get_columns();
	$hidden = array();
	$sortable = $this->get_sortable_columns();
	$this->_column_headers = array($columns, $hidden, $sortable);

	/* -- Fetch the items -- */
	$this->items = $wpdb->get_results($query);
    }

    function get_column_info() {
	$this->_column_headers = array(
		$this->get_columns(),
		array(),
		$this->get_sortable_columns(),
	);
	return $this->_column_headers;
    }

    function no_items() {
	    _e( 'No Affilate States History found.' );
    }

    function display() {
	extract( $this->_args );
	$this->display_tablenav( 'top' ); ?>
	<table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
	    <thead>
		<tr>
		    <?php $this->print_column_headers(); ?>
		</tr>
	    </thead>
	    <tfoot>
		<tr>
		    <?php $this->print_column_headers( false ); ?>
		</tr>
	    </tfoot>
	    <tbody id="the-comment-list">
		<?php $this->display_rows_or_placeholder(); ?>
	    </tbody>
	</table>
	<?php
	$this->display_tablenav( 'bottom' );
    }

    function single_row( $item ) {
	static $row_class = '';
	if ( empty( $row_class ) ) {
		$row_class = ' class="alternate"';
	} else {
		$row_class = '';
	}
	echo '<tr' . $row_class . ' >';
	echo $this->single_row_columns( $item );
	echo '</tr>';
    }

    function get_bulk_actions() {
	return  array();
    }

    function get_columns() {
	return array(
            'id'=>__('#'),
	    'date'=>__('Date & Time'),
	    'referred_from'=>__('Referred From'),
	    'ip_address'=>__('Location'),
	    'landing_page' => __('Landing Page'),
	);
    }
    function get_sortable_columns() {
        $sortable_columns = array(
            'id' =>array('id', false),
            'date' => array('payment_method', true),
        );
        return $sortable_columns;
    }

    function column_id( $item ) {
	    echo $item->id ;
    }
    function column_date( $item ) {
	    echo date('F j, Y, g:i a', strtotime($item->date) + (get_site_option('gmt_offset') * 1 * 3600)) ;
    }
    function column_referred_from( $row) {
	    if ($row->referred_from != '') echo  '<a target="_blank" href="' . $row->referred_from . '">' . $row->referred_from . '</a>'; else echo 'No Link';
    }
    function column_ip_address($row ) {
	    echo $row->ip_address;
    }
    function column_landing_page($row ) {
	    if ($row->landing_page != '')  echo '<a target="_blank" href="' . $row->landing_page . '">' . $row->landing_page . '</a>'; else echo 'No Link';
    }
}