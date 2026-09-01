/**
 * AppointKit admin scripts.
 *
 * Two independent pieces:
 *   1. Confirmation prompts for destructive row actions.
 *   2. A week/day booking calendar for the Calendar screen.
 *
 * The calendar is deliberately dependency-free. Shipping a minified copy of a
 * third-party calendar library would add several hundred KB to the plugin and
 * would need its unminified source bundled alongside for WordPress.org review,
 * so the grid below is drawn directly instead.
 *
 * @package AppointKit
 */

( function ( window, document ) {
	'use strict';

	if ( window.appointkitAdminLoaded ) {
		return;
	}
	window.appointkitAdminLoaded = true;

	var adminConfig = window.appointkitAdmin || {};

	// --- 1. Destructive action confirmations ---

	document.addEventListener(
		'click',
		function ( event ) {
			var trigger = event.target.closest ? event.target.closest( '.appointkit-confirm' ) : null;
			if ( ! trigger ) {
				return;
			}
			var message = ( adminConfig.i18n && adminConfig.i18n.confirmDelete ) ||
			'Are you sure you want to delete this?';
			if ( ! window.confirm( message ) ) {
				event.preventDefault();
			}
		}
	);

	// --- 2. Calendar ---

	var calConfig = window.appointkitCalendar || null;
	var DAY_MS    = 86400000;

	/**
	 * Start of the day for a date, in local time.
	 *
	 * @param {Date} date Any date.
	 * @return {Date} Midnight on that day.
	 */
	function startOfDay( date ) {
		return new Date( date.getFullYear(), date.getMonth(), date.getDate() );
	}

	/**
	 * Sunday that begins the week containing the given date.
	 *
	 * @param {Date} date Any date.
	 * @return {Date} Week start.
	 */
	function startOfWeek( date ) {
		var start = startOfDay( date );
		start.setDate( start.getDate() - start.getDay() );
		return start;
	}

	/**
	 * Format a Date as YYYY-MM-DD HH:MM:SS in UTC, for the REST range query.
	 *
	 * @param {Date} date Date to format.
	 * @return {string} UTC MySQL datetime.
	 */
	function toUtcSql( date ) {
		function pad( n ) {
			return ( n < 10 ? '0' : '' ) + n;
		}
		return date.getUTCFullYear() + '-' + pad( date.getUTCMonth() + 1 ) + '-' + pad( date.getUTCDate() ) +
			' ' + pad( date.getUTCHours() ) + ':' + pad( date.getUTCMinutes() ) + ':' + pad( date.getUTCSeconds() );
	}

	/**
	 * The booking calendar.
	 *
	 * @param {HTMLElement} root  Calendar container.
	 * @param {Object}      config Localized settings.
	 */
	function Calendar( root, config ) {
		this.root   = root;
		this.config = config;
		this.view   = 'week';
		this.cursor = startOfDay( new Date() );
		this.events = [];

		this.grid   = root.querySelector( '[data-cal-grid]' );
		this.title  = root.querySelector( '[data-cal-title]' );
		this.status = root.querySelector( '[data-cal-status]' );

		this.bind();
		this.load();
	}

	Calendar.prototype.bind = function () {
		var self = this;

		Array.prototype.forEach.call(
			this.root.querySelectorAll( '[data-cal-nav]' ),
			function ( button ) {
				button.addEventListener(
					'click',
					function () {
						var direction = button.getAttribute( 'data-cal-nav' );
						var span      = 'week' === self.view ? 7 : 1;

						if ( 'today' === direction ) {
							self.cursor = startOfDay( new Date() );
						} else if ( 'prev' === direction ) {
							self.cursor = new Date( self.cursor.getTime() - span * DAY_MS );
						} else {
							self.cursor = new Date( self.cursor.getTime() + span * DAY_MS );
						}
						self.load();
					}
				);
			}
		);

		Array.prototype.forEach.call(
			this.root.querySelectorAll( '[data-cal-view]' ),
			function ( button ) {
				button.addEventListener(
					'click',
					function () {
						self.view = button.getAttribute( 'data-cal-view' );

						Array.prototype.forEach.call(
							self.root.querySelectorAll( '[data-cal-view]' ),
							function ( other ) {
								other.classList.toggle( 'button-primary', other === button );
							}
						);

						self.load();
					}
				);
			}
		);
	};

	/**
	 * The visible date range for the current view.
	 *
	 * @return {Object} Object with start and end Dates.
	 */
	Calendar.prototype.range = function () {
		var start = 'week' === this.view ? startOfWeek( this.cursor ) : startOfDay( this.cursor );
		var days  = 'week' === this.view ? 7 : 1;
		var end   = new Date( start.getTime() + days * DAY_MS );
		return { start: start, end: end, days: days };
	};

	Calendar.prototype.load = function () {
		var self  = this;
		var range = this.range();

		this.setStatus( 'Loading bookings…' );

		var url = this.config.apiBase + '/calendar-events' +
			'?start=' + encodeURIComponent( toUtcSql( range.start ) ) +
			'&end=' + encodeURIComponent( toUtcSql( range.end ) );

		window.fetch(
			url,
			{
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': this.config.nonce }
			}
		).then(
			function ( response ) {
				if ( ! response.ok ) {
						throw new Error( 'Request failed' );
				}
				return response.json();
			}
		).then(
			function ( events ) {
				self.events = events || [];
				self.setStatus( '' );
				self.render();
			}
		).catch(
			function () {
				self.setStatus( 'Could not load bookings. Please reload the page.' );
			}
		);
	};

	Calendar.prototype.setStatus = function ( message ) {
		if ( ! this.status ) {
			return;
		}
		this.status.textContent = message;
		this.status.hidden      = ! message;
	};

	Calendar.prototype.render = function () {
		if ( ! this.grid ) {
			return;
		}

		var range = this.range();
		var self  = this;

		while ( this.grid.firstChild ) {
			this.grid.removeChild( this.grid.firstChild );
		}

		if ( this.title ) {
			this.title.textContent = this.titleText( range );
		}

		// Work out the hour window to show, widened to fit any booking.
		var firstHour = 8;
		var lastHour  = 19;

		var placed = this.events.map(
			function ( event ) {
				var start = new Date( event.start );
				var end   = new Date( event.end );
				return isNaN( start.getTime() ) || isNaN( end.getTime() )
				? null
				: { event: event, start: start, end: end };
			}
		).filter( Boolean );

		placed.forEach(
			function ( item ) {
				firstHour = Math.min( firstHour, item.start.getHours() );
				lastHour  = Math.max( lastHour, item.end.getHours() + ( item.end.getMinutes() ? 1 : 0 ) );
			}
		);

		var hourCount   = Math.max( 1, lastHour - firstHour );
		var table       = document.createElement( 'div' );
		table.className = 'appointkit-cal';
		table.style.setProperty( '--appointkit-cal-hours', String( hourCount ) );

		// Hour gutter.
		var gutter       = document.createElement( 'div' );
		gutter.className = 'appointkit-cal__gutter';
		gutter.appendChild( document.createElement( 'div' ) ); // Header spacer.

		for ( var hour = firstHour; hour < lastHour; hour++ ) {
			var label         = document.createElement( 'div' );
			label.className   = 'appointkit-cal__hour';
			label.textContent = self.hourLabel( hour );
			gutter.appendChild( label );
		}
		table.appendChild( gutter );

		// One column per day.
		for ( var dayIndex = 0; dayIndex < range.days; dayIndex++ ) {
			var dayStart     = new Date( range.start.getTime() + dayIndex * DAY_MS );
			var column       = document.createElement( 'div' );
			column.className = 'appointkit-cal__col';

			var head         = document.createElement( 'div' );
			head.className   = 'appointkit-cal__colhead';
			head.textContent = this.dayLabel( dayStart );
			if ( startOfDay( new Date() ).getTime() === dayStart.getTime() ) {
				head.classList.add( 'is-today' );
			}
			column.appendChild( head );

			var body       = document.createElement( 'div' );
			body.className = 'appointkit-cal__body';

			for ( var h = firstHour; h < lastHour; h++ ) {
				var cell       = document.createElement( 'div' );
				cell.className = 'appointkit-cal__cell';
				body.appendChild( cell );
			}

			// Place the day's events over the hour cells.
			/* eslint-disable no-loop-func */
			placed.filter(
				function ( item ) {
					return startOfDay( item.start ).getTime() === dayStart.getTime();
				}
			).forEach(
				function ( item ) {
					body.appendChild( self.eventNode( item, firstHour, hourCount ) );
				}
			);
			/* eslint-enable no-loop-func */

			column.appendChild( body );
			table.appendChild( column );
		}

		this.grid.appendChild( table );

		if ( ! placed.length ) {
			var empty         = document.createElement( 'p' );
			empty.className   = 'appointkit-cal__empty';
			empty.textContent = 'No bookings in this period.';
			this.grid.appendChild( empty );
		}
	};

	/**
	 * Build the absolutely-positioned node for one booking.
	 *
	 * @param {Object} item      Event with parsed dates.
	 * @param {number} firstHour First hour shown.
	 * @param {number} hourCount Number of hours shown.
	 * @return {HTMLElement} The event element.
	 */
	Calendar.prototype.eventNode = function ( item, firstHour, hourCount ) {
		var startHours = item.start.getHours() + item.start.getMinutes() / 60;
		var endHours   = item.end.getHours() + item.end.getMinutes() / 60;

		// A booking running past midnight is clipped to the end of its day.
		if ( endHours <= startHours ) {
			endHours = firstHour + hourCount;
		}

		var top    = ( ( startHours - firstHour ) / hourCount ) * 100;
		var height = ( ( endHours - startHours ) / hourCount ) * 100;

		var props         = item.event.extendedProps || {};
		var node          = document.createElement( 'a' );
		node.className    = 'appointkit-cal__event';
		node.href         = 'admin.php?page=appointkit&action=view&id=' + encodeURIComponent( props.booking_id || item.event.id );
		node.style.top    = Math.max( 0, top ) + '%';
		node.style.height = Math.max( 2, Math.min( 100 - top, height ) ) + '%';

		if ( item.event.color ) {
			node.style.background = item.event.color;
		}
		if ( props.status ) {
			node.classList.add( 'is-' + String( props.status ).replace( /[^a-z0-9_-]/gi, '' ) );
		}

		var time         = document.createElement( 'span' );
		time.className   = 'appointkit-cal__event-time';
		time.textContent = this.timeLabel( item.start );
		node.appendChild( time );

		var title         = document.createElement( 'span' );
		title.className   = 'appointkit-cal__event-title';
		title.textContent = item.event.title || '';
		node.appendChild( title );

		if ( props.staff_name ) {
			var staff         = document.createElement( 'span' );
			staff.className   = 'appointkit-cal__event-staff';
			staff.textContent = props.staff_name;
			node.appendChild( staff );
		}

		return node;
	};

	Calendar.prototype.titleText = function ( range ) {
		var last = new Date( range.end.getTime() - DAY_MS );
		if ( 'day' === this.view ) {
			return range.start.toLocaleDateString(
				undefined,
				{
					weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
				}
			);
		}
		var opts = { month: 'short', day: 'numeric' };
		return range.start.toLocaleDateString( undefined, opts ) + ' to ' +
			last.toLocaleDateString( undefined, { month: 'short', day: 'numeric', year: 'numeric' } );
	};

	Calendar.prototype.dayLabel = function ( date ) {
		return date.toLocaleDateString( undefined, { weekday: 'short', day: 'numeric' } );
	};

	Calendar.prototype.hourLabel = function ( hour ) {
		var date = new Date();
		date.setHours( hour, 0, 0, 0 );
		return date.toLocaleTimeString( undefined, { hour: 'numeric' } );
	};

	Calendar.prototype.timeLabel = function ( date ) {
		return date.toLocaleTimeString( undefined, { hour: 'numeric', minute: '2-digit' } );
	};

	/**
	 * Run a callback once the DOM is parsed.
	 *
	 * Checking readyState first means the callback still runs when this script
	 * is loaded late (for example by an optimisation plugin that loads scripts
	 * asynchronously), after DOMContentLoaded has already fired.
	 *
	 * @param {Function} callback Function to run.
	 */
	function ready( callback ) {
		if ( 'loading' !== document.readyState ) {
			callback();
			return;
		}
		document.addEventListener( 'DOMContentLoaded', callback );
	}

	ready(
		function () {
			var root = document.getElementById( 'appointkit-calendar' );
			if ( ! root || ! calConfig || ! calConfig.apiBase ) {
				return;
			}
			if ( root.getAttribute( 'data-appointkit-ready' ) ) {
				return;
			}
			root.setAttribute( 'data-appointkit-ready', '1' );
			new Calendar( root, calConfig );
		}
	);
} )( window, document );
