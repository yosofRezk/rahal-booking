# Rahal Booking System

A lightweight, standalone WordPress booking engine designed for travel agencies, tour operators, and activity providers.

Rahal Booking allows you to attach a full-featured booking workflow to any existing Custom Post Type (CPT) without restructuring your site content, relying on WooCommerce, or requiring commercial plugins like Advanced Custom Fields (ACF).

---

## 1. Architectural Philosophy and Features

* **Zero Overbooking Guarantee**: Prevents race conditions using dedicated database mutual exclusion locks via MySQL `GET_LOCK()`. Even under concurrent checkout attempts, seat availability remains strictly accurate.
* **Content Agnostic**: Seamlessly binds to any existing custom post type and custom fields (Post Meta).
* **Multi-Layered Security**: Built-in honeypot trap for spam prevention, IP-based rate limiting (maximum 5 attempts per hour), and strict double-click debouncing.
* **Server-Authoritative Pricing**: Calculates subtotals, currencies, and guest pricing on the server side to eliminate client-side tampering.
* **Obfuscated Booking References**: Generates human-friendly yet non-enumerable reference identifiers formatted as `RHL-YYYY-NNNNN-XXXX`.
* **Administrative Controls**: Comprehensive booking management using WordPress native `WP_List_Table` standards, status workflows (Pending, Confirmed, Cancelled, Rejected, Expired, Completed), and manual booking creation for front-desk and phone operations.
* **Automated Email Notifications**: Dispatches responsive HTML emails to customers (Booking Received with payment instructions and holding deadlines) and site administrators (New Booking Alerts with direct admin action links).
* **Theme Adaptive Interface**: Frontend forms and modal windows automatically inherit typography and color variables from your active theme or page builder (Gutenberg, Elementor, Tailwind, etc.).

---

## 2. Prerequisites: Preparing Your Tours and Custom Fields

Before using the booking system, your WordPress site needs content to represent tours or trips.

### What is a Custom Post Type (CPT)?
In WordPress, posts and pages are default post types. A Custom Post Type is a dedicated content structure tailored for specific data, such as `tours`, `trips`, or `packages`. You can register a CPT through your theme's `functions.php` or using free plugins like Custom Post Type UI (CPT UI).

### Required Custom Fields (Post Meta)
Rahal Booking reads tour properties directly from WordPress post metadata. You need three pieces of data associated with each tour:

1. **Total Seats Field (Required)**: Numeric value representing capacity (e.g., `20`).
2. **Price per Guest Field (Required)**: Numeric price per person (e.g., `250` or `250.00`).
3. **Tour Date Field (Optional)**: Specific event date formatted as `YYYY-MM-DD` or `YYYYMMDD` (ACF format). If omitted, the tour is treated as an open-date or flexible package.

### How to Create These Custom Fields
You can add these meta fields to your tour CPT using:
* **Native WordPress Custom Fields**: Available directly under Screen Options in the classic post editor.
* **Advanced Custom Fields (ACF / ACF Free)**: Create a Field Group assigned to your Tour CPT with fields named `total_seats`, `tour_price`, and `tour_date`.
* **Meta Box / Pods / Custom Code**: Any tool storing standard values inside the `wp_postmeta` table.

---

## 3. Installation and Initial Configuration

1. Copy or upload the `rahal` plugin folder into your WordPress installation directory under `/wp-content/plugins/`.
2. Navigate to **Plugins** in your WordPress dashboard and click **Activate** under **Rahal Booking**.
3. Open the plugin administration menu: **Rahal Bookings > Settings**.
4. Configure your mapping parameters:
   * **Tour Post Type**: Enter the slug of your custom post type (e.g., `tour` or `trip`).
   * **Seats Meta Field**: Enter the custom field key storing total seats (e.g., `total_seats`).
   * **Price Meta Field**: Enter the custom field key storing the price per person (e.g., `tour_price`).
   * **Date Meta Field**: Enter the custom field key storing the tour date (e.g., `tour_date`).
   * **Currency**: Select your business currency code (e.g., `USD`, `EUR`, `EGP`).
   * **Admin Notification Email**: Enter the specific inbox email address where administrators should receive new booking alerts (defaults to the WordPress administration email if left blank).
   * **Payment Instructions**: Add instructions for manual payment methods (Pay at Branch and Bank Transfer) along with holding deadlines in hours.
