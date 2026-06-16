# Maa Saraswati Sansthan - Session Summary

Date: 2026-04-29
Workspace: c:/xampp/htdocs/saraswati

## 1. Project Foundation Completed
- Built full PHP (XAMPP) website with Bootstrap + Tailwind visual layer.
- Created complete public page set:
  - index.php (Home)
  - about.php
  - aarti.php
  - significance.php
  - festivals.php
  - project.php
  - gallery.php
  - videos.php
  - donate.php
  - contact.php
- Added shared includes:
  - includes/config.php
  - includes/functions.php
  - includes/header.php
  - includes/footer.php
  - includes/db.php
- Added assets and UI behavior:
  - assets/css/style.css
  - assets/js/main.js

## 2. Design + UX Work Done
- Updated site to spiritual saffron/gold look and custom motifs.
- Replaced unrelated stock visuals with project images:
  - logo from assets/images/logo.jpeg
  - hero banners from assets/images/sarabanner1.jpeg and assets/images/sarabanner2.jpeg
- Added hero upgrades:
  - temple silhouette overlay
  - animated banner transitions
  - decorative section dividers
- Added sticky Donate CTA and floating WhatsApp button.
- Replaced WA text with actual logo icon:
  - assets/images/whatsapplogo.png

## 3. Navigation and Visibility Fixes
- Fixed navbar menu visibility conflict (Tailwind collapse vs Bootstrap collapse).
- Adjusted navbar expansion breakpoint for better desktop/laptop behavior.
- Verified mobile hamburger menu and desktop links visibility.

## 4. Hero Welcome Overlay Controls
- Added close button on hero welcome overlay.
- Added persistence so closed overlay stays hidden across refreshes using localStorage.
- Added footer link to re-enable overlay:
  - "Show Welcome Again" / "वेलकम बैनर फिर दिखाएं"

## 5. Donation + UPI Improvements
- Donation page includes bank details, copy buttons, QR display, and UPI actions.
- UPI logic centralized and improved:
  - canonical UPI URI helper
  - Android intent fallback button
  - desktop fallback guidance when deep link cannot open
- Added exact URI override support:
  - data/upi-uri.txt (paste exact upi://pay?... from QR to match 1:1)
- Default UPI id currently set to: 42636837783@sbi

## 6. MySQL Database Setup Added
- Created/updated schema file:
  - database/saraswati_schema.sql
- Existing tables:
  - events
  - videos
  - gallery_images
  - contact_messages
- Added new auth/profile tables:
  - users
  - password_resets
  - donations
- Schema applied successfully in local XAMPP MySQL.

## 7. Admin Panel Evolution
- Initial admin panel created for uploads + events JSON update.
- Upgraded admin access to role-based auth (admin role required).
- Current admin panel path:
  - admin/index.php

## 8. Complete Auth System Implemented
- Added session/auth layer:
  - includes/auth.php
- Added pages:
  - signup.php
  - login.php
  - logout.php
  - forgot-password.php
  - reset-password.php
  - profile.php
- Functional scope:
  - signup
  - login
  - reset password (token-based)
  - landing to profile
  - role display (member / adhyaksh / sachiv / admin)
  - profile image upload/change
  - donation history and manual entry form

## 9. Default Admin Credentials
- Email: admin@maa-saraswati.co.in
- Password: admin123
- Seeded via schema in users table.

## 10. Auth + Role Behavior in Header
- Guests: Signup + Login links visible.
- Logged-in users: Profile + Logout links visible.
- Admin users: Admin link visible.

## 11. Data + Upload Folders in Use
- uploads/gallery
- uploads/qr
- uploads/audio
- uploads/docs
- uploads/profiles
- data/events.json
- data/videos.json
- data/contact-messages.json
- data/upi-uri.txt

## 12. Validation Performed
- Repeated PHP lint checks across entire codebase passed.
- Browser checks were run for:
  - navbar visibility
  - hero overlay close/reset behavior
  - WhatsApp icon rendering
  - signup -> profile flow
  - admin login + admin panel access

## 13. Current Known Operational Notes
- UPI deep links often fail on desktop browsers by design; intended to open on mobile UPI apps.
- For exact QR-matched UPI URI, populate data/upi-uri.txt with scanned upi://pay URL.
- Forgot-password currently shows reset link directly in UI (development mode behavior).

## 14. Suggested Next Steps
1. Integrate SMTP/email service so reset links are emailed instead of shown in-page.
2. Build admin user management (create user, assign role, deactivate user).
3. Auto-capture donations from payment callbacks into donations table.
4. Add security hardening:
   - CSRF tokens on auth/profile/admin forms
   - rate limiting on login
   - strict upload MIME validation
   - password policy and lockout controls
5. Replace Tailwind CDN with build-time CSS pipeline for production.
