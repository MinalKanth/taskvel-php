<?php
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/mailer.php';

/**
 * Who should be told about a progress update on a team task: the task's
 * creator (the "reporting senior" in the common case), plus the team owner
 * if they're someone different — covers "send updates to their reporting
 * senior or team owner" without needing a separate configurable
 * org-chart/manager field. Never includes the person who made the update.
 *
 * @return array<int,array{id:int,name:string,email:string}>
 */
function team_task_update_recipients(PDO $pdo, int $teamId, int $creatorId, int $actorId): array
{
    $stmt = $pdo->prepare(
        "SELECT DISTINCT u.id, u.name, u.email
         FROM users u
         WHERE u.id = ?
            OR u.id = (SELECT user_id FROM team_members WHERE team_id = ? AND role = 'owner' LIMIT 1)"
    );
    $stmt->execute([$creatorId, $teamId]);
    $recipients = array_values(array_filter($stmt->fetchAll(), fn($r) => (int)$r['id'] !== $actorId));
    return $recipients;
}

/**
 * Records a progress-update row and notifies the recipients above
 * (in-app always; email only if the recipient has notify_task_updates_email
 * enabled). Both notification channels are best-effort — a failure here
 * never rolls back the update itself that the assignee just submitted.
 */
function notify_team_task_update(PDO $pdo, array $task, int $actorId, string $statusTo, int $progressTo, ?string $notes): void
{
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$actorId]);
    $updaterName = $stmt->fetchColumn() ?: 'A teammate';

    $link = './team.php?id=' . $task['team_id'] . '#team-tasks';
    $statusLabel = ucwords(str_replace('_', ' ', $statusTo));
    $recipients = team_task_update_recipients($pdo, (int)$task['team_id'], (int)$task['created_by'], $actorId);

    foreach ($recipients as $r) {
        try {
            create_notification(
                $pdo, (int)$r['id'], 'team_task_update',
                'Task update: ' . $task['title'],
                "$updaterName set it to $statusLabel ($progressTo%)" . ($notes ? " — \"$notes\"" : ''),
                $link, (int)$task['id']
            );
        } catch (Throwable $e) { /* in-app notification is best-effort */ }

        try {
            $stmt2 = $pdo->prepare('SELECT COALESCE(notify_task_updates_email, 1) FROM users WHERE id = ?');
            $stmt2->execute([$r['id']]);
            if ((int)$stmt2->fetchColumn() === 1) {
                send_task_update_email($r['email'], $r['name'], $updaterName, $task['title'], $statusTo, $progressTo, $notes, $link);
            }
        } catch (Throwable $e) { /* email is best-effort — never block the update */ }
    }
}