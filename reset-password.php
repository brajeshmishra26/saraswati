<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = localized_page_title('Set New Password');
$message = '';
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$validReset = $token !== '' ? find_valid_password_reset($token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'reset_password_form')) {
        $message = 'Security token mismatch. Please refresh and try again.';
    }

    $newPassword = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($message !== '') {
        // Keep current message.
    } elseif ($validReset === null) {
        $message = 'Invalid or expired reset token.';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } elseif (($policyError = password_policy_error($newPassword)) !== null) {
        $message = $policyError;
    } else {
        $updated = update_user_password((int) $validReset['user_id'], $newPassword);
        if ($updated) {
            mark_password_reset_used((int) $validReset['id']);
            header('Location: login.php?lang=' . urlencode($lang));
            exit;
        }
        $message = 'Unable to update password.';
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="container py-5" style="max-width: 680px;">
    <h1 class="font-heading mb-4">Set New Password</h1>

    <?php if ($message !== ''): ?>
        <div class="alert alert-danger"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($validReset === null): ?>
        <div class="alert alert-warning">Invalid or expired token. Please request reset again.</div>
        <a href="forgot-password.php?lang=<?= h($lang) ?>" class="btn btn-warning">Request Reset</a>
    <?php else: ?>
        <div class="section-card rounded-4 p-4">
            <form method="post" action="reset-password.php?lang=<?= h($lang) ?>">
                <?= csrf_input('reset_password_form') ?>
                <input type="hidden" name="token" value="<?= h($token) ?>">
                <div class="mb-3">
                    <label class="form-label" for="password">New Password</label>
                    <input class="form-control" id="password" name="password" type="password" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="8" required>
                </div>
                <button class="btn btn-warning" type="submit">Update Password</button>
            </form>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
