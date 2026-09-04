<?php
require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/billing.php';

/**
 * Enforce the per-day AI usage quota tied to the user's plan
 * (plan_limits.max_ai_daily). Free-plan users share one small daily
 * allowance across ALL AI features combined — suggestions, quick add,
 * daily focus, workday summary; Pro/Business get an effectively unlimited
 * allowance. This sits ON TOP OF each endpoint's existing per-feature
 * hourly rate limit, which stays in place purely as an abuse safety net.
 *
 * Call this once per AI action, right before doing the actual AI work.
 * On success it also records the usage — don't call rate_limit_hit again
 * for the same request.
 *
 * Returns ['ok'=>true, 'remaining'=>int, 'limit'=>int] or
 *         ['ok'=>false, 'error'=>string, 'upgrade_required'=>true, 'limit'=>int].
 */
function ai_consume_daily_quota(int $uid): array
{
    $plan = user_plan($uid);
    $limits = plan_limits($plan);
    $max = (int)($limits['max_ai_daily'] ?? 3);

    if (!rate_limit_check("ai_daily:$uid", $max, 86400)) {
        return [
            'ok' => false,
            'limit' => $max,
            'error' => "You've used today's $max free AI action" . ($max === 1 ? '' : 's') . ". Upgrade to Taskvel Pro for unlimited AI.",
            'upgrade_required' => true,
        ];
    }
    rate_limit_hit("ai_daily:$uid", 86400);

    return ['ok' => true, 'limit' => $max, 'remaining' => max(0, $max - ai_daily_usage_count($uid))];
}

// How many of today's AI actions this user has already used, regardless
// of plan — reads the same rate_limits row ai_consume_daily_quota() writes
// to, so it always reflects the true current count.
function ai_daily_usage_count(int $uid): int
{
    try {
        $stmt = db()->prepare('SELECT attempts, window_start FROM rate_limits WHERE rl_key = ?');
        $stmt->execute(["ai_daily:$uid"]);
        $row = $stmt->fetch();
        if (!$row) return 0;
        if ((time() - strtotime($row['window_start'])) > 86400) return 0;
        return (int)$row['attempts'];
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Ask Gemini for smart suggestions (urgency, impact, deadline, estimate,
 * steps, tags) for a task, given just its name (and optional note).
 *
 * Returns an array like:
 *   [
 *     'ok' => true,
 *     'urgency' => 'high',            // critical|high|medium|low
 *     'damage'  => 'moderate',        // severe|moderate|minor
 *     'deadline' => '2026-09-05',     // YYYY-MM-DD or null
 *     'estimateMins' => 30,           // int or null
 *     'steps' => ['Do X', 'Do Y'],    // up to 3 short strings
 *     'tags' => ['work', 'urgent'],   // up to 3 short strings
 *   ]
 * or ['ok' => false, 'error' => '...'] on failure. Never throws — callers
 * can always fall back to manual input if 'ok' is false.
 */
function ai_suggest_task(string $taskName, string $note = ''): array
{
    if (GEMINI_API_KEY === '') {
        return ['ok' => false, 'error' => 'AI is not configured yet (missing GEMINI_API_KEY).'];
    }
    $taskName = trim($taskName);
    if ($taskName === '') {
        return ['ok' => false, 'error' => 'No task name given.'];
    }

    $today = date('Y-m-d (D)');
    $prompt = <<<PROMPT
You are helping organize a to-do list app. Today's date is $today.

Task: "{$taskName}"
Extra notes: "{$note}"

Reply with ONLY a compact JSON object (no markdown, no code fences, no
commentary) with exactly these keys:
{
  "urgency": one of "critical" | "high" | "medium" | "low",
  "damage": one of "severe" | "moderate" | "minor"   (impact if this drags on),
  "deadline": a realistic "YYYY-MM-DD" date if the task implies one, else null,
  "estimateMins": a realistic whole number of minutes this task takes, else null,
  "steps": an array of up to 3 short (max 6 words) actionable subtask strings,
  "tags": an array of up to 3 short single-word lowercase tags
}
PROMPT;

    $payload = [
        'contents' => [[
            'parts' => [['text' => $prompt]],
        ]],
        'generationConfig' => [
            'temperature' => 0.4,
            'maxOutputTokens' => 300,
            'responseMimeType' => 'application/json',
        ],
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'error' => "Could not reach AI service: $err"];
    }
    $resp = json_decode($raw, true);
    if ($httpCode !== 200 || !is_array($resp)) {
        $msg = $resp['error']['message'] ?? "AI service returned HTTP $httpCode";
        return ['ok' => false, 'error' => $msg];
    }

    $text = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $text = trim($text);
    // Strip accidental ```json fences even though we asked for raw JSON.
    $text = preg_replace('/^```(?:json)?|```$/m', '', $text);
    $data = json_decode(trim($text), true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'AI returned an unexpected response.'];
    }

    $urgency = in_array($data['urgency'] ?? '', ['critical', 'high', 'medium', 'low'], true)
        ? $data['urgency'] : null;
    $damage = in_array($data['damage'] ?? '', ['severe', 'moderate', 'minor'], true)
        ? $data['damage'] : null;
    $deadline = null;
    if (!empty($data['deadline']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['deadline'])) {
        $deadline = $data['deadline'];
    }
    $estimate = isset($data['estimateMins']) && is_numeric($data['estimateMins'])
        ? max(0, (int)$data['estimateMins']) : null;
    $steps = array_slice(array_filter(array_map('strval', $data['steps'] ?? [])), 0, 3);
    $tags = array_slice(array_filter(array_map('strval', $data['tags'] ?? [])), 0, 3);

    return [
        'ok' => true,
        'urgency' => $urgency,
        'damage' => $damage,
        'deadline' => $deadline,
        'estimateMins' => $estimate,
        'steps' => array_values($steps),
        'tags' => array_values($tags),
    ];
}

