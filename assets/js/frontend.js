/**
 * AppointKit booking form.
 *
 * Drives the six-step wizard rendered by templates/frontend/booking-form.php:
 * service, staff, date, time, details, confirm. All data comes from the
 * /appointkit/v1 REST endpoints; nothing is rendered server-side.
 *
 * Text from the API (service names, staff names) is inserted with textContent
 * rather than innerHTML so a malicious service name cannot inject markup.
 *
 * @package AppointKit
 */

( function ( window, document ) {
	'use strict';

	var config = window.appointkitForm || {};
	var i18n   = config.i18n || {};
	var TZ     = window.AppointKitTZ;

	/**
	 * Translate with a safe fallback when a string is missing.
	 *
	 * @param {string} key      i18n key.
	 * @param {string} fallback Default English text.
	 * @return {string} Translated string.
	 */
	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	/**
	 * Create an element with optional class and text.
	 *
	 * @param {string} tag       Tag name.
	 * @param {string} className Class attribute.
	 * @param {string} text      Text content.
	 * @return {HTMLElement} The new element.
	 */
	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( undefined !== text && null !== text ) {
			node.textContent = text;
		}
		return node;
	}

	/**
	 * Remove every child of a node.
	 *
	 * @param {HTMLElement} node Parent node.
	 */
	function empty( node ) {
		while ( node.firstChild ) {
			node.removeChild( node.firstChild );
		}
	}

	/**
	 * One booking form instance.
	 *
	 * @param {HTMLElement} root The .appointkit-booking-form element.
	 */
	function BookingForm( root ) {
		this.root  = root;
		this.nonce = root.getAttribute( 'data-nonce' ) || config.nonce || '';

		this.state = {
			step: 1,
			serviceId: parseInt( root.getAttribute( 'data-service-id' ), 10 ) || 0,
			staffId: parseInt( root.getAttribute( 'data-staff-id' ), 10 ) || 0,
			service: null,
			staff: null,
			date: '',
			slot: null,
			submitting: false
		};

		// Steps that were pre-selected via shortcode attributes are skipped.
		this.lockedService = this.state.serviceId > 0;
		this.lockedStaff   = this.state.staffId > 0;

		this.stripe = null;
		this.card   = null;

		this.cacheDom();
		this.bind();
		this.start();
	}

	BookingForm.prototype.cacheDom = function () {
		var root   = this.root;
		this.steps = {};
		Array.prototype.forEach.call(
			root.querySelectorAll( '.appointkit-step' ),
			function ( node ) {
				this.steps[ node.getAttribute( 'data-step' ) ] = node;
			}.bind( this )
		);

		this.navItems       = root.querySelectorAll( '.appointkit-step-nav__item' );
		this.serviceList    = root.querySelector( '.appointkit-service-list' );
		this.staffList      = root.querySelector( '.appointkit-staff-list' );
		this.datePicker     = root.querySelector( '.appointkit-datepicker' );
		this.slotList       = root.querySelector( '.appointkit-slots' );
		this.detailsForm    = root.querySelector( '.appointkit-details-form' );
		this.summary        = root.querySelector( '.appointkit-summary' );
		this.submitBtn      = root.querySelector( '#appointkit-submit' );
		this.backBtn        = root.querySelector( '.appointkit-btn--back' );
		this.formErrors     = root.querySelector( '.appointkit-form-errors' );
		this.paymentSection = root.querySelector( '#appointkit-payment-section' );
		this.stripeMount    = root.querySelector( '#appointkit-stripe-element' );
		this.stripeErrors   = root.querySelector( '#appointkit-stripe-errors' );
		this.success        = root.querySelector( '.appointkit-success' );
		this.successDetails = root.querySelector( '.appointkit-success__details' );
	};

	BookingForm.prototype.bind = function () {
		var self = this;

		if ( this.datePicker ) {
			this.datePicker.addEventListener(
				'change',
				function () {
					self.state.date = this.value;
					if ( self.state.date ) {
						self.go( 4 );
					}
				}
			);
		}

		if ( this.submitBtn ) {
			this.submitBtn.addEventListener(
				'click',
				function () {
					self.submit();
				}
			);
		}

		if ( this.backBtn ) {
			this.backBtn.addEventListener(
				'click',
				function () {
					self.go( 5 );
				}
			);
		}

		// Steps 2-5 get a footer with Back (and Next where a step has no
		// auto-advancing selection). Step 6 already has its own actions.
		[ 2, 3, 4, 5 ].forEach(
			function ( step ) {
				var node = self.steps[ step ];
				if ( ! node ) {
						return;
				}
				var actions = el( 'div', 'appointkit-actions' );

				if ( step > self.firstStep() ) {
					var back  = el( 'button', 'appointkit-btn appointkit-btn--back', t( 'back', 'Back' ) );
					back.type = 'button';
					back.addEventListener(
						'click',
						function () {
							self.back( step );
						}
					);
					actions.appendChild( back );
				}

				if ( 5 === step ) {
					var next  = el( 'button', 'appointkit-btn appointkit-btn--primary', t( 'next', 'Next' ) );
					next.type = 'button';
					next.addEventListener(
						'click',
						function () {
							if ( self.validateDetails() ) {
								self.go( 6 );
							}
						}
					);
					actions.appendChild( next );
				}

				node.appendChild( actions );
			}
		);
	};

	/**
	 * Decide which step a Back button should return to, skipping locked steps.
	 *
	 * @param {number} from Current step.
	 */
	BookingForm.prototype.back = function ( from ) {
		var target = from - 1;
		while ( target > 1 && ! this.stepAllowed( target ) ) {
			target--;
		}
		this.go( Math.max( this.firstStep(), target ) );
	};

	/**
	 * Whether a step is reachable, given what the shortcode pre-selected.
	 *
	 * @param {number} step Step number.
	 * @return {boolean} True when the visitor may see this step.
	 */
	BookingForm.prototype.stepAllowed = function ( step ) {
		if ( 1 === step ) {
			return ! this.lockedService;
		}
		if ( 2 === step ) {
			return ! this.lockedStaff;
		}
		return true;
	};

	/**
	 * The earliest step this form can show.
	 *
	 * @return {number} First reachable step.
	 */
	BookingForm.prototype.firstStep = function () {
		if ( ! this.lockedService ) {
			return 1;
		}
		if ( ! this.lockedStaff ) {
			return 2;
		}
		return 3;
	};

	BookingForm.prototype.start = function () {
		if ( this.lockedService ) {
			// Fetch the pre-selected service so the summary can name it.
			this.request( 'GET', '/services' ).then(
				function ( services ) {
					var match          = ( services || [] ).filter(
						function ( s ) {
							return parseInt( s.id, 10 ) === this.state.serviceId;
						}.bind( this )
					)[ 0 ];
					this.state.service = match || null;
					this.go( this.lockedStaff ? 3 : 2 );
				}.bind( this )
			).catch(
				function () {
					this.go( this.lockedStaff ? 3 : 2 );
				}.bind( this )
			);
			return;
		}
		this.go( 1 );
	};

	/**
	 * Show a step and load whatever data it needs.
	 *
	 * @param {number} step Step number.
	 */
	BookingForm.prototype.go = function ( step ) {
		this.state.step = step;

		Object.keys( this.steps ).forEach(
			function ( key ) {
				this.steps[ key ].hidden = parseInt( key, 10 ) !== step;
			}.bind( this )
		);

		Array.prototype.forEach.call(
			this.navItems,
			function ( item ) {
				var itemStep = parseInt( item.getAttribute( 'data-step' ), 10 );
				item.classList.toggle( 'is-active', itemStep === step );
				item.classList.toggle( 'is-complete', itemStep < step );
			}
		);

		if ( 1 === step ) {
			this.loadServices();
		} else if ( 2 === step ) {
			this.loadStaff();
		} else if ( 4 === step ) {
			this.loadSlots();
		} else if ( 6 === step ) {
			this.renderSummary();
			this.setupStripe();
		}
	};

	// --- Step 1: services ---

	BookingForm.prototype.loadServices = function () {
		var self = this;
		this.setLoading( this.serviceList );

		this.request( 'GET', '/services' ).then(
			function ( services ) {
				empty( self.serviceList );
				if ( ! services || ! services.length ) {
						self.serviceList.appendChild( el( 'p', 'appointkit-empty', t( 'noServices', 'No services are available for booking yet.' ) ) );
						return;
				}
				services.forEach(
					function ( service ) {
						var card  = el( 'button', 'appointkit-card appointkit-card--service' );
						card.type = 'button';
						card.setAttribute( 'role', 'listitem' );

						card.appendChild( el( 'span', 'appointkit-card__title', service.name ) );

						var meta         = el( 'span', 'appointkit-card__meta' );
						meta.textContent = self.durationLabel( service.duration );
						if ( service.price_display && parseFloat( service.price ) > 0 ) {
							meta.textContent += ' · ' + service.price_display;
						}
						card.appendChild( meta );

						if ( service.description ) {
							card.appendChild( el( 'span', 'appointkit-card__desc', service.description ) );
						}
						if ( service.color ) {
							card.style.borderLeftColor = service.color;
						}

						card.addEventListener(
							'click',
							function () {
								self.state.service   = service;
								self.state.serviceId = parseInt( service.id, 10 );
								self.state.staff     = null;
								self.state.staffId   = self.lockedStaff ? self.state.staffId : 0;
								self.go( self.lockedStaff ? 3 : 2 );
							}
						);

						self.serviceList.appendChild( card );
					}
				);
			}
		).catch(
			function () {
				self.showListError( self.serviceList );
			}
		);
	};

	// --- Step 2: staff ---

	BookingForm.prototype.loadStaff = function () {
		var self = this;
		this.setLoading( this.staffList );

		this.request( 'GET', '/staff?service_id=' + encodeURIComponent( this.state.serviceId ) ).then(
			function ( staff ) {
				empty( self.staffList );
				if ( ! staff || ! staff.length ) {
						self.staffList.appendChild( el( 'p', 'appointkit-empty', t( 'noStaff', 'No staff are available for this service.' ) ) );
						return;
				}

				// "Any available" resolves to a specific person once a slot is picked.
				var any  = el( 'button', 'appointkit-card appointkit-card--staff' );
				any.type = 'button';
				any.setAttribute( 'role', 'listitem' );
				any.appendChild( el( 'span', 'appointkit-card__title', t( 'anyStaff', 'Any available' ) ) );
				any.addEventListener(
					'click',
					function () {
						self.state.staff   = null;
						self.state.staffId = 0;
						self.go( 3 );
					}
				);
				self.staffList.appendChild( any );

				staff.forEach(
					function ( member ) {
						var card  = el( 'button', 'appointkit-card appointkit-card--staff' );
						card.type = 'button';
						card.setAttribute( 'role', 'listitem' );

						if ( member.photo_url ) {
								var img       = document.createElement( 'img' );
								img.className = 'appointkit-card__photo';
								img.src       = member.photo_url;
								img.alt       = '';
								card.appendChild( img );
						}

						card.appendChild( el( 'span', 'appointkit-card__title', member.name ) );
						if ( member.bio ) {
							card.appendChild( el( 'span', 'appointkit-card__desc', member.bio ) );
						}

						card.addEventListener(
							'click',
							function () {
								self.state.staff   = member;
								self.state.staffId = parseInt( member.id, 10 );
								self.go( 3 );
							}
						);

						self.staffList.appendChild( card );
					}
				);
			}
		).catch(
			function () {
				self.showListError( self.staffList );
			}
		);
	};

	// --- Step 4: slots ---

	BookingForm.prototype.loadSlots = function () {
		var self = this;
		this.setLoading( this.slotList );

		var query = '/slots?service_id=' + encodeURIComponent( this.state.serviceId ) +
			'&staff_id=' + encodeURIComponent( this.state.staffId ) +
			'&date=' + encodeURIComponent( this.state.date );

		this.request( 'GET', query ).then(
			function ( slots ) {
				empty( self.slotList );
				if ( ! slots || ! slots.length ) {
						self.slotList.appendChild( el( 'p', 'appointkit-empty', t( 'noSlots', 'No available times for this date. Please choose another date.' ) ) );
						return;
				}
				slots.forEach(
					function ( slot ) {
						var label = slot.start_display;
						// Re-label in the visitor's timezone when we can work it out.
						if ( TZ ) {
							label = TZ.localTime( slot.start_utc, slot.start_display );
						}
						var btn  = el( 'button', 'appointkit-slot', label );
						btn.type = 'button';
						btn.setAttribute( 'role', 'listitem' );
						btn.addEventListener(
							'click',
							function () {
								self.state.slot = slot;
								self.go( 5 );
							}
						);
						self.slotList.appendChild( btn );
					}
				);
			}
		).catch(
			function () {
				self.showListError( self.slotList );
			}
		);
	};

	// --- Step 5: details ---

	BookingForm.prototype.validateDetails = function () {
		this.clearErrors();
		if ( ! this.detailsForm ) {
			return true;
		}

		var name     = this.field( 'customer_name' );
		var email    = this.field( 'customer_email' );
		var problems = [];

		if ( ! name || ! name.value.trim() ) {
			problems.push( t( 'nameRequired', 'Please enter your name.' ) );
		}
		if ( ! email || ! email.value.trim() ) {
			problems.push( t( 'emailRequired', 'Please enter your email address.' ) );
		} else if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email.value.trim() ) ) {
			problems.push( t( 'emailInvalid', 'Please enter a valid email address.' ) );
		}

		if ( problems.length ) {
			this.showErrors( problems.join( ' ' ), this.steps[ 5 ] );
			return false;
		}
		return true;
	};

	/**
	 * Read a named input from the details form.
	 *
	 * @param {string} name Input name attribute.
	 * @return {HTMLElement|null} The input.
	 */
	BookingForm.prototype.field = function ( name ) {
		return this.detailsForm ? this.detailsForm.querySelector( '[name="' + name + '"]' ) : null;
	};

	// --- Step 6: summary, payment, submit ---

	BookingForm.prototype.renderSummary = function () {
		if ( ! this.summary ) {
			return;
		}
		empty( this.summary );

		var rows = [];
		if ( this.state.service ) {
			rows.push( [ t( 'service', 'Service' ), this.state.service.name ] );
		}
		if ( this.state.staff ) {
			rows.push( [ t( 'staff', 'Staff' ), this.state.staff.name ] );
		}
		if ( this.state.slot ) {
			var when = TZ
				? TZ.localDateTime( this.state.slot.start_utc, this.state.date + ' ' + this.state.slot.start_display )
				: this.state.date + ' ' + this.state.slot.start_display;
			rows.push( [ t( 'when', 'When' ), when ] );
		}
		if ( this.state.service && parseFloat( this.state.service.price ) > 0 ) {
			rows.push( [ t( 'price', 'Price' ), this.state.service.price_display ] );
		}

		var list = el( 'dl', 'appointkit-summary__list' );
		rows.forEach(
			function ( row ) {
				list.appendChild( el( 'dt', null, row[ 0 ] ) );
				list.appendChild( el( 'dd', null, row[ 1 ] ) );
			}
		);
		this.summary.appendChild( list );
	};

	/**
	 * True when this booking needs a card payment.
	 *
	 * @return {boolean} Whether to collect payment.
	 */
	BookingForm.prototype.needsPayment = function () {
		return ! ! (
			config.stripePublishableKey &&
			this.paymentSection &&
			this.state.service &&
			parseFloat( this.state.service.price ) > 0
		);
	};

	BookingForm.prototype.setupStripe = function () {
		if ( ! this.needsPayment() ) {
			if ( this.paymentSection ) {
				this.paymentSection.hidden = true;
			}
			return;
		}

		this.paymentSection.hidden = false;

		if ( this.card || 'undefined' === typeof window.Stripe ) {
			return;
		}

		this.stripe  = window.Stripe( config.stripePublishableKey );
		var elements = this.stripe.elements();
		this.card    = elements.create( 'card' );
		this.card.mount( this.stripeMount );

		var self = this;
		this.card.on(
			'change',
			function ( event ) {
				if ( self.stripeErrors ) {
					self.stripeErrors.textContent = event.error ? event.error.message : '';
				}
			}
		);
	};

	BookingForm.prototype.submit = function () {
		if ( this.state.submitting ) {
			return;
		}
		if ( ! this.state.slot ) {
			this.showErrors( t( 'selectTime', 'Select a time' ) );
			return;
		}
		if ( ! this.validateDetails() ) {
			this.go( 5 );
			return;
		}

		this.clearErrors();
		this.setSubmitting( true );

		var self = this;

		if ( this.needsPayment() && this.stripe && this.card ) {
			this.stripe.createPaymentMethod( { type: 'card', card: this.card } ).then(
				function ( result ) {
					if ( result.error ) {
						if ( self.stripeErrors ) {
							self.stripeErrors.textContent = result.error.message;
						}
						self.setSubmitting( false );
						return;
					}
					self.createBooking( result.paymentMethod.id );
				}
			).catch(
				function () {
					self.showErrors( t( 'errorOccurred', 'An error occurred. Please try again.' ) );
					self.setSubmitting( false );
				}
			);
			return;
		}

		this.createBooking( '' );
	};

	/**
	 * POST the booking.
	 *
	 * @param {string} paymentMethodId Stripe PaymentMethod ID, or empty string.
	 */
	BookingForm.prototype.createBooking = function ( paymentMethodId ) {
		var self = this;

		// With "any available" staff, the chosen slot tells us who is free.
		var staffId = this.state.staffId || ( this.state.slot && this.state.slot.staff_id ) || 0;

		var payload = {
			service_id: this.state.serviceId,
			staff_id: staffId,
			start_utc: this.state.slot.start_utc,
			customer_name: this.value( 'customer_name' ),
			customer_email: this.value( 'customer_email' ),
			customer_phone: this.value( 'customer_phone' ),
			notes: this.value( 'notes' )
		};

		if ( paymentMethodId ) {
			payload.payment_method_id = paymentMethodId;
		}

		this.request( 'POST', '/bookings', payload ).then(
			function ( booking ) {
				self.setSubmitting( false );
				self.showSuccess( booking );
			}
		).catch(
			function ( error ) {
				self.setSubmitting( false );
				self.showErrors( ( error && error.message ) || t( 'errorOccurred', 'An error occurred. Please try again.' ) );
			}
		);
	};

	/**
	 * Read a trimmed value from the details form.
	 *
	 * @param {string} name Input name.
	 * @return {string} Trimmed value.
	 */
	BookingForm.prototype.value = function ( name ) {
		var input = this.field( name );
		return input ? input.value.trim() : '';
	};

	BookingForm.prototype.showSuccess = function ( booking ) {
		Object.keys( this.steps ).forEach(
			function ( key ) {
				this.steps[ key ].hidden = true;
			}.bind( this )
		);

		var nav = this.root.querySelector( '.appointkit-steps' );
		if ( nav ) {
			nav.hidden = true;
		}

		if ( this.successDetails && booking ) {
			empty( this.successDetails );
			var list = el( 'dl', 'appointkit-summary__list' );
			if ( booking.service_name ) {
				list.appendChild( el( 'dt', null, t( 'service', 'Service' ) ) );
				list.appendChild( el( 'dd', null, booking.service_name ) );
			}
			if ( booking.start_display ) {
				list.appendChild( el( 'dt', null, t( 'when', 'When' ) ) );
				list.appendChild( el( 'dd', null, booking.start_display ) );
			}
			this.successDetails.appendChild( list );
		}

		if ( this.success ) {
			this.success.hidden = false;
			this.success.setAttribute( 'tabindex', '-1' );
			this.success.focus();
		}
	};

	// --- Shared helpers ---

	BookingForm.prototype.setSubmitting = function ( busy ) {
		this.state.submitting = busy;
		if ( this.submitBtn ) {
			this.submitBtn.disabled = busy;
			this.submitBtn.classList.toggle( 'is-busy', busy );
		}
	};

	BookingForm.prototype.setLoading = function ( node ) {
		if ( ! node ) {
			return;
		}
		empty( node );
		node.appendChild( el( 'p', 'appointkit-loading', t( 'loading', 'Loading…' ) ) );
	};

	BookingForm.prototype.showListError = function ( node ) {
		if ( ! node ) {
			return;
		}
		empty( node );
		node.appendChild( el( 'p', 'appointkit-error', t( 'errorOccurred', 'An error occurred. Please try again.' ) ) );
	};

	/**
	 * Show an error message, optionally inside a specific step.
	 *
	 * @param {string}      message Error text.
	 * @param {HTMLElement} within  Step element to render into.
	 */
	BookingForm.prototype.showErrors = function ( message, within ) {
		var target = this.formErrors;

		if ( within ) {
			target = within.querySelector( '.appointkit-form-errors' );
			if ( ! target ) {
				target = el( 'div', 'appointkit-form-errors' );
				target.setAttribute( 'role', 'alert' );
				target.setAttribute( 'aria-live', 'polite' );
				within.appendChild( target );
			}
		}

		if ( target ) {
			target.textContent = message;
		}
	};

	BookingForm.prototype.clearErrors = function () {
		Array.prototype.forEach.call(
			this.root.querySelectorAll( '.appointkit-form-errors' ),
			function ( node ) {
				node.textContent = '';
			}
		);
		if ( this.stripeErrors ) {
			this.stripeErrors.textContent = '';
		}
	};

	/**
	 * Format a duration in minutes for display.
	 *
	 * @param {number} minutes Duration.
	 * @return {string} Human-readable duration.
	 */
	BookingForm.prototype.durationLabel = function ( minutes ) {
		minutes = parseInt( minutes, 10 ) || 0;
		if ( minutes < 60 ) {
			return minutes + ' ' + t( 'minutesShort', 'min' );
		}
		var hours = Math.floor( minutes / 60 );
		var rest  = minutes % 60;
		var label = hours + ' ' + t( 'hoursShort', 'hr' );
		return rest ? label + ' ' + rest + ' ' + t( 'minutesShort', 'min' ) : label;
	};

	/**
	 * Call the REST API.
	 *
	 * @param {string} method HTTP method.
	 * @param {string} path   Path below the API base.
	 * @param {Object} body   Optional JSON body.
	 * @return {Promise} Resolves with parsed JSON.
	 */
	BookingForm.prototype.request = function ( method, path, body ) {
		var options = {
			method: method,
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': this.nonce
			}
		};
		if ( body ) {
			options.body = JSON.stringify( body );
		}

		return window.fetch( config.apiBase + path, options ).then(
			function ( response ) {
				return response.json().then(
					function ( data ) {
						if ( ! response.ok ) {
								throw new Error( ( data && data.message ) || 'Request failed' );
						}
						return data;
					}
				);
			}
		);
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
			Array.prototype.forEach.call(
				document.querySelectorAll( '.appointkit-booking-form' ),
				function ( node ) {
					if ( ! node.getAttribute( 'data-appointkit-ready' ) ) {
						node.setAttribute( 'data-appointkit-ready', '1' );
						new BookingForm( node );
					}
				}
			);
		}
	);
} )( window, document );
