<?php
require_once __DIR__ . '/../includes/licensing.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    // Any member of the org can see the templates their admin published.
    case 'GET:list':
        $membership = user_organization_membership($uid);
        if (!$membership) json_response(['templates' => []]);
        $stmt = $pdo->prepare(
            "SELECT ot.id, ot.name, ot.payload, ot.created_at, u.name AS created_by_name
             FROM org_templates ot LEFT JOIN users u ON u.id = ot.created_by
             WHERE ot.organization_id = ? ORDER BY ot.created_at DESC"
        );
        $stmt->execute([$membership['organization_id']]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) $r['payload'] = json_decode($r['payload'], true);
        json_response(['templates' => $rows]);
        break;

    // Only an owner/admin can publish a template for the whole org to use.
    case 'POST:create':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);
        $name = clean_str($in['name'] ?? 'Untitled template', 120);
        $stmt = $pdo->prepare('INSERT INTO org_templates (organization_id, name, payload, created_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$orgId, $name, json_encode($in['payload'] ?? []), $uid]);
        audit_log($uid, 'org_template_created', ['organization_id' => $orgId, 'name' => $name]);
        json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
        break;

    case 'DELETE:delete':
        $orgId = (int)($_GET['org_id'] ?? 0);
        require_org_admin($orgId);
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare('DELETE FROM org_templates WHERE id = ? AND organization_id = ?')->execute([$id, $orgId]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}