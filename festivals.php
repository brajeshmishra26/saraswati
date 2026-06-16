<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = localized_page_title(t('festivals'));
$festivalDates = current_year_festival_dates();
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 fade-up">
        <h1 class="font-heading mb-0"><?= h(t('festivals')) ?></h1>
        <span class="badge text-bg-warning">Dynamic Year: <?= date('Y') ?></span>
    </div>

    <div class="row g-4">
        <?php foreach ($festivalDates as $festival): ?>
            <div class="col-md-4 fade-up">
                <div class="section-card rounded-4 p-4 h-100">
                    <h2 class="h5 font-heading"><?= h($festival['name']) ?></h2>
                    <p class="fs-5 fw-semibold text-amber-700 mb-1"><?= h($festival['date']) ?></p>
                    <p class="mb-0"><?= h($festival['note']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-light border border-warning-subtle mt-4 fade-up">
        Festival dates auto-refresh yearly from server year. Admin can update exact lunar-calendar dates in the panel.
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
