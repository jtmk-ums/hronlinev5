# Rekod Perkhidmatan Kakitangan Migration Plan

Date recorded: 2026-11-28

## Menu Target

Old Yii2 dynamic menu path:

- Category: `Pengurusan Sumber Manusia`
- Parent menu: `Sumber Manusia`, `system_core.tbl_menu_side.id = 8`
- Child menu: `Rekod Perkhidmatan Kakitangan`, `system_core.tbl_menu_side.id = 109`
- Old URL: `perkhidmatan/index`

Current Yii3 status:

- `MenuService::LOCAL_ROUTE_MAP` does not map `perkhidmatan/index`, so the menu currently remains a Yii2 legacy handoff.
- No local `/perkhidmatan/*` routes exist yet.
- Yii3 already has a strong related pattern in `BiodataProfileService`, `/biodata/index`, and `/biodata/adminview`.

## Old Yii2 Source Inventory

Primary old module:

- Controller: `C:\Apache24\htdocs\staff\controllers\PerkhidmatanController.php`
- Views:
  - `C:\Apache24\htdocs\staff\views\perkhidmatan\index.php`
  - `C:\Apache24\htdocs\staff\views\perkhidmatan\_search.php`
  - `C:\Apache24\htdocs\staff\views\perkhidmatan\view.php`
  - `C:\Apache24\htdocs\staff\views\perkhidmatan\viewuser.php`
  - `C:\Apache24\htdocs\staff\views\perkhidmatan\treestat.php`

Related Yii2 service-record submodules linked from the detail screen:

| Label | Old route |
| --- | --- |
| Status Lantikan | `status-lantikan/view`, `status-lantikan/viewuser` |
| Status Sandangan | `status-sandangan/view`, `status-sandangan/viewuser` |
| Status Perkhidmatan | `status-perkhidmatan/view`, `status-perkhidmatan/viewuser` |
| Pihak Berkuasa Melantik | `pihak-berkuasa/view`, `pihak-berkuasa/viewuser` |
| Beban Perkhidmatan | `beban-perkhidmatan/view`, `beban-perkhidmatan/viewuser` |
| Waktu Bekerja | `waktu-bekerja/view`, `waktu-bekerja/viewuser` |
| Status Pengesahan | `status-pengesahan/view`, `status-pengesahan/viewuser` |
| Tempoh Percubaan | `tempoh-percubaan/view`, `tempoh-percubaan/viewuser` |
| Jenis Gaji | `jenis-gaji/view`, `jenis-gaji/viewuser` |
| Skim/Jenis Perkhidmatan | `jenis-perkhidmatan/view`, `jenis-perkhidmatan/viewuser` |
| Pergerakan Gaji | `pergerakan-gaji/view`, `pergerakan-gaji/viewuser` |
| Fail Perkhidmatan | `fail-perkhidmatan/view`, `fail-perkhidmatan/viewuser` |
| Status Pencen | `status-pencen/view`, `status-pencen/viewuser` |
| Mata Gaji | `mata-gaji/view`, `mata-gaji/viewuser` |
| Pihak Berkuasa Pencen | `pihak-berkuasa-pencen/view`, `pihak-berkuasa-pencen/viewuser` |
| Umur Bersara | `umur-bersara/view`, `umur-bersara/viewuser` |
| Sejarah Penempatan | `sejarah-penempatan/view`, `sejarah-penempatan/viewuser` |
| Perubahan Data | `perubahan-data/view`, `perubahan-data/viewuser` |

## Old Behavior Summary

`PerkhidmatanController::actionIndex()`:

- Requires Yii2 authenticated user.
- Manually allows only `system_core.tbl_user_access` rows where `access IN (1,2,3)`.
- Uses `TblprcobiodataSearch::carian()` for staff search.
- Search filters:
  - kategori jawatan
  - program pengajaran
  - kod program
  - kampus
  - JFPIU
  - status lantikan
  - pendidikan tertinggi
  - status pekerja
  - jenis carian: nama, IC, UMSPER
  - carian data text
- List columns:
  - IC/KP, UMSPER, nama, gred, email, JFPIU, pendidikan tertinggi, kampus, status, view action

`PerkhidmatanController::actionView($id)`:

