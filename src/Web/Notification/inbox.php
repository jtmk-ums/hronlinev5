<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var Csrf $csrf
 * @var string $title
 * @var array<string, mixed> $notifications
 */

$this->setTitle($title . ' - ' . $applicationParams->name);

$rows = is_array($notifications['rows'] ?? null) ? $notifications['rows'] : [];
$selected = is_array($notifications['selected'] ?? null) ? $notifications['selected'] : null;
$page = (int) ($notifications['page'] ?? 1);
$pageCount = (int) ($notifications['pageCount'] ?? 1);
$total = (int) ($notifications['total'] ?? 0);
$offset = (int) ($notifications['offset'] ?? 0);
$count = count($rows);
$from = $total === 0 ? 0 : $offset + 1;
$to = $offset + $count;
?>

<main class="profile-page notification-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= Html::encode((string) $total) ?> belum dibaca</p>
            </div>
            <div class="notification-heading-actions">
                <a class="button-link button-link-secondary" href="/site/searching">
                    <i data-lucide="search"></i>
                    <span>Search</span>
                </a>
                <a class="button-link button-link-secondary" href="/site/archive">
                    <i data-lucide="archive"></i>
                    <span>Archive</span>
                </a>
            </div>
        </div>

        <div class="page-section-body">
            <div class="notification-inbox-layout">
                <section class="notification-pane notification-list-pane">
                    <div class="notification-pane-header">
                        <div>
                            <h2>Inbox</h2>
                            <p>Papar <?= Html::encode((string) $from) ?>-<?= Html::encode((string) $to) ?> daripada <?= Html::encode((string) $total) ?></p>
                        </div>
                    </div>

                    <?php if ($rows === []): ?>
                        <div class="profile-empty">Tiada notifikasi belum dibaca.</div>
                    <?php else: ?>
                        <form method="post" action="/site/mark_read" class="notification-list-form">
                            <?= $csrf->hiddenInput() ?>
                            <input type="hidden" name="returnUrl" value="/site/notifikasi?page=<?= Html::encode((string) $page) ?>">

                            <div class="notification-row-list">
                                <?php foreach ($rows as $row): ?>
                                    <?php $id = (string) ($row['id'] ?? ''); ?>
                                    <article class="notification-row<?= $selected !== null && (string) ($selected['id'] ?? '') === $id ? ' is-selected' : '' ?>">
                                        <label class="notification-row-check">
                                            <input type="checkbox" name="ids[]" value="<?= Html::encode($id) ?>">
                                            <span class="sr-only">Pilih</span>
                                        </label>
                                        <a href="<?= Html::encode((string) ($row['url'] ?? '#')) ?>">
                                            <span class="notification-row-title">
                                                <?= Html::encode((string) ($row['title'] ?? '-')) ?>
                                            </span>
                                            <span class="notification-row-meta">
                                                <?= Html::encode((string) ($row['dateShort'] ?? '-')) ?>
                                            </span>
                                            <span class="notification-row-excerpt">
                                                <?= Html::encode((string) ($row['excerpt'] ?? '-')) ?>
                                            </span>
                                        </a>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <div class="record-form-actions notification-list-actions">
                                <button type="submit" class="button-link">
                                    <i data-lucide="mail-open"></i>
                                    <span>Tanda Dibaca</span>
                                </button>
                                <button type="submit" class="button-link button-link-secondary" formaction="/site/send_archive">
                                    <i data-lucide="archive"></i>
                                    <span>Arkib</span>
                                </button>
                            </div>
                        </form>

                        <?php if ($pageCount > 1): ?>
                            <nav class="grid-pagination notification-pagination" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="/site/notifikasi?page=<?= $page - 1 ?>">Sebelum</a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $pageCount; $i++): ?>
                                    <a href="/site/notifikasi?page=<?= $i ?>"<?= $i === $page ? ' class="is-active"' : '' ?>><?= $i ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $pageCount): ?>
                                    <a href="/site/notifikasi?page=<?= $page + 1 ?>">Seterus</a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>

                <section class="notification-pane notification-detail-pane">
                    <?php if ($selected === null): ?>
                        <div class="notification-detail-empty">
                            <i data-lucide="inbox"></i>
                            <span>Pilih notifikasi untuk dibaca.</span>
                        </div>
                    <?php else: ?>
                        <div class="notification-detail-heading">
                            <div>
                                <h2><?= Html::encode((string) ($selected['title'] ?? '-')) ?></h2>
                                <p><?= Html::encode((string) ($selected['date'] ?? '-')) ?></p>
                            </div>
                            <span class="status-badge status-badge-success">
                                <?= Html::encode((string) ($selected['statusLabel'] ?? '-')) ?>
                            </span>
                        </div>
                        <div class="notification-content">
                            <?= (string) ($selected['contentHtml'] ?? '-') ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </section>
</main>
