# SchoolSphere: School / College Management System

Laravel 12 + Blade + Bootstrap 5 + jQuery Ajax CRUD implementation for a full School/College Management System.

## Tech Stack

- PHP Laravel 12
- Blade templates
- Bootstrap 5
- jQuery + Ajax for all CRUD forms
- MySQL database: `School_DB_all`
- DomPDF for PDF exports and iCards
- Laravel Excel for Excel exports
- Chart.js for dashboard charts

## Implemented Roles

- `super_admin`: full access
- `admin`: full or custom module access
- `teacher`: limited by assigned permissions and own class scope

## Default Super Admin Seed

- Email: `superadmin@school.com`
- Password: `Admin@123`
- First login enforces password change

## Implemented Modules

1. Students
2. Staff / Teachers
3. Classes
4. Sections
5. Subjects
6. Exams
7. Results
8. Attendance
9. Fees & Payments
10. Timetable
11. Notifications
12. Holidays
13. Leaves
14. Calendar Events
15. Audit Logs
16. Dashboard + iCard PDF

All modules are exposed as role-gated routes and use Ajax-driven CRUD (modal forms) except audit logs (read-only).

## Core Features

- Dynamic module registry for shared CRUD behavior
- Ajax create/update/delete with server validation feedback
- Soft deletes with `deleted_by` in all domain tables
- Audit logs for login/password and CRUD actions
- File upload fields (photo, aadhar, PAN, qualification docs, student docs)
- Dashboard cards + charts
- Leave/Holiday/Calendar integration
- PDF/Excel report export per module
- Student/staff iCard PDF generation route:
  - `/icards/generate/{type}/{id}`
- Single shared layout shell:
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/header.blade.php`
  - `resources/views/layouts/footer.blade.php`
- Single JS bundle for theme toggle, charts, Ajax CRUD, and modals:
  - `resources/js/app.js`

## Important Environment Notes

- `.env.example` defaults are set to MySQL `School_DB_all`.
- In this machine, `maatwebsite/excel` required composer install with:
  - `--ignore-platform-req=ext-gd`
- In production/local setup, enabling PHP `gd` extension is recommended.

## Security Hardening (Recommended)

This project now includes configurable hardening controls:

- Global security headers middleware (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, optional HSTS/CSP)
- Optional external IP blocking middleware
- Optional HTTPS URL forcing outside local environment
- Login brute-force protection with configurable throttle and lockout audit logs

Set these in `.env` for production:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `SECURITY_HEADERS_ENABLED=true`
- `SECURITY_FORCE_HTTPS=true` (only when HTTPS is properly configured)
- `SESSION_SECURE_COOKIE=true`
- `SESSION_HTTP_ONLY=true`
- `SESSION_SAME_SITE=lax` (or `strict` if your flow allows)
- `SECURITY_LOGIN_MAX_ATTEMPTS=5`
- `SECURITY_LOGIN_DECAY_MINUTES=10`

To allow only selected IPs:

- `SECURITY_BLOCK_EXTERNAL=true`
- `SECURITY_ALLOWED_IPS=127.0.0.1,::1,YOUR.SERVER.IP`

Note: Keep `SECURITY_CSP` empty unless you provide a complete policy string that matches your inline scripts/CDN usage.

## Ready-to-Run Steps

1. Create database:
   - `School_DB_all`
2. Copy env and configure DB credentials:
   - `copy .env.example .env`
   - update `DB_USERNAME` / `DB_PASSWORD`
3. Install dependencies:
   - `composer install`
   - `npm install`
4. Generate app key:
   - `php artisan key:generate`
5. Run migrations and seeders:
   - `php artisan migrate --seed`
6. Link storage:
   - `php artisan storage:link`
7. Build assets:
   - `npm run build`
8. Run app:
   - `php artisan serve`

## Key Routes

- `/` -> redirects to login
- `/login`, `/logout`
- `/password/change`
- `/dashboard`
- `/{module}` for all CRUD modules
- `/{module}/export/pdf`
- `/{module}/export/excel`
- `/audit-logs`
- `/icards`
- `/icards/generate/{type}/{id}`

## Billing Webhooks Reference

- See `BILLING_WEBHOOK_CONTRACTS.md` for provider signature rules and payload formats.
- QA helper command: `php artisan billing:webhook:sign {generic|stripe|razorpay}`
- QA sample payload generator: `php artisan billing:webhook:sample {generic|stripe|razorpay}`
- QA direct simulation: `php artisan billing:webhook:fire {generic|stripe|razorpay}`
- One-step shortcut: `php artisan billing:webhook:fire stripe --from-sample --secret=YOUR_SECRET --dry-run`
- Non-interactive send requires `--force` (or use `--dry-run`)
- Host allowlist env: `BILLING_FIRE_ALLOWED_HOSTS=localhost,127.0.0.1,::1` (non-allowlisted host needs `--force`)
- One-time host exception: `php artisan billing:webhook:fire generic --url=https://example.com/api/billing/webhook --allow-host=example.com --dry-run`
- Safety: `--allow-host` with real send is blocked unless `--force` is added
