# Notification Migration Plan

Date recorded: 2026-11-28

## Goal

Complete the Yii3 notification migration so the global topbar, notification center, archive, search, read flow, and migrated module notification writes all use the local Yii3 application instead of relying on legacy `/staff/web/site/...` pages.

This plan continues the topbar work from `AI hronline/20_global_topbar_migration_plan.md`.

## Scope

There are two separate migration tracks:

- Staff-facing notification center:
  - inbox / unread listing;
  - read detail page;
  - search across unread/read notifications;
  - archived listing;
  - archived detail page;
  - mark-as-read and archive actions;
  - global topbar dropdown integration.
- Notification producer API:
  - a shared Yii3 service for modules to create rows in `system_core.tbl_notifications`;
  - consistent title/content/date/status handling;
  - safe post-commit usage from migrated modules.

Out of scope for the first pass:

- external push/mobile notification transports;
- email/SMS notification rules;
- rewriting every legacy module that still creates notifications in Yii2;
- changing the existing notification table schema.

## Legacy Sources

Primary legacy files:

- `C:\Apache24\htdocs\staff\models\Notification.php`
- `C:\Apache24\htdocs\staff\models\NotificationSearch.php`
- `C:\Apache24\htdocs\staff\controllers\SiteController.php`
- `C:\Apache24\htdocs\staff\widgets\NotificationWidget.php`
- `C:\Apache24\htdocs\staff\widgets\views\NotificationWidget.php`
- `C:\Apache24\htdocs\staff\widgets\views\NotificationWidget-bs5.php`
- `C:\Apache24\htdocs\staff\views\site\notifikasi.php`
- `C:\Apache24\htdocs\staff\views\site\notifikasi_left.php`
- `C:\Apache24\htdocs\staff\views\site\notifikasi_search.php`
- `C:\Apache24\htdocs\staff\views\site\archive.php`
- `C:\Apache24\htdocs\staff\views\site\read_noti.php`
- `C:\Apache24\htdocs\staff\views\site\read-arc.php`

Legacy table:

```text
system_core.tbl_notifications
```

Legacy columns used:

- `id`
- `icno`
- `title`
- `content`
- `ntf_dt`
- `status`

Legacy status values:

- `0` = unread
- `1` = read
- `2` = archived

## Current Yii3 State

Already implemented:

- `src/Shared/TopbarService.php`
  - unread count;
  - latest 5 unread records;
  - dropdown links to `/site/read_noti?id=...`;
  - non-fatal notification lookup failures.
- `src/Shared/NotificationService.php`
  - list current user's status `0` and `1` notifications;
  - read owned notification by ID;
  - mark unread notification as read when opened;
  - mark selected owned notifications as read;
  - archive selected owned notifications.
- `src/Web/Notification/ListAction.php`
- `src/Web/Notification/ReadAction.php`
- `src/Web/Notification/MarkReadAction.php`
- `src/Web/Notification/ArchiveAction.php`
- `src/Web/Notification/list.php`
- `src/Web/Notification/detail.php`
- Routes:
  - `GET /site/notifikasi`
  - `GET /site/read_noti`
  - `POST /site/mark_read`
  - `POST /site/send_archive`

Current gap:

- Yii3 has a usable notification list, but it is not yet full legacy parity.
- The legacy `/site/notifikasi` UX is a split inbox: unread list on the left and selected message body on the right.
- Yii3 does not yet have `/site/searching`, `/site/archive`, or `/site/read-arc`.
- Yii3 does not yet have a shared notification creation method for migrated modules.

## Legacy Behavior To Preserve

### Topbar

- Show unread count with `COUNT(*) WHERE icno = current user AND status = 0`.
- Show latest 5 unread rows ordered by `ntf_dt DESC`.
- Open detail route when clicking a notification.
- Support dropdown mark-read and archive actions.

### Inbox

Legacy route:

```text
GET /site/notifikasi
```

Behavior:

