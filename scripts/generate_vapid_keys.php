<?php
/**
 * Run this ONCE on your server to generate a VAPID keypair for Web Push:
 *
 *   php scripts/generate_vapid_keys.php
 *
 * Paste the printed values into config/vapid.php. Keep the private key
 * secret — never expose it to the browser. The public key IS meant to be
 * sent to the client (it's embedded in index.php automatically once set).
 */

function base64url(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$res = openssl_pkey_new([
    'private_key_type' => OPENSSL_KEYTYPE_EC,
    'curve_name' => 'prime256v1',
]);
if (!$res) {
    fwrite(STDERR, "Could not generate EC key — is your PHP built with OpenSSL EC support?\n");
    exit(1);
}

$details = openssl_pkey_get_details($res);
$publicKeyRaw = "\x04" . $details['ec']['x'] . $details['ec']['y']; // uncompressed point format
$privateKeyRaw = $details['ec']['d'];

echo "VAPID_PUBLIC_KEY  = '" . base64url($publicKeyRaw) . "'\n";
echo "VAPID_PRIVATE_KEY = '" . base64url($privateKeyRaw) . "'\n";
echo "\nPaste both into config/vapid.php, then set VAPID_SUBJECT to a mailto: address you control.\n";
