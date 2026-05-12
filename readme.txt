=== AppointKit – Appointment Booking & Scheduling ===
Contributors: arunrajiah
Tags: appointments, booking, scheduling, calendar, stripe
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete appointment booking and scheduling for WordPress. Unlimited services, staff, and bookings. Stripe payments, Google Calendar sync, and email notifications.

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
* Custom booking form fields
* Coupons & discounts
* Session packages
* Wait-list
* Multi-location
* Resources (rooms, equipment)
* Webhooks (Zapier/Make)
* And more

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

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
