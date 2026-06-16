<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

auth_require_login();
$user = auth_user();
$pageTitle = localized_page_title('Profile');
$message = '';

if ($user === null) {
    header('Location: login.php?lang=' . urlencode($lang));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_donation'])) {
        if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'profile_donation_form')) {
            $message = 'Security token mismatch. Please refresh and try again.';
        }

        $amount = (float) ($_POST['amount'] ?? 0);
        $method = trim((string) ($_POST['method'] ?? 'UPI'));
        $reference = trim((string) ($_POST['transaction_ref'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($message === '') {
            $saved = create_donation((int) $user['id'], $amount, $method, $reference, $notes);
            $message = $saved ? 'Donation entry saved.' : 'Unable to save donation entry.';
        }
    }

    if (isset($_POST['upload_profile']) && isset($_FILES['profile_image']) && is_uploaded_file($_FILES['profile_image']['tmp_name'])) {
        if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'profile_upload_form')) {
            $message = 'Security token mismatch. Please refresh and try again.';
        }

        $upload = $message === ''
            ? validate_and_move_upload(
                $_FILES['profile_image'],
                [
                    'jpg' => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'png' => ['image/png'],
                    'webp' => ['image/webp'],
                ],
                __DIR__ . '/uploads/profiles/'
            )
            : ['ok' => false, 'error' => $message];

        if (!empty($upload['ok'])) {
            $relative = 'uploads/profiles/' . basename((string) $upload['path']);
            $ok = update_profile_image((int) $user['id'], $relative);
            $message = $ok ? 'Profile image updated.' : 'Image uploaded but profile not updated.';
        } else {
            $message = (string) ($upload['error'] ?? 'Unable to upload profile image.');
        }
    }

    $user = auth_user();
}

$donations = user_donations((int) $user['id']);
$profileImage = trim((string) ($user['profile_image'] ?? ''));
if ($profileImage === '' || !file_exists(__DIR__ . '/' . $profileImage)) {
    $profileImage = 'assets/images/logo.jpeg';
}

require __DIR__ . '/includes/header.php';
?>
<section class="container py-5">
    <h1 class="font-heading mb-4">Profile</h1>
    <?php if ($message !== ''): ?>
        <div class="alert alert-info"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="section-card rounded-4 p-4 h-100">
                <img src="<?= h($profileImage) ?>" alt="Profile" class="rounded-circle border border-warning-subtle mb-3" style="width:130px;height:130px;object-fit:cover;">
                <h2 class="h5 mb-1"><?= h((string) $user['full_name']) ?></h2>
                <div class="small text-muted mb-2"><?= h((string) $user['email']) ?></div>
                <div class="badge text-bg-warning mb-3"><?= h(auth_role_label((string) $user['role'])) ?></div>

                <form method="post" enctype="multipart/form-data" action="profile.php?lang=<?= h($lang) ?>">
                    <?= csrf_input('profile_upload_form') ?>
                    <label class="form-label" for="profile_image">Change / Upload Profile Pic</label>
                    <input class="form-control mb-2" type="file" name="profile_image" id="profile_image" accept="image/*" required>
                    <button class="btn btn-warning btn-sm" name="upload_profile" value="1" type="submit">Upload</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="section-card rounded-4 p-4 mb-4">
                <h2 class="h5 mb-3">Add Donation History Entry</h2>
                <form method="post" action="profile.php?lang=<?= h($lang) ?>" class="row g-2">
                    <?= csrf_input('profile_donation_form') ?>
                    <div class="col-md-4">
                        <label class="form-label" for="amount">Amount</label>
                        <input class="form-control" id="amount" name="amount" type="number" min="1" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="method">Method</label>
                        <input class="form-control" id="method" name="method" value="UPI" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="transaction_ref">Transaction Ref</label>
                        <input class="form-control" id="transaction_ref" name="transaction_ref">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <input class="form-control" id="notes" name="notes">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-warning" type="submit" name="save_donation" value="1">Save Entry</button>
                    </div>
                </form>
            </div>

            <div class="section-card rounded-4 p-4">
                <h2 class="h5 mb-3">Donation History</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Ref</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($donations)): ?>
                            <tr><td colspan="5" class="text-muted">No donations recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($donations as $donation): ?>
                                <tr>
                                    <td><?= h((string) $donation['donated_at']) ?></td>
                                    <td>INR <?= h(number_format((float) $donation['amount'], 2)) ?></td>
                                    <td><?= h((string) $donation['method']) ?></td>
                                    <td><?= h((string) ($donation['transaction_ref'] ?? '')) ?></td>
                                    <td><?= h((string) $donation['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
