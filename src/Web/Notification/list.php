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
$page = (int) ($notifications['page'] ?? 1);
$pageCount = (int) ($notifications['pageCount'] ?? 1);
$total = (int) ($notifications['total'] ?? 0);
$offset = (int) ($notifications['offset'] ?? 0);
$count = count($rows);
$from = $total === 0 ? 0 : $offset + 1;
$to = $offset + $count;
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p>Senarai notifikasi belum diarkibkan.</p>
            </div>
            <a class="button-link button-link-secondary" href="/dashboard">
                <i data-lucide="arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header grid-result-header">
                    <div>
                        <h2>Notifikasi</h2>
                        <p>Papar <?= Html::encode((string) $from) ?>-<?= Html::encode((string) $to) ?> daripada <?= Html::encode((string) $total) ?> rekod</p>
                    </div>
                </div>

                <?php if ($rows === []): ?>
                    <div class="profile-empty">Tiada notifikasi.</div>
                <?php else: ?>
                    <form method="post" action="/site/mark_read" class="notification-list-form">
                        <?= $csrf->hiddenInput() ?>
                        <input type="hidden" name="returnUrl" value="/site/notifikasi?page=<?= Html::encode((string) $page) ?>">
                        <div class="table-scroll grid-table-wrap">
                            <table class="data-table staff-overview-table">
                                <thead>
                                <tr>
                                    <th>
                                        <span class="sr-only">Pilih</span>
                                    </th>
                                    <th>Tajuk</th>
                                    <th>Tarikh</th>
                                    <th>Status</th>
                                    <th>Tindakan</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <?php $id = (string) ($row['id'] ?? ''); ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="ids[]" value="<?= Html::encode($id) ?>">
                                        </td>
                                        <td class="grid-name">
                                            <?= Html::encode((string) ($row['title'] ?? '-')) ?>
                                            <small><?= Html::encode((string) ($row['excerpt'] ?? '-')) ?></small>
                                        </td>
                                        <td><?= Html::encode((string) ($row['date'] ?? '-')) ?></td>
                                        <td>
                                            <span class="status-badge <?= (string) ($row['status'] ?? '') === '0' ? 'status-badge-warning' : 'status-badge-success' ?>">
                                                <?= Html::encode((string) ($row['statusLabel'] ?? '-')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="grid-actions-inline">
                                                <a class="grid-action" href="<?= Html::encode((string) ($row['url'] ?? '#')) ?>" title="Lihat">
                                                    <i data-lucide="eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
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
                        <nav class="grid-pagination" aria-label="Pagination">
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
        </div>
    </section>
</main>
