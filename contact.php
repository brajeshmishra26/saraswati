<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = localized_page_title(t('contact'));

$status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''), 'contact_form')) {
        $status = 'Security token mismatch. Please refresh and try again.';
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($status !== '') {
        // Keep current status.
    } elseif ($name !== '' && $message !== '') {
        $saved = save_contact_message([
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'created_at' => date('c'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
        $status = $saved ? 'Message sent successfully.' : 'Unable to save message. Please try again.';
    } else {
        $status = 'Please fill required fields.';
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <h1 class="font-heading mb-4 fade-up"><?= h(t('contact')) ?></h1>

    <div class="row g-4">
        <div class="col-lg-6 fade-up">
            <div class="section-card rounded-4 p-4 h-100">
                <h2 class="h5 font-heading mb-3">Phone Numbers</h2>
                <?php foreach ($site['phones'] as $phone): ?>
                    <p class="mb-1"><?= h($phone) ?></p>
                <?php endforeach; ?>
                <p class="mt-3 mb-2"><strong>Location:</strong> Lakhnadon, Madhya Pradesh</p>
                <div class="ratio ratio-4x3 rounded-3 overflow-hidden border border-warning-subtle">
                    <iframe src="<?= h($site['location_map_embed']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Lakhnadon Map"></iframe>
                </div>
            </div>
        </div>

        <div class="col-lg-6 fade-up">
            <div class="section-card rounded-4 p-4 h-100">
                <h2 class="h5 font-heading mb-3"><?= h(t('contact_us')) ?></h2>
                <?php if ($status !== ''): ?>
                    <div class="alert alert-info"><?= h($status) ?></div>
                <?php endif; ?>
                <form method="post" action="contact.php?lang=<?= h($lang) ?>">
                    <?= csrf_input('contact_form') ?>
                    <div class="mb-3">
                        <label class="form-label" for="name"><?= h(t('name')) ?> *</label>
                        <input class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email"><?= h(t('email')) ?></label>
                        <input class="form-control" id="email" name="email" type="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="message"><?= h(t('message')) ?> *</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                    <button class="btn btn-warning" type="submit"><?= h(t('submit')) ?></button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
