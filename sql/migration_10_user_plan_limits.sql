USE taskvel_php;

-- ------------------------------------------------------------
-- Feature 2: per-user team/member limits.
--
-- `users.plan` ('free' | 'pro', added in migration_02) already exists but
-- nothing reads it yet — team creation limits were being checked against
-- teams.plan (a *team's own* billing tier) instead of the *user's*
-- personal Taskvel Pro plan. Per the spec, "Free users: max 2 teams /
-- 5 members" and "Pro users: unlimited" is about the account holder,
-- not the team, so we key these limits off users.plan and keep the
-- existing teams.plan / plan_limits system as an independent, more
-- granular tier a specific team can additionally be upgraded to
-- (e.g. a free-plan user's team that a sponsor pays for separately).
-- ------------------------------------------------------------
ALTER TABLE plan_limits
    ADD COLUMN max_teams INT UNSIGNED NOT NULL DEFAULT 1 AFTER plan;

UPDATE plan_limits SET max_teams = 2,      max_members = 5      WHERE plan = 'free';
UPDATE plan_limits SET max_teams = 999999, max_members = 999999 WHERE plan = 'pro';
UPDATE plan_limits SET max_teams = 999999                       WHERE plan = 'business';

-- ------------------------------------------------------------
-- Bug fix carried over from Feature 1: removing/leaving a team already
-- unassigned that member's project_tasks, but not their team_tasks
-- (which didn't exist yet when that code was written). Without this,
-- a removed member could still show up as the assignee on a team task.
-- No schema change needed here — see api/teams.php for the corresponding
-- code fix — this comment just documents why remove-member/leave now
-- touch team_tasks too, in case the query plan is inspected later.
-- ------------------------------------------------------------