<?php

declare(strict_types=1);

namespace App\Shared\Perkhidmatan;

use App\Shared\EmployeeRepository;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class PerkhidmatanCrudService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const CONFIG = [
        'status-lantikan' => [
            'title' => 'Status Lantikan',
            'table' => 'hronline.tblrscoapmtstatus',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'status-lantikan',
            'stat' => true,
            'fields' => [
                ['name' => 'ApmtStatusCd', 'label' => 'Status Lantikan', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.appointmentstatus', 'key' => 'ApmtStatusCd', 'label' => 'ApmtStatusNm']],
                ['name' => 'ApmtStatusStDt', 'label' => 'Tarikh Mula Lantikan', 'type' => 'date', 'required' => true],
                ['name' => 'ApmtStatusEndDt', 'label' => 'Tarikh Tamat Lantikan', 'type' => 'date'],
            ],
        ],
        'status-perkhidmatan' => [
            'title' => 'Status Perkhidmatan',
            'table' => 'hronline.tblrscoservstatus',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'status-perkhidmatan',
            'stat' => true,
            'fields' => [
                ['name' => 'ServStatusCd', 'label' => 'Status Perkhidmatan', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.servicestatus', 'key' => 'ServStatusCd', 'label' => 'ServStatusNm']],
                ['name' => 'ServStatusDtl', 'label' => 'Butiran', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.servicestatusdetail', 'key' => 'id', 'label' => 'name']],
                ['name' => 'ServStatusStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
                ['name' => 'sebab_berhenti', 'label' => 'Sebab Berhenti', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.servicestatusdetail', 'key' => 'id', 'label' => 'name']],
            ],
        ],
        'pihak-berkuasa' => [
            'title' => 'Pihak Berkuasa Melantik',
            'table' => 'hronline.tblrscoaptathy',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'pihak-berkuasa',
            'stat' => true,
            'fields' => [
                ['name' => 'AptAthyCd', 'label' => 'Pihak Berkuasa', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.appointingauthority', 'key' => 'AptAthyCd', 'label' => 'AptAthyNm']],
                ['name' => 'AptAthyStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
            ],
        ],
        'beban-perkhidmatan' => [
            'title' => 'Beban Perkhidmatan',
            'table' => 'hronline.tblrscoservload',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'beban-perkhidmatan',
            'stat' => true,
            'fields' => [
                ['name' => 'ServLoadCd', 'label' => 'Beban Perkhidmatan', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.serviceloading', 'key' => 'ServLoadCd', 'label' => 'ServLoadNm']],
                ['name' => 'ServLoadStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
            ],
        ],
        'status-pengesahan' => [
            'title' => 'Status Pengesahan',
            'table' => 'hronline.tblrscoconfirmstatus',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'status-pengesahan',
            'stat' => true,
            'fields' => [
                ['name' => 'ConfirmStatusCd', 'label' => 'Status Pengesahan', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.confirmationstatus', 'key' => 'ConfirmStatusCd', 'label' => 'ConfirmStatusNm']],
                ['name' => 'ConfirmStatusStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
            ],
        ],
        'tempoh-percubaan' => [
            'title' => 'Tempoh Percubaan',
            'table' => 'hronline.tblrscoprobtnperiod',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'tempoh-percubaan',
            'stat' => true,
            'fields' => [
                ['name' => 'ProbtnPeriod', 'label' => 'Tempoh Percubaan', 'type' => 'number', 'required' => true],
                ['name' => 'ProbtnPeriodMin', 'label' => 'Tempoh Minimum', 'type' => 'number'],
                ['name' => 'ProbtnStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
                ['name' => 'ProbtnEndDt', 'label' => 'Tarikh Tamat', 'type' => 'date', 'required' => true],
            ],
        ],
        'jenis-gaji' => [
            'title' => 'Jenis Gaji',
            'table' => 'hronline.tblrscosaltype',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'jenis-gaji',
            'stat' => true,
            'fields' => [
                ['name' => 'SalTypeCd', 'label' => 'Jenis Gaji', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.salarytype', 'key' => 'SalTypeCd', 'label' => 'SalTypeNm']],
                ['name' => 'SalStatus', 'label' => 'Status Gaji', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.salarystatus', 'key' => 'id', 'label' => 'name']],
                ['name' => 'SalTypeStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
            ],
        ],
        'jenis-perkhidmatan' => [
            'title' => 'Skim/Jenis Perkhidmatan',
            'table' => 'hronline.tblrscoservtype',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'jenis-perkhidmatan',
            'stat' => true,
            'fields' => [
                ['name' => 'ServTypeCd', 'label' => 'Jenis Perkhidmatan', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.servicetype', 'key' => 'ServTypeCd', 'label' => 'ServTypeNm']],
                ['name' => 'ServTypeStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
            ],
        ],
        'pergerakan-gaji' => [
            'title' => 'Pergerakan Gaji',
            'table' => 'hronline.tblrscosalmovemth',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'pergerakan-gaji',
            'stat' => true,
            'fields' => [
                ['name' => 'SalMoveMth', 'label' => 'Bulan Pergerakan', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.movemonth', 'key' => 'id', 'label' => 'name']],
                ['name' => 'SalMoveMthType', 'label' => 'Jenis Pergerakan', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.movemonthtype', 'key' => 'id', 'label' => 'name']],
                ['name' => 'SalMoveMthStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
            ],
        ],
        'fail-perkhidmatan' => [
            'title' => 'Fail Perkhidmatan',
            'table' => 'hronline.tblrscofileno',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'fail-perkhidmatan',
            'stat' => true,
            'fields' => [
                ['name' => 'COFileNo', 'label' => 'No. Fail', 'type' => 'text', 'required' => true],
                ['name' => 'COFileNoEftvDt', 'label' => 'Tarikh Kuat Kuasa', 'type' => 'date', 'required' => true],
            ],
        ],
        'status-pencen' => [
            'title' => 'Status Pencen',
            'table' => 'hronline.tblrscopsnstatus',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'status-pencen',
            'stat' => true,
            'fields' => [
                ['name' => 'PsnStatusCd', 'label' => 'Status Pencen', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.pensionstatus', 'key' => 'PsnStatusCd', 'label' => 'PsnStatusNm']],
                ['name' => 'PsnStatusNo', 'label' => 'No. KWAP', 'type' => 'text'],
                ['name' => 'PsnEpfNo', 'label' => 'No. KWSP', 'type' => 'text'],
                ['name' => 'PsnIncomeTaxNo', 'label' => 'No. Cukai Pendapatan', 'type' => 'text'],
                ['name' => 'PsnStatusStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
            ],
        ],
        'mata-gaji' => [
            'title' => 'Mata Gaji',
            'table' => 'hronline.tblrscopersalpoint',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'mata-gaji',
            'stat' => true,
            'fields' => [
                ['name' => 'SalPointCd', 'label' => 'Mata Gaji', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.salarypoint', 'key' => 'SalPointCd', 'label' => 'SalPointAmt']],
                ['name' => 'PerSalPointStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
                ['name' => 'PerSalPointEndDt', 'label' => 'Tarikh Tamat', 'type' => 'date'],
            ],
        ],
        'pihak-berkuasa-pencen' => [
            'title' => 'Pihak Berkuasa Pencen',
            'table' => 'hronline.tblrscopsnathy',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'pihak-berkuasa-pencen',
            'stat' => true,
            'fields' => [
                ['name' => 'PsnAthyCd', 'label' => 'Pihak Berkuasa', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.pensionauthority', 'key' => 'PsnAthyCd', 'label' => 'PsnAthyNm']],
                ['name' => 'PsnAthyStDt', 'label' => 'Tarikh Mula', 'type' => 'date', 'required' => true],
            ],
        ],
        'umur-bersara' => [
            'title' => 'Umur Bersara',
            'table' => 'hronline.tblrscoretireage',
            'icnoColumn' => 'ICNO',
            'idColumn' => 'id',
            'backRoute' => 'umur-bersara',
            'stat' => true,
            'fields' => [
                ['name' => 'RetireAgeCd', 'label' => 'Umur Bersara', 'type' => 'select', 'required' => true, 'reference' => ['table' => 'hronline.retireage', 'key' => 'RetireAgeCd', 'labels' => ['Name', 'RetireAgeDesc']]],
                ['name' => 'CORetireAgeEftvDt', 'label' => 'Tarikh Kuat Kuasa', 'type' => 'date', 'required' => true],
            ],
        ],
    ];

    /**
     * @var array<string, string>
     */
    private const DEFERRED = [
        'status-sandangan' => 'Status Sandangan mempunyai side effect Yii2 ke rekod gaji, identiti staf, PKS dan fail. CRUD belum dibuka sehingga workflow penuh dipadankan.',
        'waktu-bekerja' => 'Waktu Bekerja berada dalam skema attendance dan audit HR lama tidak sama. CRUD belum dibuka sehingga flow attendance dipadankan.',
        'sejarah-penempatan' => 'Sejarah Penempatan mempunyai side effect penempatan, kelulusan dan surat/PDF. CRUD belum dibuka sehingga workflow penuh dipadankan.',
        'perubahan-data' => 'Perubahan Data ialah paparan audit sahaja dan tidak boleh dikemaskini atau dipadam dari modul ini.',
    ];

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private PerkhidmatanAuditService $audit,
    ) {}

    /**
     * @return array{enabled: bool, message: string, createUrl: string}
     */
    public function management(string $slug, string $icno, bool $adminMode): array
    {
        if (!$adminMode) {
            return ['enabled' => false, 'message' => '', 'createUrl' => ''];
        }

        if (isset(self::CONFIG[$slug])) {
            return [
                'enabled' => true,
                'message' => '',
                'createUrl' => '/perkhidmatan/' . $slug . '/tambah?icno=' . rawurlencode($icno),
            ];
        }

        return [
            'enabled' => false,
            'message' => self::DEFERRED[$slug] ?? 'CRUD untuk item ini belum tersedia.',
            'createUrl' => '',
        ];
    }

    public function isEditable(string $slug): bool
    {
        return isset(self::CONFIG[$slug]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function form(string $slug, string $icno, ?string $id = null): ?array
    {
        $config = self::CONFIG[$slug] ?? null;
        if ($config === null) {
            return null;
        }

        $values = $id === null ? $this->defaultValues($config, $icno) : $this->valuesForEdit($config, $id);
        if ($values === null) {
            return null;
        }

        if ($id !== null) {
            $icno = (string) ($values[(string) $config['icnoColumn']] ?? '');
        }

        $staff = $this->employees->byIcno($icno);
        if ($staff === null) {
            return null;
        }

        return [
            'slug' => $slug,
            'mode' => $id === null ? 'create' : 'update',
            'title' => (string) $config['title'],
            'staff' => [
                'icno' => $icno,
                'name' => $this->employees->displayName($staff['CONm'] ?? null),
            ],
            'fields' => $this->fieldsWithOptions($config),
            'values' => $values,
            'errors' => [],
            'action' => $id === null
                ? '/perkhidmatan/' . $slug . '/tambah?icno=' . rawurlencode($icno)
                : '/perkhidmatan/' . $slug . '/kemaskini?id=' . rawurlencode($id),
            'backUrl' => '/perkhidmatan/' . $slug . '/view?icno=' . rawurlencode($icno),
        ];
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $context
     * @return array{success: bool, form: array<string, mixed>|null}
     */
    public function save(string $slug, string $icno, ?string $id, array $body, array $context): array
    {
        $config = self::CONFIG[$slug] ?? null;
        if ($config === null) {
            return ['success' => false, 'form' => null];
        }

        $record = $this->recordFromBody($config, $body);
        $errors = $this->validate($config, $record);
        if ($errors !== []) {
            $form = $this->form($slug, $icno, $id);
            if ($form !== null) {
                $form['values'] = array_merge($form['values'], $this->stringValues($record));
                $form['errors'] = $errors;
            }

            return ['success' => false, 'form' => $form];
        }

        $table = (string) $config['table'];
        $icnoColumn = (string) $config['icnoColumn'];
        $idColumn = (string) $config['idColumn'];
        $record[$icnoColumn] = $icno;
        $isCreate = $id === null || $id === '';

        $savedId = $this->db->transaction(function () use ($isCreate, $table, $idColumn, $id, $record, $config, $icno, $context): string {
            if ($isCreate) {
                $this->db->createCommand()->insert($table, $record)->execute();
                $savedId = (string) $this->db->createCommand('SELECT LAST_INSERT_ID()')->queryScalar();
                if ($savedId === '' || $savedId === '0') {
                    $savedId = $this->value($record[$idColumn] ?? '');
                }

                $this->audit->logCreate($table, $icno, $savedId, $record, $context);
                if (($config['stat'] ?? false) === true) {
                    $this->audit->touchStatOnCreate($table, $icno, $savedId, ['status' => '0']);
                }

                return $savedId;
            }

            $old = $this->rowById($config, (string) $id);
            if ($old === null) {
                return '';
            }

            $this->db->createCommand()->update($table, $record, [$idColumn => $id])->execute();
            $this->audit->logUpdate($table, $icno, (string) $id, $old, $record, $context);
            if (($config['stat'] ?? false) === true) {
                $this->audit->touchStatOnUpdate($table, $icno, (string) $id, ['status' => '1']);
            }

            return (string) $id;
        });

        return ['success' => $savedId !== '', 'form' => null];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function delete(string $slug, string $id, array $context): string
    {
        $config = self::CONFIG[$slug] ?? null;
        if ($config === null || $id === '') {
            return '';
        }

        $row = $this->rowById($config, $id);
        if ($row === null) {
            return '';
        }

        $table = (string) $config['table'];
        $icnoColumn = (string) $config['icnoColumn'];
        $idColumn = (string) $config['idColumn'];
        $icno = $this->value($row[$icnoColumn] ?? '');
        if ($icno === '') {
            return '';
        }

        $this->db->transaction(function () use ($table, $idColumn, $id, $row, $icno, $context, $config): void {
            $this->db->createCommand()->delete($table, [$idColumn => $id])->execute();
            $this->audit->logDelete($table, $icno, $id, $row, $context);
            if (($config['stat'] ?? false) === true) {
                $this->audit->deleteStat($table, $icno, $id);
            }
        });

        return $icno;
    }

    public function updateUrl(string $slug, string $id): string
    {
        return $this->isEditable($slug) && $id !== '' ? '/perkhidmatan/' . $slug . '/kemaskini?id=' . rawurlencode($id) : '';
    }

    public function deleteUrl(string $slug, string $id): string
    {
        return $this->isEditable($slug) && $id !== '' ? '/perkhidmatan/' . $slug . '/padam?id=' . rawurlencode($id) : '';
    }

    public function detailUrl(string $slug, string $id): string
    {
        return $id !== '' ? '/perkhidmatan/' . $slug . '/lihat?id=' . rawurlencode($id) : '';
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    private function defaultValues(array $config, string $icno): array
    {
        $values = [(string) $config['icnoColumn'] => $icno];
        foreach ($config['fields'] as $field) {
            if (!is_array($field)) {
                continue;
            }

            $values[(string) $field['name']] = '';
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, string>|null
     */
    private function valuesForEdit(array $config, string $id): ?array
    {
        $row = $this->rowById($config, $id);
        if ($row === null) {
            return null;
        }

        $values = ['id' => $id];
        foreach ($config['fields'] as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = (string) $field['name'];
            $values[$name] = $this->value($row[$name] ?? '', '');
        }

        $values[(string) $config['icnoColumn']] = $this->value($row[(string) $config['icnoColumn']] ?? '', '');

        return $values;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>|null
     */
    private function rowById(array $config, string $id): ?array
    {
        try {
            $row = $this->db->createCommand(
                'SELECT * FROM ' . (string) $config['table'] . ' WHERE ' . (string) $config['idColumn'] . ' = :id LIMIT 1',
                [':id' => $id],
            )->queryOne();
        } catch (\Throwable) {
            return null;
        }

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $config
     * @return list<array<string, mixed>>
     */
    private function fieldsWithOptions(array $config): array
    {
        $fields = [];
        foreach ($config['fields'] as $field) {
            if (!is_array($field)) {
                continue;
            }

            $field['options'] = isset($field['reference']) && is_array($field['reference'])
                ? $this->referenceOptions($field['reference'])
                : [];
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $reference
     * @return list<array{id: string, name: string}>
     */
    private function referenceOptions(array $reference): array
    {
        $table = $this->identifier($reference['table'] ?? null);
        $key = $this->identifier($reference['key'] ?? null);
        $labels = is_array($reference['labels'] ?? null) ? $reference['labels'] : [$reference['label'] ?? null];
        if ($table === null || $key === null || $labels === []) {
            return [];
        }

        $rows = [];
        $activeLabel = null;
        foreach ($labels as $label) {
            $label = $this->identifier($label);
            if ($label === null) {
                continue;
            }

            try {
                $rows = $this->db->createCommand("SELECT {$key}, {$label} FROM {$table} ORDER BY {$label}")->queryAll();
                $activeLabel = $label;
                break;
            } catch (\Throwable) {
                $rows = [];
            }
        }

        if ($activeLabel === null) {
            return [];
        }

        $options = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = $this->value($row[$key] ?? '', '');
            $name = $this->value($row[$activeLabel] ?? '', '');
            if ($id === '' || $name === '') {
                continue;
            }

            $options[] = ['id' => $id, 'name' => $name];
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function recordFromBody(array $config, array $body): array
    {
        $record = [];
        foreach ($config['fields'] as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = (string) $field['name'];
            $value = $this->value($body[$name] ?? '', '');
            $record[$name] = $value === '' ? null : $value;
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $record
     * @return array<string, string>
     */
    private function validate(array $config, array $record): array
    {
        $errors = [];
        foreach ($config['fields'] as $field) {
            if (!is_array($field) || ($field['required'] ?? false) !== true) {
                continue;
            }

            $name = (string) $field['name'];
            if ($this->value($record[$name] ?? '', '') === '') {
                $errors[$name] = 'Ruangan ini adalah mandatori.';
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, string>
     */
    private function stringValues(array $record): array
    {
        $values = [];
        foreach ($record as $key => $value) {
            $values[(string) $key] = $this->value($value, '');
        }

        return $values;
    }

    private function identifier(mixed $identifier): ?string
    {
        if (!is_string($identifier) || preg_match('/^[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)?$/', $identifier) !== 1) {
            return null;
        }

        return $identifier;
    }

    private function value(mixed $value, string $fallback = 'Tiada Rekod'): string
    {
        if ($value === null || !is_scalar($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
