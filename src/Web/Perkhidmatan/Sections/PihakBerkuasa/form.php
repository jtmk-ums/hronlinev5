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
 * @var array<string, mixed> $form
 */

$mode = (string) ($form['mode'] ?? 'create');
$title = ($mode === 'update' ? 'Kemaskini ' : 'Tambah ') . (string) ($form['title'] ?? 'Rekod Perkhidmatan');
$this->setTitle($title . ' - ' . $applicationParams->name);
$values = is_array($form['values'] ?? null) ? $form['values'] : [];
$errors = is_array($form['errors'] ?? null) ? $form['errors'] : [];
$fields = is_array($form['fields'] ?? null) ? $form['fields'] : [];
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="field-error">' . Html::encode((string) $errors[$field]) . '</p>'
    : '';
$selected = static fn (string $field, string $value): string => (string) ($values[$field] ?? '') === $value ? ' selected' : '';
?>

<main class="profile-page perkhidmatan-section-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= Html::encode((string) ($form['staff']['name'] ?? 'Maklumat rekod perkhidmatan')) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="<?= Html::encode((string) ($form['backUrl'] ?? '/perkhidmatan/index')) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header">
                    <h2>Maklumat Rekod</h2>
                </div>

                <?php if (isset($errors['general'])): ?>
                    <div class="record-form-alert">
                        <div class="alert alert-danger"><?= Html::encode((string) $errors['general']) ?></div>
                    </div>
                <?php endif; ?>

                <form class="record-form" method="post" action="<?= Html::encode((string) ($form['action'] ?? '')) ?>" novalidate>
                    <?= $csrf->hiddenInput() ?>

                    <div class="record-form-grid">
                        <?php foreach ($fields as $field): ?>
                            <?php
                            if (!is_array($field)) {
                                continue;
                            }
                            $name = (string) ($field['name'] ?? '');
                            $label = (string) ($field['label'] ?? $name);
                            $type = (string) ($field['type'] ?? 'text');
                            $required = ($field['required'] ?? false) === true;
                            $value = (string) ($values[$name] ?? '');
                            ?>
                            <label class="grid-field">
                                <span><?= Html::encode($label) ?><?= $required ? ' <b>*</b>' : '' ?></span>
                                <?php if ($type === 'select'): ?>
                                    <select name="<?= Html::encode($name) ?>" data-searchable-select>
                                        <option value="">Pilih <?= Html::encode($label) ?></option>
                                        <?php foreach (($field['options'] ?? []) as $option): ?>
                                            <?php if (!is_array($option)) { continue; } ?>
                                            <option value="<?= Html::encode((string) $option['id']) ?>"<?= $selected($name, (string) $option['id']) ?>><?= Html::encode((string) $option['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="<?= Html::encode($type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text')) ?>" name="<?= Html::encode($name) ?>" value="<?= Html::encode($value) ?>">
                                <?php endif; ?>
                                <?= $fieldError($name) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="record-form-actions">
                        <a class="button-link button-link-secondary" href="<?= Html::encode((string) ($form['backUrl'] ?? '/perkhidmatan/index')) ?>">
                            <i data-lucide="arrow-left"></i>
                            <span>Kembali</span>
                        </a>
                        <button class="button-link-success" type="submit">
                            <i data-lucide="save"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