- Shows admin service-profile detail for selected staff.
- Shows profile photo from BKUMS by `sha1(ICNO)`.
- Shows summary fields for employment/service status.
- Shows service-record section links.
- Shows latest 5 `hronline.updatestatus` rows for the selected staff.
- Loads `tree-stat` modal through AJAX.

`PerkhidmatanController::actionViewuser()`:

- Shows current logged-in user's service-profile summary.
- Provides a smaller self-service set of service-record links.
- Includes old HR contact text.

`PerkhidmatanController::actionTreeStat($id)`:

- Builds a timeline from:
  - `hronline.tblrscoapmtstatus`
  - `hronline.tblrscosandangan`
  - `hronline.tblrscoservstatus`
- Sorts by start date descending and renders a Gentelella timeline widget.

## Yii3 Target Shape

Add a dedicated module namespace instead of mixing this into Biodata:

```text
src/Web/Perkhidmatan/
src/Shared/Perkhidmatan/
```

Recommended first files:

```text
src/Shared/Perkhidmatan/PerkhidmatanAuditService.php
src/Shared/Perkhidmatan/PerkhidmatanAccessService.php
src/Shared/Perkhidmatan/PerkhidmatanProfileService.php
src/Shared/Perkhidmatan/PerkhidmatanReferenceService.php
src/Web/Perkhidmatan/IndexAction.php
src/Web/Perkhidmatan/ViewAction.php
src/Web/Perkhidmatan/SelfViewAction.php
src/Web/Perkhidmatan/RedirectViewAction.php
src/Web/Perkhidmatan/TimelineAction.php
src/Web/Perkhidmatan/index.php
src/Web/Perkhidmatan/view.php
src/Web/Perkhidmatan/viewuser.php
src/Web/Perkhidmatan/timeline.php
```

## Web Structure Demo

Recommended Yii3 web/action layer:

```text
src/Web/Perkhidmatan/
+-- IndexAction.php        # HR/admin staff search and result list
+-- ViewAction.php         # HR/admin selected staff service profile
+-- SelfViewAction.php     # Current user's own service profile
+-- RedirectViewAction.php # Compatibility route for old redirectview
+-- TimelineAction.php     # Timeline fragment/page for tree-stat
+-- index.php              # Search form and paginated staff table
+-- view.php               # Admin detail, service links, change history
+-- viewuser.php           # Staff self-service detail and service links
+-- timeline.php           # Appointment/sandangan/service status timeline
```

The action classes should stay thin:

```text
IndexAction
  - read legacy session
  - require HR/admin access
  - call PerkhidmatanProfileService::searchStaff($query)
  - render index.php

ViewAction
  - read legacy session
  - require HR/admin access
  - read id/icno query value
  - call profileForIcno($icno, 'admin')
  - call changeHistory($icno, $page)
  - render view.php

SelfViewAction
  - read legacy session
  - resolve current ICNO only
  - call profileForIcno($icno, 'user')
  - render viewuser.php

RedirectViewAction
  - read legacy session
  - redirect to /perkhidmatan/viewuser

TimelineAction
  - read legacy session
  - allow own ICNO or HR/admin access
  - call timeline($icno)
  - render timeline.php
```

Route registration target in `config/common/routes.php`:

```php
Route::get('/perkhidmatan/index')
    ->action(Web\Perkhidmatan\IndexAction::class)
    ->name('perkhidmatan-index'),
Route::get('/perkhidmatan/view')
    ->action(Web\Perkhidmatan\ViewAction::class)
    ->name('perkhidmatan-view'),
Route::get('/perkhidmatan/viewuser')
    ->action(Web\Perkhidmatan\SelfViewAction::class)
    ->name('perkhidmatan-viewuser'),
Route::get('/perkhidmatan/redirectview')
    ->action(Web\Perkhidmatan\RedirectViewAction::class)
    ->name('perkhidmatan-redirectview'),
Route::get('/perkhidmatan/tree-stat')
    ->action(Web\Perkhidmatan\TimelineAction::class)
    ->name('perkhidmatan-tree-stat'),
```

Template contracts:

`index.php` receives:

```text
$grid = [
    'filters' => [...],
    'options' => [...],
    'rows' => [
        [
            'icno' => '...',
            'staffNo' => '...',
            'name' => '...',
            'grade' => '...',
            'email' => '...',
            'department' => '...',
            'education' => '...',
            'campus' => '...',
            'status' => '...',
            'statusCode' => '...',
        ],
    ],
    'count' => 0,
    'total' => 0,
    'limit' => 15,
    'page' => 1,
    'pageCount' => 1,
    'offset' => 0,
]
```

