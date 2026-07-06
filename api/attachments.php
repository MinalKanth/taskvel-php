<?php
require_once __DIR__ . '/../includes/auth.php';
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

switch ("$method:$action") {

    case 'POST:upload':
        enforce_rate_limit("upload:$uid", 20, 3600); // 20 uploads/hour/user

        $taskId = (int)($_POST['task_id'] ?? 0);
        if (!task_owned_or_editable($pdo, $taskId, $uid)) json_response(['error' => 'Forbidden'], 403);
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

        $dir = __DIR__ . '/../uploads/' . $taskId;
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

        $relPath = 'uploads/' . $taskId . '/' . $safeName;
        $originalName = clean_str($file['name'] ?? 'file', 190);
        $stmt = $pdo->prepare('INSERT INTO attachments (task_id, uploaded_by, file_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$taskId, $uid, $originalName, $relPath, $file['size'], $realMime]);

        audit_log($uid, 'file_uploaded', ['task_id' => $taskId, 'mime' => $realMime, 'size' => $file['size']]);

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

    case 'DELETE:delete':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM attachments WHERE id = ? AND uploaded_by = ?');
        $stmt->execute([$id, $uid]);
        $a = $stmt->fetch();
        if (!$a) json_response(['error' => 'Not found'], 404);
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
