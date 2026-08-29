<?php
require_once __DIR__ . '/../config/ai.php';

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