`view.php` receives:

```text
$profile = [
    'icno' => '...',
    'displayName' => '...',
    'staffNo' => '...',
    'email' => '...',
    'profileImage' => '...',
    'fields' => [
        ['label' => 'Status Lantikan', 'value' => '...'],
    ],
    'sections' => [
        ['label' => 'Status Lantikan', 'url' => '...', 'legacy' => true],
    ],
]

$history = [
    'rows' => [
        [
            'section' => 'Status Perkhidmatan',
            'activity' => 'Kemaskini',
            'updatedAt' => '...',
            'updatedBy' => '...',
        ],
    ],
    'page' => 1,
    'pageCount' => 1,
    'total' => 0,
]
```

`viewuser.php` receives the same `$profile` shape, but `sections` only includes self-service links.

`timeline.php` receives:

```text
$timeline = [
    'staff' => ['icno' => '...', 'name' => '...'],
    'items' => [
        [
            'title' => 'Dilantik',
            'date' => '01 Januari 2020',
            'source' => 'Status Lantikan',
        ],
    ],
]
```

Route targets:

```text
GET /perkhidmatan/index
GET /perkhidmatan/view?id={icno}
GET /perkhidmatan/viewuser
GET /perkhidmatan/redirectview
GET /perkhidmatan/tree-stat?id={icno}
```

Menu mapping:

- Add `perkhidmatan/index` to `MenuService::LOCAL_ROUTE_MAP`.
- Keep submodule links as Yii2 handoffs in the first slice unless the specific submodule is migrated.
- Mark handoff links with `LegacyUrl::to(...)`, `external-link`, and `Yii2`.

## Full Migration Meaning

For this module, "fully migrated" means more than moving the landing page.

Done means:

- No Yii2 handoff remains for `Rekod Perkhidmatan Kakitangan` hub routes.
- Every service-record item linked from the hub has local Yii3 admin and self-service read pages where old Yii2 had them.
- Every item with old create/update/delete behavior has local Yii3 create/update/delete behavior.
- Every migrated write preserves the old audit side effects:
  - `hronline.updatestatus`
  - `hronline.tbl_stat`, for models that used it in Yii2
  - old activity mapping: `0` create, `1` update, `2` delete
  - old table names in `COTableName`
  - old `COUpdateSQL` serialized changed-field payload shape
- Every direct write uses POST plus CSRF, explicit access checks, and item-specific service code.
- Final forms use direct form markup, not metadata field loops.
- Submodules remain explicit action/view/service pairs, not one slug-based CRUD engine.
- Special side effects are migrated deliberately instead of hidden inside a generic helper.

`Perubahan Data` is the local audit/history viewer for this area. It should not become an arbitrary audit-table editor in Yii3 because that would weaken the audit trail. The important full-migration requirement is that all migrated CRUD actions create the same log rows that Yii2 created, and that the history screens display those rows locally.

## Full Item Migration Matrix

