/**
 * Bookings screen — status control.
 *
 * The form works on its own: pick a status, press Update. This only makes the
 * button behave like a save button rather than a permanent fixture, so a row
 * that has not been touched stays quiet.
 */
( function () {
	'use strict';

	var forms = document.querySelectorAll( '.tzh-status' );

	if ( ! forms.length ) {
		return;
	}

	Array.prototype.forEach.call( forms, function ( form ) {
		var select = form.querySelector( '.tzh-status__select' );
		var button = form.querySelector( '.tzh-status__go' );

		if ( ! select || ! button ) {
			return;
		}

		form.classList.add( 'tzh-status--live' );

		select.addEventListener( 'change', function () {
			form.classList.toggle( 'tzh-status--dirty', select.value !== select.dataset.initial );
		} );

		form.addEventListener( 'submit', function () {
			button.disabled = true;
			button.textContent = ( window.tzhBookings && window.tzhBookings.saving ) || 'Saving…';
		} );
	} );
}() );
