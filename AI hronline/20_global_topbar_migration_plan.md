# Global Topbar Migration Plan

Date recorded: 2026-11-28

## Goal

Migrate the legacy HROnline topbar into the Yii3 shared layout so every migrated page has the same global account controls:

- notification dropdown;
- unread notification badge;
- small BKUMS profile picture;
- staff profile dropdown.

The topbar should live in the Yii3 shared main layout, not inside an individual module page such as `/cuti/individu/pilih`.

## Current Yii3 State

- Shared layout: `src/Web/Shared/Layout/Main/layout.php`
- Shared CSS: `assets/main/site.css`
- Current header contains:
  - mobile sidebar toggle;
  - hidden mobile brand;
  - simple `Dashboard` and `External` links.
- The layout receives global view parameters from `config/common/params.php`, but it does not yet receive current-user staff context.
- Yii3 already has:
  - `LegacySessionReader` for the Yii2-compatible session;
  - `EmployeeRepository` for staff identity/name lookup;
  - `BiodataProfileService` with the BKUMS photo URL formula;
  - migrated profile-picture workflows under `/gambar-profile/...`;
  - existing notification writes to `system_core.tbl_notifications` from some migrated flows.

## Legacy Behavior To Preserve

Legacy reference:

- `C:\Apache24\htdocs\staff\views\layouts\main.php`
- `C:\Apache24\htdocs\staff\views\layouts\adminlte4.php`
- `C:\Apache24\htdocs\staff\widgets\NotificationWidget.php`
- `C:\Apache24\htdocs\staff\widgets\views\NotificationWidget-bs5.php`
- `C:\Apache24\htdocs\staff\controllers\SiteController.php`
- `C:\Apache24\htdocs\staff\models\Notification.php`

Observed legacy rules:

- Notifications are stored in `system_core.tbl_notifications`.
- `status = 0` means unread.
- `status = 1` means read.
- `status = 2` means archived.
- Header dropdown shows the latest 5 unread rows for the current `icno`, ordered by `ntf_dt DESC`.
- Badge shows total unread count.
- Notification item links open a read/detail page and mark unread notifications as read.
- Bulk header actions support mark-as-read and archive.
- The legacy avatar URL is:

```text
https://bkums.ums.edu.my/public/picstf/{UPPERCASE_SHA1_ICNO}.jpeg
```

- Profile dropdown links:
  - My Profile: `/biodata/userview`
  - Diari: `/diari/index`, only for department diary heads in legacy
  - Change Password: existing Google Drive URL
  - Log Out: legacy `/site/logout` equivalent

## Proposed Yii3 Shape

### Data Layer

Add `src/Shared/TopbarService.php`.

Responsibilities:

- Resolve current user from the legacy session.
- Return a compact layout context:
  - `authenticated`
  - `icno`
  - `displayName`
  - `shortName`
  - `profileImage`
  - `profileUrl`
  - `changePasswordUrl`
  - `logoutUrl`
  - `showDiary`
  - `diaryUrl`
  - `notifications`
  - `notificationCount`
- Read latest unread notifications from `system_core.tbl_notifications`.
- Keep notification failures non-fatal; topbar must not break the page.

Use `EmployeeRepository` for name/display data. Use the same BKUMS photo URL formula already used by `BiodataProfileService`.

### Layout Injection

Add a Yii3 view injection class, for example:

```text
src/Web/Shared/Layout/Main/TopbarViewInjection.php
```

It should implement `Yiisoft\Yii\View\Renderer\LayoutParametersInjectionInterface`.

Responsibilities:

- Use `Yiisoft\RequestProvider\RequestProviderInterface` to access the current request.
- Use `TopbarService` to build the context for the layout.
- Inject one layout parameter, for example:

```php
'topbar' => [...]
```

Register it in:

```text
config/common/params.php
```

under `yiisoft/yii-view-renderer` `injections`.

This keeps every action clean; no need to pass topbar data from each controller.

### Markup

Update `src/Web/Shared/Layout/Main/layout.php`.

Target header structure:

- left:
  - mobile sidebar toggle;
  - mobile brand only when sidebar is collapsed;
- right:
  - notification icon button with unread badge;
  - notification dropdown panel;
  - profile button with circular avatar and optional staff name;
  - profile dropdown menu.

Use Lucide icons:

- `bell` for notifications;
- `user` for My Profile;
- `book-open` for Diari;
- `lock-keyhole` for Change Password;
- `log-out` for Log Out;
- `archive`, `mail-open`, `calendar-check` for notification actions where needed.

### Routes

Add local Yii3 routes for notification behavior:

```text
GET  /site/notifikasi
GET  /site/read_noti?id=...
POST /site/mark_read?id=...
POST /site/send_archive?id=...
```

Recommended Yii3 route names can stay close to legacy paths for compatibility, but implementation should use normal Yii3 actions:

