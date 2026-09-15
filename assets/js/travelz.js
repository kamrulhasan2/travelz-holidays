/**
 * TravelZ Holidays — front-end behaviour.
 *
 * Progressive enhancement only: every screen this plugin renders works with
 * JavaScript switched off. This file adds the tabs, the toasts and the live
 * booking arithmetic on top.
 */
( function () {
	'use strict';

	var config = window.tzhFront || {};

	/** Format an amount the way the rest of the site does. */
	function money( amount ) {
		var symbol = config.currency || '৳';

		if ( ! isFinite( amount ) ) {
			return symbol + '0';
		}

		return symbol + Math.round( amount ).toLocaleString( 'en-IN' );
	}

	/* ---------- Toasts ---------- */

	function toast( message, options ) {
		options = options || {};

		var host = document.querySelector( '.tz-toasts' );

		if ( ! host ) {
			host = document.createElement( 'div' );
			host.className = 'tz-toasts';
			host.setAttribute( 'role', 'status' );
			host.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( host );
		}

		var box = document.createElement( 'div' );
		box.className = 'tz-toast' + ( options.type ? ' tz-toast--' + options.type : '' );
		box.textContent = message;

		if ( options.description ) {
			var sub = document.createElement( 'div' );
			sub.className = 'tz-toast__sub';
			sub.textContent = options.description;
			box.appendChild( sub );
		}

		host.appendChild( box );

		window.setTimeout( function () {
			box.remove();

			if ( ! host.children.length ) {
				host.remove();
			}
		}, options.duration || 4000 );
	}

	/* ---------- Tabs ---------- */

	function initTabs( root ) {
		var tabs = Array.prototype.slice.call( root.querySelectorAll( '[data-tz-tab]' ) );
		var panels = root.querySelectorAll( '[data-tz-panel]' );

		if ( ! tabs.length ) {
			return;
		}

		function show( slug, focus ) {
			var known = false;

			panels.forEach( function ( panel ) {
				var match = panel.getAttribute( 'data-tz-panel' ) === slug;
				panel.hidden = ! match;
				known = known || match;
			} );

			if ( ! known ) {
				return;
			}

			tabs.forEach( function ( tab ) {
				var match = tab.getAttribute( 'data-tz-tab' ) === slug;
				tab.setAttribute( 'aria-selected', match ? 'true' : 'false' );
				tab.tabIndex = match ? 0 : -1;

				if ( match && focus ) {
					tab.focus();
				}
			} );

			if ( window.history.replaceState ) {
				window.history.replaceState( null, '', '#' + slug );
			}
		}

		tabs.forEach( function ( tab, index ) {
			tab.addEventListener( 'click', function () {
				show( tab.getAttribute( 'data-tz-tab' ), false );
			} );

			tab.addEventListener( 'keydown', function ( event ) {
				if ( 'ArrowRight' !== event.key && 'ArrowLeft' !== event.key ) {
					return;
				}

				event.preventDefault();

				var step = 'ArrowRight' === event.key ? 1 : tabs.length - 1;
				var next = tabs[ ( index + step ) % tabs.length ];

				show( next.getAttribute( 'data-tz-tab' ), true );
			} );
		} );

		var hash = window.location.hash.replace( '#', '' );

		show( hash || tabs[ 0 ].getAttribute( 'data-tz-tab' ), false );
	}


	/* ---------- Price range (two overlaid sliders) ---------- */

	function initRange( root ) {
		var min = root.querySelector( '[data-tz-range-min]' );
		var max = root.querySelector( '[data-tz-range-max]' );
		var fill = root.querySelector( '[data-tz-range-fill]' );

		if ( ! min || ! max ) {
			return;
		}

		var floor = parseFloat( min.min );
		var ceiling = parseFloat( min.max );
		var span = ceiling - floor || 1;

		function out( which ) {
			return document.querySelector( '[data-tz-range-out="' + which + '"]' );
		}

		function paint() {
			var lo = parseFloat( min.value );
			var hi = parseFloat( max.value );

			if ( fill ) {
				fill.style.left = ( ( lo - floor ) / span * 100 ) + '%';
				fill.style.right = ( ( ceiling - hi ) / span * 100 ) + '%';
			}

			var lowCell = out( 'min' );
			var highCell = out( 'max' );

			if ( lowCell ) {
				lowCell.textContent = money( lo );
			}

			if ( highCell ) {
				highCell.textContent = money( hi );
			}
		}

		// The handles must never cross: whichever one is being dragged pushes
		// the other rather than passing it.
		min.addEventListener( 'input', function () {
			if ( parseFloat( min.value ) > parseFloat( max.value ) ) {
				max.value = min.value;
			}

			paint();
		} );

		max.addEventListener( 'input', function () {
			if ( parseFloat( max.value ) < parseFloat( min.value ) ) {
				min.value = max.value;
			}

			paint();
		} );

		paint();
	}

	/* ---------- Filter panel ---------- */

	function initFilters( form ) {
		var main = document.querySelector( '.tz-list-main' );
		var results = main ? main.querySelector( '[data-tz-results]' ) : null;

		if ( ! results || ! config.rest ) {
			return;
		}

		// With the fetch path live, the submit button is no longer needed.
		var apply = form.querySelector( '.tz-filters__apply' );

		if ( apply ) {
			apply.hidden = true;
		}

		var token = 0;

		function load( page, push ) {
			var data = new FormData( form );
			var params = new URLSearchParams();

			data.forEach( function ( value, key ) {
				params.append( key, value );
			} );

			if ( page > 1 ) {
				params.set( 'page', String( page ) );
			}

			var mine = ++token;

			results.setAttribute( 'aria-busy', 'true' );
			results.classList.add( 'is-loading' );

			window.fetch( config.rest + '?' + params.toString(), {
				headers: { Accept: 'application/json' }
			} )
				.then( function ( response ) {
					return response.ok ? response.json() : Promise.reject( response.status );
				} )
				.then( function ( payload ) {
					if ( mine !== token ) {
						return;
					}

					results.outerHTML = payload.html;
					results = main.querySelector( '[data-tz-results]' );

					if ( push && window.history.pushState ) {
						var query = params.toString();
						window.history.pushState( {}, '', query ? form.action + '?' + query : form.action );
					}
				} )
				.catch( function () {
					if ( mine === token ) {
						results.removeAttribute( 'aria-busy' );
						results.classList.remove( 'is-loading' );
						toast( config.i18n && config.i18n.loading ? config.i18n.loading : 'Could not load packages.', { type: 'error' } );
					}
				} );
		}

		var debounce = null;

		form.addEventListener( 'change', function () {
			load( 1, true );
		} );

		form.addEventListener( 'input', function ( event ) {
			if ( 'range' !== event.target.type ) {
				return;
			}

			window.clearTimeout( debounce );
			debounce = window.setTimeout( function () {
				load( 1, true );
			}, 400 );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			load( 1, true );
		} );

		// Paginate without leaving the page.
		main.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( '.tz-pagination a' );

			if ( ! link ) {
				return;
			}

			event.preventDefault();

			var page = link.href.match( /\/page\/(\d+)/ );
			load( page ? parseInt( page[ 1 ], 10 ) : 1, true );
			main.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		} );

		window.addEventListener( 'popstate', function () {
			window.location.reload();
		} );
	}

	/* ---------- Mobile filter drawer ---------- */

	function initDrawer() {
		var aside = document.querySelector( '.tz-list-aside' );
		var opener = document.querySelector( '[data-tz-filters-open]' );

		if ( ! aside || ! opener ) {
			return;
		}

		function close() {
			aside.classList.remove( 'is-open' );
			opener.setAttribute( 'aria-expanded', 'false' );
			document.body.classList.remove( 'tz-locked' );
		}

		opener.addEventListener( 'click', function () {
			var open = aside.classList.toggle( 'is-open' );
			opener.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			document.body.classList.toggle( 'tz-locked', open );
		} );

		aside.addEventListener( 'click', function ( event ) {
			if ( event.target === aside ) {
				close();
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				close();
			}
		} );
	}

	function init() {
		document.querySelectorAll( '[data-tz-tabs]' ).forEach( initTabs );
		document.querySelectorAll( '[data-tz-range]' ).forEach( initRange );
		document.querySelectorAll( '[data-tz-filters]' ).forEach( initFilters );
		initDrawer();
	}

	window.tzhToast = toast;
	window.tzhMoney = money;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
