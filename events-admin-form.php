<?php
require_once __DIR__ . '/includes/auth.php';
if (!current_user_id()) { header('Location: login.php'); exit; }
$user = current_user();

$STATUS_LABELS = ['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed'];

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'event';
}

function unique_slug(PDO $pdo, string $base, int $excludeId = 0): string
{
    $slug = $base;
    $i = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM events WHERE slug = :slug AND id != :ex LIMIT 1');
        $stmt->execute([':slug' => $slug, ':ex' => $excludeId]);
        if (!$stmt->fetch()) return $slug;
        $i++;
        $slug = $base . '-' . $i;
    }
}

/* ---------------------------------------------------------------
   SAVE  (create or update) — posted via fetch() with an X-CSRF-Token
   header, same convention as the rest of this app's write actions.
--------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (!current_user_id()) json_response(['error' => 'Unauthenticated'], 401);

    $id      = (int) ($_POST['id'] ?? 0);
    $title   = clean_str((string) ($_POST['title'] ?? ''), 200);
    $location = clean_str((string) ($_POST['location'] ?? ''), 200);
    $eventDate = (string) ($_POST['event_date'] ?? '');
    $eventTime = (string) ($_POST['event_time'] ?? '') ?: null;
    $categoryId = $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
    $status = (string) ($_POST['status'] ?? 'upcoming');
    if (!array_key_exists($status, $STATUS_LABELS)) $status = 'upcoming';

    $errors = [];
    if ($title === '') $errors[] = 'Title is required.';
    if ($location === '') $errors[] = 'Location is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) $errors[] = 'A valid event date is required.';

    if ($errors) {
        json_response(['error' => implode(' ', $errors)], 422);
    }

    $bannerImage   = clean_str((string) ($_POST['banner_image'] ?? ''), 255) ?: null;
    $shortDesc     = clean_str((string) ($_POST['short_description'] ?? ''), 500) ?: null;
    $fullDesc      = trim((string) ($_POST['full_description'] ?? '')) ?: null;
    $youtubeUrl    = clean_str((string) ($_POST['youtube_url'] ?? ''), 255) ?: null;
    $bookingLink   = clean_str((string) ($_POST['booking_link'] ?? ''), 255) ?: null;
    $organizerName = clean_str((string) ($_POST['organizer_name'] ?? ''), 150) ?: null;
    $organizerPhone = clean_str((string) ($_POST['organizer_phone'] ?? ''), 30) ?: null;
    $organizerEmail = clean_str((string) ($_POST['organizer_email'] ?? ''), 150) ?: null;

    $images = array_values(array_filter(array_map('trim', (array) ($_POST['images'] ?? []))));
    $hotelNames = (array) ($_POST['hotel_name'] ?? []);
    $hotelDescs = (array) ($_POST['hotel_description'] ?? []);
    $hotelLinks = (array) ($_POST['hotel_link'] ?? []);

    $pdo = db();
    try {
        $pdo->beginTransaction();

        if ($id > 0) {
            // ---- Update existing event ----
            $existing = $pdo->prepare('SELECT id, title FROM events WHERE id = :id');
            $existing->execute([':id' => $id]);
            $row = $existing->fetch();
            if (!$row) {
                $pdo->rollBack();
                json_response(['error' => 'Event not found.'], 404);
            }
            $slug = $row['title'] === $title
                ? $pdo->query('SELECT slug FROM events WHERE id = ' . (int) $id)->fetchColumn()
                : unique_slug($pdo, slugify($title), $id);

            $stmt = $pdo->prepare(
                'UPDATE events SET
                    title = :title, slug = :slug, banner_image = :banner_image,
                    short_description = :short_description, full_description = :full_description,
                    event_date = :event_date, event_time = :event_time, location = :location,
                    category_id = :category_id, status = :status,
                    youtube_url = :youtube_url, booking_link = :booking_link,
                    organizer_name = :organizer_name, organizer_phone = :organizer_phone, organizer_email = :organizer_email,
                    updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':title' => $title, ':slug' => $slug, ':banner_image' => $bannerImage,
                ':short_description' => $shortDesc, ':full_description' => $fullDesc,
                ':event_date' => $eventDate, ':event_time' => $eventTime, ':location' => $location,
                ':category_id' => $categoryId, ':status' => $status,
                ':youtube_url' => $youtubeUrl, ':booking_link' => $bookingLink,
                ':organizer_name' => $organizerName, ':organizer_phone' => $organizerPhone, ':organizer_email' => $organizerEmail,
                ':id' => $id,
            ]);

            // Simplest correct way to keep child rows in sync: replace them.
            $pdo->prepare('DELETE FROM event_images WHERE event_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM event_hotels WHERE event_id = :id')->execute([':id' => $id]);
            $eventId = $id;
            audit_log(current_user_id(), 'event_updated', ['event_id' => $id]);
        } else {
            // ---- Create new event ----
            $slug = unique_slug($pdo, slugify($title));
            $stmt = $pdo->prepare(
                'INSERT INTO events
                    (title, slug, banner_image, short_description, full_description,
                     event_date, event_time, location, category_id, status,
                     youtube_url, booking_link, organizer_name, organizer_phone, organizer_email,
                     created_by, created_at, updated_at)
                 VALUES
                    (:title, :slug, :banner_image, :short_description, :full_description,
                     :event_date, :event_time, :location, :category_id, :status,
                     :youtube_url, :booking_link, :organizer_name, :organizer_phone, :organizer_email,
                     :created_by, NOW(), NOW())'
            );
            $stmt->execute([
                ':title' => $title, ':slug' => $slug, ':banner_image' => $bannerImage,
                ':short_description' => $shortDesc, ':full_description' => $fullDesc,
                ':event_date' => $eventDate, ':event_time' => $eventTime, ':location' => $location,
                ':category_id' => $categoryId, ':status' => $status,
                ':youtube_url' => $youtubeUrl, ':booking_link' => $bookingLink,
                ':organizer_name' => $organizerName, ':organizer_phone' => $organizerPhone, ':organizer_email' => $organizerEmail,
                ':created_by' => current_user_id(),
            ]);
            $eventId = (int) $pdo->lastInsertId();
            audit_log(current_user_id(), 'event_created', ['event_id' => $eventId]);
        }

        // ---- Gallery images ----
        $imgStmt = $pdo->prepare('INSERT INTO event_images (event_id, image_url, position) VALUES (:eid, :url, :pos)');
        foreach ($images as $i => $url) {
            $url = clean_str($url, 255);
            if ($url === '') continue;
            $imgStmt->execute([':eid' => $eventId, ':url' => $url, ':pos' => $i + 1]);
        }

        // ---- Nearby hotels ----
        $hotelStmt = $pdo->prepare(
            'INSERT INTO event_hotels (event_id, hotel_name, description, booking_link, position) VALUES (:eid, :name, :desc, :link, :pos)'
        );
        $pos = 1;
        foreach ($hotelNames as $i => $name) {
            $name = clean_str((string) $name, 150);
            if ($name === '') continue;
            $desc = clean_str((string) ($hotelDescs[$i] ?? ''), 500) ?: null;
            $link = clean_str((string) ($hotelLinks[$i] ?? ''), 255) ?: null;
            $hotelStmt->execute([':eid' => $eventId, ':name' => $name, ':desc' => $desc, ':link' => $link, ':pos' => $pos]);
            $pos++;
        }

        $pdo->commit();
        json_response(['ok' => true, 'redirect' => 'events-admin.php']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Event save failed: ' . $e->getMessage());
        json_response(['error' => 'Something went wrong while saving the event.'], 500);
    }
}

/* ---------------------------------------------------------------
   LOAD (GET) — prefill form when editing
--------------------------------------------------------------- */
$id = (int) ($_GET['id'] ?? 0);
$event = [
    'id' => 0, 'title' => '', 'banner_image' => '', 'short_description' => '', 'full_description' => '',
    'event_date' => '', 'event_time' => '', 'location' => '', 'category_id' => '', 'status' => 'upcoming',
    'youtube_url' => '', 'booking_link' => '', 'organizer_name' => '', 'organizer_phone' => '', 'organizer_email' => '',
];
$images = [];
$hotels = [];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM events WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $found = $stmt->fetch();
    if ($found) {
        $event = $found;
        $imgStmt = db()->prepare('SELECT image_url FROM event_images WHERE event_id = :id ORDER BY position ASC, id ASC');
        $imgStmt->execute([':id' => $id]);
        $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

        $hotelStmt = db()->prepare('SELECT * FROM event_hotels WHERE event_id = :id ORDER BY position ASC, id ASC');
        $hotelStmt->execute([':id' => $id]);
        $hotels = $hotelStmt->fetchAll();
    }
}
if (!$images) $images = [''];
if (!$hotels) $hotels = [['hotel_name' => '', 'description' => '', 'booking_link' => '']];

