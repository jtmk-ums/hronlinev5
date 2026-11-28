<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed> $timeline
 */

$this->setTitle('Timeline Rekod Perkhidmatan - ' . $applicationParams->name);
$staff = $timeline['staff'];
$items = $timeline['items'];
?>

<main class="profile-page perkhidmatan-timeline-page">
    <section class="profile-card page-main-card">
        <div class="profile-heading page-card-heading">
            <div>
                <h1>Timeline Rekod Perkhidmatan</h1>
                <p><?= Html::encode((string) $staff['name']) ?></p>
            </div>
            <div class="profile-actions">
                <a class="button-link button-link-secondary" href="/perkhidmatan/view?id=<?= Html::encode(rawurlencode((string) $staff['icno'])) ?>">
                    <i data-lucide="arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-section page-section-body">
            <section class="content-inner-card">
                <div class="profile-card-header grid-card-header grid-result-header">
                    <div>
                        <h2>Sejarah Status</h2>
                    </div>
                </div>
                <?php if ($items === []): ?>
                    <div class="profile-empty">Tiada rekod timeline dijumpai.</div>
                <?php endif; ?>
                <dl class="detail-list">
                    <?php foreach ($items as $item): ?>
                        <div>
                            <dt><?= Html::encode((string) $item['date']) ?></dt>
                            <dd><?= Html::encode((string) $item['source']) ?>: <?= Html::encode((string) $item['title']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>
        </div>
    </section>
</main>
