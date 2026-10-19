# PROGRESS — Rahal Booking Plugin

## Steps

- [x] Step 0  Phase 0 setup (structure + docs + test data)
- [x] Step 1  Skeleton (plugin file + class-plugin.php + functions.php)
- [x] Step 2  Installer (table + capability)
- [x] Step 3  Settings
- [x] Step 4  Tour reader
- [ ] Step 5  Repository + Availability + seat shortcodes
- [ ] Step 6  Pricing
- [ ] Step 7  Booking Manager
- [ ] Step 8  Booking form
- [ ] Step 9  Admin bookings
- [ ] Step 10 Emails
- [ ] Step 11 QA
- [ ] Step 12 Release

## Current step

**Step 4 — Tour reader** — في الانتظار.

## Decisions / deviations from SPEC

| التاريخ | القرار |
| --- | --- |
| 19 أكتوبر 2026 | اسم البلاجن: **Rahal** — اسم النشر قد يتغير قبل v0.4 |
| 19 أكتوبر 2026 | Prefix الدوال والـ options: `rahl_` — الكلاسات: `RAHL_` — Text Domain: `rahal` |
| 19 أكتوبر 2026 | اسم جدول الحجوزات: `{prefix}rahl_bookings` (بدل tbs_ الموجود في SPEC) |
| 19 أكتوبر 2026 | رقم الحجز المرجعي بصيغة `RHL-YYYY-NNNNN` (بدل TB-) |
| 19 أكتوبر 2026 | Option اسم الإعدادات: `rahl_settings` — Option نسخة قاعدة البيانات: `rahl_db_version` |
| 19 أكتوبر 2026 | صلاحية إدارة الحجوزات: `rahl_manage_bookings` |

## Known issues

لا يوجد بعد.