/**
 * Ask Gemini for a short, natural-language "here's what to focus on today"
 * paragraph, given a compact summary of the user's open tasks. Never sends
 * the whole task object (person/collab/notes/etc.) — just name, priority,
 * and deadline info, capped to a small list, to keep the prompt small and
 * avoid over-sharing.
 *
 * $tasks: array of ['name'=>string, 'priority'=>string, 'overdue'=>bool,
 *                    'dueToday'=>bool, 'deadline'=>?string]
 *
 * Returns ['ok'=>true, 'message'=>string] or ['ok'=>false, 'error'=>string].
 */
function ai_daily_focus(array $tasks): array
{
    if (GEMINI_API_KEY === '') {
        return ['ok' => false, 'error' => 'AI is not configured yet (missing GEMINI_API_KEY).'];
    }
    if (empty($tasks)) {
        return ['ok' => false, 'error' => 'No open tasks to summarize.'];
    }

    // Cap what we send: 12 tasks is plenty for a useful summary and keeps
    // the request small and fast.
    $tasks = array_slice($tasks, 0, 12);
    $lines = [];
    foreach ($tasks as $t) {
        $name = clean_str($t['name'] ?? '', 120);
        if ($name === '') continue;
        $bits = [];
        $bits[] = 'priority: ' . clean_str($t['priority'] ?? 'medium', 20);
        if (!empty($t['overdue'])) $bits[] = 'OVERDUE';
        elseif (!empty($t['dueToday'])) $bits[] = 'due today';
        elseif (!empty($t['deadline'])) $bits[] = 'due ' . clean_str($t['deadline'], 20);
        $lines[] = "- $name (" . implode(', ', $bits) . ')';
    }
    if (empty($lines)) {
        return ['ok' => false, 'error' => 'No open tasks to summarize.'];
    }
    $taskList = implode("\n", $lines);
    $hour = (int)date('G');
    $timeOfDay = $hour < 5 ? 'late night' : ($hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening'));

    $prompt = <<<PROMPT
You are a calm, encouraging productivity assistant inside a to-do app. It is
currently the $timeOfDay. Here is the user's current open task list:

$taskList

Write a short (2-3 sentences, under 60 words total) spoken-style focus
briefing: mention what to tackle first and why, in a warm, motivating but
concise tone. No bullet points, no markdown, no headers — plain prose only,
as if a helpful assistant were speaking it out loud. Do not repeat the raw
task list verbatim; refer to at most 1-2 tasks by name.
PROMPT;

    $payload = [
        'contents' => [[
            'parts' => [['text' => $prompt]],
        ]],
        'generationConfig' => [
            'temperature' => 0.6,
            'maxOutputTokens' => 150,
        ],
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'error' => "Could not reach AI service: $err"];
    }
    $resp = json_decode($raw, true);
    if ($httpCode !== 200 || !is_array($resp)) {
        $msg = $resp['error']['message'] ?? "AI service returned HTTP $httpCode";
        return ['ok' => false, 'error' => $msg];
    }

    $text = trim($resp['candidates'][0]['content']['parts'][0]['text'] ?? '');
    if ($text === '') {
        return ['ok' => false, 'error' => 'AI returned an empty response.'];
    }

    return ['ok' => true, 'message' => $text];
}

