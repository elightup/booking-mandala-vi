<?php
get_header();
?>
<main id="primary" class="site-main booking">
<?php
$image = rwmb_meta( 'image' );
$title = rwmb_meta( 'title_banner' );
?>
<section class="banner-hero">
	<img src="<?= esc_url( $image['full_url'] );?>" width="<?= esc_attr( $image['width'] )?>" height="<?= esc_attr( $image['height'] )?>" alt="">
	<div class="banner-hero__wrap">
		<div class="container">
			<div class="banner-hero__inner">
				<h1><?= esc_html( $title );?></h1>
				<nav class="breadcrumbs" aria-label="Breadcrumbs">
					<a href="<?php echo home_url(); ?>" class="breadcrumb breadcrumb--first">Trang Chủ</a>
					<span class="breadcrumbs__separator">></span>
					<span class="breadcrumb bk-cl">Chọn Khách Sạn</span>
					<span class="breadcrumbs__separator">></span>
					<a href="<?php echo home_url() . '/booking/'; ?>" class="breadcrumb breadcrumb--second bk-cl2">Đặt phòng</a>
					<span class="breadcrumbs__separator">></span>
					<span class="breadcrumb bk-cl">Thanh Toán</span>
				</nav>
				<?= do_shortcode( '[booking_mandala]' );?>
			</div>
		</div>
	</div>
</section>
<?php
$data_hotel = BM\Init::get_data_hotel();
$hotel_id   = isset( $_GET['hotel-id'] ) ? $_GET['hotel-id'] : '62285593-3bcf-1635130380-4f06-9e5f-bbabb06126ae';
$id_room    = isset( $_GET['id_room'] ) ? $_GET['id_room'] : '';
$time       = isset( $_GET['time'] ) ? $_GET['time'] : 1;
$d          = date( 'd/m/Y' );
$t          = mktime( 0, 0, 0, date( 'm' ), date( 'd' ) + $time, date( 'Y' ) );
$m          = date( 'd/m/Y', $t );

$check_in   = isset( $_GET['check-in'] ) ? $_GET['check-in'] : $d;
$check_out  = isset( $_GET['check-out'] ) ? $_GET['check-out'] : $m;
$discount   = isset( $_GET['discount'] ) ? $_GET['discount'] : '';
$time_start = explode( '/', $check_in );
$time_end   = explode( '/', $check_out );
$adults     = 1;
$children   = 0;
$start      = implode( '-', $time_start );
$end        = implode( '-', $time_end );
$day_in     = strtotime( $start );
$day_in     = date( 'd+M+Y', $day_in );
$day_out    = strtotime( $end );
$day_out    = date( 'd+M+Y', $day_out );
$end_time   = strtotime( $end );
$start_time = strtotime( $start );
$day        = ( $end_time - $start_time ) / ( 24 * 3600 );

