USE taskvel;

-- Passwords below are all: "password123"  (bcrypt hash generated via PHP password_hash)
INSERT INTO users (id, name, email, password_hash, accent_color, theme) VALUES
(1, 'Rohet Kevat', 'rohet@example.com', '$2y$10$WqZ8mR8x0f9C2E0dK8G3xOe6qk9Fv1n1oQvXk9E4c8b3f1o9m5G9K', '#0a0a0a', 'dark'),
(2, 'Jane Smith',  'jane@example.com',  '$2y$10$WqZ8mR8x0f9C2E0dK8G3xOe6qk9Fv1n1oQvXk9E4c8b3f1o9m5G9K', '#2563eb', 'light'),
(3, 'Kuljeet Singh','kuljeet@example.com','$2y$10$WqZ8mR8x0f9C2E0dK8G3xOe6qk9Fv1n1oQvXk9E4c8b3f1o9m5G9K', '#16a34a', 'system');

INSERT INTO tags (id, user_id, name, color) VALUES
(1, 1, 'Work', '#2563eb'),
(2, 1, 'Personal', '#f59e0b'),
(3, 1, 'Urgent', '#dc2626'),
(4, 2, 'Client', '#7c3aed');

INSERT INTO tasks (id, owner_id, title, description, status, priority, urgent, important, due_date, due_time, pinned, position) VALUES
(1, 1, 'Finish Taskvel permissions module', 'Wire up PermissionController CRUD + AJAX user loading', 'in_progress', 'high', 1, 1, '2026-07-10', '18:00:00', 1, 1),
(2, 1, 'Fix GitHub README contribution art', 'Yearly contributions feature still broken', 'todo', 'medium', 0, 1, '2026-07-12', NULL, 0, 2),
(3, 1, 'Submit QA invoice A00011', 'Anytime Telehealth June work log', 'done', 'high', 1, 1, '2026-07-01', NULL, 0, 3),
(4, 1, 'Review Wispr FCM raw HTTP v1 integration', NULL, 'todo', 'low', 0, 0, '2026-07-20', NULL, 0, 4),
(5, 2, 'Prepare client proposal deck', 'Slides for Biome Enterprises', 'todo', 'high', 1, 1, '2026-07-09', '12:00:00', 0, 1);

UPDATE tasks SET completed_at = '2026-07-01 17:30:00' WHERE id = 3;

INSERT INTO task_steps (task_id, title, done, position) VALUES
(1, 'Build PermissionsSeeder', 1, 1),
(1, 'PermissionController CRUD', 1, 2),
(1, 'DataTable view', 0, 3),
(1, 'Dynamic assignment page + AJAX', 0, 4),
(5, 'Draft outline', 1, 1),
(5, 'Design slides', 0, 2);

INSERT INTO task_tags (task_id, tag_id) VALUES
(1, 1), (1, 3),
(2, 1),
(3, 1),
(5, 4);

INSERT INTO remarks (task_id, user_id, body, is_draft) VALUES
(1, 1, 'AJAX user loading endpoint done, testing now.', 0),
(1, 2, 'Looks good, ping me when the DataTable view is ready.', 0),
(5, 2, 'Client wants navy branding, updating deck now.', 0);

-- Share task #1 with Jane (already accepted) and invite a brand-new email (pending)
INSERT INTO task_shares (task_id, owner_id, shared_with_user_id, invite_email, permission, status, invite_token, responded_at) VALUES
(1, 1, 2, 'jane@example.com', 'edit', 'accepted', 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4', '2026-07-02 10:00:00');

INSERT INTO task_shares (task_id, owner_id, shared_with_user_id, invite_email, permission, status, invite_token) VALUES
(2, 1, NULL, 'newcollaborator@example.com', 'view', 'pending', 'f6e5d4c3b2a1f6e5d4c3b2a1f6e5d4c3');

INSERT INTO notifications (user_id, type, title, body, task_id, is_read) VALUES
(1, 'comment', 'Jane commented on "Finish Taskvel permissions module"', 'Looks good, ping me when the DataTable view is ready.', 1, 0),
(1, 'due_soon', 'Task due tomorrow', '"Prepare client proposal deck" style tasks you share are due soon', NULL, 1),
(2, 'shared', 'Rohet shared a task with you', '"Finish Taskvel permissions module" — edit access', 1, 0);

INSERT INTO time_logs (task_id, user_id, tag, started_at, ended_at, duration_seconds) VALUES
(1, 1, 'Work', '2026-07-05 09:00:00', '2026-07-05 10:30:00', 5400),
(1, 1, 'Work', '2026-07-05 14:00:00', '2026-07-05 15:00:00', 3600),
(3, 1, 'QA', '2026-06-30 09:00:00', '2026-06-30 11:00:00', 7200);

INSERT INTO focus_sessions (user_id, task_id, mode, ambient_sound, duration_seconds, started_at, ended_at, completed) VALUES
(1, 1, 'focus', 'rain', 1500, '2026-07-05 09:00:00', '2026-07-05 09:25:00', 1),
(1, 1, 'short_break', NULL, 300, '2026-07-05 09:25:00', '2026-07-05 09:30:00', 1),
(1, 4, 'deep_work', 'cafe', 3000, '2026-07-04 16:00:00', '2026-07-04 16:50:00', 1);

INSERT INTO streaks (user_id, current_streak, longest_streak, last_active_date) VALUES
(1, 5, 12, '2026-07-06'),
(2, 2, 6,  '2026-07-05');

INSERT INTO templates (user_id, name, payload) VALUES
(1, 'Bug fix checklist', JSON_OBJECT('title','Fix: ','priority','high','steps', JSON_ARRAY('Reproduce','Root cause','Patch','Test','Deploy'))),
(1, 'Client onboarding', JSON_OBJECT('title','Onboard: ','priority','medium','steps', JSON_ARRAY('Kickoff call','Access setup','Send welcome doc')));

INSERT INTO activity_log (user_id, task_id, action, meta) VALUES
(1, 1, 'created', JSON_OBJECT('title','Finish Taskvel permissions module')),
(1, 1, 'shared', JSON_OBJECT('with','jane@example.com')),
(1, 3, 'completed', JSON_OBJECT('title','Submit QA invoice A00011')),
(2, 1, 'commented', JSON_OBJECT('excerpt','Looks good, ping me...'));
