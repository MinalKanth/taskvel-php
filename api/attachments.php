<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/teams.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

const MAX_SIZE = 10 * 1024 * 1024; // 10MB per file

// Allowlist maps a *detected* (not client-supplied) MIME type to the file
// extension it's stored with — closes the classic "upload a .php disguised
// as image/png via a spoofed Content-Type header" hole, since the browser's
// claimed type is never trusted here.
const ALLOWED_MIME_EXT = [
    'image/png'  => 'png',
    'image/jpeg' => 'jpg',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
    'text/plain' => 'txt',
];

function task_owned_or_editable(PDO $pdo, int $taskId, int $uid): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM tasks WHERE id = ? AND owner_id = ?
                            UNION
                            SELECT 1 FROM task_shares WHERE task_id = ? AND shared_with_user_id = ? AND status='accepted' AND permission='edit'");
    $stmt->execute([$taskId, $uid, $taskId, $uid]);
    return (bool)$stmt->fetch();
}

// A team task's assignee (submitting a progress update) or a team
// manager/owner may attach files to it.
function team_task_editable(PDO $pdo, int $teamTaskId, int $uid): bool
{
    $stmt = $pdo->prepare('SELECT team_id, assignee_id FROM team_tasks WHERE id = ?');
    $stmt->execute([$teamTaskId]);
    $t = $stmt->fetch();
    if (!$t) return false;
    return $t['assignee_id'] == $uid || can_manage_team((int)$t['team_id'], $uid);
}

