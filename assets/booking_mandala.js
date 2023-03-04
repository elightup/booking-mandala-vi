( function ( $ ) {
	"use strict";
	/* object js */
	var BookingMandala = {
		init: function () {
			this.date_time_picker();
			this.change_datepicker_start();
			this.check_valid();
			this.select_room();
			this.select_people();
			this.ajax_booking();
		},

		/*** Date Time Picker ***/
		date_time_picker: function () {
			if ( $().datepicker ) {
				$( '.datepicker_start, .datepicker_end' ).each( function () {
					$( this ).datepicker( {
						dateFormat: 'dd/mm/yy',
						changeMonth: true,
						changeYear: true,
						minDate: 0,
					} );
				} );
			}
		},

		change_datepicker_start: function () {
			$( document ).on( 'change', '.datepicker_start', function ( e ) {
				e.preventDefault();
				var that = $( this );
				var time_start = that.closest( '.form-booking' ).find( '.datepicker_start' ).val();
				var arr = time_start.split( '/' );
				var date = new Date( arr[ 2 ] + '-' + arr[ 1 ] + '-' + arr[ 0 ] );
				var d = date.getDate();
				var m = date.getMonth();
				var y = date.getFullYear();
				var time_end = new Date( y, m, d + 1 );
				var minDate = time_end.getDate() + '/' + ( time_end.getMonth() + 1 ) + '/' + time_end.getFullYear();
				$( '.datepicker_end' ).datepicker( 'option', 'minDate', minDate );

			} );
		},

		/*check avai */
		check_valid: function () {
			$( document ).on( 'click', '.check-valid', function ( e ) {
				var that = $( this );
				var holtel_id = that.closest( '.form-booking' ).find( '.select-destination' ).val();
				var time_start = that.closest( '.form-booking' ).find( '.datepicker_start' ).val();
				var time_end = that.closest( '.form-booking' ).find( '.datepicker_end' ).val();
				var discount_code = that.closest( '.form-booking' ).find( '.select-discount' ).val();
				var code = that.closest( '.container-booking-mandala' ).data( 'code' );
				var arr_code = code.split( ', ' );
				if ( !holtel_id ) {
					alert( 'Bạn phải chọn điểm đến!' );
					e.preventDefault();
				}
				if ( !time_start ) {
					alert( 'Bạn phải chọn ngày đến!' );
					e.preventDefault();
				}
				if ( !time_end ) {
					alert( 'Bạn phải chọn ngày đi!' );
					e.preventDefault();
				}
				if ( discount_code ) {
					if ( arr_code.includes( discount_code ) ) {
						alert( 'Mã khuyến mại hợp lệ!.' );
					} else {
						alert( 'Mã khuyến mại không hợp lệ. Vui lòng thử lại!.' );
						e.preventDefault();
					}
				}


			} );
		},

		/*select rooms*/
		select_room: function () {
			$( document ).on( 'click', '.rooms_remaining', function ( e ) {
				var that = $( this ).find( '.remaining' );
				var value = that.val();
				var sum = that.closest( '.rate-plan' ).data( 'room' );
				var i = that.closest( '.rate-plan' ).data( 'index' );
				var s = 0;
				that.closest( '.content-plan' ).find( '.rate-plan' ).each( function ( index ) {
					if ( i != index ) {
						var x = $( this ).find( '.remaining' ).val();
						s = s + Number( x );
					}
				} );
				var y = Number( sum ) - Number( s );
				that.closest( '.content-plan' ).find( '.rate-plan' ).each( function ( index ) {
					if ( i == index ) {
						var options = '';
						for ( let i = 0; i <= Number( y ); i++ ) {
							options += '<option value = "' + i + '">' + i + ' Phòng</option>';
						}
						var q = $( this ).find( '.remaining' ).val();
						$( this ).find( '.remaining' ).html( options );
						$( this ).find( `.remaining option[value='${ q }']` ).prop( 'selected', true );
						$( this ).find( '.remaining' ).on( 'change', function ( e ) {
							var k = $( this ).val();
							$( this ).closest( '.rate-plan' ).find( `.remaining option[value='${ k }']` ).prop( 'selected', true );
							var p = $( this ).closest( '.rate-plan' ).find( ".content-people" ).data( 'people' );
							var adult = Number( p.adult ) + Number( p.extra_adult );
							var child = Number( p.child ) + Number( p.extra_child );
							var o_adult = '';
							for ( let i = 1; i <= Number( adult ); i++ ) {
								o_adult += '<option value = "' + i + '">' + i + '</option>';
							}
							var o_child = '';
							for ( let i = 0; i <= Number( child ); i++ ) {
								o_child += '<option value = "' + i + '">' + i + '</option>';
							}
							var people = '';
							for ( let i = 1; i <= Number( k ); i++ ) {
								people += `<div class="adult-child" data-index="${ Number( i - 1 ) }">
									<div class="select-chose">Phòng ${ i }</div>
									<div class="select-adult">
									<label>Người lớn</label>
									<select class="adult">${ o_adult }</select>
									</div>
									<div class="select-child">
									<label>Trẻ em (6-11 years old)</label>
									<select class="child">${ o_child }</select>
									</div></div>`;
							}
							var name_room = $( this ).closest( '.item-booking' ).find( ".name" ).text();
							var name_rate = $( this ).closest( '.rate-plan' ).find( ".name-rate" ).text();
							var rate_id = $( this ).closest( '.rate-plan' ).data( 'id_plan' );
							var so = $( this ).closest( '.rate-plan' ).data( 'so' );
							var room_id = $( this ).closest( '.item-booking' ).data( 'id_room' );
							var price = $( this ).closest( '.rate-plan' ).find( '.price-rate' ).data( 'price_rate' );
							var content = '';
							for ( let i = 1; i <= Number( k ); i++ ) {
								content += `<div class='rate' data-price='${ price.price }' data-price_adult="0" data-price_child="0" data-adult_child='{"o_adult":0,"o_child":0}'>
									<div class="room"><label>Phòng:<span class="sort-rate"></span></label>${ name_room }</div>
									<div class="name_rate">${ name_rate }</div>
									<div class="people">1 người lớn - 0 trẻ em</div>
									<div class="extra_adult"></div>
									<div class="extra_child"></div>
									<div class="price">${ price.price.toLocaleString() } VND /đêm</div></div>`;
							}
							var check_room = $( this ).closest( '.booking-mandala' ).find( `.content-book .room-${ room_id }` ).length;
							if ( !check_room ) {
								$( this ).closest( '.booking-mandala' ).find( '.content-book' ).append( `<div class='room-${ room_id }' data-room_id ="${ room_id }"></div>` );
							}
							var check_rate = $( this ).closest( '.booking-mandala' ).find( `.content-book .room-${ room_id }` ).find( `.rate-${ rate_id }` ).length;
							if ( check_rate ) {
								$( this ).closest( '.booking-mandala' ).find( `.content-book .room-${ room_id }` ).find( `.rate-${ rate_id }` ).html( '' );
							} else {
								$( this ).closest( '.booking-mandala' ).find( `.content-book .room-${ room_id }` ).append( `<div class='rate-${ rate_id }' data-so="${ so }"></div>` );
							}
							$( this ).closest( '.booking-mandala' ).find( `.content-book .room-${ room_id }` ).find( `.rate-${ rate_id }` ).append( content );
							$( this ).closest( '.rate-plan' ).find( '.content-people' ).html( people );

							//add price
							var sum_price = 0;
							$( '.content-book .rate' ).each( function () {
								var p_room = $( this ).data( 'price' );
								var p_eadult = $( this ).data( 'price_adult' );
								var p_echild = $( this ).data( 'price_child' );
								sum_price += Number( p_room ) + Number( p_eadult ) + Number( p_echild );
							} );
							var room = $( '.info-hotel' ).find( '.time' ).data( 'day' );
							var price_checkout = Number( sum_price ) * Number( room );
							if ( Number( price_checkout ) ) {
								$( '.content-info' ).find( '.price-button' ).html( `<div class="total"><label>Tổng cộng</label><span>${ price_checkout.toLocaleString() } VND<span></div><a class="checkout">Đặt Ngay</a>` );
							} else {
								$( '.content-info' ).find( '.price-button' ).html( '' );
							}
							$( '.content-info .rate' ).each( function ( index ) {
								var sort = Number( index ) + 1;
								$( this ).find( '.sort-rate' ).html( sort );
							} );

						} );

					}
				} );

			} );
		},

		/*select people */
		select_people: function () {
			$( document ).on( 'click', '.select-adult', function ( e ) {
				var p = $( this ).closest( ".content-people" ).data( 'people' );
				if ( p.extra_child == p.extra_adult ) {
					var adult_child = Number( p.adult ) + Number( p.child ) + Number( p.extra_adult );
				} else {
					if ( p.extra_adult ) {
						var adult_child = Number( p.adult ) + Number( p.child ) + Number( p.extra_adult );
					} else {
						var adult_child = Number( p.adult ) + Number( p.child ) + Number( p.extra_child );
					}
				}
				var max_adult = Number( p.adult ) + Number( p.extra_adult );
				var x = $( this ).closest( ".adult-child" ).find( '.child' ).val();
				var y = Number( adult_child ) - Number( x );
				if ( Number( y ) > Number( max_adult ) ) {
					y = max_adult;
				}
				var options = '';
				for ( let i = 1; i <= Number( y ); i++ ) {
					options += '<option value = "' + i + '">' + i + '</option>';
				}
				var q = $( this ).find( '.adult' ).val();
				$( this ).find( '.adult' ).html( options );
				$( this ).find( `.adult option[value='${ q }']` ).prop( 'selected', true );
				$( this ).find( '.adult' ).on( 'change', function ( e ) {
					var k = $( this ).val();
					$( this ).closest( '.adult-child' ).find( `.adult option[value='${ k }']` ).prop( 'selected', true );
				} );

			} );

			$( document ).on( 'click', '.select-child', function ( e ) {
				var p = $( this ).closest( ".content-people" ).data( 'people' );
				if ( p.extra_child == p.extra_adult ) {
					var adult_child = Number( p.adult ) + Number( p.child ) + Number( p.extra_adult );
				} else {
					if ( p.extra_adult ) {
						var adult_child = Number( p.adult ) + Number( p.child ) + Number( p.extra_adult );
					} else {
						var adult_child = Number( p.adult ) + Number( p.child ) + Number( p.extra_child );
					}
				}
				var max_child = Number( p.child ) + Number( p.extra_child ) + Number( p.adult ) - 1;
				var x = $( this ).closest( ".adult-child" ).find( '.adult' ).val();
				var y = Number( adult_child ) - Number( x );
				if ( Number( y ) > Number( max_child ) ) {
					y = max_child;
				}
				var options = '';
				for ( let i = 0; i <= Number( y ); i++ ) {
					options += '<option value = "' + i + '">' + i + '</option>';
				}
				var q = $( this ).find( '.child' ).val();
				$( this ).find( '.child' ).html( options );
				$( this ).find( `.child option[value='${ q }']` ).prop( 'selected', true );
				$( this ).find( '.child' ).on( 'change', function ( e ) {
					var k = $( this ).val();
					$( this ).closest( '.adult-child' ).find( `.child option[value='${ k }']` ).prop( 'selected', true );

				} );

			} );

			$( document ).on( 'change', '.select-adult .adult', function ( e ) {
				var that = $( this );
				var adult = that.val();
				var child = that.closest( '.adult-child' ).find( '.child' ).val();
				var p = $( this ).closest( '.rate-plan' ).find( ".content-people" ).data( 'people' );
				var sort = that.closest( '.adult-child' ).data( 'index' );
				var rate_id = that.closest( '.rate-plan' ).data( 'id_plan' );
				var room_id = that.closest( '.item-booking' ).data( 'id_room' );
				that.closest( '.booking-mandala' ).find( `.content-book .room-${ room_id }` ).find( `.rate-${ rate_id } .rate` ).each( function ( index ) {
					if ( sort == index ) {
						if ( p.extra_adult == p.extra_child ) {
							if ( Number( adult ) <= Number( p.adult ) && Number( child ) > Number( p.child ) ) {
								var free_adult = Number( p.adult ) - Number( adult );
								var ex_child = Number( child ) - Number( p.child ) - Number( free_adult );
								var ex_adult = 0;
							} else {
								var ex_adult = Number( adult ) - Number( p.adult );
								var ex_child = Number( child ) - Number( p.child );
							}
						} else {
							if ( p.extra_adult ) {

								if ( Number( adult ) <= Number( p.adult ) && Number( child ) > Number( p.child ) ) {
									var free_adult = Number( p.adult ) - Number( adult );
									var ex_adult = Number( child ) - Number( p.child ) - Number( free_adult );
									var ex_child = 0;
								} else if ( Number( adult ) > Number( p.adult ) && Number( child ) > Number( p.child ) ) {
									var ex_adult = Number( adult ) - Number( p.adult ) + Number( child ) - Number( p.child );
									var ex_child = 0;
								} else {
									var ex_adult = Number( adult ) - Number( p.adult );
									var ex_child = Number( child ) - Number( p.child );
								}

							} else {

								if ( Number( adult ) <= Number( p.adult ) && Number( child ) > Number( p.child ) ) {
									var free_adult = Number( p.adult ) - Number( adult );
									var ex_child = Number( child ) - Number( p.child ) - Number( free_adult );
									var ex_adult = 0;
								} else {
									var ex_adult = Number( adult ) - Number( p.adult );
									var ex_child = Number( child ) - Number( p.child );
								}
							}
						}
						var extra_adult = ( ex_adult > 0 ) ? ex_adult : 0;
						var extra_child = ( ex_child > 0 ) ? ex_child : 0;

						var price = that.closest( '.rate-plan' ).find( '.price-rate' ).data( 'price_rate' );
						var rate_adult = price.price_adult ? price.price_adult : 0;
						var price_exadult = 0;
						if ( extra_adult ) {
							for ( let $i = 0; $i < Number( extra_adult ); $i++ ) {
								price_exadult += Number( rate_adult[ $i ] );
							}
						}
						var rate_child = price.price_child ? price.price_child : 0;
						var price_exchild = 0;
						if ( extra_child ) {
							for ( let $i = 0; $i < Number( extra_child ); $i++ ) {
								price_exchild += Number( rate_child[ $i ] );
							}
						}
						var sum_price_night = Number( price.price ) + Number( price_exchild ) + Number( price_exadult );
						$( this ).find( '.price' ).text( `${ sum_price_night.toLocaleString() } VND /đêm` );
						$( this ).find( '.people' ).text( `${ adult } người lớn - ${ child } trẻ em ` );
						var o_adult_child = {
							'o_adult': extra_adult,
							'o_child': extra_child,
						};
						$( this ).data( 'adult_child', o_adult_child );
						if ( price_exadult ) {
							$( this ).find( '.extra_adult' ).text( `Phụ thu người lớn: ${ price_exadult.toLocaleString() } VND /đêm` );
							$( this ).data( 'price_adult', price_exadult );
						} else {
							$( this ).find( '.extra_adult' ).text( '' );
							$( this ).data( 'price_adult', 0 );
						}
						if ( price_exchild ) {
							$( this ).find( '.extra_child' ).text( `Phụ thu trẻ em ${ price_exchild.toLocaleString() } VND /đêm ` );
							$( this ).data( 'price_child', price_exchild );
						} else {
							$( this ).find( '.extra_child' ).text( '' );
							$( this ).data( 'price_child', 0 );
						}
					}
				} );
				//add price
				var sum_price = 0;
				$( '.content-book .rate' ).each( function () {
					var p_room = $( this ).data( 'price' );
					var p_eadult = $( this ).data( 'price_adult' );
					var p_echild = $( this ).data( 'price_child' );
					sum_price += Number( p_room ) + Number( p_eadult ) + Number( p_echild );
				} );
				var room = $( '.info-hotel' ).find( '.time' ).data( 'day' );
				var price_checkout = Number( sum_price ) * Number( room );
				if ( Number( price_checkout ) ) {
					$( '.content-info' ).find( '.price-button' ).html( `<div class="total"><label>Tổng cộng</label><span>${ price_checkout.toLocaleString() } VND<span></div><a class="checkout">Đặt Ngay</a>` );
				} else {
					$( '.content-info' ).find( '.price-button' ).html( '' );
				}
			} );
			$( document ).on( 'change', '.select-child .child', function ( e ) {
				var that = $( this );
				var child = that.val();
				var adult = that.closest( '.adult-child' ).find( '.adult' ).val();
				var p = $( this ).closest( '.rate-plan' ).find( ".content-people" ).data( 'people' );
				var sort = that.closest( '.adult-child' ).data( 'index' );
				var rate_id = that.closest( '.rate-plan' ).data( 'id_plan' );
				var room_id = that.closest( '.item-booking' ).data( 'id_room' );
				$( this ).closest( '.booking-mandala' ).find( `.content-book .room-${ room_id }` ).find( `.rate-${ rate_id } .rate` ).each( function ( index ) {
					if ( sort == index ) {

						if ( p.extra_adult == p.extra_child ) {
							if ( Number( adult ) <= Number( p.adult ) && Number( child ) > Number( p.child ) ) {
								var free_adult = Number( p.adult ) - Number( adult );
								var ex_child = Number( child ) - Number( p.child ) - Number( free_adult );
								var ex_adult = 0;
							} else {
								var ex_adult = Number( adult ) - Number( p.adult );
								var ex_child = Number( child ) - Number( p.child );
							}
						} else {
							if ( p.extra_adult ) {

								if ( Number( adult ) <= Number( p.adult ) && Number( child ) > Number( p.child ) ) {
									var free_adult = Number( p.adult ) - Number( adult );
									var ex_adult = Number( child ) - Number( p.child ) - Number( free_adult );
									var ex_child = 0;
								} else if ( Number( adult ) > Number( p.adult ) && Number( child ) > Number( p.child ) ) {
									var ex_adult = Number( adult ) - Number( p.adult ) + Number( child ) - Number( p.child );
									var ex_child = 0;
								} else {
									var ex_adult = Number( adult ) - Number( p.adult );
									var ex_child = Number( child ) - Number( p.child );
								}

							} else {

								if ( Number( adult ) <= Number( p.adult ) && Number( child ) > Number( p.child ) ) {
									var free_adult = Number( p.adult ) - Number( adult );
									var ex_child = Number( child ) - Number( p.child ) - Number( free_adult );
									var ex_adult = 0;
								} else {
									var ex_adult = Number( adult ) - Number( p.adult );
									var ex_child = Number( child ) - Number( p.child );
								}
							}
						}
						var extra_adult = ( ex_adult > 0 ) ? ex_adult : 0;
						var extra_child = ( ex_child > 0 ) ? ex_child : 0;
						var price = that.closest( '.rate-plan' ).find( '.price-rate' ).data( 'price_rate' );
						var rate_adult = price.price_adult ? price.price_adult : 0;
						var price_exadult = 0;
						if ( extra_adult ) {
							for ( let $i = 0; $i < Number( extra_adult ); $i++ ) {
								price_exadult += Number( rate_adult[ $i ] );
							}
						}
						var rate_child = price.price_child ? price.price_child : 0;
						var price_exchild = 0;
						if ( extra_child ) {
							for ( let $i = 0; $i < Number( extra_child ); $i++ ) {
								price_exchild += Number( rate_child[ $i ] );
							}
						}
						var sum_price_night = Number( price.price ) + Number( price_exchild ) + Number( price_exadult );
						$( this ).find( '.price' ).text( `${ sum_price_night.toLocaleString() } VND /đêm` );
						$( this ).find( '.people' ).text( `${ adult } người lớn - ${ child } trẻ em ` );
						var o_adult_child = {
							'o_adult': extra_adult,
							'o_child': extra_child,
						};
						$( this ).data( 'adult_child', o_adult_child );
						if ( price_exadult ) {
							$( this ).find( '.extra_adult' ).text( `Phụ thu người lớn: ${ price_exadult.toLocaleString() } VND /đêm` );
							$( this ).data( 'price_adult', price_exadult );
						} else {
							$( this ).find( '.extra_adult' ).text( '' );
							$( this ).data( 'price_adult', 0 );
						}
						if ( price_exchild ) {
							$( this ).find( '.extra_child' ).text( `Phụ thu trẻ em: ${ price_exchild.toLocaleString() } VND /đêm ` );
							$( this ).data( 'price_child', price_exchild );
						} else {
							$( this ).find( '.extra_child' ).text( '' );
							$( this ).data( 'price_child', 0 );
						}
					}
				} );
				//add price
				var sum_price = 0;
				$( '.content-book .rate' ).each( function () {
					var p_room = $( this ).data( 'price' );
					var p_eadult = $( this ).data( 'price_adult' );
					var p_echild = $( this ).data( 'price_child' );
					sum_price += Number( p_room ) + Number( p_eadult ) + Number( p_echild );
				} );
				var room = $( '.info-hotel' ).find( '.time' ).data( 'day' );
				var price_checkout = Number( sum_price ) * Number( room );
				if ( Number( price_checkout ) ) {
					$( '.content-info' ).find( '.price-button' ).html( `<div class="total"><label>Tổng cộng</label><span>${ price_checkout.toLocaleString() } VND<span></div><a class="checkout">Đặt Ngay</a>` );
				} else {
					$( '.content-info' ).find( '.price-button' ).html( '' );
				}
			} );
		},

		check_out: function () {
			var id_hotel = $( '.info-hotel' ).find( '.name' ).data( 'id_hotel' );
			var check_in = $( '.info-hotel' ).find( '.time' ).data( 'day_in' );
			var check_out = $( '.info-hotel' ).find( '.time' ).data( 'day_out' );
			var link = `https://book.securebookings.net/checkout?id=${ id_hotel }&currency=USD&check_in=${ check_in }&check_out=${ check_out }&lang=en&promoCode=&widget_type=&exSource=&affId=&exit_discount=0&no_deposit=`;
			var check_out = [];
			$( '.content-book' ).find( '>div' ).each( function () {
				var room_id = $( this ).data( 'room_id' );
				var room = [];
				var s_room = 0;
				var s_adult = 0;
				var s_child = 0;
				$( this ).find( '>div' ).each( function () {
					var plan_id = $( this ).data( 'so' );
					var rate = [];
					var i = 0;
					var rate_adult = 0;
					var rate_child = 0;
					$( this ).find( '.rate' ).each( function () {
						i++;
						var adult_child = $( this ).data( 'adult_child' );
						rate_adult += Number( adult_child.o_adult );
						rate_child += Number( adult_child.o_child );
						rate.push( adult_child );
					} );
					s_room += i;
					s_adult += rate_adult;
					s_child += rate_child;
					var co_rate = [ plan_id, rate ];
					room.push( co_rate );
				} );
				var co_room = [ room_id, s_room, s_adult, s_child, room ];
				check_out.push( co_room );
			} );
			for ( let i = 0; i < check_out.length; i++ ) {
				link += `&list_booked[${ check_out[ i ][ 0 ] }]=${ check_out[ i ][ 1 ] },${ check_out[ i ][ 2 ] },${ check_out[ i ][ 3 ] }`;
				var link_rate = '';
				for ( let j = 0; j < check_out[ i ][ 4 ].length; j++ ) {
					for ( let n = 0; n < check_out[ i ][ 4 ][ j ][ 1 ].length; n++ ) {
						var e_adult = check_out[ i ][ 4 ][ j ][ 1 ][ n ].o_adult;
						var e_child = check_out[ i ][ 4 ][ j ][ 1 ][ n ].o_child;
						link_rate += `&list_booked_details[${ check_out[ i ][ 4 ][ j ][ 0 ] }][]=1,${ e_adult },${ e_child }`;
					}
				}
				link += link_rate;
			}
			link += '&url_back=https://book.securebookings.net/roomrate?id=8a1a268a-cd8a-4525-975b-fb020652553e';
			window.location.href = link;
		},

		ajax_booking: function () {
			function send_ajax(that) {
				var holtel_id = that.closest( '.form-booking' ).find( '.select-destination' ).val();
				var date_start = that.closest( '.form-booking' ).find( '.datepicker_start' ).val();
				var date_end = that.closest( '.form-booking' ).find( '.datepicker_end' ).val();
				var discount = that.closest( '.form-booking' ).find( '.select-discount' ).data( 'discount' );
				$.ajax( {
					type: "POST",
					url: ajax_object.ajax_url,
					dataType: 'json',
					data: {
						action: "discount",
						hotel_id: holtel_id,
						date_start: date_start,
						date_end: date_end,
						discount: discount,
					},
					success: function ( response ) {
						$( '.select-discount' ).html( response.data );
					}
				} );
			}
			$( document ).ready( function () {
				$( document ).on( 'change', '.select-destination, .datepicker_start, .datepicker_end ', function ( e ) {
					e.preventDefault();
					var that = $(this)
					send_ajax(that);
				} );
			} );
		}

	};

	/* ready */
	$( document ).ready( function () {
		BookingMandala.init();
		$( document ).on( 'click', '.checkout', function ( e ) {
			e.preventDefault();
			BookingMandala.check_out();

		} );
		if ( $( '#text-search' ).hasClass( 'text-search' ) ) {
			$( '.booking-mandala .info-booking' ).css( 'margin-top', '150px' );
		} else {
			$( '.booking-mandala .info-booking' ).css( 'margin-top', '100px' );
		}
		$( '.slider-img' ).slick( {
			slidesToShow: 1,
			centerPadding: '0px',
			centerMode: true,
			dots: false,
			arrows: true,
			autoplay: true,
			rows: 0,
			autoplaySpeed: 4000,
			responsive: [
				{
					breakpoint: 991,
					settings: {
						centerMode: false,
						centerPadding: '30px',
						slidesToShow: 1
					}
				},
				{
					breakpoint: 600,
					settings: {
						centerMode: false,
						centerPadding: '30px',
						slidesToShow: 1
					}
				}
			]
		} );
		if ( $( '#primary' ).hasClass( 'booking' ) || $( '#primary' ).hasClass( 'rooms-page' ) ) {
			$( '.slider-img' ).slickLightbox( {
				slick: {
					itemSelector: 'a',
					navigateByKeyboard: true,
					dots: true,
					infinite: true,
					centerMode: true,
					slidesToShow: 1,
					slidesToScroll: 1,
					mobileFirst: true
				}
			} );
		}
		$( '.container-booking-mandala select' ).select2();
		$( '.select-discount' ).select2( {
			tags: true,
			placeholder: 'Mã khuyến mại',
			allowClear: true
		} );
		var monkeyList = new List( 'list-booking', {
			valueNames: [ 'name' ],
			page: 10,
			pagination: true,

		} );
		$( '.nav-pagination' ).append( '<div class="btn-next">Next →</div>' );
		$( '.nav-pagination' ).prepend( '<div class="btn-prev">← Previous</div>' );
		$( '.nav-pagination .btn-next' ).on( 'click', function () {
			$( '.pagination .active' ).next().trigger( 'click' );
		} );

		$( '.nav-pagination .btn-prev' ).on( 'click', function () {
			$( '.pagination .active' ).prev().trigger( 'click' );

		} );

		$( document ).on( 'click', '.book_rate', function ( e ) {

			$( this ).closest( '.item-booking' ).find( '.content-plan' ).toggle( 'slow' );
		} );



	} );

} )( jQuery );