$categories = db()->query('SELECT id, name FROM event_categories ORDER BY name ASC')->fetchAll();

function fv($val): string { return htmlspecialchars((string) ($val ?? '')); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $event['id'] ? 'Edit Event' : 'Add Event' ?> · Taskvel</title>
<style>
    :root {
        --bg:#f6f6f4; --bg-elev:#fff; --bg-sunk:#ededea; --ink:#0a0a0a; --ink2:#3d3d3b; --ink3:#7c7c78;
        --line:#e6e5e0; --line2:#d4d3cd; --accent:#4f46e5; --accent-soft:rgba(79,70,229,.1); --on-accent:#fff;
        --good:#059669; --good-soft:rgba(5,150,105,.1); --warn:#d97706; --warn-soft:rgba(217,119,6,.1); --bad:#dc2626; --bad-soft:rgba(220,38,38,.1);
        --shadow:0 10px 34px rgba(10,10,10,.08); --r:14px; --ease:cubic-bezier(.22,1,.36,1);
    }
    * { box-sizing:border-box; }
    body { margin:0; font-family:-apple-system,'Segoe UI',Arial,sans-serif; background:var(--bg); color:var(--ink); }
    .wrap { max-width:820px; margin:0 auto; padding:24px 18px 90px; }
    .topbar a.back { color:var(--ink3); text-decoration:none; font-size:13px; font-weight:600; }
    h1 { font-size:22px; font-weight:800; margin:14px 0 4px; }
    .sub { color:var(--ink3); font-size:13.5px; margin-bottom:18px; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; border-radius:10px; border:none;
        background:var(--accent); color:var(--on-accent); font-weight:700; font-size:13px; cursor:pointer; text-decoration:none; }
    .btn.ghost { background:var(--bg-elev); color:var(--ink); border:1px solid var(--line2); }
    .btn.sm { padding:6px 11px; font-size:11.5px; }

    .panel { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:20px 22px; margin-bottom:16px; }
    .panel h2 { font-size:14px; font-weight:700; margin:0 0 14px; }
    .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px 16px; }
    .field { display:flex; flex-direction:column; gap:5px; }
    .field.span-2 { grid-column:span 2; }
    .field label { font-size:12.5px; font-weight:600; color:var(--ink2); }
    .field input, .field select, .field textarea {
        padding:9px 11px; border:1px solid var(--line2); border-radius:9px; font-size:13px; font-family:inherit; background:#fff;
    }
    .field textarea { resize:vertical; min-height:70px; }

    .repeat-row { display:flex; gap:8px; margin-bottom:8px; align-items:center; }
    .repeat-row input { flex:1; }
    .repeat-row .remove-btn {
        background:var(--bad-soft); color:var(--bad); border:none; border-radius:7px; width:30px; height:30px;
        cursor:pointer; font-weight:700; flex-shrink:0;
    }
    .hotel-row { display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:8px; margin-bottom:8px; }
    .hotel-row input { padding:8px 10px; border:1px solid var(--line2); border-radius:8px; font-size:12.5px; }

    .form-actions {
        display:flex; justify-content:flex-end; gap:10px; margin-top:6px;
    }
    .general-error {
        background:var(--bad-soft); color:var(--bad); padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;
        display:none;
    }
    @media (max-width:640px) {
        .field.span-2 { grid-column:span 1; }
        .hotel-row { grid-template-columns:1fr; }
    }