| Item | Old controller | Old model/table | Target Yii3 web folder | Audit/log scope | Notes |
| --- | --- | --- | --- | --- | --- |
| Hub Rekod Perkhidmatan | `PerkhidmatanController` | staff profile/search tables | `src/Web/Perkhidmatan/` | Read `updatestatus` latest history | Search, admin profile, self profile, timeline. |
| Status Lantikan | `StatusLantikanController` | `Tblrscoapmtstatus` / `hronline.tblrscoapmtstatus` | `src/Web/Perkhidmatan/Sections/StatusLantikan/` | `updatestatus` + `tbl_stat` | Appointment status CRUD and self view. |
| Status Sandangan | `StatusSandanganController` | `Tblrscosandangan` / `hronline.tblrscosandangan` | `src/Web/Perkhidmatan/Sections/StatusSandangan/` | `updatestatus` + `tbl_stat` | High risk because Yii2 create flow can also update salary movement, staff identity, PKS, and file number records. |
| Status Perkhidmatan | `StatusPerkhidmatanController` | `Tblrscoservstatus` / `hronline.tblrscoservstatus` | `src/Web/Perkhidmatan/Sections/StatusPerkhidmatan/` | `updatestatus` + `tbl_stat` | Preserve service status/detail references. |
| Pihak Berkuasa Melantik | `PihakBerkuasaController` | `Tblrscoaptathy` / `hronline.tblrscoaptathy` | `src/Web/Perkhidmatan/Sections/PihakBerkuasa/` | `updatestatus` + `tbl_stat` | Old route name is `pihak-berkuasa`. |
| Beban Perkhidmatan | `BebanPerkhidmatanController` | `Tblrscoservload` / `hronline.tblrscoservload` | `src/Web/Perkhidmatan/Sections/BebanPerkhidmatan/` | `updatestatus` + `tbl_stat` | Preserve service loading reference labels. |
| Waktu Bekerja | `WaktuBekerjaController` | `TblWp` / `attendance.tbl_wp` | `src/Web/Perkhidmatan/Sections/WaktuBekerja/` | No old HR `updatestatus` hook found | Keep attendance-table behavior separate. Do not invent HR audit rows unless product owner confirms. |
| Status Pengesahan | `StatusPengesahanController` | `Tblrscoconfirmstatus` / `hronline.tblrscoconfirmstatus` | `src/Web/Perkhidmatan/Sections/StatusPengesahan/` | `updatestatus` + `tbl_stat` | Preserve confirmation status reference. |
| Tempoh Percubaan | `TempohPercubaanController` | `Tblrscoprobtnperiod` / `hronline.tblrscoprobtnperiod` | `src/Web/Perkhidmatan/Sections/TempohPercubaan/` | `updatestatus` + `tbl_stat` | Preserve probation start/end/min period rules. |
| Jenis Gaji | `JenisGajiController` | `Tblrscosaltype` / `hronline.tblrscosaltype` | `src/Web/Perkhidmatan/Sections/JenisGaji/` | `updatestatus` + `tbl_stat` | Preserve salary type and salary status references. |
| Skim/Jenis Perkhidmatan | `JenisPerkhidmatanController` | `Tblrscoservtype` / `hronline.tblrscoservtype` | `src/Web/Perkhidmatan/Sections/JenisPerkhidmatan/` | `updatestatus` + `tbl_stat` | Old label mixes skim and service type. Keep route readable. |
| Pergerakan Gaji | `PergerakanGajiController` | `Tblrscosalmovemth` / `hronline.tblrscosalmovemth` | `src/Web/Perkhidmatan/Sections/PergerakanGaji/` | `updatestatus` + `tbl_stat` | Preserve movement month and movement type references. |
| Fail Perkhidmatan | `FailPerkhidmatanController` | `Tblrscofileno` / `hronline.tblrscofileno` | `src/Web/Perkhidmatan/Sections/FailPerkhidmatan/` | `updatestatus` + `tbl_stat` | Includes basic file number CRUD plus BRP/PDF extras as a later slice. |
| Status Pencen | `StatusPencenController` | `Tblrscopsnstatus` / `hronline.tblrscopsnstatus` | `src/Web/Perkhidmatan/Sections/StatusPencen/` | `updatestatus` + `tbl_stat` | Preserve pension status and KWAP references. |
| Mata Gaji | `MataGajiController` | `Tblrscopersalpoint` / `hronline.tblrscopersalpoint` | `src/Web/Perkhidmatan/Sections/MataGaji/` | `updatestatus` + `tbl_stat` | Yii2 delete appears to call the wrong finder; Yii3 should fix this safely. |
| Pihak Berkuasa Pencen | `PihakBerkuasaPencenController` | `Tblrscopsnathy` / `hronline.tblrscopsnathy` | `src/Web/Perkhidmatan/Sections/PihakBerkuasaPencen/` | `updatestatus` + `tbl_stat` | Preserve pension authority reference. |
| Umur Bersara | `UmurBersaraController` | `Tblrscoretireage` / `hronline.tblrscoretireage` | `src/Web/Perkhidmatan/Sections/UmurBersara/` | `updatestatus` + `tbl_stat` | Use `Tblrscoretireage` behavior, not the older ambiguous retire-age model. |
| Sejarah Penempatan | `SejarahPenempatanController` | `TblPenempatan` / `hronline.tblpenempatan` | `src/Web/Perkhidmatan/Sections/SejarahPenempatan/` | `updatestatus` + `tbl_stat` | High risk because it has extra access rules, side effects into `TblPapSenaraiStaf`, recommendation data, and PDF surat flow. |
| Perubahan Data | `PerubahanDataController` | `Updatestatus` / `hronline.updatestatus` | `src/Web/Perkhidmatan/Sections/PerubahanData/` | Read audit log | Local read-only audit/history pages for admin and self-service. |