- lists current user's unread notifications only: `status IN (0)`;
- page size is 5;
- sorted by `ntf_dt DESC`;
- item preview shows title, date, and first 50 content characters;
- selected/read message opens via `noti-read?id=...` or `read_noti?id=...`;
- archive link points to `/site/archive`;
- search link points to `/site/searching`.

Yii3 target:

- Keep `/site/notifikasi` as the canonical migrated route.
- Use Yii3 layout and current design system.
- Either preserve split inbox UX or use a clearer responsive two-pane layout:
  - left/list area: unread notifications;
  - right/detail area: selected notification content;
  - on mobile, stack list above detail.

### Search

Legacy route:

```text
GET /site/searching
```

Behavior:

- searches current user's status `0` and `1` notifications;
- filters:
  - `title`;
  - `status`;
  - legacy model also allows `id`, `icno`, `content`, and `ntf_dt`, but the visible form mainly exposes `title` and `status`;
- page size is 5;
- sorted by `ntf_dt DESC`;
- unread rows are visually highlighted.

Yii3 target:

- Add `GET /site/searching`.
- Support query parameters:
  - `title`;
  - `status`;
  - optionally `content`;
  - optionally `date`.
- Do not allow searching another user's notifications by `icno`.

### Archive

Legacy route:

```text
GET /site/archive
```

Behavior:

- lists current user's archived notifications: `status IN (2)`;
- filters by title;
- page size is 5;
- sorted by `ntf_dt DESC`;
- archived message opens via `/site/read-arc?id=...`.

Yii3 target:

- Add `GET /site/archive`.
- Add `GET /site/read-arc`.
- Keep ownership check: current user can only read their own archived rows.

### Read Detail

Legacy routes:

```text
GET /site/read_noti?id=...
GET /site/read-arc?id=...
GET /site/noti-read?id=...
```

Behavior:

- finds the notification by `id` and current user's `icno`;
- if status is `0`, marks it as `1`;
- renders title, formatted date, and HTML content.

Yii3 target:

- Keep `/site/read_noti?id=...` as the primary route.
- Add compatibility alias `/site/noti-read?id=...`.
- Add `/site/read-arc?id=...` for archive detail.
- Preserve HTML content safely:
  - legacy notifications often include HTML links/buttons in `content`;
  - Yii3 must allow a controlled HTML subset or sanitize content before rendering;
  - do not blindly escape into unreadable markup if the notification contains valid legacy action links.

### Bulk Actions

Legacy routes:

```text
POST/GET /site/mark_read?id=...
POST/GET /site/send_archive?id=...
```

Legacy caveat:

- some legacy actions update by `id` without ownership checks.

Yii3 target:

- Keep POST-only forms for CSRF safety.
- Keep ownership condition in every update:

```sql
WHERE icno = :currentIcno AND id IN (...)
```

- Support selected IDs from `ids[]`.
- Optionally support single `id` query/body value for legacy compatibility, but still require ownership.

## Target Yii3 Design

### Service Layer

Extend `src/Shared/NotificationService.php`.

Add query methods:

- `unreadInbox(string $icno, int $page, int $selectedId = null): array`
- `searchForStaff(string $icno, NotificationCriteria $criteria): array`
- `archiveForStaff(string $icno, NotificationCriteria $criteria): array`
- `readArchiveForStaff(string $icno, int $id): ?array`
- `create(string $icno, string $title, string $content, ?DateTimeInterface $date = null): int`

Add small criteria value object if it keeps method signatures clean:

```text
src/Shared/NotificationCriteria.php
```

Suggested fields:

- `page`
- `title`
- `status`
- `content`
- `date`
- `pageSize`

Default page size:

- Use 5 for legacy-parity pages.
- Keep topbar latest limit at 5.
- Current Yii3 list page size 15 can be changed to 5 during parity work, or retained only for the newer table view if we intentionally choose modern behavior.

### Actions

Add or update:

- `src/Web/Notification/InboxAction.php`
  - canonical handler for `/site/notifikasi`;
  - can replace current `ListAction` or delegate to it.
- `src/Web/Notification/SearchAction.php`
  - handler for `/site/searching`.
- `src/Web/Notification/ArchiveListAction.php`
  - handler for `/site/archive`.
