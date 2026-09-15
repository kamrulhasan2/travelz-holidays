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


	/* ---------- Repeaters ---------- */

	/**
	 * Rewrite every name attribute under a repeater so row indexes stay
	 * contiguous after an add, remove or move — at any nesting depth.
	 */
	function reindex( repeater, base ) {
		var rowsBox = repeater.querySelector( ':scope > [data-tzh-rows]' );

		if ( ! rowsBox ) {
			return;
		}

		var label = repeater.getAttribute( 'data-tzh-label' ) || '';
		var rows = rowsBox.querySelectorAll( ':scope > [data-tzh-row]' );

		rows.forEach( function ( row, index ) {
			var rowBase = base + '[' + index + ']';

			row.querySelectorAll( '[data-tzh-leaf]' ).forEach( function ( input ) {
				if ( input.closest( '[data-tzh-repeater]' ) !== repeater ) {
					return;
				}

				input.name = rowBase + '[' + input.getAttribute( 'data-tzh-leaf' ) + ']';
			} );

			row.querySelectorAll( '[data-tzh-repeater]' ).forEach( function ( nested ) {
				if ( nested.parentElement.closest( '[data-tzh-repeater]' ) !== repeater ) {
					return;
				}

				reindex( nested, rowBase + '[' + nested.getAttribute( 'data-tzh-key' ) + ']' );
			} );

			var num = row.querySelector( ':scope > .tzh-rep__head [data-tzh-num]' );

			if ( num ) {
				num.textContent = label + ' ' + ( index + 1 );
			}

			updateSummary( row );
		} );

		rowsBox.classList.toggle( 'is-empty', 0 === rows.length );
	}

	function updateSummary( row ) {
		var target = row.querySelector( ':scope > .tzh-rep__head [data-tzh-summary]' );

		if ( ! target ) {
			return;
		}

		var source = row.querySelector( '[data-tzh-summary-source]' );
		var owner = source ? source.closest( '[data-tzh-row]' ) : null;

		target.textContent = ( source && owner === row ) ? source.value : '';
	}

	function rootOf( element ) {
		var repeater = element.closest( '[data-tzh-repeater]' );

		while ( repeater && ! repeater.hasAttribute( 'data-tzh-base' ) ) {
			repeater = repeater.parentElement
				? repeater.parentElement.closest( '[data-tzh-repeater]' )
				: null;
		}

		return repeater;
	}

	function refresh( element ) {
		var root = rootOf( element );

		if ( root ) {
			reindex( root, root.getAttribute( 'data-tzh-base' ) );
		}
	}

	function initRepeaters( editor ) {
		editor.querySelectorAll( '[data-tzh-repeater][data-tzh-base]' ).forEach( function ( root ) {
			reindex( root, root.getAttribute( 'data-tzh-base' ) );
		} );

		editor.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( 'button' );

			if ( ! button || ! editor.contains( button ) ) {
				return;
			}

			if ( button.hasAttribute( 'data-tzh-add' ) ) {
				event.preventDefault();

				var repeater = button.closest( '[data-tzh-repeater]' );
				var template = repeater.querySelector( ':scope > template[data-tzh-row-template]' );
				var rowsBox = repeater.querySelector( ':scope > [data-tzh-rows]' );
				var clone = template.content.firstElementChild.cloneNode( true );

				rowsBox.appendChild( clone );
				refresh( repeater );

				var firstInput = clone.querySelector( 'input, textarea, select' );

				if ( firstInput ) {
					firstInput.focus();
				}

				return;
			}

			var row = button.closest( '[data-tzh-row]' );

			if ( ! row ) {
				return;
			}

			if ( button.hasAttribute( 'data-tzh-remove' ) ) {
				event.preventDefault();

				var parent = row.parentElement;
				row.remove();
				refresh( parent );

				return;
			}

			if ( button.hasAttribute( 'data-tzh-up' ) ) {
				event.preventDefault();

				if ( row.previousElementSibling ) {
					row.parentElement.insertBefore( row, row.previousElementSibling );
					refresh( row );
				}

				return;
			}

			if ( button.hasAttribute( 'data-tzh-down' ) ) {
				event.preventDefault();

				if ( row.nextElementSibling ) {
					row.parentElement.insertBefore( row.nextElementSibling, row );
					refresh( row );
				}

				return;
			}

			if ( button.hasAttribute( 'data-tzh-toggle' ) ) {
				event.preventDefault();

				var collapsed = row.classList.toggle( 'is-collapsed' );
				button.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
			}
		} );

		editor.addEventListener( 'input', function ( event ) {
			if ( event.target.hasAttribute && event.target.hasAttribute( 'data-tzh-summary-source' ) ) {
				var row = event.target.closest( '[data-tzh-row]' );

				if ( row ) {
					updateSummary( row );
				}
			}
		} );
	}

	function init() {
		var editor = document.querySelector( '[data-tzh-editor]' );

		if ( ! editor ) {
			return;
		}

		initTabs( editor );
		initRepeaters( editor );

		// Repeater rows can bring new media fields with them.
		if ( window.tzhScanMedia ) {
			window.tzhScanMedia();
		}
		initPricePreview();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