// A project (board) task's assignee, creator, or a team manager/owner may
// attach files to it — same rule as project_task_edit_context() in
// includes/teams.php, reimplemented locally to avoid a circular include.
function project_task_editable(PDO $pdo, int $projectTaskId, int $uid): bool
{
    $stmt = $pdo->prepare('SELECT p.team_id, pt.assignee_id, pt.created_by FROM project_tasks pt
                            JOIN projects p ON p.id = pt.project_id WHERE pt.id = ?');
    $stmt->execute([$projectTaskId]);
    $t = $stmt->fetch();
    if (!$t) return false;
    return $t['assignee_id'] == $uid || $t['created_by'] == $uid || can_manage_team((int)$t['team_id'], $uid);
}

switch ("$method:$action") {

    case 'POST:upload':
        enforce_rate_limit("upload:$uid", 20, 3600); // 20 uploads/hour/user

        $taskId = !empty($_POST['task_id']) ? (int)$_POST['task_id'] : null;
        $teamTaskId = !empty($_POST['team_task_id']) ? (int)$_POST['team_task_id'] : null;
        $projectTaskId = !empty($_POST['project_task_id']) ? (int)$_POST['project_task_id'] : null;
        if (!$taskId && !$teamTaskId && !$projectTaskId) json_response(['error' => 'task_id, team_task_id, or project_task_id is required'], 422);
        if ($taskId && !task_owned_or_editable($pdo, $taskId, $uid)) json_response(['error' => 'Forbidden'], 403);
        if ($teamTaskId && !team_task_editable($pdo, $teamTaskId, $uid)) json_response(['error' => 'Forbidden'], 403);
        if ($projectTaskId && !project_task_editable($pdo, $projectTaskId, $uid)) json_response(['error' => 'Forbidden'], 403);
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) json_response(['error' => 'No file uploaded'], 422);

        $file = $_FILES['file'];
        if ($file['size'] > MAX_SIZE) json_response(['error' => 'File exceeds 10MB limit'], 422);

        // SECURITY FIX: never trust $_FILES[...]['type'] (attacker-controlled
        // Content-Type from the multipart request) — detect the real MIME
        // type by sniffing the file's actual bytes instead.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(ALLOWED_MIME_EXT[$realMime])) {
            json_response(['error' => 'File type not allowed'], 422);
        }
        $ext = ALLOWED_MIME_EXT[$realMime];

        $dirKey = $taskId ? "task-$taskId" : ($teamTaskId ? "team-task-$teamTaskId" : "project-task-$projectTaskId");
        $dir = __DIR__ . '/../uploads/' . $dirKey;
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Prevent script-execution even if a future ALLOWED_MIME_EXT entry
        // or server misconfiguration were to make this dir web-executable.
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "php_flag engine off\nAddHandler cgi-script .php .php3 .php4 .php5 .phtml\nOptions -ExecCGI -Indexes\n");
        }

        // SECURITY FIX: the stored filename is now fully server-generated
        // (random, with an extension derived from the *detected* MIME type)
        // — never derived from the attacker-supplied original filename, which
        // closes off path traversal, null-byte, and double-extension tricks.
        // The original name is kept only as display metadata (HTML-escaped
        // wherever it's rendered).
        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) json_response(['error' => 'Upload failed'], 500);

        $relPath = 'uploads/' . $dirKey . '/' . $safeName;
        $originalName = clean_str($file['name'] ?? 'file', 190);
        $stmt = $pdo->prepare('INSERT INTO attachments (task_id, team_task_id, project_task_id, uploaded_by, file_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$taskId, $teamTaskId, $projectTaskId, $uid, $originalName, $relPath, $file['size'], $realMime]);

        audit_log($uid, 'file_uploaded', ['task_id' => $taskId, 'team_task_id' => $teamTaskId, 'project_task_id' => $projectTaskId, 'mime' => $realMime, 'size' => $file['size']]);

        json_response(['ok' => true, 'attachment' => [
            'id' => (int)$pdo->lastInsertId(), 'file_name' => $originalName, 'file_path' => $relPath, 'file_size' => $file['size'],
        ]], 201);
        break;

    case 'GET:list':
        $taskId = (int)($_GET['task_id'] ?? 0);
        if (!task_owned_or_editable($pdo, $taskId, $uid)) json_response(['error' => 'Forbidden'], 403);
        $stmt = $pdo->prepare('SELECT a.* FROM attachments a WHERE a.task_id = ?');
        $stmt->execute([$taskId]);
        json_response(['attachments' => $stmt->fetchAll()]);
        break;

    case 'GET:list-for-project-task':
        $projectTaskId = (int)($_GET['project_task_id'] ?? 0);
        if (!project_task_editable($pdo, $projectTaskId, $uid)) {
            // Fall back to read access: any team member can view a project task's files, even if only managers/assignee can add/remove them.
            $stmt = $pdo->prepare('SELECT p.team_id FROM project_tasks pt JOIN projects p ON p.id = pt.project_id WHERE pt.id = ?');
            $stmt->execute([$projectTaskId]);
            $teamId = $stmt->fetchColumn();
            if (!$teamId || !team_role((int)$teamId, $uid)) json_response(['error' => 'Forbidden'], 403);
        }
        $stmt = $pdo->prepare('SELECT a.*, u.name AS uploaded_by_name FROM attachments a
                               LEFT JOIN users u ON u.id = a.uploaded_by WHERE a.project_task_id = ? ORDER BY a.created_at ASC');
        $stmt->execute([$projectTaskId]);
        json_response(['attachments' => $stmt->fetchAll()]);
        break;

    case 'GET:list-for-update':
        $updateId = (int)($_GET['team_task_update_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT tt.team_id FROM team_task_updates ttu JOIN team_tasks tt ON tt.id = ttu.team_task_id WHERE ttu.id = ?');
        $stmt->execute([$updateId]);
        $teamId = $stmt->fetchColumn();
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_member((int)$teamId);
        $stmt = $pdo->prepare('SELECT a.* FROM attachments a WHERE a.team_task_update_id = ?');
        $stmt->execute([$updateId]);
        json_response(['attachments' => $stmt->fetchAll()]);
        break;

    case 'DELETE:delete':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM attachments WHERE id = ?');
        $stmt->execute([$id]);
        $a = $stmt->fetch();
        if (!$a) json_response(['error' => 'Not found'], 404);
        $canDelete = $a['uploaded_by'] == $uid;
        if (!$canDelete && $a['project_task_id']) {
            $stmt = $pdo->prepare('SELECT p.team_id FROM project_tasks pt JOIN projects p ON p.id = pt.project_id WHERE pt.id = ?');
            $stmt->execute([$a['project_task_id']]);
            $teamId = $stmt->fetchColumn();
            $canDelete = $teamId && can_manage_team((int)$teamId, $uid);
        }
        if (!$canDelete) json_response(['error' => 'Forbidden'], 403);
        // Path is entirely server-generated (see upload above), but resolve
        // and re-verify it stays inside the uploads directory before
        // deleting, as a defense-in-depth guard against any future regression.
        $uploadsRoot = realpath(__DIR__ . '/../uploads');
        $target = realpath(__DIR__ . '/../' . $a['file_path']);
        if ($target && $uploadsRoot && str_starts_with($target, $uploadsRoot)) {
            @unlink($target);
        }
        $pdo->prepare('DELETE FROM attachments WHERE id = ?')->execute([$id]);
        audit_log($uid, 'file_deleted', ['attachment_id' => $id]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}