# InternIMS — System Documentation

This document describes how InternIMS works end-to-end: roles, core workflows, key data models, and the main places in the codebase where each feature lives.

## Contents

- Overview
- Tech stack and architecture
- Roles and access control
- Core student workflow
- Admin workflows
- Faculty workflows
- Data model overview
- Notifications and reminders
- Local development and services
- Testing and code style
- Troubleshooting

## Overview

InternIMS is an internship management system built around a staged student journey:

1. **Eligibility verification** (upload required documents, admin review).
2. **Placement proposals** (student submits up to 2 proposals, admin approves/rejects with remarks).
3. **Placement confirmation** (student confirms an approved proposal → creates an `Internship`).
4. **Weekly logbooks** (student submits weekly logbook + signed PDF, faculty verifies/reviews).

## Tech stack and architecture

- **Backend**: Laravel 12
- **Auth**: Laravel Fortify (+ optional OAuth via Socialite)
- **UI**: Livewire 3 + Volt single-file components (SFCs), Flux UI components, Tailwind CSS v4
- **Data**: Eloquent models backed by migrations
- **Notifications**: Laravel notifications (`database` + `mail`)
- **Scheduler**: `routes/console.php` schedules reminders
- **Local dev**: Laravel Sail / Docker (`compose.yaml`)

### Where the “pages” live (Volt)

Most interactive pages are Volt components stored in `resources/views/livewire/**`. Routes are registered in `routes/web.php` using `Livewire\Volt\Volt::route(...)`.

Examples:

- Student: `resources/views/livewire/eligibility/index.blade.php`, `resources/views/livewire/placement/index.blade.php`, `resources/views/livewire/logbooks/index.blade.php`
- Admin: `resources/views/livewire/admin/eligibility/index.blade.php`, `resources/views/livewire/admin/companies/index.blade.php`
- Faculty: `resources/views/livewire/faculty/logbooks/index.blade.php`

## Roles and access control

InternIMS uses a simple role field on `users.role`:

- `student`
- `faculty`
- `admin`

### Role middleware

Role-gated route groups use a custom middleware alias:

- Middleware alias registration: `bootstrap/app.php`
- Middleware implementation: `app/Http/Middleware/EnsureUserHasRole.php`

Routes use `->middleware('role:admin')` and `->middleware('role:faculty,admin')` in `routes/web.php`.

### Student gating middleware

Certain student pages are blocked until prerequisites are met:

- Placement requires eligibility completion: `app/Http/Middleware/EnsureEligibilityCompleted.php`
- Logbooks require internship existence: `app/Http/Middleware/EnsureInternshipExists.php`

Admins/faculty bypass these gates.

### Policies (logbooks)

Faculty logbook review screens use authorization via `LogbookEntryPolicy`:

- Policy: `app/Policies/LogbookEntryPolicy.php`
- Registered in: `app/Providers/AuthServiceProvider.php`

## Core student workflow

### 1) Dashboard

Student dashboard summarizes:

- Eligibility completion
- Placement status
- Logbook counts
- Unread notifications
- Important dates (current semester)

Primary entry point: `resources/views/livewire/dashboard.blade.php`

### 2) Eligibility verification (Stage 1)

Student uploads 3 required document types:

- `resume`
- `transcript`
- `offer_letter`

Implementation:

- Student UI + upload logic: `resources/views/livewire/eligibility/index.blade.php`
- Storage: files stored on the `public` disk under `eligibility/<type>/...`
- Records: `eligibility_docs` table via `App\Models\EligibilityDoc`

When a student uploads a document, all admins are notified (database + mail) using:

- `App\Notifications\EligibilityDocSubmittedNotification`

### 3) Placement proposals (Stage 2)

Students submit **two** company proposals. Each proposal has independent status:

- `pending`
- `approved`
- `rejected`

Implementation:

- Student UI + submit: `resources/views/livewire/placement/index.blade.php`
- Data: `applications` + `proposed_companies`
  - `App\Models\Application`
  - `App\Models\ProposedCompany`

On first submission, admins are notified (database + mail) using:

- `App\Notifications\ProposedCompanySubmittedNotification`

