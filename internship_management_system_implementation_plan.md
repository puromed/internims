# Internship Management System – Lean Implementation Plan

## Executive Summary
- Ten-week milestone plan prioritizes the student, faculty, and admin experiences, with a built-in buffer to accommodate design tweaks and capstone feedback before the February 2026 presentation.
- AI-assisted logbook features will be delivered through an asynchronous Laravel pipeline that leverages the `gemini/laravel` package for rapid integration and structured JSON responses for skills and sentiment extraction, keeping vendor choice flexible for future stages.[1][2][3]
- Defined AI pair-programming workflow keeps you in command while treating assistants as review partners to accelerate implementation without sacrificing craftsmanship.

## Current Context & Assets
- **Technology stack:** Laravel 12, Tailwind CSS v4, Alpine.js (Breeze/Livewire), MySQL.
- **Existing artifacts:** Tailwind UI HTML prototypes ready to port into Blade components; Gemini API key available with optional Z.AI key; project proposal and ERD already drafted.
- **Key outcomes:** Unlock Stage 1 eligibility, Stage 2 placement, and Stage 3 logbook flows with faculty/admin oversight; ensure AI features augment rather than replace student input; ship a SaaS-quality UI.

## Development Milestones
| Week (starting) | Focus | Key Deliverables | Exit Criteria |
| --- | --- | --- | --- |
| Week 1 (Dec 8) | Environment & UI scaffolding | Laravel 12 project, Breeze/Livewire auth, Tailwind v4 config, base layout ported | Team login works, global layout matches mock |
| Week 2 (Dec 15) | Component library & dashboards | Blade components (`x-app-layout`, `x-card`, `x-badge`, `x-button`, `x-file-upload`, `x-textarea-ai`), student dashboard shell | Components responsive, reusable storybook page or style guide |
| Week 3 (Dec 22) | Stage 1 Eligibility | Eligibility upload forms, storage logic, admin review toggles, status badges | Documents upload, status transitions visible on dashboard |
| Week 4 (Dec 29) | Stage 2 Placement | Placement create/show flows, external placement form validation, Role Checker stub with queued AI call | `is_eligible` gating enforced, AI button returns stubbed analysis |
| Week 5 (Jan 5) | Stage 3 Logbooks foundation | Logbook table, create form (textarea + file upload), migrations for AI JSON, queue infrastructure | Student can submit logbook & upload PDF; jobs queued successfully |
| Week 6 (Jan 12) | Stage 3 AI integration & QA | Gemini/Z.AI integration, JSON parsing, faculty verification view with PDF embed | Logbook analysis persisted and rendered for faculty review |
| Week 7 (Jan 19) | Faculty module | Faculty dashboard, placement & logbook approvals, comment workflows | Supervisors can approve/reject with comments and status sync |
| Week 8 (Jan 26) | Admin module & messaging | Admin eligibility panel, company settings, student messaging inbox | Admin toggles eligibility; messaging channel supports threaded chat |
| Week 9 (Feb 2) | Hardening & observability | Automated tests, queue monitoring dashboard, logging, seed data for demo | All critical paths covered by feature/system tests; queue metrics visible |
| Week 10 (Feb 9) | UAT, documentation, polish | User acceptance testing, accessibility sweep, deployment scripts, final report | Stakeholder sign-off; production build ready with rollback plan |

The schedule intentionally front-loads component work and core student flows so that the remaining weeks emphasize iteration, AI tuning, and polish.

``mermaid
gantt
    dateFormat  YYYY-MM-DD
    title IMS Lean Build Timeline
    section Foundation
    Environment & scaffolding      :a1, 2025-12-08, 7d
    Component library & dashboards :a2, after a1, 7d
    section Student Journey
    Stage 1 eligibility            :b1, after a2, 7d
    Stage 2 placement              :b2, after b1, 7d
    Stage 3 logbooks foundation    :crit, b3, after b2, 7d
    Stage 3 AI integration         :crit, b4, after b3, 7d
    section Staff Modules
    Faculty module                 :c1, after b4, 7d
    Admin & messaging              :c2, after c1, 7d
    section Stabilisation
    Hardening & observability      :d1, after c2, 7d
    UAT & documentation            :d2, after d1, 7d
