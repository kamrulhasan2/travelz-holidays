/**
 * TravelZ Holidays — media picker.
 *
 * Wires every [data-tzh-media] block on the page to the WordPress media
 * library. Shared by the package editor and the destination screens, and safe
 * to load on either alone.
 */
( function () {
	'use strict';

	var strings = window.tzhMedia || {};

	function init( wrapper ) {
		if ( wrapper.dataset.tzhMediaReady ) {
			return;
		}

		wrapper.dataset.tzhMediaReady = '1';

		var input = wrapper.querySelector( '[data-tzh-media-input]' );
		var preview = wrapper.querySelector( '[data-tzh-media-preview]' );
		var pick = wrapper.querySelector( '[data-tzh-media-pick]' );
		var clear = wrapper.querySelector( '[data-tzh-media-clear]' );
		var frame = null;

		if ( ! input || ! pick || ! window.wp || ! window.wp.media ) {
			return;
		}

		pick.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = window.wp.media( {
					title: strings.title || 'Choose image',
					button: { text: strings.button || 'Use this image' },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					var url = attachment.sizes && attachment.sizes.medium
						? attachment.sizes.medium.url
						: attachment.url;

					input.value = attachment.id;
					preview.innerHTML = '';

					var img = document.createElement( 'img' );
					img.src = url;
					img.alt = '';
					preview.appendChild( img );

					if ( clear ) {
						clear.hidden = false;
					}
				} );
			}

			frame.open();
		} );

		if ( clear ) {
			clear.addEventListener( 'click', function () {
				input.value = '0';
				preview.innerHTML = '';
				clear.hidden = true;
			} );
		}
	}

	function scan() {
		document.querySelectorAll( '[data-tzh-media]' ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', scan );
	} else {
		scan();
	}

	// The Add New Destination form is rebuilt by WordPress after each save.
	document.addEventListener( 'ajaxComplete', scan );
	window.tzhScanMedia = scan;
}() );
