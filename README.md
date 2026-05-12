# AppointKit

**AppointKit** is a free WordPress appointment booking plugin. It provides a complete booking system — services, staff, availability, payments, and email notifications — all built on WordPress core APIs with no external dependencies.

## Features

- **Services** — Define bookable services with duration, price, buffer times, and slot intervals
- **Staff** — Multiple staff with individual schedules and timezone support
- **Availability** — Automatic slot generation respecting working hours, buffers, and blocked times
- **Booking widget** — Gutenberg block and `[appointkit]` shortcode
- **Stripe payments** — PaymentIntents with 3DS/SCA support (no SDK required)
- **Google Calendar** — Free/busy sync to block unavailable times
- **Email notifications** — Confirmation, cancellation, and reminder emails for customers and staff
- **REST API** — Full JSON API for headless and custom integrations

## Requirements

- WordPress 6.3+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+

## Installation

1. Upload the `appointkit` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins → Installed Plugins**
3. Go to **AppointKit → Settings** to configure Stripe keys and email templates
4. Add services under **AppointKit → Services**
5. Embed the booking form with `[appointkit]` or the AppointKit Gutenberg block

## Extending with AppointKit Pro

AppointKit is designed to be extended via hooks. The Pro add-on adds:

- SMS notifications (Twilio)
- Recurring bookings
- Group bookings
- Zoom / Google Meet auto-links
- WooCommerce integration
- Custom booking form fields
- Coupon codes and discounts
- Session packages
- Memberships
- Multi-location support
- Resource management
- Waitlist
- Advanced availability (blackout dates)
- Webhooks
- WPML / Polylang integration

Available at [hub.arunrajiah.com](https://hub.arunrajiah.com).

## License

GPL v2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
