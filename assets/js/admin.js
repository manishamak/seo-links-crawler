/* global slcAdminObj, jQuery */
( function( document, $ ) {
	/*
	 *  Display Internal links via Ajax call.
	 */
	$( document ).on( 'click', '.slc-button-action', function ( e ) {
		e.preventDefault();

		const $btn = $( this );
		const $resultantDiv = $( '.slc-links-wrap' );

		if ( $btn.hasClass( 'disabled' ) || $btn.hasClass( 'loading' ) ) {
			return false;
		}
		$resultantDiv.empty();
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
					if (res.data.file_error) {
						jQuery('#wpbody-content').prepend('<div class="error"><p>'+ res.data.file_error +'</p></div>');
					}
					html = '<ul>';
					var obj = res.data.result;
					Object.keys(obj).forEach(function(k) {
						let link = obj[k];
						html += '<li><a href="' + link + '" class="slc-link">' + link + '</a></li>';
					});
					html += '</ul>';
				}else{
					html = '<p>' + res.data + '</p>';
				}
				$resultantDiv.html( html );
		} ).fail( function ( xhr ) {
			console.log( xhr.responseText );
		} ).always(function() {
			$btn.removeClass( 'loading disabled' );
			$btn.text( slcAdminObj.resetBtnText );
		  });
    })
})( document, jQuery);