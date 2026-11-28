<?php

declare(strict_types=1);

namespace App\Shared;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiApplicationService
{
    private const ACTIVE_PICKER_IDS = [1, 2, 20, 21, 28];

    private const DEFAULT_IDS = [
        'ck' => 18,
        'cb' => 28,
        'cr1' => 1,
        'cr1-aca' => 1,
        'cr2' => 2,
        'ctr' => 3,
        'cs1' => 20,
        'cs2' => 21,
        'hso' => 58,
    ];

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private MediaHostFileService $fileService,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function selectableLeaveTypes(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT jenis_cuti_id, jenis_cuti_nama, jenis_cuti_catatan, jenis_cuti_catatan_bi, action, form_type
            FROM hrm.cuti_leave_type
            WHERE jenis_cuti_papar IN (1, 2)
              AND jenis_cuti_id IN (1, 2, 20, 21, 28)
            ORDER BY CASE jenis_cuti_id
                WHEN 1 THEN 1
                WHEN 2 THEN 2
                WHEN 20 THEN 3
                WHEN 21 THEN 4
                WHEN 28 THEN 5
                ELSE 99
            END
            SQL,
        )->queryAll();

        $types = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (int) ($row['jenis_cuti_id'] ?? 0);
            $route = match ($id) {
                1 => $this->isAcademicNonAdmin($icno) ? '/cuti/individu/cr1-aca?id=1' : '/cuti/individu/cr1?id=1',
                2 => '/cuti/individu/cr2?id=2',
                20 => '/cuti/individu/cs1?id=20',
                21 => '/cuti/individu/cs2?id=21',
                28 => '/cuti/individu/cb?id=28',
                default => CutiMenuService::normalizeRoute('cuti/individu/' . $this->value($row['action'] ?? null, 'pilih')),
            };

