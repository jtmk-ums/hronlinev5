<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class MenuService
{
    private const LOCAL_ROUTE_MAP = [
        'dashboard/index' => '/dashboard',
        'site/menu' => '/menu',
        'biodata/userview' => '/biodata/userview',
        'biodata/index' => '/biodata/index',
        'biodata/lihatbiodata' => '/biodata/lihatbiodata',
        'alamat/view' => '/biodata/alamat/view',
        'pendidikan/view' => '/biodata/pendidikan/view',
        'bahasa/view' => '/biodata/bahasa/view',
        'badan-profesional/view' => '/biodata/keahlian/view',
        'anugerah/view' => '/biodata/anugerah/view',
        'pasport-permit/view' => '/biodata/pasport-permit/view',
        'lesen/view' => '/biodata/lesen/view',
        'akaun/senarai-akaun' => '/biodata/akaun/senarai-akaun',
        'kecacatan/view' => '/biodata/kecacatan/view',
        'pengalamankerja/view' => '/biodata/pengalamankerja/view',
        'sejarahperubatan/view' => '/biodata/sejarahperubatan/view',
        'keluarga/view' => '/biodata/keluarga/view',
        'bidangkepakaran/view' => '/biodata/bidangkepakaran/view',
        'vaksinasi/index' => '/biodata/vaksinasi/index',
        'kelayakan-profesional/view' => '/biodata/kelayakan-profesional/view',
        'gambar-profile/semakan' => '/gambar-profile/semakan',
        'gambar-profile/senarai' => '/gambar-profile/senarai',
        'gambar-profile/rekod-permohonan' => '/gambar-profile/rekod-permohonan',
        'gambar-profile/cari-kakitangan' => '/gambar-profile/cari-kakitangan',
        'gambar-profile/admin-senarai-penukaran' => '/gambar-profile/admin-senarai-penukaran',
        'gambar-profile/tambah-admin' => '/gambar-profile/tambah-admin',
        'gambar-profile/senarai-admin' => '/gambar-profile/senarai-admin',
        'cuti/individu/index' => '/cuti/individu/index',
        'pengesahan-data-staf' => '/pengesahan-data-staf/index',
        'pengesahan-data-staf/index' => '/pengesahan-data-staf/index',
        'lantikan/index' => '/lantikan/index',
        'lantikan/lantikan-belum-selesai' => '/lantikan/lantikan-belum-selesai',
        'lantikan/lssu-index' => '/lantikan/lssu-index',
        'perkhidmatan/index' => '/perkhidmatan/index',
        'kehadiran/index' => '/kehadiran/index',
        'kehadiran/laporan-kehadiran' => '/kehadiran/laporan-kehadiran',
        'kehadiran/tindakan_ketidakpatuhan' => '/kehadiran/tindakan_ketidakpatuhan',
        'kehadiran/staf-color-list' => '/kehadiran/staf-color-list',
        'kehadiran/kad-warna' => '/kehadiran/kad-warna',
        'kehadiran/kad_warna' => '/kehadiran/kad_warna',
        'kehadiran/wfh-list' => '/kehadiran/wfh-list',
        'kehadiran/absent-list' => '/kehadiran/absent-list',
        'kehadiran/senarai_tindakan' => '/kehadiran/senarai_tindakan',
        'kehadiran/staff-color-card' => '/kehadiran/staff-color-card',
        'kehadiran/pantau-kehadiran' => '/kehadiran/pantau-kehadiran',
        'kehadiran/monthly-summary' => '/kehadiran/monthly-summary',
        'kehadiran/wfh-lulus' => '/kehadiran/wfh-lulus',
        'kehadiran/pantau-permohonan-wfh-pegawai' => '/kehadiran/pantau-permohonan-wfh-pegawai',
        'kehadiran/absent-list-kp' => '/kehadiran/absent-list-kp',
        'kehadiran/pengesahan' => '/kehadiran/pengesahan',
        'kehadiran/wfh-lulus-tindakan' => '/kehadiran/wfh-lulus-tindakan',
        'kehadiran/delete-wfh' => '/kehadiran/delete-wfh',
        'kehadiran/verify-thtc-sv' => '/kehadiran/verify-thtc-sv',
        'kehadiran/approve-thtc' => '/kehadiran/approve-thtc',
        'kehadiran/senarai_kakitangan' => '/kehadiran/senarai_kakitangan',
        'kehadiran/staff-wbb' => '/kehadiran/staff-wbb',
        'kehadiran/sup-mth-rpt' => '/kehadiran/sup-mth-rpt',
        'kehadiran/sup-year-rpt' => '/kehadiran/sup-year-rpt',
        'kehadiran/senarai-warna-kad' => '/kehadiran/senarai-warna-kad',
        'kehadiran/sup-year-performance-rpt' => '/kehadiran/sup-year-performance-rpt',
        'kehadiran/pantau-staf' => '/kehadiran/pantau-staf',
        'kehadiran/shield-status-list' => '/kehadiran/shield-status-list',
        'kehadiran/staff-shift-list' => '/kehadiran/staff-shift-list',
        'kehadiran/shift-setup' => '/kehadiran/shift-setup',
        'kehadiran-admin/manage-shift-supervisor' => '/kehadiran-admin/manage-shift-supervisor',
        'kehadiran/wfh-by-mth' => '/kehadiran/wfh-by-mth',
        'kehadiran/wfh-jadual' => '/kehadiran/wfh-jadual',
        'kehadiran/pantau-permohonan-wfh' => '/kehadiran/pantau-permohonan-wfh',
        'kehadiran/absent-list-sv' => '/kehadiran/absent-list-sv',
        'kehadiran/kj-pantau-kehadiran' => '/kehadiran/kj-pantau-kehadiran',
        'kehadiran/wfh-jadual-kj' => '/kehadiran/wfh-jadual-kj',
        'kehadiran/list-kj' => '/kehadiran/list-kj',
        'kehadiran/staff-all' => '/kehadiran/staff-all',
        'kehadiran/admin-mth-rpt' => '/kehadiran/admin-mth-rpt',
        'kehadiran/color-rpt' => '/kehadiran/color-rpt',
        'attendance/index' => '/attendance/index',
        'kehadiran/pantau-permohonan-wfh-admin' => '/kehadiran/pantau-permohonan-wfh-admin',
        'kehadiran/wfh-jadual-admin' => '/kehadiran/wfh-jadual-admin',
        'kehadiran/statistik-wfh' => '/kehadiran/statistik-wfh',
        'kehadiran/admin-post-list' => '/kehadiran/admin-post-list',
        'attendance/absent-list-bpsm' => '/attendance/absent-list-bpsm',
    ];

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private MenuVisibilityService $visibility,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function menuForIdentity(string $identityId): ?array
    {
        $icno = $this->employees->icnoFromIdentity($identityId);
        $staff = $this->employees->byIcno($icno);
        if ($icno === '' || $staff === null) {
            return null;
        }

        $hiddenUnsupported = [];
        $childRows = $this->visibleRows($this->childRows(), $icno, $hiddenUnsupported);
        $children = $this->childrenByParent($childRows);
        $mainRows = $this->visibleRows($this->mainRows(), $icno, $hiddenUnsupported);

        $categories = [];
        foreach ($this->categories() as $category) {
            $items = [];
            foreach ($mainRows as $row) {
                if ((string) ($row['category_id'] ?? '') !== (string) ($category['id'] ?? '')) {
                    continue;
                }

                $item = $this->menuItem($row, $children[(string) ($row['id'] ?? '')] ?? []);
                if ($item !== null) {
                    $items[] = $item;
                }
            }

            if ($items === []) {
                continue;
            }

            $categories[] = [
                'id' => (string) ($category['id'] ?? ''),
                'name' => $this->value($category['name'] ?? null),
                'icon' => $this->value($category['icon'] ?? null, 'folder'),
                'items' => $items,
                'count' => count($items),
            ];
        }

        return [
            'icno' => $icno,
            'staffName' => $this->employees->displayName($staff['CONm'] ?? null),
            'categories' => $categories,
            'unsupportedRules' => array_values(array_unique($hiddenUnsupported)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categories(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, name, icon
            FROM system_core.tbl_menu_category
            WHERE status = 1
            ORDER BY `order`
            SQL,
        )->queryAll();

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mainRows(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, category_id, label, url, visible
            FROM system_core.tbl_menu_side
            WHERE parent_id IS NULL
              AND status = 1
            ORDER BY label
            SQL,
        )->queryAll();

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function childRows(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, parent_id, label, url, visible
            FROM system_core.tbl_menu_side
            WHERE parent_id IS NOT NULL
              AND status = 1
            ORDER BY label
            SQL,
        )->queryAll();

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $hiddenUnsupported
     * @return list<array<string, mixed>>
     */
    private function visibleRows(array $rows, string $icno, array &$hiddenUnsupported): array
    {
        $visible = [];
        foreach ($rows as $row) {
            $result = $this->visibility->evaluate($this->value($row['visible'] ?? null, ''), $icno);
            if ($result['visible']) {
                $visible[] = $row;
                continue;
            }

            if (!$result['supported']) {
                $hiddenUnsupported[] = $this->value($row['label'] ?? null, 'Unknown') . ': ' . $result['reason'];
            }
        }

        return $visible;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, list<array<string, mixed>>>
     */
    private function childrenByParent(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $parentId = (string) ($row['parent_id'] ?? '');
            if ($parentId === '') {
                continue;
            }

            $grouped[$parentId][] = $row;
        }

        return $grouped;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<array<string, mixed>> $children
     * @return array<string, mixed>|null
     */
    private function menuItem(array $row, array $children): ?array
    {
        $childItems = [];
        foreach ($children as $child) {
            $childItem = $this->menuItem($child, []);
            if ($childItem !== null) {
                $childItems[] = $childItem;
            }
        }

        $url = $this->value($row['url'] ?? null, '');
        if ($url === '' && $childItems === []) {
            return null;
        }

        $normalized = $url === '' ? ['url' => '', 'external' => false, 'legacy' => false] : $this->normalizeUrl($url);

        return [
            'id' => (string) ($row['id'] ?? ''),
            'label' => $this->value($row['label'] ?? null),
            'url' => $normalized['url'],
            'external' => $normalized['external'],
            'legacy' => $normalized['legacy'],
            'children' => $childItems,
        ];
    }

    /**
     * @return array{url: string, external: bool, legacy: bool}
     */
    private function normalizeUrl(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['url' => '', 'external' => false, 'legacy' => false];
        }

        if (str_starts_with($url, 'https://') || str_starts_with($url, 'http://')) {
            return ['url' => $url, 'external' => true, 'legacy' => false];
        }

        $route = ltrim($url, '/');
        if (isset(self::LOCAL_ROUTE_MAP[$route])) {
            return ['url' => self::LOCAL_ROUTE_MAP[$route], 'external' => false, 'legacy' => false];
        }

        return [
            'url' => LegacyUrl::to($route),
            'external' => true,
            'legacy' => true,
        ];
    }

    private function value(mixed $value, string $fallback = 'Tiada Rekod'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
