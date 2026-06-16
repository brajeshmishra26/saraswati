    </main>

    <a href="donate.php?lang=<?= h($lang) ?>" class="sticky-donate"><?= h(t('donate_now')) ?></a>
    <a href="https://wa.me/<?= h(whatsapp_phone_number((string) $site['whatsapp_number'])) ?>?text=<?= urlencode('Jai Maa Saraswati. I want to support the temple project.') ?>" class="floating-whatsapp" target="_blank" rel="noopener" aria-label="WhatsApp">
        <img src="assets/images/whatsapplogo.png" loading="lazy" alt="WhatsApp">
    </a>

    <footer class="site-footer py-5 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="font-heading mb-3"><?= h($site['name']) ?></h5>
                    <p class="mb-2 font-devanagari"><?= h($site['tagline_hi']) ?></p>
                    <p class="small text-muted mb-0">Temple and social service initiative based in Lakhnadon.</p>
                </div>
                <div class="col-md-3">
                    <h6 class="mb-3">Quick Links</h6>
                    <ul class="list-unstyled small">
                        <li><a href="about.php?lang=<?= h($lang) ?>"><?= h(t('about')) ?></a></li>
                        <li><a href="project.php?lang=<?= h($lang) ?>"><?= h(t('project')) ?></a></li>
                        <li><a href="gallery.php?lang=<?= h($lang) ?>"><?= h(t('gallery')) ?></a></li>
                        <li><a href="donate.php?lang=<?= h($lang) ?>"><?= h(t('donate')) ?></a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="mb-3">Contact</h6>
                    <?php foreach ($site['phones'] as $phone): ?>
                        <div class="small"><?= h($phone) ?></div>
                    <?php endforeach; ?>
                    <div class="small mt-2">Lakhnadon, Madhya Pradesh</div>
                </div>
            </div>
            <hr>
            <div class="small text-center">© <?= date('Y') ?> Maa Saraswati Sansthan. All rights reserved.</div>
            <div class="small text-center mt-2">
                <a
                    href="index.php?lang=<?= h($lang) ?>"
                    onclick="try { localStorage.removeItem('welcomeOverlayClosed'); } catch(e) {}"
                ><?= h(t('show_welcome_again')) ?></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