/**
 * Parse a single free-form sentence (e.g. "Submit GST report next Friday,
 * very urgent") into a clean structured task — this powers "quick add"
 * boxes where the user types a whole task in plain English instead of
 * filling in a form field by field.
 *
 * Returns an array like ai_suggest_task()'s, plus a cleaned-up 'title':
 *   ['ok'=>true, 'title'=>'Submit GST report', 'urgency'=>'high', ...]
 * or ['ok'=>false, 'error'=>'...'].
 */
function ai_parse_task_from_text(string $text): array
{
    if (GEMINI_API_KEY === '') {
        return ['ok' => false, 'error' => 'AI is not configured yet (missing GEMINI_API_KEY).'];
    }
    $text = trim($text);
    if ($text === '') {
        return ['ok' => false, 'error' => 'No text given.'];
    }

    $today = date('Y-m-d (D)');
    $prompt = <<<PROMPT
You are a task-parser inside a to-do app. Today's date is $today.

The user typed this whole task in plain English:
"{$text}"

Reply with ONLY a compact JSON object (no markdown, no code fences, no
commentary) with exactly these keys:
{
  "title": a short, clean task title with priority/date words removed (e.g. "Call client about contract"),
  "urgency": one of "critical" | "high" | "medium" | "low" (infer from wording like "urgent", "asap", "whenever", else "medium"),
  "damage": one of "severe" | "moderate" | "minor" (impact if this drags on),
  "deadline": a "YYYY-MM-DD" date if the text implies one (e.g. "tomorrow", "next friday", "in 3 days"), else null,
  "estimateMins": a realistic whole number of minutes this task takes, else null,
  "steps": an array of up to 3 short (max 6 words) actionable subtask strings, or an empty array if none are implied,
  "tags": an array of up to 3 short single-word lowercase tags implied by the text (e.g. "work", "personal", "billing")
}
PROMPT;

    $payload = [
        'contents' => [[
            'parts' => [['text' => $prompt]],
        ]],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 300,
            'responseMimeType' => 'application/json',
        ],
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'error' => "Could not reach AI service: $err"];
    }
    $resp = json_decode($raw, true);
    if ($httpCode !== 200 || !is_array($resp)) {
        $msg = $resp['error']['message'] ?? "AI service returned HTTP $httpCode";
        return ['ok' => false, 'error' => $msg];
    }

    $body = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $body = trim($body);
    $body = preg_replace('/^```(?:json)?|```$/m', '', $body);
    $data = json_decode(trim($body), true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'AI returned an unexpected response.'];
    }

    $title = clean_str((string)($data['title'] ?? ''), 255) ?: clean_str($text, 255);
    $urgency = in_array($data['urgency'] ?? '', ['critical', 'high', 'medium', 'low'], true)
        ? $data['urgency'] : 'medium';
    $damage = in_array($data['damage'] ?? '', ['severe', 'moderate', 'minor'], true)
        ? $data['damage'] : 'moderate';
    $deadline = null;
    if (!empty($data['deadline']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['deadline'])) {
        $deadline = $data['deadline'];
    }
    $estimate = isset($data['estimateMins']) && is_numeric($data['estimateMins'])
        ? max(0, (int)$data['estimateMins']) : null;
    $steps = array_slice(array_filter(array_map('strval', $data['steps'] ?? [])), 0, 3);
    $tags = array_slice(array_filter(array_map('strval', $data['tags'] ?? [])), 0, 3);

    return [
        'ok' => true,
        'title' => $title,
        'urgency' => $urgency,
        'damage' => $damage,
        'deadline' => $deadline,
        'estimateMins' => $estimate,
        'steps' => array_values($steps),
        'tags' => array_values($tags),
    ];
}

