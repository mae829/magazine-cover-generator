<?php
/**
 * Plugin Name: Magazine Cover Generator
 * Description: Plugin to generate magazine covers with user uploaded images
 * Version: 1.0
 * Author: Mike Estrada
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die ( 'YOU SHALL NOT PASS!' );
}

define( 'MCG_DIR', plugin_dir_path(__FILE__) );
define( 'MCG_URL', plugin_dir_url(__FILE__) );
define( 'MCG_BASE', plugin_basename( __FILE__ ) );
define( 'MCG_VERSION', '1.0' );
// define( 'MCG_MAX_UPLOAD_SIZE', 1048576 ); // 1MB in bytes
// define( 'MCG_MAX_UPLOAD_SIZE', 2097152 ); // 2MB in bytes
define( 'MCG_MAX_UPLOAD_SIZE', 3145728 ); // 3MB in bytes
define( 'MCG_TYPE_WHITELIST', serialize( array(
	'image/jpeg',
	'image/png'
) ) );

if ( !class_exists( 'Magazine_Cover_Generator_Setup' ) ) {

	class Magazine_Cover_Generator_Setup {

		static $instance = false;

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

		public function __construct() {

			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts_and_styles' ) );

			// check if CMB2 is loaded. if not BAIL
			if ( defined( 'CMB2_LOADED' ) ) {

				// set up settings page
				if ( file_exists( MCG_DIR .'inc/magazine-cover-generator-admin.php' ) ){

					require_once MCG_DIR .'inc/magazine-cover-generator-admin.php';
					add_action( 'init', array( 'Magazine_Cover_Generator_Admin', 'singleton' ), 10 );

				}

				// set up shortcode
				if ( file_exists( MCG_DIR .'inc/magazine-cover-generator-shortcode.php' ) ){

					require_once MCG_DIR .'inc/magazine-cover-generator-shortcode.php';
					add_action( 'init', array( 'Magazine_Cover_Generator_Shortcode', 'singleton' ), 10 );

				}

			} else {

				// Display error if CMB2 is not IN USE
				echo '<div class="error">
						<p>This plugin is dependent of CMB2 plugin. Please <strong>activate CMB2</strong>.</p>
					</div>';
			}

		}

		public function register_scripts_and_styles() {

			wp_register_style( 'magazine-cover-generator-css', MCG_URL .'css/magazine-cover-generator.min.css', array(), MCG_VERSION );
			wp_register_script( 'magazine-cover-generator-js', MCG_URL .'js/image.editor.min.js', array( 'jquery' ), null, true );

		}

	}

	//load after all plugins are loaded, so we can check if CMB2 is loaded (was originally set up with CMB2)
	add_action( 'plugins_loaded', array( 'Magazine_Cover_Generator_Setup', 'singleton' ) );

}
