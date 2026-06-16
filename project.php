<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = localized_page_title(t('project'));
$galleryItems = load_gallery_items($defaultGallery);
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <div class="row g-4 align-items-start">
        <div class="col-lg-6 fade-up">
            <h1 class="font-heading mb-3">Lakhnadon Temple Project</h1>
            <p>The Maa Saraswati temple construction initiative in Lakhnadon is the flagship project of the trust. This temple is envisioned as a spiritual and cultural center for students, families, and devotees.</p>
            <ul class="list-group">
                <li class="list-group-item">Bhoomi Pujan was completed on 19-20 April 2026.</li>
                <li class="list-group-item">Project combines temple architecture with community prayer spaces.</li>
                <li class="list-group-item">Volunteers and devotees are actively supporting every phase.</li>
            </ul>
        </div>
        <div class="col-lg-6 fade-up">
                <img src="assets/images/mandir.jpeg" loading="lazy" class="w-100 rounded-4 border border-warning-subtle" alt="Lakhnadon Temple Project">
        </div>
    </div>

    <h2 class="h4 font-heading mt-5 mb-3 fade-up">Bhoomi Pujan Gallery</h2>
    <div class="row g-3">
        <?php foreach (array_slice($galleryItems, 0, 8) as $item): ?>
            <div class="col-6 col-md-4 col-lg-3 fade-up">
                <img src="<?= h($item['file']) ?>" loading="lazy" class="w-100 rounded-3 border border-warning-subtle" alt="<?= h($item['title']) ?>">
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
