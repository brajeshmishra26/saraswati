<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = localized_page_title(t('aarti'));

$pdfPath = file_exists(__DIR__ . '/uploads/docs/saraswati-aarti.pdf') ? 'uploads/docs/saraswati-aarti.pdf' : '';
$audioPath = file_exists(__DIR__ . '/uploads/audio/saraswati-aarti.mp3') ? 'uploads/audio/saraswati-aarti.mp3' : '';

require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <div class="row g-4">
        <div class="col-lg-7 fade-up">
            <h1 class="font-heading mb-3"><?= h(t('aarti')) ?></h1>
            <h2 class="h4 mb-3 font-devanagari">माँ सरस्वती आरती</h2>
            <p class="font-devanagari mb-3">
                जय सरस्वती माता, मैया जय सरस्वती माता।<br>
                सदगुण वैभव शालिनी, त्रिभुवन विख्याता।।
            </p>
            <h3 class="h5 font-heading mt-4">Saraswati Vandana</h3>
            <p class="font-devanagari mb-3">या कुन्देन्दुतुषारहारधवला या शुभ्रवस्त्रावृता। या वीणावरदण्डमण्डितकरा या श्वेतपद्मासना।।</p>
        </div>
        <div class="col-lg-5 fade-up">
            <div class="section-card rounded-4 p-4">
                <h3 class="h5 mb-3"><?= h(t('play_audio')) ?></h3>
                <?php if ($audioPath !== ''): ?>
                    <audio controls preload="none" class="w-100 mb-3">
                        <source src="<?= h($audioPath) ?>" type="audio/mpeg">
                    </audio>
                <?php else: ?>
                    <p class="small text-muted">Upload audio file as uploads/audio/saraswati-aarti.mp3</p>
                <?php endif; ?>

                <?php if ($pdfPath !== ''): ?>
                    <a class="btn btn-warning" href="<?= h($pdfPath) ?>" download><?= h(t('download_pdf')) ?></a>
                <?php else: ?>
                    <button class="btn btn-outline-secondary" type="button" disabled><?= h(t('download_pdf')) ?> (Upload PDF in uploads/docs)</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
