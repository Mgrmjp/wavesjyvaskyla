<?php
if ($kitchenWait === null) {
    return;
}
?>
<div class="site-header-wait" aria-live="polite" title="<?= esc($kitchenWait['title']) ?>">
    <span class="site-header-wait__pulse" aria-hidden="true"></span>
    <span class="site-header-wait__main">
        <span class="site-header-wait__text"><?= esc($kitchenWait['text']) ?></span>
        <span class="site-header-wait__time"><?= esc($kitchenWait['time']) ?></span>
    </span>
    <span class="site-header-wait__tag"><?= esc($kitchenWait['test']) ?></span>
    <span class="site-header-wait__info"><?= esc($kitchenWait['title']) ?></span>
</div>
