<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Items extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Item', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Items', 'sp' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

	}

	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No tags are avaliable.', 'sp' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'status':
			case 'tag':
			case 'content':
			case 'edit':
			case 'action':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}


	function column_edit($item) {
	    return sprintf(
	            '<a href="admin.php?page=ebs-version&type=edit&id=%s" >Edit</a>', $item['edit']
	    );
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

		$delete_nonce = wp_create_nonce( 'sp_delete_item' );

		$title = '<strong>' . $item['tag'] . '</strong>';

		$actions = [
			'delete' => sprintf( '<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
		];

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'id'    => __( 'Id', 'sp' ),
			'tag'    => __( 'Tag', 'sp' ),
			'content' => __( 'Content', 'sp' ),
			'edit' => __( 'Edit', 'sp' ),
			'action' => __( 'Action', 'sp' )
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'tag' => array( 'tag', true ),
			'content' => array( 'content', false )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete'
		];

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'items_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_items( $per_page, $current_page );
	}


	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_item' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				self::delete_item( absint( $_GET['item'] ) );

		                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		                // add_query_arg() return the current url
		                wp_redirect( esc_url_raw(add_query_arg()) );
				exit;
			}

		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_item( $id );

			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		        // add_query_arg() return the current url
		        wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
		}
	}

}

class SP_Content {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $items_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		$this->items_obj = new Items();
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}



	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {

		if ( ! empty( $_POST["action"] == 'add-tag' ) ) {
			echo 'fireeeeee';
			var_dump($_POST);
    	Items_List::add_item($_POST['tag-name'],$_POST['tag-content']);
		}else{
			echo 'not fire!!!';
			var_dump($_POST);
		}
		?>
		<div class="wrap">
			<h2>WP TagCon</h2>

			<div id="col-container" class="wp-clearfix">

			<div id="col-left">
			<div class="col-wrap">

				<div class="form-wrap">
				<h2>Add New Tag For Content</h2>
				<form id="addtag" method="post" action="" class="validate">
				<input type="hidden" name="action" value="add-tag">
				<input type="hidden" name="screen" value="edit-performer">
				<input type="hidden" name="taxonomy" value="performer">
				<input type="hidden" name="post_type" value="post">
				<input type="hidden" id="_wpnonce_add-tag" name="_wpnonce_add-tag" value="b93fe0a685"><input type="hidden" name="_wp_http_referer" value="/wp-admin/edit-tags.php?taxonomy=performer">
				<div class="form-field form-required term-name-wrap">
					<label for="title-name">Title</label>
					<input name="title-name" id="title-name" type="text" value="" size="40" aria-required="true">
					<p>Choose your tag</p>
				</div>
				<div class="form-field term-description-wrap">
					<label for="tag-description">Content</label>
					<textarea name="tag-content" id="tag-content" rows="5" cols="40"></textarea>
					<p>Enter your content in HTML</p>
				</div>

				<p class="submit"><input type="submit" name="submit" id="submit_form" class="button button-primary" value="Add New Item"></p></form></div>

			</div>
			</div><!-- /col-left -->

			<div id="col-right">
			<div class="col-wrap">

				<div class="form-wrap">
				<h2>Add New Tag For Content</h2>
				</div>


				<form id="modtag" method="post">
					<?php
					$this->items_obj->prepare_items();
					$this->items_obj->display();
					var_dump($this->items_obj);?>
				</form>


			</div>
			</div><!-- /col-right -->

			</div>
		</div>
	<?php
	}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Items',
			'default' => 5,
			'option'  => 'items_per_page'
		];

		add_screen_option( $option, $args );

	}


}
$id=0;

// Add shortag from added content
function tagcon($id){
    	$data = Items_List::get_tagcon_item_id($id[0]);
			return $data[0]['content'];
    }

add_shortcode('tagcon', 'tagcon', $id);


function content(){
	$new = new SP_Content;
	$new->plugin_settings_page();
}
