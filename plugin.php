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
	public static function get_items( $per_page = 5, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}tagcon";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}


	/**
	 * Delete a item record.
	 *
	 * @param int $id customer ID
	 */
	public static function delete_item( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}tagcon",
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

	public static function get_tagcon_item( ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}tagcon";
		$sql .= ' ORDER BY ' . 'tag';

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	public static function get_tagcon_item_id($id) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}tagcon WHERE id = ".$id;
		$sql .= ' ORDER BY ' . 'tag';

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	/**
	 * Add a item record.
	 *
	 * @param int $id customer ID
	 */
	public static function add_item( $tag, $content ) {
		global $wpdb;

		$wpdb->insert(
			"{$wpdb->prefix}tagcon",
			[ 'status' => 0, 'tag' => $tag, 'content' => $content ],
			[ '%d', '%s', '%s']
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
			'wp_tagcon/plugin.php',
			[ $this, 'plugin_settings_page' ]
		);
		add_submenu_page('wp_tagcon/plugin.php', 'Content', 'Content', 'manage_options', 'wp-tagcon/content.php', 'content');
		add_submenu_page('wp_tagcon/plugin.php', 'Add To Content', 'Add To Content', 'manage_options', 'wp-tagcon/add_content.php', 'add_to_content');
		add_action( "load-$hook", [ $this, 'screen_option' ] );

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
