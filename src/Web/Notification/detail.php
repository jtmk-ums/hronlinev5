<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $title
 * @var array<string, string>|null $notification
 * @var string $backUrl
 * @var string $modeLabel
 */

$this->setTitle($title . ' - ' . $applicationParams->name);
$backUrl = $backUrl ?? '/site/notifikasi';
$modeLabel = $modeLabel ?? 'Inbox';
?>

<main class="profile-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1><?= Html::encode($title) ?></h1>
                <p><?= $notification === null ? 'Rekod notifikasi tidak dijumpai.' : Html::encode($notification['title'] ?? '-') ?></p>
            </div>
            <a class="button-link button-link-secondary" href="<?= Html::encode($backUrl) ?>">
                <i data-lucide="arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="page-section-body">
            <?php if ($notification === null): ?>
                <div class="profile-empty">Notifikasi tidak dijumpai atau bukan milik anda.</div>
            <?php else: ?>
                <section class="content-inner-card">
                    <div class="profile-card-header">
                        <h2><?= Html::encode($notification['title'] ?? '-') ?></h2>
                    </div>
                    <dl class="detail-list">
                        <div>
                            <dt>Folder</dt>
                            <dd><?= Html::encode($modeLabel) ?></dd>
                        </div>
                        <div>
                            <dt>Tarikh</dt>
                            <dd><?= Html::encode($notification['date'] ?? '-') ?></dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd><?= Html::encode($notification['statusLabel'] ?? '-') ?></dd>
                        </div>
                        <div>
                            <dt>Kandungan</dt>
                            <dd class="notification-content"><?= (string) ($notification['contentHtml'] ?? '-') ?></dd>
                        </div>
                    </dl>
                </section>
            <?php endif; ?>
        </div>
    </section>
</main>
