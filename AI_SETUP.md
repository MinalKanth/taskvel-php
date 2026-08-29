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

