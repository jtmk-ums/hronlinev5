<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed> $grid
 */

$this->setTitle('Rekod Perkhidmatan Kakitangan - ' . $applicationParams->name);
$filters = $grid['filters'];
$options = $grid['options'];
$rows = $grid['rows'];
$visibleCount = count($rows);
$resultCount = $grid['count'];
$totalCount = $grid['total'];
$page = $grid['page'];
$pageCount = $grid['pageCount'];
$offset = $grid['offset'];
$firstVisible = $visibleCount === 0 ? 0 : $offset + 1;
$lastVisible = $offset + $visibleCount;
$paginationFilters = array_filter($filters, static fn (string $value): bool => $value !== '');
$pageLink = static fn (int $targetPage): string => '/perkhidmatan/index?' . http_build_query(
    array_merge($paginationFilters, ['page' => (string) $targetPage]),
);
$startPage = max(1, $page - 2);
$endPage = min($pageCount, $page + 2);
if ($endPage - $startPage < 4) {
    $startPage = max(1, $endPage - 4);
    $endPage = min($pageCount, $startPage + 4);
}

$selected = static fn (string $name, string $value): string => ($filters[$name] ?? '') === $value ? ' selected' : '';
$renderOptions = static function (string $name, array $items) use ($selected): void {
    foreach ($items as $item) {
        ?>
        <option value="<?= Html::encode($item['id']) ?>"<?= $selected($name, $item['id']) ?>><?= Html::encode($item['name']) ?></option>
        <?php
    }
};
$statusBadgeClass = static function (array $profile): string {
    $statusCode = ltrim(trim((string) ($profile['statusCode'] ?? '')), '0');
    $statusName = strtolower(trim((string) ($profile['status'] ?? '')));

    return $statusCode === '6' || $statusName === 'bekas pekerja' || $statusName === 'tidak aktif'
        ? 'status-badge-danger'
        : 'status-badge-success';
};
?>

