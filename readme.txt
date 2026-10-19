=== Rahal Booking ===
Contributors: rahalteam
Tags: booking, travel, tours, reservation, appointments
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A standalone booking engine for WordPress travel sites. Attach booking to any existing tour post type without rebuilding your content.

== Description ==

Rahal Booking is an independent booking engine plugin. It attaches a complete booking system to your existing tours/trips — whatever post type you use — without forcing you to migrate your content.

**Key Features (v0.1)**

* Links to any custom post type (tours, trips, packages, etc.)
* Reads seats, price, and date from any custom field
* Real-time seat availability calculation
* Double-booking prevention with MySQL locks
* Reference number generation (RHL-2026-00001)
* Honeypot spam protection
* Rate limiting (5 attempts/hour per IP)
* Admin booking list with Confirm / Reject / Cancel / Mark as Paid
* Email notifications for customer and admin
* Manual payment methods (Branch / Transfer)

**Upcoming Features**

* Automatic booking expiry with Cron (v0.2)
* Group discounts and early booking discounts (v0.3)
* Online payment gateways: Paymob, Stripe (v1.0)
* Gutenberg block (v0.4)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/rahal/`.
2. Activate through the **Plugins** screen.
3. Go to **Rahal Bookings > Settings** and configure your tour post type and field names.
4. Add `[tbs_booking_form]` shortcode to your tour page template.

== Frequently Asked Questions ==

= Does this plugin require ACF? =

No. It reads from standard WordPress post meta, so it works with ACF, Meta Box, or any custom fields plugin.

= Can it work with my existing tours? =

Yes. Just tell it the post type name and the meta field names for seats and price — done.

= What happens when a booking expires? =

In v0.1, seats are freed automatically via the database query (no cron needed). The cron and expiry emails come in v0.2.

== Changelog ==

= 0.1.0 =
* Initial release.
* Core booking engine with double-booking lock.
* Manual payment methods (branch, transfer).
* Admin bookings list.
* Email notifications.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
