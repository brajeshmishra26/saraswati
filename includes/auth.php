<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const AUTH_COOKIE_NAME = 'saraswati_auth';

function auth_is_https_request(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
}

function auth_signing_key(): string
{
    static $key = null;
    if (is_string($key) && $key !== '') {
        return $key;
    }

    $fromEnv = trim((string) getenv('APP_KEY'));
    if ($fromEnv !== '') {
        $key = hash('sha256', $fromEnv, true);
        return $key;
    }

    global $paymentConfig, $dbConfig;
    $seed = (string) ($paymentConfig['callback_secret'] ?? '');
    if ($seed === '') {
        $seed = (string) ($dbConfig['name'] ?? '') . '|' . (string) ($dbConfig['user'] ?? '') . '|saraswati-auth';
    }

    $key = hash('sha256', $seed, true);
    return $key;
}

function auth_b64url_encode(string $input): string
{
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

function auth_b64url_decode(string $input): ?string
{
    $encoded = strtr($input, '-_', '+/');
    $padding = strlen($encoded) % 4;
    if ($padding > 0) {
        $encoded .= str_repeat('=', 4 - $padding);
    }

    $decoded = base64_decode($encoded, true);
    return $decoded === false ? null : $decoded;
}

function auth_cookie_value(int $userId): string
{
    $issuedAt = time();
    $payload = (string) $userId . '|' . $issuedAt;
    $signature = hash_hmac('sha256', $payload, auth_signing_key(), true);

    return auth_b64url_encode($payload) . '.' . auth_b64url_encode($signature);
}

function auth_user_id_from_cookie(): ?int
{
    $raw = (string) ($_COOKIE[AUTH_COOKIE_NAME] ?? '');
    if ($raw === '') {
        return null;
    }

    $parts = explode('.', $raw, 2);
    if (count($parts) !== 2) {
        return null;
    }

    $payload = auth_b64url_decode($parts[0]);
    $signature = auth_b64url_decode($parts[1]);
    if ($payload === null || $signature === null) {
        return null;
    }

    $expected = hash_hmac('sha256', $payload, auth_signing_key(), true);
    if (!hash_equals($expected, $signature)) {
        return null;
    }

    $segments = explode('|', $payload, 2);
    if (count($segments) !== 2) {
        return null;
    }

    $userId = (int) ($segments[0] ?? 0);
    $issuedAt = (int) ($segments[1] ?? 0);
    if ($userId <= 0 || $issuedAt <= 0) {
        return null;
    }

    // 12-hour validity for non-session fallback auth cookie.
    if ((time() - $issuedAt) > 43200) {
        return null;
    }

    return $userId;
}

function auth_set_cookie(int $userId): void
{
    setcookie(AUTH_COOKIE_NAME, auth_cookie_value($userId), [
        'expires' => 0,
        'path' => '/',
        'secure' => auth_is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function auth_clear_cookie(): void
{
    setcookie(AUTH_COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => auth_is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function auth_user(): ?array
{
    if (empty($_SESSION['auth_user_id'])) {
        $cookieUserId = auth_user_id_from_cookie();
        if ($cookieUserId !== null) {
            $_SESSION['auth_user_id'] = $cookieUserId;
        } else {
            return null;
        }
    }

    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, full_name, email, role, profile_image FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute([':id' => (int) $_SESSION['auth_user_id']]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function auth_login(int $userId): void
{
    $_SESSION['auth_user_id'] = $userId;
    auth_set_cookie($userId);
}

function auth_logout(): void
{
    unset($_SESSION['auth_user_id']);
    auth_clear_cookie();
}

function auth_require_login(): void
{
    if (auth_user() === null) {
        header('Location: ' . auth_login_url());
        exit;
    }
}

function auth_login_url(): string
{
    $lang = '';

    if (isset($_GET['lang']) && is_string($_GET['lang'])) {
        $lang = trim($_GET['lang']);
    }

    if ($lang === '' && isset($GLOBALS['lang']) && is_string($GLOBALS['lang'])) {
        $lang = trim($GLOBALS['lang']);
    }

    if ($lang !== '') {
        return '/login.php?lang=' . urlencode($lang);
    }

    return '/login.php';
}

function auth_require_roles(array $roles): void
{
    $user = auth_user();
    if ($user === null || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function auth_role_label(string $role): string
{
    $map = [
        'member' => 'Member',
        'adhyaksh' => 'Adhyaksh',
        'sachiv' => 'Sachiv',
        'admin' => 'Admin',
    ];

    return $map[$role] ?? ucfirst($role);
}
