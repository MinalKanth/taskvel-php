# AI Smart Task Suggestions — Setup

This adds a **🤖 AI Suggest** button to the "New task" sheet in `taskvel-pro.php`.
Click it after typing a task name and it fills in urgency, impact, a deadline,
a time estimate, up to 3 subtasks, and up to 3 tags — using Google's **Gemini
API free tier** (no credit card, no payment).

## 1. Get a free API key
1. Go to https://aistudio.google.com/apikey
2. Sign in with any Google account
3. Click "Create API key" and copy it

## 2. Add it to your app
Open `.env` (already has a placeholder at the bottom) and paste your key:

```
GEMINI_API_KEY=your_key_here
```

That's it — no other config, no composer packages, nothing to install.
PHP calls Gemini directly over HTTPS using `curl`, which is already built
into PHP.

## 3. What was added
- `config/ai.php` — reads `GEMINI_API_KEY` from `.env`
- `includes/ai.php` — `ai_suggest_task($name, $note)` calls Gemini and
  returns clean, validated JSON
- `api/ai_suggest.php` — authenticated endpoint (used by `taskvel-pro.php`):
  `POST /api/ai_suggest.php?action=suggest` with body `{ name, note }`,
  rate-limited per logged-in user (20/hour)
- `api/ai_suggest_public.php` — **public** endpoint (used by
  `taskvel-free.php`, which has no login system at all). Same request
  shape, but rate-limited per **IP address** instead (8/hour) since there's
  no user account to key off. Keep this in mind: it's reachable by anyone
  who finds the URL, not just people using your app.
- `taskvel-pro.php` and `taskvel-free.php` — both got a 🤖 AI Suggest
  button + `aiSuggestTask()` JS function wired into their "New task" sheet
- `taskvel-premium.php` was **not** touched — it's a marketing/landing page,
  not the actual app (no task form, no backend calls at all).

## 4. Free tier limits
Gemini's free tier (as of writing) allows a healthy number of requests per
minute/day per API key. The endpoint also rate-limits each user to 20
AI-suggest calls/hour server-side so nobody accidentally burns your quota.
If you hit the free limit, requests will just fail with a friendly error —
nothing breaks, no charges happen.

## 5. Reusing this in other pages
`ai_suggest_task()` and `ai_daily_focus()` in `includes/ai.php` are plain
PHP functions — call them from `project.php`, `team.php`, etc. the same
way, or add new functions in that file for other AI features.

## 6. Feature 2: Daily Focus Briefing
Both apps already had a rule-based "Good morning, here's your day" popup.
It now also asks AI for a short, warm 2-3 sentence focus tip based on your
open tasks (priority + deadlines only — no other task details are sent).

- `ai_daily_focus()` in `includes/ai.php` — builds the prompt & calls Gemini
- `api/ai_focus.php` — authenticated version (10/hour per user)
- `api/ai_focus_public.php` — public version for `taskvel-free.php`
  (6/hour per IP)
- The AI line loads in automatically a moment after the popup appears, and
  there's also a "🤖 Ask AI what to focus on" button to re-run it any time.
  If the AI call fails for any reason, the existing rule-based briefing
  still shows normally — nothing breaks.

## 7. Feature 3: Natural Language Quick Add
A new ✨ input bar sits above the normal search/add-task toolbar on both
apps. Type a whole task in plain English — e.g. "Submit GST report next
Friday, high priority" — and press Enter or tap ✨. AI extracts a clean
title, urgency, impact, deadline, time estimate, subtasks, and tags, and
creates the task immediately (no form to fill in).

- `ai_parse_task_from_text()` in `includes/ai.php` — builds the prompt &
  calls Gemini, returns a clean structured task
- `api/ai_parse_task.php` — authenticated version (20/hour per user)
- `api/ai_parse_task_public.php` — public version for `taskvel-free.php`
  (8/hour per IP)
- The existing "+" button and full "New task" sheet are untouched — this is
  an additional, faster way to add tasks, not a replacement.

