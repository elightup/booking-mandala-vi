<?php
/*!
 * Plugin Name:  Booking mandala vi
 * Theme URI:    https://titanweb.vn/
 * Author:       eLightUp
 * Author URI:   https://titanweb.vn/
 * Description:  The plugin for Mandala website.
 * Version:      1.0.0
 * Text Domain:  mandala
 *
 */

// Prevent loading this file directly.
defined( 'ABSPATH' ) || die;

if ( ! function_exists( 'booking_mandala_load' ) ) {
	if ( file_exists( __DIR__ . '/vendor' ) ) {
		require __DIR__ . '/vendor/autoload.php';
	}

	add_action( 'plugins_loaded', 'booking_mandala_load');

	function booking_mandala_load() {

		define( 'BM_DIR', __DIR__ );
		define( 'BM_PATH', plugin_dir_path( __FILE__ ) );
		define( 'BM_URL', plugin_dir_url( __FILE__ ) );
		define( 'BM_VER', '1.0.0' );

		load_plugin_textdomain( 'mandala', false, basename( BM_DIR ) . '/languages' );
		new BM\Add_css_js;
		new BM\Init;
		new BM\Ajax;
		new BM\Shortcode;
	}
}