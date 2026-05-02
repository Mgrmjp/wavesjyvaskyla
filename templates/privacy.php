<?php
$s = settings();
include INCLUDES_DIR . '/header.php';
?>

<section class="max-w-5xl mx-auto px-5 pt-8 pb-20">
    <h1 class="display text-accent mb-4"><?= esc($page['title']) ?></h1>
    <div class="rule-accent mb-12"></div>

    <div class="max-w-2xl space-y-8 text-muted" style="line-height:1.8">
        <?php if (lang() === 'fi'): ?>
        <div>
            <h2 class="headline text-text mb-2">Vastaanottaja</h2>
            <p>Konttiravintola Waves, Satamakatu 2 B, 40100 Jyväskylä.<br>Sähköposti: gdpr@wavesjyvaskyla.fi</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Mitä tietoja keräämme</h2>
            <p>Yhteydenottolomakkeen kautta lähetetyt tiedot: nimi, sähköpostiosoite ja viestin sisältö. Lisäksi tallennamme lähetyshetken aikaleiman ja IP-osoiteen.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Tietojen käsittelyn peruste</h2>
            <p>Tietoja käsitellään oikeutetun inteen perustuen vastaataksemme yhteydenottoihin.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Tietojen säilytys</h2>
            <p>Viestit säilytetään 12 kuukautta vastaanottamisen jälkeen, minkä jälkeen ne poistetaan pysyvästi.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Tietojen jakaminen</h2>
            <p>Emme siirrä henkilötietoja kolmansille osapuolille ellei laki sitä vaadi.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Evästeet</h2>
            <p>Käytämme evästettä ainoastaan sivuston toiminnallisuuteen (kielivalinta ja istunto). Emme käytä seurantaaevästeitä.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Oikeutesi</h2>
            <p>Sinulla on oikeus:<br>
            &bull; Pyytää pääsyä henkilötietoihisi<br>
            &bull; Pyytää tietojen oikaisemista<br>
            &bull; Pyytää tietojen poistamista<br>
            &bull; Peruuttaa suostumuksesi</p>
            <p>Ota yhteyttä osoitteeseen gdpr@wavesjyvaskyla.fi.</p>
        </div>
        <?php else: ?>
        <div>
            <h2 class="headline text-text mb-2">Controller</h2>
            <p>Konttiravintola Waves, Satamakatu 2 B, 40100 Jyväskylä, Finland.<br>Email: gdpr@wavesjyvaskyla.fi</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">What data we collect</h2>
            <p>Information submitted through the contact form: name, email address, and message content. We also record the submission timestamp and IP address.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Legal basis for processing</h2>
            <p>Data is processed based on legitimate interest to respond to inquiries.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Data retention</h2>
            <p>Messages are retained for 12 months after receipt, after which they are permanently deleted.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Data sharing</h2>
            <p>We do not transfer personal data to third parties unless required by law.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Cookies</h2>
            <p>We use cookies solely for site functionality (language preference and session). We do not use tracking cookies.</p>
        </div>
        <div>
            <h2 class="headline text-text mb-2">Your rights</h2>
            <p>You have the right to:<br>
            &bull; Access your personal data<br>
            &bull; Request correction of your data<br>
            &bull; Request deletion of your data<br>
            &bull; Withdraw your consent</p>
            <p>Contact us at gdpr@wavesjyvaskyla.fi.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include INCLUDES_DIR . '/footer.php'; ?>