## 8. Extended to Project & Team tasks
`project.php` (project task modal) and `team.php` (assign-a-task modal) now
also have a 🤖 AI Suggest button under the description field. It reuses the
same `api/ai_suggest.php` endpoint from Feature 1 — no new backend files —
and fills in priority, due date, and appends a "Suggested subtasks" list to
the description (these forms don't have separate steps/tags fields, so
subtask ideas are appended as text you can turn into real subtasks after
saving).

Note: `team.php`'s priority dropdown uses low/medium/high/**urgent**, unlike
the low/medium/high/**critical** used everywhere else — the JS maps
"critical" → "urgent" automatically when applying the AI suggestion.

`taskvel-premium.php` and the Daily Focus Briefing / Quick Add features were
**not** extended to project/team tasks — those stayed personal-task-only
since project/team tasks don't have a comparable "your day" view.

## 9. Natural Language Quick Add extended to Project & Team tasks
Same ✨ quick-add idea as Feature 3, now also on `project.php` (above the
board) and `team.php` (in the Team Tasks section). Type a plain-English
task and it's created directly via the existing `api/project_tasks.php` /
`api/team_tasks.php` create actions — reuses the same `api/ai_parse_task.php`
endpoint from Feature 3, no new backend files.

- Project tasks: AI-suggested subtasks are added as real subtasks via
  `subtask-add` (project tasks support real subtasks).
- Team tasks: there's no subtasks concept, so suggested subtasks are
  appended into the description as a checklist-style text list instead.
- Team task priority is mapped critical → urgent, same as Feature 8.

## 10. End-of-Day AI Report Summary
`checkin.php`'s check-out flow (`api/workday.php`) now generates a short
2-3 sentence "day in review" paragraph from the tasks you logged, worked
time, and your own checkout notes.

- `ai_workday_summary()` in `includes/ai.php` — builds the prompt & calls Gemini
- `api/workday.php` — calls it during `POST:checkout`, rate-limited 10/hour
  per user as a safety net (checkout normally happens once a day)
- `includes/mailer.php` — `send_daily_report_email()` gained an optional
  `$aiSummary` parameter; when present, it's shown as a highlighted 🤖
  paragraph at the top of the report email. Fully backward compatible —
  the parameter defaults to `null` and every other caller/behavior is
  unchanged.
- `checkin.php` — the on-screen "Today's summary" card also shows the same
  paragraph after you check out, not just the email.
- If the AI call fails or isn't configured, checkout still completes
  normally and the email/summary just don't include the extra paragraph —
  nothing blocks or breaks.

## 11. Premium gating: Daily AI quota by plan
Every AI feature (Suggest, Quick Add, Daily Focus, Workday Summary) now
shares one daily usage quota tied to your existing plan system:

- **Free plan**: 3 AI actions/day, combined across every AI feature.
- **Pro / Business**: effectively unlimited (999,999/day).

Once a free-plan user hits their daily limit, they get a clear message —
*"You've used today's 3 free AI actions. Upgrade to Taskvel Pro for
unlimited AI."* — with an `upgrade_required` flag in the response your
frontend can use to show an upgrade prompt/link to `billing.php`.

- `sql/migration_16_ai_plan_limits.sql` — adds `max_ai_daily` to your
  existing `plan_limits` table (3 for free, unlimited for pro/business)
- `ai_consume_daily_quota()` / `ai_daily_usage_count()` in `includes/ai.php`
  — reuses your existing `rate_limits` table and `user_plan()`/
  `plan_limits()` functions, no new tracking table needed
- Wired into `api/ai_suggest.php`, `api/ai_focus.php`, `api/ai_parse_task.php`,
  and the workday checkout AI summary — **all of these already power**
  `taskvel-pro.php`, `project.php`, and `team.php`, so the quota
  automatically applies everywhere those are used, with no per-page changes.
- The **existing per-feature hourly rate limits stay in place unchanged**
  as an abuse safety net on top of the new daily plan quota — the two work
  together, not instead of each other.
- The public `*_public.php` endpoints (`taskvel-free.php`, which has no
  login/plan at all) are **not** affected — they keep their existing
  IP-based limits only.
- To change the free-plan daily number, just update `plan_limits.max_ai_daily`
  for the `free` row (or edit the `UPDATE` statement before running the
  migration).

