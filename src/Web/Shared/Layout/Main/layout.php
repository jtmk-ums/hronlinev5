<?php

declare(strict_types=1);

use App\Shared\LegacyUrl;
use App\Web\Shared\Layout\Main\MainAsset;
use Yiisoft\Html\Html;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Csrf $csrf
 * @var string $content
 * @var array<string, mixed> $topbar
 * @var Yiisoft\View\WebView $this
 */

$assetManager->register(MainAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$topbar = is_array($topbar ?? null) ? $topbar : ['authenticated' => false, 'notifications' => [], 'notificationCount' => 0];
$topbarAuthenticated = (bool) ($topbar['authenticated'] ?? false);
$topbarNotifications = is_array($topbar['notifications'] ?? null) ? $topbar['notifications'] : [];
$topbarNotificationCount = (int) ($topbar['notificationCount'] ?? 0);
$topbarCurrentUrl = (string) ($topbar['currentUrl'] ?? '/dashboard');

$this->beginPage()
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <title><?= Html::encode($this->getTitle()) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="app-toast-region" data-app-toast-region aria-live="polite" aria-atomic="true"></div>

<div class="app-shell">
    <header class="app-header">
        <button class="sidebar-toggle" type="button" aria-label="Toggle navigation" data-sidebar-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a class="brand header-brand" href="/dashboard">
            <img class="brand-logo" src="/images/hronline_logo.png" alt="HROnline">
            <span>
                <strong>HROnline</strong>
                <small>Yii3 migration</small>
            </span>
        </a>
        <nav class="app-nav" aria-label="Primary">
            <a href="/dashboard">Dashboard</a>
            <a href="/external-login">External</a>
        </nav>
        <?php if ($topbarAuthenticated): ?>
            <div class="header-actions">
                <details class="topbar-dropdown topbar-notifications">
                    <summary class="topbar-icon-button" aria-label="Notifications">
                        <i data-lucide="bell"></i>
                        <?php if ($topbarNotificationCount > 0): ?>
                            <span class="topbar-badge"><?= Html::encode((string) $topbarNotificationCount) ?></span>
                        <?php endif; ?>
                    </summary>
                    <div class="topbar-menu topbar-notification-menu">
                        <form method="post" action="/site/mark_read">
                            <?= $csrf->hiddenInput() ?>
                            <input type="hidden" name="returnUrl" value="<?= Html::encode($topbarCurrentUrl) ?>">
                            <div class="topbar-menu-header">
                                <strong>Notifikasi</strong>
                                <span><?= Html::encode((string) $topbarNotificationCount) ?> belum dibaca</span>
                            </div>

                            <?php if ($topbarNotifications === []): ?>
                                <div class="topbar-empty">Tiada notifikasi baharu.</div>
                            <?php else: ?>
                                <div class="topbar-notification-list">
                                    <?php foreach ($topbarNotifications as $notification): ?>
                                        <?php $notificationId = (string) ($notification['id'] ?? ''); ?>
                                        <div class="topbar-notification-item">
                                            <input type="checkbox" name="ids[]" value="<?= Html::encode($notificationId) ?>">
                                            <span class="topbar-notification-icon"><i data-lucide="calendar-check"></i></span>
                                            <span>
                                                <a href="<?= Html::encode((string) ($notification['url'] ?? '#')) ?>">
                                                    <?= Html::encode((string) ($notification['shortTitle'] ?? $notification['title'] ?? 'Notification')) ?>
                                                </a>
                                                <small><?= Html::encode((string) ($notification['date'] ?? '')) ?></small>
                                                <em><?= Html::encode((string) ($notification['excerpt'] ?? '-')) ?></em>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="topbar-bulk-actions">
                                    <button type="submit" class="topbar-menu-action">
                                        <i data-lucide="mail-open"></i>
                                        <span>Dibaca</span>
                                    </button>
                                    <button type="submit" class="topbar-menu-action" formaction="/site/send_archive">
                                        <i data-lucide="archive"></i>
                                        <span>Arkib</span>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <a class="topbar-menu-footer" href="<?= Html::encode((string) ($topbar['notificationsUrl'] ?? '/site/notifikasi')) ?>">
                                <strong>Pusat Notifikasi</strong>
                                <i data-lucide="chevron-right"></i>
                            </a>
                        </form>
                    </div>
                </details>

                <details class="topbar-dropdown topbar-profile-menu">
                    <summary class="topbar-profile" aria-label="Profile menu">
                        <img class="topbar-avatar" src="<?= Html::encode((string) ($topbar['profileImage'] ?? '')) ?>" alt="">
                        <span><?= Html::encode((string) ($topbar['shortName'] ?? 'Staff')) ?></span>
                        <i data-lucide="chevron-down"></i>
                    </summary>
                    <div class="topbar-menu">
                        <div class="topbar-profile-card">
                            <img class="topbar-avatar topbar-avatar-large" src="<?= Html::encode((string) ($topbar['profileImage'] ?? '')) ?>" alt="">
                            <span><?= Html::encode((string) ($topbar['displayName'] ?? 'Staff')) ?></span>
                        </div>
                        <a class="topbar-menu-link" href="<?= Html::encode((string) ($topbar['profileUrl'] ?? '/biodata/userview')) ?>">
                            <i data-lucide="user"></i>
                            <span>My Profile</span>
                        </a>
                        <?php if (!empty($topbar['showDiary'])): ?>
                            <a class="topbar-menu-link" href="<?= Html::encode((string) ($topbar['diaryUrl'] ?? '#')) ?>">
                                <i data-lucide="book-open"></i>
                                <span>Diari</span>
                            </a>
                        <?php endif; ?>
                        <a class="topbar-menu-link" href="<?= Html::encode((string) ($topbar['changePasswordUrl'] ?? '#')) ?>" target="_blank" rel="noopener">
                            <i data-lucide="lock-keyhole"></i>
                            <span>Change Password</span>
                        </a>
                        <a class="topbar-menu-link topbar-menu-link-danger" href="<?= Html::encode((string) ($topbar['logoutUrl'] ?? '/site/logout')) ?>">
                            <i data-lucide="log-out"></i>
                            <span>Log Out</span>
                        </a>
                    </div>
                </details>
            </div>
        <?php endif; ?>
    </header>

    <aside class="app-sidebar" data-sidebar>
        <a class="sidebar-brand" href="/dashboard">
            <img class="sidebar-brand-logo" src="/images/hronline_logo.png" alt="HROnline">
            <span><b>HR</b>Online</span>
        </a>
        <nav class="sidebar-nav" aria-label="Workspace">
            <a href="/dashboard">
                <span class="nav-icon">MW</span>
                <span>MyWorkspace</span>
            </a>
            <a href="/menu">
                <span class="nav-icon">MN</span>
                <span>Menu</span>
            </a>
            <a href="/kehadiran/index">
                <span class="nav-icon">ST</span>
                <span>STARS</span>
            </a>
            <a href="<?= Html::encode(LegacyUrl::to('keselamatan/index')) ?>">
                <span class="nav-icon">SK</span>
                <span>SKB</span>
            </a>
            <a href="/biodata/userview">
                <span class="nav-icon">PF</span>
                <span>Profil Staf</span>
            </a>
            <a href="https://docs.ums.edu.my/" target="_blank" rel="noopener">
                <span class="nav-icon">UD</span>
                <span>UMS Docs</span>
            </a>
            <a href="<?= Html::encode(LegacyUrl::to('support-system/index')) ?>">
                <span class="nav-icon">SS</span>
                <span>Support System</span>
            </a>
        </nav>
    </aside>

    <div class="app-body">
        <main class="content">
            <div class="content_i">
                <?= $content ?>
            </div>
        </main>
    </div>

    <footer class="footer">
        <span>&copy; <?= date('Y') ?> <?= Html::encode($applicationParams->name) ?></span>
    </footer>
</div>

<script>
document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-open');
});

const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
document.querySelectorAll('.sidebar-nav a').forEach((link) => {
    const href = link.getAttribute('href') || '';
    if (href.startsWith('http')) {
        return;
    }

    const linkPath = new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    const isDashboardAlias = currentPath === '/dashboard/index' && linkPath === '/dashboard';
    const isBiodataAlias = currentPath.startsWith('/biodata/') && linkPath === '/biodata/userview';
    const isAttendanceAlias = currentPath.startsWith('/kehadiran/') && linkPath === '/kehadiran/index';
    if (linkPath === currentPath || isDashboardAlias || isBiodataAlias || isAttendanceAlias) {
        link.classList.add('is-active');
    }
});
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
