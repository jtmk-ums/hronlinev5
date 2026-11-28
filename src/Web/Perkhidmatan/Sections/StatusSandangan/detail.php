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
 * @var array<string, mixed> $detail
 * @var string $backUrl
 * @var array{enabled: bool, message: string, createUrl: string} $management
 * @var PerkhidmatanCrudService $crud
 */

$title = (string) ($detail['title'] ?? 'Rekod Perkhidmatan');
$slug = (string) ($detail['slug'] ?? '');
$record = is_array($detail['record'] ?? null) ? $detail['record'] : [];
$recordId = (string) ($record['id'] ?? '');
$fields = is_array($record['fields'] ?? null) ? $record['fields'] : [];
$this->setTitle('Lihat ' . $title . ' - ' . $applicationParams->name);
$updateUrl = ($management['enabled'] ?? false) === true ? $crud->updateUrl($slug, $recordId) : '';
$deleteUrl = ($management['enabled'] ?? false) === true ? $crud->deleteUrl($slug, $recordId) : '';
?>

<main class="profile-page perkhidmatan-section-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= Html::encode((string) ($detail['staff']['name'] ?? 'Maklumat rekod perkhidmatan')) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
                <?php if ($updateUrl !== ''): ?>
                    <a class="button-link" href="<?= Html::encode($updateUrl) ?>">
                        <i data-lucide="pencil"></i>
                        <span>Kemaskini</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header grid-result-header">
                    <div>
                        <h2>Maklumat Rekod</h2>
                    </div>
                </div>

                <dl class="detail-list perkhidmatan-record-list">
                    <?php foreach ($fields as $field): ?>
                        <?php if (!is_array($field)) { continue; } ?>
                        <div>
                            <dt><?= Html::encode((string) ($field['label'] ?? '')) ?></dt>
                            <dd><?= Html::encode((string) ($field['value'] ?? '')) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>

                <?php if ($deleteUrl !== ''): ?>
                    <div class="record-form-actions perkhidmatan-record-actions">
                        <form class="grid-action-form" action="<?= Html::encode($deleteUrl) ?>" method="post" onsubmit="return window.confirm('Padam rekod ini?');">
                            <?= $csrf->hiddenInput() ?>
                            <button class="grid-action grid-action-danger" type="submit">
                                <i data-lucide="trash-2"></i>
                                <span>Padam</span>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</main>
