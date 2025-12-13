## Next Implementation Priorities

This document captures the recommended next steps following the completion of the notification system. Action items are grouped by achievability so we can sequence work efficiently.

### Immediate Wins (Low Effort, High Impact)

1. **Comprehensive Demo Seeder**  
   - Expand `DatabaseSeeder` (or add dedicated seeders) so `php artisan migrate:fresh --seed` produces:
     - Student + faculty pairings with `internships.faculty_supervisor_id` populated.
     - Companies, applications, and internships that mirror the ERD relationships.
     - Logbook entries across multiple weeks and statuses (`pending_review`, `verified`, `revision_requested`).
     - Seeded notifications so the bell/dropdown feels active immediately after seeding.  
   - Benefit: restores a realistic QA sandbox after every reseed and supports demos without manual bootstrapping.

2. **Mailtrap Credential Validation Checklist**  
   - Document the SMTP values in the README or an ops note and add a quick command/script to reapply them after reseeding.  
   - Ensures the freshly seeded environment still exercises mail channels that the tests depend on.

### Near-Term Enhancements (Moderate Effort)

1. **Align Logbook Entries with Internships**  
   - Introduce `logbook_entries.internship_id` (matching the ERD) while retaining `user_id` temporarily for backward compatibility.  
   - Backfill existing rows by linking each student’s active internship.  
   - Update relationships, policies, and queries to prefer the internship association.  
   - Outcome: simplified supervisor scoping (`internships → logbook_entries`) and cleaner future analytics.

2. **Faculty Assignment Admin UI**  
   - Provide an admin Livewire/Volt screen to assign or reassign `faculty_supervisor_id` on internships.  
   - Trigger in-app/email notifications when a supervisor assignment changes so both student and faculty are informed.  
   - Builds on the new notification infrastructure and keeps assignments in sync with ERD expectations.

### Strategic Follow-Ups (Higher Effort, Sets Up Future Work)

1. **Company & Application Management Tools**  
   - Lightweight admin panel to manage companies (`is_active` toggles) and review applications.  
   - Accepting an application should automatically create an internship (per the ERD), ensuring the lifecycle is fully represented.

2. **Documents Requirement Stub**  
   - Implement the ERD’s document requirement (e.g., `required_documents` table or checklist tied to internships).  
   - Integrate with notifications for upcoming due items, laying the groundwork for compliance tracking.

3. **Bulk Faculty Actions**  
   - Add multi-select approve/revision actions on the faculty logbook queue.  
   - Pairs nicely with the notification system to batch-update and alert multiple students at once.

### Notes

- Each of the above builds on the freshly completed notification work and the ERD you provided.
- Prioritising the Immediate Wins restores productive developer loops after `migrate:fresh --seed` and keeps mail delivery functioning.
- The Near-Term Enhancements address schema alignment and supervisor workflows that will unlock richer reporting later.