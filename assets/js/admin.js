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

	/**
	 * Update the status bar text and CSS modifier.
	 *
	 * @param {string} text    Status message to display.
	 * @param {string} variant One of 'running', 'success', 'error', 'none'.
	 */
	function updateStatusBar( text, variant ) {
		const bar = document.querySelector( '.slc-status-bar' );
		if ( ! bar ) {
			return;
		}
		const span = document.createElement( 'span' );
		span.className = 'slc-status slc-status--' + variant;
		span.textContent = text;
		bar.innerHTML = '';
		bar.appendChild( span );
	}

	function handleCrawlClick( e ) {
		e.preventDefault();

		const btn = e.currentTarget;
		const resultWrap = document.querySelector( '.slc-links-wrap' );

		if ( btn.classList.contains( 'disabled' ) ) {
			return;
		}

		if ( slcAdminObj.isLocked ) {
			showNotice( slcAdminObj.lockedMsg, 'warning' );
			return;
		}

		resultWrap.innerHTML = '';
		btn.classList.add( 'loading', 'disabled' );
		btn.innerHTML = '<span class="slc-spinner"></span>' + escapeHtml( slcAdminObj.loading );
		updateStatusBar( slcAdminObj.loading + '…', 'running' );

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

					var meta = res.data.crawl_meta;
					if ( meta ) {
						var count = meta.link_count || 0;
						updateStatusBar( count + ' links found just now.', 'success' );
					}
					slcAdminObj.isLocked = false;
				} else {
					var p = document.createElement( 'p' );
					p.className = 'slc-error-message';
					p.textContent = res.data;
					resultWrap.appendChild( p );
					updateStatusBar( res.data, 'error' );
					slcAdminObj.isLocked = false;
				}
			} )
			.catch( function( error ) {
				showNotice( error.message || 'An unexpected error occurred.', 'error' );
				slcAdminObj.isLocked = false;
			} )
			.finally( function() {
				btn.classList.remove( 'loading', 'disabled' );
				btn.textContent = slcAdminObj.resetBtnText;
			} );
	}

	function handleClearClick() {
		const btn        = document.querySelector( '.slc-button-clear' );
		const resultWrap = document.querySelector( '.slc-links-wrap' );

		if ( btn.disabled ) {
			return;
		}

		btn.disabled    = true;
		btn.textContent = slcAdminObj.clearing;

		const formData = new FormData();
		formData.append( 'action', 'slc_clear_cache' );
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
					resultWrap.innerHTML = '';
					showNotice( res.data.message, 'success' );
					updateStatusBar( res.data.message, 'none' );
				} else {
					showNotice( res.data || 'Failed to clear cache.', 'error' );
				}
			} )
			.catch( function( error ) {
				showNotice( error.message || 'An unexpected error occurred.', 'error' );
			} )
			.finally( function() {
				btn.disabled    = false;
				btn.textContent = slcAdminObj.clearBtnText;
			} );
	}

	document.addEventListener( 'DOMContentLoaded', function() {
		const crawlBtn = document.querySelector( '.slc-button-action' );
		if ( crawlBtn ) {
			if ( slcAdminObj.isLocked ) {
				crawlBtn.classList.add( 'disabled' );
			}
			crawlBtn.addEventListener( 'click', handleCrawlClick );
		}

		const clearBtn = document.querySelector( '.slc-button-clear' );
		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', handleClearClick );
		}
	} );
} )( document );
