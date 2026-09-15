/**
 * TravelZ Holidays — setup guide.
 *
 * One job: copy a shortcode to the clipboard and say so.
 */
( function () {
	'use strict';

	var words = window.tzhSetup || {};

	function flash( button ) {
		var original = button.getAttribute( 'data-tzh-label' ) || button.textContent;

		button.setAttribute( 'data-tzh-label', original );
		button.textContent = words.copied || 'Copied';
		button.classList.add( 'is-copied' );

		window.setTimeout( function () {
			button.textContent = original;
			button.classList.remove( 'is-copied' );
		}, 1600 );
	}

	function copy( text, button ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).then(
				function () {
					flash( button );
				},
				function () {
					// Permission refused, or the window lost focus mid-click.
					legacy( text, button );
				}
			);

			return;
		}

		legacy( text, button );
	}

	/** Plain http, which a local install usually is, has no clipboard API. */
	function legacy( text, button ) {
		var box = document.createElement( 'textarea' );

		box.value = text;
		box.setAttribute( 'readonly', 'readonly' );
		box.style.position = 'fixed';
		box.style.opacity = '0';

		document.body.appendChild( box );
		box.select();

		try {
			document.execCommand( 'copy' );
			flash( button );
		} catch ( error ) {
			// Nothing to do: the code is on screen and can be selected by hand.
		}

		box.remove();
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-tzh-copy-text]' );

		if ( ! button ) {
			return;
		}

		event.preventDefault();
		copy( button.getAttribute( 'data-tzh-copy-text' ), button );
	} );
}() );
