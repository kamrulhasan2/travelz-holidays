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

			if ( button.hasAttribute( 'data-tzh-copy' ) ) {
				event.preventDefault();

				// A cloned node carries the typed values with it, which is the
				// whole point: day four is usually day three with one change.
				var copy = row.cloneNode( true );

				copy.classList.remove( 'is-collapsed' );
				row.parentElement.insertBefore( copy, row.nextElementSibling );
				refresh( copy );

				copy.querySelectorAll( 'input, textarea, select' ).forEach( function ( input, index ) {
					var source = row.querySelectorAll( 'input, textarea, select' )[ index ];

					if ( ! source ) {
						return;
					}

					if ( 'checkbox' === input.type || 'radio' === input.type ) {
						input.checked = source.checked;
					} else {
						input.value = source.value;
					}
				} );

				refresh( copy );

				if ( window.tzhScanMedia ) {
					window.tzhScanMedia();
				}

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

	/* ---------- Summary bar and tab badges ---------- */

	function initSummary( editor ) {
		var keys = config.factKeys || {};
		var words = config.i18n || {};
		var empties = words.empty || {};
		var required = config.required || [];
		var ready = editor.querySelector( '[data-tzh-ready]' );

		function pad( value ) {
			var n = parseInt( value, 10 );

			return isFinite( n ) && n > 0 ? ( n < 10 ? '0' + n : String( n ) ) : '';
		}

		function textOf( input ) {
			if ( ! input ) {
				return '';
			}

			// A select carries its label, not its id — nobody recognises "12".
			if ( 'SELECT' === input.tagName ) {
				var option = input.options[ input.selectedIndex ];

				return option && option.value ? option.textContent.trim() : '';
			}

			return input.value.trim();
		}

		function setFact( name, value ) {
			var host = editor.querySelector( '[data-tzh-fact="' + name + '"]' );

			if ( ! host ) {
				return;
			}

			var slot = host.querySelector( '[data-tzh-fact-value]' );
			var filled = '' !== value;

			host.classList.toggle( 'is-empty', ! filled );
			slot.textContent = filled ? value : ( empties[ name ] || '' );
		}

		function duration() {
			var days = pad( textOf( field( keys.days ) ) );
			var nights = pad( textOf( field( keys.nights ) ) );

			if ( ! days ) {
				return '';
			}

			return ( words.duration || '%1$s Days %2$s Nights' )
				.replace( '%1$s', days )
				.replace( '%2$s', nights || '00' );
		}

		function badges() {
			editor.querySelectorAll( '[data-tzh-panel]' ).forEach( function ( panel ) {
				var slug = panel.getAttribute( 'data-tzh-panel' );
				var badge = editor.querySelector( '[data-tzh-badge="' + slug + '"]' );

				if ( ! badge ) {
					return;
				}

				var rows = panel.querySelectorAll( '[data-tzh-repeater][data-tzh-base] > [data-tzh-rows] > [data-tzh-row]' ).length;
				var lines = 0;

				panel.querySelectorAll( 'textarea[data-tzh-lines]' ).forEach( function ( box ) {
					lines += box.value.split( '\n' ).filter( function ( line ) {
						return '' !== line.trim();
					} ).length;
				} );

				var count = rows + lines;

				badge.textContent = count ? String( count ) : '';
				badge.hidden = 0 === count;
				badge.classList.remove( 'is-alert' );
			} );

			// A missing essential outranks a count: the tab shows the problem.
			required.forEach( function ( rule ) {
				var input = field( rule.name );

				if ( ! input || '' !== textOf( input ) && '0' !== textOf( input ) ) {
					return;
				}

				var badge = editor.querySelector( '[data-tzh-badge="' + rule.tab + '"]' );

				if ( badge ) {
					badge.textContent = '!';
					badge.hidden = false;
					badge.classList.add( 'is-alert' );
				}
			} );
		}

		function update() {
			setFact( 'code', textOf( field( keys.code ) ) );
			setFact( 'dest', textOf( field( keys.dest ) ) );
			setFact( 'duration', duration() );

			var price = parseInt( textOf( field( keys.price ) ), 10 );
			setFact( 'price', isFinite( price ) && price > 0 ? money( price ) : '' );

			var missing = required.filter( function ( rule ) {
				var input = field( rule.name );
				var value = textOf( input );

				return input && ( '' === value || '0' === value );
			} ).map( function ( rule ) {
				return rule.label;
			} );

			if ( ready ) {
				ready.textContent = missing.length
					? ( words.missing || 'Still missing: %s' ).replace( '%s', missing.join( ', ' ) )
					: ( words.ready || 'Ready to publish' );
				ready.classList.toggle( 'is-warn', missing.length > 0 );
			}

			badges();
		}

		editor.addEventListener( 'input', update );
		editor.addEventListener( 'change', update );

		update();
	}

	/* ---------- Drag to reorder ---------- */

	function initDragging( editor ) {
		var dragged = null;

		editor.addEventListener( 'dragstart', function ( event ) {
			var grip = event.target.closest( '[data-tzh-grip]' );

			if ( ! grip ) {
				return;
			}

			dragged = grip.closest( '[data-tzh-row]' );
			dragged.classList.add( 'is-dragging' );

			// Firefox refuses to start a drag without payload.
			event.dataTransfer.setData( 'text/plain', '' );
			event.dataTransfer.effectAllowed = 'move';
		} );

		editor.addEventListener( 'dragover', function ( event ) {
			if ( ! dragged ) {
				return;
			}

			var over = event.target.closest( '[data-tzh-row]' );

			// Only rows in the same list may swap; a hotel cannot become a day.
			if ( ! over || over === dragged || over.parentElement !== dragged.parentElement ) {
				return;
			}

			event.preventDefault();

			var box = over.getBoundingClientRect();
			var after = event.clientY > box.top + box.height / 2;

			over.parentElement.insertBefore( dragged, after ? over.nextElementSibling : over );
		} );

		editor.addEventListener( 'drop', function ( event ) {
			if ( dragged ) {
				event.preventDefault();
			}
		} );

		editor.addEventListener( 'dragend', function () {
			if ( ! dragged ) {
				return;
			}

			dragged.classList.remove( 'is-dragging' );
			refresh( dragged );
			dragged = null;
		} );
	}

	/* ---------- Unsaved changes ---------- */

	function initGuard( editor ) {
		var dirty = false;
		var form = editor.closest( 'form' );

		editor.addEventListener( 'input', function () {
			dirty = true;
		} );

		editor.addEventListener( 'change', function () {
			dirty = true;
		} );

		if ( form ) {
			form.addEventListener( 'submit', function () {
				dirty = false;
			} );
		}

		window.addEventListener( 'beforeunload', function ( event ) {
			if ( ! dirty ) {
				return;
			}

			event.preventDefault();
			event.returnValue = '';
		} );
	}

	function init() {
		var editor = document.querySelector( '[data-tzh-editor]' );

		if ( ! editor ) {
			return;
		}

		initTabs( editor );
		initRepeaters( editor );
		initDragging( editor );
		initSummary( editor );
		initGuard( editor );

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