## Submodule Web Structure Demo

Each migrated item should follow this shape, with the folder name changed per item:

```text
src/Web/Perkhidmatan/Sections/StatusLantikan/
+-- ViewAction.php       # admin/self list route for the item
+-- DetailAction.php     # item-local detail route
+-- FormAction.php       # item-local tambah/kemaskini route when CRUD is enabled
+-- DeleteAction.php     # item-local POST delete route when CRUD is enabled
+-- view.php             # item-local list markup
+-- detail.php           # item-local detail markup
+-- form.php             # item-local form markup
```

Shared code for the same item lives beside it in `src/Shared/Perkhidmatan/Sections/`:

```text
src/Shared/Perkhidmatan/Sections/StatusLantikanService.php
src/Shared/Perkhidmatan/Sections/StatusLantikanCrudService.php
```

Standard route shape:

```text
GET      /perkhidmatan/status-lantikan/view?icno={icno}
GET      /perkhidmatan/status-lantikan/viewuser
GET|POST /perkhidmatan/status-lantikan/tambah?icno={icno}
GET|POST /perkhidmatan/status-lantikan/kemaskini?id={id}
POST     /perkhidmatan/status-lantikan/padam
```

Use this same explicit pattern for:

```text
status-lantikan
status-sandangan
status-perkhidmatan
pihak-berkuasa
beban-perkhidmatan
waktu-bekerja
status-pengesahan
tempoh-percubaan
jenis-gaji
jenis-perkhidmatan
pergerakan-gaji
fail-perkhidmatan
status-pencen
mata-gaji
pihak-berkuasa-pencen
umur-bersara
sejarah-penempatan
perubahan-data
```

Do not build this as a single `SectionAction` or one metadata CRUD renderer. The repeated shape is intentional: it keeps route, access, form fields, validation, side effects, and audit behavior traceable.

## Service Design

Use explicit service methods, not metadata-driven screens:

```text
PerkhidmatanProfileService::searchStaff(array $query): array
PerkhidmatanProfileService::profileForIcno(string $icno, string $mode): ?array
PerkhidmatanProfileService::changeHistory(string $icno, int $page): array
PerkhidmatanProfileService::timeline(string $icno): array
PerkhidmatanProfileService::adminSections(string $icno): array
PerkhidmatanProfileService::selfSections(): array
```

Shared services allowed by the standard:

```text
PerkhidmatanAccessService
  - requireLegacySession()
  - requireAdminAccess()
  - canViewIcno($currentIcno, $targetIcno)
  - requirePenempatanAccess(), for Sejarah Penempatan admin flows

PerkhidmatanAuditService
  - logCreate($tableName, $icno, $idValue, array $newValues, array $context)
  - logUpdate($tableName, $icno, $idValue, array $oldValues, array $newValues, array $context)
  - logDelete($tableName, $icno, $idValue, array $oldValues, array $context)
  - touchStatOnCreate($tableName, $icno, $idValue, array $context)
  - touchStatOnUpdate($tableName, $icno, $idValue, array $context)
  - deleteStat($tableName, $icno, $idValue)

PerkhidmatanReferenceService
  - named reference lists only, such as appointment statuses, service statuses, authorities, salary types
```

Audit service boundaries:

- It may centralize the exact row insert/update mechanics for `updatestatus` and `tbl_stat`.
- It must not hide item workflow rules, side effects, validation, or form fields.
- It must preserve the old field names, including the legacy spelling `COUpadteDate`.
- It must preserve PHP serialized `COUpdateSQL` content where Yii2 used serialized changed-field data.
- It must capture the same user, IP, computer, and action context as closely as the Yii3 runtime allows.

Important reuse:

- Reuse the SQL and formatting lessons already in `BiodataProfileService`.
- Do not point `/perkhidmatan/index` to the Biodata list directly, because the menu label, access policy, detail links, and change-history block are different.
- If common code is extracted later, keep it as small helpers for staff search/date formatting only.

## Access Plan

First slice should use explicit route guards:

