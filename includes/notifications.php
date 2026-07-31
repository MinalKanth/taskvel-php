<?php
require_once __DIR__ . '/auth.php';

/**
 * Create a single in-app notification row for one user.
 *
 * Centralizes what api/share.php and api/remarks.php previously did with
 * inline INSERTs, and generalizes it with $linkUrl / $teamTaskId so new
 * features (team tasks, task-update timelines, trial reminders) don't
 * need their own ad-hoc notification code or new schema columns.
 *
 * This is intentionally best-effort: callers should wrap in try/catch
 * (matching the existing send_web_push_to_user() convention) so a
 * notification failure never blocks the underlying action.
 */
function create_notification(
    PDO $pdo,
    int $userId,
    string $type,
    string $title,
    ?string $body = null,
    ?string $linkUrl = null,
    ?int $teamTaskId = null,
    ?int $taskId = null
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, title, body, task_id, team_task_id, link_url)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $title, $body, $taskId, $teamTaskId, $linkUrl]);
}