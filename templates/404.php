<?php
http_response_code(404);
$s = settings();
include INCLUDES_DIR . '/header.php';
?>

<section class="max-w-5xl mx-auto px-5 pt-20 pb-32">
    <p class="display text-muted mb-6">404</p>
    <h1 class="headline mb-6"><?= t('Sivua ei löytynyt', 'Page not found') ?></h1>
    <p class="lead mb-8"><?= t('Osoite on väärä tai sivu on poistettu.', 'The address is wrong or the page has been removed.') ?></p>
    <a href="<?= url() ?>" class="px-6 py-3 bg-accent text-bg font-bold text-sm tracking-wide uppercase hover:opacity-90 transition-opacity"><?= t('Etusivulle', 'Back to home') ?></a>
</section>

<?php include INCLUDES_DIR . '/footer.php'; ?>
