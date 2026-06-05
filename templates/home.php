<?php
$s = settings();
$notices = notices();
include INCLUDES_DIR . '/header.php';
?>

<section class="home-hero-wrap" style="--home-hero-image: url('<?= esc(publicAsset('/assets/files/frontpage-hero-upscaled.avif')) ?>');">
    <div class="home-hero-bg"></div>
    <div class="max-w-5xl mx-auto px-5 home-hero-content">
        <div class="home-hero-inner">
            <div class="home-hero-main">
                <div class="home-hero-heading">
                    <h1 class="display text-accent home-display">Waves</h1>
                </div>
                <p class="lead home-hero-subtitle">
                    <?= esc($s['hero_text_' . lang()] ?? '') ?>
                </p>
                <p class="text-sm home-meta">
                    <span><?= t('Satamakatu 2 B, Jyväskylä', 'Satamakatu 2 B, Jyväskylä') ?></span>
                    <span><?= t('Sataman terassi', 'Harbor terrace') ?></span>
                    <span><?= t('Tacot & burgerit', 'Tacos & burgers') ?></span>
                    <span><?= t('Ei pöytävarauksia', 'No reservations') ?></span>
                </p>
                <div class="home-cta-row">
                    <a href="<?= url('menu') ?>" class="btn btn-primary"><?= t('Ruokalista', 'Menu') ?><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 6h10M6 1l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                    <a href="<?= url('yhteystiedot') ?>" class="btn btn-secondary"><?= t('Yhteystiedot', 'Contact') ?></a>
                </div>
                <div class="rule-accent home-divider"></div>
                <div class="home-about-block">
                    <p class="label"><?= t('Tietoa', 'About') ?></p>
                    <div class="prose home-about-text">
                        <?= safeIntroHtml((string) ($s['intro_' . lang()] ?? '')) ?>
                    </div>
                </div>
            </div>
            <aside class="hours-card home-opening-card">
                <p class="label"><?= t('Aukioloajat', 'Opening Hours') ?></p>
                <?php include INCLUDES_DIR . '/opening-hours.php'; ?>
            </aside>
        </div>
    </div>
<div class="wave-divider" aria-hidden="true">
    <svg class="wave wave--back" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,76 C90,48 210,56 360,76 C510,96 630,104 720,76 C810,48 930,56 1080,76 C1230,96 1350,104 1440,76 L1440,120 L0,120 Z"></path>
    </svg>

    <svg class="wave wave--mid" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,58 C100,36 220,40 360,58 C500,76 620,80 720,58 C820,36 940,40 1080,58 C1220,76 1340,80 1440,58 L1440,120 L0,120 Z"></path>
    </svg>

    <svg class="wave wave--front" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,68 C80,92 200,96 360,68 C520,40 640,44 720,68 C800,92 920,96 1080,68 C1240,40 1360,44 1440,68 L1440,120 L0,120 Z"></path>
    </svg>
</div>
</section>

<section class="home-highlights">
    <div class="max-w-5xl mx-auto px-5">
        <p class="label home-highlights__label"><?= t('Kesä Wavesilla', 'Summer at Waves') ?></p>
        <div class="highlight-grid">
            <article class="highlight-card">
                <h2><?= t('Kesän maut', 'Summer flavors') ?></h2>
                <p><?= t('Rapeat tacot, smash-burgerit ja raikkaat juomat sataman äärellä.', 'Crisp tacos, smash burgers, and cold drinks by the harbor.') ?></p>
            </article>
            <article class="highlight-card">
                <h2><?= t('Sataman terassi', 'Harbor terrace') ?></h2>
                <p><?= t('Rento paikka aurinkoon, iltaan ja sataman tunnelmaan.', 'An easy place for sun, evenings, and the harbor atmosphere.') ?></p>
            </article>
            <article class="highlight-card">
                <h2><?= t('Ei pöytävarauksia', 'No reservations') ?></h2>
                <p><?= t('Tule paikalle, nappaa annos ja jää hetkeksi.', 'Walk in, grab something good, and stay a while.') ?></p>
            </article>
        </div>
    </div>
</section>

<?php include INCLUDES_DIR . '/footer.php'; ?>