### 4) Admin review → approve/reject proposals

Admins review proposals in:

- `resources/views/livewire/admin/companies/index.blade.php`

Approval/rejection updates `proposed_companies.status` and may include `admin_remarks` on rejection.

Students are notified (database + mail) using:

- `App\Notifications\ProposedCompanyStatusNotification`

### 5) Student confirms an approved proposal (Stage 3)

Once at least one proposal is approved, the student confirms a final choice. Confirmation:

- Sets `applications.status` to `approved`
- Creates an `internships` record linked to the application

Implementation:

- Confirmation action: `confirmPlacement(...)` in `resources/views/livewire/placement/index.blade.php`
- Model: `App\Models\Internship`

### 6) Weekly logbooks (Stage 4)

Students submit weekly logbooks (max 24 weeks) with:

- `entry_text`
- required signed PDF upload (stored on `public` disk)

Statuses used in the current workflow:

- Student-side `logbook_entries.status`: `draft`, `submitted`, `pending_review`, `approved`
- Supervisor-side `logbook_entries.supervisor_status`: `pending`, `verified`, `revision_requested`

Implementation:

- Student list + submission form: `resources/views/livewire/logbooks/index.blade.php`
- Student detail view: `resources/views/livewire/logbooks/show.blade.php`
- Model: `App\Models\LogbookEntry`

When a student submits a logbook entry, the assigned faculty supervisor (if any) is notified using:

- `App\Notifications\NewLogbookSubmittedNotification`

## Admin workflows

Admin area routes are under `/admin` (see `routes/web.php`).

### Admin dashboard

High-level counts (users, eligibility backlog, internships, pending logbooks):

- `resources/views/livewire/admin/dashboard.blade.php`

### Eligibility review

Admins can preview PDFs and approve/reject all required documents:

- `resources/views/livewire/admin/eligibility/index.blade.php`

Approval/rejection notifies students using:

- `App\Notifications\EligibilityStatusNotification`

### Company proposals review

Approve/reject company proposals, optionally with remarks:

- `resources/views/livewire/admin/companies/index.blade.php`

### User management

Admins can:

- Create new `faculty` or `admin` users
- Change existing user roles (with guardrails like not demoting self)

Implementation:

- `resources/views/livewire/admin/users/index.blade.php`

### Faculty assignments

Admins assign a faculty supervisor to internships (manual or auto-assign):

- `resources/views/livewire/admin/assignments/index.blade.php`

Assignment updates:

- `internships.faculty_supervisor_id`
- Internship `status` may change between `pending` and `active`

### Important dates (deadlines)

Admins manage important dates (eligibility/placement/internship/other) which are also used for reminders:

- `resources/views/livewire/admin/dates/index.blade.php`
- Model: `App\Models\ImportantDate`
- Semester detection: `app/Services/SemesterService.php`

## Faculty workflows

Faculty area routes are under `/faculty` (see `routes/web.php`).

### Faculty dashboard

Summarizes:

- Pending logbooks to review
- Assigned students count
- Students with revisions requested

Implementation:

- `resources/views/livewire/faculty/dashboard.blade.php`

### Assigned students

Shows assigned students and logbook progress:

- `resources/views/livewire/faculty/students/index.blade.php`

### Logbook verification (review queue)

Faculty review queue supports:

- Filtering/search
- Bulk approve
- Bulk “request revision” (requires a comment)

Implementation:

- `resources/views/livewire/faculty/logbooks/index.blade.php`

### Logbook review detail

Faculty can approve or request revisions for a specific logbook:

- `resources/views/livewire/faculty/logbooks/show.blade.php`

Notifications:

- Approve → `App\Notifications\LogbookEntryApprovedNotification`
- Revision requested → `App\Notifications\LogbookEntryRevisionRequestedNotification`

## Data model overview

This is a high-level map of the main tables/models used in the application.

### `users` (`App\Models\User`)

Key fields:

- `role`: `student|faculty|admin`
- `theme_preference`: `light|dark|system`

Relationships:

