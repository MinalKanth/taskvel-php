<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$uid = current_user_id();
$pdo = db();
$format = one_of($_GET['format'] ?? 'csv', ['csv', 'json'], 'csv');

$stmt = $pdo->prepare("SELECT t.title, t.status, t.priority, t.due_date, t.created_at, t.completed_at
                        FROM tasks t WHERE t.owner_id = ?
                        ORDER BY t.created_at DESC");
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();

$pdo->prepare('INSERT INTO export_jobs (user_id, format) VALUES (?, ?)')->execute([$uid, $format]);

if ($format === 'json') {
    json_response(['tasks' => $rows]);
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="taskvel-export-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Title', 'Status', 'Priority', 'Due Date', 'Created At', 'Completed At']);
    foreach ($rows as $r) fputcsv($out, array_map('csv_safe', $r));
    fclose($out);
    exit;
}


// PDF export: recommend generating server-side with a library such as Dompdf
// (composer require dompdf/dompdf) rendering an HTML table of $rows to PDF.
json_response(['error' => 'Unsupported format. Use csv or json (see comment for PDF via Dompdf).'], 400);
