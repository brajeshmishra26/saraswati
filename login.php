<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (auth_user() !== null) {
    header('Location: profile.php?lang=' . urlencode($lang));
    exit;
}

$pageTitle = localized_page_title('Login');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfOk = csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'login_form');
    if (!$csrfOk) {
        $message = 'Security token mismatch. Please refresh and try again.';
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    if ($message === '' && is_login_rate_limited($email, $ip)) {
        $message = 'Too many login attempts. Please try again after 15 minutes.';
    }

    if ($message === '') {
        $user = find_user_by_email($email);

        if ($user === null || (int) ($user['is_active'] ?? 1) !== 1) {
            record_login_attempt($email, $ip);
            $message = 'Invalid email or password.';
        } elseif (is_user_locked($user)) {
            record_login_attempt($email, $ip);
            $message = 'Your account is temporarily locked due to failed login attempts. Try again later.';
        } elseif (password_verify($password, (string) $user['password_hash'])) {
            clear_login_attempts($email, $ip);
            clear_user_login_failures((int) $user['id']);
            auth_login((int) $user['id']);
            header('Location: profile.php?lang=' . urlencode($lang));
            exit;
        } else {
            record_login_attempt($email, $ip);
            register_user_login_failure((int) $user['id']);
            $message = 'Invalid email or password.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="container py-5" style="max-width: 680px;">
    <h1 class="font-heading mb-4">Login</h1>
    <?php if ($message !== ''): ?>
        <div class="alert alert-danger"><?= h($message) ?></div>
    <?php endif; ?>
    <div class="section-card rounded-4 p-4">
        <form method="post" action="login.php?lang=<?= h($lang) ?>">
            <?= csrf_input('login_form') ?>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" required>
            </div>
            <button class="btn btn-warning" type="submit">Login</button>
            <a class="btn btn-link" href="forgot-password.php?lang=<?= h($lang) ?>">Forgot password?</a>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
