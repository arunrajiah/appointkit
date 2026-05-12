<?php
/**
 * Booking form template.
 *
 * Variables available: $atts (array with service_id, staff_id).
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="appointkit-booking-form"
	data-service-id="<?php echo absint( $atts['service_id'] ); ?>"
	data-staff-id="<?php echo absint( $atts['staff_id'] ); ?>"
	data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">

	<div class="appointkit-steps">
		<ol class="appointkit-step-nav" aria-label="<?php esc_attr_e( 'Booking steps', 'appointkit' ); ?>">
			<li class="appointkit-step-nav__item is-active" data-step="1"><?php esc_html_e( 'Service', 'appointkit' ); ?></li>
			<li class="appointkit-step-nav__item" data-step="2"><?php esc_html_e( 'Staff', 'appointkit' ); ?></li>
			<li class="appointkit-step-nav__item" data-step="3"><?php esc_html_e( 'Date', 'appointkit' ); ?></li>
			<li class="appointkit-step-nav__item" data-step="4"><?php esc_html_e( 'Time', 'appointkit' ); ?></li>
			<li class="appointkit-step-nav__item" data-step="5"><?php esc_html_e( 'Details', 'appointkit' ); ?></li>
			<li class="appointkit-step-nav__item" data-step="6"><?php esc_html_e( 'Confirm', 'appointkit' ); ?></li>
		</ol>
	</div>

	<!-- Step 1: Service selection -->
	<div class="appointkit-step" data-step="1">
		<h3><?php esc_html_e( 'Select a Service', 'appointkit' ); ?></h3>
		<div class="appointkit-service-list" role="list" aria-label="<?php esc_attr_e( 'Available services', 'appointkit' ); ?>">
			<p class="appointkit-loading"><?php esc_html_e( 'Loading services…', 'appointkit' ); ?></p>
		</div>
	</div>

	<!-- Step 2: Staff selection -->
	<div class="appointkit-step" data-step="2" hidden>
		<h3><?php esc_html_e( 'Select a Staff Member', 'appointkit' ); ?></h3>
		<div class="appointkit-staff-list" role="list" aria-label="<?php esc_attr_e( 'Available staff', 'appointkit' ); ?>">
			<p class="appointkit-loading"><?php esc_html_e( 'Loading staff…', 'appointkit' ); ?></p>
		</div>
	</div>

	<!-- Step 3: Date selection -->
	<div class="appointkit-step" data-step="3" hidden>
		<h3><?php esc_html_e( 'Select a Date', 'appointkit' ); ?></h3>
		<div class="appointkit-datepicker-container">
			<input type="date" class="appointkit-datepicker"
				aria-label="<?php esc_attr_e( 'Select appointment date', 'appointkit' ); ?>"
				min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
		</div>
	</div>

	<!-- Step 4: Time slot selection -->
	<div class="appointkit-step" data-step="4" hidden>
		<h3><?php esc_html_e( 'Select a Time', 'appointkit' ); ?></h3>
		<div class="appointkit-slots" role="list" aria-label="<?php esc_attr_e( 'Available time slots', 'appointkit' ); ?>">
			<p class="appointkit-loading"><?php esc_html_e( 'Loading available times…', 'appointkit' ); ?></p>
		</div>
		<p class="appointkit-note" style="font-size:12px;color:#888;">
			<?php esc_html_e( 'Times shown in your local timezone.', 'appointkit' ); ?>
		</p>
	</div>

	<!-- Step 5: Customer details -->
	<div class="appointkit-step" data-step="5" hidden>
		<h3><?php esc_html_e( 'Your Details', 'appointkit' ); ?></h3>
		<form class="appointkit-details-form" novalidate>
			<div class="appointkit-field">
				<label for="appointkit-name"><?php esc_html_e( 'Full Name', 'appointkit' ); ?> <span aria-hidden="true">*</span></label>
				<input type="text" id="appointkit-name" name="customer_name" required
					autocomplete="name"
					aria-required="true">
			</div>
			<div class="appointkit-field">
				<label for="appointkit-email"><?php esc_html_e( 'Email Address', 'appointkit' ); ?> <span aria-hidden="true">*</span></label>
				<input type="email" id="appointkit-email" name="customer_email" required
					autocomplete="email"
					aria-required="true">
			</div>
			<div class="appointkit-field">
				<label for="appointkit-phone"><?php esc_html_e( 'Phone Number', 'appointkit' ); ?></label>
				<input type="tel" id="appointkit-phone" name="customer_phone"
					autocomplete="tel">
			</div>
			<div class="appointkit-field">
				<label for="appointkit-notes"><?php esc_html_e( 'Notes (optional)', 'appointkit' ); ?></label>
				<textarea id="appointkit-notes" name="notes" rows="3"></textarea>
			</div>
			<?php do_action( 'appointkit_booking_form_extra_fields' ); ?>
		</form>
	</div>

	<!-- Step 6: Confirmation + Payment -->
	<div class="appointkit-step" data-step="6" hidden>
		<h3><?php esc_html_e( 'Confirm Your Booking', 'appointkit' ); ?></h3>
		<div class="appointkit-summary"></div>

		<?php if ( appointkit_stripe_is_configured() ) : ?>
		<div class="appointkit-payment" id="appointkit-payment-section">
			<h4><?php esc_html_e( 'Payment', 'appointkit' ); ?></h4>
			<div id="appointkit-stripe-element" aria-label="<?php esc_attr_e( 'Credit card field', 'appointkit' ); ?>"></div>
			<div id="appointkit-stripe-errors" role="alert" aria-live="polite"></div>
		</div>
		<?php endif; ?>

		<div class="appointkit-actions">
			<button type="button" class="appointkit-btn appointkit-btn--back"><?php esc_html_e( '← Back', 'appointkit' ); ?></button>
			<button type="button" class="appointkit-btn appointkit-btn--primary" id="appointkit-submit">
				<?php if ( appointkit_stripe_is_configured() ) : ?>
					<?php esc_html_e( 'Pay & Confirm', 'appointkit' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Confirm Booking', 'appointkit' ); ?>
				<?php endif; ?>
			</button>
		</div>
		<div class="appointkit-form-errors" role="alert" aria-live="polite"></div>
	</div>

	<!-- Success state -->
	<div class="appointkit-success" hidden>
		<div class="appointkit-success__icon" aria-hidden="true">✓</div>
		<h3><?php esc_html_e( 'Booking Confirmed!', 'appointkit' ); ?></h3>
		<p><?php esc_html_e( 'A confirmation email has been sent to you.', 'appointkit' ); ?></p>
		<div class="appointkit-success__details"></div>
	</div>

</div>
