<?php
/**
 * VAPID keys for Web Push (real OS-level notifications, even when Taskvel
 * isn't open). Generate a keypair once with:
 *
 *   php scripts/generate_vapid_keys.php
 *
 * and paste the values below. Until VAPID_PUBLIC_KEY is filled in, the
 * client silently skips push subscription — everything else in Taskvel
 * keeps working exactly as before.
 */

define('VAPID_PUBLIC_KEY', '');   // e.g. 'BN...' (base64url, ~87 chars)
define('VAPID_PRIVATE_KEY', '');  // e.g. 'aB...' (base64url, ~43 chars) — keep secret!
define('VAPID_SUBJECT', 'mailto:you@example.com'); // contact address required by the push spec
