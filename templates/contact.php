<?php
$s = settings();
include INCLUDES_DIR . '/header.php';
?>

<section class="max-w-5xl mx-auto px-5 pt-8 pb-20">
    <h1 class="display text-accent mb-4"><?= esc($page['title']) ?></h1>
    <p class="lead max-w-xl mb-12"><?= t('Tule käymään tai ota yhteyttä.', 'Drop by or get in touch.') ?></p>
    <div class="rule-accent mb-16"></div>

    <div class="grid-asymmetric">
        <div class="space-y-8">
            <?php if (!empty($s['address'])): ?>
            <div>
                <p class="label mb-2"><?= t('Osoite', 'Address') ?></p>
                <p class="text-lg font-semibold"><?= esc($s['address']) ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($s['phone'])): ?>
            <div>
                <p class="label mb-2"><?= t('Puhelin', 'Phone') ?></p>
                <a href="tel:<?= esc($s['phone']) ?>" class="text-lg font-semibold text-accent hover:text-text transition-colors"><?= esc($s['phone']) ?></a>
            </div>
            <?php endif; ?>

            <?php if (!empty($s['email'])): ?>
            <div>
                <p class="label mb-2"><?= t('Sähköposti', 'Email') ?></p>
                <a href="mailto:<?= esc($s['email']) ?>" class="text-lg font-semibold text-accent hover:text-text transition-colors"><?= esc($s['email']) ?></a>
            </div>
            <?php endif; ?>

            <?php if (!empty($s['social_links'])): ?>
            <div>
                <p class="label mb-3"><?= t('Sosiaalinen media', 'Social') ?></p>
                <div class="flex gap-5">
                    <?php foreach ($s['social_links'] as $link): ?>
                    <a href="<?= esc($link['url'] ?? '') ?>" target="_blank" rel="noopener" class="text-muted hover:text-text transition-colors" aria-label="<?= ucfirst($link['platform'] ?? '') ?>">
                        <?= socialIcon($link['platform'] ?? '') ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <p class="label mb-4"><?= t('Kartta', 'Map') ?></p>
            <div class="border border-editorial relative" style="overflow:hidden;">
                <div id="map" style="width:100%; height:380px;"></div>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-1 mt-3 text-xs text-muted">
                <span class="flex items-center gap-1.5"><span class="legend-dot dot-landmark"></span><?= t('Maamerkki', 'Landmark') ?></span>
                <span class="flex items-center gap-1.5"><span class="legend-dot dot-transit"></span><?= t('Liikenne', 'Transit') ?></span>
                <span class="flex items-center gap-1.5"><span class="legend-dot dot-parking"></span><?= t('Pysäköinti', 'Parking') ?></span>
                <span class="flex items-center gap-1.5"><span class="legend-dot dot-restaurant"></span><?= t('Ravintola', 'Restaurant') ?></span>
            </div>
            <p class="text-sm text-muted mt-2">
                <a href="https://www.openstreetmap.org/?mlat=62.2386&mlon=25.7531#map=17/62.2386/25.7531" target="_blank" rel="noopener" class="text-accent hover:text-text transition-colors"><?= t('Avaa kartta', 'Open map') ?> &rarr;</a>
            </p>
            <p class="mt-1">
                <a href="https://www.google.com/maps/dir/?api=1&destination=Satamakatu+2+B+40100+Jyv%C3%A4skyl%C3%A4" target="_blank" rel="noopener" class="btn" style="display:inline-flex;height:40px;line-height:40px;padding:0 20px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                    <?= t('Reittiohjeet', 'Directions') ?>
                </a>
            </p>
        </div>
    </div>

    <div class="rule mt-16 mb-12"></div>

    <div class="max-w-xl">
        <h2 class="headline text-accent mb-4"><?= t('Lähetä viesti', 'Send a message') ?></h2>
        <?php if (!empty($sent)): ?>
        <p class="text-accent font-semibold mb-4"><?= t('Viesti lähetetty! Otamme yhteyttä pian.', 'Message sent! We\'ll get back to you soon.') ?></p>
        <?php elseif (!empty($error)): ?>
        <p class="text-warm font-semibold mb-4"><?= esc($error) ?></p>
        <?php endif; ?>
        <form method="post" action="<?= url('contact-submit') ?>" class="space-y-5" id="contact-form">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="ts" value="<?= time() ?>" id="form-ts">
            <input type="hidden" name="js" value="" id="form-js">
            <div style="position:absolute;left:-9999px;" aria-hidden="true"><label for="website">Website</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>
            <div>
                <label for="name" class="label block mb-1"><?= t('Nimi', 'Name') ?></label>
                <input type="text" id="name" name="name" required class="w-full bg-surface border border-editorial text-text px-4 py-3 focus:outline-none focus:border-accent transition-colors">
            </div>
            <div>
                <label for="email" class="label block mb-1"><?= t('Sähköposti', 'Email') ?></label>
                <input type="email" id="email" name="email" required class="w-full bg-surface border border-editorial text-text px-4 py-3 focus:outline-none focus:border-accent transition-colors">
            </div>
            <div>
                <label for="message" class="label block mb-1"><?= t('Viesti', 'Message') ?></label>
                <textarea id="message" name="message" rows="5" required class="w-full bg-surface border border-editorial text-text px-4 py-3 focus:outline-none focus:border-accent transition-colors"></textarea>
            </div>
            <button type="submit" class="btn"><?= t('Lähetä', 'Send') ?></button>
            <p class="text-xs text-muted mt-3"><?= t('Lähettämällä viestin hyväksyt <a href="' . url('tietosuoja') . '" class="text-accent hover:text-text transition-colors">tietosuojaselosteemme</a>.', 'By submitting, you accept our <a href="' . url('privacy') . '" class="text-accent hover:text-text transition-colors">privacy policy</a>.') ?></p>
        </form>
    </div>
</section>

<?php include INCLUDES_DIR . '/footer.php'; ?>
