# AppointKit

**Free appointment booking plugin for WordPress.** Services, staff, availability rules, Stripe payments, Google Calendar sync, and email notifications — all in one plugin with no SaaS fees and no external dependencies.

[![WordPress tested up to](https://img.shields.io/badge/WordPress-6.7-blue.svg)](https://wordpress.org/plugins/appointkit/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://www.php.net/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## What it does

AppointKit adds a fully-featured booking system to any WordPress site. Your customers pick a service, choose a staff member, pick an available time slot, pay via Stripe, and receive a confirmation email — all without leaving your site.

**Typical use cases:** hair salons, physiotherapy clinics, coaching sessions, photography studios, legal consultations, tutoring, fitness classes.

---

## Quick demo

```
[appointkit_form]                               → full booking form (all services)
[appointkit_form service_id="3"]                → pre-select a specific service
[appointkit_form staff_id="5"]                  → pre-select a specific staff member
[appointkit_form service_id="3" staff_id="5"]   → pre-select both
```

---

## Features

### Booking
- Multi-step booking flow: service → staff → date → time → customer details → payment
- Real-time slot availability — only shows times that are actually open
- Buffer times before and after appointments (no back-to-back double bookings)
- Configurable slot intervals (15 min, 30 min, 1 hr, etc.)
- Timezone-aware: all times stored in UTC, displayed in the customer's local timezone
- Cancellation with configurable notice period

### Staff & Services
- Unlimited services with individual duration, price, and color
- Unlimited staff with individual working hours and day-off rules
- Per-staff timezone support (e.g. remote staff in different countries)
- "Any staff" mode — let the system auto-assign the next available staff member
- Date overrides — set one-off availability changes without touching the regular schedule

### Payments
- Stripe PaymentIntents (SCA / 3DS 2.0 compliant)
- No PHP SDK required — communicates with Stripe via `wp_remote_post()`
- Pending → Confirmed flow with automatic status updates
- Refunds issued directly from the WP admin bookings list

### Calendar & Notifications
- Google Calendar free/busy sync — blocks times when a staff member is busy in Google Calendar
- Confirmation, cancellation, and reminder emails for both customers and staff
- Customisable email templates in **AppointKit → Settings → Emails**
- WP-Cron reminders sent 24 hours before each appointment

### Embedding
- **Gutenberg block** — drag it into any page, configure via block sidebar
- **Shortcode** — `[appointkit_form]` with optional `service_id` and `staff_id` attributes
- **REST API** — full JSON API for headless setups

---

## Installation

### From WordPress.org (recommended)
1. Go to **Plugins → Add New** in your WordPress admin
2. Search for **AppointKit**
3. Click **Install Now** then **Activate**

### Manual install
1. Download the latest ZIP from the [Releases](https://github.com/arunrajiah/appointkit/releases) page
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP and click **Install Now → Activate**

---

## Getting started

### Step 1 — Configure Stripe

1. Go to **AppointKit → Settings → Payments**
2. Enter your **Stripe Publishable Key** and **Secret Key**
   - Test keys: `pk_test_…` / `sk_test_…` (from [dashboard.stripe.com/test/apikeys](https://dashboard.stripe.com/test/apikeys))
   - Live keys: `pk_live_…` / `sk_live_…` (from [dashboard.stripe.com/apikeys](https://dashboard.stripe.com/apikeys))
3. Set up the Stripe webhook (see below)

**Stripe webhook setup:**
1. In Stripe Dashboard → Developers → Webhooks → Add endpoint
2. URL: `https://yoursite.com/wp-json/appointkit/v1/stripe-webhook`
3. Events to listen for: `payment_intent.succeeded`, `payment_intent.payment_failed`
4. Copy the **Signing Secret** and paste it into **AppointKit → Settings → Stripe Webhook Secret**

### Step 2 — Add your staff

1. Go to **AppointKit → Staff → Add New**
2. Fill in name, email, and timezone
3. Set weekly working hours (e.g. Mon–Fri 9:00–17:00)
4. Optionally connect their Google Calendar (see Google Calendar setup below)

### Step 3 — Add your services

1. Go to **AppointKit → Services → Add New**
2. Set the service name, duration, price, and buffer time
3. Select which staff members can provide this service
4. Choose the slot interval (how often booking slots appear)

### Step 4 — Embed the booking form

Add the Gutenberg block (**AppointKit Booking Form**) to any page, or use the shortcode:

```
[appointkit_form]
```

That's it — the form is live.

---

## Google Calendar sync (optional)

Connecting a staff member's Google Calendar blocks their busy times automatically — no manual entering of holidays or blocked dates needed.

1. Go to **AppointKit → Settings → Integrations → Google Calendar**
2. Create a Google Cloud project at [console.cloud.google.com](https://console.cloud.google.com)
3. Enable the **Google Calendar API**
4. Create OAuth 2.0 credentials (Web application)
   - Authorised redirect URI: `https://yoursite.com/wp-json/appointkit/v1/google-calendar/callback`
5. Copy the Client ID and Secret into the settings
6. Click **Connect Google Calendar** for each staff member and authorise access

AppointKit only reads free/busy data — it never creates or modifies events in Google Calendar.

---

## REST API

All endpoints are under `/wp-json/appointkit/v1/`.

### Get services
```http
GET /wp-json/appointkit/v1/services
```
```json
[
  { "id": 1, "name": "Haircut", "duration": 30, "price": 2500, "currency": "usd" },
  { "id": 2, "name": "Colour", "duration": 90, "price": 7500, "currency": "usd" }
]
```

### Get available slots
```http
GET /wp-json/appointkit/v1/slots?service_id=1&date=2026-06-15
```
```json
[
  { "start_utc": "2026-06-15 08:00:00", "end_utc": "2026-06-15 08:30:00", "staff_id": 3, "staff_name": "Sarah" },
  { "start_utc": "2026-06-15 08:30:00", "end_utc": "2026-06-15 09:00:00", "staff_id": 3, "staff_name": "Sarah" }
]
```

### Create a booking
```http
POST /wp-json/appointkit/v1/bookings
Content-Type: application/json

{
  "service_id": 1,
  "staff_id": 3,
  "start_utc": "2026-06-15 08:00:00",
  "end_utc": "2026-06-15 08:30:00",
  "customer_name": "Jane Smith",
  "customer_email": "jane@example.com",
  "customer_phone": "+447700900123",
  "payment_method_id": "pm_1234abcd"
}
```
```json
{
  "id": 42,
  "status": "confirmed",
  "payment_status": "paid",
  "start_local": "15 Jun 2026, 9:00 AM BST"
}
```

### Cancel a booking
```http
DELETE /wp-json/appointkit/v1/bookings/42
```
Requires either admin authentication or the booking owner's email passed as `customer_email` query param.

---

## Developer hooks

AppointKit is built for extensibility. Every major event fires a documented action or filter.

### Actions
```php
// Fired when a new booking is created (status: pending)
do_action( 'appointkit_booking_created', $booking );

// Fired when a booking is confirmed (payment succeeded)
do_action( 'appointkit_booking_confirmed', $booking );

// Fired when a booking is cancelled
do_action( 'appointkit_booking_cancelled', $booking );
```

### Filters
```php
// Add or remove available time slots
add_filter( 'appointkit_available_slots', function( $slots, $service_id, $staff_id, $date ) {
    // Remove the lunch hour
    return array_filter( $slots, fn($s) => substr($s['start_utc'], 11, 5) !== '12:00' );
}, 10, 4 );

// Add extra booking form fields
add_filter( 'appointkit_booking_form_fields', function( $fields ) {
    $fields[] = [ 'name' => 'notes', 'label' => 'Special requests', 'type' => 'textarea', 'required' => false ];
    return $fields;
} );

// Override buffer time per service
add_filter( 'appointkit_buffer_minutes', function( $buffer, $service_id ) {
    return $service_id === 5 ? 60 : $buffer; // 1-hour buffer for service 5
}, 10, 2 );

// Add a custom payment gateway
add_filter( 'appointkit_payment_gateways', function( $gateways ) {
    $gateways['my_gateway'] = [ 'label' => 'My Gateway', 'class' => 'My_Gateway_Class' ];
    return $gateways;
} );
```

Full hook reference: [`includes/extensibility/class-hook-registry.php`](includes/extensibility/class-hook-registry.php)

---

## Frequently asked questions

**Does it work without Stripe?**
Yes — if no Stripe keys are configured, the booking flow skips payment and confirms the booking directly. Useful for free services or when you handle payment offline.

**Can customers book without creating an account?**
Yes — guest booking is supported by default. No WordPress account is required.

**How does "any staff" mode work?**
If `staff_id` is omitted from a booking request (or the customer picks "Any" in the form), AppointKit finds the first available staff member for the requested slot.

**Does it support multiple locations?**
The free plugin has a single location. [AppointKit Pro](https://hub.arunrajiah.com/products/appointkit-pro) adds multi-location support with per-location staff and service scoping.

**Can I send SMS reminders?**
SMS notifications via Twilio are available in [AppointKit Pro](https://hub.arunrajiah.com/products/appointkit-pro).

**Is the plugin GDPR-friendly?**
Customer data is stored only in your own WordPress database. No data is sent to third parties except Stripe (for payments) and Google (if you connect Google Calendar). Both are processor relationships you control.

---

## Upgrading to Pro

[AppointKit Pro](https://hub.arunrajiah.com/products/appointkit-pro) extends the free plugin with 15 modules:

| Module | What it adds |
|--------|-------------|
| SMS / Twilio | Text reminders to customers and staff |
| Recurring bookings | Weekly PT sessions, monthly check-ins, etc. |
| Group bookings | Yoga classes, workshops — multiple people per slot |
| Zoom / Google Meet | Auto-generate meeting links on confirmation |
| WooCommerce | Sell bookings as WooCommerce products |
| Custom forms | Extra fields on the booking form per service |
| Coupons | Discount codes with expiry and usage caps |
| Packages | Sell 10 sessions for the price of 8 |
| Memberships | Monthly subscribers get a % discount + priority slots |
| Multi-location | Branches, clinics, studios — scoped by staff and service |
| Resources | Book a room or piece of equipment alongside a staff member |
| Waitlist | Queue customers when a slot is full |
| Blackout dates | Close the whole practice for bank holidays |
| Webhooks | Push booking events to Zapier, Make, or your own API |
| Multilingual | WPML and Polylang string translation |

---

## Contributing

Pull requests are welcome! Please:

1. Fork the repo and create a branch: `git checkout -b feature/my-feature`
2. Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
3. Run PHPCS before submitting: `composer lint`
4. Run the test suite: `composer test`
5. Open a PR against `main` with a clear description of what changed and why

**Reporting bugs:** open a [GitHub issue](https://github.com/arunrajiah/appointkit/issues) with steps to reproduce, WordPress version, PHP version, and any relevant error messages.

---

## License

GPL v2 or later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)
