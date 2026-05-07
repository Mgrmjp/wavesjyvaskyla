<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';
adminAuth();

$data = DataStore::ensure('notices', ['notices' => []]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $deleteId = $_POST['id'] ?? '';
        $data['notices'] = array_values(array_filter($data['notices'], fn($n) => ($n['id'] ?? '') !== $deleteId));
        DataStore::save('notices', $data);
        header('Location: /admin/notices.php?status=deleted');
        exit;
    }

    if ($action === 'toggle') {
        $toggleId = $_POST['id'] ?? '';
        foreach ($data['notices'] as &$n) {
            if (($n['id'] ?? '') === $toggleId) {
                $n['active'] = !($n['active'] ?? false);
                break;
            }
        }
        DataStore::save('notices', $data);
        header('Location: /admin/notices.php');
        exit;
    }

    $data['notices'] = [];
    $texts_fi = $_POST['text_fi'] ?? [];
    foreach ($texts_fi as $i => $text_fi) {
        if (empty($text_fi)) continue;
        $data['notices'][] = [
            'id' => $_POST['item_id'][$i] ?? generateId(),
            'text_fi' => $text_fi,
            'text_en' => $_POST['text_en'][$i] ?? '',
            'active' => !empty($_POST['active'][$i]),
            'start_date' => $_POST['start_date'][$i] ?? '',
            'end_date' => $_POST['end_date'][$i] ?? '',
            'style' => $_POST['style'][$i] ?? 'info',
        ];
    }
    DataStore::save('notices', $data);
    header('Location: notices.php?saved=1');
    exit;
}

$today = date('Y-m-d');

function noticeStatus(array $n, string $today): string
{
    if (!empty($n['active'])) {
        $start = $n['start_date'] ?? '';
        $end = $n['end_date'] ?? '';
        if ($start && $today < $start) return 'scheduled';
        if ($end && $today > $end) return 'expired';
        return 'published';
    }
    return 'draft';
}

function noticeStatusLabel(string $status): string
{
    return match($status) {
        'published' => 'Aktiivinen',
        'scheduled' => 'Ajastettu',
        'expired' => 'Vanhentunut',
        'draft' => 'Luonnos',
        default => $status,
    };
}

$flash = $_GET['status'] ?? '';

$title = 'Ilmoitukset';
include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['saved'])): ?><div class="alert">Tallennettu!</div><?php endif; ?>
<?php if ($flash === 'deleted'): ?><div class="alert alert--error">Ilmoitus poistettiin.</div><?php endif; ?>

<?php
$active = array_filter($data['notices'], fn($n) => noticeStatus($n, $today) === 'published');
$scheduled = array_filter($data['notices'], fn($n) => noticeStatus($n, $today) === 'scheduled');
$expired = array_filter($data['notices'], fn($n) => noticeStatus($n, $today) === 'expired');
$draft = array_filter($data['notices'], fn($n) => noticeStatus($n, $today) === 'draft');
$allNotices = $data['notices'];
?>

<div class="editor-list-overview">
    <span class="editor-overview-pill"><strong><?= count($active) ?></strong> aktiivinen</span>
    <span class="editor-overview-pill"><strong><?= count($scheduled) ?></strong> ajastettu</span>
    <span class="editor-overview-pill"><strong><?= count($expired) ?></strong> vanhentunut</span>
    <span class="editor-overview-pill"><strong><?= count($draft) ?></strong> luonnosta</span>
</div>

