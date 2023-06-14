/* global slcAdminObj, jQuery */
( function( document, $ ) {
	/*
	 *  Display Internal links via Ajax call.
	 */
	$( document ).on( 'click', '.slc-button', function ( e ) {
		e.preventDefault();

		const $btn = $( this );

		if ( $btn.hasClass( 'disabled' ) || $btn.hasClass( 'loading' ) ) {
			return false;
		}

		$btn.addClass( 'loading disabled' );
		$btn.text( slcAdminObj.loading );

		// Setup ajax POST data.
		const data = {
			action: 'slc_admin_display_links',
			nonce: slcAdminObj.nonce,
		};

		$.post( slcAdminObj.ajaxurl, data, function ( res ) {
			$( '.slc-links-wrap' ).html( res );
		} ).fail( function ( xhr ) {
			console.log( xhr.responseText );
		} );
    })
})( document, jQuery);