```

## Module Implementation Playbook

### Shared Blade Component Library
- Port Tailwind UI HTML snippets into Blade components with named slots and sensible defaults. Use per-component config for color schemes (indigo primary, neutral secondary) and dark-mode readiness.
- Include Alpine-driven interactivity (sidebar collapse, toast dismissal) inside `x-app-layout`.
- Ensure components accept `class` merges (`@props(['variant' => 'primary'])`) to maintain flexibility.
- Document components in a reference page to accelerate reuse.

### Student Module
- **Dashboard:** Build a Livewire component that composes progress stepper, stats, and “Action Required” alert. Pull computed stats from cached queries to avoid redundant joins. Use policy checks to guard faculty-only metrics.
- **Stage 1 Eligibility:** Implement upload rows as individual Livewire components hooking to `eligibility_docs` table. Store files on S3-compatible storage with signed URLs; update status after admin review.
- **Stage 2 Placement:** Enforce gating via middleware on `is_eligible`. Create placement form with validation, storing to `applications`. Role Checker button dispatches AI job returning suitability tags stored alongside application record (initially stubbed, activated in Week 6).
- **Stage 3 Logbooks:**
  - Index view: Paginate 24-week entries with status badges.
  - Create view: Two-column layout; left column uses `x-textarea-ai` with "Analyze with Gemini" button, right column uses `x-file-upload` for signed logsheet.
  - On submit, persist entry, upload file, dispatch AI analysis job that requests structured JSON (skills, sentiment, risk flags) for faculty to review.[2][3]
  - Show view: Render stored JSON insight as key-value chips and embed signed PDF or provide secure download.

- **Messages:** Lightweight Livewire/Alpine chat view using `messages` table; include filters for faculty vs admin contacts.

### Faculty Module
- **Dashboard:** Aggregate assigned interns, pending approvals, AI alerts. Use cached metrics refreshed via scheduled command.
- **Placement Approvals:** Implement review screen with summary, AI role checker output, and actions (Approve/Reject with message). Sync decisions back to `applications` and `internships`.
- **Logbook Verification:** Side-by-side layout with AI summary JSON on left and embedded PDF viewer on right. Actions set `supervisor_status` and attach comments.

### Admin Module
- **Eligibility Review:** Data table with filters, bulk actions to toggle `is_eligible`, links to documents.
- **Settings → Companies:** CRUD interface for `companies` table with soft delete, search, and status toggles.

### Messaging & Notifications
- Implement database notifications for status changes (document approved/rejected, placement updates). Provide toggles for email summaries.
- Add global “Alerts” dropdown inside `x-app-layout`.

### Database & Data Migration Strategy
- Create migrations per checklist: add `is_eligible` to `users`, create tables for `eligibility_docs`, `applications`, `internships`, `logbook_entries`, `messages`, `announcements`, `notifications`.
- Establish foreign keys and cascading rules; index frequently queried columns (`status`, `week_number`, `faculty_supervisor_id`).
- Seed baseline data for companies, statuses, sample logbooks for demo.

## AI Integration Strategy

### API Option Assessment
| Criteria | Gemini API | Z.AI |
| --- | --- | --- |
| Laravel integration | Official community package (`gemini/laravel`) provides facade/config scaffolding for rapid setup.[1] | OpenAI-compatible REST endpoints with curl/SDK examples; integrate via generic HTTP client.[4] |
| Structured outputs | Native `responseSchema` support guarantees JSON adherence for skills/sentiment payloads on Gemini 2.5 models.[2][3] | Supports standard chat completions; requires manual prompting for strict JSON, though APIs are schema-agnostic.[4] |
| Model strengths | Multimodal, strong reasoning, logging/dashboard via Google AI Studio; generous tooling ecosystem.[2][3] | GLM-4.6 family optimized for coding and long contexts (up to ~200K tokens) with cost-efficient tiers.[5] |
| Recommended use | Primary provider for logbook analysis and role checker to exploit structured JSON guarantees. | Fallback or comparative provider for experimentation, especially for longer placement descriptions or cost-sensitive bursts. |

### Recommended Architecture
1. Livewire component dispatches `AnalyzeLogbook` job after validation.
2. Job pushes payload to queue (Redis) with custom timeout and retries tuned for AI latency.
3. Job invokes selected provider (Gemini default) and stores response JSON in `ai_analysis_json`. If primary provider fails, optionally requeue to `AnalyzeLogbookFallback` targeting Z.AI.
4. Notify faculty and student upon completion; expose AI insights in UI with last-run timestamp.

### Implementation Steps
1. **Provider abstraction:** Create `App\Services\AiClients\AiClientInterface` with implementations for Gemini and Z.AI. Use config flag to toggle default.
2. **Gemini setup:** Install package (`composer require gemini/laravel`), publish config, set `GEMINI_API_KEY` and default model (e.g., `gemini-2.5-pro`). Example call:
   ```php
   $result = \Gemini::geminiPro()->generateContent([
       'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
       'response_mime_type' => 'application/json',
       'response_schema' => $logbookSchema,
   ]);
   ```
   Persist `$result->text()` after decoding.[1][2]
3. **Z.AI client:** Use `Http::withToken(config('services.zai.key'))` to call `https://api.z.ai/api/paas/v4/chat/completions`, mirroring OpenAI payload. Capture `messages[].content` and parse JSON if provided.[4][5]
4. **Prompt engineering:** Define system prompts for consistent tone, supply rubric (skills taxonomy, sentiment scale). Maintain versioned prompt files for transparency.
5. **Queue tuning:** Set `$timeout` and `$tries` on jobs, chunk long-running batches, and log request IDs for traceability.
6. **Audit & storage:** Store raw AI response hashes alongside normalized JSON for review and possible reprocessing.

