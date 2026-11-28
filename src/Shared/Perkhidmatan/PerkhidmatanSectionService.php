<?php

declare(strict_types=1);

namespace App\Shared\Perkhidmatan;

use App\Shared\EmployeeRepository;
use App\Shared\StaffDateFormatter;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class PerkhidmatanSectionService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const CONFIG = [
        'status-lantikan' => [
            'title' => 'Status Lantikan',
            'table' => 'hronline.tblrscoapmtstatus',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'ApmtStatusStDt',
            'labels' => [
                'ApmtStatusCd' => 'Status Lantikan',
                'ApmtStatusStDt' => 'Tarikh Mula',
                'ApmtStatusEndDt' => 'Tarikh Tamat',
            ],
            'references' => [
                'ApmtStatusCd' => ['table' => 'hronline.appointmentstatus', 'key' => 'ApmtStatusCd', 'labels' => ['ApmtStatusNm']],
            ],
        ],
        'status-sandangan' => [
            'title' => 'Status Sandangan',
            'table' => 'hronline.tblrscosandangan',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'start_date',
            'labels' => [
                'gredjawatan' => 'Gred Jawatan',
                'sandangan_id' => 'Sandangan',
                'ApmtTypeCd' => 'Jenis Lantikan',
                'start_date' => 'Tarikh Mula',
                'status' => 'Status',
            ],
            'references' => [
                'gredjawatan' => ['table' => 'hronline.gredjawatan', 'key' => 'id', 'labels' => ['fname', 'nama', 'gred']],
                'sandangan_id' => ['table' => 'hronline.sandangan', 'key' => 'sandangan_id', 'labels' => ['sandangan_name']],
                'ApmtTypeCd' => ['table' => 'hronline.appointmenttype', 'key' => 'ApmtTypeCd', 'labels' => ['ApmtTypeNm']],
                'status' => ['values' => ['0' => 'Tidak Aktif', '1' => 'Aktif']],
            ],
        ],
        'status-perkhidmatan' => [
            'title' => 'Status Perkhidmatan',
            'table' => 'hronline.tblrscoservstatus',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'ServStatusStDt',
            'labels' => [
                'ServStatusCd' => 'Status Perkhidmatan',
                'ServStatusStDt' => 'Tarikh Mula',
                'ServStatusEndDt' => 'Tarikh Tamat',
                'ServStatusDtl' => 'Butiran',
                'sebab_berhenti' => 'Sebab Berhenti',
            ],
            'references' => [
                'ServStatusCd' => ['table' => 'hronline.servicestatus', 'key' => 'ServStatusCd', 'labels' => ['ServStatusNm']],
                'ServStatusDtl' => ['table' => 'hronline.servicestatusdetail', 'key' => 'id', 'labels' => ['name']],
                'sebab_berhenti' => ['table' => 'hronline.servicestatusdetail', 'key' => 'id', 'labels' => ['name']],
            ],
        ],
        'pihak-berkuasa' => [
            'title' => 'Pihak Berkuasa Melantik',
            'table' => 'hronline.tblrscoaptathy',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'AptAthyStDt',
            'labels' => [
                'AptAthyCd' => 'Pihak Berkuasa',
                'AptAthyStDt' => 'Tarikh Mula',
                'AptAthyEndDt' => 'Tarikh Tamat',
            ],
            'references' => [
                'AptAthyCd' => ['table' => 'hronline.appointingauthority', 'key' => 'AptAthyCd', 'labels' => ['AptAthyNm']],
            ],
        ],
        'beban-perkhidmatan' => [
            'title' => 'Beban Perkhidmatan',
            'table' => 'hronline.tblrscoservload',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'ServLoadStDt',
            'labels' => [
                'ServLoadCd' => 'Beban Perkhidmatan',
                'ServLoadStDt' => 'Tarikh Mula',
                'ServLoadEndDt' => 'Tarikh Tamat',
            ],
            'references' => [
                'ServLoadCd' => ['table' => 'hronline.serviceloading', 'key' => 'ServLoadCd', 'labels' => ['ServLoadNm']],
            ],
        ],
        'waktu-bekerja' => [
            'title' => 'Waktu Bekerja',
            'table' => 'attendance.tbl_wp',
            'icnoColumn' => 'icno',
            'orderColumn' => 'start_date',
            'labels' => [
                'wp_id' => 'Waktu Bekerja',
                'remark' => 'Catatan',
                'start_date' => 'Tarikh Mula',
                'entry_dt' => 'Tarikh Masuk',
            ],
            'references' => [
                'wp_id' => ['table' => 'attendance.ref_wp', 'key' => 'id', 'labels' => ['jenis_wp', 'detail']],
                'entry_by' => ['table' => 'hronline.tblprcobiodata', 'key' => 'ICNO', 'labels' => ['CONm']],
                'update_by' => ['table' => 'hronline.tblprcobiodata', 'key' => 'ICNO', 'labels' => ['CONm']],
            ],
        ],
        'status-pengesahan' => [
            'title' => 'Status Pengesahan',
            'table' => 'hronline.tblrscoconfirmstatus',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'ConfirmStatusStDt',
            'labels' => [
                'ConfirmStatusCd' => 'Status Pengesahan',
                'ConfirmStatusStDt' => 'Tarikh Mula',
                'ConfirmStatusEndDt' => 'Tarikh Tamat',
            ],
            'references' => [
                'ConfirmStatusCd' => ['table' => 'hronline.confirmationstatus', 'key' => 'ConfirmStatusCd', 'labels' => ['ConfirmStatusNm']],
            ],
        ],
        'tempoh-percubaan' => [
            'title' => 'Tempoh Percubaan',
            'table' => 'hronline.tblrscoprobtnperiod',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'ProbtnStDt',
            'labels' => [
                'ProbtnPeriod' => 'Tempoh Percubaan',
                'ProbtnPeriodMin' => 'Tempoh Minimum',
                'ProbtnStDt' => 'Tarikh Mula',
                'ProbtnEndDt' => 'Tarikh Tamat',
            ],
        ],
        'jenis-gaji' => [
            'title' => 'Jenis Gaji',
            'table' => 'hronline.tblrscosaltype',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'SalTypeStDt',
            'labels' => [
                'SalTypeCd' => 'Jenis Gaji',
                'SalStatus' => 'Status Gaji',
                'SalTypeStDt' => 'Tarikh Mula',
                'SalTypeEndDt' => 'Tarikh Tamat',
            ],
            'references' => [
                'SalTypeCd' => ['table' => 'hronline.salarytype', 'key' => 'SalTypeCd', 'labels' => ['SalTypeNm']],
                'SalStatus' => ['table' => 'hronline.salarystatus', 'key' => 'id', 'labels' => ['name']],
            ],
        ],
        'jenis-perkhidmatan' => [
            'title' => 'Skim/Jenis Perkhidmatan',
            'table' => 'hronline.tblrscoservtype',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'ServTypeStDt',
            'labels' => [
                'ServTypeCd' => 'Jenis Perkhidmatan',
                'ServTypeStDt' => 'Tarikh Mula',
                'ServTypeEndDt' => 'Tarikh Tamat',
            ],
            'references' => [
                'ServTypeCd' => ['table' => 'hronline.servicetype', 'key' => 'ServTypeCd', 'labels' => ['ServTypeNm']],
            ],
        ],
        'pergerakan-gaji' => [
            'title' => 'Pergerakan Gaji',
            'table' => 'hronline.tblrscosalmovemth',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'SalMoveMthStDt',
            'labels' => [
                'SalMoveMth' => 'Bulan Pergerakan',
                'SalMoveMthType' => 'Jenis Pergerakan',
                'SalMoveMthStDt' => 'Tarikh Mula',
                'SalMoveMthEndDt' => 'Tarikh Tamat',
            ],
            'references' => [
                'SalMoveMth' => ['table' => 'hronline.movemonth', 'key' => 'id', 'labels' => ['name']],
                'SalMoveMthType' => ['table' => 'hronline.movemonthtype', 'key' => 'id', 'labels' => ['name']],
            ],
        ],
        'fail-perkhidmatan' => [
            'title' => 'Fail Perkhidmatan',
            'table' => 'hronline.tblrscofileno',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'COFileNoEftvDt',
            'labels' => [
                'COFileNo' => 'No. Fail',
                'COFileNoEftvDt' => 'Tarikh Kuat Kuasa',
            ],
        ],
        'status-pencen' => [
            'title' => 'Status Pencen',
            'table' => 'hronline.tblrscopsnstatus',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'PsnStatusStDt',
            'labels' => [
                'PsnStatusCd' => 'Status Pencen',
                'PsnStatusStDt' => 'Tarikh Mula',
                'PsnStatusEndDt' => 'Tarikh Tamat',
                'PsnStatusNo' => 'No. KWAP',
                'PsnEpfNo' => 'No. KWSP',
            ],
            'references' => [
                'PsnStatusCd' => ['table' => 'hronline.pensionstatus', 'key' => 'PsnStatusCd', 'labels' => ['PsnStatusNm']],
            ],
        ],
        'mata-gaji' => [
            'title' => 'Mata Gaji',
            'table' => 'hronline.tblrscopersalpoint',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'PerSalPointStDt',
            'labels' => [
                'SalPointCd' => 'Mata Gaji',
                'PerSalPointStDt' => 'Tarikh Mula',
                'PerSalPointEndDt' => 'Tarikh Tamat',
            ],
            'references' => [
                'SalPointCd' => ['table' => 'hronline.salarypoint', 'key' => 'SalPointCd', 'labels' => ['SalPointAmt', 'SalPointId', 'SalPointCd']],
            ],
        ],
        'pihak-berkuasa-pencen' => [
            'title' => 'Pihak Berkuasa Pencen',
            'table' => 'hronline.tblrscopsnathy',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'PsnAthyStDt',
            'labels' => [
                'PsnAthyCd' => 'Pihak Berkuasa',
                'PsnAthyStDt' => 'Tarikh Mula',
                'PsnAthyEndDt' => 'Tarikh Tamat',
            ],
            'references' => [
                'PsnAthyCd' => ['table' => 'hronline.pensionauthority', 'key' => 'PsnAthyCd', 'labels' => ['PsnAthyNm']],
            ],
        ],
        'umur-bersara' => [
            'title' => 'Umur Bersara',
            'table' => 'hronline.tblrscoretireage',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'CORetireAgeEftvDt',
            'labels' => [
                'RetireAgeCd' => 'Umur Bersara',
                'CORetireAgeEftvDt' => 'Tarikh Kuat Kuasa',
            ],
            'references' => [
                'RetireAgeCd' => ['table' => 'hronline.retireage', 'key' => 'RetireAgeCd', 'labels' => ['Name', 'RetireAgeDesc']],
            ],
        ],
        'sejarah-penempatan' => [
            'title' => 'Sejarah Penempatan',
            'table' => 'hronline.tblpenempatan',
            'icnoColumn' => 'ICNO',
            'orderColumn' => 'date_start',
            'labels' => [
                'dept_id' => 'Jabatan',
                'campus_id' => 'Kampus',
                'reason_id' => 'Sebab',
                'remark' => 'Catatan',
                'letter_order_refno' => 'No. Rujukan Surat',
                'date_start' => 'Tarikh Mula',
                'date_letter_order' => 'Tarikh Surat',
            ],
            'references' => [
                'dept_id' => ['table' => 'hronline.department', 'key' => 'id', 'labels' => ['fullname', 'shortname']],
                'campus_id' => ['table' => 'hronline.campus', 'key' => 'campus_id', 'labels' => ['campus_name']],
                'reason_id' => ['table' => 'hronline.ref_reason_penempatan', 'key' => 'reason_id', 'labels' => ['name']],
                'update_by' => ['table' => 'hronline.tblprcobiodata', 'key' => 'ICNO', 'labels' => ['CONm']],
            ],
        ],
    ];

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private StaffDateFormatter $dateFormatter,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function sectionForIcno(string $slug, string $icno): ?array
    {
        if ($slug === 'perubahan-data') {
            return $this->changeSectionForIcno($icno);
        }

        $config = self::CONFIG[$slug] ?? null;
        if ($config === null) {
            return null;
        }

        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        $table = (string) $config['table'];
        $icnoColumn = (string) $config['icnoColumn'];
        $orderColumn = (string) $config['orderColumn'];
        try {
            $rows = $this->db->createCommand(
                "SELECT * FROM {$table} WHERE {$icnoColumn} = :icno ORDER BY {$orderColumn} DESC",
                [':icno' => $icno],
            )->queryAll();
        } catch (\Throwable) {
            $rows = [];
        }

        $labels = is_array($config['labels']) ? $config['labels'] : [];
        $references = is_array($config['references'] ?? null) ? $config['references'] : [];
        $records = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $records[] = [
                'id' => $this->recordId($row),
                'fields' => $this->fields($row, $labels, $references),
            ];
        }

        return [
            'slug' => $slug,
            'title' => (string) $config['title'],
            'staff' => [
                'icno' => $icno,
                'name' => $this->employees->displayName($staff['CONm'] ?? null),
            ],
            'records' => $records,
            'readOnlyNote' => $slug === 'waktu-bekerja'
                ? 'Rekod ini datang daripada skema attendance dan audit HR tidak ditambah secara automatik.'
                : '',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function statusLantikanForIcno(string $icno, bool $selfMode): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        $orderColumn = $selfMode ? 'record.id' : 'record.ApmtStatusStDt';
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT record.id,
                   record.ICNO,
                   record.ApmtStatusCd,
                   record.ApmtStatusStDt,
                   record.ApmtStatusEndDt,
                   appointment.ApmtStatusNm,
                   (
                       SELECT retire.RetireAgeCd
                       FROM hronline.tblrscoretireage retire
                       WHERE retire.ICNO = record.ICNO
                       ORDER BY retire.CORetireAgeEftvDt DESC, retire.id DESC
                       LIMIT 1
                   ) AS RetireAgeCd,
                   (
                       SELECT retire.CORetireAgeEftvDt
                       FROM hronline.tblrscoretireage retire
                       WHERE retire.ICNO = record.ICNO
                       ORDER BY retire.CORetireAgeEftvDt DESC, retire.id DESC
                       LIMIT 1
                   ) AS CORetireAgeEftvDt
            FROM hronline.tblrscoapmtstatus record
            LEFT JOIN hronline.appointmentstatus appointment ON appointment.ApmtStatusCd = record.ApmtStatusCd
            WHERE record.ICNO = :icno
            ORDER BY {$orderColumn} DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $records = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $endDate = $row['ApmtStatusEndDt'] ?? null;
            if (
                $selfMode
                && (string) ($row['ApmtStatusCd'] ?? '') === '1'
                && $this->value($row['RetireAgeCd'] ?? null, '') !== ''
            ) {
                $endDate = $row['CORetireAgeEftvDt'] ?? $endDate;
            }

            $records[] = [
                'id' => $this->value($row['id'] ?? null, ''),
                'status' => $this->value($row['ApmtStatusNm'] ?? null),
                'startDate' => $this->dateFormatter->displayMalay($row['ApmtStatusStDt'] ?? null, 'Tiada Rekod'),
                'endDate' => $this->dateFormatter->displayMalay($endDate, 'Tiada Rekod'),
            ];
        }

        return [
            'title' => 'Status Lantikan',
            'staff' => [
                'icno' => $icno,
                'name' => $this->employees->displayName($staff['CONm'] ?? null),
            ],
            'records' => $records,
            'selfMode' => $selfMode,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function recordForId(string $slug, string $id): ?array
    {
        if ($id === '') {
            return null;
        }

        $config = self::CONFIG[$slug] ?? null;
        if ($config === null) {
            return $slug === 'perubahan-data' ? $this->changeRecordForId($id) : null;
        }

        $row = $this->rowByRecordId((string) $config['table'], $id);
        if ($row === null) {
            return null;
        }

        $icnoColumn = (string) $config['icnoColumn'];
        $icno = $this->value($row[$icnoColumn] ?? $row['ICNO'] ?? $row['icno'] ?? null, '');
        $staff = $icno === '' ? null : $this->employees->byIcno($icno);
        $labels = is_array($config['labels']) ? $config['labels'] : [];
        $references = is_array($config['references'] ?? null) ? $config['references'] : [];

        return [
            'slug' => $slug,
            'title' => (string) $config['title'],
            'staff' => [
                'icno' => $icno,
                'name' => $this->employees->displayName($staff['CONm'] ?? null),
            ],
            'record' => [
                'id' => $this->recordId($row),
                'fields' => $this->fields($row, $labels, $references),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function slugs(): array
    {
        return array_merge(array_keys(self::CONFIG), ['perubahan-data']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function changeSectionForIcno(string $icno): ?array
    {
        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT u.id, u.COTableName, u.COActivity, u.COUpadteDate, u.COUpdateCompUser, u.idval,
                   rt.nama AS table_label
            FROM hronline.updatestatus u
            LEFT JOIN hronline.r_table rt ON rt.`table` = u.COTableName
            WHERE u.usern = :icno
            ORDER BY u.COUpadteDate DESC
            LIMIT 100
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $records = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $records[] = [
                'id' => $this->value($row['id'] ?? null, ''),
                'fields' => [
                    ['label' => 'Bahagian', 'value' => $this->value($row['table_label'] ?? null, $this->value($row['COTableName'] ?? null))],
                    ['label' => 'Jadual', 'value' => $this->value($row['COTableName'] ?? null)],
                    ['label' => 'Aktiviti', 'value' => $this->activityLabel($row['COActivity'] ?? null)],
                    ['label' => 'Tarikh', 'value' => $this->dateFormatter->display($row['COUpadteDate'] ?? null, 'Tiada Rekod')],
                    ['label' => 'Dikemaskini Oleh', 'value' => $this->value($row['COUpdateCompUser'] ?? null)],
                    ['label' => 'ID Rekod', 'value' => $this->value($row['idval'] ?? null, '-')],
                ],
            ];
        }

        return [
            'slug' => 'perubahan-data',
            'title' => 'Perubahan Data',
            'staff' => [
                'icno' => $icno,
                'name' => $this->employees->displayName($staff['CONm'] ?? null),
            ],
            'records' => $records,
            'readOnlyNote' => 'Paparan audit ini adalah baca sahaja.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function changeRecordForId(string $id): ?array
    {
        try {
            $row = $this->db->createCommand(
                <<<SQL
                SELECT u.id, u.usern, u.COTableName, u.COActivity, u.COUpadteDate, u.COUpdateCompUser, u.idval,
                       rt.nama AS table_label
                FROM hronline.updatestatus u
                LEFT JOIN hronline.r_table rt ON rt.`table` = u.COTableName
                WHERE u.id = :id
                LIMIT 1
                SQL,
                [':id' => $id],
            )->queryOne();
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($row)) {
            return null;
        }

        $icno = $this->value($row['usern'] ?? null, '');
        $staff = $icno === '' ? null : $this->employees->byIcno($icno);

        return [
            'slug' => 'perubahan-data',
            'title' => 'Perubahan Data',
            'staff' => [
                'icno' => $icno,
                'name' => $this->employees->displayName($staff['CONm'] ?? null),
            ],
            'record' => [
                'id' => $this->value($row['id'] ?? null, ''),
                'fields' => [
                    ['label' => 'Bahagian', 'value' => $this->value($row['table_label'] ?? null, $this->value($row['COTableName'] ?? null))],
                    ['label' => 'Jadual', 'value' => $this->value($row['COTableName'] ?? null)],
                    ['label' => 'Aktiviti', 'value' => $this->activityLabel($row['COActivity'] ?? null)],
                    ['label' => 'Tarikh', 'value' => $this->dateFormatter->display($row['COUpadteDate'] ?? null, 'Tiada Rekod')],
                    ['label' => 'Dikemaskini Oleh', 'value' => $this->value($row['COUpdateCompUser'] ?? null)],
                    ['label' => 'ID Rekod', 'value' => $this->value($row['idval'] ?? null, '-')],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rowByRecordId(string $table, string $id): ?array
    {
        foreach (['id', 'awdId', 'wp_id'] as $column) {
            try {
                $row = $this->db->createCommand(
                    "SELECT * FROM {$table} WHERE {$column} = :id LIMIT 1",
                    [':id' => $id],
                )->queryOne();
            } catch (\Throwable) {
                continue;
            }

            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, string> $labels
     * @param array<string, mixed> $references
     * @return list<array{label: string, value: string}>
     */
    private function fields(array $row, array $labels, array $references): array
    {
        $fields = [];
        $preferred = array_keys($labels);
        $remaining = array_diff(array_keys($row), array_merge(['id', 'awdId', 'ICNO', 'icno'], $preferred));
        foreach (array_merge($preferred, $remaining) as $column) {
            if (!array_key_exists($column, $row)) {
                continue;
            }

            $fields[] = [
                'label' => $labels[$column] ?? $this->labelFromColumn((string) $column),
                'value' => $this->displayFieldValue((string) $column, $row[$column], $references),
            ];
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function recordId(array $row): string
    {
        foreach (['id', 'awdId', 'wp_id'] as $column) {
            $value = $this->value($row[$column] ?? null, '');
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function displayValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $this->dateFormatter->display($value);
        }

        if (is_scalar($value)) {
            $string = trim((string) $value);
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $string) === 1) {
                return $this->dateFormatter->display($string, $string);
            }
        }

        return $this->value($value);
    }

    /**
     * @param array<string, mixed> $references
     */
    private function displayFieldValue(string $column, mixed $value, array $references): string
    {
        $reference = $references[$column] ?? null;
        if (is_array($reference)) {
            $label = $this->referenceLabel($value, $reference);
            if ($label !== null) {
                return $label;
            }
        }

        return $this->displayValue($value);
    }

    /**
     * @param array<string, mixed> $reference
     */
    private function referenceLabel(mixed $value, array $reference): ?string
    {
        $keyValue = $this->value($value, '');
        if ($keyValue === '') {
            return null;
        }

        $values = $reference['values'] ?? null;
        if (is_array($values) && array_key_exists($keyValue, $values)) {
            return $this->value($values[$keyValue] ?? null);
        }

        $table = $this->identifier($reference['table'] ?? null);
        $key = $this->identifier($reference['key'] ?? null);
        $labels = is_array($reference['labels'] ?? null) ? $reference['labels'] : [];
        if ($table === null || $key === null || $labels === []) {
            return null;
        }

        foreach ($labels as $labelColumn) {
            $labelColumn = $this->identifier($labelColumn);
            if ($labelColumn === null) {
                continue;
            }

            $cacheKey = $table . '|' . $key . '|' . $labelColumn . '|' . $keyValue;
            $label = $this->cachedReferenceLabel($cacheKey, $table, $key, $labelColumn, $keyValue);
            if ($label !== null) {
                return $label;
            }
        }

        return null;
    }

    private function cachedReferenceLabel(string $cacheKey, string $table, string $key, string $labelColumn, string $keyValue): ?string
    {
        /** @var array<string, string|null> $cache */
        static $cache = [];

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        try {
            $label = $this->db->createCommand(
                "SELECT {$labelColumn} FROM {$table} WHERE {$key} = :value LIMIT 1",
                [':value' => $keyValue],
            )->queryScalar();
        } catch (\Throwable) {
            $cache[$cacheKey] = null;

            return null;
        }

        $label = $this->value($label, '');
        $cache[$cacheKey] = $label === '' ? null : $label;

        return $cache[$cacheKey];
    }

    private function identifier(mixed $identifier): ?string
    {
        if (!is_string($identifier) || preg_match('/^[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)?$/', $identifier) !== 1) {
            return null;
        }

        return $identifier;
    }

    private function labelFromColumn(string $column): string
    {
        $column = str_replace(['_', '-'], ' ', $column);
        $column = preg_replace('/(?<!^)[A-Z]/', ' $0', $column) ?? $column;

        return ucwords(trim($column));
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

        if (!is_scalar($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