- `src/Web/Notification/ArchiveReadAction.php`
  - handler for `/site/read-arc`.
- `src/Web/Notification/ReadAction.php`
  - continue handling `/site/read_noti`;
  - also route `/site/noti-read` here as a compatibility alias.
- `src/Web/Notification/MarkReadAction.php`
  - keep.
- `src/Web/Notification/ArchiveAction.php`
  - keep.

### Routes

Add:

```text
GET  /site/searching
GET  /site/archive
GET  /site/read-arc
GET  /site/noti-read
```

Keep existing:

```text
GET  /site/notifikasi
GET  /site/read_noti
POST /site/mark_read
POST /site/send_archive
```

### Views

Suggested view files:

- `src/Web/Notification/inbox.php`
- `src/Web/Notification/search.php`
- `src/Web/Notification/archive.php`
- `src/Web/Notification/detail.php`

The detail template can be shared between inbox and archive by passing:

- notification row;
- back URL;
- mode label.

UX should be simple and operational:

- compact list rows;
- title, date, status, excerpt;
- clear archive/search links;
- stable pagination;
- no nested cards;
- mobile-safe list/detail stacking.

### Rendering Legacy HTML Content

Many legacy notifications store HTML in `content`, for example `Html::a(...)` buttons.

Migration rule:

- Keep title escaped.
- Render content through an allowlist sanitizer if available in the project.
- If no sanitizer is available, initially escape content in search/list excerpts and only render controlled HTML in detail after reviewing dependencies.

Implementation options:

- Prefer an existing Yii3 HTML sanitizer package if installed.
- Otherwise add a tiny local allowlist helper for:
  - `a[href,class,title,target,rel]`
  - `br`
  - `strong`
  - `b`
  - `i`
  - `small`
  - `span[class]`
  - `p`

Do not allow:

- `script`
- inline event handlers such as `onclick`
- arbitrary inline `style`
- `javascript:` URLs.

## Implementation Slices

### NOTIF-01 - Audit And Route Parity

- [x] Identify legacy model/search/controller/views.
- [x] Confirm table and status values.
- [x] Confirm current Yii3 topbar/list/read/update state.
- [x] Add missing routes:
  - `/site/searching`
  - `/site/archive`
  - `/site/read-arc`
  - `/site/noti-read`
- [x] Decide whether `ListAction` is renamed to `InboxAction` or kept with expanded behavior. Kept `ListAction` as the compatibility route handler and expanded it to render the Yii3 inbox.

### NOTIF-02 - Query And Criteria Layer

- [x] Extend `NotificationService` with reusable criteria-based querying.
- [x] Support status filters:
  - inbox: `[0]`;
  - search: `[0, 1]`;
  - archive: `[2]`.
- [x] Support title/status/content/date filters.
- [x] Keep every query scoped by current user's `icno`.
- [x] Keep sort default as `ntf_dt DESC`.
- [x] Add page metadata compatible with the existing Yii3 templates.

### NOTIF-03 - Inbox Parity

- [x] Update `/site/notifikasi` to match legacy intent:
  - unread list;
  - selected notification detail area;
  - archive link;
  - search link;
  - page size 5 or intentional modern alternative.
- [x] Add `/site/noti-read?id=...` compatibility route.
- [x] Ensure opening an unread notification marks it read in implementation.
- [x] Ensure topbar count drops after a read action in implementation.

### NOTIF-04 - Search Page

- [x] Add `SearchAction`.
- [x] Add `search.php`.
- [x] Implement title filter.
- [x] Implement status filter for unread/read.
- [x] Add pagination.
- [x] Highlight unread rows.
- [x] Link result rows to `/site/read_noti?id=...`.

### NOTIF-05 - Archive Page

- [x] Add `ArchiveListAction`.
- [x] Add `ArchiveReadAction`.
- [x] Add `archive.php`.
- [x] Route `/site/archive`.
- [x] Route `/site/read-arc`.
- [x] Implement title filter.
- [x] Link archive rows to `/site/read-arc?id=...`.
- [x] Keep archived rows out of unread/read searches unless explicitly requested later.

