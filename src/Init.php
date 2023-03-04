<?php
namespace BM;

if ( ! class_exists( 'Init' ) ) {
	class Init {

		public function __construct() {
			$token = $this->get_token_global();
			define( 'BM_TOKEN', $token );
			add_filter( 'page_template', [ $this, 'booking_page_template' ] );
		}
		static function get_day_by_name( $name ) {
			$combo = strstr( $name, '[' );
			if ( ! $combo ) {
				return 1;
			}
			$day = substr( $combo, 3, 1 );
			if ( is_numeric( $day ) ) {
				return $day;
			} else {
				return 1;
			}
		}

		public function get_token_global() {
			$setting    = get_option( 'basic_auth' );
			$user_name  = $setting['user_name'];
			$pass       = $setting['password'];
			$basic      = $user_name . ':' . $pass;
			$basic_auth = base64_encode( $basic );
			$request    = wp_remote_get( 'https://api.hotellinksolutions.com/ota/mandala/token', [
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . $basic_auth,
				],
			] );
			$response   = json_decode( $request['body'], true );
			$token      = $response['data']['access_token'];
			return $token;
		}

		public static function get_data_hotel() {
			if ( get_transient( 'data_hotel' ) ) {
				return get_transient( 'data_hotel' );
			}
			$body_data = json_encode([
					'lang' => 'vi'
				]);
			$request_hotels  = wp_remote_post( 'https://api.hotellinksolutions.com/ota/mandala/hotelData', [
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . BM_TOKEN,
				],
				'body' => $body_data,
			] );
			$response_hotels = json_decode( $request_hotels['body'], true );
			$data_hotel      = $response_hotels['data']['hotels'];
			set_transient( 'data_hotel', $data_hotel, DAY_IN_SECONDS );
			return $data_hotel;
		}

		public static function get_room_data( $hotel_id ) {
			$start           = date( 'Y-m-d' );
			$t               = mktime( 0, 0, 0, date( 'm' ), date( 'd' ) + 1, date( 'Y' ) );
			$end             = date( 'Y-m-d', $t );
			$body_search     = json_encode([
				'start_date' => $start,
				'end_date'   => $end,
				'currency'   => 'VND',
				'lang'       => 'vi',
				'hotel_id'   => $hotel_id,
			]);
			$request_hotels  = wp_remote_post( 'https://api.hotellinksolutions.com/ota/Mandala/roomData', [
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . BM_TOKEN,
				],
				'body'    => $body_search,
			] );
			$response_hotels = json_decode( $request_hotels['body'], true );
			return $response_hotels['data']['rooms'];
		}

		static function get_data_room( $room_id, $hotel_id ) {
			$data_hotel = self::get_room_data( $hotel_id );
			$data_room  = [];
			foreach ( $data_hotel as $types ) {
				if ( $types['room_id'] != $room_id ) {
					continue;
				}
					$bed_room = $types['beds'];
					$beds     = [];
				foreach ( $bed_room as $bed ) {
					if ( $bed['quantity'] ) {
						$beds[] = $bed['quantity'] . ' ' . $bed['name'];
					}
				}
					$exbed_room = $types['extraBeds'];
					$exbeds     = [];
				foreach ( $exbed_room as $exbed ) {
					if ( $exbed['quantity'] ) {
						$exbeds[] = $exbed['quantity'] . ' ' . $exbed['name'];
					}
				}
					$text_exbed = ( $exbeds ) ? ' or ' . implode( ', ', $exbeds ) : '';
					$bed_rooms  = implode( ', ', $beds );
					$plan_room  = $types['rate_plans'];
					$extras     = $plan_room[0]['max_occupancy'];
					$data_room  = [
						'id'      => $types['room_id'],
						'images'  => $types['images'] ?: [],
						'name'    => $types['name'] ?: '',
						'size'    => $types['room_size'] ? $types['room_size']['value'] : '',
						'extras'  => $extras,
						'bed'     => $bed_rooms . $text_exbed,
						'desc'    => $types['description'] ?: '',
						'feature' => $types['features'],
						'price'   => $types['price']['min']['value'] ?: $types['price']['max']['value'],
					];
			}
			return $data_room;

		}

		static function get_data_rooms( $hotel_id ) {
			$data_hotel = self::get_room_data( $hotel_id ) ?: [];
			$data_rooms = [];
			foreach ( $data_hotel as $types ) {
					$bed_room = $types['beds'];
					$beds     = [];
				foreach ( $bed_room as $bed ) {
					if ( $bed['quantity'] ) {
						$beds[] = $bed['quantity'] . ' ' . $bed['name'];
					}
				}
					$exbed_room = $types['extraBeds'];
					$exbeds     = [];
				foreach ( $exbed_room as $exbed ) {
					if ( $exbed['quantity'] ) {
						$exbeds[] = $exbed['quantity'] . ' ' . $exbed['name'];
					}
				}
					$text_exbed   = ( $exbeds ) ? ' or ' . implode( ', ', $exbeds ) : '';
					$bed_rooms    = implode( ', ', $beds );
					$plan_room    = $types['rate_plans'];
					$extras       = $plan_room[0]['max_occupancy'];
					$data_rooms[] = [
						'id'      => $types['room_id'],
						'images'  => $types['images'] ? $types['images'] : '',
						'name'    => $types['name'] ?: '',
						'size'    => $types['room_size'] ? $types['room_size']['value'] : '',
						'extras'  => $extras,
						'bed'     => $bed_rooms . $text_exbed,
						'desc'    => $types['description'] ?: '',
						'feature' => $types['features'],
						'price'   => $types['price']['min']['value'] ?: $types['price']['max']['value'],
					];
			}
			return $data_rooms;
		}

		public function booking_page_template( $page_template ) {
			if ( is_page( 'booking' ) ) {
				$page_template = BM_PATH . 'booking.php';
			}
			return $page_template;
		}

		// static function get_discount_hotel() {

		// }
	}
}
