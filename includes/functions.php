<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function read_json_file(string $path, array $fallback): array
{
    if (!file_exists($path)) {
        return $fallback;
    }

    $content = file_get_contents($path);
    if ($content === false || trim($content) === '') {
        return $fallback;
    }

    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function csrf_token(string $form): string
{
    ensure_session_started();

    if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    $ttl = 7200;
    global $security;
    if (isset($security['csrf_ttl'])) {
        $ttl = (int) $security['csrf_ttl'];
    }

    $entry = $_SESSION['csrf_tokens'][$form] ?? null;
    $isValid = is_array($entry)
        && isset($entry['token'], $entry['created_at'])
        && (time() - (int) $entry['created_at']) < $ttl;

    if (!$isValid) {
        $_SESSION['csrf_tokens'][$form] = [
            'token' => csrf_signed_token($form),
            'created_at' => time(),
        ];
    }

    return (string) $_SESSION['csrf_tokens'][$form]['token'];
}

function csrf_input(string $form): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token($form)) . '">';
}

function csrf_validate(?string $token, string $form): bool
{
    ensure_session_started();
    if ($token === null || $token === '') {
        return false;
    }

    $stored = $_SESSION['csrf_tokens'][$form]['token'] ?? null;
    if (!is_string($stored) || $stored === '') {
        // Fallback for environments with unstable session persistence.
        return csrf_validate_signed_token($token, $form);
    }

    return hash_equals($stored, $token) || csrf_validate_signed_token($token, $form);
}

function csrf_signed_token(string $form): string
{
    $issuedAt = time();
    $nonce = bin2hex(random_bytes(12));
    $payload = $form . '|' . $issuedAt . '|' . $nonce;
    $signature = hash_hmac('sha256', $payload, csrf_signing_key(), true);

    return csrf_b64url_encode($payload) . '.' . csrf_b64url_encode($signature);
}

function csrf_validate_signed_token(string $token, string $form): bool
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        return false;
    }

    $payload = csrf_b64url_decode($parts[0]);
    $signature = csrf_b64url_decode($parts[1]);
    if ($payload === null || $signature === null) {
        return false;
    }

    $expected = hash_hmac('sha256', $payload, csrf_signing_key(), true);
    if (!hash_equals($expected, $signature)) {
        return false;
    }

    $segments = explode('|', $payload, 3);
    if (count($segments) !== 3) {
        return false;
    }

    [$tokenForm, $issuedAtRaw] = $segments;
    if (!hash_equals($form, $tokenForm)) {
        return false;
    }

    $issuedAt = (int) $issuedAtRaw;
    if ($issuedAt <= 0) {
        return false;
    }

    $ttl = 7200;
    global $security;
    if (isset($security['csrf_ttl'])) {
        $ttl = (int) $security['csrf_ttl'];
    }

    return (time() - $issuedAt) < $ttl;
}

function csrf_signing_key(): string
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
        $seed = (string) ($dbConfig['name'] ?? '') . '|' . (string) ($dbConfig['user'] ?? '') . '|saraswati-csrf';
    }

    $key = hash('sha256', $seed, true);
    return $key;
}

