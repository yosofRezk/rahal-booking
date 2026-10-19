# PROGRESS — Rahal Booking Plugin

## Steps

- [x] Step 0  Phase 0 setup (structure + docs + test data)
- [x] Step 1  Skeleton (plugin file + class-plugin.php + functions.php)
- [x] Step 2  Installer (table + capability)
- [x] Step 3  Settings
- [x] Step 4  Tour reader
- [x] Step 5  Repository + Availability + seat shortcodes
- [ ] Step 6  Pricing
- [ ] Step 7  Booking Manager
- [ ] Step 8  Booking form
- [ ] Step 9  Admin bookings
- [ ] Step 10 Emails
- [ ] Step 11 QA
- [ ] Step 12 Release

## Current step

**Step 5 — Repository + Availability + seat shortcodes** — في انتظار اختبارك.

## Decisions / deviations from SPEC

| التاريخ | القرار |
| --- | --- |
| 19 أكتوبر 2026 | اسم البلاجن: **Rahal** — اسم النشر قد يتغير قبل v0.4 |
| 19 أكتوبر 2026 | Prefix الدوال والـ options: `rahl_` — الكلاسات: `RAHL_` — Text Domain: `rahal` |
| 19 أكتوبر 2026 | اسم جدول الحجوزات: `{prefix}rahl_bookings` (بدل tbs_ الموجود في SPEC) |
| 19 أكتوبر 2026 | رقم الحجز المرجعي بصيغة `RHL-YYYY-NNNNN` (بدل TB-) |
| 19 أكتوبر 2026 | Option اسم الإعدادات: `rahl_settings` — Option نسخة قاعدة البيانات: `rahl_db_version` |
| 19 أكتوبر 2026 | صلاحية إدارة الحجوزات: `rahl_manage_bookings` |
| 20 أكتوبر 2026 | شورت كودز المقاعد: `rahl_available_seats`, `rahl_total_seats`, `rahl_booked_seats` (بدل `tbs_*` في SPEC) |
| Step 4 | Known limitation: الصيغة الأوروبية للسعر `1.200,50` غير مدعومة في v0.1 |

## Known issues

لا يوجد بعد.

## Step 5 notes

- استعلام المحجوز: `confirmed` **أو** `pending` مع `expires_at > UTC_TIMESTAMP()` (المقاعد ترجع فور انتهاء المهلة من غير Cron).
- `tour_date IS NULL` و` tour_date = %s` استعلامان منفصلان (prepare + NULL).
- بدون Transient cache في v0.1 (حسب PLAN).
