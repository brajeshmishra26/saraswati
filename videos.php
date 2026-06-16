<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = localized_page_title(t('videos'));
$videos = load_videos($defaultVideos);
$lowBandwidth = ($_GET['lite'] ?? '1') === '1';

$requiredVideos = [
    [
        'title' => 'Maa Saraswati Post Update',
        'youtube_id' => '',
        'youtube_url' => 'https://www.youtube.com/watch?v=Knw4iHDy2sY',
        'description' => 'Latest official update from Maa Saraswati Sansthan YouTube post.',
    ],
    [
        'title' => 'Maa Saraswati Video',
        'youtube_id' => 'LOnDkWEv4tQ',
        'youtube_url' => 'https://youtu.be/LOnDkWEv4tQ',
        'description' => 'Featured video from Maa Saraswati Sansthan YouTube channel.',
    ],
];

$videos = array_values($videos);
if (count($videos) >= 2) {
    $videos[0] = array_merge($videos[0], $requiredVideos[0]);
    $videos[1] = array_merge($videos[1], $requiredVideos[1]);
} else {
    $videos = $requiredVideos;
}

$resolveYoutubeId = static function (array $video): ?string {
    $youtubeId = trim((string) ($video['youtube_id'] ?? ''));
    if ($youtubeId !== '') {
        return $youtubeId;
    }

    $youtubeUrl = trim((string) ($video['youtube_url'] ?? ''));
    if ($youtubeUrl === '') {
        return null;
    }

    if (preg_match('~youtu\.be/([A-Za-z0-9_-]{11})~', $youtubeUrl, $matches) === 1) {
        return $matches[1];
    }

    if (preg_match('~[?&]v=([A-Za-z0-9_-]{11})~', $youtubeUrl, $matches) === 1) {
        return $matches[1];
    }

    if (preg_match('~/(?:shorts|embed)/([A-Za-z0-9_-]{11})~', $youtubeUrl, $matches) === 1) {
        return $matches[1];
    }

    return null;
};
require __DIR__ . '/includes/header.php';
?>

<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 fade-up">
        <h1 class="font-heading mb-0"><?= h(t('videos')) ?></h1>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <a href="https://www.youtube.com/@maasaraswatisansthan" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-warning">
                Visit YouTube Channel
            </a>
            <a href="videos.php?lang=<?= h($lang) ?>&lite=<?= $lowBandwidth ? '0' : '1' ?>" class="btn btn-sm btn-outline-warning">
                <?= $lowBandwidth ? 'Disable Low Bandwidth Mode' : 'Enable Low Bandwidth Mode' ?>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($videos as $video): ?>
            <?php
            $youtubeId = $resolveYoutubeId($video);
            $youtubeUrl = trim((string) ($video['youtube_url'] ?? ''));
            if ($youtubeUrl === '' && $youtubeId !== null) {
                $youtubeUrl = 'https://www.youtube.com/watch?v=' . $youtubeId;
            }
            ?>
            <div class="col-lg-6 fade-up">
                <div class="section-card rounded-4 p-3 h-100">
                    <h2 class="h5 font-heading"><?= h($video['title']) ?></h2>
                    <p class="small"><?= h($video['description']) ?></p>

                    <?php if ($lowBandwidth && $youtubeId !== null): ?>
                        <div class="ratio ratio-16x9 bg-light rounded-3 align-items-center justify-content-center d-flex" data-video-id="<?= h($youtubeId) ?>" role="button" aria-label="Play video <?= h($video['title']) ?>">
                            <img class="video-thumb" loading="lazy" src="https://img.youtube.com/vi/<?= h($youtubeId) ?>/hqdefault.jpg" alt="<?= h($video['title']) ?> thumbnail">
                        </div>
                        <p class="small text-muted mt-2 mb-0">Thumbnail mode active. Tap image to load video.</p>
                    <?php elseif ($lowBandwidth && $youtubeUrl !== ''): ?>
                        <div class="ratio ratio-16x9 bg-light rounded-3 align-items-center justify-content-center d-flex">
                            <a href="<?= h($youtubeUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark">Open on YouTube</a>
                        </div>
                    <?php else: ?>
                        <?php if ($youtubeId !== null): ?>
                            <div class="ratio ratio-16x9">
                                <iframe loading="lazy" src="https://www.youtube.com/embed/<?= h($youtubeId) ?>?rel=0" title="YouTube video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        <?php elseif ($youtubeUrl !== ''): ?>
                            <div class="ratio ratio-16x9 bg-light rounded-3 align-items-center justify-content-center d-flex">
                                <a href="<?= h($youtubeUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark">Open on YouTube</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
