<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $profile
 * @var array<string, mixed> $history
 */

$this->setTitle('Rekod Perkhidmatan Saya - ' . $applicationParams->name);
$fields = $profile['fields'] ?? [];
$sections = $profile['sections'] ?? [];
$historyRows = $history['rows'] ?? [];
$icno = (string) ($profile['icno'] ?? '');
$historyPage = (int) ($history['page'] ?? 1);
$historyPageCount = (int) ($history['pageCount'] ?? 1);
$historyTotal = (int) ($history['total'] ?? 0);
$historyOffset = (int) ($history['offset'] ?? 0);
$historyFirstVisible = $historyRows === [] ? 0 : $historyOffset + 1;
$historyLastVisible = $historyOffset + count($historyRows);
$historyPageLink = static fn (int $targetPage): string => '/perkhidmatan/viewuser?' . http_build_query([
    'history_page' => (string) $targetPage,
]);
?>

<main class="profile-page perkhidmatan-profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Rekod Perkhidmatan Saya</h1>
                <p><?= $profile === null ? 'Maklumat perkhidmatan' : Html::encode((string) $profile['displayName']) ?></p>
            </div>
            <div class="profile-actions">
                <?php if ($profile !== null): ?>
                    <a class="button-link" href="/perkhidmatan/tree-stat?id=<?= Html::encode(rawurlencode($icno)) ?>">
                        <i data-lucide="git-branch"></i>
                        <span>Timeline</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($profile === null): ?>
            <div class="profile-empty">Rekod kakitangan tidak dijumpai.</div>
        <?php else: ?>
            <div class="profile-hero page-card-section">
                <img class="profile-photo" src="<?= Html::encode((string) $profile['profileImage']) ?>" alt="Profile image">
                <div class="profile-identity">
                    <h2><?= Html::encode((string) $profile['displayName']) ?></h2>
                    <p><?= Html::encode($icno) ?></p>
                    <p><?= Html::encode((string) $profile['email']) ?></p>
                </div>
            </div>

            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2>Ringkasan Perkhidmatan</h2>
                    </div>
                    <dl class="profile-detail-grid">
                        <?php foreach ($fields as $field): ?>
                            <div>
                                <dt><?= Html::encode((string) $field['label']) ?></dt>
                                <dd><?= Html::encode((string) $field['value']) ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>
            </div>

            <div class="page-card-section">
                <div class="profile-card-header">
                    <h2>Maklumat Dan Rekod Perkhidmatan</h2>
                </div>
                <nav class="profile-section-grid" aria-label="Rekod Perkhidmatan">
                    <?php foreach ($sections as $section): ?>
                        <a href="<?= Html::encode((string) $section['url']) ?>">
                            <i data-lucide="arrow-right"></i>
                            <span><?= Html::encode((string) $section['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header grid-card-header grid-result-header">
                        <div>
                            <h2>Perubahan Data Terkini</h2>
                        </div>
                    </div>
                    <div class="table-scroll grid-table-wrap">
                        <table class="data-table">
                            <thead>
                            <tr>
                                <th>Bahagian</th>
                                <th>Aktiviti</th>
                                <th>Tarikh</th>
                                <th>ID Rekod</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($historyRows === []): ?>
                                <tr>
                                    <td class="grid-empty" colspan="4">Tiada rekod perubahan.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($historyRows as $row): ?>
                                <tr>
                                    <td class="grid-name"><?= Html::encode((string) $row['section']) ?></td>
                                    <td><?= Html::encode((string) $row['activity']) ?></td>
                                    <td><?= Html::encode((string) $row['updatedAt']) ?></td>
                                    <td><?= Html::encode((string) $row['idValue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="grid-count-footer">
                        <?php if ($historyPageCount > 1): ?>
                            <nav class="grid-pagination" aria-label="Halaman Perubahan Data">
                                <a class="<?= $historyPage === 1 ? 'is-disabled' : '' ?>" href="<?= Html::encode($historyPageLink(max(1, $historyPage - 1))) ?>">
                                    <i data-lucide="chevron-left"></i>
                                    <span>Sebelum</span>
                                </a>
                                <?php for ($pageNumber = 1; $pageNumber <= $historyPageCount; $pageNumber++): ?>
                                    <a class="<?= $pageNumber === $historyPage ? 'is-active' : '' ?>" href="<?= Html::encode($historyPageLink($pageNumber)) ?>">
                                        <?= Html::encode((string) $pageNumber) ?>
                                    </a>
                                <?php endfor; ?>
                                <a class="<?= $historyPage === $historyPageCount ? 'is-disabled' : '' ?>" href="<?= Html::encode($historyPageLink(min($historyPageCount, $historyPage + 1))) ?>">
                                    <span>Seterus</span>
                                    <i data-lucide="chevron-right"></i>
                                </a>
                            </nav>
                        <?php endif; ?>
                        <span class="grid-count">
                            Papar <?= Html::encode((string) $historyFirstVisible) ?>-<?= Html::encode((string) $historyLastVisible) ?>
                            daripada <?= Html::encode((string) $historyTotal) ?> rekod
                        </span>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
