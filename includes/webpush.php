<?php
/**
 * Minimal, dependency-free Web Push sender (RFC 8291 aes128gcm encryption +
 * RFC 8292 VAPID authentication), so Taskvel doesn't need Composer or any
 * external library to deliver real OS-level push notifications — the same
 * notifications that arrive even when the browser tab is closed, on
 * desktop and on Android/iOS.
 *
 * Requires PHP 8.1+ (uses openssl_pkey_derive for the ECDH step). If your
 * host is on an older PHP version, swap send_web_push()'s internals for
 * the `minishlink/web-push` Composer package instead — same call shape.
 */
require_once __DIR__ . '/../config/vapid.php';

function b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function b64url_decode(string $data): string
{
    $data = strtr($data, '-_', '+/');
    $pad = strlen($data) % 4;
    if ($pad) $data .= str_repeat('=', 4 - $pad);
    return base64_decode($data);
}

// Wraps a raw 32-byte EC private scalar + 65-byte uncompressed public point
// into a SEC1 "EC PRIVATE KEY" DER/PEM so openssl_sign()/openssl_pkey_derive()
// can use it — avoids needing openssl_pkey_new with imported components.
function ec_private_key_resource(string $d32, string $pub65)
{
    $der = "\x30\x77"
         . "\x02\x01\x01"
         . "\x04\x20" . $d32
         . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"
         . "\xa1\x44\x03\x42\x00" . $pub65;
    $pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    return openssl_pkey_get_private($pem);
}

// Wraps a raw 65-byte uncompressed EC point into a SubjectPublicKeyInfo PEM.
function ec_public_key_resource(string $pub65)
{
    $prefix = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');
    $der = $prefix . $pub65;
    $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    return openssl_pkey_get_public($pem);
}

// Converts an OpenSSL DER ECDSA signature into the raw r||s (64-byte) form
// that JWT's ES256 requires.
function der_signature_to_raw(string $der): string
{
    $offset = 2; // skip SEQUENCE tag + length
    $parseInt = function () use ($der, &$offset) {
        $offset++; // skip INTEGER tag (0x02)
        $len = ord($der[$offset]); $offset++;
        $bytes = substr($der, $offset, $len); $offset += $len;
        $bytes = ltrim($bytes, "\x00");
        return str_pad($bytes, 32, "\x00", STR_PAD_LEFT);
    };
    $r = $parseInt();
    $s = $parseInt();
    return $r . $s;
}

function vapid_public_key_raw(): string
{
    return b64url_decode(VAPID_PUBLIC_KEY);
}

/**
 * Sends one Web Push message. $subscription is the array shape stored in
 * push_subscriptions: ['endpoint' => .., 'p256dh' => .., 'auth' => ..].
 * $payload is any JSON-serializable array — matches what sw.js expects
 * (title, body, url, tag, icon).
 * Returns true on 2xx/201/410-style "gone" (treated as success-to-caller
 * since the caller should then prune the dead subscription), false otherwise.
 */
function send_web_push(array $subscription, array $payload): array
{
    if (VAPID_PUBLIC_KEY === '' || VAPID_PRIVATE_KEY === '') {
        return ['ok' => false, 'error' => 'VAPID keys not configured — run scripts/generate_vapid_keys.php'];
    }

    $endpoint = $subscription['endpoint'];
    $uaPublic = b64url_decode($subscription['p256dh']); // 65 bytes, uncompressed point
    $authSecret = b64url_decode($subscription['auth']); // 16 bytes

    // ── 1. Ephemeral EC keypair for this message ──
    $eph = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    $ephDetails = openssl_pkey_get_details($eph);
    $asPublic = "\x04" . $ephDetails['ec']['x'] . $ephDetails['ec']['y']; // 65 bytes

    // ── 2. ECDH shared secret between our ephemeral key and the browser's p256dh ──
    $uaPubKeyRes = ec_public_key_resource($uaPublic);
    if (!$uaPubKeyRes) return ['ok' => false, 'error' => 'Invalid subscription public key'];
    $sharedSecret = openssl_pkey_derive($uaPubKeyRes, $eph);
    if ($sharedSecret === false) return ['ok' => false, 'error' => 'ECDH derive failed (needs PHP 8.1+)'];

    // ── 3. RFC 8291: derive the content-encryption IKM from the shared secret + auth secret ──
    $prkKey = hash_hmac('sha256', $sharedSecret, $authSecret, true);
    $keyInfo = "WebPush: info\x00" . $uaPublic . $asPublic;
    $ikm = hash_hmac('sha256', $keyInfo . "\x01", $prkKey, true);

    // ── 4. RFC 8188 aes128gcm: derive CEK + nonce from a random salt ──
    $salt = random_bytes(16);
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $cek = substr(hash_hmac('sha256', "Content-Encoding: aes128gcm\x00\x01", $prk, true), 0, 16);
    $nonce = substr(hash_hmac('sha256', "Content-Encoding: nonce\x00\x01", $prk, true), 0, 12);

    // ── 5. Encrypt the JSON payload (single record, delimiter 0x02 then no padding) ──
    $plaintext = json_encode($payload) . "\x02";
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($ciphertext === false) return ['ok' => false, 'error' => 'Encryption failed'];

    $recordSize = pack('N', 4096); // big-endian uint32
    $body = $salt . $recordSize . chr(strlen($asPublic)) . $asPublic . $ciphertext . $tag;

    // ── 6. VAPID JWT (ES256) so the push service accepts the request ──
    $parsed = parse_url($endpoint);
    $audience = $parsed['scheme'] . '://' . $parsed['host'];
    $header = b64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $claims = b64url_encode(json_encode(['aud' => $audience, 'exp' => time() + 12 * 3600, 'sub' => VAPID_SUBJECT]));
    $signingInput = "$header.$claims";
    $vapidPriv = ec_private_key_resource(b64url_decode(VAPID_PRIVATE_KEY), vapid_public_key_raw());
    openssl_sign($signingInput, $sigDer, $vapidPriv, OPENSSL_ALGO_SHA256);
    $jwt = $signingInput . '.' . b64url_encode(der_signature_to_raw($sigDer));

    // ── 7. POST the encrypted body to the browser's push endpoint ──
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: 86400',
            'Authorization: vapid t=' . $jwt . ', k=' . VAPID_PUBLIC_KEY,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status >= 200 && $status < 300) return ['ok' => true];
    if ($status === 404 || $status === 410) return ['ok' => false, 'gone' => true, 'error' => 'Subscription expired'];
    return ['ok' => false, 'error' => "Push service returned HTTP $status"];
}

// Convenience: send to every device a user has subscribed, pruning dead ones.
function send_web_push_to_user(PDO $pdo, int $userId, array $payload): void
{
    $stmt = $pdo->prepare('SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?');
    $stmt->execute([$userId]);
    foreach ($stmt->fetchAll() as $sub) {
        $result = send_web_push($sub, $payload);
        if (!empty($result['gone'])) {
            $pdo->prepare('DELETE FROM push_subscriptions WHERE id = ?')->execute([$sub['id']]);
        }
    }
}