/**
 * Ask Gemini for a short "day in review" paragraph for an end-of-day
 * check-out report, given the tasks worked on today and the day's stats.
 * Used to make the daily report email (and the on-screen checkout summary)
 * read like a real update instead of just a stat block.
 *
 * $tasks: array of raw workday_tasks rows (title, status, duration_seconds)
 * $summary: the $summary array already built in api/workday.php (done,
 *           worked_text, overtime_text, etc.)
 * $notes: the free-text notes the user typed at checkout, if any
 *
 * Returns ['ok'=>true, 'summary'=>string] or ['ok'=>false, 'error'=>string].
 * Callers should treat failure as "skip the AI paragraph", never as a
 * reason to block checkout.
 */
function ai_workday_summary(array $tasks, array $summary, ?string $notes = null): array
{
    if (GEMINI_API_KEY === '') {
        return ['ok' => false, 'error' => 'AI is not configured yet (missing GEMINI_API_KEY).'];
    }
    if (empty($tasks)) {
        return ['ok' => false, 'error' => 'No tasks logged today.'];
    }

    $lines = [];
    foreach (array_slice($tasks, 0, 15) as $t) {
        $title = clean_str($t['title'] ?? '', 120);
        if ($title === '') continue;
        $status = clean_str($t['status'] ?? '', 30);
        $dur = !empty($t['duration_seconds']) ? gmdate('H\h i\m', (int)$t['duration_seconds']) : null;
        $bits = [$status];
        if ($dur) $bits[] = "took $dur";
        $lines[] = "- $title (" . implode(', ', $bits) . ')';
    }
    if (empty($lines)) {
        return ['ok' => false, 'error' => 'No tasks logged today.'];
    }
    $taskList = implode("\n", $lines);
    $notesLine = $notes ? "\nThe person's own end-of-day notes: \"" . clean_str($notes, 500) . '"' : '';

    $prompt = <<<PROMPT
You are writing a short end-of-day summary paragraph for a daily work
report email, sent from an employee to their manager.

Today's tasks:
$taskList

Worked: {$summary['worked_text']}, {$summary['done']} task(s) completed
out of {$summary['total']}.{$notesLine}

Write 2-3 sentences, under 55 words total, in third person (e.g. "Rohet
completed..."), professional and factual — no emojis, no markdown, plain
prose only, as it will be inserted directly into an HTML email. Mention
what got done and call out anything still pending or awaiting approval if
relevant. Do not invent details not implied by the list above.
PROMPT;

    $payload = [
        'contents' => [[
            'parts' => [['text' => $prompt]],
        ]],
        'generationConfig' => [
            'temperature' => 0.4,
            'maxOutputTokens' => 150,
        ],
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'error' => "Could not reach AI service: $err"];
    }
    $resp = json_decode($raw, true);
    if ($httpCode !== 200 || !is_array($resp)) {
        $msg = $resp['error']['message'] ?? "AI service returned HTTP $httpCode";
        return ['ok' => false, 'error' => $msg];
    }

    $text = trim($resp['candidates'][0]['content']['parts'][0]['text'] ?? '');
    if ($text === '') {
        return ['ok' => false, 'error' => 'AI returned an empty response.'];
    }

    return ['ok' => true, 'summary' => $text];
}


function ai_fix_journal_entry(string $content): array
{
    if (GEMINI_API_KEY === '') {
        return ['ok' => false, 'error' => 'AI is not configured yet (missing GEMINI_API_KEY).'];
    }
    $content = trim($content);
    if ($content === '') {
        return ['ok' => false, 'error' => 'No journal text given.'];
    }

    $prompt = <<<PROMPT
You are proofreading a personal trading journal entry.

Original entry:
"{$content}"

Correct spelling and grammar, improve sentence structure, and lightly
rephrase for clarity while keeping the original meaning, tone and any
mood emoji line unchanged. Keep the same number of lines (max 15) and
do not add commentary, headings, or markdown.

Reply with ONLY the corrected entry text, nothing else.
PROMPT;

    $payload = [
        'contents' => [[ 'parts' => [['text' => $prompt]] ]],
        'generationConfig' => [ 'temperature' => 0.3, 'maxOutputTokens' => 600 ],
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'error' => "Could not reach AI service: $err"];
    }
    $resp = json_decode($raw, true);
    if ($httpCode !== 200 || !is_array($resp)) {
        $msg = $resp['error']['message'] ?? "AI service returned HTTP $httpCode";
        return ['ok' => false, 'error' => $msg];
    }

    $text = trim($resp['candidates'][0]['content']['parts'][0]['text'] ?? '');
    if ($text === '') {
        return ['ok' => false, 'error' => 'AI returned an empty response.'];
    }

    return ['ok' => true, 'corrected' => $text];
}