- All `/perkhidmatan/*` routes require a valid legacy session.
- `/perkhidmatan/index` and `/perkhidmatan/view` require `system_core.tbl_user_access.access IN (1,2,3)`.
- `/perkhidmatan/viewuser` and `/perkhidmatan/redirectview` resolve only the current staff identity.
- `/perkhidmatan/tree-stat` follows the same target access rule as its caller:
  - own ICNO is allowed for self mode
  - HR/admin access is allowed for any selected staff

Note:

- The old menu visibility expression includes `accessLevel` and `isKetuaProgram()`, but the old controller index only allowed `tbl_user_access.access IN (1,2,3)`.
- For the first migration, preserve controller-level access behavior. Treat wider Ketua Program access as a product decision before enabling it locally.
- Unlike old Yii2, direct detail URLs should not bypass the index access check.

## UI Plan

Follow `AI hronline/13_yii3_ui_standard.md`.

List page:

- One `profile-page` and one `page-main-card`.
- Search area as compact operational controls using `record-form` or existing grid search classes.
- Table classes:
  - `table-scroll`
  - `grid-table-wrap`
  - `data-table`
  - `staff-overview-table` or `perkhidmatan-table`
- Page size: 15.
- Pagination preserves filters.
- Footer: `Papar X-Y daripada Z rekod`.
- Status values use `status-badge`.
- View action uses Lucide `eye`.

Admin detail page:

- Page title: `Maklumat Dan Rekod Perkhidmatan`.
- Back action to `/perkhidmatan/index`.
- Use profile photo and `detail-list`/`profile-detail-grid`.
- Service record links rendered in a clear grid.
- Yii2-only links show a `Yii2` marker and `external-link` icon.
- Timeline can be either inline or a simple local detail section; avoid recreating old Bootstrap modal complexity unless needed.
- Change history is a paginated table, not a Yii2 LinkPager clone.

Self-service page:

- Use `/perkhidmatan/viewuser` for current staff.
- Smaller service-record grid matching old self-service links.
- Keep contact information if still required by owner, but style it as normal operational content.

## Data Dependencies

Core tables/views already used by current Yii3 biodata/profile code:

- `hronline.tblprcobiodata`
- `hronline.title`
- `hronline.campus`
- `hronline.department`
- `hronline.gredjawatan`
- `hronline.servicestatus`
- `hronline.appointmentstatus`
- `hronline.rekod_lantikan_terkini`
- `hronline.v_latest_pengesahan`
- `hronline.v_latest_bln_gaji`
- `hronline.v_latest_bersara`
- `hronline.v_latest_pencen`
- `hronline.tbl_kwsp`

Additional first-slice dependencies:

- `system_core.tbl_user_access`
- `hronline.updatestatus`
- `hronline.tblrscoapmtstatus`
- `hronline.tblrscosandangan`
- `hronline.tblrscoservstatus`
- reference tables for labels used by those record tables

Full migration write dependencies:

- `hronline.tblrscoapmtstatus`
- `hronline.tblrscosandangan`
- `hronline.tblrscoservstatus`
- `hronline.tblrscoaptathy`
- `hronline.tblrscoservload`
- `attendance.tbl_wp`
- `hronline.tblrscoconfirmstatus`
- `hronline.tblrscoprobtnperiod`
- `hronline.tblrscosaltype`
- `hronline.tblrscoservtype`
- `hronline.tblrscosalmovemth`
- `hronline.tblrscofileno`
- `hronline.tblrscopsnstatus`
- `hronline.tblrscopersalpoint`
- `hronline.tblrscopsnathy`
- `hronline.tblrscoretireage`
- `hronline.tblpenempatan`
- `hronline.tbl_stat`

Full migration display/history dependencies:

- `hronline.updatestatus`
- `hronline.r_table` / old table-label mapping, where needed for audit labels
- `system_core.tblprcobiodata`, for updated-by staff names
- old reference tables used by each record model
- BRP/PDF dependencies for the later Fail Perkhidmatan slice

## Migration Phases

### Phase 1 - Hub, Access, Audit Foundation

- Add local routes and menu mapping for `perkhidmatan/index`.
- Implement `PerkhidmatanAccessService`.
- Implement `PerkhidmatanAuditService` before any local write action.
- Implement staff search, admin detail, self detail, redirect view, and timeline locally.
- Implement local latest-change history on the admin detail page.
- Keep item links as Yii2 handoffs only until each item below is migrated.

