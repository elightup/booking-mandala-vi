<?php
namespace BM;

class Shortcode {

	public $shortcode = 'booking_mandala';

	public function __construct() {
		// add shortcode
		add_shortcode( $this->shortcode, array( $this, 'booking_mandala_shortcode' ) );
	}

	public function booking_mandala_shortcode() {
		ob_start();
		$this->booking_template_shortcode();
		return ob_get_clean();
	}

	public function booking_template_shortcode() {
		$data_hotel = Init::get_data_hotel();
		foreach ( $data_hotel as $value ) {
			$hotels[] = [
				'id'   => $value['hotel_id'],
				'name' => $value['name'],
			];
		}
		$h             = '62285593-3bcf-1635130380-4f06-9e5f-bbabb06126ae';
		$d             = date( 'd/m/Y' );
		$time          = isset( $_GET['time'] ) ? $_GET['time'] : 1;
		$t             = mktime( 0, 0, 0, date( 'm' ), date( 'd' ) + $time, date( 'Y' ) );
		$m             = date( 'd/m/Y', $t );
		$hotel_id      = isset( $_GET['hotel-id'] ) ? $_GET['hotel-id'] : $h;
		$check_in      = isset( $_GET['check-in'] ) ? $_GET['check-in'] : $d;
		$check_out     = isset( $_GET['check-out'] ) ? $_GET['check-out'] : $m;
		$code          = rwmb_meta( 'promonotions', [ 'object_type' => 'setting' ], 'basic_auth' );
		$discount_code = isset( $_GET['discount'] ) ? ( $_GET['discount'] ) : '';
		?>
		<form class="container-booking-mandala" data-code="<?= esc_attr( $code );?>" method="get" action="<?php echo home_url( '/' ) . 'booking' ?>">
			<div class="form-booking">
					<div class="bm_row">
						<label><img src="<?php echo BM_URL . 'assets/image/pin.png'; ?>" alt="Destination"><?php echo esc_html__( 'Điểm đến', 'mandala' ) ?></label>
						<select class="select-destination" name="hotel-id">
							<?php
							foreach ( $hotels as $hotel ) {
								?>
								<option value="<?php echo $hotel['id']; ?>" <?php selected( $hotel['id'], $hotel_id ); ?>><?php echo $hotel['name'] ?></option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="bm_row">
							<label><img src="<?php echo BM_URL . 'assets/image/calendar.png'; ?>" alt="Ngày đến - Ngày đi">Ngày đến <span class="checkin-checkout">-</span> Ngày đi</label>
							<div class="check-time">
								<input type="text"
								name="check-in"
								value="<?php echo $check_in; ?>"
								class="datepicker_start"
								placeholder="Check in"
								autocomplete="nope" autocorrect="off" autocapitalize="none">
								<input type="text"
								name="check-out"
								value="<?php echo $check_out; ?>"
								class="datepicker_end"
								placeholder="Check out"
								autocomplete="nope" autocorrect="off" autocapitalize="none">
							</div>
					</div>
					<div class="bm_row">
						<label><img src="<?php echo BM_URL . 'assets/image/price-tag.png'; ?>" alt="Discount"><?php echo esc_html__( 'Mã khuyến mại', 'mandala' ) ?></label>
						<!--input type="text" name="discount" value="< ?php echo $discount; ?>" placeholder="Your discount code"-->
						<?php
						$time_start    = explode( '/', $check_in );
						$start         = implode( '-', $time_start );
						$time_end      = explode( '/', $check_out );
						$end           = implode( '-', $time_end );
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
						?>
						<select class="select-discount" name="discount" data-discount="<?= $discount_code; ?>">
							<?php if ( is_page( 'booking' ) ) { ?>
								<option value=""></option>
							<?php } ?>
							<?php if ( ! $discounts && $discount_code ) { ?>
								<option value="<?php echo $discount_code; ?>" selected><?php echo $discount_code ?></option>
							<?php } ?>
							<?php foreach ( $discounts as $key => $discount ) :
								if ( $hotel_id === $key ) :
									$check = [];
									foreach ( $discount as $k => $value ) :
										$check[] = $value;
									endforeach;
									if ( in_array( $discount_code, $check ) || ( $discount && ! $discount_code ) ) {
										foreach ( $discount as $k => $value ) :
											?>
											<option value="<?php echo $value; ?>" <?php selected( $discount_code, $value ); ?> data-hotel="<?= esc_attr( $key );?>"><?php echo get_the_title( $k ) ?></option>
										<?php endforeach ?>
										<?php
									} else {
										if ( $discount_code ) {
											?>
											<option value="<?php echo $discount_code; ?>" selected><?php echo $discount_code ?></option>
											<?php
										}
									}
									?>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
					</div>
					<button class="check-valid">Kiểm Tra Phòng</button>
			</div>
		</form>
		<?php
	}

}
