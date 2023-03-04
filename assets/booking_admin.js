( function ( $ ) {
	"use strict";
	/* object js */
	var BookingAdmin = {
		init: function () {
			this.delete_cake_admin();
		},

		delete_cake_admin: function() {
			$( document ).on( 'click', '#delete_cache', function ( e ) {
				e.preventDefault();
				$.ajax( {
					type: "POST",
					url: ajax_object.ajax_url,
					data: {
						action: "delete_cake_admin",
					},
					success: function ( response ) {
						alert( 'Đã xóa cake thành công' );
					}
				} );
			} );

		},

	};

	/* ready */
	$( document ).ready( function () {
		BookingAdmin.init();
	} );

} )( jQuery );