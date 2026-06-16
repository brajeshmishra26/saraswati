<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

auth_require_login();
auth_require_roles(['admin']);

$authUser = auth_user();
$message = '';

if (!empty($_POST['save_events_json'])) {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'admin_events_form')) {
        $message = 'Security token mismatch. Please refresh and try again.';
    }
}

if (!empty($_POST['save_events_json'])) {
    if ($message === '') {
        $json = (string) $_POST['events_json'];
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $saved = save_events($decoded);
            $message = $saved ? 'Events updated.' : 'Events update failed.';
        } else {
            $message = 'Invalid JSON format.';
        }
    }
}

if (isset($_POST['upload_type']) && isset($_FILES['file_upload']) && is_uploaded_file($_FILES['file_upload']['tmp_name'])) {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'admin_upload_form')) {
        $message = 'Security token mismatch. Please refresh and try again.';
    }

    $type = (string) $_POST['upload_type'];
    $map = [
        'gallery' => [
            'dir' => __DIR__ . '/../uploads/gallery/',
            'allowed' => [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'webp' => ['image/webp'],
                'avif' => ['image/avif'],
            ],
        ],
        'qr' => [
            'dir' => __DIR__ . '/../uploads/qr/',
            'allowed' => [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'webp' => ['image/webp'],
                'svg' => ['image/svg+xml'],
            ],
        ],
        'audio' => [
            'dir' => __DIR__ . '/../uploads/audio/',
            'allowed' => [
                'mp3' => ['audio/mpeg'],
                'wav' => ['audio/wav', 'audio/x-wav'],
                'ogg' => ['audio/ogg'],
            ],
        ],
        'docs' => [
            'dir' => __DIR__ . '/../uploads/docs/',
            'allowed' => [
                'pdf' => ['application/pdf'],
            ],
        ],
    ];

    if ($message === '' && isset($map[$type])) {
        $upload = validate_and_move_upload(
            $_FILES['file_upload'],
            $map[$type]['allowed'],
            $map[$type]['dir']
        );

        if (!empty($upload['ok'])) {
            if ($type === 'gallery') {
                $relativePath = 'uploads/gallery/' . basename((string) $upload['path']);
                save_gallery_item('Events', $relativePath, pathinfo((string) $upload['path'], PATHINFO_FILENAME));
            }
            $message = 'Upload successful.';
        } else {
            $message = (string) ($upload['error'] ?? 'Upload failed.');
        }
    }
}

if (!empty($_POST['create_user'])) {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'admin_create_user_form')) {
        $message = 'Security token mismatch. Please refresh and try again.';
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'member');
        $result = create_user($fullName, $email, $password, $role);
        $message = (string) ($result['message'] ?? 'Unable to create user.');
    }
}

if (!empty($_POST['toggle_user_active'])) {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'admin_toggle_user_form')) {
        $message = 'Security token mismatch. Please refresh and try again.';
    } else {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 1) === 1;

        if ($targetUserId === (int) ($authUser['id'] ?? 0)) {
            $message = 'You cannot deactivate your own admin account.';
        } else {
            $ok = admin_set_user_active($targetUserId, !$active);
            $message = $ok ? 'User status updated.' : 'Unable to update user status.';
        }
    }
}

if (!empty($_POST['save_admin_credentials'])) {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'admin_credentials_form')) {
        $message = 'Security token mismatch. Please refresh and try again.';
    } else {
        $input = [
            'MAIL_FROM_EMAIL' => (string) ($_POST['MAIL_FROM_EMAIL'] ?? ''),
            'MAIL_FROM_NAME' => (string) ($_POST['MAIL_FROM_NAME'] ?? ''),
            'SMTP_HOST' => (string) ($_POST['SMTP_HOST'] ?? ''),
            'SMTP_PORT' => (string) ($_POST['SMTP_PORT'] ?? ''),
            'SMTP_USER' => (string) ($_POST['SMTP_USER'] ?? ''),
            'SMTP_PASS' => (string) ($_POST['SMTP_PASS'] ?? ''),
            'PAYMENT_CALLBACK_SECRET' => (string) ($_POST['PAYMENT_CALLBACK_SECRET'] ?? ''),
        ];

        $ok = admin_update_credentials($input, (int) ($authUser['id'] ?? 0));
        $message = $ok ? 'Admin credential settings updated.' : 'Unable to update admin credential settings.';
    }
}

