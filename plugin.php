<?php

/*
Plugin Name: WP-TagCon
Plugin URI: http://neutronik.pl
Description: Automaticly add content to posts based on selected tag
Version: 1.0
Author: Krzysztof Grygiel
Author URI:  http://neutronik.pl
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once plugin_dir_path(__FILE__) . '/content.php';
require_once plugin_dir_path(__FILE__) . '/add_content.php';


class Items_List {

	/** Class constructor */
	public function __construct() {

	}


	/**
	 * Retrieve customers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_items( $per_page = 5, $page_number = 1, $table ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}".$table;

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' DESC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	public static function get_items_ce( $table ) {

		global $wpdb;

		$sql_results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}{$table} ORDER BY id DESC");
		return $sql_results;
	}

	/**
	 * Delete a item record.
	 *
	 * @param int $id customer ID
	 */
	public static function delete_item( $id,$table ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}".$table,
			[ 'id' => $id ],
			[ '%d' ]
		);
	}

	public static function get_tag_item( ) {
		global $wpdb;
		$sql = "SELECT name,slug FROM {$wpdb->prefix}terms WHERE name<>''";
		$sql .= ' ORDER BY ' . 'name';

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	public static function get_title_item( ) {
		global $wpdb;
		$sql = "SELECT title FROM {$wpdb->prefix}tagcon_content";
		$sql .= ' ORDER BY ' . 'title';

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	public static function get_tagcon_item($table) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}".$table;
		$sql .= ' ORDER BY ' . 'id';

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	public static function link_content_by_id($id) {
		global $wpdb;
		$id = intval($id);
		$sql = "SELECT id FROM {$wpdb->prefix}tagcon WHERE content_id = {$id}";

		$data = $wpdb->get_results( $sql);
		$id = intval($data[0]->id);

		$sql = "SELECT content FROM {$wpdb->prefix}tagcon_content WHERE id = {$id}";

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return $result;
	}

	public static function get_tagcon_item_content( ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}tagcon_content";
		$sql .= ' ORDER BY ' . 'title';

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}


	/**
	 * Add a item record.
	 *
	 * @param int $id customer ID
	 */
	public static function add_item( $tag, $title ) {
		global $wpdb;

		$sql = "SELECT id FROM {$wpdb->prefix}tagcon_content WHERE title = '{$title}'";
		$result = $wpdb->get_results($sql);
		$id = intval($result[0]->id);

		$wpdb->insert(
			"{$wpdb->prefix}tagcon",
			[ 'status' => 0, 'content_id' => $id, 'tag' => $tag, 'title' => $title ],
			[ '%d', '%d', '%s', '%s']
		);

		$sql = "SELECT id FROM {$wpdb->prefix}tagcon WHERE title = '{$title}'";
		$result = $wpdb->get_results($sql);
		$id = intval($result[0]->id);
		$wpdb->update(
			"{$wpdb->prefix}tagcon",
			[ 'shortcode' => '[tagcon '.$id.']' ],
			['id' => $id],
			[ '%s' ],
			[ '%d' ]
		);
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}tagcon";

		return $wpdb->get_var( $sql );
	}

}


class SP_Plugin {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $items_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_menu() {

		$hook = add_menu_page(
			'WP TagCon',
			'WP TagCon',
			'manage_options',
			'wp-tagcon_dashboard',
			[ $this, 'plugin_settings_page' ]
		);
		add_submenu_page('wp-tagcon_dashboard', 'Content', 'Content', 'manage_options', 'content', 'content');
		add_submenu_page('wp-tagcon_dashboard', 'Add To Content', 'Add To Content', 'manage_options', 'add_to_content', 'add_to_content');
		add_action( "load-$hook", [ $this, 'screen_option' ] );

		add_action('admin_enqueue_scripts', 'ln_reg_css_and_js');

		    function ln_reg_css_and_js($hook)
		    {

		    $current_screen = get_current_screen();

		    if ( strpos($current_screen->base, 'wp-tagcon') === false) {
		        return;
		    } else {

		        wp_enqueue_style('boot_css', plugins_url('css/custom.css',__FILE__ ));
		        //wp_enqueue_script('boot_js', plugins_url('inc/bootstrap.js',__FILE__ ));
		        //wp_enqueue_script('ln_script', plugins_url('inc/main_script.js', __FILE__), ['jquery'], false, true);
		        }
		    }

	}


	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
		?>
		<div class="wrap">
			<h2>WP TagCon</h2>

			<div id="col-container" class="wp-clearfix">

			<div id="col-left">
			<div class="col-wrap">

				<div class="form-wrap">
				<h2>Add New Tag For Content</h2>


			</div>
			</div>
			</div><!-- /col-left -->

			<div id="col-right">
			<div class="col-wrap">


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

		$this->items_obj = new Items_List();
	}

	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}



add_action( 'plugins_loaded', function () {
	SP_Plugin::get_instance();
} );
