<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = localized_page_title('Reset Password');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'forgot_password_form')) {
        $message = 'Security token mismatch. Please refresh and try again.';
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    $user = $message === '' ? find_user_by_email($email) : null;

    if ($user !== null) {
        $token = create_password_reset_token((int) $user['id']);
        if ($token !== null) {
            $resetPath = 'reset-password.php?token=' . urlencode($token) . '&lang=' . urlencode($lang);
            send_password_reset_email((string) $user['email'], (string) ($user['full_name'] ?? ''), $resetPath);
        }
    }

    if ($message === '') {
        $message = 'If this email exists, a password reset link has been emailed.';
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="container py-5" style="max-width: 680px;">
    <h1 class="font-heading mb-4">Reset Password</h1>
    <?php if ($message !== ''): ?>
        <div class="alert alert-info"><?= h($message) ?></div>
    <?php endif; ?>
    <div class="section-card rounded-4 p-4">
        <form method="post" action="forgot-password.php?lang=<?= h($lang) ?>">
            <?= csrf_input('forgot_password_form') ?>
            <div class="mb-3">
                <label class="form-label" for="email">Registered Email</label>
                <input class="form-control" id="email" name="email" type="email" required>
            </div>
            <button class="btn btn-warning" type="submit">Send Reset Link</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
