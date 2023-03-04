<?php
namespace BM;

class Ajax {
		/**
		 * @var bool
		 */
	protected static $_loaded = false;

	public function __construct() {
		if ( self::$_loaded ) {
			return;
		}

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}

		$this->init();

		self::$_loaded = true;
	}
	public function init() {
		// Define All Ajax function
		$arr_ajax = array(
			'discount',
			// 'check_discount',
			'delete_cake_admin',
		);

		foreach ( $arr_ajax as $val ) {
			add_action( 'wp_ajax_' . $val, array( $this, $val ) );
			add_action( 'wp_ajax_nopriv_' . $val, array( $this, $val ) );
		}
	}

	public function delete_cake_admin() {
		delete_transient( 'data_hotel' );
		delete_transient( 'data_discount_hotel' );
		wp_send_json_success( true );
		wp_die();
	}

	public function discount() {
		ob_start();
		$hotel_id      = isset( $_POST['hotel_id'] ) ? ( $_POST['hotel_id'] ) : '';
		$check_in      = isset( $_POST['date_start'] ) ? ( $_POST['date_start'] ) : '';
		$time_start    = explode( '/', $check_in );
		$start         = implode( '-', $time_start );
		$check_out     = isset( $_POST['date_end'] ) ? ( $_POST['date_end'] ) : '';
		$time_end      = explode( '/', $check_out );
		$end           = implode( '-', $time_end );
		$discount_code = isset( $_POST['discount'] ) ? ( $_POST['discount'] ) : '';
		$codes         = [];
		$discounts     = [];
		$args          = array(
			'post_type'      => 'promotions',
			'posts_per_page' => -1,
		);
		$query         = new \WP_Query( $args );
		if ( $query->have_posts() ) :
			while ( $query->have_posts() ) :
				$query->the_post();
				$id              = get_the_ID();
				$promotions_code = rwmb_meta( 'promotions_code' );
				$date_start      = rwmb_meta( 'time_start' );
				$date_end        = rwmb_meta( 'time_end' );
				$hotels          = rwmb_meta( 'select_system_holtel' );
				$hidden_code     = rwmb_meta( 'check_show_promotions' );
				if ( ! $hidden_code
				&& ( strtotime( $date_start ) <= strtotime( $start )
				&& strtotime( $date_end ) >= strtotime( $start ) )
				|| ( strtotime( $date_start ) <= strtotime( $end )
				&& strtotime( $date_end ) >= strtotime( $end ) ) ) {
					foreach ( $hotels as $hotel ) {
						$codes[ $hotel ][ $id ] = $promotions_code;
					}
				}

			endwhile;
		endif;
		wp_reset_postdata();
		foreach ( $codes as $key => $code ) :
			$discounts[ $key ] = array_unique( $code );
		endforeach;
		foreach ( $discounts as $key => $discount ) {

			if ( $hotel_id == $key ) {
				foreach ( $discount as $k => $value ) {
					?>
					<option value="<?php echo $value; ?>" <?php //selected( $value, $discount_code ); ?> data-hotel="<?= esc_attr( $key );?>"><?php echo get_the_title( $k ); ?></option>
					<?php
				}
			}
		}
		$result = ob_get_clean();
		wp_send_json_success( $result );
		wp_die();
	}
	// public function check_discount() {
	// ob_start();
	// $discount_code = isset( $_POST['discount_code'] ) ? ( $_POST['discount_code'] ) : '';
	// $code          = rwmb_meta( 'promonotions', [ 'object_type' => 'setting' ], 'basic_auth' );
	// $arr_code      = explode( ', ', $code );
	// if ( ! in_array( $discount_code, $arr_code ) ) {
	// $result = false;
	// } else {
	// $result = true;
	// }
	// wp_send_json_success( $result );
	// wp_die();
	// }
}
