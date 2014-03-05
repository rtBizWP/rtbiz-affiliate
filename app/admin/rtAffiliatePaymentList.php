<?php

class rtAffiliatePaymentList extends WP_List_Table {
	public function __construct() {

		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax' => true, 'plural' => 'paymentlist', 'singular' => 'paymentlist',
		) );
	}

	function prepare_items() {
		global $wpdb;
		$this->process_bulk_action();
		$cond = " WHERE deleted is null ";
		if ( filter_input( INPUT_GET, 'user_id' ) != null && filter_input( INPUT_GET, 'user_id' ) != 0 ) {
			$cond .= " and user_id = " . filter_input( INPUT_GET, 'user_id' );
		}

		$query = "SELECT * FROM " . $wpdb->prefix . "rt_aff_transaction  $cond ";

		if ( isset( $_REQUEST[ "orderby" ] ) ) {
			$order_by = $_REQUEST[ "orderby" ];
		} else {
			$order_by = "date";
		}

		if ( isset( $_REQUEST[ "order" ] ) ) {
			$order = $_REQUEST[ "order" ];
		} else {
			$order = "desc";
		}
		$query .= " order by " . $order_by . " " . $order;
		/* -- Pagination parameters -- */
		//Number of elements in your table?
		$totalitems = $wpdb->query( $query );
		//return the total number of affected rows
		//How many to display per page?
		$perpage = 10;
		//Which page is this?
		$paged = ! empty( $_GET[ "paged" ] ) ? mysql_real_escape_string( $_GET[ "paged" ] ) : '';
		//Page Number
		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}
		//How many pages do we have in total?
		$totalpages = ceil( $totalitems / $perpage );
		//adjust the query to take pagination into account
		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ( $paged - 1 ) * $perpage;
			$query .= ' LIMIT ' . (int)$offset . ',' . (int)$perpage;
		}
		/* -- Register the pagination -- */
		$this->set_pagination_args( array(
			"total_items" => $totalitems, "total_pages" => $totalpages, "per_page" => $perpage,
		) );
		//The pagination links are automatically built according to those parameters

		/* -- Register the Columns -- */
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/* -- Fetch the items -- */
		$this->items = $wpdb->get_results( $query );
	}

	function get_column_info() {
		$this->_column_headers = array(
			$this->get_columns(), array(), $this->get_sortable_columns(),
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
		$actions             = array();
		$actions[ 'delete' ] = __( 'Delete ' );

		return $actions;
	}

	function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />', 'id' => __( '#' ), 'user_id' => __( 'User Name' ), 'txn_id' => __( 'Transaction ID' ), 'payment_method' => __( 'Payment Method' ), 'type' => __( 'Type' ), 'amount' => __( 'Amount' ), 'approved' => __( 'Approved' ), 'note' => __( 'Note' ), 'date' => __( 'Date' ), 'action' => __( 'Action' ),
		);
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array( 'id', false ), 'user_id' => array( 'user_id', false ), 'payment_method' => array( 'payment_method', false ), 'type' => array( 'type', false ), 'amount' => array( 'amount', false ), 'date' => array( 'payment_method', true ),
		);

		return $sortable_columns;
	}

	function column_cb( $item ) {
		printf( '<label class="screen-reader-text" for="aid-%1$d">' . __( 'Select activity item %1$d', 'buddypress' ) . '</label><input type="checkbox" name="%1$s[]" value="%2$s" id="aid-%1$d" />', $this->_args[ 'singular' ], $item->id );
	}

	function column_id( $row ) {
		echo $row->id;
	}

	function column_user_id( $row ) {
		$userdata = get_userdata( $row->user_id );
		echo '<a href="' . admin_url( 'user-edit.php?user_id=' . $row->user_id, 'http' ) . '">' . $userdata->user_login . '</a><br />(' . $userdata->user_email . ')';
	}

	function column_txn_id( $row ) {
		if ( isset ( $row->txn_id ) && ! empty ( $row->txn_id ) && ( 'shop_order' == get_post_type( $row->txn_id ) ) ) {
			$txn_id = '<a href="' . get_edit_post_link( $row->txn_id ) . '" target="_blank">WC-' . $row->txn_id . '</a>';
		} else {
			$txn_id = $row->txn_id;
		}
		echo $txn_id;
	}

	function column_payment_method( $row ) {
		global $rt_affiliate;
		echo isset( $rt_affiliate->payment_methods[ $row->payment_method ] ) ? $rt_affiliate->payment_methods[ $row->payment_method ] : '--';
	}

	function column_type( $row ) {
		global $rt_affiliate;
		echo $rt_affiliate->payment_types[ $row->type ];
	}

	function column_amount( $row ) {
		echo $row->amount . " " . ( ( isset( $row->currency ) ? $row->currency : '' ) );
	}

	function column_approved( $row ) {
		echo ( $row->approved ) ? 'Yes' : 'No';
	}

	function column_note( $row ) {
		echo $row->note;
	}

	function column_action( $row ) {
		?>
		<a href="?page=rt-affiliate-manage-payment&action=edit&pid=<?php echo $row->id; ?>">Edit</a>&nbsp;&nbsp;|&nbsp;&nbsp;
		<a class='rtAff-delete-payment' href="#"
		   data-href="?page=rt-affiliate-manage-payment&action=delete&pid=<?php echo $row->id; ?>">Delete</a>
	<?php
	}

	function column_date( $row ) {
		echo date( 'F j, Y, g:i a', strtotime( $row->date ) + ( get_site_option( 'gmt_offset' ) * 1 * 3600 ) );
	}

	function process_bulk_action() {

		if ( isset( $_REQUEST[ 'paymentlist' ] ) ) {
			global $wpdb;
			$media_id = ( is_array( $_REQUEST[ 'paymentlist' ] ) ) ? $_REQUEST[ 'paymentlist' ] : array( $_REQUEST[ 'paymentlist' ] );
			$userid   = "select distinct user_id from {$wpdb->prefix}rt_aff_transaction  where id in (" . implode( ",", $media_id ) . ")";
			if ( 'delete' === $this->current_action() ) {
				$sql = "update {$wpdb->prefix}rt_aff_transaction set deleted='y', deleted_date=now() where id in (" . implode( ",", $media_id ) . ")";
				$wpdb->get_row( $sql );
			}
			$user_ids = $wpdb->get_results( $userid );
			global $rtAffiliateAdmin;
			foreach ( $user_ids as $uid ) {
				$rtAffiliateAdmin->update_user_earning( $uid->user_id );
			}
		}
	}
}