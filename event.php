<?php
require_once __DIR__ . '/includes/auth.php';

function youtube_embed_url(?string $url): ?string
{
    if (!$url) return null;
    $id = null;
    if (preg_match('~youtu\.be/([A-Za-z0-9_\-]+)~', $url, $m)) $id = $m[1];
    elseif (preg_match('~[?&]v=([A-Za-z0-9_\-]+)~', $url, $m)) $id = $m[1];
    elseif (preg_match('~youtube\.com/embed/([A-Za-z0-9_\-]+)~', $url, $m)) $id = $m[1];
    return $id ? 'https://www.youtube.com/embed/' . $id : null;
}

$slug = trim((string) ($_GET['slug'] ?? ''));

$stmt = db()->prepare(
    "SELECT e.*, c.name AS category_name
     FROM events e
     LEFT JOIN event_categories c ON c.id = e.category_id
     WHERE e.slug = :slug
     LIMIT 1"
);
$stmt->execute([':slug' => $slug]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
}

$images = [];
$hotels = [];
if ($event) {
    $imgStmt = db()->prepare('SELECT image_url FROM event_images WHERE event_id = :id ORDER BY position ASC, id ASC');
    $imgStmt->execute([':id' => $event['id']]);
    $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    $hotelStmt = db()->prepare('SELECT * FROM event_hotels WHERE event_id = :id ORDER BY position ASC, id ASC');
    $hotelStmt->execute([':id' => $event['id']]);
    $hotels = $hotelStmt->fetchAll();
}

$STATUS_LABELS = ['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed'];
$embedUrl = $event ? youtube_embed_url($event['youtube_url']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $event ? htmlspecialchars($event['title']) . ' · Events · Taskvel' : 'Event not found · Taskvel' ?></title>
<style>
    :root {
        --bg:#f6f6f4; --bg-elev:#fff; --bg-sunk:#ededea; --ink:#0a0a0a; --ink2:#3d3d3b; --ink3:#7c7c78;
        --line:#e6e5e0; --line2:#d4d3cd; --accent:#4f46e5; --accent-soft:rgba(79,70,229,.1); --on-accent:#fff;
        --good:#059669; --good-soft:rgba(5,150,105,.1); --warn:#d97706; --warn-soft:rgba(217,119,6,.1); --bad:#dc2626; --bad-soft:rgba(220,38,38,.1);
        --shadow:0 10px 34px rgba(10,10,10,.08); --r:14px; --ease:cubic-bezier(.22,1,.36,1);
    }
    * { box-sizing:border-box; }
    body { margin:0; font-family:-apple-system,'Segoe UI',Arial,sans-serif; background:var(--bg); color:var(--ink); }
    .wrap { max-width:900px; margin:0 auto; padding:24px 18px 90px; }
    .topbar a.back { color:var(--ink3); text-decoration:none; font-size:13px; font-weight:600; }
    h1 { font-size:24px; font-weight:800; margin:16px 0 6px; }
    .sub { color:var(--ink3); font-size:13.5px; margin-bottom:6px; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:10px 18px; border-radius:10px; border:none;
        background:var(--accent); color:var(--on-accent); font-weight:700; font-size:13.5px; cursor:pointer; text-decoration:none; }
    .btn.ghost { background:var(--bg-elev); color:var(--ink); border:1px solid var(--line2); }

    .banner { width:100%; height:280px; object-fit:cover; border-radius:var(--r); background:var(--bg-sunk); margin-top:14px; }

    .pill { font-size:9.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; letter-spacing:.4px; }
    .pill-upcoming { background:var(--accent-soft); color:var(--accent); }
    .pill-ongoing { background:var(--warn-soft); color:var(--warn); }
    .pill-completed { background:var(--good-soft); color:var(--good); }
    .pill-category { background:var(--bg-sunk); color:var(--ink3); }
    .badges { display:flex; gap:6px; margin:10px 0; }

    .meta-row { display:flex; gap:18px; flex-wrap:wrap; font-size:13.5px; color:var(--ink2); margin:10px 0 18px; }
    .meta-row span { display:flex; align-items:center; gap:6px; }

    section { margin-top:30px; }
    section h2 { font-size:15px; font-weight:700; margin-bottom:12px; }

    .desc-block { font-size:14px; line-height:1.6; color:var(--ink2); }

    .gallery { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; }
    .gallery img { width:100%; height:110px; object-fit:cover; border-radius:10px; border:1px solid var(--line); cursor:pointer; }

    .video-wrap { position:relative; width:100%; padding-top:56.25%; border-radius:var(--r); overflow:hidden; background:var(--bg-sunk); }
    .video-wrap iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }

    .hotel-card {
        background:var(--bg-elev); border:1px solid var(--line); border-radius:12px;
        padding:14px 16px; margin-bottom:10px; display:flex; justify-content:space-between;
        align-items:center; gap:12px; flex-wrap:wrap;
    }
    .hotel-card .name { font-weight:700; font-size:14px; }
    .hotel-card .desc { font-size:12.5px; color:var(--ink3); margin-top:3px; }

    .organizer-card {
        background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:16px 18px;
    }
    .organizer-card .name { font-weight:700; font-size:14.5px; margin-bottom:6px; }
    .organizer-card .contact { font-size:13px; color:var(--ink2); display:flex; flex-direction:column; gap:3px; }

    .not-found {
        text-align:center; padding:60px 20px; color:var(--ink3);
    }
