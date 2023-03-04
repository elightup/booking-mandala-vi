<?php
namespace BM;

class Add_css_js {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_booking_mandala' ) );
		add_action( 'admin_footer', array( $this, 'enqueue_booking_admin' ) );
	}

	public function enqueue_booking_mandala() {
		wp_enqueue_style( 'bm_css', BM_URL . 'assets/booking_mandala.css', '', BM_VER );
		wp_enqueue_style( 'jquery-ui', BM_URL . 'assets/jquery-ui/jquery-ui.min.css', '', BM_VER );
		wp_enqueue_script( 'jquery-ui', BM_URL . 'assets/jquery-ui/jquery-ui.js', array( 'jquery' ), BM_VER );
		wp_enqueue_style( 'select2', BM_URL . 'assets/select2/select2.min.css', '', BM_VER );
		wp_enqueue_script( 'select2', BM_URL . 'assets/select2/select2.min.js', array( 'jquery' ), BM_VER );
		wp_enqueue_script( 'list', BM_URL . 'assets/list.min.js', array( 'jquery' ), '1.5.0' );
		wp_enqueue_script( 'bm_js', BM_URL . 'assets/booking_mandala.js', array( 'jquery' ), BM_VER, true );
		wp_localize_script( 'bm_js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	public function enqueue_booking_admin() {
		wp_enqueue_script( 'am_js', BM_URL . 'assets/booking_admin.js', array( 'jquery' ), BM_VER, true );
		wp_localize_script( 'am_js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}
}