<form method="post" class="mt-4">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">

    <div class="editor-items-stack" style="padding-bottom:0">
        <?php if (empty($allNotices)): ?>
        <?php renderEmptyState('ⓘ', 'Ei ilmoituksia', 'Luo ensimmäinen ilmoitus alla olevalla lomakkeella.'); ?>
        <?php else: ?>
        <?php foreach ($allNotices as $i => $n): ?>
        <?php
        $status = noticeStatus($n, $today);
        $style = $n['style'] ?? 'info';
        $iconMap = ['info' => 'ⓘ', 'warning' => '⚠', 'closed' => '●'];
        $iconClass = 'notice-card__icon--' . ($style === 'closed' ? 'closed' : $style);
        ?>
        <div class="notice-card">
            <input type="hidden" name="item_id[<?= $i ?>]" value="<?= esc($n['id'] ?? '') ?>">
            <div class="notice-card__icon <?= $iconClass ?>"><?= $iconMap[$style] ?? 'ⓘ' ?></div>
            <div class="notice-card__body">
                <div class="flex items-center gap-2 flex-wrap">
                    <h4><?= esc(mb_substr($n['text_fi'] ?? '', 0, 60)) ?></h4>
                    <?php renderStatusBadge($status, noticeStatusLabel($status)); ?>
                    <?php renderTranslationBadge($n['text_fi'] ?? '', $n['text_en'] ?? ''); ?>
                </div>
                <div class="notice-card__text"><?= esc($n['text_en'] ?? '') ?><br><em><?= esc(mb_substr($n['text_fi'] ?? '', 60)) ?></em></div>
                <div class="notice-card__dates">
                    <?php if (!empty($n['start_date'])): ?><span>Alkaa: <?= esc($n['start_date']) ?></span><?php endif; ?>
                    <?php if (!empty($n['end_date'])): ?><span>Päättyy: <?= esc($n['end_date']) ?></span><?php endif; ?>
                    <?php if ($status === 'expired'): ?><span style="color:var(--danger);font-weight:700">VANHENTUNUT</span><?php endif; ?>
                </div>
                <div class="notice-card__langs" style="margin-top:0.35rem">
                    <div class="form-group form-group--fi" style="margin-bottom:0;flex:1;min-width:140px">
                        <label style="font-size:0.7rem"><?= flagSvg('fi') ?> Teksti</label>
                        <input type="text" name="text_fi[<?= $i ?>]" value="<?= esc($n['text_fi'] ?? '') ?>" placeholder="Ilmoitus suomeksi">
                    </div>
                    <div class="form-group form-group--en" style="margin-bottom:0;flex:1;min-width:140px">
                        <label style="font-size:0.7rem"><?= flagSvg('gb') ?> Text</label>
                        <input type="text" name="text_en[<?= $i ?>]" value="<?= esc($n['text_en'] ?? '') ?>" placeholder="Notice in English">
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap" style="margin-top:0.35rem">
                    <div class="form-group" style="margin-bottom:0;min-width:100px">
                        <label style="font-size:0.7rem">Alku</label>
                        <input type="date" name="start_date[<?= $i ?>]" value="<?= esc($n['start_date'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;min-width:100px">
                        <label style="font-size:0.7rem">Loppu</label>
                        <input type="date" name="end_date[<?= $i ?>]" value="<?= esc($n['end_date'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;min-width:90px">
                        <label style="font-size:0.7rem">Tyyli</label>
                        <select name="style[<?= $i ?>]">
                            <option value="info" <?= $style === 'info' ? 'selected' : '' ?>>Info</option>
                            <option value="warning" <?= $style === 'warning' ? 'selected' : '' ?>>Varoitus</option>
                            <option value="closed" <?= $style === 'closed' ? 'selected' : '' ?>>Suljettu</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="notice-card__actions">
                <label class="editor-visibility-toggle" style="padding:0.3rem 0.5rem;font-size:0.75rem">
                    <input type="checkbox" name="active[<?= $i ?>]" value="1" <?= !empty($n['active']) ? 'checked' : '' ?>>
                    <span>Aktiivinen</span>
                </label>
                <button type="submit" formaction="/admin/notices.php" formmethod="post" name="action" value="delete" onclick="return confirm('Poista tämä ilmoitus?')" class="btn btn--danger btn--sm">Poista</button>
                <input type="hidden" name="id" value="<?= esc($n['id'] ?? '') ?>">
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="notice-card" style="border:2px dashed var(--line)">
            <div class="notice-card__icon notice-card__icon--info">ⓘ</div>
            <div class="notice-card__body">
                <h4>Uusi ilmoitus</h4>
                <div class="notice-card__langs">
                    <div class="form-group form-group--fi" style="margin-bottom:0;flex:1;min-width:140px">
                        <label style="font-size:0.7rem"><?= flagSvg('fi') ?> Teksti</label>
                        <input type="text" name="text_fi[]" placeholder="Ilmoitus suomeksi">
                    </div>
                    <div class="form-group form-group--en" style="margin-bottom:0;flex:1;min-width:140px">
                        <label style="font-size:0.7rem"><?= flagSvg('gb') ?> Text</label>
                        <input type="text" name="text_en[]" placeholder="Notice in English">
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap" style="margin-top:0.35rem">
                    <div class="form-group" style="margin-bottom:0;min-width:100px">
                        <label style="font-size:0.7rem">Alku</label>
                        <input type="date" name="start_date[]">
                    </div>
                    <div class="form-group" style="margin-bottom:0;min-width:100px">
                        <label style="font-size:0.7rem">Loppu</label>
                        <input type="date" name="end_date[]">
                    </div>
                    <div class="form-group" style="margin-bottom:0;min-width:90px">
                        <label style="font-size:0.7rem">Tyyli</label>
                        <select name="style[]">
                            <option value="info">Info</option>
                            <option value="warning">Varoitus</option>
                            <option value="closed">Suljettu</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="notice-card__actions">
                <label class="editor-visibility-toggle" style="padding:0.3rem 0.5rem;font-size:0.75rem">
                    <input type="checkbox" name="active[]" value="1" checked>
                    <span>Aktiivinen</span>
                </label>
            </div>
        </div>
    </div>

    <div class="admin-sticky-save">
        <span class="admin-sticky-save__info">Tallenna ilmoitukset. Tyhjät ilmoitukset ohitetaan.</span>
        <button type="submit">Tallenna ilmoitukset</button>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