5. Save settings. The built-in **Connection Check** card will immediately verify whether your field keys match published tours.

---

## 4. Shortcode Reference and Use Cases

Rahal Booking provides five shortcodes. Attributes are optional when placed directly on a single tour page, as the plugin automatically detects the current post ID.

### 1. `[rahl_booking_form]`
Renders a complete, responsive booking form directly embedded in the page flow.

* **Attributes**:
  * `tour_id` (optional): Numeric ID of the tour. Defaults to the current page.
* **Example**:
  ```text
  [rahl_booking_form]
  [rahl_booking_form tour_id="85"]
  ```
* **Use Case**:
  Ideal for dedicated single tour pages where visitors read full itineraries and descriptions, then complete their reservation at the bottom of the page or in a sticky sidebar.

---

### 2. `[rahl_booking_button]`
Outputs an interactive button that triggers an elegant popup modal window containing the reservation form when clicked.

* **Attributes**:
  * `tour_id` (optional): Numeric ID of the tour.
  * `text` (optional): Custom label displayed on the button. Default is "Book Now".
  * `class` (optional): Additional CSS class for styling.
* **Example**:
  ```text
  [rahl_booking_button tour_id="85" text="Reserve Your Spot"]
  ```
* **Use Case**:
  * **Archive and Listing Cards**: Embed directly into archive loops, comparison tables, or Elementor card grids. Visitors can book any tour immediately without leaving the main catalog.
  * **Compact Header Banners**: Place a lightweight call-to-action button in hero sections or promotional banners without cluttering the initial viewport with full form fields.
  * When seats reach zero, the button automatically disables and reflects a "Fully Booked" label.

---

### 3. `[rahl_available_seats]`
Displays the live count of remaining unreserved seats in real time.

* **Attributes**:
  * `tour_id` (optional): Numeric ID of the tour.
  * `date` (optional): Specific date formatted as `YYYY-MM-DD`.
* **Example**:
  ```text
  Only [rahl_available_seats tour_id="85"] seats left!
  ```
* **Use Case**:
  Creates authentic urgency on landing pages and sales copy, reassuring customers about real-time availability and boosting conversion rates.

---

### 4. `[rahl_total_seats]`
Outputs the maximum seat capacity of the specified tour.

* **Attributes**:
  * `tour_id` (optional): Numeric ID of the tour.
* **Example**:
  ```text
  Capacity: [rahl_total_seats tour_id="85"] passengers
  ```
* **Use Case**:
  Useful in tour specifications, info boxes, and comparison lists where travelers need to know group size limitations before booking.

---

### 5. `[rahl_booked_seats]`
Outputs the current number of confirmed and active pending seats reserved for a tour.

* **Attributes**:
  * `tour_id` (optional): Numeric ID of the tour.
  * `date` (optional): Specific date formatted as `YYYY-MM-DD`.
* **Example**:
  ```text
  Joined so far: [rahl_booked_seats tour_id="85"] travelers
  ```
* **Use Case**:
  Acts as social proof, showing prospective customers that other people have already joined the excursion.

---

## 5. Email Notification System and Setup

Rahal Booking features a fully automated dual-notification system triggered immediately upon the submission of every booking.

### How Admin Notifications Work
Whenever a customer books a tour (or an administrator creates a manual booking), an instant email alert is dispatched to the store administrator or reservations team.

