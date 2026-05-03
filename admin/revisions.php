<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/RevisionLog.php';
require_once __DIR__ . '/includes/helpers.php';
adminAuth();

RevisionLog::init(DATA_DIR);

$flash = '';
$filter = $_GET['filter'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'restore') {
        $id = $_POST['id'] ?? '';
        if (RevisionLog::restore($id)) {
            $flash = 'revision-restored';
        } else {
            $flash = 'restore-failed';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        RevisionLog::deleteRevision($id);
        $flash = 'revision-deleted';
    }
}

$entityFilter = $filter === 'all' ? null : $filter;
$revisions = RevisionLog::getRevisions($entityFilter, 100);

$title = 'Revisiot';
include __DIR__ . '/includes/header.php';
?>

<?php
$flashMessages = [
    'revision-restored' => 'Versio palautettu onnistuneesti.',
    'restore-failed' => 'Palautus epäonnistui.',
    'revision-deleted' => 'Versio poistettu.',
];
if (isset($flashMessages[$flash])): ?><div class="alert"><?= esc($flashMessages[$flash]) ?></div><?php endif; ?>

<div class="editor-list-overview">
    <a href="?filter=all" class="editor-overview-pill <?= $filter === 'all' ? 'is-active' : '' ?>">Kaikki (<?= count(RevisionLog::getRevisions(null, 100)) ?>)</a>
    <a href="?filter=menu" class="editor-overview-pill <?= $filter === 'menu' ? 'is-active' : '' ?>">Menu (<?= count(RevisionLog::getRevisions('menu', 100)) ?>)</a>
    <a href="?filter=settings" class="editor-overview-pill <?= $filter === 'settings' ? 'is-active' : '' ?>">Asetukset (<?= count(RevisionLog::getRevisions('settings', 100)) ?>)</a>
</div>

<?php if (empty($revisions)): ?>
<div class="empty-state mt-4">
    <div class="empty-state__icon">↺</div>
    <h3 class="empty-state__title">Ei versioita</h3>
    <p class="empty-state__text">Muutokset menuun ja asetuksiin tallentuvat automaattisesti versiohistoriaan.</p>
</div>
<?php else: ?>

<div class="editor-items-stack" style="padding-bottom:0">
    <?php foreach ($revisions as $rev): ?>
    <?php
        $entityLabel = $rev['entity'] === 'menu' ? 'Menu' : 'Asetukset';
        $actionLabel = match($rev['action']) {
            'created' => 'Luotu',
            'updated' => 'Muokattu',
            'deleted' => 'Poistettu',
            default => $rev['action'],
        };
        $timeAgo = timeAgo($rev['timestamp'] ?? '');
    ?>
    <details class="editor-list-item revision-item">
        <summary class="editor-list-item__row">
            <div class="editor-list-item__body">
                <div class="revision-entity-badge revision-entity--<?= esc($rev['entity'] ?? '') ?>"><?= esc($entityLabel) ?></div>
                <div class="editor-list-item__content">
                    <h4><?= esc($actionLabel) ?></h4>
                    <div class="editor-list-item__meta">
                        <span class="editor-chip"><?= esc($timeAgo) ?></span>
                        <span class="editor-chip editor-chip--soft"><?= esc(date('d.m.Y H:i', strtotime($rev['timestamp'] ?? 'now'))) ?></span>
                    </div>
                </div>
            </div>
            <div class="editor-list-item__tools">
                <form method="post" style="display:inline" onsubmit="return confirm('Palauta tämä versio? Nykyinen tila korvataan.')">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="action" value="restore">
                    <input type="hidden" name="id" value="<?= esc($rev['id'] ?? '') ?>">
                    <button type="submit" class="editor-tool-button editor-tool-button--primary">Palauta</button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Poista tämä versio?')">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= esc($rev['id'] ?? '') ?>">
                    <button type="submit" class="editor-tool-button">Poista</button>
                </form>
            </div>
        </summary>
        <div class="editor-list-item__details" style="padding:0 14px 14px">
            <?php if (!empty($rev['before'])): ?>
            <div style="margin-bottom:12px">
                <div class="text-xs text-gray mb-1" style="font-weight:700">Ennen:</div>
                <pre style="background:var(--admin-surface-muted);border:1px solid var(--admin-border);border-radius:var(--admin-radius-ctrl);padding:10px;font-size:12px;overflow:auto;max-height:200px;margin:0"><?= esc(json_encode($rev['before'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
            <?php endif; ?>
            <div>
                <div class="text-xs text-gray mb-1" style="font-weight:700">Jälkeen:</div>
                <pre style="background:var(--admin-surface-muted);border:1px solid var(--admin-border);border-radius:var(--admin-radius-ctrl);padding:10px;font-size:12px;overflow:auto;max-height:200px;margin:0"><?= esc(json_encode($rev['data'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
        </div>
    </details>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
function timeAgo(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) return '—';
    $diff = time() - $ts;
    if ($diff < 60) return 'juuri nyt';
    if ($diff < 3600) return floor($diff / 60) . ' min sitten';
    if ($diff < 86400) return floor($diff / 3600) . ' t sitten';
    if ($diff < 604800) return floor($diff / 86400) . ' pv sitten';
    return date('d.m.Y', $ts);
}
?>

<style>
.revision-entity-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 56px; height: 28px; padding: 0 8px;
    border-radius: 6px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em; flex-shrink: 0;
}
.revision-entity--menu { background: var(--admin-primary-soft); color: var(--admin-primary); }
.revision-entity--settings { background: var(--admin-warning-soft); color: var(--admin-warning); }
.editor-list-overview a.is-active { background: var(--admin-primary-soft); border-color: var(--admin-primary); color: var(--admin-primary); }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
