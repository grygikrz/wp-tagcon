<?php

class ItemsContent extends WP_List_Table {

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
			case 'id':
			case 'status':
			case 'tag':
			case 'title':
			case 'shortcode':
			case 'edit':
				return $item->$column_name;
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
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->id
		);
	}


	function column_edit($item) {
	    return sprintf(
	            '<a href="admin.php?page=add_to_content&id=%s#edit-tag" >Edit</a>', $item->id
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
			'id'    => __( 'id', 'sp' ),
			'tag'    => __( 'tag', 'sp' ),
			'title'    => __( 'title', 'sp' ),
			'shortcode'    => __( 'shortcode', 'sp' ),
			'edit' => __( 'edit', 'sp' ),
			'action' => __( 'action', 'sp' )
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
			'id' => array( 'id', true ),
			'tag' => array( 'tag', false )
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
		$columns  = $this->get_columns();
			$hidden   = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array(
					$columns,
					$hidden,
					$sortable
			);

			# >>>> Pagination
			$per_page     = 5;
			$current_page = $this->get_pagenum();
			$total_items  = Items_List::record_count();
			$this->set_pagination_args( array (
					'total_items' => $total_items,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total_items / $per_page )
			) );
			$last_post = $current_page * $per_page;
			$first_post = $last_post - $per_page + 1;
			$last_post > $total_items AND $last_post = $total_items;


			// SQL results
			$posts = Items_List::get_items_ce( 'tagcon' );
			empty( $posts ) AND $posts = array();

			// Setup the range of keys/indizes that contain
			// the posts on the currently displayed page(d).
			// Flip keys with values as the range outputs the range in the values.
			$range = array_flip( range( $first_post - 1, $last_post - 1, 1 ) );

			// Filter out the posts we're not displaying on the current page.
			$posts_array = array_intersect_key( $posts, $range );
			# <<<< Pagination

			// Prepare the data
			$permalink = __( 'Edit:' );
			foreach ( $posts_array as $key => $post )
			{
					$link     = get_edit_post_link( $post->id );
					$no_title = __( 'No title set' );
					$title    = ! $post->tag ? "<em>{$no_title}</em>" : $post->tag;
					$posts[ $key ]->tag = "<a title='{$permalink} {$title}' href='{$link}'>{$title}</a>";
			}
			$this->items = $posts_array;

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
				Items_List::delete_item( absint( $_GET['item'] ) );

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
				Items_List::delete_item( $id );

			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		        // add_query_arg() return the current url
		        wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
		}
	}

}


class SP_AddContent {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $items_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		$this->items_obj = new ItemsContent();
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}


	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {

		if (!empty($_POST["link-tag"])) {
			echo 'fireeeeee';
			var_dump($_POST);
    	Items_List::add_item($_POST['tag-name'],$_POST['content-title']);
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
				<h2>Link Tag For Content</h2>
				<form id="link-tag" method="post" action="" class="validate">
				<div class="form-field form-required term-name-wrap">
					<label for="tag-name">Tags</label>
					<select name='tag-name'>
			<?php
			$tags = Items_List::get_tag_item();
			foreach ($tags as $tag)
			{
					echo "<option value='" . $tag['slug'] ."'>" . $tag['name']."</option>";
			}
			?>
					</select>
					<p>Choose your tag</p>
				</div>
				<div class="form-field form-required term-name-wrap">
					<label for="tag-name">Title of your content</label>
					<select name='content-title'>
			<?php
			$titles = Items_List::get_title_item();
			foreach ($titles as $title)
			{
					echo "<option value='" . $title['title'] ."'>" . $title['title']."</option>";
			}
			?>
					</select>
					<p>Choose your tag</p>
				</div>

				<p class="submit"><input type="submit" name="link-tag" id="submit_form" class="button button-primary" value="Link tag to content"></p>
			</form>
		</div>

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
					?>
				</form>
				<form id="edit-tag" method="post" action="" class="validate">
				<input type="hidden" id="_wpnonce_add-tag" name="_wpnonce_add-tag" value="b93fe0a685">
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

	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

$id=0;

// Add shortag from added content
function tagcon($id){
    	$data = Items_List::link_content_by_id($id[0]);
			return $data[0]['content'];
    }

add_shortcode('tagcon', 'tagcon', $id);


function add_to_content(){
	$new = new SP_AddContent;
	$new->plugin_settings_page();
}
