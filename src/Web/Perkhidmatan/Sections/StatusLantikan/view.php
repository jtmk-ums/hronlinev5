<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\Perkhidmatan\PerkhidmatanCrudService;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var Csrf $csrf
 * @var array<string, mixed>|null $section
 * @var bool $selfMode
 * @var string $backUrl
 * @var array{enabled: bool, message: string, createUrl: string} $management
 * @var PerkhidmatanCrudService $crud
 */

$this->setTitle('Status Lantikan - ' . $applicationParams->name);
$records = $section['records'] ?? [];
$showActions = !$selfMode;
?>

<main class="profile-page perkhidmatan-section-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Status Lantikan</h1>
                <p><?= $section === null ? 'Maklumat status lantikan' : Html::encode((string) $section['staff']['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
                <?php if (($management['createUrl'] ?? '') !== ''): ?>
                    <a class="button-link" href="<?= Html::encode($management['createUrl']) ?>">
                        <i data-lucide="plus"></i>
                        <span>Tambah</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($section === null): ?>
            <div class="profile-empty">Rekod tidak dijumpai.</div>
        <?php else: ?>
            <div class="page-card-section page-section-body">
                <section class="content-inner-card">
                    <div class="profile-card-header grid-card-header grid-result-header">
                        <div>
                            <h2>Status Lantikan</h2>
                        </div>
                    </div>

                    <?php if (!$selfMode): ?>
                        <div class="record-form-alert">
                            <div class="alert alert-info">
                                * LANTIKAN TETAP DI UMS diisi sekali sahaja berdasarkan tarikh mula di lantik tetap walaupun berlaku PERUBAHAN SKIM/JAWATAN.
                            </div>
                            <div class="alert alert-info">
                                * LANTIKAN KONTRAK DI UMS diisi setiap kali kontrak disambung.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="table-scroll grid-table-wrap">
                        <table class="data-table staff-overview-table">
                            <thead>
                            <tr>
                                <th>Status Lantikan</th>
                                <th>Tarikh Mula Lantikan</th>
                                <th>Tarikh Tamat Lantikan</th>
                                <?php if ($showActions): ?>
                                    <th>Tindakan</th>
                                <?php endif; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($records === []): ?>
                                <tr>
                                    <td class="grid-empty" colspan="<?= $showActions ? '4' : '3' ?>">Tiada Rekod</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($records as $record): ?>
                                <?php
                                $recordId = (string) ($record['id'] ?? '');
                                $detailUrl = $showActions ? $crud->detailUrl('status-lantikan', $recordId) : '';
                                $updateUrl = ($management['enabled'] ?? false) === true ? $crud->updateUrl('status-lantikan', $recordId) : '';
                                $deleteUrl = ($management['enabled'] ?? false) === true ? $crud->deleteUrl('status-lantikan', $recordId) : '';
                                ?>
                                <tr>
                                    <td class="grid-name"><?= Html::encode((string) $record['status']) ?></td>
                                    <td><?= Html::encode((string) $record['startDate']) ?></td>
                                    <td><?= Html::encode((string) $record['endDate']) ?></td>
                                    <?php if ($showActions): ?>
                                        <td>
                                            <div class="grid-actions-inline">
                                                <?php if ($detailUrl !== ''): ?>
                                                    <a class="grid-action" href="<?= Html::encode($detailUrl) ?>">
                                                        <i data-lucide="eye"></i>
                                                        <span>Lihat</span>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($updateUrl !== ''): ?>
                                                    <a class="grid-action" href="<?= Html::encode($updateUrl) ?>">
                                                        <i data-lucide="pencil"></i>
                                                        <span>Kemaskini</span>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($deleteUrl !== ''): ?>
                                                    <form class="grid-action-form" action="<?= Html::encode($deleteUrl) ?>" method="post" onsubmit="return window.confirm('Padam rekod ini?');">
                                                        <?= $csrf->hiddenInput() ?>
                                                        <button class="grid-action grid-action-danger" type="submit">
                                                            <i data-lucide="trash-2"></i>
                                                            <span>Padam</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="grid-count-footer">
                        <span class="grid-count">Papar <?= Html::encode((string) count($records)) ?> rekod</span>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </section>
</main>
