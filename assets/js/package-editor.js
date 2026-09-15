/**
 * TravelZ Holidays — package editor.
 *
 * Tab switching, the media picker and the live price preview. No framework:
 * the markup is already on the page, this only wires it up.
 */
( function () {
	'use strict';

	var config = window.tzhEditor || {};
	var STORAGE_KEY = 'tzhEditorTab';

	function field( key ) {
		return document.querySelector( '[name="tzh[' + key + ']"]' );
	}

	function money( amount ) {
		var symbol = config.currency || '৳';

		if ( ! isFinite( amount ) ) {
			return '—';
		}

		// en-IN grouping: 1,00,000 rather than 100,000.
		return symbol + Math.round( amount ).toLocaleString( 'en-IN' );
	}

	/* ---------- Tabs ---------- */

	function initTabs( editor ) {
		var tabs = editor.querySelectorAll( '[data-tzh-tab]' );
		var panels = editor.querySelectorAll( '[data-tzh-panel]' );

		function activate( slug, remember ) {
			var found = false;

			panels.forEach( function ( panel ) {
				var match = panel.getAttribute( 'data-tzh-panel' ) === slug;
				panel.hidden = ! match;
				panel.classList.toggle( 'is-active', match );
				found = found || match;
			} );

			if ( ! found ) {
				return false;
			}

			tabs.forEach( function ( tab ) {
				var match = tab.getAttribute( 'data-tzh-tab' ) === slug;
				tab.classList.toggle( 'is-active', match );
				tab.setAttribute( 'aria-selected', match ? 'true' : 'false' );
			} );

			if ( remember ) {
				try {
					window.localStorage.setItem( STORAGE_KEY, slug );
				} catch ( e ) {
					// Private windows and blocked storage are fine; the tab just
					// resets to the first one next time.
				}
			}

			return true;
		}

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				activate( tab.getAttribute( 'data-tzh-tab' ), true );
			} );

			tab.addEventListener( 'keydown', function ( event ) {
				if ( 'ArrowDown' !== event.key && 'ArrowUp' !== event.key ) {
					return;
				}

				event.preventDefault();

				var list = Array.prototype.slice.call( tabs );
				var index = list.indexOf( tab );
				var next = list[ ( index + ( 'ArrowDown' === event.key ? 1 : list.length - 1 ) ) % list.length ];

				next.focus();
				activate( next.getAttribute( 'data-tzh-tab' ), true );
			} );
		} );

		var stored = null;

		try {
			stored = window.localStorage.getItem( STORAGE_KEY );
		} catch ( e ) {
			stored = null;
		}

		if ( stored ) {
			activate( stored, false );
		}
	}

	/* ---------- Media picker ---------- */

	function initMedia( wrapper ) {
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
					title: config.mediaTitle || 'Choose image',
					button: { text: config.mediaButton || 'Use this image' },
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

					clear.hidden = false;
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

	/* ---------- Price preview ---------- */

	function initPricePreview() {
		var keys = config.priceKeys || {};
		var adultInput = field( keys.adult );
		var rateInput = field( keys.rate );
		var infantInput = field( keys.infant );

		if ( ! adultInput ) {
			return;
		}

		function out( slug ) {
			return document.querySelector( '[data-tzh-preview="' + slug + '"]' );
		}

		function update() {
			var adult = parseFloat( adultInput.value );
			var rate = rateInput ? parseFloat( rateInput.value ) : 70;
			var infant = infantInput ? parseFloat( infantInput.value ) : 0;

			if ( ! isFinite( adult ) ) {
				adult = 0;
			}

			if ( ! isFinite( rate ) ) {
				rate = 0;
			}

			if ( ! isFinite( infant ) ) {
				infant = 0;
			}

			var cells = {
				adult: adult,
				child: Math.round( ( adult * rate ) / 100 ),
				infant: infant
			};

			Object.keys( cells ).forEach( function ( slug ) {
				var cell = out( slug );

				if ( cell ) {
					cell.textContent = money( cells[ slug ] );
				}
			} );
		}

		[ adultInput, rateInput, infantInput ].forEach( function ( input ) {
			if ( input ) {
				input.addEventListener( 'input', update );
			}
		} );

		update();
	}

	function init() {
		var editor = document.querySelector( '[data-tzh-editor]' );

		if ( ! editor ) {
			return;
		}

		initTabs( editor );
		editor.querySelectorAll( '[data-tzh-media]' ).forEach( initMedia );
		initPricePreview();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
