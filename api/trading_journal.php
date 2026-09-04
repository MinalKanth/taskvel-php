<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'error'   => 'Fatal error: ' . $e['message'],
            'file'    => $e['file'],
            'line'    => $e['line'],
        ]);
    }
});

require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/ai.php';

$uid    = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();
$pdo    = db();

switch ("$method:$action") {

    // ================= GOALS =================

    // GET goal for a single month, e.g. ?month=2026-09
    case 'GET:access-status':
        json_response(['access' => trading_journal_access($uid)]);
        break;

    case 'GET:goal':
        $month = valid_month($_GET['month'] ?? '');
        if (!$month) json_response(['error' => 'Invalid or missing month'], 422);
        $stmt = $pdo->prepare('SELECT id, month, target_amount FROM trading_goals WHERE user_id = ? AND month = ?');
        $stmt->execute([$uid, $month]);
        $row = $stmt->fetch();
        json_response(['goal' => $row ? goal_out($row) : null]);
        break;

    // GET all goals ever set (used for the "goal vs achieved" chart across months)
    case 'GET:list-goals':
        $stmt = $pdo->prepare('SELECT id, month, target_amount FROM trading_goals WHERE user_id = ? ORDER BY month ASC');
        $stmt->execute([$uid]);
        json_response(['goals' => array_map('goal_out', $stmt->fetchAll())]);
        break;

    // POST { month: 'YYYY-MM', target_amount: number }  — upsert
    case 'POST:save-goal':
        require_trading_journal_write($uid);
        $month = valid_month($in['month'] ?? '');
        if (!$month) json_response(['error' => 'Invalid or missing month'], 422);
        $target = round((float)($in['target_amount'] ?? 0), 2);
        if ($target < 0 || $target > 100000000) json_response(['error' => 'Target amount out of range'], 422);

        $stmt = $pdo->prepare(
            'INSERT INTO trading_goals (user_id, month, target_amount) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE target_amount = VALUES(target_amount)'
        );
        $stmt->execute([$uid, $month, $target]);
        json_response(['ok' => true]);
        break;

    // ================= DAILY P&L ENTRIES =================

    // GET all entries for the logged-in user (bounded, most recent first).
    // Filtering by date range/preset is done client-side against this set,
    // same approach as loadWork() in my-work.php.
    case 'GET:list-entries':
        $stmt = $pdo->prepare(
            'SELECT id, entry_date, pnl_amount, status, notes
             FROM trading_entries WHERE user_id = ?
             ORDER BY entry_date DESC, id DESC LIMIT 5000'
        );
        $stmt->execute([$uid]);
        json_response(['entries' => array_map('entry_out', $stmt->fetchAll())]);
        break;

    // POST { id?: number, entry_date, pnl_amount, status, notes? } — create or update
    case 'POST:save-entry':
        require_trading_journal_write($uid);
        $date = valid_date($in['entry_date'] ?? '');
        if (!$date) json_response(['error' => 'Invalid or missing entry_date'], 422);

        $status = one_of($in['status'] ?? 'profit', ['profit', 'loss', 'breakeven'], 'profit');
        $amount = round((float)($in['pnl_amount'] ?? 0), 2);
        if (abs($amount) > 100000000) json_response(['error' => 'Amount out of range'], 422);
        $notes  = clean_str($in['notes'] ?? '', 500);
        $id     = (int)($in['id'] ?? 0);

        if ($id > 0) {
            // Update — must belong to this user.
            $stmt = $pdo->prepare(
                'UPDATE trading_entries SET entry_date = ?, pnl_amount = ?, status = ?, notes = ?
                 WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$date, $amount, $status, $notes, $id, $uid]);
            if ($stmt->rowCount() === 0) {
                // Either it doesn't exist or isn't this user's — verify which.
                $chk = $pdo->prepare('SELECT id FROM trading_entries WHERE id = ? AND user_id = ?');
                $chk->execute([$id, $uid]);
                if (!$chk->fetch()) json_response(['error' => 'Entry not found'], 404);
            }
            json_response(['ok' => true, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO trading_entries (user_id, entry_date, pnl_amount, status, notes) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$uid, $date, $amount, $status, $notes]);
            maybe_start_trading_journal_trial($uid);
            json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        }
        break;

    // DELETE ?id=123
    case 'DELETE:delete-entry':
        require_trading_journal_write($uid);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(['error' => 'Missing id'], 422);
        $pdo->prepare('DELETE FROM trading_entries WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
        json_response(['ok' => true]);
        break;

    // ================= DAILY JOURNAL =================

    // GET ?date=YYYY-MM-DD — single day's journal entry
    case 'GET:journal':
        $date = valid_date($_GET['date'] ?? '');
        if (!$date) json_response(['error' => 'Invalid or missing date'], 422);
        $stmt = $pdo->prepare('SELECT id, entry_date, content FROM trading_journal WHERE user_id = ? AND entry_date = ?');
        $stmt->execute([$uid, $date]);
        $row = $stmt->fetch();
        json_response(['journal' => $row ? journal_out($row) : null]);
        break;

    // GET all journal entries (used to show a history list / badge dots on the calendar-like filters)
    case 'GET:list-journal':
        $stmt = $pdo->prepare('SELECT id, entry_date, content FROM trading_journal WHERE user_id = ? ORDER BY entry_date DESC LIMIT 2000');
        $stmt->execute([$uid]);
        json_response(['journal' => array_map('journal_out', $stmt->fetchAll())]);
        break;

    // POST { date, content } — upsert, hard-clamped to 15 lines / 4000 chars server-side
    case 'POST:save-journal':
        require_trading_journal_write($uid);
        $date = valid_date($in['date'] ?? '');
        if (!$date) json_response(['error' => 'Invalid or missing date'], 422);
        $content = clamp_journal_content((string)($in['content'] ?? ''));
        if ($content === '') json_response(['error' => 'Journal entry cannot be empty'], 422);

        $stmt = $pdo->prepare(
            'INSERT INTO trading_journal (user_id, entry_date, content) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE content = VALUES(content)'
        );
        $stmt->execute([$uid, $date, $content]);
        json_response(['ok' => true]);
        break;

    // DELETE ?id=123
    case 'DELETE:delete-journal':
        require_trading_journal_write($uid);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(['error' => 'Missing id'], 422);
        $pdo->prepare('DELETE FROM trading_journal WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
        json_response(['ok' => true]);
        break;
    
    case 'POST:ai-fix-journal':
        require_trading_journal_write($uid);
        $rl = enforce_rate_limit_soft("ai_journal_fix:$uid", 20, 3600);
        if (!$rl['ok']) {
            json_response(['error' => $rl['error'] ?? 'Too many AI requests, please slow down.'], 429);
        }
        rate_limit_hit("ai_journal_fix:$uid", 3600);

        $content = clean_str($in['content'] ?? '', 4000);
        if ($content === '') {
            json_response(['error' => 'Journal text is required.'], 400);
        }

        $result = ai_fix_journal_entry($content);
        if (!$result['ok']) {
            json_response(['error' => $result['error']], 502);
        }

        json_response(['corrected' => $result['corrected']]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}

// ---------------- helpers ----------------

function valid_date(string $s): ?string
{
    $s = trim($s);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return null;
    $parts = explode('-', $s);
    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]) ? $s : null;
}

function valid_month(string $s): ?string
{
    $s = trim($s);
    return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $s) ? $s : null;
}

// Server-side backstop for the client's 15-line limit — clamps to 15 lines
// and 4000 characters regardless of what the client sends.
function clamp_journal_content(string $s): string
{
    $s = clean_str($s, 4000);
    $lines = preg_split('/\r\n|\r|\n/', $s);
    $lines = array_slice($lines, 0, 15);
    return trim(implode("\n", $lines));
}

function goal_out(array $r): array
{
    return [
        'id'            => (int)$r['id'],
        'month'         => $r['month'],
        'target_amount' => (float)$r['target_amount'],
    ];
}

function entry_out(array $r): array
{
    return [
        'id'         => (int)$r['id'],
        'entry_date' => $r['entry_date'],
        'pnl_amount' => (float)$r['pnl_amount'],
        'status'     => $r['status'],
        'notes'      => $r['notes'],
    ];
}

function journal_out(array $r): array
{
    return [
        'id'         => (int)$r['id'],
        'entry_date' => $r['entry_date'],
        'content'    => $r['content'],
    ];
}