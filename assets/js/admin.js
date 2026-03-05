/* global slcAdminObj */
( function( document ) {
	'use strict';

	/**
	 * Safely escapes a string for use in HTML text content.
	 *
	 * @param {string} str Raw string.
	 * @return {string} Escaped string.
	 */
	function escapeHtml( str ) {
		const div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( str ) );
		return div.innerHTML;
	}

	/**
	 * Validates that a URL string uses http(s) protocol.
	 *
	 * @param {string} url URL to validate.
	 * @return {boolean} True if URL is safe.
	 */
	function isSafeUrl( url ) {
		try {
			const parsed = new URL( url );
			return [ 'http:', 'https:' ].includes( parsed.protocol );
		} catch ( e ) {
			return false;
		}
	}

	function showNotice( message, type ) {
		const existing = document.querySelector( '.slc-admin-notice' );
		if ( existing ) {
			existing.remove();
		}

		const notice = document.createElement( 'div' );
		notice.className = 'notice notice-' + type + ' slc-admin-notice is-dismissible';
		const p = document.createElement( 'p' );
		p.textContent = message;
		notice.appendChild( p );

		const container = document.getElementById( 'wpbody-content' );
		if ( container ) {
			container.prepend( notice );
		}
	}

	function renderLinks( links ) {
		const fragment = document.createDocumentFragment();
		const ul = document.createElement( 'ul' );

		Object.keys( links ).forEach( function( key ) {
			const link = links[ key ];
			const li = document.createElement( 'li' );
			const anchor = document.createElement( 'a' );

			if ( isSafeUrl( link ) ) {
				anchor.href = link;
			} else {
				anchor.href = '#';
			}

			anchor.className = 'slc-link';
			anchor.textContent = link;
			anchor.setAttribute( 'target', '_blank' );
			anchor.setAttribute( 'rel', 'noopener noreferrer' );

			li.appendChild( anchor );
			ul.appendChild( li );
		} );

		fragment.appendChild( ul );
		return fragment;
	}

	function handleCrawlClick( e ) {
		e.preventDefault();

		const btn = e.currentTarget;
		const resultWrap = document.querySelector( '.slc-links-wrap' );

		if ( btn.classList.contains( 'disabled' ) ) {
			return;
		}

		resultWrap.innerHTML = '';
		btn.classList.add( 'loading', 'disabled' );
		btn.innerHTML = '<span class="slc-spinner"></span>' + escapeHtml( slcAdminObj.loading );

		const formData = new FormData();
		formData.append( 'action', 'slc_admin_display_links' );
		formData.append( 'nonce', slcAdminObj.nonce );

		fetch( slcAdminObj.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( res ) {
				if ( res.success ) {
					if ( res.data.file_error ) {
						showNotice( res.data.file_error, 'error' );
					}
					resultWrap.appendChild( renderLinks( res.data.result ) );
				} else {
					const p = document.createElement( 'p' );
					p.className = 'slc-error-message';
					p.textContent = res.data;
					resultWrap.appendChild( p );
				}
			} )
			.catch( function( error ) {
				showNotice( error.message || 'An unexpected error occurred.', 'error' );
			} )
			.finally( function() {
				btn.classList.remove( 'loading', 'disabled' );
				btn.textContent = slcAdminObj.resetBtnText;
			} );
	}

	document.addEventListener( 'DOMContentLoaded', function() {
		const crawlBtn = document.querySelector( '.slc-button-action' );
		if ( crawlBtn ) {
			crawlBtn.addEventListener( 'click', handleCrawlClick );
		}
	} );
} )( document );