function csrf_b64url_encode(string $input): string
{
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

function csrf_b64url_decode(string $input): ?string
{
    $encoded = strtr($input, '-_', '+/');
    $padding = strlen($encoded) % 4;
    if ($padding > 0) {
        $encoded .= str_repeat('=', 4 - $padding);
    }

    $decoded = base64_decode($encoded, true);
    return $decoded === false ? null : $decoded;
}

function password_policy_error(string $password): ?string
{
    global $security;
    $policy = $security['password_policy'] ?? [];

    $minLength = (int) ($policy['min_length'] ?? 8);
    if (strlen($password) < $minLength) {
        return 'Password must be at least ' . $minLength . ' characters.';
    }

    if (!empty($policy['require_upper']) && !preg_match('/[A-Z]/', $password)) {
        return 'Password must include at least one uppercase letter.';
    }

    if (!empty($policy['require_lower']) && !preg_match('/[a-z]/', $password)) {
        return 'Password must include at least one lowercase letter.';
    }

    if (!empty($policy['require_number']) && !preg_match('/[0-9]/', $password)) {
        return 'Password must include at least one number.';
    }

    if (!empty($policy['require_symbol']) && !preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Password must include at least one special character.';
    }

    return null;
}

function app_base_url(): string
{
    $fromEnv = trim((string) getenv('APP_URL'));
    if ($fromEnv !== '') {
        return rtrim($fromEnv, '/');
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $scheme = $isHttps ? 'https://' : 'http://';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    return rtrim($scheme . $host, '/');
}

function send_password_reset_email(string $toEmail, string $toName, string $resetPathWithQuery): bool
{
    global $mailConfig;

    $fromEmail = (string) ($mailConfig['from_email'] ?? 'no-reply@maa-saraswati.co.in');
    $fromName = (string) ($mailConfig['from_name'] ?? 'Maa Saraswati Sansthan');
    $resetUrl = app_base_url() . '/' . ltrim($resetPathWithQuery, '/');

    $subject = 'Password Reset Request - Maa Saraswati Sansthan';
    $safeName = $toName === '' ? 'User' : $toName;
    $body = "Namaste {$safeName},\n\n"
        . "We received a request to reset your password.\n"
        . "Please use the link below within 30 minutes:\n\n"
        . $resetUrl . "\n\n"
        . "If you did not request this, you can ignore this email.\n\n"
        . "Maa Saraswati Sansthan";

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
    ];

    $smtpHost = trim((string) ($mailConfig['smtp_host'] ?? ''));
    if ($smtpHost !== '') {
        ini_set('SMTP', $smtpHost);
        ini_set('smtp_port', (string) ((int) ($mailConfig['smtp_port'] ?? 25)));
        ini_set('sendmail_from', $fromEmail);
    }

    $ok = @mail($toEmail, $subject, $body, implode("\r\n", $headers));

    $logFile = (string) ($mailConfig['log_file'] ?? '');
    if ($logFile !== '') {
        $line = sprintf(
            "[%s] to=%s status=%s url=%s%s",
            date('c'),
            $toEmail,
            $ok ? 'sent' : 'failed',
            $resetUrl,
            PHP_EOL
        );
        @file_put_contents($logFile, $line, FILE_APPEND);
    }

    return $ok;
}

function is_login_rate_limited(string $email, string $ip): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return false;
    }

    global $security;
    $cfg = $security['login_rate_limit'] ?? [];
    $maxAttempts = (int) ($cfg['max_attempts'] ?? 5);
    $windowSeconds = (int) ($cfg['window_seconds'] ?? 900);

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS attempt_count
         FROM login_attempts
         WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL :window SECOND)
           AND (email = :email OR ip_address = :ip)'
    );
    $stmt->bindValue(':window', $windowSeconds, PDO::PARAM_INT);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch();

    return ((int) ($row['attempt_count'] ?? 0)) >= $maxAttempts;
}

function record_login_attempt(string $email, string $ip): void
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (:email, :ip, NOW())'
    );
    $stmt->execute([
        ':email' => $email,
        ':ip' => $ip,
    ]);
}

function clear_login_attempts(string $email, string $ip): void
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email OR ip_address = :ip');
    $stmt->execute([
        ':email' => $email,
        ':ip' => $ip,
    ]);
}

function is_user_locked(array $user): bool
{
    $lockUntil = trim((string) ($user['lock_until'] ?? ''));
    if ($lockUntil === '') {
        return false;
    }

    return strtotime($lockUntil) > time();
}