$body_search     = json_encode([
	'start_date'         => $start,
	'end_date'           => $end,
	'currency'           => 'VND',
	'lang'               => 'vi',
	'special_offer_code' => $discount,
	'travelers'          => [
		[
			'adults'   => $adults,
			'children' => $children,
		],
	],
	'hotels'             => [
		[
			'hotel_id'     => $hotel_id,
			'partner_code' => '',
		],
	],
]);
$request_search  = wp_remote_post( 'https://api.hotellinksolutions.com/ota/mandala/hotelAvailability', [
	'headers' => [
		'Content-Type'  => 'application/json',
		'Authorization' => 'Bearer ' . BM_TOKEN,
	],
	'body'    => $body_search,
] );
$response_search = json_decode( $request_search['body'], true );
if ( empty( $response_search['data']['hotels']) || ( $response_search['data']['hotels'][0]['response_type'] == 'unavailable'   ) ) {
	?>
	<h2 class="data-bug">No data available</h2>
	<?php
	return get_footer();
}
$room_ids   = array_filter( $response_search['data']['hotels'][0]['room_types'] );
$data_rooms = [];
foreach ( $data_hotel as $value ) {
	if ( $hotel_id != $value['hotel_id'] ) {
		continue;
	}
	$time_hotel = $value['booking_time'];
	$name_hotel = $value['name'];
	foreach ( $value['room_types'] as $types ) {

		if ( ! in_array( $types['room_id'], $room_ids ) ) {
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
		$text_exbed = ( $exbeds ) ? ' or '.implode( ', ',$exbeds) : '';
		$bed_rooms = implode( ', ', $beds );
		$plan_room = $types['rate_plans'];
		$extras    = $plan_room[0]['max_occupancy'];

		$data_rooms[] = [
			'id'         => $types['room_id'],
			'images'     => $types['images'] ? $types['images'] : '',
			'name'       => $types['name'] ?: '',
			'size'       => $types['room_size'] ? $types['room_size']['value'] : '',
			'extras'     => $extras,
			'bed'        => $bed_rooms.$text_exbed,
			'desc'       => $types['description'] ?: '',
			'feature'    => $types['features'],
			'rate_plans' => $types['rate_plans'],
		];

	}
}
$room_rates = array_filter( $response_search['data']['hotels'][0]['room_rates'] );
$data_rates = [];
foreach ( $room_rates as $room_rate ) {
	$rate_by_date = $room_rate['special_offers'] ? $room_rate['special_offers'][0]['rate_by_date'] : $room_rate['rate_by_date'];
	$rates        = [];
	foreach ( $rate_by_date as $rate ) {
		$rates[] = $rate['rate'];
	}
	$rates        = $rates ?: [ 0 ];
	$data_rates[] = [
		'room_id'         => $room_rate['room_id'],
		'plan_id'         => $room_rate['plan_id'],
		'price_s'         => $room_rate['price']['total_price'],
		'unit_price'      => [
			'unit_price'   => $room_rate['unit_price'] ? $room_rate['unit_price'] : [],
			's_unit_price' => $room_rate['special_offers'] ? $room_rate['special_offers'][0]['unit_price'] : [],
		],
		'so'              => $room_rate['special_offers'] ? $room_rate['special_offers'][0]['plan_id'] : $room_rate['plan_id'],
		'name_so'         => $room_rate['special_offers'] ? $room_rate['special_offers'][0]['name'] : '',
		'note_so'         => $room_rate['special_offers'] ? $room_rate['special_offers'][0]['booking_condition_note'] : $room_rate['booking_condition_note'],
		'other_so'        => $room_rate['special_offers'] ? $room_rate['special_offers'][0]['booking_condition_policies'] : $room_rate['booking_condition_policies'],
		'con_so'          => $room_rate['special_offers'] ? $room_rate['special_offers'][0]['booking_condition'] : $room_rate['booking_condition'],
		'pay_so'          => $room_rate['special_offers'] ? $room_rate['special_offers'][0]['payment_policy'] : $room_rate['payment_policy'],
		'desc_so'         => $room_rate['special_offers'] ? $room_rate['special_offers'][0]['description'] : '',
		'price'           => (float) min( $rates ),
		'rooms_remaining' => $room_rate['rooms_remaining'],
	];
}
$data_plan = [];
foreach ( $data_rates as $values ) {
	$id = $values['room_id'];
	if ( isset( $data_plan[ $id ] ) ) {
		$data_plan[ $id ]['unit_price'] [] = $values['unit_price'];
		$data_plan[ $id ]                  = [
			'price'           => ( $data_plan[ $id ]['price'] >= $values['price'] ) ? $values['price'] : $data_plan[ $id ]['price'],
			'plan_id'         => $data_plan[ $id ]['plan_id'] . ',' . $values['plan_id'],
			'unit_price'      => $data_plan[ $id ]['unit_price'],
			'name_so'         => $data_plan[ $id ]['name_so'] . ',' . $values['name_so'],
			'note_so'         => $data_plan[ $id ]['note_so'] . '/' . $values['note_so'],
			'con_so'          => $data_plan[ $id ]['con_so'] . '/' . $values['con_so'],
			'other_so'        => $data_plan[ $id ]['other_so'] . '/' . $values['other_so'],
			'pay_so'          => $data_plan[ $id ]['pay_so'] . '/' . $values['pay_so'],
			'desc_so'         => $data_plan[ $id ]['desc_so'] . '/' . $values['desc_so'],
			'so'              => $data_plan[ $id ]['so'] . ',' . $values['so'],
			'rooms_remaining' => $values['rooms_remaining'],
			'price_s'         => $data_plan[ $id ]['price_s'] . '/' . $values['price_s'],
		];
	} else {
		$data_plan[ $id ]['unit_price'] [] = $values['unit_price'];
		$data_plan[ $id ]                  = [
			'price'           => $values['price'],
			'plan_id'         => $values['plan_id'],
			'unit_price'      => $data_plan[ $id ]['unit_price'],
			'name_so'         => $values['name_so'],
			'note_so'         => $values['note_so'],
			'con_so'          => $values['con_so'],
			'pay_so'          => $values['pay_so'],
			'desc_so'         => $values['desc_so'],
			'other_so'        => $values['other_so'],
			'so'              => $values['so'],
			'rooms_remaining' => $values['rooms_remaining'],
			'price_s'         => $values['price_s'],
		];
	}
}

foreach ( $data_rooms as $keys => $rooms ) {
	foreach ( $data_plan as $key => $value ) {
		if ( $rooms['id'] != $key ) {
			continue;
		}
		$data_rooms[ $keys ]['price'] = $value['price'];
		$plan_ids                     = explode( ',', $value['plan_id'] );
		$sos                          = explode( ',', $value['so'] );
		$name_sos                     = explode( ',', $value['name_so'] );
		$con_sos                      = explode( '/', $value['con_so'] );
		$other_sos                    = explode( '/', $value['other_so'] );
		$note_sos                     = explode( '/', $value['note_so'] );
		$pay_sos                      = explode( '/', $value['pay_so'] );
		$desc_sos                     = explode( '/', $value['desc_so'] );
		$price_s                      = explode( '/', $value['price_s'] );
		foreach ( $rooms['rate_plans'] as $room ) {
			foreach ( $plan_ids as $key => $plan_id ) {
				if ( $plan_id != $room['plan_id'] ) {
					continue;
				}
				$data_rooms[ $keys ]['plan_ids'][] = [
					'id_plan'         => $room['plan_id'],
					'name'            => $room['name'],
					'meal_plan'       => $room['meal_plan'],
					'inclusions_name' => $room['inclusions_name'],
					'inclusions_desc' => $room['inclusions_desc'],
					'max_occupancy'   => $room['max_occupancy'],
					'unit_price'      => isset( $value['unit_price'][ $key ] ) ? $value['unit_price'][ $key ] : '',
					'rooms_remaining' => $value['rooms_remaining'],
					'so'              => $sos[ $key ],
					'name_so'         => $name_sos[ $key ],
					'con_so'          => $con_sos[ $key ],
					'other_so'        => $other_sos[ $key ],
					'note_so'         => $note_sos[ $key ],
					'pay_so'          => $pay_sos[ $key ],
					'desc_so'         => $desc_sos[ $key ],
					'price_s'         => $price_s[ $key ],
				];
			}
		}
	}
}

?>
<div class="booking-mandala container">
	<div id="list-booking" class="container list-booking">
		<?php
		if ( ! $id_room ) {
			?>
			<h3 id="text-search" class="text-search"><?php echo count( $data_rooms ) . ' kết quả tìm kiếm' ?></h3>
			<?php
			if ( count( $data_rooms ) == 0 ) {
				?>
				<h3 id="text-search" class="text-search">Không còn phòng trống</h3>
				<?php
			}
		} else {
			$check_room = [];
			foreach ( $data_rooms as $rooms ) {
				$check_room[] = $rooms['id'];
			}
			if ( ! in_array( $id_room, $check_room ) ) {
				?>
			<h3 id="text-search" class="text-search">Phòng này không có sẵn. Vui lòng chọn phòng khác</h3>
				<?php
			}
		}
		?>
		<div class="list list-item">
			<?php
			foreach ( $data_rooms as $rooms ) {
				$extra = $rooms['extras'];
				if ( $rooms['id'] != $id_room ) {
					continue;
				}
				if ( count( $rooms['images'] ) > 1 ) {
					$class = 'slider-img';
				} else {
					$class = 'not-slider-img';
				}
				?>
				<div class="item-booking" data-id_room = "<?php echo esc_attr( $rooms['id'] ); ?>">
					<div class="img-content">
						<div class="content-img">
							<div class="<?php echo $class; ?>">
								<?php foreach ( $rooms['images'] as $img ) { ?>
									<div class="cover-img">
										<a href="<?php echo $img['url']; ?>"><img alt="<?php echo $rooms['name']; ?>" src="<?php echo $img['url']; ?>"></a>
									</div>
								<?php } ?>
							</div>
						</div>
						<div class="content-item"  data-id_room = "<?php echo $rooms['id']; ?>">
							<a href="<?php echo home_url( '/' ) . 'rooms-detail/?id_room=' . $rooms['id'] . '&hotel-id=' . $hotel_id; ?>"><h2 class="name"><?php echo $rooms['name']; ?></h2></a>
							<div class="dt-extra">
								<label><?php echo $rooms['size'] . ' m2'; ?></label>|
								<span class="bed"><?php echo $rooms['bed'] ?></span>
							</div>
							<p class="description"><?php echo $rooms['desc']; ?></p>
							<label class="list-amenities">Danh sách tiện ích:</label>
							<div class="feature">
								<?php
								$features = explode( ', ', $rooms['feature'] );
								$t        = 0;
								foreach ( $features as $value ) {
									$values = explode( '/', $value );
									if ( $values[0] ) {
										$t++;
										if ( $t == 6 ) {
											?>
											<div class="tooltip">
												<a href="<?php echo '#' . $rooms['id'] ?>" class="btn-xemthem-ft">+</a>
												<span class="tooltiptext"><?php echo 'Xem tất cả'; ?></span>
											</div>
											<?php
										}
										?>
										<div class="tooltip">
											<img alt="<?php echo $values[0]; ?>"src="<?php echo BM_URL . 'assets/image/icons/' . $values[0] . '.png' ?>">
											<span class="tooltiptext"><?php echo $values[0]; ?></span>
										</div>
										<?php
									}
								}
								?>
							</div>
							<div class="mfp-hide white-popup-block" id="<?= esc_attr( $rooms['id'] )?>">
								<div class="popup_body">
									<div class="popup-close"><?php Mandala_Icons::render( 'close' ) ?></div>
									<div class="popup-title"><h2><?php echo $rooms['name']; ?></h2></div>
									<div class="popup-title-desc">Tất cả tiện ích</div>
									<?php
									foreach ( $features as $value ) {
										$values = explode( '/', $value );
										if ( $values[0] ) {
											if ( $values[0] == 'Phòng' ) {
												$values[0] = 'Tủ quần áo';
											}
											?>
											<div class="tooltip">
												<img alt="<?php echo $values[0]; ?>"src="<?php echo BM_URL . 'assets/image/icons/' . $values[0] . '.png' ?>">
												<span class="title-feature"><?php echo $values[0]; ?></span>
											</div>
											<?php
										}
									}
									?>
								</div>
							</div>
							<div class="price-btn">
								<div class="price">
									<label>Giá chỉ từ</label>
									<span><?php echo number_format( $rooms['price'], 0, ',', '.' ) . ' VND /đêm'; ?></span>
								</div>
								<div class="btn">
									<button class="book_rate">Lựa chọn<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path fill="#fff" d="M169.4 470.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 370.8 224 64c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 306.7L54.6 265.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z"/></svg></button>
								</div>

							</div>
						</div>
					</div>
					<?php
					if ( $id_room == $rooms['id'] ) {
						$style = 'display: block;';

					} else {
						$style = 'display:none;';
					}
					?>
					<div class="content-plan" style="<?php echo $style; ?>">
						<?php
						foreach ( $rooms['plan_ids'] as $key => $plan_id ) {
							$remaining     = $plan_id['rooms_remaining'];
							$occupancy     = $plan_id['max_occupancy'];
							$i             = 0;
							$sum           = 0;
							$price_exchild = [];
							$price_exadult = [];
							$unit_price    = $plan_id['unit_price']['unit_price'] ? $plan_id['unit_price']['unit_price'] : [];
							$s_unit_price  = $plan_id['unit_price']['s_unit_price'] ? $plan_id['unit_price']['s_unit_price'] : [];
							if ( $unit_price ) {
								foreach ( $unit_price as $value ) {
									$i++;
									$sum += $value['rate'];
									for ( $j = 1; $j <= $occupancy['extra_adult']; $j++ ) {
										$exadult = isset( $value['adultRate'][ $j ] ) ? $value['adultRate'][ $j ] : 0;
										if ( isset( $price_exadult[ $j ] ) ) {
											$price_exadult[ $j ] = $price_exadult[ $j ] + $exadult;
										} else {
											$price_exadult[ $j ] = $exadult;

										}
									}
									for ( $j = 1; $j <= $occupancy['extra_child']; $j++ ) {
										$exchild = isset( $value['childRate'][ $j ] ) ? $value['childRate'][ $j ] : 0;

										if ( isset( $price_exchild[ $j ] ) ) {
											$price_exchild[ $j ] = $price_exchild[ $j ] + $exchild;
										} else {
											$price_exchild[ $j ] = $exchild;

										}
									}
								}
								$p_adults = [];
								foreach ( $price_exadult as $p_adult ) {
									$price_adult = $p_adult / $i;
									$p_adults[]  = round( $price_adult );
								}
								$p_childs = [];
								foreach ( $price_exchild as $p_child ) {
									$price_child = $p_child / $i;
									$p_childs[]  = round( $price_child );
								}
								$price = 0;
								if ( $i ) {
									$price = $sum / $i;
								}
								$price     = round( $price );
								$sum_price = [
									'price'       => $price,
									'price_adult' => $p_adults,
									'price_child' => $p_childs,
								];
							}
							$j               = 0;
							$s               = 0;
							$s_price_exchild = [];
							$s_price_exadult = [];
							if ( $s_unit_price ) {
								foreach ( $s_unit_price as $value ) {
									$j++;
									$s += $value['rate'];
									for ( $i = 1; $i <= $occupancy['extra_adult']; $i++ ) {
										$s_exadult = isset( $value['adultRate'][ $i ] ) ? $value['adultRate'][ $i ] : 0;
										if ( isset( $s_price_exadult[ $i ] ) ) {
											$s_price_exadult[ $i ] = $s_price_exadult[ $i ] + $s_exadult;
										} else {
											$s_price_exadult[ $i ] = $s_exadult;

										}
									}
									for ( $i = 1; $i <= $occupancy['extra_child']; $i++ ) {
										$s_exchild = isset( $value['childRate'][ $i ] ) ? $value['childRate'][ $i ] : 0;

										if ( isset( $s_price_exchild[ $i ] ) ) {
											$s_price_exchild[ $i ] = $s_price_exchild[ $i ] + $s_exchild;
										} else {
											$s_price_exchild[ $i ] = $s_exchild;

										}
									}
								}
								$s_price   = 0;
								$sp_adults = [];
								foreach ( $s_price_exadult as $p_adult ) {
									$price_adult = $p_adult / $j;
									$sp_adults[] = round( $price_adult);
								}
								$sp_childs = [];
								foreach ( $s_price_exchild as $p_child ) {
									$price_child = $p_child / $j;
									$sp_childs[] = round( $price_child);
								}
								if ( $j ) {
									$s_price = $s / $j;
								}
								$s_price       = round( $s_price);
								$special_price = [
									'price'       => $s_price,
									'price_adult' => $sp_adults,
									'price_child' => $sp_childs,
								];
							}
							$price_rate = $s_unit_price ? $special_price : $sum_price;
							$meals      = [];
							foreach ( $plan_id['meal_plan'] as $key1 => $meal ) {
								if ( $meal == true && $key1 != 'allInclusive' ) {
									$meals[] = $key1;
								} elseif ( $meal == true && $key1 == 'allInclusive' ) {
									$meals = [ 'ăn sáng', 'ăn trưa', 'ăn tối' ];
								}
							}
							?>
							<div class="rate-plan" data-room = "<?php echo $remaining; ?>" data-index = "<?php echo $key; ?>" data-id_plan = "<?php echo $plan_id['id_plan']; ?>" data-so = "<?php echo esc_attr( $plan_id['so'] ) ?>">
								<div class="text-rate">
									<div class="name-rate"><?php echo $plan_id['name']; ?></div>
									<span class="tooltip">
										<span class="name-so">
											<?php
											echo $plan_id['name_so'];
											if ( $plan_id['desc_so'] ) {
												?>
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path fill="#d2a97d" d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-144c-17.7 0-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32s-14.3 32-32 32z"/></svg>
												<?php
											}
											?>
										</span>
										<?php if ( $plan_id['desc_so'] ) { ?>
										<span class="tooltiptext"><?php echo $plan_id['desc_so']; ?></span>
										<?php } ?>
									</span>
									<span class="tooltip">
										<span class="note-so"><?php echo $plan_id['note_so'] ?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zm32 224c0 17.7-14.3 32-32 32s-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32z"/></svg></span>
										<span class="tooltiptext">
											<?php if ( $plan_id['con_so'] ) { ?>
												<div class="detail-tt">
													<span>Hủy:</span>
													<span><?php echo $plan_id['con_so']; ?></span>
												</div>
											<?php } ?>
											<?php if ( $plan_id['pay_so'] ) { ?>
												<div class="detail-tt">
													<span>Thanh Toán:</span>
													<span><?php echo $plan_id['pay_so']; ?></span>
												</div>
											<?php } ?>
											<?php if ( $time_hotel ) { ?>
												<div class="detail-tt">
													<span>Nhận phòng:</span>
													<span><?php echo $time_hotel['check_in']; ?></span>
												</div>
												<div class="detail-tt">
													<span>Trả phòng:</span>
													<span><?php echo $time_hotel['check_out']; ?></span>
												</div>
											<?php } ?>
											<?php if ( $plan_id['other_so'] ) { ?>
												<div class="detail-tt">
													<span>Chính sách khác:</span>
													<span><?php echo $plan_id['other_so']; ?></span>
												</div>
											<?php } ?>
										</span>
									</span>
									<?php if ( $plan_id['inclusions_name'] ) { ?>
									<span class="tooltip">
										<span class="note-so"><?php echo $plan_id['inclusions_name'] ?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg></span>
											<?php if ( $plan_id['inclusions_desc'] ) { ?>
												<span class="tooltiptext"><?php echo $plan_id['inclusions_desc'] ?></span>
											<?php } ?>
									</span>
									<?php } ?>
									<?php
									if ( $meals ) {
										$meals = implode( ', ', $meals );
										?>
										<div><?php echo 'Bao gồm ' . $meals; ?> </div>
									<?php } ?>
								</div>
								<div class="tooltip tooltip-people">
									<?php if ( $occupancy['extra_adult'] || $occupancy['extra_child'] ) { ?>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M352 128c0 70.7-57.3 128-128 128s-128-57.3-128-128S153.3 0 224 0s128 57.3 128 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312V248H440c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V136c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H552v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>
										<?php
									} else {
										?>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0S96 57.3 96 128s57.3 128 128 128zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>
										<?php
									}
									?>
									<span><?php echo $occupancy['adult'] . ' người lớn' ?></span>
									<?php if ( $occupancy['extra_adult'] || $occupancy['extra_child'] ) { ?>
										<span>Có thể thêm người</span>
									<?php } ?>
									<span class="tooltiptext">Khách 0 - 5 tuổi được ở miễn phí.
										<?php
										if ( $occupancy['adult'] ) {
											echo 'Tối đa ' . $occupancy['adult'] . ' người lớn'; }
										?>
										<?php
										if ( $occupancy['child'] ) {
											echo 'Tối đa ' . $occupancy['child'] . ' trẻ em'; }
										?>
										<?php
										if ( $occupancy['extra_adult'] ) {
											echo 'và ' . $occupancy['extra_adult'] . ' người lớn ở ghép '; }
										?>
										<?php
										if ( $occupancy['extra_child'] ) {
											echo ', ' . $occupancy['extra_child'] . ' trẻ em ở ghép'; }
										?>
										</span>
								</div>
								<div class="price-rate" data-price_rate = "<?php echo esc_attr( json_encode( $price_rate ) ); ?>">
									<?php
									$class = '';
									if ( $unit_price && $s_unit_price ) {
										$class = 'rate-price-spe';
									}
									if ( $unit_price ) {
										?>
										<span class="rate-price <?php echo $class; ?>"><?php echo number_format( $price, 0, ',', '.' ). ' VND /đêm'; ?></span>
										<?php
									}
									if ( $s_unit_price ) {
										if ( ! $unit_price ) {
											$price_s = $plan_id['price_s'] / $day;
											$price_s = round( $price_s );
											?>
											<span class="rate-price rate-price-spe"><?php echo number_format( $price_s, 0, ',', '.' ). ' VND /đêm'; ?></span>
											<?php
										}
										?>
										<span class="rate-price-dis"><?php echo number_format( $s_price, 0, ',', '.' ). ' VND /đêm'; ?></span>
										<?php
									}
									?>
								</div>
								<div class="rooms_remaining">
									<select class="remaining">
										<?php
										for ( $i = 0; $i <= $remaining; $i++ ) {
											?>
											<option value="<?php echo $i; ?>"><?php echo $i . ' phòng'; ?></option>
											<?php
										}
										?>
									</select>
								</div>
								<div class="content-people" data-people = "<?php echo esc_attr( json_encode( $occupancy ) ); ?>">
								</div>
							</div>
							<?php
						}

						?>
					</div>
				</div>
				<?php
			}
			?>


			<?php
			foreach ( $data_rooms as $rooms ) {
				$extra = $rooms['extras'];
				if ( $rooms['id'] == $id_room ) {
					continue;
				}
				if ( count( $rooms['images'] ) > 1 ) {
					$class = 'slider-img';
				} else {
					$class = 'not-slider-img';
				}
				?>
				<div class="item-booking" data-id_room = "<?php echo esc_attr( $rooms['id'] ); ?>">
					<div class="img-content">
						<div class="content-img">
							<div class="<?php echo $class; ?>">
								<?php foreach ( $rooms['images'] as $img ) { ?>
									<div class="cover-img">
										<a href="<?php echo $img['url']; ?>"><img alt="<?php echo $rooms['name']; ?>" src="<?php echo $img['url']; ?>"></a>
									</div>
								<?php } ?>
							</div>
						</div>
						<div class="content-item"  data-id_room = "<?php echo $rooms['id']; ?>">
							<a href="<?php echo home_url( '/' ) . 'rooms-detail/?id_room=' . $rooms['id'] . '&hotel-id=' . $hotel_id; ?>"><h2 class="name"><?php echo $rooms['name']; ?></h2></a>
							<div class="dt-extra">
								<label><?php echo $rooms['size'] . ' m2'; ?></label>|
								<span class="bed"><?php echo $rooms['bed'] ?></span>
							</div>
							<p class="description"><?php echo $rooms['desc']; ?></p>
							<label class="list-amenities">Danh sách tiện ích:</label>
							<div class="feature">
								<?php
								$features = explode( ', ', $rooms['feature'] );
								$t        = 0;
								foreach ( $features as $value ) {
									$values = explode( '/', $value );
									if ( $values[0] ) {
										$t++;
										if ( $t == 6 ) {
											?>
											<div class="tooltip">
												<a href="<?php echo '#' . $rooms['id'] ?>" class="btn-xemthem-ft">+</a>
												<span class="tooltiptext"><?php echo 'Xem tất cả'; ?></span>
											</div>
											<?php
										}
										?>
										<div class="tooltip">
											<img alt="<?php echo $values[0]; ?>"src="<?php echo BM_URL . 'assets/image/icons/' . $values[0] . '.png' ?>">
											<span class="tooltiptext"><?php echo $values[0]; ?></span>
										</div>
										<?php
									}
								}
								?>
							</div>
							<div class="mfp-hide white-popup-block" id="<?= esc_attr( $rooms['id'] )?>">
								<div class="popup_body">
									<div class="popup-close"><?php Mandala_Icons::render( 'close' ) ?></div>
									<div class="popup-title"><h2><?php echo $rooms['name']; ?></h2></div>
									<div class="popup-title-desc">Tất cả tiện ích</div>
									<?php
									foreach ( $features as $value ) {
										$values = explode( '/', $value );
										if ( $values[0] ) {
											if ( $values[0] == 'Phòng' ) {
												$values[0] = 'Tủ quần áo';
											}
											?>
											<div class="tooltip">
												<img alt="<?php echo $values[0]; ?>"src="<?php echo BM_URL . 'assets/image/icons/' . $values[0] . '.png' ?>">
												<span class="title-feature"><?php echo $values[0]; ?></span>
											</div>
											<?php
										}
									}
									?>
								</div>
							</div>
							<div class="price-btn">
								<div class="price">
									<label>Giá chỉ từ</label>
									<span><?php echo number_format( $rooms['price'], 0, ',', '.' ) . ' VND /đêm'; ?></span>
								</div>
								<div class="btn">
									<button class="book_rate">Lựa chọn<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path fill="#fff" d="M169.4 470.6c12.5 12.5 32.8 12.5 45.3 0l160-160c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L224 370.8 224 64c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 306.7L54.6 265.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l160 160z"/></svg></button>
								</div>

							</div>
						</div>
					</div>
					<?php
					if ( $id_room == $rooms['id'] ) {
						$style = 'display: block;';

					} else {
						$style = 'display:none;';
					}
					?>
					<div class="content-plan" style="<?php echo $style; ?>">
						<?php
						foreach ( $rooms['plan_ids'] as $key => $plan_id ) {
							$remaining     = $plan_id['rooms_remaining'];
							$occupancy     = $plan_id['max_occupancy'];
							$i             = 0;
							$sum           = 0;
							$price_exchild = [];
							$price_exadult = [];
							$unit_price    = $plan_id['unit_price']['unit_price'] ? $plan_id['unit_price']['unit_price'] : [];
							$s_unit_price  = $plan_id['unit_price']['s_unit_price'] ? $plan_id['unit_price']['s_unit_price'] : [];
							if ( $unit_price ) {
								foreach ( $unit_price as $value ) {
									$i++;
									$sum += $value['rate'];
									for ( $j = 1; $j <= $occupancy['extra_adult']; $j++ ) {
										$exadult = isset( $value['adultRate'][ $j ] ) ? $value['adultRate'][ $j ] : 0;
										if ( isset( $price_exadult[ $j ] ) ) {
											$price_exadult[ $j ] = $price_exadult[ $j ] + $exadult;
										} else {
											$price_exadult[ $j ] = $exadult;

										}
									}
									for ( $j = 1; $j <= $occupancy['extra_child']; $j++ ) {
										$exchild = isset( $value['childRate'][ $j ] ) ? $value['childRate'][ $j ] : 0;

										if ( isset( $price_exchild[ $j ] ) ) {
											$price_exchild[ $j ] = $price_exchild[ $j ] + $exchild;
										} else {
											$price_exchild[ $j ] = $exchild;

										}
									}
								}
								$p_adults = [];
								foreach ( $price_exadult as $p_adult ) {
									$price_adult = $p_adult / $i;
									$p_adults[]  = round( $price_adult );
								}
								$p_childs = [];
								foreach ( $price_exchild as $p_child ) {
									$price_child = $p_child / $i;
									$p_childs[]  = round( $price_child );
								}
								$price = 0;
								if ( $i ) {
									$price = $sum / $i;
								}
								$price     = round( $price );
								$sum_price = [
									'price'       => $price,
									'price_adult' => $p_adults,
									'price_child' => $p_childs,
								];
							}
							$j               = 0;
							$s               = 0;
							$s_price_exchild = [];
							$s_price_exadult = [];
							if ( $s_unit_price ) {
								foreach ( $s_unit_price as $value ) {
									$j++;
									$s += $value['rate'];
									for ( $i = 1; $i <= $occupancy['extra_adult']; $i++ ) {
										$s_exadult = isset( $value['adultRate'][ $i ] ) ? $value['adultRate'][ $i ] : 0;
										if ( isset( $s_price_exadult[ $i ] ) ) {
											$s_price_exadult[ $i ] = $s_price_exadult[ $i ] + $s_exadult;
										} else {
											$s_price_exadult[ $i ] = $s_exadult;

										}
									}
									for ( $i = 1; $i <= $occupancy['extra_child']; $i++ ) {
										$s_exchild = isset( $value['childRate'][ $i ] ) ? $value['childRate'][ $i ] : 0;

										if ( isset( $s_price_exchild[ $i ] ) ) {
											$s_price_exchild[ $i ] = $s_price_exchild[ $i ] + $s_exchild;
										} else {
											$s_price_exchild[ $i ] = $s_exchild;

										}
									}
								}
								$s_price   = 0;
								$sp_adults = [];
								foreach ( $s_price_exadult as $p_adult ) {
									$price_adult = $p_adult / $j;
									$sp_adults[] = round( $price_adult);
								}
								$sp_childs = [];
								foreach ( $s_price_exchild as $p_child ) {
									$price_child = $p_child / $j;
									$sp_childs[] = round( $price_child);
								}
								if ( $j ) {
									$s_price = $s / $j;
								}
								$s_price       = round( $s_price);
								$special_price = [
									'price'       => $s_price,
									'price_adult' => $sp_adults,
									'price_child' => $sp_childs,
								];
							}
							$price_rate = $s_unit_price ? $special_price : $sum_price;
							$meals      = [];
							foreach ( $plan_id['meal_plan'] as $key1 => $meal ) {
								if ( $meal == true && $key1 != 'allInclusive' ) {
									$meals[] = $key1;
								} elseif ( $meal == true && $key1 == 'allInclusive' ) {
									$meals = [ 'ăn sáng', 'ăn trưa', 'ăn tối' ];
								}
							}
							?>
							<div class="rate-plan" data-room = "<?php echo $remaining; ?>" data-index = "<?php echo $key; ?>" data-id_plan = "<?php echo $plan_id['id_plan']; ?>" data-so = "<?php echo esc_attr( $plan_id['so'] ) ?>">
								<div class="text-rate">
									<div class="name-rate"><?php echo $plan_id['name']; ?></div>
									<span class="tooltip">
										<span class="name-so">
											<?php
											echo $plan_id['name_so'];
											if ( $plan_id['desc_so'] ) {
												?>
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path fill="#d2a97d" d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-144c-17.7 0-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32s-14.3 32-32 32z"/></svg>
												<?php
											}
											?>
										</span>
										<?php if ( $plan_id['desc_so'] ) { ?>
										<span class="tooltiptext"><?php echo $plan_id['desc_so']; ?></span>
										<?php } ?>
									</span>
									<span class="tooltip">
										<span class="note-so"><?php echo $plan_id['note_so'] ?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zm32 224c0 17.7-14.3 32-32 32s-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32z"/></svg></span>
										<span class="tooltiptext">
											<?php if ( $plan_id['con_so'] ) { ?>
												<div class="detail-tt">
													<span>Hủy:</span>
													<span><?php echo $plan_id['con_so']; ?></span>
												</div>
											<?php } ?>
											<?php if ( $plan_id['pay_so'] ) { ?>
												<div class="detail-tt">
													<span>Thanh Toán:</span>
													<span><?php echo $plan_id['pay_so']; ?></span>
												</div>
											<?php } ?>
											<?php if ( $time_hotel ) { ?>
												<div class="detail-tt">
													<span>Nhận phòng:</span>
													<span><?php echo $time_hotel['check_in']; ?></span>
												</div>
												<div class="detail-tt">
													<span>Trả phòng:</span>
													<span><?php echo $time_hotel['check_out']; ?></span>
												</div>
											<?php } ?>
											<?php if ( $plan_id['other_so'] ) { ?>
												<div class="detail-tt">
													<span>Chính sách khác:</span>
													<span><?php echo $plan_id['other_so']; ?></span>
												</div>
											<?php } ?>
										</span>
									</span>
									<?php if ( $plan_id['inclusions_name'] ) { ?>
									<span class="tooltip">
										<span class="note-so"><?php echo $plan_id['inclusions_name'] ?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg></span>
											<?php if ( $plan_id['inclusions_desc'] ) { ?>
												<span class="tooltiptext"><?php echo $plan_id['inclusions_desc'] ?></span>
											<?php } ?>
									</span>
									<?php } ?>
									<?php
									if ( $meals ) {
										$meals = implode( ', ', $meals );
										?>
										<div><?php echo 'Bao gồm ' . $meals; ?> </div>
									<?php } ?>
								</div>
								<div class="tooltip tooltip-people">
									<?php if ( $occupancy['extra_adult'] || $occupancy['extra_child'] ) { ?>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M352 128c0 70.7-57.3 128-128 128s-128-57.3-128-128S153.3 0 224 0s128 57.3 128 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312V248H440c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V136c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H552v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>
										<?php
									} else {
										?>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0S96 57.3 96 128s57.3 128 128 128zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>
										<?php
									}
									?>
									<span><?php echo $occupancy['adult'] . ' người lớn' ?></span>
									<?php if ( $occupancy['extra_adult'] || $occupancy['extra_child'] ) { ?>
										<span>Có thể thêm người</span>
									<?php } ?>
									<span class="tooltiptext">Khách 0 - 5 tuổi được ở miễn phí.
										<?php
										if ( $occupancy['adult'] ) {
											echo 'Tối đa ' . $occupancy['adult'] . ' người lớn'; }
										?>
										<?php
										if ( $occupancy['child'] ) {
											echo 'Tối đa ' . $occupancy['child'] . ' trẻ em'; }
										?>
										<?php
										if ( $occupancy['extra_adult'] ) {
											echo 'và ' . $occupancy['extra_adult'] . ' người lớn ở ghép '; }
										?>
										<?php
										if ( $occupancy['extra_child'] ) {
											echo ', ' . $occupancy['extra_child'] . ' trẻ em ở ghép'; }
										?>
										</span>
								</div>
								<div class="price-rate" data-price_rate = "<?php echo esc_attr( json_encode( $price_rate ) ); ?>">
									<?php
									$class = '';
									if ( $unit_price && $s_unit_price ) {
										$class = 'rate-price-spe';
									}
									if ( $unit_price ) {
										?>
										<span class="rate-price <?php echo $class; ?>"><?php echo number_format( $price, 0, ',', '.' ). ' VND /đêm'; ?></span>
										<?php
									}
									if ( $s_unit_price ) {
										if ( ! $unit_price ) {
											$price_s = $plan_id['price_s'] / $day;
											$price_s = round( $price_s );
											?>
											<span class="rate-price rate-price-spe"><?php echo number_format( $price_s, 0, ',', '.' ). ' VND /đêm'; ?></span>
											<?php
										}
										?>
										<span class="rate-price-dis"><?php echo number_format( $s_price, 0, ',', '.' ). ' VND /đêm'; ?></span>
										<?php
									}
									?>
								</div>
								<div class="rooms_remaining">
									<select class="remaining">
										<?php
										for ( $i = 0; $i <= $remaining; $i++ ) {
											?>
											<option value="<?php echo $i; ?>"><?php echo $i . ' phòng'; ?></option>
											<?php
										}
										?>
									</select>
								</div>
								<div class="content-people" data-people = "<?php echo esc_attr( json_encode( $occupancy ) ); ?>">
								</div>
							</div>
							<?php
						}

						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	<div class="nav-pagination">
		<ul class="pagination"></ul>
	</div>
	</div>
	 <div class="info-booking">
		 <div class="show-hide-info"><button class="show-info">Chi tiết</button><button class="hide-info">Ẩn</button></div>
		<div class="content-info">
			<h3 class="title">Thông tin phòng</h3>
			<div class="info-hotel">
				<h4 class="name" data-id_hotel="<?php echo $hotel_id; ?>"><?php echo $name_hotel; ?></h4>
						<span class="time" data-day="<?php echo $day; ?>" data-day_in="<?php echo $day_in; ?>" data-day_out="<?php echo $day_out; ?>">
					<?php
					echo $check_in . ' - ' . $check_out . ' ( ' . $day . ' đêm )';
					?>
				</span>
			</div>
			<div class="content-book">
			</div>
			<div class="price-button">
			</div>
			<div class="note">( Giá trên chưa bao gồm thuế VAT )</div>
		</div>
	</div>
</div>
</main>
<?php
get_footer();
?>
