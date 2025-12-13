## Completed Work Snapshot

- Faculty dashboard, review queue, and detail views now support the full supervisor workflow (status updates, comments, badges, dark mode).
- Authorization gates (`LogbookEntryPolicy`, role middleware) prevent students from accessing faculty tools.
- Tests cover access control and review actions; CI is green (40 tests passing).

> **Reminder:** if you introduce more reviewer states, update both the student and faculty badge maps in tandem.

---

## Next Implementation Focus: Student Notifications & Email Alerts

### Objective
Close the feedback loop so interns and supervisors receive timely alerts when logbook statuses change, without needing to poll the UI.

### Workstream 1 – Notification Infrastructure
- **Create Notification Classes**
  - `LogbookEntryApprovedNotification`
  - `LogbookEntryRevisionRequestedNotification`
  - `NewLogbookSubmittedNotification`
- **Trigger Points**
  - Fire notifications from the existing approve / revision Livewire actions.
  - Dispatch `NewLogbookSubmittedNotification` inside the student submission handler.
- **Delivery Channels**
  - Start with `database` channel; implement `toDatabase()` payload containing entry id, week, status, reviewer, and optional comment snippet.
- **Acceptance Criteria**
  - Notifications table records the correct payload each time a supervisor action occurs.
  - Duplicate notifications are not created on repeated clicks (leverage existing loading guards).
  - Feature tests cover each notification type.

### Workstream 2 – Email (Mail) Delivery
- **Configure Mail (Mailtrap Sandbox)**
  - Use the shared sandbox at `sandbox.smtp.mailtrap.io` (free tier).
  - Update `.env` with the generated credentials:
    ```dotenv
    MAIL_MAILER=smtp
    MAIL_HOST=sandbox.smtp.mailtrap.io
    MAIL_PORT=2525
    MAIL_USERNAME=7bb939a3560b31   # replace with the current Mailtrap username
    MAIL_PASSWORD=****5316        # replace with the current Mailtrap password
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS="internims@example.com"
    MAIL_FROM_NAME="Internims"
    ```
  - Run `php artisan config:clear` after editing `.env`.
- **Extend Notification Channels**
  - Add `mail` channel to the above notifications with concise subject/body.
  - Include supervisor comments in revision emails.
- **Preferences** *(optional but recommended)*
  - Add boolean columns `notify_on_logbook_review`, `notify_on_logbook_submission` to `users` table or a settings table.
  - Honor preferences when dispatching notifications.
- **Acceptance Criteria**
  - Test send via `php artisan tinker` (e.g., dispatch a notification) and confirm the message appears inside the Mailtrap inbox.
  - Mailable previews render correctly (`php artisan notifications:table` not needed if already set).
  - Tests assert that notifications respect user preferences.

### Workstream 3 – In-App Notification UI
- **Header Bell Component**
  - Add a bell icon with unread count to the shared layout (`resources/views/components/layouts/app/header` or equivalent).
- **Notification Drawer / Page**
  - Livewire component listing recent notifications with status icon, message, timestamp, CTA to open the logbook.
  - Provide Mark-as-read / Mark-all-as-read actions (update `read_at`).
- **Badge Integration**
  - Ensure the count decrements immediately on mark-as-read.
- **Acceptance Criteria**
  - Students and faculty can view and clear notifications without page refresh.
  - Accessibility pass: keyboard navigation and screen-reader labels for bell + list items.

### Workstream 4 – Optional Enhancements
- **Bulk Faculty Actions**: allow approving/reviewing multiple entries from the queue once notifications are stable.
- **Export / Reporting**: add CSV export of reviewed logbooks for faculty.
- **Messaging**: integrate notifications with future chat/AI modules.

---

## Implementation Checklist
- [ ] Database + mail notifications fire for approve, revision, and submission events.
- [ ] Notification preferences (if added) respected.
- [ ] Notification UI surfaces unread items and allows clearing.
- [ ] Tests cover notification dispatch, UI visibility, and permissions.
- [ ] Dark mode styles applied to any new UI components.

Once the above is complete, update this document with outcomes and define the next strategic milestone (e.g., admin analytics or messaging module).