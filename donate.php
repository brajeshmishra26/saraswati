<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = localized_page_title(t('donate'));
$qrPath = uploaded_qr_path();
$upiLink = donation_upi_link();
$upiIntentLink = donation_upi_intent_link($upiLink);
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <h1 class="font-heading mb-4 fade-up"><?= h(t('donate')) ?></h1>

    <div class="row g-4">
        <div class="col-lg-7 fade-up">
            <div class="section-card rounded-4 p-4 h-100">
                <h2 class="h5 font-heading mb-3">Bank Details</h2>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <tr>
                            <th>Account Name</th>
                            <td><?= h($site['bank']['account_name']) ?></td>
                            <td><button class="btn btn-sm btn-outline-warning copy-btn" data-copy="<?= h($site['bank']['account_name']) ?>">Copy</button></td>
                        </tr>
                        <tr>
                            <th>Bank</th>
                            <td><?= h($site['bank']['bank']) ?></td>
                            <td><button class="btn btn-sm btn-outline-warning copy-btn" data-copy="<?= h($site['bank']['bank']) ?>">Copy</button></td>
                        </tr>
                        <tr>
                            <th>Branch</th>
                            <td><?= h($site['bank']['branch']) ?></td>
                            <td><button class="btn btn-sm btn-outline-warning copy-btn" data-copy="<?= h($site['bank']['branch']) ?>">Copy</button></td>
                        </tr>
                        <tr>
                            <th>Account Number</th>
                            <td><?= h($site['bank']['account_number']) ?></td>
                            <td><button class="btn btn-sm btn-outline-warning copy-btn" data-copy="<?= h($site['bank']['account_number']) ?>">Copy</button></td>
                        </tr>
                        <tr>
                            <th>IFSC</th>
                            <td><?= h($site['bank']['ifsc']) ?></td>
                            <td><button class="btn btn-sm btn-outline-warning copy-btn" data-copy="<?= h($site['bank']['ifsc']) ?>">Copy</button></td>
                        </tr>
                    </table>
                </div>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <a class="btn btn-warning" href="<?= h($upiLink) ?>" data-upi-pay="1">UPI Click-to-Pay</a>
                    <a class="btn btn-outline-warning" href="<?= h($upiIntentLink) ?>">Android Intent</a>
                    <button type="button" class="btn btn-outline-warning" data-copy="<?= h($upiLink) ?>">Copy UPI Link</button>
                </div>
                <div class="small text-muted mt-3">
                    Use UPI Click-to-Pay on mobile with any UPI app. If it does not open, use Android Intent or scan the QR.
                </div>
            </div>
        </div>

        <div class="col-lg-5 fade-up">
            <div class="section-card rounded-4 p-4 text-center h-100">
                <h2 class="h5 font-heading mb-3"><?= h(t('scan_donate')) ?></h2>
                <img src="<?= h($qrPath) ?>" loading="lazy" class="img-fluid rounded-3 border border-warning-subtle" alt="Donation QR code">
                <p class="small text-muted mt-3 mb-0">For best result, upload real QR image to uploads/qr.</p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