## AI Pair Programming Workflow
- **Plan-first:** Sketch task outlines yourself, then ask the AI to critique or fill gaps before coding, maintaining architectural intent.[6][7]
- **Treat output as junior PRs:** Review every suggestion line-by-line, request tests, and run them immediately to avoid silent regressions.[6][7]
- **Short, specific prompts:** Reference exact file paths or function names instead of pasting large blobs to preserve context accuracy.[7][8]
- **Test-driven loops:** Have the assistant draft failing tests, validate them manually, then let it propose fixes; rerun suites after each iteration.[6][7]
- **Knowledge sharing:** Record effective prompts, pitfalls, and agreed coding standards in a living `PAIRING.md` to keep future sessions aligned.[8]

## Operational Testing & Monitoring
- Write feature tests covering job dispatch, provider failure handling, and structured JSON validation.
- Use Laravel Horizon or custom metrics dashboards to watch queue throughput and failure rates; adjust timeouts and split long tasks when they approach system limits.[9]
- Configure Supervisor (or Windows Task Scheduler equivalents) to keep workers alive, auto-restart on failure, and scale worker count based on CPU/I/O characteristics.[10]
- Enable Gemini logging dashboards for prompt/response inspection and create synthetic logbooks for regression testing.[2][3]

## Risk Register & Mitigation Plan
| Risk | Trigger/Signal | Mitigation | Owner |
| --- | --- | --- | --- |
| AI provider latency or quota limits | Queue wait times >60s, provider error codes | Implement fallback to Z.AI, cache recent analyses, monitor usage dashboards | AI integration lead |
| Queue saturation | Spiking `AnalyzeLogbook` jobs during submission deadlines | Scale workers horizontally, batch incoming jobs, adjust timeout/tries.[9][10] | DevOps |
| Scope creep in UI polish | Frequent design tweaks late in cycle | Timebox UI refinements per week, log backlog items for post-capstone iteration | Product lead |
| Data privacy & compliance | Sensitive documents stored without access control | Enforce signed URLs, role-based policies, audit logging, data retention policy | Security |
| Student adoption risk | Students skip AI features or fail to upload signed PDFs | Provide onboarding walkthroughs, inline guidance, fallback manual submission channel | Student liaison |

## Documentation & Next Steps
1. Finalize `.env.example`, environment setup guide, and component usage doc by end of Week 2.
2. Start maintaining a change log and prompt registry as soon as AI endpoints go live.
3. Schedule weekly demo reviews (Fridays) to showcase progress against milestones and capture supervisor feedback.
4. Assemble final technical report and user guide during Week 10 alongside presentation slide deck.

## Sources
1. Ruhan Ahmad, “Integrating Google Gemini with Laravel: A Simple Guide,” *Medium*, May 2024. https://medium.com/@abdulbasit_15759/integrating-google-gemini-with-laravel-a-simple-guide-b0b82f031a89
2. Google AI, “Structured Outputs | Gemini API,” Nov 2025. https://ai.google.dev/gemini-api/docs/structured-output
3. Gemini API Team, “Improving Structured Outputs in the Gemini API,” *Google Blog*, Nov 2025. https://blog.google/technology/developers/gemini-api-structured-outputs/
4. Z.AI, “Quick Start – Developer Documentation.” https://docs.z.ai/
5. Z.AI, “Overview – Models & Agents,” Nov 2025. https://docs.z.ai/guides/overview/overview
6. Randy Letona, “AI Coding – Best Practices in 2025,” *DEV Community*, Sept 2025. https://dev.to/ranndy360/ai-coding-best-practices-in-2025-4eel
7. Forge Team, “AI Agent Best Practices: 12 Lessons from AI Pair Programming,” *Forge*, June 2025. https://forgecode.dev/blog/ai-agent-best-practices/
8. Greg Foster, “Best practices for pair programming with AI assistants,” *Graphite*, Nov 2025. https://graphite.com/guides/ai-pair-programming-best-practices
9. Laravel Daily, “Long-Running Jobs: Timeouts, Fatal Errors and a Better Way,” June 2025. https://laraveldaily.com/lesson/queues-laravel/long-running-jobs-timeouts-1
10. Md. Asif Rahman, “Mastering Background Job Processing with Supervisor and Laravel Queues,” *DEV Community*, Jan 2025. https://dev.to/asifzcpe/mastering-background-job-processing-with-supervisor-and-laravel-queues-1onb