* **Configuring the Recipient Address**:
  1. Navigate to **Rahal Bookings > Settings**.
  2. Under the **Booking Rules** section, locate the **Admin Notification Email** field.
  3. Enter the specific email address where reservation alerts should be delivered (for example, `bookings@yourcompany.com`).
  4. If this field is left empty, Rahal Booking automatically falls back to your WordPress general administration email address configured under **Settings > General > Administration Email Address**.
* **What the Admin Notification Includes**:
  * Booking reference code (e.g., `RHL-2026-00001-A7K2`).
  * Customer name, phone number, and email address.
  * Tour title, selected travel date, and number of guests.
  * Total price calculated by the server and chosen payment method.
  * Direct action link that opens the exact booking inside the WordPress admin dashboard for instant review and status updates.

### How Customer Confirmations Work
Customers receive an immediate HTML confirmation email sent directly to the email address they entered into the booking form (`customer_email`).

* **Customer Email Contents**:
  * Professional greeting with full reservation summary.
  * Unique booking reference number to use during communications.
  * Clear payment instructions tailored to their selected payment method:
    * **Bank Transfer**: Displays your bank account name, IBAN/account number, and bank details as defined in your settings.
    * **Pay at Branch**: Displays your physical office address and business hours.
  * Holding period deadline notice (e.g., "Your seats are held for 48 hours. Please complete payment before the expiration deadline to ensure your reservation is confirmed").

### Ensuring Reliable Email Deliverability (SMTP Setup)
By default, WordPress attempts to send emails using PHP `mail()` via the server hosting your website. On most shared hosts and VPS configurations, PHP `mail()` either fails, gets blocked by spam firewalls, or arrives directly into the recipient's Junk folder.

To guarantee that both you and your travelers receive emails reliably:
1. Install an SMTP plugin such as **WP Mail SMTP**, **FluentSMTP**, or **Post SMTP**.
2. Configure the plugin to connect with a reputable transactional email service or mail provider:
   * Google Workspace / Gmail
   * Microsoft 365
   * SendGrid
   * Brevo (formerly Sendinblue)
   * Mailgun
   * Amazon SES
   * Authenticated SMTP from your hosting provider
3. Send a test email from the SMTP plugin settings to verify proper SPF, DKIM, and DMARC alignment.

### Developer Hooks and Extensibility
Developers can customize email subjects and headers using standard WordPress filter hooks:
* `rahl_email_customer_subject`: Filter customer confirmation subject line.
* `rahl_email_customer_headers`: Filter customer email headers (e.g., adding custom Reply-To or BCC).
* `rahl_email_admin_subject`: Filter administrator notification subject line.
* `rahl_email_admin_headers`: Filter administrator email headers.

---

## 6. Administrative Workflow

1. **Viewing Orders**: Access **Rahal Bookings > All Bookings** to inspect customer data, booking references, dates, guest counts, and color-coded status badges.
2. **Executing Actions**: Hover over any reference number to perform status updates:
   * **Confirm**: Validates a pending booking and clears expiry timers.
   * **Confirm & Pay**: Confirms the booking and sets payment status to Paid simultaneously (optimized for front-desk cash collections).
   * **Cancel**: Revokes reservations and instantly returns held seats to the public availability pool.
   * **Mark as Paid**: Updates manual transactions to Paid without modifying booking status.
3. **Manual Front-Desk Bookings**: Access **Rahal Bookings > Add New** to record walk-in or telephone reservations. Staff can select published tours, enter customer details, choose initial states, and either proceed to create additional entries or jump straight to the booking list with row highlighting.

---

## 7. Technical Specifications

* **Minimum PHP Version**: 7.4 (Tested up to PHP 8.2).
* **Minimum WordPress Version**: 6.0 (Tested up to WordPress 6.7).
* **Database Tables**: Dedicated custom table `{prefix}rahl_bookings`.
* **Uninstall Cleanliness**: If configured in settings, database tables and stored options can be purged cleanly upon uninstallation via `uninstall.php`.
* **Internationalization**: Fully localized with gettext functions ready for translation into any language.