### Phase 2 - Low-Risk Read and CRUD Sections

Migrate these with full admin/self read pages, direct forms, CRUD where old Yii2 had CRUD, and audit parity:

1. Status Lantikan
2. Status Perkhidmatan
3. Pihak Berkuasa Melantik
4. Beban Perkhidmatan
5. Status Pengesahan
6. Tempoh Percubaan
7. Jenis Gaji
8. Skim/Jenis Perkhidmatan
9. Pergerakan Gaji
10. Fail Perkhidmatan basic file-number CRUD
11. Status Pencen
12. Mata Gaji
13. Pihak Berkuasa Pencen
14. Umur Bersara
15. Perubahan Data read-only audit/history pages

For each item:

- Add named action classes and dedicated templates.
- Add item service and CRUD service.
- Keep validation and reference dropdowns explicit in code.
- Add audit calls in the same transaction as the data write where possible.
- Smoke-test read pages before enabling write buttons.
- Test writes only on safe owner-approved rows.

### Phase 3 - Status Sandangan

Migrate `Status Sandangan` separately because it has more business side effects than the simple service-record tables:

- Local admin and self read pages.
- Local create/update/delete pages.
- Audit parity for `Tblrscosandangan`.
- Preserve old create-flow side effects into salary movement, staff identity, PKS, and file number records where the old Yii2 flow performed them.
- Add explicit regression checks for appointment type, sandangan, gred jawatan, and effective date handling.

### Phase 4 - Waktu Bekerja

Migrate `Waktu Bekerja` separately because it writes to `attendance.tbl_wp`, not the normal `hronline.tblrsco*` service-record pattern:

- Local admin and self read pages.
- Local CRUD if old Yii2 behavior supports it for the selected access role.
- Preserve date normalization from old `TblWp`.
- Keep audit behavior faithful: no new `updatestatus` rows unless explicitly approved, because the old model did not show the standard HR audit hooks.

### Phase 5 - Sejarah Penempatan

Migrate `Sejarah Penempatan` as its own subproject:

- Local admin and self read pages.
- Local create/update/delete for placement records.
- Preserve `TblPenempatan` audit behavior.
- Preserve extra access checks through the old placement access model.
- Preserve side effects into `TblPapSenaraiStaf`.
- Migrate placement recommendation data and the surat/PDF flow.
- Keep PDF generation behind a separate verification pass because it touches output format, fonts, and old Kartik/mPDF assumptions.

### Phase 6 - Fail Perkhidmatan Extras

After basic `Fail Perkhidmatan` CRUD is stable:

- Migrate `buku-rekod`.
- Migrate `tugas-generate-letter`.
- Replace Yii2/Kartik/mPDF usage with the Yii3-approved PDF path.
- Verify BRP data sources: `brp.Tblrscobrp`, family, work experience, education, pension status, and retirement age dependencies.

### Phase 7 - Cutover and Cleanup

- Remove Yii2 handoff markers for every completed item.
- Keep only deliberately deferred legacy links, if any, and document the reason.
- Recheck the menu path from `Pengurusan Sumber Manusia > Sumber Manusia > Rekod Perkhidmatan Kakitangan`.
- Run PHP lint and Yii route/list checks.
- Smoke-test admin, self-service, access-denied, and unauthenticated flows.
- Verify audit rows by comparing a safe Yii3 write against the Yii2 payload shape.

## Open Questions

- Should Ketua Program users gain local access to this page, or should Yii3 preserve the old controller restriction of `tbl_user_access.access IN (1,2,3)`?
- Should old contact names/tel extensions in `viewuser.php` remain visible, or be replaced with a maintained support/contact source?
- Should `tree-stat` stay as a modal action or become an inline timeline block?
- For `Waktu Bekerja`, should Yii3 keep the old attendance-only behavior, or should HR audit logging be added as a new business rule?
- For PDF flows, should output be pixel-close to Yii2, or is content parity with the Yii3-approved PDF renderer enough?

## Execution Notes

2026-11-28 update:

- Local Yii3 Perkhidmatan hub, admin view, self view, timeline, item read routes, and menu mapping have been added.
- The Carian section now follows the Rekod Peribadi search styling.
- The admin/self Perkhidmatan profile view now follows the Biodata user-view layout direction.
- `Perubahan Data Terkini` is paginated at 5 rows per page.
- `Status Lantikan` now has a dedicated Yii3 read view matching the old Yii2 table columns and notes.
- Perkhidmatan item pages resolve migrated reference labels for status, authority, salary, pension, placement, gred, sandangan, and working-hour references so raw IDs are not shown when the reference row exists.
- UMSPER/staff number display has been removed from the Yii3 Perkhidmatan search/detail/module templates.
- Admin `Tambah`, `Kemaskini`, and POST `Padam` routes are enabled for the low-risk single-table HR service records: Status Lantikan, Status Perkhidmatan, Pihak Berkuasa Melantik, Beban Perkhidmatan, Status Pengesahan, Tempoh Percubaan, Jenis Gaji, Skim/Jenis Perkhidmatan, Pergerakan Gaji, Fail Perkhidmatan basic file number, Status Pencen, Mata Gaji, Pihak Berkuasa Pencen, and Umur Bersara.
- Enabled write routes use local Yii3 forms, explicit required-field validation from the old Yii2 models, POST delete with CSRF, `hronline.updatestatus`, and `hronline.tbl_stat` where the old models used it. The audit payload now follows the old Yii2 serialized array shape.
- CRUD remains intentionally deferred for Status Sandangan, Waktu Bekerja, Sejarah Penempatan, and Perubahan Data. The first three require separate side-effect migration; Perubahan Data remains read-only audit history.
- The Perkhidmatan web layer now follows the Biodata section structure under `src/Web/Perkhidmatan/Sections/{Item}`: every item folder owns `ViewAction.php`, `DetailAction.php`, `FormAction.php`, `DeleteAction.php`, `view.php`, `detail.php`, and `form.php`.
- Item `view` pages are list-first again, including Status Sandangan. Admin list rows expose `Lihat` for every item and `Kemaskini`/POST `Padam` for the currently enabled CRUD items.
- Detail pages are now routed through each item's local `/lihat` route instead of a shared record-detail action/template.
- Perkhidmatan save redirects now use the standard Yii3 flash query keys: `created=1` for Tambah, `updated=1` for Kemaskini, and `deleted=1` for Padam, so the existing app toast messages appear after CRUD actions.

## Verification Checklist

- [x] `php -l` passes for new PHP files.
- [x] `php yii list` passes.
- [ ] Unauthenticated `/perkhidmatan/index` redirects to `/login`.
- [ ] Authenticated user without HR access receives access denied.
- [ ] HR access user can open `/perkhidmatan/index`.
- [ ] Search filters persist after submit and pagination.
- [ ] List shows 15 rows per page.
- [ ] Footer shows `Papar X-Y daripada Z rekod`.
- [ ] `/perkhidmatan/view?id={icno}` returns 200 for an HR access user.
- [ ] `/perkhidmatan/viewuser` only shows the logged-in user's record.
- [ ] `/perkhidmatan/tree-stat?id={icno}` does not expose another staff record to a normal staff user.
- [ ] During phased migration, any remaining Yii2 handoff link is visually marked.
- [ ] At final cutover, no service-record item still depends on Yii2 handoff.
- [x] Low-risk migrated items have admin read route, self read route where old Yii2 had one, and local create/update/delete where old Yii2 had write behavior.
- [x] Every Rekod Perkhidmatan item has a Biodata-style local web folder with local view/detail/form/delete actions and templates.
- [x] Item pages open as record lists first; individual record details are behind local `lihat` routes.
- [x] Tambah, Kemaskini, and Padam redirects trigger the standard success flash/toast messages.
- [x] Enabled delete routes use POST plus CSRF for data mutation.
- [x] Enabled writes log `hronline.updatestatus` using old activity code and serialized payload shape.
- [ ] `hronline.tbl_stat` side effects match old Yii2 behavior for audited `hronline.tblrsco*` and placement models.
- [ ] Waktu Bekerja audit behavior is explicitly approved because the old model is outside the standard HR audit pattern.
- [ ] Status Sandangan side effects are verified against old Yii2 behavior.
- [ ] Sejarah Penempatan access rules, side effects, and PDF output are verified separately.
- [ ] Fail Perkhidmatan BRP/PDF extras are verified separately.
- [ ] No Yii2 widgets, Kartik widgets, or Gentelella widgets are used in Yii3 templates.
- [ ] Final forms use direct markup, not metadata-generated field loops.