function register_user_login_failure(int $userId): void
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return;
    }

    global $security;
    $cfg = $security['login_rate_limit'] ?? [];
    $maxAttempts = (int) ($cfg['max_attempts'] ?? 5);
    $lockSeconds = (int) ($cfg['lock_seconds'] ?? 900);

    $stmt = $pdo->prepare(
        'UPDATE users
         SET failed_login_attempts = failed_login_attempts + 1,
             lock_until = CASE
                WHEN failed_login_attempts + 1 >= :max_attempts THEN DATE_ADD(NOW(), INTERVAL :lock_seconds SECOND)
                ELSE lock_until
             END,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->bindValue(':max_attempts', $maxAttempts, PDO::PARAM_INT);
    $stmt->bindValue(':lock_seconds', $lockSeconds, PDO::PARAM_INT);
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
}

function clear_user_login_failures(int $userId): void
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return;
    }

    $stmt = $pdo->prepare(
        'UPDATE users
         SET failed_login_attempts = 0, lock_until = NULL, updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([':id' => $userId]);
}

function validate_and_move_upload(array $file, array $allowed, string $targetDir, string $prefix = ''): array
{
    global $security;
    $maxBytes = (int) (($security['uploads']['max_bytes'] ?? 5 * 1024 * 1024));

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        return ['ok' => false, 'error' => 'File size is invalid or exceeds limit.'];
    }

    $name = basename((string) ($file['name'] ?? 'file'));
    $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        return ['ok' => false, 'error' => 'File extension is not allowed.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMimes = $allowed[$ext];
    if (!in_array($mime, $allowedMimes, true)) {
        return ['ok' => false, 'error' => 'File MIME type is not allowed.'];
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    $filename = ($prefix !== '' ? $prefix : time() . '_') . $safe;
    $target = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        return ['ok' => false, 'error' => 'Unable to store uploaded file.'];
    }

    return [
        'ok' => true,
        'filename' => $filename,
        'path' => $target,
        'mime' => $mime,
    ];
}

function save_contact_message(array $payload): bool
{
    $pdo = db();
    if ($pdo instanceof PDO) {
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, message, ip_address, created_at) VALUES (:name, :email, :message, :ip, NOW())'
        );

        return $stmt->execute([
            ':name' => (string) ($payload['name'] ?? ''),
            ':email' => (string) ($payload['email'] ?? ''),
            ':message' => (string) ($payload['message'] ?? ''),
            ':ip' => (string) ($payload['ip'] ?? ''),
        ]);
    }

    $file = __DIR__ . '/../data/contact-messages.json';
    $rows = read_json_file($file, []);
    $rows[] = $payload;

    return file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function whatsapp_phone_number(string $rawNumber): string
{
    // wa.me requires digits only in international format (country code + number).
    $digits = preg_replace('/\D+/', '', $rawNumber) ?? '';

    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    // Default India numbers to +91 when 10-digit local mobile is configured.
    if (strlen($digits) === 10) {
        return '91' . $digits;
    }

    return $digits;
}

function uploaded_qr_path(): string
{
    $candidates = glob(__DIR__ . '/../uploads/qr/*.{png,jpg,jpeg,webp,svg}', GLOB_BRACE);
    if ($candidates && isset($candidates[0])) {
        return str_replace('\\', '/', 'uploads/qr/' . basename($candidates[0]));
    }

    if (file_exists(__DIR__ . '/../assets/images/QR.jpeg')) {
        return 'assets/images/QR.jpeg';
    }

    return 'assets/images/qr-placeholder.svg';
}

function uploaded_gallery_items(array $fallback): array
{
    $items = $fallback;
    $files = glob(__DIR__ . '/../uploads/gallery/*.{png,jpg,jpeg,webp,avif}', GLOB_BRACE);

    foreach ($files as $file) {
        $items[] = [
            'category' => 'Events',
            'file' => str_replace('\\', '/', 'uploads/gallery/' . basename($file)),
            'title' => pathinfo($file, PATHINFO_FILENAME),
        ];
    }

    return $items;
}

function load_events(array $fallback): array
{
    $pdo = db();
    if ($pdo instanceof PDO) {
        $rows = $pdo->query('SELECT title, event_date AS date, location FROM events ORDER BY display_order ASC, id DESC')->fetchAll();
        if (is_array($rows) && !empty($rows)) {
            return $rows;
        }
    }

    return read_json_file(__DIR__ . '/../data/events.json', $fallback);
}

