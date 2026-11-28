<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\NotificationCriteria;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $title
 * @var array<string, mixed> $notifications
 * @var NotificationCriteria $criteria
 */

$this->setTitle($title . ' - ' . $applicationParams->name);

$rows = is_array($notifications['rows'] ?? null) ? $notifications['rows'] : [];
$page = (int) ($notifications['page'] ?? 1);
$pageCount = (int) ($notifications['pageCount'] ?? 1);
$total = (int) ($notifications['total'] ?? 0);
$offset = (int) ($notifications['offset'] ?? 0);
$count = count($rows);
$from = $total === 0 ? 0 : $offset + 1;
$to = $offset + $count;
$pageUrl = static function (int $targetPage) use ($criteria): string {
    $params = ['page' => $targetPage];
    foreach (['title', 'content', 'date'] as $field) {
        if ($criteria->{$field} !== '') {
            $params[$field] = $criteria->{$field};
        }
    }
    if ($criteria->status !== null) {
        $params['status'] = $criteria->status;
    }

    return '/site/searching?' . http_build_query($params);
};
?>

<main class="profile-page notification-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Papar <?= Html::encode((string) $from) ?>-<?= Html::encode((string) $to) ?> daripada <?= Html::encode((string) $total) ?> rekod</p>
            </div>
            <a class="button-link button-link-secondary" href="/site/notifikasi">
                <i data-lucide="arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="page-section-body">
            <form method="get" action="/site/searching" class="notification-filter">
                <label>
                    <span>Modul</span>
                    <input type="text" name="title" value="<?= Html::encode($criteria->title) ?>" placeholder="Modul">
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value=""<?= $criteria->status === null ? ' selected' : '' ?>>Semua</option>
                        <option value="0"<?= $criteria->status === 0 ? ' selected' : '' ?>>UNREAD</option>
                        <option value="1"<?= $criteria->status === 1 ? ' selected' : '' ?>>READ</option>
                    </select>
                </label>
                <label>
                    <span>Kandungan</span>
                    <input type="text" name="content" value="<?= Html::encode($criteria->content) ?>">
                </label>
                <label>
                    <span>Tarikh</span>
                    <input type="date" name="date" value="<?= Html::encode($criteria->date) ?>">
                </label>
                <div class="notification-filter-actions">
                    <button type="submit" class="button-link">
                        <i data-lucide="search"></i>
                        <span>Search</span>
                    </button>
                    <a class="button-link button-link-secondary" href="/site/searching">
                        <i data-lucide="rotate-ccw"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </form>

            <section class="notification-pane notification-full-pane">
                <?php if ($rows === []): ?>
                    <div class="profile-empty">Tiada notifikasi.</div>
                <?php else: ?>
                    <div class="notification-row-list">
                        <?php foreach ($rows as $row): ?>
                            <?php $isUnread = (string) ($row['status'] ?? '') === '0'; ?>
                            <article class="notification-row<?= $isUnread ? ' is-unread' : '' ?>">
                                <a href="<?= Html::encode((string) ($row['url'] ?? '#')) ?>">
                                    <span class="notification-row-title">
                                        <?= Html::encode((string) ($row['title'] ?? '-')) ?>
                                    </span>
                                    <span class="notification-row-meta">
                                        <?= Html::encode((string) ($row['dateShort'] ?? '-')) ?>
                                        <span class="status-badge <?= $isUnread ? 'status-badge-warning' : 'status-badge-success' ?>">
                                            <?= Html::encode((string) ($row['statusLabel'] ?? '-')) ?>
                                        </span>
                                    </span>
                                    <span class="notification-row-excerpt">
                                        <?= Html::encode((string) ($row['excerpt'] ?? '-')) ?>
                                    </span>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($pageCount > 1): ?>
                        <nav class="grid-pagination notification-pagination" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="<?= Html::encode($pageUrl($page - 1)) ?>">Sebelum</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $pageCount; $i++): ?>
                                <a href="<?= Html::encode($pageUrl($i)) ?>"<?= $i === $page ? ' class="is-active"' : '' ?>><?= $i ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $pageCount): ?>
                                <a href="<?= Html::encode($pageUrl($page + 1)) ?>">Seterus</a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </section>
</main>