            $types[] = [
                'id' => (string) $id,
                'name' => $this->value($row['jenis_cuti_catatan'] ?? $row['jenis_cuti_nama'] ?? null),
                'nameEn' => $this->value($row['jenis_cuti_catatan_bi'] ?? null, ''),
                'legacyRoute' => 'cuti/individu/' . $this->value($row['action'] ?? null, 'pilih'),
                'formType' => $this->value($row['form_type'] ?? null, ''),
                'url' => $route,
                'local' => str_starts_with($route, '/'),
            ];
        }

        return $types;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>|null
     */
    public function formData(string $icno, string $path, array $query, array $values = [], array $errors = []): ?array
    {
        $key = $this->keyFromPath($path);
        if ($key === null) {
            return null;
        }

        $typeId = $this->typeId($key, $query);
        $leaveType = $this->leaveType($typeId);
        if ($leaveType === null) {
            return null;
        }

        $staff = $this->staffRow($icno);
        if ($staff === null) {
            return null;
        }

        $values = $values === [] ? $this->defaultValues($typeId) : $values;
        $assignees = $this->assignees($key, $typeId, $icno, $staff, $values);

        return [
            'key' => $key,
            'title' => $this->titleFor($key, $leaveType),
            'leaveType' => $leaveType,
            'staff' => [
                'icno' => $icno,
                'name' => $this->employees->displayName($staff['CONm'] ?? null),
                'department' => $this->value($staff['department_name'] ?? null),
            ],
            'values' => $values,
            'errors' => $errors,
            'options' => [
                'staff' => $this->staffOptions($icno),
                'sickVerifiers' => $this->sickVerifierOptions((int) ($staff['DeptId'] ?? 0)),
                'maternityNumbers' => $this->maternityNumberOptions(),
                'durations' => [
                    ['id' => '30', 'name' => '30 Hari'],
                    ['id' => '60', 'name' => '60 Hari'],
                    ['id' => '90', 'name' => '90 Hari'],
                ],
                'emergencyReasons' => [
                    'Banjir',
                    'Gempa Bumi',
                    'Ribut',
                    'Perintah berkurung',
                    'Tanah runtuh',
                    'Jambatan runtuh',
                    'Perintah Sekatan Am',
                    'Lain-lain',
                ],
            ],
            'assignees' => $assignees,
            'maternityBalance' => max(0, 360 - $this->maternityUsed($icno)),
            'legacyUrl' => LegacyUrl::to($path) . '?id=' . $typeId,
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $input
     * @return array{success: bool, values: array<string, mixed>, errors: array<string, string>, id?: string}
     */
    public function submit(
        string $icno,
        string $path,
        array $query,
        array $input,
        ?UploadedFileInterface $file,
        string $ipAddress,
    ): array {
        $key = $this->keyFromPath($path);
        if ($key === null) {
            return ['success' => false, 'values' => [], 'errors' => ['general' => 'Borang cuti tidak disokong.']];
        }

        $typeId = $this->typeId($key, $query);
        $values = $this->normalizeValues($typeId, $input);
        $staff = $this->staffRow($icno);
        if ($staff === null) {
            return ['success' => false, 'values' => $values, 'errors' => ['general' => 'Rekod staf tidak dijumpai.']];
        }

        $errors = $this->validate($key, $typeId, $icno, $staff, $values, $file);
        if ($errors !== []) {
            return ['success' => false, 'values' => $values, 'errors' => $errors];
        }

        $start = $this->requireDate($values['startDate']);
        $end = $key === 'cb'
            ? $start->modify('+' . ((int) $values['duration'] - 1) . ' days')
            : $this->requireDate($values['endDate']);
        $fullDate = $this->formatFullDate($start, $end);
        $tempoh = $this->durationFor($key, $icno, $start, $end);
        $assignees = $this->assignees($key, $typeId, $icno, $staff, $values, $tempoh);

        $fileHash = '';
        if ($this->requiresFile($key)) {
            $upload = $this->fileService->uploadDocument(
                $file,
                $this->uploadDirectory($key),
                $icno,
                $this->uploadCategory($key),
                $this->uploadDescription($key),
            );

            if ($upload['success'] !== true) {
                return [
                    'success' => false,
                    'values' => $values,
                    'errors' => ['file' => $upload['message'] ?? 'Muat naik dokumen gagal.'],
                ];
            }

            $fileHash = (string) $upload['hash'];
        }

        $remark = $key === 'ck' ? $this->emergencyRemark($values) : (string) $values['remark'];
        $now = date('Y-m-d H:i:s');
        $record = [
            'jenis_cuti_id' => $typeId,
            'icno' => $icno,
            'full_date' => $fullDate,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'tempoh' => $tempoh,
            'cb_cuti_bersalin_bilangan_id' => $values['cbCutiBilanganId'] !== '' ? $values['cbCutiBilanganId'] : null,
            'cb_kelahiran_anak_bilangan_id' => $values['cbAnakBilanganId'] !== '' ? $values['cbAnakBilanganId'] : null,
            'remark' => $remark,
            'destination' => $values['destination'] !== '' ? $values['destination'] : null,
            'type' => $values['type'] === '1' ? 1 : 0,
            'ganti_by' => $values['gantiBy'] !== '' ? $values['gantiBy'] : null,
            'semakan_by' => $assignees['semakanBy'] !== '' ? $assignees['semakanBy'] : null,
            'peraku_by' => $assignees['perakuBy'] !== '' ? $assignees['perakuBy'] : null,
            'lulus_by' => $assignees['lulusBy'] !== '' ? $assignees['lulusBy'] : null,
            'status' => $assignees['status'],
            'mohon_dt' => $now,
            'file_hashcode' => $fileHash !== '' ? $fileHash : null,
        ];

        $id = $this->db->transaction(function () use ($record, $icno, $fullDate, $start, $end, $now, $ipAddress): string {
            $this->db->createCommand()->insert('hrm.cuti_tbl_records', $record)->execute();
            $id = (string) $this->db->getLastInsertId();

            $this->db->createCommand()->insert('hrm.cuti_log', [
                'ntf_session_id' => $icno,
                'ntf_tindakan' => 'Mohon',
                'ntf_status' => 'ENTRY',
                'ntf_cr_id' => $id,
                'ntf_datetime' => $now,
                'ntf_ip' => substr($ipAddress, 0, 20),
                'full_date' => $fullDate,
                'start_date' => $start->format('Y-m-d H:i:s'),
                'end_date' => $end->format('Y-m-d H:i:s'),
            ])->execute();

            return $id;
        });

        return ['success' => true, 'values' => $values, 'errors' => [], 'id' => $id];
    }

    public function isAcademicNonAdmin(string $icno): bool
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT g.job_category,
                   admin.id AS adminpost_id
            FROM hronline.tblprcobiodata bio
            LEFT JOIN hronline.gredjawatan g ON g.id = bio.gredJawatan
            LEFT JOIN hronline.tblrscoadminpost admin ON admin.ICNO = bio.ICNO AND admin.flag = 1
            WHERE bio.ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        return is_array($row)
            && (string) ($row['job_category'] ?? '') === '1'
            && $this->value($row['adminpost_id'] ?? null, '') === '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function staffRow(string $icno): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT bio.*, dept.fullname AS department_name, dept.chief AS department_chief, dept.pp AS department_pp,
                   dept230.chief AS bsm_chief
            FROM hronline.tblprcobiodata bio
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hronline.department dept230 ON dept230.id = 230
            WHERE bio.ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, string>|null
     */
    private function leaveType(int $id): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT jenis_cuti_id, jenis_cuti_nama, jenis_cuti_catatan, jenis_cuti_catatan_bi, action, form_type
            FROM hrm.cuti_leave_type
            WHERE jenis_cuti_id = :id
            LIMIT 1
            SQL,
            [':id' => $id],
        )->queryOne();

        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (string) ($row['jenis_cuti_id'] ?? $id),
            'code' => $this->value($row['jenis_cuti_nama'] ?? null, ''),
            'name' => $this->value($row['jenis_cuti_catatan'] ?? $row['jenis_cuti_nama'] ?? null),
            'nameEn' => $this->value($row['jenis_cuti_catatan_bi'] ?? null, ''),
            'action' => $this->value($row['action'] ?? null, ''),
            'formType' => $this->value($row['form_type'] ?? null, ''),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeValues(int $typeId, array $input): array
    {
        $reasons = $input['reasons'] ?? [];
        if (!is_array($reasons)) {
            $reasons = [];
        }

        return [
            'typeId' => (string) $typeId,
            'startDate' => $this->formValue($input['startDate'] ?? null),
            'endDate' => $this->formValue($input['endDate'] ?? null),
            'remark' => $this->formValue($input['remark'] ?? null),
            'gantiBy' => $this->formValue($input['gantiBy'] ?? null),
            'destination' => $this->formValue($input['destination'] ?? null),
            'semakanBy' => $this->formValue($input['semakanBy'] ?? null),
            'duration' => $this->formValue($input['duration'] ?? null),
            'cbCutiBilanganId' => $this->formValue($input['cbCutiBilanganId'] ?? null),
            'cbAnakBilanganId' => $this->formValue($input['cbAnakBilanganId'] ?? null),
            'type' => $this->formValue($input['type'] ?? '0'),
            'others' => $this->formValue($input['others'] ?? null),
            'reasons' => array_values(array_filter(array_map(fn (mixed $value): string => $this->formValue($value), $reasons))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultValues(int $typeId): array
    {
        return [
            'typeId' => (string) $typeId,
            'startDate' => '',
            'endDate' => '',
            'remark' => '',
            'gantiBy' => '',
            'destination' => '',
            'semakanBy' => '',
            'duration' => '',
            'cbCutiBilanganId' => '',
            'cbAnakBilanganId' => '',
            'type' => '0',
            'others' => '',
            'reasons' => [],
        ];
    }

    /**
     * @param array<string, mixed> $staff
     * @param array<string, mixed> $values
     * @return array<string, string>
     */
    private function validate(
        string $key,
        int $typeId,
        string $icno,
        array $staff,
        array $values,
        ?UploadedFileInterface $file,
    ): array {
        $errors = [];

        if ($key === 'cb') {
            if ($this->value($staff['GenderCd'] ?? null, '') === 'L') {
                $errors['general'] = 'Harap maaf, Cuti Bersalin hanya untuk staf perempuan.';
            }
            if ($this->value($staff['MrtlStatusCd'] ?? null, '') === '1') {
                $errors['general'] = 'Sila kemaskini status perkahwinan sebelum memohon Cuti Bersalin.';
            }
            if (!$this->isValidDate($values['startDate'])) {
                $errors['startDate'] = 'Sila pilih tarikh mula.';
            }
            if (!in_array($values['duration'], ['30', '60', '90'], true)) {
                $errors['duration'] = 'Sila pilih tempoh Cuti Bersalin.';
            }
            foreach (['cbCutiBilanganId' => 'Sila pilih Cuti Bersalin untuk kali.', 'cbAnakBilanganId' => 'Sila pilih kelahiran anak.'] as $field => $message) {
                if ($values[$field] === '') {
                    $errors[$field] = $message;
                }
            }
            if ($values['remark'] === '') {
                $errors['remark'] = 'Sila nyatakan catatan/tujuan.';
            }
            if ($values['duration'] !== '' && $this->maternityUsed($icno) + (int) $values['duration'] > 360) {
                $errors['duration'] = 'Kelayakan Cuti Bersalin tidak mencukupi.';
            }
            $sequenceError = $this->maternitySequenceError($icno, $values['cbCutiBilanganId'], $values['cbAnakBilanganId']);
            if ($sequenceError !== null) {
                $errors['cbCutiBilanganId'] = $sequenceError;
            }
        } else {
            if (!$this->isValidDate($values['startDate'])) {
                $errors['startDate'] = 'Sila pilih tarikh mula.';
            }
            if (!$this->isValidDate($values['endDate'])) {
                $errors['endDate'] = 'Sila pilih tarikh tamat.';
            }
            if (!isset($errors['startDate'], $errors['endDate']) && $values['startDate'] > $values['endDate']) {
                $errors['endDate'] = 'Tarikh tamat tidak boleh lebih awal daripada tarikh mula.';
            }
        }

        if (!isset($errors['startDate'], $errors['endDate']) && $values['startDate'] !== '') {
            $start = $this->requireDate($values['startDate']);
            $end = $key === 'cb'
                ? $start->modify('+' . ((int) ($values['duration'] !== '' ? $values['duration'] : 1) - 1) . ' days')
                : $this->requireDate($values['endDate']);

            if ($this->hasOverlap($icno, $start, $end)) {
                $errors['startDate'] = 'Tarikh cuti bertindih dengan rekod cuti lain.';
            }

            if (in_array($key, ['cr1', 'cr1-aca', 'cr2'], true)) {
                $annualError = $this->annualLeaveError($icno, $start, $end);
                if ($annualError !== null) {
                    $errors['startDate'] = $annualError;
                }
            }

            if ($key === 'cs1') {
                $this->validateSickCap($errors, $icno, 20, $start, $end, 15);
            }

            if (in_array($key, ['cs2', 'hso'], true)) {
                $cap = in_array((string) ($staff['statLantikan'] ?? ''), ['3', '6', '7'], true) ? 75 : 90;
                $this->validateSickCap($errors, $icno, 21, $start, $end, $cap);
            }
        }

        if (in_array($key, ['cr1', 'cr2'], true) && $values['gantiBy'] === '') {
            $errors['gantiBy'] = 'Pengganti adalah wajib.';
        }

        if ($key === 'cr2' && $values['destination'] === '') {
            $errors['destination'] = 'Sila nyatakan destinasi luar negara.';
        }

        if (in_array($key, ['cr1', 'cr1-aca', 'cr2', 'ctr', 'cb', 'cs1', 'cs2', 'hso'], true) && $values['remark'] === '') {
            $errors['remark'] = 'Sila nyatakan catatan/tujuan.';
        }

        if (in_array($key, ['cs1', 'cs2', 'hso'], true) && $values['semakanBy'] === '') {
            $errors['semakanBy'] = 'Pegawai penyemak adalah wajib.';
        }

        if ($key === 'ck' && $values['reasons'] === []) {
            $errors['reasons'] = 'Sila pilih sekurang-kurangnya satu sebab.';
        }

        $fileError = $this->validateUploadedFile($file, $this->requiresFile($key));
        if ($fileError !== null) {
            $errors['file'] = $fileError;
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $staff
     * @param array<string, mixed> $values
     * @return array{semakanBy: string, semakanName: string, perakuBy: string, perakuName: string, lulusBy: string, lulusName: string, status: string}
     */
    private function assignees(
        string $key,
        int $typeId,
        string $icno,
        array $staff,
        array $values,
        ?int $tempoh = null,
    ): array {
        $set = $this->setPegawai($icno);
        $departmentChief = $this->value($staff['department_chief'] ?? null, '');
        $departmentPp = $this->value($staff['department_pp'] ?? null, '');
        $bsmChief = $this->value($staff['bsm_chief'] ?? null, '');
        $status = 'ENTRY';
        $semakanBy = '';
        $perakuBy = '';
        $lulusBy = '';

        if ($key === 'cr1') {
            $perakuBy = $this->value($set['peraku_icno'] ?? null, '');
            $lulusBy = $this->value($set['pelulus_icno'] ?? null, '');
            if (($tempoh ?? 0) >= 14) {
                $lulusBy = $this->tindakanApprover('8') ?: $lulusBy;
                $status = 'BSMCHECK';
            }
        } elseif ($key === 'cr1-aca') {
            $perakuBy = $this->value($set['peraku_icno'] ?? null, '');
            $lulusBy = $this->value($set['pelulus_icno'] ?? null, '');
            $status = $perakuBy !== '' ? 'AGREED' : 'VERIFIED';
            if (($tempoh ?? 0) >= 14) {
                $lulusBy = $this->tindakanApprover('8') ?: $lulusBy;
                $status = 'BSMCHECK';
            }
        } elseif ($key === 'cr2') {
            if ($departmentChief !== '' && $departmentChief === $icno) {
                $chiefSet = $this->setPegawai($departmentChief);
                $perakuBy = $this->value($chiefSet['pelulus_icno'] ?? null, '');
                $lulusBy = $this->activeTindakanApprover() ?: $this->managementIcno('3');
            } else {
                $perakuBy = $departmentChief;
                $lulusBy = $this->managementIcno('3');
            }
        } elseif (in_array($key, ['cs1', 'cs2', 'hso'], true)) {
            $semakanBy = (string) $values['semakanBy'];
            $lulusBy = $this->value($set['pelulus_icno'] ?? null, '');
        } elseif ($key === 'cb') {
            $status = 'BSMCHECK';
            $semakanBy = $this->managementForAction('cb') ?: $this->managementIcno('1', 'cuti');
            $perakuBy = $departmentChief;
            $lulusBy = $bsmChief;
        } elseif ($key === 'ck') {
            $status = 'BSMCHECK';
            $semakanBy = $this->managementForAction('cb') ?: $this->managementIcno('1', 'cuti');
            $perakuBy = $departmentChief;
            $lulusBy = $bsmChief;
        } elseif ($key === 'ctr') {
            $perakuBy = $departmentPp;
            $lulusBy = $bsmChief;
        }

        return [
            'semakanBy' => $semakanBy,
            'semakanName' => $this->staffName($semakanBy),
            'perakuBy' => $perakuBy,
            'perakuName' => $this->staffName($perakuBy),
            'lulusBy' => $lulusBy,
            'lulusName' => $this->staffName($lulusBy),
            'status' => $status,
        ];
    }

    private function durationFor(string $key, string $icno, DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        if ($key === 'cb') {
            return $this->inclusiveDays($start, $end);
        }

        if (in_array($key, ['cs1', 'cs2', 'hso'], true)) {
            return $this->isShiftStaff($icno, $start) ? $this->inclusiveDays($start, $end) : $this->weekdaysExcludingWeekends($start, $end);
        }

        if (in_array($key, ['cr1', 'cr1-aca', 'cr2'], true)) {
            if ($this->isShiftStaff($icno, $start)) {
                return $this->inclusiveDays($start, $end);
            }
            $days = $this->weekdaysExcludingWeekends($start, $end);
            return $this->hasCampusHoliday($icno, $start, $end) ? max(0, $days - 1) : $days;
        }

        if ($key === 'ctr') {
            return $this->weekdaysIncludingHolidays($start, $end);
        }

        return $this->weekdaysExcludingWeekends($start, $end);
    }

    private function annualLeaveError(string $icno, DateTimeImmutable $start, DateTimeImmutable $end): ?string
    {
        if ($start->format('Y') === date('Y') && (int) $end->format('Y') > (int) date('Y')) {
            return 'Tarikh cuti tidak boleh melebihi tahun semasa.';
        }

        if ($this->db->createCommand(
            'SELECT 1 FROM hrm.cuti_gcr_application WHERE pemohon_icno = :icno AND gcr_year = :year LIMIT 1',
            [':icno' => $icno, ':year' => $start->format('Y')],
        )->queryScalar()) {
            return 'Anda telah membuat permohonan GCR & CBTH. Sila hubungi penyelia cuti.';
        }

        $entitlement = $this->latestEntitlement($icno, $start);
        if ($entitlement === null) {
            return 'Anda masih tidak mempunyai kelayakan cuti.';
        }

        if ($start->format('Y-m-d') > $this->value($entitlement['layak_tamat'] ?? null, '')) {
            return 'Kelayakan cuti anda perlu dikemaskini.';
        }

        if ($end->format('Y-m-d') > $this->value($entitlement['layak_tamat'] ?? null, '')) {
            return 'Tarikh bercuti melebihi tarikh kelayakan.';
        }

        $total = (int) ($entitlement['layak_cuti'] ?? 0)
            + (int) ($entitlement['layak_bawa_lepas'] ?? 0)
            + (int) ($entitlement['layak_selaras'] ?? 0);
        $used = $this->usedAnnualLeave($icno, (string) $entitlement['layak_mula'], (string) $entitlement['layak_tamat']);
        $balance = $total - $used - (int) ($entitlement['layak_gcr'] ?? 0);
        $duration = $this->weekdaysExcludingWeekends($start, $end);

        if ($balance <= 0) {
            return 'Kelayakan cuti anda sudah habis digunakan.';
        }

        if ($duration > $balance) {
            return 'Baki cuti tidak mencukupi. Baki Cuti = ' . $balance;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestEntitlement(string $icno, DateTimeImmutable $start): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT *
            FROM hrm.cuti_entitlement
            WHERE layak_icno = :icno
              AND YEAR(layak_tamat) >= YEAR(:date)
            ORDER BY layak_tamat DESC
            LIMIT 1
            SQL,
            [':icno' => $icno, ':date' => $start->format('Y-m-d')],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    private function usedAnnualLeave(string $icno, string $start, string $end): int
    {
        return (int) $this->db->createCommand(
            <<<SQL
            SELECT COALESCE(SUM(tempoh), 0)
            FROM hrm.cuti_tbl_records
            WHERE icno = :icno
              AND status != 'REJECTED'
              AND start_date BETWEEN :startDate AND :endDate
              AND jenis_cuti_id IN (1, 2, 42)
            SQL,
            [':icno' => $icno, ':startDate' => $start, ':endDate' => $end],
        )->queryScalar();
    }

    /**
     * @param array<string, string> $errors
     */
    private function validateSickCap(array &$errors, string $icno, int $typeId, DateTimeImmutable $start, DateTimeImmutable $end, int $cap): void
    {
        $used = (int) $this->db->createCommand(
            <<<SQL
            SELECT COALESCE(SUM(tempoh), 0)
            FROM hrm.cuti_tbl_records
            WHERE icno = :icno
              AND jenis_cuti_id = :typeId
              AND YEAR(start_date) = :year
              AND status != 'REJECTED'
            SQL,
            [':icno' => $icno, ':typeId' => $typeId, ':year' => $start->format('Y')],
        )->queryScalar();

        $duration = $this->weekdaysExcludingWeekends($start, $end);
        if ($used + $duration > $cap) {
            $errors['startDate'] = 'Kelayakan cuti sakit tidak mencukupi. Baki = ' . max(0, $cap - $used);
        }
    }

    private function hasOverlap(string $icno, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        return (bool) $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM hrm.cuti_tbl_records
            WHERE icno = :icno
              AND status != 'REJECTED'
              AND start_date <= :endDate
              AND end_date >= :startDate
            LIMIT 1
            SQL,
            [':icno' => $icno, ':startDate' => $start->format('Y-m-d'), ':endDate' => $end->format('Y-m-d')],
        )->queryScalar();
    }

    private function maternityUsed(string $icno): int
    {
        return (int) $this->db->createCommand(
            "SELECT COALESCE(SUM(tempoh), 0) FROM hrm.cuti_tbl_records WHERE icno = :icno AND jenis_cuti_id = 28 AND status != 'REJECTED'",
            [':icno' => $icno],
        )->queryScalar();
    }

    private function maternitySequenceError(string $icno, string $cbId, string $anakId): ?string
    {
        if ($cbId === '' || $anakId === '') {
            return null;
        }

        $selected = $this->db->createCommand(
            'SELECT id, bil FROM hrm.cuti_ref_bersalin_bilangan WHERE id IN (:cb, :anak)',
            [':cb' => $cbId, ':anak' => $anakId],
        )->queryAll();

        $bil = [];
        foreach ($selected as $row) {
            if (is_array($row)) {
                $bil[(string) $row['id']] = (int) $row['bil'];
            }
        }

        if (!isset($bil[$cbId], $bil[$anakId])) {
            return 'Maklumat Cuti Bersalin yang dipilih tidak sah.';
        }

        $row = $this->db->createCommand(
            <<<SQL
            SELECT MAX(cb_ref.bil) AS max_cb, MAX(anak_ref.bil) AS max_anak
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hrm.cuti_ref_bersalin_bilangan cb_ref ON cb_ref.id = r.cb_cuti_bersalin_bilangan_id
            LEFT JOIN hrm.cuti_ref_bersalin_bilangan anak_ref ON anak_ref.id = r.cb_kelahiran_anak_bilangan_id
            WHERE r.icno = :icno
              AND r.jenis_cuti_id = 28
              AND r.status != 'REJECTED'
              AND r.cb_cuti_bersalin_bilangan_id IS NOT NULL
              AND r.cb_kelahiran_anak_bilangan_id IS NOT NULL
            SQL,
            [':icno' => $icno],
        )->queryOne();

        if (is_array($row) && (int) ($row['max_cb'] ?? 0) > 0 && $bil[$cbId] <= (int) $row['max_cb']) {
            return 'Rekod Cuti Bersalin terdahulu telah mencapai kali ke-' . (int) $row['max_cb'] . '. Sila pilih kali seterusnya.';
        }

        if (is_array($row) && (int) ($row['max_anak'] ?? 0) > 0 && $bil[$anakId] <= (int) $row['max_anak']) {
            return 'Rekod kelahiran anak terdahulu telah mencapai anak ke-' . (int) $row['max_anak'] . '. Sila pilih kelahiran seterusnya.';
        }

        return null;
    }

    private function validateUploadedFile(?UploadedFileInterface $file, bool $required): ?string
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return $required ? 'Sila muat naik dokumen sokongan.' : null;
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return 'Dokumen tidak berjaya dimuat naik.';
        }

        if (($file->getSize() ?? 0) > 6 * 1024 * 1024) {
            return 'Saiz dokumen tidak boleh melebihi 6 MB.';
        }

        $filename = strtolower((string) $file->getClientFilename());
        if (!preg_match('/\.(pdf|jpg|jpeg|png)$/', $filename)) {
            return 'Sila muat naik fail PDF, JPG, JPEG atau PNG.';
        }

        return null;
    }

    private function requiresFile(string $key): bool
    {
        return in_array($key, ['ck', 'cb', 'ctr', 'cs1', 'cs2', 'hso'], true);
    }

    private function uploadCategory(string $key): string
    {
        return in_array($key, ['cs1', 'hso'], true) ? '05' : '04';
    }

    private function uploadDescription(string $key): string
    {
        return match ($key) {
            'ck' => 'Cuti Kecemasan',
            'cb' => 'Cuti Bersalin',
            'ctr' => 'Maklumat Cuti Kakitangan/Cuti Tanpa Rekod',
            'cs1', 'cs2', 'hso' => 'Medical Certificate',
            default => 'Cuti',
        };
    }

    private function uploadDirectory(string $key): string
    {
        return match ($key) {
            'cs1', 'cs2', 'hso' => 'Medical Certificate',
            default => 'Cuti',
        };
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function staffOptions(string $excludeIcno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT ICNO, CONm
            FROM hronline.tblprcobiodata
            WHERE Status = 1
              AND ICNO <> :icno
            ORDER BY CONm
            SQL,
            [':icno' => $excludeIcno],
        )->queryAll();

        return $this->options($rows, 'ICNO', 'CONm');
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function sickVerifierOptions(int $departmentId): array
    {
        $sql = <<<SQL
        SELECT a.akses_cuti_icno AS icno, b.CONm AS name
        FROM hrm.cuti_access a
        LEFT JOIN hronline.tblprcobiodata b ON b.ICNO = a.akses_cuti_icno
        LEFT JOIN hronline.gredjawatan c ON c.id = b.gredJawatan
        WHERE c.gred_no < 41
          AND c.id > 10
          AND a.akses_cuti_int = 2
          AND a.akses_jspiu_id = :dept
        ORDER BY b.CONm
        SQL;

        $rows = $this->db->createCommand($sql, [':dept' => $departmentId])->queryAll();
        if ($rows === []) {
            $sql = <<<SQL
            SELECT a.akses_cuti_icno AS icno, b.CONm AS name
            FROM hrm.cuti_access a
            LEFT JOIN hronline.tblprcobiodata b ON b.ICNO = a.akses_cuti_icno
            LEFT JOIN hronline.gredjawatan c ON c.id = b.gredJawatan
            WHERE c.id > 10
              AND a.akses_cuti_int = 2
              AND a.akses_jspiu_id = :dept
            ORDER BY b.CONm
            SQL;
            $rows = $this->db->createCommand($sql, [':dept' => $departmentId])->queryAll();
        }

        return $this->options($rows, 'icno', 'name');
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function maternityNumberOptions(): array
    {
        $rows = $this->db->createCommand(
            'SELECT id, CONCAT(keterangan, " (", bil, ")") AS name FROM hrm.cuti_ref_bersalin_bilangan ORDER BY bil',
        )->queryAll();

        return $this->options($rows, 'id', 'name');
    }

    /**
     * @param iterable<mixed> $rows
     * @return list<array{id: string, name: string}>
     */
    private function options(iterable $rows, string $idColumn, string $nameColumn): array
    {
        $options = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = $this->value($row[$idColumn] ?? null, '');
            $name = $this->employees->displayName($row[$nameColumn] ?? null);
            if ($id !== '' && $name !== '') {
                $options[] = ['id' => $id, 'name' => $name];
            }
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private function setPegawai(string $icno): array
    {
        $row = $this->db->createCommand(
            'SELECT * FROM hrm.cuti_set_pegawai WHERE pemohon_icno = :icno AND set_status = 1 ORDER BY set_pegawai_id DESC LIMIT 1',
            [':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $row : [];
    }

    private function managementForAction(string $action): string
    {
        return $this->value($this->db->createCommand(
            'SELECT icno FROM hrm.cuti_tbl_management WHERE isActive = 1 AND action = :action ORDER BY id DESC LIMIT 1',
            [':action' => $action],
        )->queryScalar(), '');
    }

    private function managementIcno(string $level, ?string $user = null): string
    {
        $sql = 'SELECT icno FROM hrm.cuti_tbl_management WHERE isActive = 1 AND level = :level';
        $params = [':level' => $level];
        if ($user !== null) {
            $sql .= ' AND user = :user';
            $params[':user'] = $user;
        }
        $sql .= ' ORDER BY id DESC LIMIT 1';

        return $this->value($this->db->createCommand($sql, $params)->queryScalar(), '');
    }

    private function tindakanApprover(string $id): string
    {
        return $this->value($this->db->createCommand(
            "SELECT icno_pemberi_kuasa FROM hrm.cuti_behalf_action WHERE id = :id AND status = '1' LIMIT 1",
            [':id' => $id],
        )->queryScalar(), '');
    }

    private function activeTindakanApprover(): string
    {
        return $this->value($this->db->createCommand(
            "SELECT icno_pemberi_kuasa FROM hrm.cuti_behalf_action WHERE status = '1' ORDER BY id LIMIT 1",
        )->queryScalar(), '');
    }

    private function staffName(string $icno): string
    {
        if ($icno === '') {
            return '-';
        }

        return $this->employees->displayName($this->db->createCommand(
            'SELECT CONm FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryScalar());
    }

    private function isShiftStaff(string $icno, DateTimeImmutable $date): bool
    {
        $yearShift = $this->db->createCommand(
            'SELECT 1 FROM keselamatan.tbl_shift_keselamatan WHERE icno = :icno AND YEAR(tarikh) = :year LIMIT 1',
            [':icno' => $icno, ':year' => date('Y')],
        )->queryScalar();
        if ($yearShift) {
            return true;
        }

        return (bool) $this->db->createCommand(
            'SELECT 1 FROM attendance.tbl_shifts WHERE icno = :icno AND tarikh = :date LIMIT 1',
            [':icno' => $icno, ':date' => $date->format('Y-m-d')],
        )->queryScalar();
    }

    private function hasCampusHoliday(string $icno, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        $campus = $this->value($this->db->createCommand(
            'SELECT campus_id FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryScalar(), '');

        $column = $campus === '2' ? 'wilayah_sahaja' : 'sabah_sahaja';

        return (bool) $this->db->createCommand(
            "SELECT 1 FROM hrm.cuti_public_holiday WHERE tarikh_cuti BETWEEN :start AND :end AND {$column} = 1 LIMIT 1",
            [':start' => $start->format('Y-m-d'), ':end' => $end->format('Y-m-d')],
        )->queryScalar();
    }

    private function inclusiveDays(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        return (int) $start->diff($end)->format('%a') + 1;
    }

    private function weekdaysIncludingHolidays(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        $days = $this->inclusiveDays($start, $end);
        $rows = $this->db->createCommand(
            "SELECT tarikh_cuti, sabah_sahaja FROM hrm.cuti_public_holiday WHERE tarikh_cuti BETWEEN :start AND :end",
            [':start' => $start->format('Y-m-d'), ':end' => $end->format('Y-m-d')],
        )->queryAll();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $holiday = new DateTimeImmutable((string) $row['tarikh_cuti']);
            $day = (int) $holiday->format('N');
            if ($day !== 6 && $day !== 7 && (string) ($row['sabah_sahaja'] ?? '') !== '1') {
                $days--;
            }
        }

        return max(0, $days);
    }

    private function weekdaysExcludingWeekends(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        $days = 0;
        $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
        foreach ($period as $date) {
            $weekday = (int) $date->format('N');
            if ($weekday !== 6 && $weekday !== 7) {
                $days++;
            }
        }

        return $days;
    }

    private function emergencyRemark(array $values): string
    {
        $reasons = $values['reasons'] ?? [];
        $remark = implode(',', is_array($reasons) ? $reasons : []);
        $others = (string) ($values['others'] ?? '');

        return $others !== '' ? $remark . '(' . $others . ')' : $remark;
    }

    private function keyFromPath(string $path): ?string
    {
        $key = basename($path);

        return array_key_exists($key, self::DEFAULT_IDS) ? $key : null;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function typeId(string $key, array $query): int
    {
        $id = $query['id'] ?? null;
        if (is_scalar($id) && ctype_digit((string) $id)) {
            return (int) $id;
        }

        return self::DEFAULT_IDS[$key];
    }

    private function titleFor(string $key, array $leaveType): string
    {
        return match ($key) {
            'cr1-aca' => 'Cuti Rehat Dalam Negara',
            default => $this->value($leaveType['name'] ?? null, 'Permohonan Cuti'),
        };
    }

    private function isValidDate(mixed $value): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function requireDate(mixed $value): DateTimeImmutable
    {
        return new DateTimeImmutable((string) $value);
    }

    private function formatFullDate(DateTimeImmutable $start, DateTimeImmutable $end): string
    {
        return $start->format('d/m/Y') . ' to ' . $end->format('d/m/Y');
    }

    private function formValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function value(mixed $value, string $fallback = '-'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
