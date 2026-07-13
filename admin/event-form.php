<?php
require_once __DIR__ . '/../includes/admin.php';
require_admin();

$pdo = db();
$id  = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$error  = null;

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'event';
}

function unique_slug(PDO $pdo, string $base, int $exclude = 0): string
{
    $slug = $base; $n = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM events WHERE slug = ? AND id != ? LIMIT 1');
        $stmt->execute([$slug, $exclude]);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . (++$n);
    }
}

// ── Save (classic form POST with hidden csrf field) ──────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } else {
        $title     = clean_str($_POST['title'] ?? '', 190);
        $eventDate = $_POST['event_date'] ?? '';
        $status    = one_of($_POST['status'] ?? '', ['upcoming', 'ongoing', 'completed'], 'upcoming');

        $fields = [
            ':title'             => $title,
            ':banner_image'      => clean_str($_POST['banner_image'] ?? '', 255) ?: null,
            ':short_description' => clean_str($_POST['short_description'] ?? '', 300) ?: null,
            ':full_description'  => clean_str($_POST['full_description'] ?? '', 20000) ?: null,
            ':event_date'        => $eventDate,
            ':event_time'        => clean_str($_POST['event_time'] ?? '', 60) ?: null,
            ':location'          => clean_str($_POST['location'] ?? '', 190) ?: null,
            ':category_id'       => (int)($_POST['category_id'] ?? 0) ?: null,
            ':status'            => $status,
            ':youtube_url'       => clean_str($_POST['youtube_url'] ?? '', 255) ?: null,
            ':booking_link'      => clean_str($_POST['booking_link'] ?? '', 255) ?: null,
            ':organizer_name'    => clean_str($_POST['organizer_name'] ?? '', 120) ?: null,
            ':organizer_phone'   => clean_str($_POST['organizer_phone'] ?? '', 30) ?: null,
            ':organizer_email'   => clean_email($_POST['organizer_email'] ?? '') ?: null,
        ];

        if ($title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            $error = 'Title and a valid event date are required.';
        } elseif ($fields[':organizer_email'] && !filter_var($fields[':organizer_email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Organizer email is not valid.';
        } else {
            foreach ([':youtube_url', ':booking_link', ':banner_image'] as $urlField) {
                if ($fields[$urlField] && !preg_match('~^https?://~i', $fields[$urlField])) {
                    $error = 'Links must start with http:// or https://.';
                    break;
                }
            }
        }

        if (!$error) {
            $pdo->beginTransaction();
            try {
                if ($isEdit) {
                    $fields[':id'] = $id;
                    $pdo->prepare('UPDATE events SET
                            title = :title, banner_image = :banner_image,
                            short_description = :short_description, full_description = :full_description,
                            event_date = :event_date, event_time = :event_time, location = :location,
                            category_id = :category_id, status = :status,
                            youtube_url = :youtube_url, booking_link = :booking_link,
                            organizer_name = :organizer_name, organizer_phone = :organizer_phone,
                            organizer_email = :organizer_email, updated_at = NOW()
                        WHERE id = :id')->execute($fields);
                    $pdo->prepare('DELETE FROM event_images WHERE event_id = ?')->execute([$id]);
                    $pdo->prepare('DELETE FROM event_hotels WHERE event_id = ?')->execute([$id]);
                    $eventId = $id;
                    audit_log(current_user_id(), 'admin_event_updated', ['event_id' => $id]);
                } else {
                    $fields[':slug'] = unique_slug($pdo, slugify($title));
                    $fields[':created_by'] = current_user_id();
                    $pdo->prepare('INSERT INTO events
                            (title, slug, banner_image, short_description, full_description,
                             event_date, event_time, location, category_id, status,
                             youtube_url, booking_link, organizer_name, organizer_phone, organizer_email,
                             created_by, created_at, updated_at)
                         VALUES
                            (:title, :slug, :banner_image, :short_description, :full_description,
                             :event_date, :event_time, :location, :category_id, :status,
                             :youtube_url, :booking_link, :organizer_name, :organizer_phone, :organizer_email,
                             :created_by, NOW(), NOW())')->execute($fields);
                    $eventId = (int)$pdo->lastInsertId();
                    audit_log(current_user_id(), 'admin_event_created', ['event_id' => $eventId]);
                }

                // Gallery images (parallel arrays from the repeatable rows)
                $imgStmt = $pdo->prepare('INSERT INTO event_images (event_id, image_url, position) VALUES (?, ?, ?)');
                foreach (array_slice((array)($_POST['image_url'] ?? []), 0, 12) as $pos => $url) {
                    $url = clean_str($url, 255);
                    if ($url !== '' && preg_match('~^https?://~i', $url)) $imgStmt->execute([$eventId, $url, $pos]);
                }
                // Nearby hotels
                $hotStmt = $pdo->prepare('INSERT INTO event_hotels (event_id, name, link, distance) VALUES (?, ?, ?, ?)');
                $hNames = (array)($_POST['hotel_name'] ?? []);
                $hLinks = (array)($_POST['hotel_link'] ?? []);
                $hDists = (array)($_POST['hotel_distance'] ?? []);
                foreach (array_slice($hNames, 0, 12) as $i => $hn) {
                    $hn = clean_str($hn, 190);
                    if ($hn === '') continue;
                    $hl = clean_str($hLinks[$i] ?? '', 255);
                    $hotStmt->execute([$eventId, $hn, preg_match('~^https?://~i', $hl) ? $hl : null, clean_str($hDists[$i] ?? '', 60) ?: null]);
                }

                $pdo->commit();
                header('Location: events.php');
                exit;
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('[Taskvel admin] event save failed: ' . $e->getMessage());
                $error = 'Could not save the event. Check the fields and try again.';
            }
        }
    }
}

// ── Load for edit ────────────────────────────────────────────
$ev = ['status' => 'upcoming'];
$images = $hotels = [];
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $ev = $stmt->fetch();
    if (!$ev) { header('Location: events.php'); exit; }
    $s = $pdo->prepare('SELECT image_url FROM event_images WHERE event_id = ? ORDER BY position');
    $s->execute([$id]);
    $images = $s->fetchAll(PDO::FETCH_COLUMN);
    $s = $pdo->prepare('SELECT name, link, distance FROM event_hotels WHERE event_id = ?');
    $s->execute([$id]);
    $hotels = $s->fetchAll();
}
// Re-fill from POST on validation error
if ($error) { $ev = array_merge($ev, $_POST); }

$categories = $pdo->query('SELECT id, name FROM event_categories ORDER BY name ASC')->fetchAll();
$csrf = csrf_token();

require_once __DIR__ . '/_layout.php';
admin_header($isEdit ? 'Edit event' : 'Add event', 'events');
$v = fn($k) => h($ev[$k] ?? '');
?>
<div class="tophead">
  <div>
    <h2><?= $isEdit ? 'Edit' : 'Add' ?> <em>event</em></h2>
    <div class="sub"><?= $isEdit ? 'Changes go live on the public page as soon as you save.' : 'Publishes to the public events page immediately.' ?></div>
  </div>
  <a class="btn ghost" href="events.php">← All events</a>
</div>

<?php if ($error): ?>
  <div class="card" style="border-color:rgba(232,106,106,.4);margin-bottom:16px;color:var(--bad)">⚠ <?= h($error) ?></div>
<?php endif; ?>

<form method="post" autocomplete="off">
<input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
<div class="grid g2" style="align-items:start">

  <div class="card">
    <div class="field"><label>Title *</label><input type="text" name="title" required maxlength="190" value="<?= $v('title') ?>"></div>
    <div class="grid" style="grid-template-columns:1fr 1fr">
      <div class="field"><label>Date *</label><input type="date" name="event_date" required value="<?= $v('event_date') ?>"></div>
      <div class="field"><label>Time</label><input type="text" name="event_time" placeholder="6:00 PM onwards" value="<?= $v('event_time') ?>"></div>
    </div>
    <div class="field"><label>Location</label><input type="text" name="location" placeholder="Venue, City" value="<?= $v('location') ?>"></div>
    <div class="grid" style="grid-template-columns:1fr 1fr">
      <div class="field">
        <label>Category</label>
        <select name="category_id">
          <option value="">— None —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)($ev['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <?php foreach (['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed'] as $k => $l): ?>
            <option value="<?= $k ?>" <?= ($ev['status'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field"><label>Short description <span class="muted">(card teaser, 300 chars)</span></label>
      <textarea name="short_description" maxlength="300" style="min-height:70px"><?= $v('short_description') ?></textarea></div>
    <div class="field"><label>Full description</label>
      <textarea name="full_description" style="min-height:180px"><?= $v('full_description') ?></textarea></div>
  </div>

  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="field"><label>Banner image URL</label><input type="url" name="banner_image" placeholder="https://…" value="<?= $v('banner_image') ?>"></div>
      <div class="field"><label>YouTube URL</label><input type="url" name="youtube_url" placeholder="https://youtube.com/…" value="<?= $v('youtube_url') ?>"></div>
      <div class="field" style="margin-bottom:0"><label>Booking link</label><input type="url" name="booking_link" placeholder="https://…" value="<?= $v('booking_link') ?>"></div>
    </div>

    <div class="card" style="margin-bottom:16px">
      <label style="margin-bottom:10px">Organizer</label>
      <div class="field"><input type="text" name="organizer_name" placeholder="Name" value="<?= $v('organizer_name') ?>"></div>
      <div class="grid" style="grid-template-columns:1fr 1fr">
        <div class="field" style="margin-bottom:0"><input type="tel" name="organizer_phone" placeholder="Phone" value="<?= $v('organizer_phone') ?>"></div>
        <div class="field" style="margin-bottom:0"><input type="email" name="organizer_email" placeholder="Email" value="<?= $v('organizer_email') ?>"></div>
      </div>
    </div>

    <div class="card" style="margin-bottom:16px">
      <label style="margin-bottom:10px">Gallery images</label>
      <div id="imgs">
        <?php foreach (($images ?: ['']) as $url): ?>
          <div class="field" style="display:flex;gap:8px">
            <input type="url" name="image_url[]" placeholder="https://…" value="<?= h($url) ?>">
            <button type="button" class="btn danger sm" onclick="this.parentNode.remove()">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn ghost sm" onclick="addRow('imgs', imgRow)">＋ Add image</button>
    </div>

    <div class="card">
      <label style="margin-bottom:10px">Nearby hotels</label>
      <div id="hotels">
        <?php foreach (($hotels ?: [['name' => '', 'link' => '', 'distance' => '']]) as $hRow): ?>
          <div class="field" style="display:flex;gap:8px">
            <input type="text" name="hotel_name[]" placeholder="Hotel name" value="<?= h($hRow['name']) ?>">
            <input type="url" name="hotel_link[]" placeholder="Link" value="<?= h($hRow['link'] ?? '') ?>" style="max-width:34%">
            <input type="text" name="hotel_distance[]" placeholder="1.2 km" value="<?= h($hRow['distance'] ?? '') ?>" style="max-width:22%">
            <button type="button" class="btn danger sm" onclick="this.parentNode.remove()">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn ghost sm" onclick="addRow('hotels', hotelRow)">＋ Add hotel</button>
    </div>
  </div>
</div>

<div style="margin-top:20px;display:flex;gap:10px">
  <button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Publish event' ?></button>
  <a class="btn ghost" href="events.php">Cancel</a>
</div>
</form>

<script>
const imgRow = () => `<div class="field" style="display:flex;gap:8px">
  <input type="url" name="image_url[]" placeholder="https://…">
  <button type="button" class="btn danger sm" onclick="this.parentNode.remove()">✕</button></div>`;
const hotelRow = () => `<div class="field" style="display:flex;gap:8px">
  <input type="text" name="hotel_name[]" placeholder="Hotel name">
  <input type="url" name="hotel_link[]" placeholder="Link" style="max-width:34%">
  <input type="text" name="hotel_distance[]" placeholder="1.2 km" style="max-width:22%">
  <button type="button" class="btn danger sm" onclick="this.parentNode.remove()">✕</button></div>`;
function addRow(containerId, tpl) {
    const box = document.getElementById(containerId);
    if (box.children.length >= 12) { toast('Maximum 12 rows'); return; }
    box.insertAdjacentHTML('beforeend', tpl());
    box.lastElementChild.querySelector('input').focus();
}
</script>
<?php admin_footer();