function save_events(array $events): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return file_put_contents(
            __DIR__ . '/../data/events.json',
            json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM events');
        $stmt = $pdo->prepare('INSERT INTO events (title, event_date, location, display_order, created_at) VALUES (:title, :event_date, :location, :display_order, NOW())');

        foreach (array_values($events) as $i => $event) {
            $stmt->execute([
                ':title' => (string) ($event['title'] ?? ''),
                ':event_date' => (string) ($event['date'] ?? ''),
                ':location' => (string) ($event['location'] ?? ''),
                ':display_order' => $i + 1,
            ]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        return false;
    }
}

function load_videos(array $fallback): array
{
    $pdo = db();
    if ($pdo instanceof PDO) {
        $rows = $pdo->query('SELECT title, youtube_id, description FROM videos ORDER BY display_order ASC, id DESC')->fetchAll();
        if (is_array($rows) && !empty($rows)) {
            return $rows;
        }
    }

    return read_json_file(__DIR__ . '/../data/videos.json', $fallback);
}

function load_gallery_items(array $fallback): array
{
    $pdo = db();
    $items = [];

    if ($pdo instanceof PDO) {
        $rows = $pdo->query('SELECT category, file_path AS file, title FROM gallery_images ORDER BY display_order ASC, id DESC')->fetchAll();
        if (is_array($rows) && !empty($rows)) {
            $items = $rows;
        }
    }

    if (empty($items)) {
        $items = $fallback;
    }

    $files = glob(__DIR__ . '/../uploads/gallery/*.{png,jpg,jpeg,webp,avif}', GLOB_BRACE);
    foreach ($files as $file) {
        $items[] = [
            'category' => 'Events',
            'file' => str_replace('\\', '/', 'uploads/gallery/' . basename($file)),
            'title' => pathinfo($file, PATHINFO_FILENAME),
        ];
    }

    return $items;
}

function save_gallery_item(string $category, string $filePath, string $title): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return true;
    }

    $max = $pdo->query('SELECT COALESCE(MAX(display_order), 0) AS max_order FROM gallery_images')->fetch();
    $nextOrder = ((int) ($max['max_order'] ?? 0)) + 1;

    $stmt = $pdo->prepare(
        'INSERT INTO gallery_images (category, file_path, title, display_order, created_at) VALUES (:category, :file_path, :title, :display_order, NOW())'
    );

    return $stmt->execute([
        ':category' => $category,
        ':file_path' => $filePath,
        ':title' => $title,
        ':display_order' => $nextOrder,
    ]);
}

function events_for_editor(array $fallback): string
{
    return json_encode(load_events($fallback), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]';
}

function find_user_by_email(string $email): ?array
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role, profile_image, is_active, failed_login_attempts, lock_until FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => strtolower(trim($email))]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function create_user(string $fullName, string $email, string $password, string $role = 'member'): array
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return ['ok' => false, 'message' => 'Database unavailable.'];
    }

    $fullName = trim($fullName);
    $email = strtolower(trim($email));

    if ($fullName === '' || $email === '' || $password === '') {
        return ['ok' => false, 'message' => 'All fields are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Invalid email format.'];
    }

    $policyError = password_policy_error($password);
    if ($policyError !== null) {
        return ['ok' => false, 'message' => $policyError];
    }

    if (find_user_by_email($email) !== null) {
        return ['ok' => false, 'message' => 'Email already registered.'];
    }

    $allowedRoles = ['member', 'adhyaksh', 'sachiv', 'admin'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'member';
    }

    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, is_active, created_at) VALUES (:full_name, :email, :password_hash, :role, 1, NOW())');
    $ok = $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role,
    ]);

    return [
        'ok' => $ok,
        'message' => $ok ? 'Signup successful.' : 'Unable to create user.',
        'user_id' => $ok ? (int) $pdo->lastInsertId() : null,
    ];
}

