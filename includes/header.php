<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (!isset($pageTitle)) {
    $pageTitle = localized_page_title('Maa Saraswati Sansthan');
}

$nav = [
    'index.php' => t('home'),
    'about.php' => t('about'),
    'aarti.php' => t('aarti'),
    'significance.php' => t('significance'),
    'festivals.php' => t('festivals'),
    'project.php' => t('project'),
    'gallery.php' => t('gallery'),
    'videos.php' => t('videos'),
    'donate.php' => t('donate'),
    'contact.php' => t('contact'),
];

$currentPage = basename($_SERVER['PHP_SELF']);
$authUser = auth_user();
?>
<!doctype html>
<html lang="<?= h($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?></title>
    <meta name="description" content="Maa Saraswati Sansthan - temple construction, spiritual guidance, and donation support.">
    <meta name="keywords" content="Maa Saraswati temple, Saraswati Aarti, Basant Panchami, Saraswati Puja India">
    <meta property="og:title" content="<?= h($site['name']) ?>">
    <meta property="og:description" content="Spiritual and social trust website for Maa Saraswati temple initiatives.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?= h($site['domain']) ?>">
    <meta name="theme-color" content="#FF9933">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Noto+Sans+Devanagari:wght@400;600;700&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/tailwind-built.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ReligiousOrganization",
      "name": "Maa Saraswati Sansthan",
      "url": "https://maa-saraswati.co.in",
      "description": "Religious and social trust for Maa Saraswati temple development and spiritual activities.",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Lakhnadon",
        "addressCountry": "IN"
      },
      "sameAs": [
                "https://wa.me/<?= h(whatsapp_phone_number((string) $site['whatsapp_number'])) ?>"
      ]
    }
    </script>
</head>
<body class="font-body">
    <header class="site-header sticky-top z-3">
        <nav class="navbar navbar-expand-md site-nav border-bottom shadow-sm">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-3" href="index.php?lang=<?= h($lang) ?>">
                    <img src="assets/images/logo.jpeg" loading="lazy" alt="Maa Saraswati Sansthan Logo" class="brand-logo">
                    <span class="brand-text">
                        <span class="brand-name">Maa Saraswati Sansthan</span>
                        <span class="brand-tagline">माँ सरस्वती संस्थान, लखनादौन</span>
                    </span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-lg-1">
                        <?php foreach ($nav as $file => $label): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === $file ? 'active' : '' ?>" href="<?= h($file) ?>?lang=<?= h($lang) ?>"><?= h($label) ?></a>
                            </li>
                        <?php endforeach; ?>

                        <?php if ($authUser === null): ?>
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'signup.php' ? 'active' : '' ?>" href="signup.php?lang=<?= h($lang) ?>">Signup</a></li>
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>" href="login.php?lang=<?= h($lang) ?>">Login</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="profile.php?lang=<?= h($lang) ?>">Profile</a></li>
                            <?php if (($authUser['role'] ?? '') === 'admin'): ?>
                                <li class="nav-item"><a class="nav-link <?= $currentPage === 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) === 'admin' ? 'active' : '' ?>" href="admin/index.php">Admin</a></li>
                            <?php endif; ?>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                        <?php endif; ?>
                    </ul>
                    <div class="ms-lg-3 d-flex align-items-center gap-2">
                        <a href="<?= h($currentPage) ?>?lang=hi" class="btn btn-sm <?= $lang === 'hi' ? 'btn-warning' : 'btn-outline-warning' ?>">हिंदी</a>
                        <a href="<?= h($currentPage) ?>?lang=en" class="btn btn-sm <?= $lang === 'en' ? 'btn-warning' : 'btn-outline-warning' ?>">EN</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="festival-reminder"><?= h(t('festival_reminder')) ?></div>

    <main>
