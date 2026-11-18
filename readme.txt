=== AppointKit ===
Contributors: arunrajiah
Tags: appointments, booking, scheduling, calendar, stripe
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Appointment booking and scheduling for WordPress. Unlimited services, staff and bookings, with Stripe payments and Google Calendar sync.

== Description ==

**AppointKit** is a modern appointment booking plugin built for service businesses: clinics, salons, coaches, tutors, gyms, and consultants.

**Free features:**

* Unlimited services and staff
* Beautiful multi-step booking form (shortcode + Gutenberg block)
* Stripe one-off payment support
* Email notifications: confirmation, cancellation, reminder (24h before)
* Google Calendar 1-way sync (reads busy times to avoid double-bookings)
* iCal feed per staff member
* Admin calendar view (week/day)
* Flexible availability rules (weekday schedules, date exceptions)
* Full timezone support — everything stored in UTC, displayed in site or customer timezone
* REST API for headless/custom integrations
* Customer "My Bookings" page — view and cancel upcoming appointments
* WordPress.org compliant — GPL v2, no ads, no phoning home

**AppointKit Pro adds:**

* SMS notifications (Twilio)
* Recurring appointments
* Group bookings
* Zoom/Google Meet auto-links
* WooCommerce integration
* Visual custom field builder (the free plugin already provides the `appointkit_booking_form_fields` filter and `appointkit_booking_form_extra_fields` action for developers)
* Coupons & discounts
* Session packages
* Wait-list
* Multi-location
* Resources (rooms, equipment)
* Webhooks (Zapier/Make)
* And more

== External services ==

AppointKit stores all booking data in your own WordPress database and does not phone home. Two optional integrations contact external services, and only after you enable them in **AppointKit → Settings**:

**Stripe** (card payments). Used to charge customers for paid services.

* Stripe.js is loaded in the visitor's browser from `https://js.stripe.com/v3/` only on pages that show the booking form and only when Stripe keys are saved. Stripe requires it to be served from their domain. Card details are entered into Stripe's own fields and never reach your server.
* When a customer submits a paid booking, your server sends the amount, currency, the Stripe payment method ID and the booking reference to `https://api.stripe.com` to create the charge.
* Service by Stripe, Inc.: [Terms of Service](https://stripe.com/legal) and [Privacy Policy](https://stripe.com/privacy).

**Google Calendar** (availability sync). Used to hide time slots that are already busy in a staff member's Google Calendar.

* When a staff member clicks "Connect Google Calendar", they are sent to Google's OAuth consent screen and your server exchanges the returned authorization code for access and refresh tokens with `https://oauth2.googleapis.com`. Tokens are stored on the staff record in your database.
* Each time the booking form computes available slots for that staff member, your server asks `https://www.googleapis.com/calendar/v3/freeBusy` for busy periods in the requested date range. Only the date range is sent. No booking details are written to Google in the free plugin.
* Service by Google LLC: [Google APIs Terms of Service](https://developers.google.com/terms) and [Privacy Policy](https://policies.google.com/privacy).

If you leave both integrations unconfigured, no request is made to any third party.

== Installation ==

1. Upload the `appointkit` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu.
3. Go to **AppointKit → Services** and add your first service.
4. Go to **AppointKit → Staff** and add your staff members.
5. Set their availability under **AppointKit → Availability**.
6. Add the `[appointkit_form]` shortcode to any page, or use the **AppointKit Booking Form** block.

== Frequently Asked Questions ==

= Does this work without Stripe? =

Yes. If you leave Stripe unconfigured, appointments are confirmed without requiring payment. Add Stripe keys in **AppointKit → Settings** whenever you're ready.

= Can customers book from any page? =

Yes. Use the `[appointkit_form]` shortcode or the Gutenberg block. You can pre-select a service or staff member with `[appointkit_form service_id="1"]`.

= How do I add Google Calendar sync? =

Go to **AppointKit → Staff**, edit a staff member, and click **Connect Google Calendar**. You'll need to set up a Google OAuth app first — see the plugin documentation.

= Is my data stored locally? =

Yes. All booking data is stored in your WordPress database. No data is sent to external servers (except Stripe for payments and Google for calendar sync, when you explicitly enable those).

== Screenshots ==

1. Booking form — service selection step
2. Booking form — time slot selection
3. Admin bookings list
4. Admin calendar view
5. Services management page

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
