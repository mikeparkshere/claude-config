<?php
/**
 * Contact handler (flatsite.md → Security). POST only. Validates, checks the honeypot (and Turnstile when configured),
 * sends through the Mailgun HTTP API, never mail(). Answers JSON when the request asks for it (the JS path), otherwise
 * redirects: 303 to /contact/thanks/ on success, back to /contact/?error=<code> on failure (no-JS path).
 * Secrets come from includes/config.local.php ($secrets). Nothing from the request is echoed into HTML here.
 */
require $_SERVER['DOCUMENT_ROOT'] . '/../includes/bootstrap.php';

$json = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
function done(bool $ok, string $code, int $status = 200): void {
    global $json;
    if ($json) {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok, 'error' => $ok ? null : $code]);
    } else {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . ($ok ? '/contact/thanks/' : '/contact/?error=' . rawurlencode($code)), true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }

// Honeypot filled: act as if it worked, send nothing.
if (trim((string) ($_POST['website'] ?? '')) !== '') done(true, '');

$clean = fn($v, $max) => mb_substr(trim(str_replace(["\r", "\n"], ' ', (string) $v)), 0, $max);
$name    = $clean($_POST['name'] ?? '', 100);
$phone   = $clean($_POST['phone'] ?? '', 40);
$email   = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
$message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 5000);
if ($name === '' || $email === '' || $message === '') done(false, 'missing', 422);

$s = $secrets ?? [];
if (empty($s['mailgun_key']) || empty($s['mailgun_domain']) || empty($s['form_to'])) {
    error_log('contact: form not configured (includes/config.local.php)');
    done(false, 'config', 503);
}

// Cloudflare Turnstile, when a secret is configured.
if (!empty($s['turnstile_secret'])) {
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => $s['turnstile_secret'], 'response' => (string) ($_POST['cf-turnstile-response'] ?? ''), 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''])]);
    $ok = json_decode((string) curl_exec($ch), true)['success'] ?? false;
    curl_close($ch);
    if (!$ok) done(false, 'spam', 422);
}

$host = ($s['mailgun_region'] ?? 'us') === 'eu' ? 'api.eu.mailgun.net' : 'api.mailgun.net';
$body = "Name: $name\nEmail: $email\n" . ($phone !== '' ? "Phone: $phone\n" : '') . "\n$message\n\n-- \nSent from the contact form at " . $site['url'] . "/contact/";
$ch = curl_init("https://$host/v3/" . rawurlencode($s['mailgun_domain']) . '/messages');
curl_setopt_array($ch, [
    CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
    CURLOPT_USERPWD => 'api:' . $s['mailgun_key'],
    CURLOPT_POSTFIELDS => [
        'from'       => $s['form_from'] ?? ($site['name'] . ' <noreply@' . $s['mailgun_domain'] . '>'),
        'to'         => $s['form_to'],
        'subject'    => 'Website message from ' . $name,        // CR/LF already stripped
        'text'       => $body,
        'h:Reply-To' => $email,                                  // validated above
        'o:tag'      => 'contact-form',
    ],
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($code !== 200) {
    error_log('contact: Mailgun HTTP ' . $code . ' ' . substr((string) $res, 0, 200));
    done(false, 'send', 502);
}
$id = json_decode((string) $res, true)['id'] ?? '';
error_log('contact: accepted ' . $id);   // "accepted" is not "delivered": match the id to a delivered event in Mailgun
done(true, '');
