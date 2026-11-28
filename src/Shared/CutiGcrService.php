<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiGcrService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private CutiRecordService $records,
        private NotificationService $notifications,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function applicationsForStaff(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT g.*, dept.fullname AS department_name,
                   semak.CONm AS semakan_name,
                   peraku.CONm AS peraku_name,
                   lulus.CONm AS lulus_name,
                   bsm.CONm AS bsm_semak_name
            FROM hrm.cuti_gcr_application g
            LEFT JOIN hronline.department dept ON dept.id = g.dept_id
            LEFT JOIN hronline.tblprcobiodata semak ON semak.ICNO = g.semakan_by
            LEFT JOIN hronline.tblprcobiodata peraku ON peraku.ICNO = g.peraku_by
            LEFT JOIN hronline.tblprcobiodata lulus ON lulus.ICNO = g.lulus_by
            LEFT JOIN hronline.tblprcobiodata bsm ON bsm.ICNO = g.bsm_semak
            WHERE g.pemohon_icno = :icno
            ORDER BY g.mohon_dt DESC, g.id DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function openWindow(): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT *
            FROM hrm.cuti_open_application
            WHERE status = 1
            ORDER BY end_date DESC, id DESC
            LIMIT 1
            SQL,
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     * @return array<string, mixed>
     */
    public function formData(string $icno, array $values = [], array $errors = []): array
    {
        $staff = $this->staffRow($icno);
        $open = $this->openWindow();
        $eligibility = $this->eligibility($icno, $staff, $open);
        $entitlement = $eligibility['entitlement'];
        $calculations = $entitlement === null ? $this->emptyCalculations() : $this->calculations($icno, $entitlement);
        $values = array_merge([
            'semakanBy' => '',
            'gcrApplied' => '',
            'cbthApplied' => '',
            'agreement' => '',
        ], $values);

        return [
            'staff' => $staff,
            'openWindow' => $open,
            'eligible' => $eligibility['eligible'],
            'blockReasons' => $eligibility['reasons'],
            'entitlement' => $entitlement,
            'calculations' => $calculations,
            'verifiers' => $staff === null ? [] : $this->verifierOptions((int) ($staff['DeptId'] ?? 0)),
            'values' => $values,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string, values: array<string, mixed>, errors: array<string, string>}
     */
    public function submit(string $icno, array $input, string $ipAddress = ''): array
    {
        $values = [
            'semakanBy' => $this->value($input['semakanBy'] ?? null, ''),
            'gcrApplied' => $this->value($input['gcrApplied'] ?? null, ''),
            'cbthApplied' => $this->value($input['cbthApplied'] ?? null, ''),
            'agreement' => isset($input['agreement']) ? '1' : '',
        ];

        $data = $this->formData($icno, $values);
        if (!$data['eligible']) {
            return [
                'success' => false,
                'message' => 'Permohonan GCR/CBTH tidak boleh dihantar.',
                'values' => $values,
                'errors' => ['general' => implode(' ', $data['blockReasons'])],
            ];
        }

        $errors = $this->validateSubmission($values, $data['calculations'], $data['verifiers']);
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Sila semak maklumat.', 'values' => $values, 'errors' => $errors];
        }

        $staff = $data['staff'];
        $open = $data['openWindow'];
        $entitlement = $data['entitlement'];
        if (!is_array($staff) || !is_array($open) || !is_array($entitlement)) {
            return ['success' => false, 'message' => 'Maklumat permohonan tidak lengkap.', 'values' => $values, 'errors' => ['general' => 'Maklumat permohonan tidak lengkap.']];
        }

        $department = $this->department((int) ($staff['DeptId'] ?? 0));
        $setPegawai = $this->setPegawai($icno);
        $departmentChief = $this->value($department['chief'] ?? null, '');
        $bsmChief = $this->value($this->db->createCommand(
            'SELECT chief FROM hronline.department WHERE id = 230 LIMIT 1',
        )->queryScalar(), '');
        $perakuBy = $departmentChief === $icno
            ? $this->value($setPegawai['pelulus_icno'] ?? null, '')
            : $departmentChief;

        $now = date('Y-m-d H:i:s');
        $openYear = (int) ($open['year'] ?? date('Y'));
        $calculations = $data['calculations'];

        $this->db->transaction(function () use ($icno, $values, $staff, $openYear, $perakuBy, $bsmChief, $now, $calculations, $entitlement, $ipAddress): void {
            $nextYear = $openYear + 1;
            $this->ensureNextYearEntitlement($icno, $nextYear, $entitlement);

            $this->db->createCommand()->insert('hrm.cuti_gcr_application', [
                'pemohon_icno' => $icno,
                'mohon_dt' => $now,
                'leave_balance' => $calculations['leaveBalance'],
                'gcr_applied' => (int) $values['gcrApplied'],
                'cbth_applied' => (int) $values['cbthApplied'],
                'semakan_by' => $values['semakanBy'],
                'peraku_by' => $perakuBy !== '' ? $perakuBy : null,
                'lulus_by' => $bsmChief !== '' ? $bsmChief : null,
                'dept_id' => (int) ($staff['DeptId'] ?? 0),
                'gcr_year' => $openYear,
                'agreement' => 1,
                'status' => 'ENTRY',
                'isActive' => 1,
                'flag' => 0,
            ])->execute();

            $id = (string) $this->db->getLastInsertId();
            $this->db->createCommand()->insert('hrm.cuti_log', [
                'ntf_session_id' => $icno,
                'ntf_tindakan' => 'Mohon GCR/CBTH',
                'ntf_status' => 'ENTRY',
                'ntf_cr_id' => $id,
                'ntf_datetime' => $now,
                'ntf_ip' => substr($ipAddress, 0, 20),
            ])->execute();

            $this->notify(
                (string) $values['semakanBy'],
                'Cuti',
                'Permohonan GCR dan CBTH Oleh ' . $this->employees->displayName($staff['CONm'] ?? null) . ' Menunggu Semakan Anda',
                $now,
            );
        });

        return ['success' => true, 'message' => 'Permohonan GCR/CBTH berjaya dihantar.', 'values' => $values, 'errors' => []];
    }

    /**
     * @return array{maxGcr: int, maxCbth: int, leaveBalance: int, absent: int, availableForGcr: int, totalGcr: int, cbthSuggested: int, lupus: int}
     */
    private function calculations(string $icno, array $entitlement): array
    {
        $leaveBalance = $this->leaveBalanceForGcr($icno, $entitlement);
        $absent = $this->absentCount($icno, (int) date('Y', strtotime((string) ($entitlement['layak_tamat'] ?? 'now'))));
        $totalGcr = $this->totalGcr($icno);
        $availableForGcr = min(15, max(0, 180 - $totalGcr));
        $maxGcr = min($this->maxGcr($entitlement), $leaveBalance, max(0, $leaveBalance - $absent), $availableForGcr);
        $maxCbth = min($this->maxCbth($entitlement), $leaveBalance);
        $cbthSuggested = min(max(0, $leaveBalance - $maxGcr), $maxCbth);

        return [
            'maxGcr' => max(0, (int) floor($maxGcr)),
            'maxCbth' => max(0, (int) floor($maxCbth)),
            'leaveBalance' => max(0, (int) floor($leaveBalance)),
            'absent' => $absent,
            'availableForGcr' => $availableForGcr,
            'totalGcr' => $totalGcr,
            'cbthSuggested' => max(0, (int) floor($cbthSuggested)),
            'lupus' => max(0, (int) floor($leaveBalance - $maxGcr - $cbthSuggested)),
        ];
    }

    /**
     * @return array{maxGcr: int, maxCbth: int, leaveBalance: int, absent: int, availableForGcr: int, totalGcr: int, cbthSuggested: int, lupus: int}
     */
    private function emptyCalculations(): array
    {
        return ['maxGcr' => 0, 'maxCbth' => 0, 'leaveBalance' => 0, 'absent' => 0, 'availableForGcr' => 0, 'totalGcr' => 0, 'cbthSuggested' => 0, 'lupus' => 0];
    }

    /**
     * @return array{eligible: bool, reasons: list<string>, entitlement: array<string, mixed>|null}
     */
    private function eligibility(string $icno, ?array $staff, ?array $open): array
    {
        $reasons = [];
        $entitlement = null;
        $tempAllowed = $this->tempAllowed($icno);

        if ($staff === null) {
            return ['eligible' => false, 'reasons' => ['Sesi semasa bukan rekod kakitangan.'], 'entitlement' => null];
        }

        if ($open === null && !$tempAllowed) {
            $reasons[] = 'Permohonan GCR dan CBTH semasa tidak dibuka.';
        }

        $openYear = (int) ($open['year'] ?? date('Y'));
        $entitlement = $this->entitlementForYear($icno, $openYear);

        if (!$tempAllowed && $this->hasActiveApplication($icno)) {
            $reasons[] = 'Anda telah pun menghantar permohonan GCR/CBTH aktif.';
        }

        if ((string) ($staff['statLantikan'] ?? '') !== '1') {
            $reasons[] = 'Permohonan GCR dan CBTH hanya dibuka untuk kakitangan tetap.';
        }

        if ((string) ($staff['Status'] ?? '') === '2') {
            $reasons[] = 'Permohonan GCR dan CBTH hanya dibuka untuk kakitangan tetap dan aktif.';
        }

        if ($open !== null && $this->hasUnresolvedAnnualLeave($icno, $openYear)) {
            $reasons[] = 'Anda mempunyai permohonan cuti yang masih belum diluluskan.';
        }

        if ($open !== null && $this->hasLockedAttendance($icno, $openYear)) {
            $reasons[] = 'Permohonan GCR/CBTH dikunci kerana rekod kehadiran ABSENT belum selesai.';
        }

        if ($entitlement === null) {
            $reasons[] = 'Anda tidak mempunyai kelayakan cuti terkini untuk tahun permohonan.';
        }

        return ['eligible' => $reasons === [], 'reasons' => $reasons, 'entitlement' => $entitlement];
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, int> $calculations
     * @param list<array{id: string, name: string}> $verifiers
     * @return array<string, string>
     */
    private function validateSubmission(array $values, array $calculations, array $verifiers): array
    {
        $errors = [];
        $gcr = filter_var($values['gcrApplied'], FILTER_VALIDATE_INT);
        $cbth = filter_var($values['cbthApplied'], FILTER_VALIDATE_INT);
        $verifierIds = array_column($verifiers, 'id');

        if ($values['semakanBy'] === '') {
            $errors['semakanBy'] = 'Penyelia cuti adalah wajib.';
        } elseif (!in_array($values['semakanBy'], $verifierIds, true)) {
            $errors['semakanBy'] = 'Penyelia cuti tidak sah untuk jabatan semasa.';
        }
        if ($gcr === false) {
            $errors['gcrApplied'] = 'Sila masukkan GCR dalam nombor hari.';
        }
        if ($cbth === false) {
            $errors['cbthApplied'] = 'Sila masukkan CBTH dalam nombor hari.';
        }
        if ($values['agreement'] !== '1') {
            $errors['agreement'] = 'Sila tandakan pengesahan permohonan.';
        }

        if ($gcr !== false && $gcr < 0) {
            $errors['gcrApplied'] = 'Sila letak jumlah 0 atau lebih.';
        }
        if ($cbth !== false && $cbth < 0) {
            $errors['cbthApplied'] = 'Sila letak jumlah 0 atau lebih.';
        }
        if ($gcr !== false && $gcr > $calculations['maxGcr']) {
            $errors['gcrApplied'] = 'GCR tidak boleh melebihi ' . $calculations['maxGcr'] . '.';
        }
        if ($cbth !== false && $cbth > $calculations['maxCbth']) {
            $errors['cbthApplied'] = 'CBTH tidak boleh melebihi ' . $calculations['maxCbth'] . '.';
        }
        if ($gcr !== false && $cbth !== false && ($gcr + $cbth) > $calculations['leaveBalance']) {
            $errors['cbthApplied'] = 'Jumlah GCR dan CBTH tidak boleh melebihi baki cuti.';
        }

        return $errors;
    }

    private function maxGcr(array $entitlement): int
    {
        $layakCuti = (int) ($entitlement['layak_cuti'] ?? 0);
        $bawaLepas = (int) ($entitlement['layak_bawa_lepas'] ?? 0);
        if ($layakCuti < 20) {
            return $layakCuti + $bawaLepas;
        }
        if ($layakCuti >= 30) {
            return 15;
        }
        if ($layakCuti === 25) {
            return 12;
        }
        if ($layakCuti === 0) {
            return $bawaLepas;
        }

        return (int) floor($layakCuti / 2);
    }

    private function maxCbth(array $entitlement): int
    {
        $layakCuti = (int) ($entitlement['layak_cuti'] ?? 0);
        if ($layakCuti <= 20) {
            return 40;
        }
        if ($layakCuti === 25) {
            return 50;
        }
        if ($layakCuti === 30) {
            return 60;
        }
        if ($layakCuti >= 35) {
            return 70;
        }

        return 50;
    }

    private function leaveBalanceForGcr(string $icno, array $entitlement): int
    {
        $total = (int) ($entitlement['layak_cuti'] ?? 0) + (int) ($entitlement['layak_bawa_lepas'] ?? 0);
        $used = $this->records->usedAnnualLeave(
            $icno,
            (string) ($entitlement['layak_mula'] ?? ''),
            (string) ($entitlement['layak_tamat'] ?? ''),
        );

        return $total - $used;
    }

    private function totalGcr(string $icno): int
    {
        return (int) $this->db->createCommand(
            'SELECT COALESCE(SUM(layak_gcr), 0) FROM hrm.cuti_entitlement WHERE layak_icno = :icno',
            [':icno' => $icno],
        )->queryScalar();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function staffRow(string $icno): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT bio.*, dept.fullname AS department_name
            FROM hronline.tblprcobiodata bio
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            WHERE bio.ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function entitlementForYear(string $icno, int $year): ?array
    {
        $end = $year . '-12-31';
        $start = $year . '-01-01';
        $row = $this->db->createCommand(
            <<<SQL
            SELECT *
            FROM hrm.cuti_entitlement
            WHERE layak_icno = :icno
              AND (layak_tamat = :endDate OR layak_mula = :startDate)
            ORDER BY layak_tamat DESC, layak_id DESC
            LIMIT 1
            SQL,
            [':icno' => $icno, ':endDate' => $end, ':startDate' => $start],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function verifierOptions(int $departmentId): array
    {
        $sql = <<<SQL
        SELECT a.akses_cuti_icno AS icno, b.CONm AS name
        FROM hrm.cuti_access a
        LEFT JOIN hronline.tblprcobiodata b ON b.ICNO = a.akses_cuti_icno
        LEFT JOIN hronline.gredjawatan c ON c.id = b.gredJawatan
        WHERE c.gred_no < 44
          AND a.akses_cuti_int = 2
          AND a.akses_jspiu_id = :dept
        ORDER BY b.CONm
        SQL;

        $rows = $this->db->createCommand($sql, [':dept' => $departmentId])->queryAll();
        if ($departmentId === 230 || $rows === []) {
            $sql = <<<SQL
            SELECT a.akses_cuti_icno AS icno, b.CONm AS name
            FROM hrm.cuti_access a
            LEFT JOIN hronline.tblprcobiodata b ON b.ICNO = a.akses_cuti_icno
            WHERE a.akses_cuti_int = 2
              AND a.akses_jspiu_id = :dept
            ORDER BY b.CONm
            SQL;
            $rows = $this->db->createCommand($sql, [':dept' => $departmentId])->queryAll();
        }

        $options = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $id = $this->value($row['icno'] ?? null, '');
                $name = $this->employees->displayName($row['name'] ?? null);
                if ($id !== '') {
                    $options[] = ['id' => $id, 'name' => $name];
                }
            }
        }

        return $options;
    }

    private function hasActiveApplication(string $icno): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM hrm.cuti_gcr_application WHERE pemohon_icno = :icno AND isActive = 1 LIMIT 1',
            [':icno' => $icno],
        )->queryScalar();
    }

    private function hasUnresolvedAnnualLeave(string $icno, int $year): bool
    {
        return (bool) $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM hrm.cuti_tbl_records
            WHERE icno = :icno
              AND jenis_cuti_id = 1
              AND YEAR(start_date) = :year
              AND status NOT IN ('APPROVED', 'REJECTED')
            LIMIT 1
            SQL,
            [':icno' => $icno, ':year' => $year],
        )->queryScalar();
    }

    private function tempAllowed(string $icno): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM hrm.cuti_temp_table WHERE icno = :icno AND allowed = 1 LIMIT 1',
            [':icno' => $icno],
        )->queryScalar();
    }

    private function hasLockedAttendance(string $icno, int $year): bool
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.tarikh
            FROM attendance.tbl_rekod r
            WHERE r.icno = :icno
              AND r.absent = 1
              AND YEAR(r.tarikh) = :year
              AND r.remark_status = 'REJECTED'
            SQL,
            [':icno' => $icno, ':year' => $year],
        )->queryAll();

        foreach ($rows as $row) {
            if (!is_array($row) || $this->isLeaveDay($icno, (string) ($row['tarikh'] ?? ''))) {
                continue;
            }
            if ($this->isAttendanceLocked((int) ($row['id'] ?? 0))) {
                return true;
            }
        }

        return false;
    }

    private function absentCount(string $icno, int $year): int
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.tarikh
            FROM attendance.tbl_rekod r
            WHERE r.icno = :icno
              AND r.absent = 1
              AND YEAR(r.tarikh) = :year
              AND r.ampun = '0'
              AND r.remark_status != 'APPROVED'
            SQL,
            [':icno' => $icno, ':year' => $year],
        )->queryAll();

        $count = 0;
        foreach ($rows as $row) {
            if (is_array($row) && !$this->isLeaveDay($icno, (string) ($row['tarikh'] ?? ''))) {
                $count++;
            }
        }

        return $count;
    }

    private function isAttendanceLocked(int $recordId): bool
    {
        if ($recordId <= 0) {
            return false;
        }

        return (bool) $this->db->createCommand(
            'SELECT 1 FROM attendance.tbl_thtc WHERE tbl_rekod_id = :id AND (locked_gcr = 1 OR status != 3) LIMIT 1',
            [':id' => $recordId],
        )->queryScalar();
    }

    private function isLeaveDay(string $icno, string $date): bool
    {
        if ($date === '') {
            return false;
        }

        return (bool) $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM hrm.cuti_tbl_records
            WHERE icno = :icno
              AND status != 'REJECTED'
              AND :date BETWEEN start_date AND end_date
            LIMIT 1
            SQL,
            [':icno' => $icno, ':date' => $date],
        )->queryScalar();
    }

    /**
     * @return array<string, mixed>
     */
    private function department(int $id): array
    {
        $row = $this->db->createCommand(
            'SELECT id, chief, pp, fullname FROM hronline.department WHERE id = :id LIMIT 1',
            [':id' => $id],
        )->queryOne();

        return is_array($row) ? $row : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function setPegawai(string $icno): array
    {
        $row = $this->db->createCommand(
            'SELECT peraku_icno, pelulus_icno FROM hrm.cuti_set_pegawai WHERE pemohon_icno = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $row : [];
    }

    private function ensureNextYearEntitlement(string $icno, int $year, array $currentEntitlement): void
    {
        $start = $year . '-01-01';
        $end = $year . '-12-31';
        $exists = (bool) $this->db->createCommand(
            'SELECT 1 FROM hrm.cuti_entitlement WHERE layak_icno = :icno AND layak_mula = :startDate LIMIT 1',
            [':icno' => $icno, ':startDate' => $start],
        )->queryScalar();
        if ($exists) {
            return;
        }

        $this->db->createCommand()->insert('hrm.cuti_entitlement', [
            'layak_icno' => $icno,
            'layak_mula' => $start,
            'layak_tamat' => $end,
            'layak_cuti' => (int) ($currentEntitlement['layak_cuti'] ?? 0),
            'layak_bawa_lepas' => 0,
            'layak_bawa_depan' => 0,
            'layak_ambil' => 0,
            'layak_gcr' => 0,
            'layak_hapus' => 0,
        ])->execute();
    }

    private function notify(string $icno, string $title, string $content, string $dateTime): void
    {
        if ($icno === '') {
            return;
        }

        try {
            $this->notifications->create($icno, $title, $content, $this->notificationDate($dateTime));
        } catch (\Throwable) {
            // Notification failure should not block the GCR workflow update.
        }
    }

    private function notificationDate(string $dateTime): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    private function value(mixed $value, string $fallback = '-'): string
    {
        if ($value === null || !is_scalar($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
