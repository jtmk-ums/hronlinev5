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

$title = $section['title'] ?? 'Rekod Perkhidmatan';
$this->setTitle($title . ' - ' . $applicationParams->name);
$records = $section['records'] ?? [];
$columns = [];
foreach ($records as $record) {
    if (!is_array($record['fields'] ?? null)) {
        continue;
    }

    foreach ($record['fields'] as $field) {
        if (!is_array($field)) {
            continue;
        }

        $label = (string) ($field['label'] ?? '');
        if ($label !== '' && !in_array($label, $columns, true)) {
            $columns[] = $label;
        }
    }

    if ($columns !== []) {
        break;
    }
}

$showActions = !$selfMode;
$emptyColspan = count($columns) + ($showActions ? 1 : 0);
?>

<main class="profile-page perkhidmatan-section-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode((string) $title) ?></h1>
                <p><?= $section === null ? 'Maklumat rekod perkhidmatan' : Html::encode((string) $section['staff']['name']) ?></p>
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
                            <h2>Senarai Rekod</h2>
                            <p><?= Html::encode((string) count($records)) ?> rekod</p>
                        </div>
                    </div>

                    <?php if (($section['readOnlyNote'] ?? '') !== ''): ?>
                        <p class="grid-empty"><?= Html::encode((string) $section['readOnlyNote']) ?></p>
                    <?php endif; ?>
                    <?php if (($management['message'] ?? '') !== ''): ?>
                        <div class="record-form-alert">
                            <div class="alert alert-info"><?= Html::encode((string) $management['message']) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="table-scroll grid-table-wrap">
                        <table class="data-table staff-overview-table">
                            <thead>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <th><?= Html::encode($column) ?></th>
                                <?php endforeach; ?>
                                <?php if ($showActions): ?>
                                    <th>Tindakan</th>
                                <?php endif; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($records === []): ?>
                                <tr>
                                    <td class="grid-empty" colspan="<?= Html::encode((string) max(1, $emptyColspan)) ?>">Tiada Rekod</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($records as $record): ?>
                                <?php
                                $recordId = (string) ($record['id'] ?? '');
                                $detailUrl = $showActions ? $crud->detailUrl((string) $section['slug'], $recordId) : '';
                                $updateUrl = ($management['enabled'] ?? false) === true ? $crud->updateUrl((string) $section['slug'], $recordId) : '';
                                $deleteUrl = ($management['enabled'] ?? false) === true ? $crud->deleteUrl((string) $section['slug'], $recordId) : '';
                                $values = [];
                                foreach (($record['fields'] ?? []) as $field) {
                                    if (!is_array($field)) {
                                        continue;
                                    }

                                    $values[(string) ($field['label'] ?? '')] = (string) ($field['value'] ?? '');
                                }
                                ?>
                                <tr>
                                    <?php foreach ($columns as $column): ?>
                                        <td><?= Html::encode($values[$column] ?? '-') ?></td>
                                    <?php endforeach; ?>
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
