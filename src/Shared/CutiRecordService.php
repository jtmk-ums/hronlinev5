<?php

declare(strict_types=1);

namespace App\Shared;

use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiRecordService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private NotificationService $notifications,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function recordsForStaff(string $icno, ?int $year = null, ?int $leaveTypeId = null): array
    {
        $year ??= (int) date('Y');
        $params = [':icno' => $icno, ':year' => $year];
        $typeSql = '';

        if ($leaveTypeId !== null && $leaveTypeId > 0) {
            $typeSql = ' AND r.jenis_cuti_id = :leaveTypeId';
            $params[':leaveTypeId'] = $leaveTypeId;
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.jenis_cuti_id, r.full_date, r.start_date, r.end_date, r.tempoh,
                   r.remark, r.destination, r.ganti_by, r.semakan_by, r.peraku_by, r.lulus_by,
                   r.status, r.mohon_dt, r.file_hashcode, r.file1, r.file2,
                   lt.jenis_cuti_nama, lt.jenis_cuti_catatan,
                   ganti.CONm AS ganti_name,
                   semak.CONm AS semakan_name,
                   peraku.CONm AS peraku_name,
                   lulus.CONm AS lulus_name
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            LEFT JOIN hronline.tblprcobiodata ganti ON ganti.ICNO = r.ganti_by
            LEFT JOIN hronline.tblprcobiodata semak ON semak.ICNO = r.semakan_by
            LEFT JOIN hronline.tblprcobiodata peraku ON peraku.ICNO = r.peraku_by
            LEFT JOIN hronline.tblprcobiodata lulus ON lulus.ICNO = r.lulus_by
            WHERE r.icno = :icno
              AND YEAR(r.start_date) = :year
              {$typeSql}
            ORDER BY r.start_date DESC, r.id DESC
            SQL,
            $params,
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function recordForStaff(string $icno, int $id): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT r.*, lt.jenis_cuti_nama, lt.jenis_cuti_catatan,
                   staff.CONm AS staff_name, staff.COOldID AS staff_no,
                   jawatan.fname AS position_name,
                   ganti.CONm AS ganti_name,
                   semak.CONm AS semakan_name,
                   peraku.CONm AS peraku_name,
                   lulus.CONm AS lulus_name
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
            LEFT JOIN hronline.gredjawatan jawatan ON jawatan.id = staff.gredJawatan
            LEFT JOIN hronline.tblprcobiodata ganti ON ganti.ICNO = r.ganti_by
            LEFT JOIN hronline.tblprcobiodata semak ON semak.ICNO = r.semakan_by
            LEFT JOIN hronline.tblprcobiodata peraku ON peraku.ICNO = r.peraku_by
            LEFT JOIN hronline.tblprcobiodata lulus ON lulus.ICNO = r.lulus_by
            WHERE r.id = :id
              AND (r.icno = :icno OR r.ganti_by = :icno)
            LIMIT 1
            SQL,
            [':id' => $id, ':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $this->normalizeRow($row) : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function substituteRecords(string $icno, ?int $year = null): array
    {
        $year ??= (int) date('Y');
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.icno, r.jenis_cuti_id, r.full_date, r.start_date, r.end_date,
                   r.tempoh, r.remark, r.status, r.mohon_dt,
                   lt.jenis_cuti_nama,
                   staff.CONm AS staff_name
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
            WHERE r.ganti_by = :icno
              AND YEAR(r.start_date) = :year
            ORDER BY r.start_date DESC, r.id DESC
            SQL,
            [':icno' => $icno, ':year' => $year],
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingSubstituteRecords(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.icno, r.jenis_cuti_id, r.full_date, r.start_date, r.end_date,
                   r.tempoh, r.remark, r.status, r.mohon_dt,
                   lt.jenis_cuti_nama, lt.jenis_cuti_catatan,
                   staff.CONm AS staff_name, staff.COOldID AS staff_no
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
            WHERE r.ganti_by = :icno
              AND r.status = 'ENTRY'
            ORDER BY r.start_date ASC, r.id ASC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        return $this->normalizeRows($rows);
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function agreeSubstitute(string $icno, int $id, string $ipAddress = ''): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Rekod tidak sah.'];
        }

        $row = $this->db->createCommand(
            <<<SQL
            SELECT r.*, staff.CONm AS staff_name
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
            WHERE r.id = :id
              AND r.ganti_by = :icno
            LIMIT 1
            SQL,
            [':id' => $id, ':icno' => $icno],
        )->queryOne();

        if (!is_array($row)) {
            return ['success' => false, 'message' => 'Rekod tidak ditemui atau bukan di bawah tindakan anda.'];
        }

        if ((string) ($row['status'] ?? '') !== 'ENTRY') {
            return ['success' => false, 'message' => 'Rekod ini tidak lagi menunggu persetujuan pengganti.'];
        }

        $perakuBy = $this->value($row['peraku_by'] ?? null, '');
        $lulusBy = $this->value($row['lulus_by'] ?? null, '');
        $leaveTypeId = (int) ($row['jenis_cuti_id'] ?? 0);
        $duration = (int) ($row['tempoh'] ?? 0);

        if ($leaveTypeId !== 2 && $duration < 14) {
            $setPegawai = $this->setPegawai((string) ($row['icno'] ?? ''));
            $perakuBy = $this->value($setPegawai['peraku_icno'] ?? null, '');
            $lulusBy = $this->value($setPegawai['pelulus_icno'] ?? null, $lulusBy);
        }

        $status = $perakuBy !== '' ? 'AGREED' : 'VERIFIED';
        $now = date('Y-m-d H:i:s');

        $this->db->transaction(function () use ($id, $icno, $perakuBy, $lulusBy, $status, $now, $row, $ipAddress): void {
            $this->db->createCommand()->update('hrm.cuti_tbl_records', [
                'peraku_by' => $perakuBy !== '' ? $perakuBy : null,
                'lulus_by' => $lulusBy !== '' ? $lulusBy : null,
                'ganti_dt' => $now,
                'status' => $status,
            ], ['id' => $id])->execute();

            $this->db->createCommand()->insert('hrm.cuti_log', [
                'ntf_session_id' => $icno,
                'ntf_tindakan' => 'Pengganti',
                'ntf_status' => $status,
                'ntf_cr_id' => (string) $id,
                'ntf_datetime' => $now,
                'ntf_ip' => substr($ipAddress, 0, 20),
                'full_date' => $this->value($row['full_date'] ?? null, ''),
                'start_date' => $this->value($row['start_date'] ?? null, ''),
                'end_date' => $this->value($row['end_date'] ?? null, ''),
            ])->execute();

            $assignee = $status === 'AGREED' ? $perakuBy : $lulusBy;
            $this->notify(
                $assignee,
                'Cuti',
                'Permohonan Cuti Oleh ' . $this->employees->displayName($row['staff_name'] ?? null)
                    . ' , ' . $this->value($row['full_date'] ?? null, '')
                    . ' Menunggu Perakuan/Kelulusan Anda. Sila Klik <a class="btn btn-primary btn-sm" href="/staff/web/cuti/pegawai/senarai-peraku-lulus">disini</a> untuk membuat tindakan',
                $now,
            );

            $this->refreshPendingTask($icno, 7);
        });

        return ['success' => true, 'message' => 'Persetujuan pengganti berjaya disimpan.'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function statementForStaff(string $icno, int $year): array
    {
        $entitlements = $this->db->createCommand(
            <<<SQL
            SELECT *
            FROM hrm.cuti_entitlement
            WHERE layak_icno = :icno
              AND YEAR(layak_mula) = :year
            ORDER BY layak_mula DESC, layak_id DESC
            SQL,
            [':icno' => $icno, ':year' => $year],
        )->queryAll();

        $statement = [];
        foreach ($entitlements as $entitlement) {
            if (!is_array($entitlement)) {
                continue;
            }

            $start = $this->value($entitlement['layak_mula'] ?? null, '');
            $end = $this->value($entitlement['layak_tamat'] ?? null, '');
            $records = $this->recordsForStatementPeriod($icno, $start, $end);
            $total = (int) ($entitlement['layak_bawa_lepas'] ?? 0)
                + (int) ($entitlement['layak_cuti'] ?? 0)
                + (int) ($entitlement['layak_selaras'] ?? 0);
            $cumulative = 0;
            foreach ($records as &$record) {
                if (in_array((int) ($record['jenis_cuti_id'] ?? 0), [1, 2], true)) {
                    $cumulative += (int) ($record['tempoh'] ?? 0);
                }
                $record['cumulative'] = $cumulative;
                $record['balance'] = $total - $cumulative;
            }
            unset($record);

            $statement[] = [
                'entitlement' => $entitlement + ['total_layak' => $total],
                'records' => $records,
            ];
        }

        return $statement;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function yearsForStaff(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT YEAR(start_date) AS year
            FROM hrm.cuti_tbl_records
            WHERE icno = :icno
              AND start_date IS NOT NULL
            GROUP BY YEAR(start_date)
            ORDER BY year DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $years = [];
        foreach ($rows as $row) {
            if (is_array($row) && ($row['year'] ?? null) !== null) {
                $years[] = ['id' => (string) $row['year'], 'name' => (string) $row['year']];
            }
        }

        return $years;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function substituteYearsForStaff(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT YEAR(start_date) AS year
            FROM hrm.cuti_tbl_records
            WHERE ganti_by = :icno
              AND start_date IS NOT NULL
            GROUP BY YEAR(start_date)
            ORDER BY year DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $years = [];
        foreach ($rows as $row) {
            if (is_array($row) && ($row['year'] ?? null) !== null) {
                $years[] = ['id' => (string) $row['year'], 'name' => (string) $row['year']];
            }
        }

        return $years;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function leaveTypesForStaff(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT DISTINCT lt.jenis_cuti_id, lt.jenis_cuti_catatan, lt.jenis_cuti_nama
            FROM hrm.cuti_tbl_records r
            INNER JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            WHERE r.icno = :icno
            ORDER BY lt.jenis_cuti_catatan, lt.jenis_cuti_nama
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $types = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $types[] = [
                'id' => (string) ($row['jenis_cuti_id'] ?? ''),
                'name' => $this->value($row['jenis_cuti_catatan'] ?? $row['jenis_cuti_nama'] ?? null),
            ];
        }

        return $types;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function entitlementYearsForStaff(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT YEAR(layak_mula) AS year
            FROM hrm.cuti_entitlement
            WHERE layak_icno = :icno
              AND layak_mula IS NOT NULL
            GROUP BY YEAR(layak_mula)
            ORDER BY year DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $years = [];
        foreach ($rows as $row) {
            if (is_array($row) && ($row['year'] ?? null) !== null) {
                $years[] = ['id' => (string) $row['year'], 'name' => (string) $row['year']];
            }
        }

        return $years;
    }

    public function usedAnnualLeave(string $icno, string $startDate, string $endDate): int
    {
        return (int) $this->db->createCommand(
            <<<SQL
            SELECT COALESCE(SUM(tempoh), 0)
            FROM hrm.cuti_tbl_records
            WHERE status != 'REJECTED'
              AND start_date BETWEEN :startDate AND :endDate
              AND jenis_cuti_id IN (1, 2, 42)
              AND icno = :icno
            SQL,
            [':icno' => $icno, ':startDate' => $startDate, ':endDate' => $endDate],
        )->queryScalar();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function calendarEvents(string $icno): array
    {
        $leaveRows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.start_date, r.end_date, r.remark, lt.jenis_cuti_nama
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            WHERE r.icno = :icno
            ORDER BY r.start_date DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $events = [];
        foreach ($leaveRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $end = $this->value($row['end_date'] ?? null, $this->value($row['start_date'] ?? null, ''));
            if ($end !== '') {
                $end = (new DateTimeImmutable($end))->modify('+1 day')->format('Y-m-d');
            }

            $events[] = [
                'id' => 'leave-' . (string) ($row['id'] ?? ''),
                'title' => trim($this->value($row['jenis_cuti_nama'] ?? null, 'Cuti') . ' - ' . $this->value($row['remark'] ?? null, '')),
                'start' => $this->value($row['start_date'] ?? null, ''),
                'end' => $end,
                'type' => 'leave',
            ];
        }

        $holidayRows = $this->db->createCommand(
            'SELECT id, nama_cuti, tarikh_cuti FROM hrm.cuti_public_holiday ORDER BY tarikh_cuti DESC',
        )->queryAll();
        foreach ($holidayRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $events[] = [
                'id' => 'holiday-' . (string) ($row['id'] ?? ''),
                'title' => $this->value($row['nama_cuti'] ?? null, 'Cuti Umum'),
                'start' => $this->value($row['tarikh_cuti'] ?? null, ''),
                'type' => 'holiday',
            ];
        }

        return $events;
    }

    /**
     * @param iterable<mixed> $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(iterable $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $normalized[] = $this->normalizeRow($row);
            }
        }

        return $normalized;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recordsForStatementPeriod(string $icno, string $startDate, string $endDate): array
    {
        if ($startDate === '' || $endDate === '') {
            return [];
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.jenis_cuti_id, r.full_date, r.start_date, r.end_date, r.tempoh,
                   r.remark, r.status, lt.jenis_cuti_nama, lt.jenis_cuti_catatan
            FROM hrm.cuti_tbl_records r
            LEFT JOIN hrm.cuti_leave_type lt ON lt.jenis_cuti_id = r.jenis_cuti_id
            WHERE r.icno = :icno
              AND r.start_date BETWEEN :startDate AND :endDate
            ORDER BY r.start_date DESC, r.id DESC
            SQL,
            [':icno' => $icno, ':startDate' => $startDate, ':endDate' => $endDate],
        )->queryAll();

        return $this->normalizeRows($rows);
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

    private function refreshPendingTask(string $icno, int $commandId): void
    {
        $this->db->createCommand()->update('system_core.tbl_pending_task', [
            'count' => 0,
            'status' => 0,
        ], [
            'icno' => $icno,
            'command_id' => $commandId,
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
            // Notification failure should not block the leave workflow update.
        }
    }

    private function notificationDate(string $dateTime): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        foreach (['staff_name', 'ganti_name', 'semakan_name', 'peraku_name', 'lulus_name'] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = $this->employees->displayName($row[$key]);
            }
        }

        $row['jenis_cuti_label'] = $this->value($row['jenis_cuti_catatan'] ?? $row['jenis_cuti_nama'] ?? null);
        $row['status_label'] = $this->value($row['status'] ?? null, 'ENTRY');
        $row['date_label'] = $this->value($row['full_date'] ?? null, $this->dateRange($row));
        $row['tempoh_label'] = $this->value($row['tempoh'] ?? null, '0');

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function dateRange(array $row): string
    {
        $start = $this->value($row['start_date'] ?? null, '');
        $end = $this->value($row['end_date'] ?? null, '');
        if ($start === '') {
            return '-';
        }

        return $end === '' || $end === $start ? $start : $start . ' - ' . $end;
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
