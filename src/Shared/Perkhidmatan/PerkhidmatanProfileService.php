<?php

declare(strict_types=1);

namespace App\Shared\Perkhidmatan;

use App\Shared\BiodataProfileService;
use App\Shared\EmployeeRepository;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class PerkhidmatanProfileService
{
    private const HISTORY_PAGE_SIZE = 5;

    /**
     * @var list<array{label: string, slug: string, self: bool}>
     */
    private const SECTIONS = [
        ['label' => 'Status Lantikan', 'slug' => 'status-lantikan', 'self' => true],
        ['label' => 'Status Sandangan', 'slug' => 'status-sandangan', 'self' => true],
        ['label' => 'Status Perkhidmatan', 'slug' => 'status-perkhidmatan', 'self' => true],
        ['label' => 'Pihak Berkuasa Melantik', 'slug' => 'pihak-berkuasa', 'self' => true],
        ['label' => 'Beban Perkhidmatan', 'slug' => 'beban-perkhidmatan', 'self' => true],
        ['label' => 'Waktu Bekerja', 'slug' => 'waktu-bekerja', 'self' => true],
        ['label' => 'Status Pengesahan', 'slug' => 'status-pengesahan', 'self' => true],
        ['label' => 'Tempoh Percubaan', 'slug' => 'tempoh-percubaan', 'self' => true],
        ['label' => 'Jenis Gaji', 'slug' => 'jenis-gaji', 'self' => true],
        ['label' => 'Skim/Jenis Perkhidmatan', 'slug' => 'jenis-perkhidmatan', 'self' => true],
        ['label' => 'Pergerakan Gaji', 'slug' => 'pergerakan-gaji', 'self' => true],
        ['label' => 'Fail Perkhidmatan', 'slug' => 'fail-perkhidmatan', 'self' => true],
        ['label' => 'Status Pencen', 'slug' => 'status-pencen', 'self' => true],
        ['label' => 'Mata Gaji', 'slug' => 'mata-gaji', 'self' => true],
        ['label' => 'Pihak Berkuasa Pencen', 'slug' => 'pihak-berkuasa-pencen', 'self' => true],
        ['label' => 'Umur Bersara', 'slug' => 'umur-bersara', 'self' => true],
        ['label' => 'Sejarah Penempatan', 'slug' => 'sejarah-penempatan', 'self' => true],
        ['label' => 'Perubahan Data', 'slug' => 'perubahan-data', 'self' => true],
    ];

    public function __construct(
        private ConnectionInterface $db,
        private BiodataProfileService $biodataProfiles,
        private EmployeeRepository $employees,
        private StaffDateFormatter $dateFormatter,
    ) {}

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function searchStaff(array $query): array
    {
        return $this->biodataProfiles->searchProfiles($query);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profileForIcno(string $icno, string $mode): ?array
    {
        $profile = $this->biodataProfiles->profileForIcno($icno, $mode);
        if ($profile === null) {
            return null;
        }

        $profile['sections'] = $mode === 'admin' ? $this->adminSections($icno) : $this->selfSections();

        return $profile;
    }

    public function icnoFromIdentity(string $identityId): string
    {
        return $this->employees->icnoFromIdentity($identityId);
    }

    /**
     * @return array<string, mixed>
     */
    public function changeHistory(string $icno, int $page = 1, bool $serviceOnly = false): array
    {
        $page = max(1, $page);
        $limit = self::HISTORY_PAGE_SIZE;
        $offset = ($page - 1) * $limit;
        $params = [':icno' => $icno];
        $where = 'u.usern = :icno';
        if ($serviceOnly) {
            $where .= " AND u.COTableName LIKE '%tblrs%'";
        }

        $total = (int) $this->db->createCommand(
            "SELECT COUNT(*) FROM hronline.updatestatus u WHERE {$where}",
            $params,
        )->queryScalar();
        $pageCount = max(1, (int) ceil($total / $limit));
        $page = min($page, $pageCount);
        $offset = ($page - 1) * $limit;

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT u.id, u.COTableName, u.COActivity, u.COUpadteDate, u.COUpdateCompUser, u.idval,
                   rt.nama AS table_label,
                   staff.CONm AS updater_name
            FROM hronline.updatestatus u
            LEFT JOIN hronline.r_table rt ON rt.`table` = u.COTableName
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = u.COUpdateCompUser
            WHERE {$where}
            ORDER BY u.COUpadteDate DESC
            LIMIT {$limit}
            OFFSET {$offset}
            SQL,
            $params,
        )->queryAll();

        $historyRows = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $historyRows[] = [
                'id' => $this->value($row['id'] ?? null, ''),
                'section' => $this->value($row['table_label'] ?? null, $this->value($row['COTableName'] ?? null)),
                'table' => $this->value($row['COTableName'] ?? null),
                'activity' => $this->activityLabel($row['COActivity'] ?? null),
                'updatedAt' => $this->dateFormatter->display($row['COUpadteDate'] ?? null, 'Tiada Rekod'),
                'updatedBy' => $this->employees->displayName($row['updater_name'] ?? $row['COUpdateCompUser'] ?? null),
                'idValue' => $this->value($row['idval'] ?? null, '-'),
            ];
        }

        return [
            'rows' => $historyRows,
            'page' => $page,
            'pageCount' => $pageCount,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function timeline(string $icno): array
    {
        $staff = $this->employees->byIcno($icno);
        $items = [];

        $this->appendTimeline($items, <<<SQL
            SELECT appointment.ApmtStatusNm AS title, record.ApmtStatusStDt AS date_value, 'Status Lantikan' AS source
            FROM hronline.tblrscoapmtstatus record
            LEFT JOIN hronline.appointmentstatus appointment ON appointment.ApmtStatusCd = record.ApmtStatusCd
            WHERE record.ICNO = :icno
            SQL, $icno);
        $this->appendTimeline($items, <<<SQL
            SELECT CONCAT(COALESCE(sandangan.sandangan_name, 'Status Sandangan'), ' [ ', COALESCE(job.fname, '-'), ' ]') AS title,
                   record.start_date AS date_value,
                   'Status Sandangan' AS source
            FROM hronline.tblrscosandangan record
            LEFT JOIN hronline.sandangan sandangan ON sandangan.sandangan_id = record.sandangan_id
            LEFT JOIN hronline.gredjawatan job ON job.id = record.gredjawatan
            WHERE record.ICNO = :icno
            SQL, $icno);
        $this->appendTimeline($items, <<<SQL
            SELECT service.ServStatusNm AS title, record.ServStatusStDt AS date_value, 'Status Perkhidmatan' AS source
            FROM hronline.tblrscoservstatus record
            LEFT JOIN hronline.servicestatus service ON service.ServStatusCd = record.ServStatusCd
            WHERE record.ICNO = :icno
            SQL, $icno);

        usort(
            $items,
            static fn (array $a, array $b): int => strcmp((string) $b['sortDate'], (string) $a['sortDate']),
        );

        foreach ($items as &$item) {
            unset($item['sortDate']);
        }

        return [
            'staff' => [
                'icno' => $icno,
                'name' => $this->employees->displayName($staff['CONm'] ?? null),
            ],
            'items' => $items,
        ];
    }

    /**
     * @return list<array{label: string, url: string, external: bool, legacy: bool}>
     */
    public function adminSections(string $icno): array
    {
        $sections = [];
        foreach (self::SECTIONS as $section) {
            $sections[] = [
                'label' => $section['label'],
                'url' => '/perkhidmatan/' . $section['slug'] . '/view?icno=' . rawurlencode($icno),
                'external' => false,
                'legacy' => false,
            ];
        }

        return $sections;
    }

    /**
     * @return list<array{label: string, url: string, external: bool, legacy: bool}>
     */
    public function selfSections(): array
    {
        $sections = [];
        foreach (self::SECTIONS as $section) {
            if (!$section['self']) {
                continue;
            }

            $sections[] = [
                'label' => $section['label'],
                'url' => '/perkhidmatan/' . $section['slug'] . '/viewuser',
                'external' => false,
                'legacy' => false,
            ];
        }

        return $sections;
    }

    /**
     * @param list<array<string, string>> $items
     */
    private function appendTimeline(array &$items, string $sql, string $icno): void
    {
        try {
            $rows = $this->db->createCommand($sql, [':icno' => $icno])->queryAll();
        } catch (\Throwable) {
            return;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $date = $row['date_value'] ?? null;
            $items[] = [
                'title' => $this->value($row['title'] ?? null),
                'date' => $this->dateFormatter->displayMalay($date, 'Tiada Rekod'),
                'source' => $this->value($row['source'] ?? null),
                'sortDate' => is_scalar($date) ? (string) $date : '',
            ];
        }
    }

    private function activityLabel(mixed $value): string
    {
        return match ((string) $value) {
            '0' => 'Tambah',
            '1' => 'Kemaskini',
            '2' => 'Padam',
            default => 'Aktiviti',
        };
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
