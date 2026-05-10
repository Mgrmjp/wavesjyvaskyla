<?php
$s = settings();
$data = DataStore::ensure('menu', ['categories' => defaultMenuCategories(), 'items' => defaultMenuItems()]);
$categories = $data['categories'] ?? [];
$items = $data['items'] ?? [];

$grouped = [];
foreach ($items as $item) {
    if (!($item['visible'] ?? false)) continue;
    $cat = $item['category'] ?? 'other';
    if (!isset($grouped[$cat])) $grouped[$cat] = [];
    $grouped[$cat][] = $item;
}

usort($categories, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

$categoryMeta = [
    'summer-tacos' => [
        'eyebrow' => t('Limeä, korianteria, rapeutta', 'Lime, coriander, crisp texture'),
        'intro' => t('Kaksi kevätsipulilettua, raikkaat kasvikset ja sataman iltaan sopiva täyte.', 'Two spring onion pancakes, crisp greens and a filling made for harbour evenings.'),
    ],
    'burgers-with-fries' => [
        'eyebrow' => t('Smash-burgerit ja ranut', 'Smash burgers and fries'),
        'intro' => t('Rapea 80 gramman pihvi, Jukolan cheddar ja talon majoneesit. Kaikki burgerit tarjoillaan ranskalaisten kanssa.', 'Crisp 80 g patty, Jukola cheddar and house mayos. Every burger is served with fries.'),
    ],
    'salads' => [
        'eyebrow' => t('Raikas väliin', 'A fresher pause'),
        'intro' => t('Rento satamasalaatti, jossa on hapokkuutta, savua ja kermaista viimeistelyä.', 'A relaxed harbour salad with acidity, smoke and a creamy finish.'),
    ],
    'snacks' => [
        'eyebrow' => t('Jaettavaa laiturille', 'To share by the water'),
        'intro' => t('Rapeita ranskalaisia, nachoja ja dippikulhoja ennen seuraavaa kierrosta.', 'Crisp fries, nachos and dipping bowls before the next round.'),
    ],
    'kids' => [
        'eyebrow' => t('Pienemmille ruokailijoille', 'For smaller guests'),
        'intro' => t('Tutut suosikit pienemmässä koossa, ilman turhaa säätöä.', 'Familiar favourites in smaller portions, kept simple.'),
    ],
    'dips' => [
        'eyebrow' => t('Talon dippivalinnat', 'House dip selection'),
        'intro' => t('Valitse annokseen lisää limeä, ranchia, aiolia tai Louisianan potkua.', 'Add lime, ranch, aioli or a Louisiana kick to the plate.'),
    ],
];

$inlineIncludeSections = [
    'summer-tacos' => true,
    'burgers-with-fries' => true,
];

$visibleCategories = array_values(array_filter($categories, function ($cat) use ($grouped) {
    $slug = $cat['slug'] ?? '';
    return $slug !== '' && !empty($grouped[$slug]);
}));

$formatPrice = function ($price): string {
    if ($price === '' || $price === null || (float)$price <= 0) {
        return '';
    }

    $value = (float)$price;
    $decimals = abs($value - round($value)) < 0.001 ? 0 : 2;
    return number_format($value, $decimals, ',', '') . ' €';
};

include INCLUDES_DIR . '/header.php';
?>

<div class="menu-page">
    <!-- ── HERO ─────────────────────────────────── -->
    <section class="menu-hero" aria-labelledby="menu-title">
        <div class="menu-hero-inner">
            <p class="menu-kicker">
                <span class="open-dot <?= $isOpen ? 'on' : 'off' ?>"></span>
                Konttiravintola Waves · Satamakatu 2 B
            </p>
            <h1 id="menu-title" class="menu-hero-title">
                <?= t('Ruokalista', 'Menu') ?>
            </h1>
            <p class="menu-hero-copy">
                <?= t('Rapeat tacot, smash-burgerit ja satamaillat Jyväskylässä.', 'Crisp tacos, smash burgers and harbour evenings in Jyväskylä.') ?>
            </p>
            <div class="menu-hero-meta" aria-label="<?= t('Ravintolan tiedot', 'Restaurant details') ?>">
                <span><?= t('Jyväskylän satama', 'Jyväskylä harbour') ?></span>
                <span><?= t('Ei pöytävarauksia', 'No reservations') ?></span>
                <span>62.2386° N, 25.7531° E</span>
            </div>
        </div>
    </section>

    <!-- ── STICKY SECTION NAV ───────────────────── -->
    <nav class="menu-jump-nav" aria-label="<?= t('Ruokalistan osiot', 'Menu sections') ?>">
        <div class="menu-jump-nav-inner">
            <?php foreach ($visibleCategories as $cat):
                $slug = $cat['slug'] ?? '';
                $title = $cat['title_' . lang()] ?? $cat['title_fi'] ?? '';
            ?>
            <a class="menu-jump-link" href="#menu-<?= esc($slug) ?>"><?= esc($title) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>

    <!-- ── MENU SECTIONS ────────────────────────── -->
    <section class="menu-content" aria-label="<?= t('Ruokalista', 'Menu') ?>">
    <?php foreach ($visibleCategories as $index => $cat):
        $slug = $cat['slug'] ?? '';
        $meta = $categoryMeta[$slug] ?? ['eyebrow' => t('Waves', 'Waves'), 'intro' => ''];
        $title = $cat['title_' . lang()] ?? $cat['title_fi'] ?? '';
        $sectionIncludeCopy = '';

        if (!empty($inlineIncludeSections[$slug])) {
            foreach ($grouped[$slug] as $candidate) {
                $candidatePrice = $formatPrice($candidate['price'] ?? null);
                $candidateDescription = $candidate['description_' . lang()] ?? $candidate['description_fi'] ?? '';
                if ($candidatePrice === '' && $candidateDescription !== '') {
                    $sectionIncludeCopy = $candidateDescription;
                    break;
                }
            }
        }
    ?>
    <section id="menu-<?= esc($slug) ?>" class="menu-section">
        <div class="menu-section-head">
            <div class="menu-section-head-text">
                <p class="menu-section-kicker"><?= esc($meta['eyebrow']) ?></p>
                <h2 class="menu-section-title"><?= esc($title) ?></h2>
            </div>
            <?php if (!empty($meta['intro'])): ?>
            <p class="menu-section-copy"><?= esc($meta['intro']) ?></p>
            <?php endif; ?>
        </div>

        <div class="menu-card-grid" role="list">
            <?php foreach ($grouped[$slug] as $item): ?>
            <?php
                $name = $item['name_' . lang()] ?? $item['name_fi'] ?? '';
                $description = $item['description_' . lang()] ?? $item['description_fi'] ?? '';
                $price = $formatPrice($item['price'] ?? null);
                $tags = array_values(array_filter(array_map('trim', explode(',', $item['dietary_tags'] ?? ''))));
                $isNote = $price === '' && $description !== '';
                $featuredLabel = '';
                $normalizedName = strtoupper($name);

                if ($slug === 'summer-tacos' && str_contains($normalizedName, 'KUHATAKUU')) {
                    $featuredLabel = t('Sataman suosikki', 'Harbour favourite');
                } elseif ($slug === 'burgers-with-fries' && str_contains($normalizedName, 'HANGOVER')) {
                    $featuredLabel = t('Eniten kysytty', 'Most popular');
                }
            ?>
            <?php if ($isNote && $sectionIncludeCopy !== '') continue; ?>
            <?php if ($isNote): ?>
            <article class="menu-note" role="listitem">
                <p class="menu-note-title"><?= esc($name) ?></p>
                <p><?= esc($description) ?></p>
            </article>
            <?php else: ?>
            <article class="menu-card <?= $featuredLabel !== '' ? 'menu-card--featured' : '' ?>" role="listitem">
                <div class="menu-card-main">
                    <?php if ($featuredLabel !== ''): ?>
                    <p class="menu-card-badge">
                        <svg width="8" height="8" viewBox="0 0 8 8" fill="none" aria-hidden="true">
                            <circle cx="4" cy="4" r="3" fill="currentColor"/>
                        </svg>
                        <?= esc($featuredLabel) ?>
                    </p>
                    <?php endif; ?>
                    <div class="menu-card-title-row">
                        <h3 class="menu-card-title"><?= esc($name) ?></h3>
                        <?php if ($price !== ''): ?>
                        <p class="menu-card-price" aria-label="<?= t('Hinta', 'Price') ?>"><?= esc($price) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($description !== ''): ?>
                    <p class="menu-card-desc"><?= esc($description) ?></p>
                    <?php endif; ?>
                    <?php if ($sectionIncludeCopy !== ''): ?>
                    <p class="menu-card-includes">
                        <span><?= t('Sisältää', 'Includes') ?></span>
                        <?= esc($sectionIncludeCopy) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($item['image'])): ?>
                <div class="menu-item-img" aria-hidden="true">
                    <img src="/uploads/<?= esc($item['image']) ?>" alt="" loading="lazy">
                </div>
                <?php endif; ?>

                <?php if ($tags): ?>
                <footer class="menu-card-tags" aria-label="<?= t('Erityisruokavaliot', 'Dietary labels') ?>">
                    <?php foreach ($tags as $tag): ?>
                    <span class="dietary-tag" data-label="<?= esc(dietaryLabel($tag)) ?>"><?= esc($tag) ?></span>
                    <?php endforeach; ?>
                </footer>
                <?php endif; ?>
            </article>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php if ($index < count($visibleCategories) - 1): ?>
        <div class="menu-wave-divider" aria-hidden="true">
            <svg viewBox="0 0 960 36" focusable="false" preserveAspectRatio="none">
                <path d="M0 18 C240 2 400 34 480 18 S640 2 720 18 S880 34 960 18" />
            </svg>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
    </section>

    <!-- ── DIETARY LEGEND ───────────────────────── -->
    <div class="menu-legend">
        <p class="label"><?= t('Merkinnät', 'Labels') ?></p>
        <p>
            <strong>G</strong> <?= t('Gluteeniton', 'Gluten-free') ?>
            <strong>L</strong> <?= t('Laktoositon', 'Lactose-free') ?>
            <strong>VL</strong> <?= t('Vähälaktoottinen', 'Low-lactose') ?>
            <strong>V</strong> <?= t('Vegaaninen', 'Vegan') ?>
            <strong>M</strong> <?= t('Maidoton', 'Dairy-free') ?>
        </p>
    </div>

    <!-- ── FOOTER DESTINATION ───────────────────── -->
    <section class="menu-destination" aria-label="<?= t('Sijainti', 'Location') ?>">
        <div>
            <p class="menu-section-kicker"><?= t('Sataman laidalla', 'By the harbour') ?></p>
            <h2><?= t('Löydät meidät veden ääreltä', 'Find us by the water') ?></h2>
        </div>
        <p><?= t('Satamakatu 2 B, Jyväskylä. Kävele sisään, tilaa tiskiltä ja istu sataman iltaan. Emme ota pöytävarauksia.', 'Satamakatu 2 B, Jyväskylä. Walk in, order at the counter and sit down by the harbour. We do not take table reservations.') ?></p>
    </section>
</div>

<?php include INCLUDES_DIR . '/footer.php'; ?>