function update_user_password(int $userId, string $newPassword): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return false;
    }

    if (password_policy_error($newPassword) !== null) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
    return $stmt->execute([
        ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id' => $userId,
    ]);
}

function create_password_reset_token(int $userId): ?string
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return null;
    }

    $token = bin2hex(random_bytes(24));
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())');
    $ok = $stmt->execute([
        ':user_id' => $userId,
        ':token_hash' => $tokenHash,
    ]);

    return $ok ? $token : null;
}

function find_user_by_id(int $userId): ?array
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, full_name, email, role, profile_image, is_active FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function admin_list_users(): array
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return [];
    }

    $rows = $pdo->query('SELECT id, full_name, email, role, is_active, created_at, updated_at FROM users ORDER BY created_at DESC')->fetchAll();
    return is_array($rows) ? $rows : [];
}

function admin_set_user_active(int $userId, bool $active): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE users SET is_active = :active, updated_at = NOW() WHERE id = :id');
    return $stmt->execute([
        ':active' => $active ? 1 : 0,
        ':id' => $userId,
    ]);
}

function find_valid_password_reset(string $token): ?array
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT pr.id, pr.user_id, u.email
         FROM password_resets pr
         INNER JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = :token_hash
           AND pr.used_at IS NULL
           AND pr.expires_at >= NOW()
         LIMIT 1'
    );
    $stmt->execute([':token_hash' => hash('sha256', $token)]);
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

function mark_password_reset_used(int $resetId): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
    return $stmt->execute([':id' => $resetId]);
}

function user_donations(int $userId): array
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT amount, method, transaction_ref, status, donated_at, notes FROM donations WHERE user_id = :user_id ORDER BY donated_at DESC');
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll() ?: [];
}

function create_donation(int $userId, float $amount, string $method, string $transactionRef = '', string $notes = ''): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO) || $amount <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('INSERT INTO donations (user_id, amount, method, transaction_ref, status, notes, donated_at, created_at) VALUES (:user_id, :amount, :method, :transaction_ref, :status, :notes, NOW(), NOW())');
    return $stmt->execute([
        ':user_id' => $userId,
        ':amount' => $amount,
        ':method' => trim($method) === '' ? 'UPI' : trim($method),
        ':transaction_ref' => trim($transactionRef),
        ':status' => 'success',
        ':notes' => trim($notes),
    ]);
}

function create_callback_donation(array $payload): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return false;
    }

    $amountRaw = $payload['amount'] ?? $payload['amount_in_paise'] ?? 0;
    $amount = (float) $amountRaw;
    if ($amount > 0 && $amount > 1000 && isset($payload['amount_in_paise'])) {
        $amount = $amount / 100;
    }
    if ($amount > 0 && $amount <= 1000 && isset($payload['amount']) && ((int) $amount % 100 === 0) && isset($payload['currency']) && strtoupper((string) $payload['currency']) === 'INR') {
        $amount = $amount / 100;
    }

    if ($amount <= 0) {
        return false;
    }

    $statusRaw = strtolower(trim((string) ($payload['status'] ?? 'pending')));
    $status = in_array($statusRaw, ['success', 'failed', 'pending'], true) ? $statusRaw : 'pending';

    $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : null;
    if ($userId !== null && $userId <= 0) {
        $userId = null;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO donations (
            user_id, amount, method, transaction_ref, status, notes, donated_at, created_at,
            donor_name, donor_email, callback_source, payment_id, payload_json
        ) VALUES (
            :user_id, :amount, :method, :transaction_ref, :status, :notes, NOW(), NOW(),
            :donor_name, :donor_email, :callback_source, :payment_id, :payload_json
        )'
    );

    return $stmt->execute([
        ':user_id' => $userId,
        ':amount' => $amount,
        ':method' => trim((string) ($payload['method'] ?? 'UPI')),
        ':transaction_ref' => trim((string) ($payload['transaction_ref'] ?? ($payload['order_id'] ?? ''))),
        ':status' => $status,
        ':notes' => trim((string) ($payload['notes'] ?? 'Captured via payment callback')),
        ':donor_name' => trim((string) ($payload['donor_name'] ?? '')),
        ':donor_email' => trim((string) ($payload['donor_email'] ?? '')),
        ':callback_source' => trim((string) ($payload['source'] ?? 'unknown')),
        ':payment_id' => trim((string) ($payload['payment_id'] ?? '')),
        ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
}

function update_profile_image(int $userId, string $path): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE users SET profile_image = :profile_image, updated_at = NOW() WHERE id = :id');
    return $stmt->execute([':profile_image' => $path, ':id' => $userId]);
}

