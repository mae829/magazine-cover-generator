<?php

if ( ! defined( 'WP_DFP_ADS_DIR' ) ) {
	exit;
}

class Magazine_Cover_Generator_Admin {

	static $instance	= false;
	private $key		= 'mcg_settings';
	private $metabox_id	= 'mcg_metabox';

	public function __construct() {

		$this->_add_actions();
	}

	/**
	 * Singleton
	 *
	 * Returns a single instance of the current class.
	 */
	public static function singleton() {

		if ( !self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Register our setting to WP
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Register the administration page.
	 *
	 */
	public function menu() {

		global $cover_generator_options_page;

		$cover_generator_options_page	= add_management_page( 'MagCover Generator', 'MagCover Generator', 'manage_options', 'magazine-cover-generator', array( $this, 'admin_page' ) );

		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-{$cover_generator_options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );

	}

	/**
	 * Page Templating: Manage the Plugin Settings Page.
	 */
	public function admin_page() { ?>

		<div class="wrap cmb2-options-page <?php echo $this->key; ?>">

			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<hr/>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>

		</div>

		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 */
	public function add_options_page_metabox() {

		// hook in our save notices
		add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );

		// Set up CMB2 fields
		$cmb = new_cmb2_box( array(
			'id'			=> $this->metabox_id,
			'hookup'		=> false,
			'cmb_styles'	=> false,
			'show_on'		=> array(
				'key'	=> 'options-page',
				'value'	=> array( $this->key, )
			),
		) );

		$cmb->add_field( array(
			'id'   => 'covers_list',
			'name' => 'Covers',
			'desc' => 'Image files to be used for the covers',
			'type' => 'file_list',
		) );

		$cmb->add_field( array(
			'id'			=> 'bg_colors',
			'name'			=> 'Background Colors',
			'desc'			=> 'Background colors of the covers',
			'type'			=> 'colorpicker',
			'default'		=> '#ffffff',
			'repeatable'	=> true,
			'text'			=> array(
				'add_row_text'	=> 'Add another color'
			)
		) );

	}

	/**
	 * Register settings notices for display
	 *
	 */
	public function settings_notices( $object_id, $updated ) {

		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}

		add_settings_error( $this->key . '-notices', '', __( 'Settings updated.', 'cgi' ), 'updated' );
		settings_errors( $this->key . '-notices' );

	}

	/**
	 * Public getter method for retrieving protected/private variables
	 */
	public function __get( $field ) {

		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );

	}

	/**
	 * Add Actions
	 *
	 * Defines all the WordPress actions and filters used by this class.
	 */
	protected function _add_actions() {

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );

	}

}

/**
 * Wrapper function around cmb2_get_option
 */
function cover_generator_get_option( $key = '' ) {
	return function_exists('cmb2_get_option') ? cmb2_get_option( Magazine_Cover_Generator_Admin::singleton()->key, $key ) : '';
}
