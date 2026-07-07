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

define('VAPID_PUBLIC_KEY', 'BBeW-xaEHhE1bcur4sHeX8uY28vCqL9afquWS_Wbh2Br6Rh_m1zH4iBbANtTwaLSfok-_XaXi18UrRBcNwVTsCw');
define('VAPID_PRIVATE_KEY', '2hc21Wuv7TVrEpV7-1T4W2-FqUbmqLfd_lWSz_K8CAU');
define('VAPID_SUBJECT', 'mailto:minalviprak@gmail.com');
