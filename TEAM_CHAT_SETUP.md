# Team Chat — Setup

A chat box was added to each team's page (`team.php`) so members can talk
without leaving the app. Messages are kept for **7 days**, then
automatically deleted — no cron job needed, it cleans itself up.

## 1. Run the migration
This feature needs one new database table. Run:

```
mysql -u your_db_user -p your_db_name < sql/migration_15_team_chat.sql
```

(Uses the same `taskvel_php` database name as the rest of the app — edit
the `USE taskvel_php;` line at the top of the file first if your database
is named differently.)

## 2. Upload the files
- `api/team_chat.php` — new backend endpoint (list + send)
- `team.php` — updated with the chat UI, CSS, and JS

Nothing else to configure — this feature doesn't use the Gemini API key or
any of the other AI setup, it's plain PHP + MySQL.

## 3. How it works
- **Not a true push/websocket chat** — this app runs on plain PHP hosting,
  which typically can't run a persistent socket server. Instead, the chat
  polls for new messages every 3 seconds, which in practice reads as live:
  a message sent by a teammate shows up within a few seconds without
  anyone refreshing the page.
- **7-day retention**: every time anyone sends a message or the chat list
  is loaded, the backend deletes any messages older than 7 days for that
  team first. There's no separate cleanup job to schedule — the table
  naturally stays small on its own.
- **Access control**: only members of a team can read or post in that
  team's chat (checked server-side via the same `team_role()` function
  used everywhere else in the app).
- **Rate limit**: 30 messages/minute per person, just as a safety net
  against accidental spam (stuck key, double-click, etc.) — not a real
  limit on normal conversation.

## 4. Customizing retention
To change the 7-day window, edit one line in `api/team_chat.php`:

```php
const CHAT_RETENTION_DAYS = 7;
```