<main class="profile-page perkhidmatan-grid-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Rekod Perkhidmatan Kakitangan</h1>
                <p>Carian dan senarai rekod perkhidmatan</p>
            </div>
        </div>

        <div class="biodata-card-section">
            <div class="profile-card-header grid-card-header">
                <h2>Carian</h2>
            </div>
            <form class="grid-search" method="get" action="/perkhidmatan/index">
                <label class="grid-field">
                    <span>Kategori Jawatan</span>
                    <select name="carian_kategorijawatan" data-searchable-select>
                        <option value="">Semua kategori</option>
                        <?php $renderOptions('carian_kategorijawatan', $options['jobCategories']); ?>
                    </select>
                </label>

                <label class="grid-field">
                    <span>Program Pengajaran</span>
                    <select name="carian_programpengajaran" data-searchable-select>
                        <option value="">Semua program</option>
                        <?php $renderOptions('carian_programpengajaran', $options['programs']); ?>
                    </select>
                </label>

                <label class="grid-field">
                    <span>Kod Program</span>
                    <select name="carian_kodprogram" data-searchable-select>
                        <option value="">Semua kod</option>
                        <?php $renderOptions('carian_kodprogram', $options['programCodes']); ?>
                    </select>
                </label>

                <label class="grid-field">
                    <span>Kampus</span>
                    <select name="campus_id" data-searchable-select>
                        <option value="">Semua kampus</option>
                        <?php $renderOptions('campus_id', $options['campuses']); ?>
                    </select>
                </label>

                <label class="grid-field">
                    <span>JFPIU</span>
                    <select name="DeptId" data-searchable-select>
                        <option value="">Semua JFPIU</option>
                        <?php $renderOptions('DeptId', $options['departments']); ?>
                    </select>
                </label>

                <label class="grid-field">
                    <span>Status Lantikan</span>
                    <select name="statLantikan" data-searchable-select>
                        <option value="">Semua lantikan</option>
                        <?php $renderOptions('statLantikan', $options['appointmentStatuses']); ?>
                    </select>
                </label>

                <label class="grid-field">
                    <span>Pendidikan Tertinggi</span>
                    <select name="HighestEduLevelCd" data-searchable-select>
                        <option value="">Semua pendidikan</option>
                        <?php $renderOptions('HighestEduLevelCd', $options['educationLevels']); ?>
                    </select>
                </label>

                <label class="grid-field">
                    <span>Status</span>
                    <select name="Status" data-searchable-select>
                        <option value="">Semua status</option>
                        <?php $renderOptions('Status', $options['serviceStatuses']); ?>
                    </select>
                </label>

                <label class="grid-field">
                    <span>Jenis Carian</span>
                    <select name="jenis_carian" data-searchable-select>
                        <?php $renderOptions('jenis_carian', $options['searchTypes']); ?>
                    </select>
                </label>

                <label class="grid-field grid-field-wide">
                    <span>Carian Data</span>
                    <input name="carian_data" value="<?= Html::encode($filters['carian_data']) ?>" placeholder="Nama / Nombor IC / ID">
                </label>

                <div class="grid-search-actions">
                    <button type="submit">
                        <i data-lucide="search"></i>
                        <span>Cari</span>
                    </button>
                    <a class="button-link button-link-secondary" href="/perkhidmatan/index">
                        <i data-lucide="rotate-ccw"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </form>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header grid-result-header">
                    <div>
                        <h2>Senarai Kakitangan</h2>
                        <p>Jumlah Carian: <?= Html::encode((string) $resultCount) ?> / <?= Html::encode((string) $totalCount) ?></p>
                    </div>
                </div>
                <div class="table-scroll grid-table-wrap">
                    <table class="data-table staff-overview-table">
                        <thead>
                        <tr>
                            <th>IC/KP</th>
                            <th>Nama</th>
                            <th>Gred</th>
                            <th>Email</th>
                            <th>JFPIU</th>
                            <th>Pendidikan</th>
                            <th>Kampus</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($rows === []): ?>
                            <tr>
                                <td class="grid-empty" colspan="9">Tiada rekod dijumpai.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $profile): ?>
                            <tr>
                                <td><?= Html::encode($profile['icno']) ?></td>
                                <td class="grid-name"><?= Html::encode($profile['name']) ?></td>
                                <td><?= Html::encode($profile['grade']) ?></td>
                                <td><?= Html::encode($profile['email']) ?></td>
                                <td><?= Html::encode($profile['department']) ?></td>
                                <td><?= Html::encode($profile['education']) ?></td>
                                <td><?= Html::encode($profile['campus']) ?></td>
                                <td><span class="status-badge <?= Html::encode($statusBadgeClass($profile)) ?>"><?= Html::encode($profile['status']) ?></span></td>
                                <td>
                                    <a class="grid-action" href="/perkhidmatan/view?id=<?= Html::encode(rawurlencode($profile['icno'])) ?>">
                                        <i data-lucide="eye"></i>
                                        <span>Lihat</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="grid-count-footer">
                    <?php if ($pageCount > 1): ?>
                        <nav class="grid-pagination" aria-label="Halaman Rekod Perkhidmatan">
                            <a class="<?= $page === 1 ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(max(1, $page - 1))) ?>">
                                <i data-lucide="chevron-left"></i>
                                <span>Sebelum</span>
                            </a>
                            <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                                <a class="<?= $pageNumber === $page ? 'is-active' : '' ?>" href="<?= Html::encode($pageLink($pageNumber)) ?>">
                                    <?= Html::encode((string) $pageNumber) ?>
                                </a>
                            <?php endfor; ?>
                            <a class="<?= $page === $pageCount ? 'is-disabled' : '' ?>" href="<?= Html::encode($pageLink(min($pageCount, $page + 1))) ?>">
                                <span>Seterus</span>
                                <i data-lucide="chevron-right"></i>
                            </a>
                        </nav>
                    <?php endif; ?>
                    <span class="grid-count">
                        Papar <?= Html::encode((string) $firstVisible) ?>-<?= Html::encode((string) $lastVisible) ?>
                        daripada <?= Html::encode((string) $resultCount) ?> rekod
                    </span>
                </div>
            </section>
        </div>
    </section>
</main>