</style>
</head>
<body>
<div class="wrap">

    <div class="topbar">
        <a href="events.php" class="back">&larr; Back to Events</a>
    </div>

    <?php if (!$event): ?>
        <div class="not-found">
            <h1>Event not found</h1>
            <p>The event you're looking for doesn't exist or may have been removed.</p>
            <a href="events.php" class="btn ghost">Browse all events</a>
        </div>
    <?php else: ?>

        <img class="banner" src="<?= htmlspecialchars($event['banner_image'] ?: 'https://placehold.co/900x400?text=Event') ?>" alt="<?= htmlspecialchars($event['title']) ?>">

        <h1><?= htmlspecialchars($event['title']) ?></h1>
        <div class="badges">
            <span class="pill pill-<?= htmlspecialchars($event['status']) ?>"><?= htmlspecialchars($STATUS_LABELS[$event['status']] ?? ucfirst($event['status'])) ?></span>
            <?php if ($event['category_name']): ?>
                <span class="pill pill-category"><?= htmlspecialchars($event['category_name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="meta-row">
            <span>📅 <?= htmlspecialchars(date('d M Y', strtotime($event['event_date']))) ?><?= $event['event_time'] ? ' · ' . htmlspecialchars(date('h:i A', strtotime($event['event_time']))) : '' ?></span>
            <span>📍 <?= htmlspecialchars($event['location']) ?></span>
        </div>

        <?php if ($event['booking_link']): ?>
            <a href="<?= htmlspecialchars($event['booking_link']) ?>" class="btn" target="_blank" rel="noopener noreferrer">Book this event &rarr;</a>
        <?php endif; ?>

        <?php if ($event['full_description'] || $event['short_description']): ?>
            <section>
                <h2>About this event</h2>
                <div class="desc-block"><?= nl2br(htmlspecialchars($event['full_description'] ?: $event['short_description'])) ?></div>
            </section>
        <?php endif; ?>

        <?php if ($images): ?>
            <section>
                <h2>Gallery</h2>
                <div class="gallery">
                    <?php foreach ($images as $img): ?>
                        <a href="<?= htmlspecialchars($img) ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?= htmlspecialchars($img) ?>" alt="Event photo" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($embedUrl): ?>
            <section>
                <h2>Video</h2>
                <div class="video-wrap">
                    <iframe src="<?= htmlspecialchars($embedUrl) ?>" title="Event video" allowfullscreen loading="lazy"></iframe>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($hotels): ?>
            <section>
                <h2>Nearby hotels</h2>
                <?php foreach ($hotels as $h): ?>
                    <div class="hotel-card">
                        <div>
                            <div class="name"><?= htmlspecialchars($h['hotel_name']) ?></div>
                            <?php if ($h['description']): ?><div class="desc"><?= htmlspecialchars($h['description']) ?></div><?php endif; ?>
                        </div>
                        <?php if ($h['booking_link']): ?>
                            <a href="<?= htmlspecialchars($h['booking_link']) ?>" class="btn ghost sm" target="_blank" rel="noopener noreferrer">View &rarr;</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($event['organizer_name'] || $event['organizer_phone'] || $event['organizer_email']): ?>
            <section>
                <h2>Organizer</h2>
                <div class="organizer-card">
                    <?php if ($event['organizer_name']): ?><div class="name"><?= htmlspecialchars($event['organizer_name']) ?></div><?php endif; ?>
                    <div class="contact">
                        <?php if ($event['organizer_phone']): ?><span>📞 <?= htmlspecialchars($event['organizer_phone']) ?></span><?php endif; ?>
                        <?php if ($event['organizer_email']): ?><span>✉️ <?= htmlspecialchars($event['organizer_email']) ?></span><?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    <?php endif; ?>

</div>
</body>
</html>