### NOTIF-06 - Safe HTML Content

- [x] Inventory current notification `content` examples from representative legacy modules.
- [x] Choose sanitizer approach. Added a local allowlist sanitizer because no sanitizer dependency is installed.
- [x] Render excerpts as plain text.
- [x] Render detail body with safe allowed HTML.
- [x] Make legacy internal links resolve correctly:
  - migrated links should stay local;
  - unmigrated module links may temporarily point to legacy compatibility routes.

### NOTIF-07 - Notification Producer API

- [x] Add `NotificationService::create(...)`.
- [x] Add optional helper for creating many notifications:
  - `createMany(array $messages): int`.
- [x] Standardize defaults:
  - `ntf_dt = current timestamp`;
  - `status = 0`;
  - trimmed `icno`, `title`, `content`;
  - title max 100;
  - content max 1000.
- [x] Update migrated modules that already write notifications to use the shared service where practical:
  - attendance;
  - cuti;
  - GCR;
  - lantikan, if/when its notification writes are enabled.
- [x] Keep notification-write failures non-fatal when the business workflow has already succeeded, matching current migrated module behavior.

### NOTIF-08 - Tests And Verification

- [x] Unit test notification criteria parsing and safe HTML sanitizer behavior.
- [ ] DB-backed integration test `NotificationService` with seeded notification rows:
  - list unread/read;
  - search filters;
  - archive filters;
  - ownership checks;
  - mark read;
  - archive;
  - create.
- [x] Route smoke tests:
  - unauthenticated routes redirect;
  - POST action routes are CSRF-protected.
- [ ] Authenticated route smoke tests:
  - list/search/archive render;
  - read/archive detail render.
- [ ] Manual authenticated browser checks:
  - topbar unread badge;
  - dropdown latest 5;
  - inbox read flow;
  - search filters;
  - archive flow;
  - desktop/mobile layout.
- [x] Confirm no update route can change another staff member's notifications in implementation.

## Execution Notes

- Added local Yii3 route parity for `/site/searching`, `/site/archive`, `/site/read-arc`, and `/site/noti-read`.
- Refactored `NotificationService` around shared criteria-based, current-user-scoped queries for inbox/search/archive.
- Added Yii3 inbox, search, and archive templates under `src/Web/Notification`.
- Added local safe HTML rendering through `NotificationContentSanitizer`; excerpts use plain text.
- Added notification create/createMany APIs and moved migrated attendance/cuti/GCR notification writes onto the shared service.
- Added focused unit coverage for criteria parsing and sanitizer behavior.
- Verified unauthenticated route smoke with local dev server on `127.0.0.1:8099`; authenticated visual/data checks remain pending without a staff session.

## Acceptance Criteria

- `/site/notifikasi` works fully in Yii3 and no longer needs the legacy notification center.
- `/site/searching` works in Yii3 for current user's unread/read notifications.
- `/site/archive` and `/site/read-arc` work in Yii3 for current user's archived notifications.
- `/site/read_noti` and `/site/noti-read` both safely read current user's notifications and mark unread rows as read.
- Mark-read and archive actions are CSRF-safe and ownership-scoped.
- Topbar count/dropdown stays consistent after read/archive actions.
- Notification content is displayed safely while preserving useful legacy links.
- Migrated modules have a shared service for creating notification rows.

## Risks

- Legacy `content` may contain raw HTML. Rendering it exactly can create XSS risk; escaping it completely may make old action buttons unusable.
- Some legacy notification links target modules that are not migrated yet.
- Many legacy modules create notifications directly; full producer migration should happen module-by-module, not as one massive rewrite.
- The current automated test suite still has old homepage/404 expectations, so notification verification may need targeted tests first.

## Recommended Execution Order

1. Add route parity and archive/search pages.
2. Refactor `NotificationService` into criteria-based queries.
3. Update inbox UI to closer legacy parity.
4. Add safe HTML handling.
5. Add notification create API.
6. Move migrated module notification writes onto the shared create API.
7. Add focused tests and authenticated browser verification.