</style>
</head>
<body>
<div class="wrap">

    <div class="topbar">
        <a href="events-admin.php" class="back">&larr; Back to events</a>
    </div>
    <h1><?= $event['id'] ? 'Edit event' : 'Add event' ?></h1>
    <div class="sub"><?= $event['id'] ? 'Update the details below.' : 'Fill in the details to create a new event.' ?></div>

    <div class="general-error" id="generalError"></div>

    <form id="eventForm">
        <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">

        <div class="panel">
            <h2>Basic details</h2>
            <div class="grid">
                <div class="field span-2">
                    <label>Event title *</label>
                    <input type="text" name="title" value="<?= fv($event['title']) ?>" maxlength="200" required>
                </div>
                <div class="field span-2">
                    <label>Banner image URL</label>
                    <input type="text" name="banner_image" value="<?= fv($event['banner_image']) ?>" maxlength="255" placeholder="https://…">
                </div>
                <div class="field span-2">
                    <label>Short description <span style="font-weight:400;color:var(--ink3);">(shown on the events list)</span></label>
                    <input type="text" name="short_description" value="<?= fv($event['short_description']) ?>" maxlength="500">
                </div>
                <div class="field span-2">
                    <label>Full description</label>
                    <textarea name="full_description"><?= fv($event['full_description']) ?></textarea>
                </div>
                <div class="field">
                    <label>Event date *</label>
                    <input type="date" name="event_date" value="<?= fv($event['event_date']) ?>" required>
                </div>
                <div class="field">
                    <label>Event time</label>
                    <input type="time" name="event_time" value="<?= fv(substr((string) $event['event_time'], 0, 5)) ?>">
                </div>
                <div class="field span-2">
                    <label>Location *</label>
                    <input type="text" name="location" value="<?= fv($event['location']) ?>" maxlength="200" required>
                </div>
                <div class="field">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">— None —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (string) $c['id'] === (string) $event['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach ($STATUS_LABELS as $k => $lbl): ?>
                            <option value="<?= $k ?>" <?= $event['status'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>Gallery images</h2>
            <div id="imageRows">
                <?php foreach ($images as $img): ?>
                    <div class="repeat-row">
                        <input type="text" name="images[]" value="<?= fv($img) ?>" placeholder="https://image-url.jpg">
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn ghost sm" onclick="addImageRow()">+ Add image</button>
        </div>

        <div class="panel">
            <h2>Nearby hotels</h2>
            <div id="hotelRows">
                <?php foreach ($hotels as $h): ?>
                    <div class="hotel-row">
                        <input type="text" name="hotel_name[]" value="<?= fv($h['hotel_name']) ?>" placeholder="Hotel name">
                        <input type="text" name="hotel_description[]" value="<?= fv($h['description']) ?>" placeholder="Description (optional)">
                        <input type="text" name="hotel_link[]" value="<?= fv($h['booking_link']) ?>" placeholder="Booking link (optional)">
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn ghost sm" onclick="addHotelRow()">+ Add hotel</button>
        </div>

        <div class="panel">
            <h2>Video &amp; booking</h2>
            <div class="grid">
                <div class="field span-2">
                    <label>YouTube video URL</label>
                    <input type="text" name="youtube_url" value="<?= fv($event['youtube_url']) ?>" maxlength="255" placeholder="https://www.youtube.com/watch?v=…">
                </div>
                <div class="field span-2">
                    <label>Official booking link</label>
                    <input type="text" name="booking_link" value="<?= fv($event['booking_link']) ?>" maxlength="255" placeholder="https://…">
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>Organizer</h2>
            <div class="grid">
                <div class="field">
                    <label>Organizer name</label>
                    <input type="text" name="organizer_name" value="<?= fv($event['organizer_name']) ?>" maxlength="150">
                </div>
                <div class="field">
                    <label>Organizer phone</label>
                    <input type="text" name="organizer_phone" value="<?= fv($event['organizer_phone']) ?>" maxlength="30">
                </div>
                <div class="field span-2">
                    <label>Organizer email</label>
                    <input type="email" name="organizer_email" value="<?= fv($event['organizer_email']) ?>" maxlength="150">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="events-admin.php" class="btn ghost">Cancel</a>
            <button type="submit" class="btn">Save event</button>
        </div>
    </form>

</div>

<script>
function addImageRow() {
    const wrap = document.getElementById('imageRows');
    const row = document.createElement('div');
    row.className = 'repeat-row';
    row.innerHTML = `<input type="text" name="images[]" placeholder="https://image-url.jpg">
                      <button type="button" class="remove-btn" onclick="this.parentElement.remove()">&times;</button>`;
    wrap.appendChild(row);
}
function addHotelRow() {
    const wrap = document.getElementById('hotelRows');
    const row = document.createElement('div');
    row.className = 'hotel-row';
    row.innerHTML = `<input type="text" name="hotel_name[]" placeholder="Hotel name">
                      <input type="text" name="hotel_description[]" placeholder="Description (optional)">
                      <input type="text" name="hotel_link[]" placeholder="Booking link (optional)">
                      <button type="button" class="remove-btn" onclick="this.parentElement.remove()">&times;</button>`;
    wrap.appendChild(row);
}

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
const form = document.getElementById('eventForm');
const errorBox = document.getElementById('generalError');

form.addEventListener('submit', async function (e) {
    e.preventDefault();
    errorBox.style.display = 'none';

    const formData = new FormData(form);
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) params.append(key, value);

    try {
        const res = await fetch('events-admin-form.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': CSRF_TOKEN },
            body: params,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.error) {
            errorBox.textContent = data.error || 'Something went wrong. Please try again.';
            errorBox.style.display = 'block';
            return;
        }
        window.location.href = data.redirect || 'events-admin.php';
    } catch (err) {
        errorBox.textContent = 'Network error — please try again.';
        errorBox.style.display = 'block';
    }
});
</script>
</body>
</html>