<?php

class rtAffiliateEarningHistory extends WP_List_Table {
	public function __construct() {

		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax' => true, 'plural' => 'paymenthistory', 'singular' => 'paymenthistory',
		) );
	}

	function prepare_items() {
		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();
		/* -- Preparing your query -- */
		$cond = '';
		if ( isset( $_POST[ 'view_type' ] ) ) {
			if ( $_POST[ 'view_type' ] == 'show_earning' ) {
				$cond = " WHERE type = 'earning' ";
			} else {
				if ( $_POST[ 'view_type' ] == 'show_payout' ) {
					$cond = " WHERE type = 'payout' ";
				} else {
					$cond = " WHERE 1 ";
				}
			}
		} else {
			$cond = " WHERE 1 ";
		}

		$admin_cond = " AND user_id = " . get_current_user_id() . " ";
		$query      = "SELECT * FROM " . $wpdb->prefix . "rt_aff_transaction  $cond and deleted is NULL " . $admin_cond;

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
		_e( 'No Payment History found.' );
	}

	function display() {
		$args = $this->_args;
		extract( $args );
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
		return array();
		$actions[ 'all' ]          = __( 'Show All' ) . '</a>';
		$actions[ 'earning_only' ] = __( 'Show Earning Only' ) . '</a>';
		$actions[ 'payout_only' ]  = __( 'Show Payout Only' ) . '</a>';

		return $actions;
	}

	function get_columns() {
		return array(
			'id' => __( '#' ), 'txn_id' => __( 'Transaction ID' ), 'payment_method' => __( 'Payment Method' ), 'type' => __( 'Type' ), 'amount' => __( 'Amount' ), 'currency' => __( 'Currency' ), 'note' => __( 'Note' ), 'date' => __( 'Date' ), 'approved' => __( 'Approved' ),
		);
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array( 'id', false ), 'payment_method' => array( 'payment_method', true ), 'type' => array( 'type', true ), 'amount' => array( 'amount', true ), 'date' => array( 'date', true ),
		);

		return $sortable_columns;
	}

	function column_id( $item ) {
		echo $item->id;
	}

	function column_txn_id( $item ) {
		echo $item->txn_id;
	}

	function column_payment_method( $item ) {
		echo $item->payment_method;
	}

	function column_type( $item ) {
		echo $item->type;
	}

	function column_currency( $item ) {
		echo $item->currency;
	}

	function column_amount( $item ) {
		echo $item->amount;
	}

	function column_note( $item ) {
		echo $item->note;
	}

	function column_date( $item ) {
		echo $item->date;
	}

	function column_approved( $item ) {
		echo ( isset( $item->approved ) && $item->approved == 1 ) ? "Yes" : "No";
	}


}