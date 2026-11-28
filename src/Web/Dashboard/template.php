<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\Shared\LegacyUrl;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var array<string, mixed>|null $dashboard
 * @var string $identityId
 */

$this->setTitle('Dashboard - ' . $applicationParams->name);
?>

<?php if ($dashboard === null): ?>
    <main class="dashboard-page">
        <section class="profile-card page-main-card">
            <div class="dashboard-band page-card-heading">
            <h1>Dashboard unavailable</h1>
            <p>The current legacy session <?= Html::encode($identityId) ?> is not a staff dashboard session.</p>
            </div>
            <div class="page-section-body">
                <a class="button-link" href="/login">
                    <i data-lucide="log-in"></i>
                    <span>Back to login</span>
                </a>
            </div>
        </section>
    </main>
<?php else: ?>
    <?php
    /** @var array<string, string> $staff */
    $staff = $dashboard['staff'];
    /** @var array<string, string> $summary */
    $summary = $dashboard['summary'];
    /** @var array<string, string> $attendance */
    $attendance = $dashboard['attendance'];
    /** @var list<array<string, string>> $pendingTasks */
    $pendingTasks = $dashboard['pendingTasks'];
    /** @var list<array{label: string, url: string, external: bool}> $shortcuts */
    $shortcuts = $dashboard['shortcuts'];
    $displayName = trim($staff['CONm'] ?? '');
    ?>

    <main class="dashboard-page">
        <section class="profile-card page-main-card dashboard-main-card">
        <div class="dashboard-band dashboard-heading page-card-heading">
            <div>
                <h1>Selamat datang</h1>
                <p><?= Html::encode($displayName) ?></p>
            </div>
            <a class="button-link button-link-secondary" href="/external-login">
                <i data-lucide="user-round-cog"></i>
                <span>Switch User</span>
            </a>
        </div>

        <div class="dashboard-grid dashboard-metrics page-section-body page-card-section" aria-label="Dashboard summary">
            <article class="metric-card">
                <span>CUTI</span>
                <strong><?= Html::encode($summary['cuti']) ?></strong>
                <small><?= Html::encode($summary['percentagecuti']) ?>%</small>
            </article>
            <article class="metric-card">
                <span>MyIDP</span>
                <strong><?= Html::encode($summary['idp']) ?></strong>
                <small><?= Html::encode($summary['percentageidp']) ?>%</small>
            </article>
            <article class="metric-card">
                <span>MyHealth</span>
                <strong>RM <?= Html::encode($summary['klinikpanel']) ?></strong>
                <small><?= Html::encode($summary['percentageklinik']) ?>% of RM <?= Html::encode($summary['total_klinikpanel']) ?></small>
            </article>
            <article class="metric-card">
                <span><?= Html::encode($attendance['type']) ?></span>
                <strong><?= Html::encode($attendance['cardColor']) ?></strong>
                <small><?= Html::encode($attendance['mode']) ?></small>
            </article>
        </div>

        <div class="dashboard-columns page-section-body">
            <div class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2>MyTask</h2>
                    <span><?= count($pendingTasks) ?> active</span>
                </div>

                <?php if ($pendingTasks === []): ?>
                    <div class="dashboard-empty">No Pending Task</div>
                <?php else: ?>
                    <div class="task-grid">
                        <?php foreach ($pendingTasks as $task): ?>
                            <a class="task-card" href="<?= Html::encode(LegacyUrl::to((string) $task['url'])) ?>">
                                <span class="task-count"><?= Html::encode($task['count']) ?></span>
                                <strong><?= Html::encode($task['title']) ?></strong>
                                <?php if ($task['detail'] !== ''): ?>
                                    <small><?= Html::encode($task['detail']) ?></small>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h2>Shortcuts</h2>
                </div>
                <nav class="shortcut-list" aria-label="Dashboard shortcuts">
                    <?php foreach ($shortcuts as $shortcut): ?>
                        <a href="<?= Html::encode($shortcut['url']) ?>"<?= $shortcut['external'] ? ' target="_blank" rel="noopener"' : '' ?>>
                            <i data-lucide="<?= $shortcut['external'] ? 'external-link' : 'arrow-right' ?>"></i>
                            <span><?= Html::encode($shortcut['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>
        </div>
        </section>
    </main>
<?php endif; ?>
