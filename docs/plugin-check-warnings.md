# Documented Plugin Check Warnings

These warnings are intentional and have been audited. Plugin Check will still
flag them; the review team can read this file to understand why we keep them.

## `direct_db_query` in repositories

AppointKit stores booking data in five custom tables:

- `wp_appointkit_services`
- `wp_appointkit_staff`
- `wp_appointkit_bookings`
- `wp_appointkit_availability_rules`
- `wp_appointkit_locations`

Using CPTs for this data would be too slow (bookings + availability requires
efficient JOINs and range queries). All queries in the `includes/repositories/`
directory use `$wpdb->prepare()` with typed placeholders (`%d`, `%s`, `%f`).
Each repository method was reviewed for SQL injection risk on 2026-05-12 by
Arun Rajiah.

## `WordPress.DB.DirectDatabaseQuery.NoCaching`

The repositories deliberately skip the object cache for write operations and
for queries that are uniquely keyed per-request (e.g., slot generation per
staff+date combination). Caching availability data aggressively would cause
double-bookings. Transients with short TTLs are used selectively in the
availability calculator where appropriate.
