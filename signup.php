<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (auth_user() !== null) {
    header('Location: profile.php?lang=' . urlencode($lang));
    exit;
}

$pageTitle = localized_page_title('Signup');
$message = '';
$selectedRole = 'member';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'signup_form')) {
        $message = 'Security token mismatch. Please refresh and try again.';
    }

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $selectedRole = (string) ($_POST['role'] ?? 'member');

    if ($message !== '') {
        // Keep current message.
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } else {
        $result = create_user($fullName, $email, $password, $selectedRole);
        $message = (string) ($result['message'] ?? 'Unable to signup.');

        if (!empty($result['ok']) && !empty($result['user_id'])) {
            auth_login((int) $result['user_id']);
            header('Location: profile.php?lang=' . urlencode($lang));
            exit;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="container py-5" style="max-width: 760px;">
    <h1 class="font-heading mb-4">Signup</h1>
    <?php if ($message !== ''): ?>
        <div class="alert alert-info"><?= h($message) ?></div>
    <?php endif; ?>
    <div class="section-card rounded-4 p-4">
        <form method="post" action="signup.php?lang=<?= h($lang) ?>">
            <?= csrf_input('signup_form') ?>
            <div class="mb-3">
                <label class="form-label" for="full_name">Full Name</label>
                <input class="form-control" id="full_name" name="full_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="role">Role</label>
                <select class="form-select" id="role" name="role">
                    <option value="member" <?= $selectedRole === 'member' ? 'selected' : '' ?>>Member</option>
                    <option value="adhyaksh" <?= $selectedRole === 'adhyaksh' ? 'selected' : '' ?>>Adhyaksh</option>
                    <option value="sachiv" <?= $selectedRole === 'sachiv' ? 'selected' : '' ?>>Sachiv</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" minlength="8" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="8" required>
            </div>
            <button class="btn btn-warning" type="submit">Create Account</button>
            <a class="btn btn-link" href="login.php?lang=<?= h($lang) ?>">Already have account? Login</a>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
