<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = localized_page_title(t('gallery'));
$category = $_GET['category'] ?? 'All';
$items = load_gallery_items($defaultGallery);
$categories = ['All', 'Construction', 'Rituals', 'Events'];
$filtered = array_values(array_filter($items, function ($item) use ($category) {
    return $category === 'All' || $item['category'] === $category;
}));
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <h1 class="font-heading mb-4 fade-up"><?= h(t('gallery')) ?></h1>
    <div class="d-flex flex-wrap gap-2 mb-4 fade-up">
        <?php foreach ($categories as $c): ?>
            <a class="btn btn-sm <?= $category === $c ? 'btn-warning' : 'btn-outline-warning' ?>" href="gallery.php?lang=<?= h($lang) ?>&category=<?= urlencode($c) ?>"><?= h($c) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <?php foreach ($filtered as $item): ?>
            <div class="col-6 col-md-4 fade-up">
                <figure class="mb-0">
                    <img src="<?= h($item['file']) ?>" loading="lazy" class="w-100 rounded-4 border border-warning-subtle" alt="<?= h($item['title']) ?>">
                    <figcaption class="small mt-2"><?= h($item['title']) ?></figcaption>
                </figure>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
