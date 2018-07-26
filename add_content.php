<?php

class SP_AddContent {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $items_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		$this->items_obj = new Items_List();
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
					<label for="tag-name">Tag</label>
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
					echo 'yyyyyyyyyyytest';
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

	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

function add_to_content(){
	$new = new SP_AddContent;
	$new->plugin_settings_page();
}
