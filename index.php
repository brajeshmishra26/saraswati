<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$events = load_events($defaultEvents);
$galleryItems = array_slice(load_gallery_items($defaultGallery), 0, 3);
$pageTitle = localized_page_title(t('home'));

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-overlay" aria-hidden="true"></div>
    <div class="temple-silhouette" aria-hidden="true"></div>
    <div class="container py-5">
        <div id="welcomeOverlay" class="col-lg-8 hero-card rounded-4 p-4 p-md-5 fade-up" data-persist-key="welcomeOverlayClosed">
            <button type="button" class="overlay-close" data-dismiss-target="#welcomeOverlay" onclick="document.getElementById('welcomeOverlay').style.display='none'; try { localStorage.setItem('welcomeOverlayClosed','1'); } catch(e) {}" aria-label="Close welcome banner">×</button>
            <p class="text-uppercase small tracking-widest mb-3">maa-saraswati.co.in</p>
            <h1 class="display-5 fw-bold font-heading mb-3"><?= h(t('hero_heading')) ?></h1>
            <p class="lead font-devanagari mb-3"><?= h($site['tagline_hi']) ?></p>
            <p class="mb-4"><?= h(t('hero_sub')) ?></p>
            <div class="d-flex flex-wrap gap-2">
                <a href="donate.php?lang=<?= h($lang) ?>" class="btn btn-warning btn-lg"><?= h(t('donate_now')) ?></a>
                <a href="about.php?lang=<?= h($lang) ?>" class="btn btn-outline-light btn-lg"><?= h(t('read_more')) ?></a>
            </div>
        </div>
    </div>
</section>

<div class="section-divider" aria-hidden="true"></div>

<section class="container py-5">
    <div class="row g-4">
        <div class="col-md-6 col-lg-3 fade-up">
            <div class="section-card rounded-4 p-4 h-100">
                <h3 class="h5 font-heading mb-2"><?= h(t('about')) ?></h3>
                <p class="mb-3"><?= h(t('about_short')) ?></p>
                <a href="about.php?lang=<?= h($lang) ?>" class="btn btn-sm btn-outline-warning"><?= h(t('read_more')) ?></a>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 fade-up">
            <div class="section-card rounded-4 p-4 h-100 border-2 border-warning">
                <h3 class="h5 font-heading mb-2"><?= h(t('donate_now')) ?></h3>
                <p class="mb-3">Support temple construction and social seva initiatives.</p>
                <a href="donate.php?lang=<?= h($lang) ?>" class="btn btn-warning btn-sm">Donate</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 fade-up">
            <div class="section-card rounded-4 p-4 h-100">
                <h3 class="h5 font-heading mb-2"><?= h(t('upcoming_events')) ?></h3>
                <?php if (!empty($events)): ?>
                    <p class="mb-1 fw-semibold"><?= h($events[0]['title']) ?></p>
                    <p class="small mb-0 text-muted"><?= h($events[0]['date']) ?> | <?= h($events[0]['location']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 fade-up">
            <div class="section-card rounded-4 p-4 h-100">
                <h3 class="h5 font-heading mb-2"><?= h(t('gallery')) ?></h3>
                <p class="mb-3">Bhoomi Pujan, rituals, plantation and social events.</p>
                <a href="gallery.php?lang=<?= h($lang) ?>" class="btn btn-sm btn-outline-warning">View</a>
            </div>
        </div>
    </div>
</section>

<div class="section-divider" aria-hidden="true"></div>

<section class="container pb-5">
    <div class="row g-4">
        <?php foreach ($galleryItems as $item): ?>
            <div class="col-md-4 fade-up">
                <img src="<?= h($item['file']) ?>" loading="lazy" alt="<?= h($item['title']) ?>" class="w-100 rounded-4 border border-warning-subtle">
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