- `socialAccounts()` → `SocialAccount`
- `eligibilityDocs()` → `EligibilityDoc`
- `applications()` → `Application`
- `internships()` → `Internship`
- `logbookEntries()` → `LogbookEntry`
- `supervisedInternships()` → `Internship` (as faculty supervisor)

### `eligibility_docs` (`App\Models\EligibilityDoc`)

- `type`: `resume|transcript|offer_letter`
- `status`: `pending|approved|rejected`
- `path`: stored file path on `public` disk

### `applications` (`App\Models\Application`)

Represents a student’s placement application lifecycle.

- `status`: `draft|submitted|approved|rejected`
- `company_name`: set on final confirmation
- `submitted_at`: submission timestamp

Also includes additional “eligibility” fields (see migration `2025_12_13_150900_add_eligibility_fields_to_applications_table.php`) which may or may not be used by the current flows.

### `proposed_companies` (`App\Models\ProposedCompany`)

Company proposals linked to an application.

- `status`: `pending|approved|rejected`
- `admin_remarks`: optional remarks for rejections

### `internships` (`App\Models\Internship`)

Represents the confirmed placement + assignment of faculty supervisor.

- `status`: commonly `pending|active` in current flows
- `faculty_supervisor_id`: assigned by admin
- `supervisor_name`: industry supervisor name (editable by student on placement page)

### `logbook_entries` (`App\Models\LogbookEntry`)

Weekly submissions.

- `week_number`: 1–24
- `file_path`: signed PDF path (public disk)
- `status`: `draft|submitted|pending_review|approved`
- `supervisor_status`: `pending|verified|revision_requested`
- `supervisor_comment`: comment for revision/feedback

### `important_dates` (`App\Models\ImportantDate`)

Used for deadlines and reminders.

- `type`: `eligibility|placement|internship|other`
- `semester`: derived via `SemesterService`

### `social_accounts` (`App\Models\SocialAccount`)

OAuth account linkage for Google/Microsoft.

## Notifications and reminders

InternIMS uses Laravel Notifications, typically sending via:

- `database` (for in-app notification bell)
- `mail` (viewable via Mailpit in local dev)

### Notification bell (in-app)

Unread notifications appear in the header/sidebar dropdown:

- UI: `resources/views/livewire/notifications/bell.blade.php`

### Scheduled deadline reminders

Deadline reminders run daily:

- Schedule: `routes/console.php` (`app:send-deadline-reminders` at `09:00`)
- Command: `app/Console/Commands/SendDeadlineReminders.php`
- Notification: `App\Notifications\DeadlineReminderNotification`

The command checks `important_dates` for deadlines happening “today” or in “3 days”, then notifies students who are missing eligibility completion or placement confirmation.

## Local development and services

### Services (Sail)

See `compose.yaml` for configured services and ports. Common defaults:

- App: `http://localhost` (port 80)
- Vite dev server: `http://localhost:5173`
- Mailpit SMTP: `localhost:1025`
- Mailpit UI: `http://localhost:8025`
- MySQL: `localhost:3306`
- Redis: `localhost:6379`

### Mailpit configuration

If you want to see real emails locally (instead of `MAIL_MAILER=log`), configure your `.env` for Sail:

- `MAIL_MAILER=smtp`
- `MAIL_HOST=mailpit`
- `MAIL_PORT=1025`

## Testing and code style

- Tests: Pest (`tests/Feature/**`, `tests/Unit/**`)
  - Run targeted tests: `php artisan test tests/Feature/PlacementConfirmationTest.php`
  - Run all tests: `php artisan test`
- Formatting: Pint
  - Format changed files: `vendor/bin/pint --dirty`

## Troubleshooting

### Vite manifest errors

If you see `Illuminate\\Foundation\\ViteException: Unable to locate file in Vite manifest`, rebuild assets:

- `npm run dev` (or `npm run build`)

### Mail not appearing in Mailpit

- Ensure `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`
- Ensure Sail is up: `./vendor/bin/sail up -d`

### Lucide icon timing warnings

Lucide icons are rendered client-side (see `resources/js/app.js`). If icons fail to render after Livewire navigation, check for duplicate “createIcons” hooks and ensure assets are built.