```text
src/Web/Notification/ListAction.php
src/Web/Notification/ReadAction.php
src/Web/Notification/MarkReadAction.php
src/Web/Notification/ArchiveAction.php
```

First slice can render only the dropdown and preserve external/legacy links if the full notification center is not migrated yet. The action endpoints should still be local before enabling dropdown buttons.

### Styling

Add focused CSS to `assets/main/site.css`.

Suggested classes:

- `.header-actions`
- `.topbar-icon-button`
- `.topbar-badge`
- `.topbar-dropdown`
- `.topbar-notification-list`
- `.topbar-notification-item`
- `.topbar-profile`
- `.topbar-avatar`
- `.topbar-menu`

Responsive behavior:

- Desktop: icon/profile cluster on the far right.
- Tablet/mobile: keep bell and avatar visible; hide staff name below `860px`.
- Dropdowns must align to the viewport and stay usable on narrow screens.
- Header height should stay stable when badges appear/disappear.

## Implementation Slices

### TOPBAR-01 - Legacy Parity Audit

- [x] Confirm legacy topbar source files.
- [x] Confirm notification table and status values.
- [x] Confirm profile image URL formula.
- [x] Confirm legacy dropdown links.
- [x] Confirm Yii3 logout target. Added local `/site/logout` to clear the legacy `hrv4` session.
- [x] Confirm Diari access rule equivalent or defer the Diari link. Legacy currently returns `true`; Yii3 topbar preserves the visible link and points it to legacy Diari.

### TOPBAR-02 - Layout Context

- [x] Add `TopbarService`.
- [x] Add `TopbarViewInjection`.
- [x] Register the injection in `config/common/params.php`.
- [x] Ensure unauthenticated pages receive `authenticated = false` and render no private controls.
- [x] Ensure DB/notification lookup failures do not break layout rendering.

### TOPBAR-03 - Header Markup And CSS

- [x] Update shared `layout.php`.
- [x] Add header action/dropdown CSS.
- [x] Remove or reposition the simple `app-nav` links if they conflict visually.
- [x] Keep sidebar active-state behavior unchanged.
- [ ] Verify `/cuti/individu/pilih`, `/dashboard`, `/menu`, and `/biodata/userview` in an authenticated browser session.

### TOPBAR-04 - Notification Actions

- [x] Add notification list/read/update service methods.
- [x] Add local read page or safe redirect behavior for `/site/read_noti`.
- [x] Add POST mark-read and archive endpoints.
- [x] Add CSRF-safe dropdown action forms or `fetch` calls with CSRF headers.
- [x] Preserve old statuses: unread `0`, read `1`, archived `2`.

### TOPBAR-05 - Regression And Browser Checks

- [x] `php -l` on all touched PHP files.
- [x] Smoke unauthenticated pages: no private topbar controls and no layout errors.
- [ ] Smoke authenticated staff pages: avatar, name, notification count, and dropdown render.
- [x] Verify unread count equals `COUNT(*) WHERE icno = current AND status = 0` in implementation.
- [x] Verify latest 5 unread notifications appear in `ntf_dt DESC` in implementation.
- [x] Verify read/archive actions update only current user's notifications in implementation.
- [ ] Browser check desktop and mobile widths for no overlap with sidebar toggle or content.

## Execution Notes

- Implemented `TopbarService`, `TopbarViewInjection`, local notification service/actions/views, and local logout.
- Updated the shared main layout and CSS so the topbar appears globally for authenticated sessions.
- Added local routes for `/site/notifikasi`, `/site/read_noti`, `/site/mark_read`, `/site/send_archive`, and `/site/logout`.
- Ran `php -l` on touched PHP files successfully.
- Ran `composer test`; application booted with the new layout, but the suite still has existing homepage/404 expectation failures unrelated to this topbar work.
- Ran local HTTP smoke on `127.0.0.1:8099`: `/` returned 200, notification routes redirected unauthenticated, and `/site/logout` returned a clearing cookie.
- Authenticated visual/browser verification remains pending because no browser screenshot tool or staff session was available in this run.

## Acceptance Criteria

- The topbar is global across migrated Yii3 pages using the main layout.
- Notification badge and dropdown match legacy unread behavior.
- Profile avatar uses the BKUMS staff photo URL.
- Profile dropdown includes My Profile, Change Password, Log Out, and Diari only when access is confirmed.
- The header remains stable and usable on mobile.
- Missing session, missing staff row, missing photo, or notification DB errors do not crash the page.

## Deferred Decisions

- Whether `/site/notifikasi` should be fully migrated now or initially link to a compatibility page.
- Whether notification bulk actions should be implemented as no-JS forms or JavaScript `fetch`.
- Whether to migrate the conditional Diari access check immediately or hide the link until the Diari module is migrated.
- Whether the simple `Dashboard`/`External` header links should stay in the header or move fully into the sidebar/menu.