$eventsJson = events_for_editor($defaultEvents);
$dbStatus = db_ready() ? 'Connected' : 'Not connected (fallback mode)';
$users = admin_list_users();
$adminCredentials = admin_credentials_values();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel | Maa Saraswati Sansthan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Admin Panel</h1>
                    <p class="small text-muted mb-2">Logged in as <?= h((string) ($authUser['email'] ?? 'admin')) ?></p>
                    <p class="small mb-3">Database status: <strong><?= h($dbStatus) ?></strong></p>
                    <?php if ($message !== ''): ?>
                        <div class="alert alert-info"><?= h($message) ?></div>
                    <?php endif; ?>

                    <a class="btn btn-outline-secondary btn-sm mb-3" href="../profile.php?lang=<?= h($lang) ?>">Back to Profile</a>

                    <h2 class="h5 mt-2">Upload Media</h2>
                    <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
                        <?= csrf_input('admin_upload_form') ?>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="upload_type" required>
                                <option value="gallery">Gallery Image</option>
                                <option value="qr">Donation QR</option>
                                <option value="audio">Aarti Audio</option>
                                <option value="docs">Aarti PDF</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">File</label>
                            <input class="form-control" type="file" name="file_upload" required>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-warning w-100" type="submit">Upload</button>
                        </div>
                    </form>

                    <h2 class="h5 mt-4">Update Events JSON</h2>
                    <form method="post">
                        <?= csrf_input('admin_events_form') ?>
                        <textarea class="form-control" rows="10" name="events_json"><?= h((string) $eventsJson) ?></textarea>
                        <button class="btn btn-warning mt-2" name="save_events_json" value="1" type="submit">Save Events</button>
                    </form>

                    <h2 class="h5 mt-4">Create User</h2>
                    <form method="post" class="row g-2 align-items-end">
                        <?= csrf_input('admin_create_user_form') ?>
                        <div class="col-md-4">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input class="form-control" type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" type="email" id="email" name="email" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="password">Password</label>
                            <input class="form-control" type="password" id="password" name="password" minlength="8" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="role">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="member">Member</option>
                                <option value="adhyaksh">Adhyaksh</option>
                                <option value="sachiv">Sachiv</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-warning" name="create_user" value="1" type="submit">Create User</button>
                        </div>
                    </form>

                    <h2 class="h5 mt-4">User Management</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $row): ?>
                                <tr>
                                    <td><?= h((string) $row['full_name']) ?></td>
                                    <td><?= h((string) $row['email']) ?></td>
                                    <td><?= h(auth_role_label((string) $row['role'])) ?></td>
                                    <td>
                                        <?php if ((int) ($row['is_active'] ?? 1) === 1): ?>
                                            <span class="badge text-bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <?= csrf_input('admin_toggle_user_form') ?>
                                            <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                            <input type="hidden" name="active" value="<?= (int) ($row['is_active'] ?? 1) ?>">
                                            <button class="btn btn-sm btn-outline-warning" type="submit" name="toggle_user_active" value="1">
                                                <?= (int) ($row['is_active'] ?? 1) === 1 ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h2 class="h5 mt-4">Mail + Callback Settings</h2>
                    <form method="post" class="row g-2 align-items-end">
                        <?= csrf_input('admin_credentials_form') ?>
                        <div class="col-md-6">
                            <label class="form-label" for="MAIL_FROM_EMAIL">MAIL_FROM_EMAIL</label>
                            <input class="form-control" type="email" id="MAIL_FROM_EMAIL" name="MAIL_FROM_EMAIL" value="<?= h((string) ($adminCredentials['MAIL_FROM_EMAIL'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="MAIL_FROM_NAME">MAIL_FROM_NAME</label>
                            <input class="form-control" type="text" id="MAIL_FROM_NAME" name="MAIL_FROM_NAME" value="<?= h((string) ($adminCredentials['MAIL_FROM_NAME'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="SMTP_HOST">SMTP_HOST</label>
                            <input class="form-control" type="text" id="SMTP_HOST" name="SMTP_HOST" value="<?= h((string) ($adminCredentials['SMTP_HOST'] ?? '')) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="SMTP_PORT">SMTP_PORT</label>
                            <input class="form-control" type="number" id="SMTP_PORT" name="SMTP_PORT" min="1" max="65535" value="<?= h((string) ($adminCredentials['SMTP_PORT'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="SMTP_USER">SMTP_USER</label>
                            <input class="form-control" type="text" id="SMTP_USER" name="SMTP_USER" value="<?= h((string) ($adminCredentials['SMTP_USER'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="SMTP_PASS">SMTP_PASS</label>
                            <input class="form-control" type="password" id="SMTP_PASS" name="SMTP_PASS" value="<?= h((string) ($adminCredentials['SMTP_PASS'] ?? '')) ?>" autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="PAYMENT_CALLBACK_SECRET">PAYMENT_CALLBACK_SECRET</label>
                            <input class="form-control" type="password" id="PAYMENT_CALLBACK_SECRET" name="PAYMENT_CALLBACK_SECRET" value="<?= h((string) ($adminCredentials['PAYMENT_CALLBACK_SECRET'] ?? '')) ?>" autocomplete="new-password">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-warning" name="save_admin_credentials" value="1" type="submit">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
