# Implementation Log — 18/12/25

## Summary
Focused on fixing core workflow bugs (email/notifications, logbooks review authorization, placement + assignments) and improving admin/faculty UX. Added/updated Pest coverage for key flows and ran Pint on touched files.

## Implementations

### Mail (Manual Testing)
- Configured Sail + Mailpit SMTP settings for manual email testing (`mailpit:1025`, dashboard `:8025`).

### Eligibility (Student ↔ Admin)
- Admin dashboard “Pending Eligibility” now reflects `eligibility_docs` completion state (instead of `applications.eligibility_status`).
- Admin receives notification (database + mail) when a student uploads eligibility documents.
- Student receives notification (database + mail) when eligibility is approved/rejected.
- Removed queued delivery for eligibility notifications to ensure Mailpit receives them immediately in local testing.

### Company Proposals (Placement)
- Admin receives notification (database + mail) when a student submits company proposals (deduped on re-submit).
- Admin “Company Proposals” UI grouped by student to avoid duplicate proposal cards per student.

### Faculty Assignments
- Admin “Faculty Assignments” now includes `pending` and `active` internships (not just `active`).
- Added “Auto assign” to distribute supervisors across unassigned internships.
- Assigning/auto-assigning a faculty supervisor sets internship `status` to `active` (and unassigning sets it to `pending`).
- Admin dashboard “Active Internships” updated to reflect assigned internships more accurately.

### Logbooks (High Priority Fix)
- Fixed faculty approval 403 by ensuring student submissions enter the faculty workflow (`status=pending_review`, `supervisor_status=pending`).
- Policy updated to allow reviewing legacy `submitted` entries as well.

### Placement Page
- “Current Status” updated to reflect placement confirmation more clearly after a company is chosen.
- Added student-editable “Internship Details” section to capture industry supervisor name (`internships.supervisor_name`).

### Faculty Dashboard
- Fixed “No students assigned” false state by including `pending` + `active` internship statuses in faculty queries.

### Admin User Management
- Enabled “Add User” and implemented admin creation of **faculty/admin** accounts only.
- Students still register through the normal registration flow (default role = student).

## Tests Added/Updated (Pest)
- Admin dashboard eligibility count.
- Company proposal submission notification + grouping.
- Faculty assignments (pending internships visibility, auto-assign, assignment sets status active).
- Logbook submission enters review workflow + faculty can approve (including legacy status).
- Placement status + industry supervisor detail saving.
- Faculty dashboard assigned-students visibility.
- Admin user creation (faculty/admin only; student blocked; unique email validated).

## Notes / Follow-ups (Tomorrow)
- Verify UI flows end-to-end with seeded/realistic data (multiple students, multiple weeks logbooks).
- Consider adding status badges (“Pending/Active”) in faculty student cards for clarity.
- Optional: Link “Needs Revision” dashboard card to filtered logbooks list.
- Admin can add important dates based on the semester. 
