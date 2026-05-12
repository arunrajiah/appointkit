# Changelog

All notable changes to AppointKit will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-05-12

### Added
- Initial release of AppointKit
- Service management with custom duration, price, buffer times, and slot intervals
- Staff profiles with per-staff availability rules and timezone support
- Availability calculator — generates bookable slots, respects working hours, buffers, and blocked times
- Booking creation with Stripe PaymentIntents (including 3DS / SCA support)
- Google Calendar free/busy sync to block staff unavailability
- Customer and staff confirmation/cancellation/reminder emails
- REST API (`/wp-json/appointkit/v1/`) for services, staff, slots, and bookings
- UTC-only database storage with site and customer timezone display
- WP-Cron reminders (hourly) and pending booking cleanup (twice daily)
- Admin dashboard with calendar view and bookings list
- Gutenberg block and `[appointkit]` shortcode for embedding the booking widget
- Extensibility hooks for Pro add-ons (filters, actions, hook registry)
- PHPUnit unit tests for availability calculator
- Playwright E2E tests for the full booking flow
- GitHub Actions CI: plugin-check, lint, PHPUnit, Playwright, deploy-to-wp-org
