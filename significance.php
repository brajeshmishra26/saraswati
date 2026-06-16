<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = localized_page_title(t('significance'));
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <h1 class="font-heading mb-4 fade-up"><?= h(t('significance')) ?></h1>
    <div class="row g-4">
        <div class="col-md-4 fade-up">
            <div class="section-card rounded-4 p-4 h-100">
                <h2 class="h5 font-heading">Who is Maa Saraswati?</h2>
                <p>Maa Saraswati is revered as the goddess of wisdom, music, arts, and learning. She inspires clarity of thought and righteous speech.</p>
            </div>
        </div>
        <div class="col-md-4 fade-up">
            <div class="section-card rounded-4 p-4 h-100">
                <h2 class="h5 font-heading">Symbolism</h2>
                <p><strong>Veena:</strong> Discipline and harmony in knowledge.</p>
                <p class="mb-0"><strong>Swan:</strong> Wisdom to distinguish truth from illusion.</p>
            </div>
        </div>
        <div class="col-md-4 fade-up">
            <div class="section-card rounded-4 p-4 h-100">
                <h2 class="h5 font-heading">Importance in Student Life</h2>
                <p class="mb-0">Students seek Maa Saraswati's blessings for concentration, confidence, and excellence in studies and character.</p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