function donation_upi_link(): string
{
    global $site;

    $fileOverride = __DIR__ . '/../data/upi-uri.txt';
    if (file_exists($fileOverride)) {
        $fromFile = trim((string) file_get_contents($fileOverride));
        if ($fromFile !== '' && stripos($fromFile, 'upi://pay?') === 0) {
            return $fromFile;
        }
    }

    $configured = trim((string) ($site['bank']['upi_uri'] ?? ''));
    if ($configured !== '' && stripos($configured, 'upi://pay?') === 0) {
        return $configured;
    }

    $upiId = trim((string) ($site['bank']['upi_id'] ?? ''));
    $name = trim((string) ($site['bank']['account_name'] ?? 'Maa Saraswati Sansthan'));

    return 'upi://pay?pa=' . rawurlencode($upiId)
        . '&pn=' . rawurlencode($name)
        . '&cu=INR';
}

function donation_upi_intent_link(string $upiLink): string
{
    if (stripos($upiLink, 'upi://') !== 0) {
        return $upiLink;
    }

    return 'intent://' . substr($upiLink, strlen('upi://')) . '#Intent;scheme=upi;end';
}

function localized_page_title(string $title): string
{
    global $site;
    return $title . ' | ' . $site['name'];
}

function admin_credential_keys(): array
{
    return [
        'MAIL_FROM_EMAIL',
        'MAIL_FROM_NAME',
        'SMTP_HOST',
        'SMTP_PORT',
        'SMTP_USER',
        'SMTP_PASS',
        'PAYMENT_CALLBACK_SECRET',
    ];
}

function admin_credentials_values(): array
{
    $pdo = db();
    $defaults = array_fill_keys(admin_credential_keys(), '');

    if (!($pdo instanceof PDO)) {
        return $defaults;
    }

    $rows = $pdo->query('SELECT setting_key, setting_value FROM admin_credentials')->fetchAll();
    if (!is_array($rows)) {
        return $defaults;
    }

    foreach ($rows as $row) {
        $key = (string) ($row['setting_key'] ?? '');
        if (!array_key_exists($key, $defaults)) {
            continue;
        }
        $defaults[$key] = (string) ($row['setting_value'] ?? '');
    }

    return $defaults;
}

function admin_update_credentials(array $payload, int $updatedBy): bool
{
    $pdo = db();
    if (!($pdo instanceof PDO)) {
        return false;
    }

    $allowed = admin_credential_keys();
    $secretKeys = ['SMTP_PASS', 'PAYMENT_CALLBACK_SECRET'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO admin_credentials (setting_key, setting_value, is_secret, updated_by, updated_at)
             VALUES (:setting_key, :setting_value, :is_secret, :updated_by, NOW())
             ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                is_secret = VALUES(is_secret),
                updated_by = VALUES(updated_by),
                updated_at = NOW()'
        );

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = trim((string) $payload[$key]);
            if ($key === 'SMTP_PORT' && $value !== '' && !ctype_digit($value)) {
                throw new RuntimeException('SMTP_PORT must be numeric.');
            }

            $stmt->execute([
                ':setting_key' => $key,
                ':setting_value' => $value,
                ':is_secret' => in_array($key, $secretKeys, true) ? 1 : 0,
                ':updated_by' => $updatedBy,
            ]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        return false;
    }
}
