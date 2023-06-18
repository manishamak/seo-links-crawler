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

		var ajaxcall = $.post( slcAdminObj.ajaxurl, data)
			.done( function ( res ) {
				let html;
				if(res.success){
					html = '<ul>';
					var obj = res.data;
					Object.keys(obj).forEach(function(k){
						html += '<li><a href='+obj[k]+'>'+obj[k]+'</a></li>';
					});
					html += '</ul>';
				}else{
					html = res.data;
				}
				$( '.slc-links-wrap' ).html( html );
			// console.log(res);
		} ).fail( function ( xhr ) {
			console.log( xhr.responseText );
		} ).always(function() {
			$btn.removeClass( 'loading disabled' );
			$btn.text( slcAdminObj.resetBtnText );
		  });
    })
})( document, jQuery);