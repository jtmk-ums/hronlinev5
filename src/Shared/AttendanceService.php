<?php

declare(strict_types=1);

namespace App\Shared;

use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class AttendanceService
{
    private const LEGACY_BASE = LegacyUrl::BASE;

    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
        private NotificationService $notifications,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function landingForIdentity(string $identityId, ?string $clientIp = null): ?array
    {
        $icno = $this->employees->icnoFromIdentity($identityId);
        $staff = $this->employees->byIcno($icno);
        if ($icno === '' || $staff === null) {
            return null;
        }

        $today = new DateTimeImmutable('today');
        $workingPattern = $this->currentWorkingPattern($icno);
        $currentRecord = $this->currentRecord(
            $icno,
            $today->format('Y-m-d'),
            $workingPattern['nextDay'] ?? false,
        );
        $cardMonth = $this->cardColorMonth($today);
        $cardColor = $this->cardColor($icno, (int) $cardMonth->format('m'), (int) $cardMonth->format('Y'));
        $roleFlags = $this->roleFlags($icno);
        $pending = $this->pendingCounts($icno);
        $clientIp = $this->value($clientIp, 'UNKNOWN');

        return [
            'icno' => $icno,
            'staffName' => $this->employees->displayName($staff['CONm'] ?? null),
            'department' => $this->employees->value($staff['department_name'] ?? null),
            'campus' => $this->employees->value($staff['campus_name'] ?? null),
            'today' => $today->format('Y-m-d'),
            'notice' => 'Adalah Dimaklumkan bahawa semua kakitangan UMS diwajibkan untuk menggunakan aplikasi UMS Go SuperApp untuk merakam waktu bekerja bermula pada 1 Oktober 2024',
            'clientIp' => $clientIp,
            'ipType' => str_starts_with($clientIp, '10') ? 'Internal' : 'External',
            'workMode' => $this->workMode($icno, $today->format('Y-m-d')),
            'workingPattern' => $workingPattern,
            'currentRecord' => $currentRecord,
            'hourComplete' => $this->hourComplete($currentRecord, $workingPattern),
            'cardColor' => $cardColor,
            'pending' => $pending,
            'roleFlags' => $roleFlags,
            'menuSections' => $this->menuSections($roleFlags, $pending),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function currentWorkingPattern(string $icno): ?array
    {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $wpRow = $this->db->createCommand(
            <<<SQL
            SELECT twp.id AS assignment_id, twp.wp_id, twp.start_date, twp.end_date,
                   ref.jenis_wp, ref.detail, ref.start_time, ref.end_time, ref.next_day,
                   ref.is_flexi, ref.in_start_time, ref.in_end_time, ref.out_start_time,
                   ref.out_end_time, ref.total_hours
            FROM attendance.tbl_wp twp
            LEFT JOIN attendance.ref_wp ref ON ref.id = twp.wp_id
            WHERE twp.icno = :icno
              AND twp.status = 'APPROVED'
              AND twp.start_date <= :today
              AND (twp.end_date >= :today OR twp.end_date IS NULL)
            ORDER BY twp.start_date DESC, twp.id DESC
            LIMIT 1
            SQL,
            [':icno' => $icno, ':today' => $today],
        )->queryOne();

        if (!is_array($wpRow)) {
            return null;
        }

        $shiftWpId = $this->shiftWorkingPatternId($icno, $today);
        if ($shiftWpId !== null) {
            $shiftRef = $this->workingPatternById($shiftWpId);
            if ($shiftRef !== null) {
                $wpRow = array_replace($wpRow, $shiftRef, ['wp_id' => $shiftWpId, 'shiftOverride' => true]);
            }
        }

        $ramadhanWpId = $this->ramadhanWorkingPatternId($icno, (string) $wpRow['wp_id'], $today);
        if ($ramadhanWpId !== null) {
            $ramadhanRef = $this->workingPatternById($ramadhanWpId);
            if ($ramadhanRef !== null) {
                $wpRow = array_replace($wpRow, $ramadhanRef, ['wp_id' => $ramadhanWpId, 'ramadhanOverride' => true]);
            }
        }

        return [
            'assignmentId' => (string) ($wpRow['assignment_id'] ?? ''),
            'id' => (string) ($wpRow['wp_id'] ?? ''),
            'label' => $this->value($wpRow['jenis_wp'] ?? null, '--Tiada WBB--'),
            'detail' => $this->value($wpRow['detail'] ?? null, ''),
            'startTime' => $this->timeValue($wpRow['start_time'] ?? null),
            'endTime' => $this->timeValue($wpRow['end_time'] ?? null),
            'inStartTime' => $this->timeValue($wpRow['in_start_time'] ?? null),
            'inEndTime' => $this->timeValue($wpRow['in_end_time'] ?? null),
            'outStartTime' => $this->timeValue($wpRow['out_start_time'] ?? null),
            'outEndTime' => $this->timeValue($wpRow['out_end_time'] ?? null),
            'totalHours' => (float) ($wpRow['total_hours'] ?? 0),
            'isFlexi' => (int) ($wpRow['is_flexi'] ?? 0) === 1,
            'nextDay' => (int) ($wpRow['next_day'] ?? 0) === 1,
            'shiftOverride' => (bool) ($wpRow['shiftOverride'] ?? false),
            'ramadhanOverride' => (bool) ($wpRow['ramadhanOverride'] ?? false),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function currentRecord(string $icno, string $date, bool $nextDay = false): ?array
    {
        $record = null;
        if ($nextDay) {
            $yesterday = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
            $record = $this->recordForDate($icno, $yesterday, true);
        }

        $record ??= $this->recordForDate($icno, $date, false);
        if ($record === null) {
            return null;
        }

        return [
            'id' => (string) ($record['id'] ?? ''),
            'date' => $this->value($record['tarikh'] ?? null, $date),
            'timeIn' => $this->formatTime($record['time_in'] ?? null),
            'timeOut' => $this->formatTime($record['time_out'] ?? null),
            'timeInRaw' => $this->value($record['time_in'] ?? null, ''),
            'timeOutRaw' => $this->value($record['time_out'] ?? null, ''),
            'totalHours' => $this->value($record['total_hours'] ?? null, '-'),
            'statusLabels' => $this->statusLabels($record),
            'remarkStatus' => $this->value($record['remark_status'] ?? null, '-'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cardColor(string $icno, int $month, int $year): array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT color, ketidakpatuhan, approved, disapproved, month_date
            FROM attendance.tbl_warnakad
            WHERE icno = :icno
              AND MONTH(month_date) = :month
              AND YEAR(month_date) = :year
            ORDER BY month_date DESC
            LIMIT 1
            SQL,
            [':icno' => $icno, ':month' => $month, ':year' => $year],
        )->queryOne();

        return [
            'color' => is_array($row) ? strtolower($this->value($row['color'] ?? null, '-')) : '-',
            'nonCompliance' => is_array($row) ? (int) ($row['ketidakpatuhan'] ?? 0) : 0,
            'approved' => is_array($row) ? (int) ($row['approved'] ?? 0) : 0,
            'disapproved' => is_array($row) ? (int) ($row['disapproved'] ?? 0) : 0,
            'month' => $month,
            'year' => $year,
            'monthDate' => is_array($row) ? $this->value($row['month_date'] ?? null, '') : '',
        ];
    }

    /**
     * @return list<int>
     */
    public function activeYears(): array
    {
        $rows = $this->db->createCommand(
            'SELECT year FROM attendance.tbl_years WHERE status = 1 ORDER BY year DESC',
        )->queryAll();

        $years = [];
        foreach ($rows as $row) {
            if (is_array($row) && is_numeric($row['year'] ?? null)) {
                $years[] = (int) $row['year'];
            }
        }

        if ($years === []) {
            $years[] = (int) (new DateTimeImmutable('today'))->format('Y');
        }

        return $years;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function staffProfile(string $icno): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT bio.ICNO, bio.CONm, bio.COOldID, bio.campus_id,
                   dept.fullname AS department_name, campus.campus_name,
                   jawatan.fname AS position_name
            FROM hronline.tblprcobiodata bio
            LEFT JOIN hronline.department dept ON dept.id = bio.DeptId
            LEFT JOIN hronline.campus campus ON campus.campus_id = bio.campus_id
            LEFT JOIN hronline.gredjawatan jawatan ON jawatan.id = bio.gredJawatan
            WHERE bio.ICNO = :icno
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        if (!is_array($row)) {
            return null;
        }

        return [
            'icno' => $this->value($row['ICNO'] ?? null),
            'name' => $this->employees->displayName($row['CONm'] ?? null),
            'staffNo' => $this->value($row['COOldID'] ?? null),
            'position' => $this->value($row['position_name'] ?? null),
            'department' => $this->value($row['department_name'] ?? null),
            'campus' => $this->value($row['campus_name'] ?? null),
            'campusId' => (int) ($row['campus_id'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceReport(string $icno, int $year, int $month): array
    {
        $month = max(1, min(12, $month));
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('last day of this month');
        $profile = $this->staffProfile($icno);

        $records = $this->db->createCommand(
            <<<SQL
            SELECT r.*, ref.jenis_wp
            FROM attendance.tbl_rekod r
            LEFT JOIN attendance.ref_wp ref ON ref.id = r.wp_id
            WHERE r.icno = :icno
              AND r.tarikh BETWEEN :start AND :end
            ORDER BY r.tarikh ASC
            SQL,
            [
                ':icno' => $icno,
                ':start' => $start->format('Y-m-d'),
                ':end' => $end->format('Y-m-d'),
            ],
        )->queryAll();

        $recordByDate = [];
        $workingMinutes = 0;
        $completedDays = 0;
        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $date = $this->value($record['tarikh'] ?? null, '');
            if ($date === '') {
                continue;
            }

            $recordByDate[$date] = $record;
            if (($record['time_in'] ?? null) !== null && ($record['time_out'] ?? null) !== null) {
                $completedDays++;
                $workingMinutes += $this->minutesBetween($record['time_in'], $record['time_out']);
            }
        }

        $tagsByDate = $this->dateTags($icno, $start, $end, (int) ($profile['campusId'] ?? 0));
        $rows = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $date = $cursor->format('Y-m-d');
            /** @var array<string, mixed>|null $record */
            $record = $recordByDate[$date] ?? null;
            $rows[] = [
                'date' => $date,
                'dateLabel' => $cursor->format('d/m/Y'),
                'day' => $cursor->format('D'),
                'wbb' => $record === null ? '-' : $this->value($record['jenis_wp'] ?? null),
                'timeIn' => $record === null ? '-' : $this->formatTime($record['time_in'] ?? null),
                'timeOut' => $record === null ? '-' : $this->formatTime($record['time_out'] ?? null),
                'statusLabels' => $record === null ? [] : $this->statusLabels($record),
                'leaveOutstation' => $tagsByDate[$date] ?? [],
                'workingHours' => $record === null ? '-' : $this->hoursBetween($record['time_in'] ?? null, $record['time_out'] ?? null),
                'remark' => $record === null ? '-' : $this->remarkLabel($record),
                'locations' => $record === null ? [] : $this->locationLinks($record),
                'recordId' => $record === null ? null : (string) ($record['id'] ?? ''),
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return [
            'profile' => $profile,
            'year' => $year,
            'month' => $month,
            'monthName' => $this->monthName($month),
            'cardColor' => $this->cardColor($icno, $month, $year),
            'summary' => [
                'completedDays' => $completedDays,
                'workingHours' => $this->formatMinutes($workingMinutes),
                'lateIn' => $this->nonComplianceCount($icno, $month, $year, 'late_in'),
                'earlyOut' => $this->nonComplianceCount($icno, $month, $year, 'early_out'),
                'incomplete' => $this->nonComplianceCount($icno, $month, $year, 'incomplete'),
                'absent' => $this->nonComplianceCount($icno, $month, $year, 'absent'),
                'external' => $this->nonComplianceCount($icno, $month, $year, 'external')
                    + $this->nonComplianceCount($icno, $month, $year, 'external_out'),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingReasonRecords(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, tarikh, time_in, time_out, late_in, early_out, incomplete,
                   absent, external, external_out
            FROM attendance.tbl_rekod
            WHERE icno = :icno
              AND remark_status IS NULL
              AND (late_in = 1 OR early_out = 1 OR incomplete = 1 OR absent = 1 OR external = 1 OR external_out = 1)
            ORDER BY tarikh ASC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        return $this->mapRows($rows, function (array $row): array {
            return [
                'id' => (string) ($row['id'] ?? ''),
                'date' => $this->formatDate($row['tarikh'] ?? null),
                'timeIn' => $this->formatTime($row['time_in'] ?? null),
                'timeOut' => $this->formatTime($row['time_out'] ?? null),
                'statusLabels' => $this->statusLabels($row),
                'remarkUrl' => self::LEGACY_BASE . '/kehadiran/remark?id=' . rawurlencode((string) ($row['id'] ?? '')),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function staffCardColorList(string $icno, int $year): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, month_date, ketidakpatuhan, approved, disapproved, color
            FROM attendance.tbl_warnakad
            WHERE icno = :icno
              AND YEAR(month_date) = :year
            ORDER BY month_date ASC
            SQL,
            [':icno' => $icno, ':year' => $year],
        )->queryAll();

        $totals = ['YELLOW' => 0, 'GREEN' => 0, 'RED' => 0];
        $list = $this->mapRows($rows, function (array $row) use (&$totals): array {
            $color = strtoupper($this->value($row['color'] ?? null, '-'));
            if (isset($totals[$color])) {
                $totals[$color]++;
            }

            $monthDate = $this->value($row['month_date'] ?? null, '');
            $month = $monthDate !== '' ? (int) date('n', strtotime($monthDate)) : 0;

            return [
                'month' => $month > 0 ? $this->monthName($month) : '-',
                'ketidakpatuhan' => (int) ($row['ketidakpatuhan'] ?? 0),
                'approved' => (int) ($row['approved'] ?? 0),
                'disapproved' => (int) ($row['disapproved'] ?? 0),
                'color' => $color,
            ];
        });

        return [
            'year' => $year,
            'totals' => $totals,
            'performance' => $this->cardPerformance($totals['GREEN'], $totals['RED']),
            'rows' => $list,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function wfhList(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT full_date, start_date, end_date, tempoh, remark, entry_dt, status
            FROM attendance.tbl_wfh
            WHERE icno = :icno
              AND YEAR(start_date) >= 2022
            ORDER BY start_date DESC, id DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        return $this->mapRows($rows, fn (array $row): array => [
            'date' => $this->value($row['full_date'] ?? null, $this->dateRangeText($row['start_date'] ?? null, $row['end_date'] ?? null)),
            'period' => $this->value($row['tempoh'] ?? null),
            'remark' => $this->value($row['remark'] ?? null),
            'entryDate' => $this->value($row['entry_dt'] ?? null),
            'status' => $this->value($row['status'] ?? null),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function absentList(string $icno, int $year, ?string $date = null): array
    {
        $params = [':icno' => $icno, ':year' => $year];
        $dateSql = '';
        if ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $dateSql = ' AND r.tarikh = :date';
            $params[':date'] = $date;
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.tarikh, r.day, r.remark_status, r.remark, r.remark_dt,
                   r.app_remark, r.app_by, app.CONm AS app_name,
                   thtc.id AS thtc_id, thtc.remark_verifier, thtc.supporting_doc,
                   thtc.status AS thtc_status, verifier.CONm AS verifier_name
            FROM attendance.tbl_rekod r
            LEFT JOIN attendance.tbl_thtc thtc ON thtc.tbl_rekod_id = r.id
            LEFT JOIN hronline.tblprcobiodata verifier ON verifier.ICNO = thtc.icno_verifier
            LEFT JOIN hronline.tblprcobiodata app ON app.ICNO = r.app_by
            WHERE r.icno = :icno
              AND r.absent = 1
              AND r.remark_status = 'REJECTED'
              AND YEAR(r.tarikh) = :year
              $dateSql
              AND NOT EXISTS (
                  SELECT 1
                  FROM hrm.cuti_tbl_records leave_record
                  WHERE leave_record.icno = r.icno
                    AND r.tarikh BETWEEN leave_record.start_date AND leave_record.end_date
                    AND leave_record.status <> 'REJECTED'
              )
            ORDER BY r.tarikh ASC
            SQL,
            $params,
        )->queryAll();

        return $this->mapRows($rows, function (array $row): array {
            $doc = $this->value($row['supporting_doc'] ?? null, '');

            return [
                'id' => (string) ($row['id'] ?? ''),
                'date' => $this->formatDate($row['tarikh'] ?? null),
                'day' => $this->value($row['day'] ?? null),
                'remarkStatus' => $this->value($row['remark_status'] ?? null),
                'remark' => $this->value($row['remark'] ?? null),
                'remarkDate' => $this->value($row['remark_dt'] ?? null),
                'approverRemark' => $this->value($row['app_remark'] ?? null),
                'hasLeave' => false,
                'verifierRemark' => $this->value($row['remark_verifier'] ?? null, ''),
                'documentUrl' => $doc !== '' && $doc !== 'deleted' ? 'https://mediahost.ums.edu.my/api/v1/viewFile/' . rawurlencode($doc) : '',
                'thtcStatus' => $this->thtcStatus((int) ($row['thtc_status'] ?? 0)),
                'verifier' => $this->employees->displayName($row['verifier_name'] ?? null),
                'approver' => $this->employees->displayName($row['app_name'] ?? null),
                'actionUrl' => ((int) ($row['thtc_status'] ?? 0) === 4 || ($row['thtc_id'] ?? null) === null)
                    ? self::LEGACY_BASE . '/kehadiran/remark-thtc?id=' . rawurlencode((string) ($row['id'] ?? ''))
                    : '',
            ];
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function officerStaff(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT DISTINCT staff.ICNO, staff.CONm, staff.COOldID, staff.DeptId, staff.campus_id,
                   dept.fullname AS department_name, dept.shortname AS department_shortname,
                   campus.campus_name, job.fname AS position_name, job.gred AS job_grade
            FROM hrm.cuti_set_pegawai officer
            INNER JOIN hronline.tblprcobiodata staff ON staff.ICNO = officer.pemohon_icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hronline.campus campus ON campus.campus_id = staff.campus_id
            LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
            WHERE (officer.peraku_icno = :icno OR officer.pelulus_icno = :icno)
              AND staff.ICNO IS NOT NULL
            ORDER BY staff.CONm
            SQL,
            [':icno' => $icno],
        )->queryAll();

        return $this->mapRows($rows, fn (array $row): array => [
            'icno' => $this->value($row['ICNO'] ?? null, ''),
            'name' => $this->employees->displayName($row['CONm'] ?? null),
            'staffNo' => $this->value($row['COOldID'] ?? null),
            'position' => $this->value($row['position_name'] ?? null),
            'grade' => $this->value($row['job_grade'] ?? null, $this->value($row['position_name'] ?? null)),
            'department' => $this->value($row['department_name'] ?? null),
            'departmentShort' => $this->value($row['department_shortname'] ?? null),
            'campus' => $this->value($row['campus_name'] ?? null),
            'deptId' => (int) ($row['DeptId'] ?? 0),
            'campusId' => (int) ($row['campus_id'] ?? 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function officerCardColorReport(string $icno, int $month, int $year): array
    {
        $month = max(1, min(12, $month));
        $staff = $this->officerStaff($icno);
        $totals = ['GREEN' => 0, 'YELLOW' => 0, 'RED' => 0, '-' => 0];
        $rows = [];

        foreach ($staff as $person) {
            $card = $this->cardColor((string) $person['icno'], $month, $year);
            $color = strtoupper((string) $card['color']);
            $totals[$color] = ($totals[$color] ?? 0) + 1;
            $rows[] = [
                'staff' => $person,
                'nonCompliance' => $card['nonCompliance'],
                'approved' => $card['approved'],
                'disapproved' => $card['disapproved'],
                'color' => $color,
            ];
        }

        return [
            'monthName' => $this->monthName($month),
            'month' => $month,
            'year' => $year,
            'totals' => $totals,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function officerMonthlySummary(string $icno, int $month, int $year): array
    {
        $month = max(1, min(12, $month));
        $rows = [];
        $totals = [
            'completedDays' => 0,
            'workingMinutes' => 0,
            'lateIn' => 0,
            'earlyOut' => 0,
            'incomplete' => 0,
            'absent' => 0,
            'external' => 0,
        ];

        foreach ($this->officerStaff($icno) as $person) {
            $summary = $this->monthlySummaryForStaff((string) $person['icno'], $month, $year);
            $totals['completedDays'] += (int) $summary['completedDays'];
            $totals['workingMinutes'] += (int) $summary['workingMinutes'];
            $totals['lateIn'] += (int) $summary['lateIn'];
            $totals['earlyOut'] += (int) $summary['earlyOut'];
            $totals['incomplete'] += (int) $summary['incomplete'];
            $totals['absent'] += (int) $summary['absent'];
            $totals['external'] += (int) $summary['external'];

            $rows[] = [
                'staff' => $person,
                'workingHours' => $this->formatMinutes((int) $summary['workingMinutes']),
                'completedDays' => $summary['completedDays'],
                'lateIn' => $summary['lateIn'],
                'earlyOut' => $summary['earlyOut'],
                'incomplete' => $summary['incomplete'],
                'absent' => $summary['absent'],
                'external' => $summary['external'],
            ];
        }

        return [
            'monthName' => $this->monthName($month),
            'month' => $month,
            'year' => $year,
            'totals' => array_merge($totals, ['workingHours' => $this->formatMinutes((int) $totals['workingMinutes'])]),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function officerDailyAttendance(string $icno, string $date): array
    {
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : (new DateTimeImmutable('today'))->format('Y-m-d');
        $staff = $this->officerStaff($icno);
        $records = $this->recordsForStaffDate($this->staffIcnoList($staff), $date);
        $rows = [];

        foreach ($staff as $person) {
            $record = $records[(string) $person['icno']] ?? null;
            $tags = $this->dateTags((string) $person['icno'], new DateTimeImmutable($date), new DateTimeImmutable($date), (int) $person['campusId']);
            $wbb = $record === null
                ? $this->value(($this->currentWorkingPattern((string) $person['icno'])['label'] ?? null), '-')
                : $this->value($record['jenis_wp'] ?? null);

            $rows[] = [
                'staff' => $person,
                'wbb' => $wbb,
                'timeIn' => $record === null ? '-' : $this->formatTime($record['time_in'] ?? null),
                'timeOut' => $record === null ? '-' : $this->formatTime($record['time_out'] ?? null),
                'leaveOutstation' => $tags[$date] ?? [],
                'statusLabels' => $record === null ? [] : $this->statusLabels($record),
                'locations' => $record === null ? [] : $this->locationLinks($record),
                'detailUrl' => $record === null ? '' : self::LEGACY_BASE . '/kehadiran/detailrekod?icno=' . rawurlencode((string) $person['icno']) . '&tarikh=' . rawurlencode($date),
                'reportUrl' => self::LEGACY_BASE . '/kehadiran/laporan_kehadiran?id=' . rawurlencode((string) $person['icno']),
                'historyUrl' => self::LEGACY_BASE . '/kehadiran/staff_history?icno=' . rawurlencode((string) $person['icno']),
            ];
        }

        return [
            'date' => $date,
            'dateLabel' => $this->formatDate($date),
            'rows' => $rows,
        ];
    }

    /**
     * @param array<string, string> $filters
     * @return list<array<string, mixed>>
     */
    public function officerWfhApplications(string $icno, array $filters = []): array
    {
        $params = [':icno' => $icno];
        $where = [
            'YEAR(wfh.start_date) > 2021',
            'wfh.app_by = :icno',
            "wfh.remark NOT LIKE '%ADDED BY PENYELIA%'",
        ];

        $status = strtoupper(trim($filters['status'] ?? ''));
        if (in_array($status, ['ENTRY', 'APPROVED', 'REJECTED', 'VERIFIED'], true)) {
            $where[] = 'wfh.status = :status';
            $params[':status'] = $status;
        }

        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $where[] = '(staff.CONm LIKE :search OR wfh.remark LIKE :search OR wfh.full_date LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $rows = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT wfh.id, wfh.icno, wfh.full_date, wfh.start_date, wfh.end_date, wfh.tempoh,
                       wfh.remark, wfh.entry_dt, wfh.app_dt, wfh.status,
                       staff.CONm AS staff_name, dept.shortname AS department_shortname, job.gred AS job_grade
                FROM attendance.tbl_wfh wfh
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = wfh.icno
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
                WHERE %s
                ORDER BY wfh.entry_dt DESC
                LIMIT 1000
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        return $this->mapRows($rows, fn (array $row): array => [
            'id' => (string) ($row['id'] ?? ''),
            'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
            'icno' => $this->value($row['icno'] ?? null),
            'grade' => $this->value($row['job_grade'] ?? null),
            'department' => $this->value($row['department_shortname'] ?? null),
            'date' => $this->value($row['full_date'] ?? null, $this->dateRangeText($row['start_date'] ?? null, $row['end_date'] ?? null)),
            'period' => $this->value($row['tempoh'] ?? null),
            'remark' => $this->value($row['remark'] ?? null),
            'entryDate' => $this->value($row['entry_dt'] ?? null),
            'approvalDate' => $this->value($row['app_dt'] ?? null),
            'status' => $this->value($row['status'] ?? null),
            'deleteUrl' => '/kehadiran/delete-wfh?id=' . rawurlencode((string) ($row['id'] ?? '')),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function officerPendingWfhApprovals(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT wfh.id, wfh.icno, wfh.full_date, wfh.start_date, wfh.end_date, wfh.tempoh,
                   wfh.remark, wfh.entry_dt, wfh.status,
                   staff.CONm AS staff_name, dept.shortname AS department_shortname, job.gred AS job_grade
            FROM attendance.tbl_wfh wfh
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = wfh.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
            WHERE wfh.app_by = :icno
              AND wfh.status = 'ENTRY'
            ORDER BY wfh.entry_dt DESC
            SQL,
            [':icno' => $icno],
        )->queryAll();

        return $this->mapRows($rows, fn (array $row): array => [
            'id' => (string) ($row['id'] ?? ''),
            'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
            'grade' => $this->value($row['job_grade'] ?? null),
            'department' => $this->value($row['department_shortname'] ?? null),
            'date' => $this->value($row['full_date'] ?? null, $this->dateRangeText($row['start_date'] ?? null, $row['end_date'] ?? null)),
            'period' => $this->value($row['tempoh'] ?? null),
            'remark' => $this->value($row['remark'] ?? null),
            'entryDate' => $this->value($row['entry_dt'] ?? null),
            'status' => $this->value($row['status'] ?? null),
            'actionUrl' => '/kehadiran/wfh-lulus-tindakan?id=' . rawurlencode((string) ($row['id'] ?? '')),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function officerPendingReasonApprovals(string $icno): array
    {
        $approvalIcno = $this->approvalIdentity($icno);
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.icno, r.tarikh, r.day, r.time_in, r.time_out, r.remark,
                   r.late_in, r.early_out, r.incomplete, r.absent, r.external, r.external_out,
                   staff.CONm AS staff_name, staff.campus_id
            FROM attendance.tbl_rekod r
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
            WHERE r.app_by = :icno
              AND r.remark_status = 'ENTRY'
            ORDER BY r.tarikh ASC, staff.CONm ASC
            SQL,
            [':icno' => $approvalIcno],
        )->queryAll();

        return $this->mapRows($rows, function (array $row): array {
            $date = $this->dateOnly($row['tarikh'] ?? null);
            $tags = $date === ''
                ? []
                : $this->dateTags((string) ($row['icno'] ?? ''), new DateTimeImmutable($date), new DateTimeImmutable($date), (int) ($row['campus_id'] ?? 0));

            return [
                'id' => (string) ($row['id'] ?? ''),
                'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                'date' => $this->formatDate($row['tarikh'] ?? null),
                'day' => $this->value($row['day'] ?? null),
                'timeIn' => $this->formatTime($row['time_in'] ?? null),
                'timeOut' => $this->formatTime($row['time_out'] ?? null),
                'statusLabels' => $this->statusLabels($row),
                'leaveOutstation' => $date === '' ? [] : ($tags[$date] ?? []),
                'remark' => $this->value($row['remark'] ?? null),
                'actionUrl' => '/kehadiran/pengesahan?id=' . rawurlencode((string) ($row['id'] ?? '')),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function officerThtcDepartmentList(string $icno): array
    {
        $department = $this->departmentPpScope($icno);
        if ($department === null) {
            return [
                'authorized' => false,
                'department' => null,
                'rows' => [],
            ];
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT thtc.id, thtc.icno, thtc.remark, thtc.date_entered, thtc.supporting_doc,
                   thtc.status, thtc.remark_verifier, thtc.remark_approver,
                   r.tarikh, r.day, r.remark AS absent_remark,
                   staff.CONm AS staff_name
            FROM attendance.tbl_thtc thtc
            LEFT JOIN attendance.tbl_rekod r ON r.id = thtc.tbl_rekod_id
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = thtc.icno
            WHERE staff.DeptId = :dept
              AND thtc.status <> 6
            ORDER BY r.tarikh DESC, thtc.date_entered DESC
            LIMIT 1000
            SQL,
            [':dept' => (int) $department['id']],
        )->queryAll();

        return [
            'authorized' => true,
            'department' => $department,
            'rows' => $this->mapRows($rows, function (array $row): array {
                $doc = $this->value($row['supporting_doc'] ?? null, '');

                return [
                    'id' => (string) ($row['id'] ?? ''),
                    'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                    'date' => $this->formatDate($row['tarikh'] ?? null),
                    'day' => $this->value($row['day'] ?? null),
                    'absentRemark' => $this->value($row['absent_remark'] ?? null),
                    'remark' => $this->value($row['remark'] ?? null),
                    'hasLeave' => 'No',
                    'documentUrl' => $doc !== '' && $doc !== 'deleted' ? 'https://mediahost.ums.edu.my/api/v1/viewFile/' . rawurlencode($doc) : '',
                    'status' => $this->thtcStatus((int) ($row['status'] ?? 0)),
                    'entryDate' => $this->value($row['date_entered'] ?? null),
                    'verifierRemark' => $this->value($row['remark_verifier'] ?? null),
                    'approverRemark' => $this->value($row['remark_approver'] ?? null),
                ];
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function supervisorAccessScope(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT akses_cuti_int, akses_jspiu_id, akses_kampus_id
            FROM hrm.cuti_access
            WHERE akses_cuti_icno = :icno
            SQL,
            [':icno' => $icno],
        )->queryAll();

        $deptIds = [];
        $campusIds = [];
        $admin = false;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $admin = $admin || (int) ($row['akses_cuti_int'] ?? 0) === 3;
            $deptId = (int) ($row['akses_jspiu_id'] ?? 0);
            $campusId = (int) ($row['akses_kampus_id'] ?? 0);
            if ($deptId > 0) {
                $deptIds[] = $deptId;
            }
            if ($campusId > 0) {
                $campusIds[] = $campusId;
            }
        }

        $deptIds = array_values(array_unique($deptIds));
        $campusIds = array_values(array_unique($campusIds));

        return [
            'authorized' => $rows !== [],
            'admin' => $admin,
            'departmentIds' => $deptIds,
            'campusIds' => $campusIds,
            'departments' => $this->supervisorDepartmentOptions($admin, $deptIds),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorStaffList(string $icno, array $filters = []): array
    {
        $scope = $this->supervisorAccessScope($icno);

        return [
            'scope' => $scope,
            'filters' => $filters,
            'rows' => $this->supervisorStaff($icno, $filters),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function supervisorStaffAttendanceReport(string $supervisorIcno, string $staffIcno, int $year, int $month): ?array
    {
        if (!$this->supervisorCanViewStaff($supervisorIcno, $staffIcno)) {
            return null;
        }

        return $this->attendanceReport($staffIcno, $year, $month);
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function supervisorStaff(string $icno, array $filters = []): array
    {
        $scope = $this->supervisorAccessScope($icno);
        if (!(bool) $scope['authorized']) {
            return [];
        }

        [$where, $params] = $this->supervisorStaffWhere($scope, $filters);
        if ($where === []) {
            return [];
        }

        $rows = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT staff.ICNO, staff.CONm, staff.COOldID, staff.DeptId, staff.campus_id,
                       dept.fullname AS department_name, dept.shortname AS department_shortname,
                       campus.campus_name, job.fname AS position_name, job.gred AS job_grade
                FROM hronline.tblprcobiodata staff
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                LEFT JOIN hronline.campus campus ON campus.campus_id = staff.campus_id
                LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
                WHERE %s
                ORDER BY staff.CONm ASC
                LIMIT 1000
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        return $this->mapRows($rows, fn (array $row): array => $this->mapStaffRow($row));
    }

    private function supervisorCanViewStaff(string $supervisorIcno, string $staffIcno): bool
    {
        $scope = $this->supervisorAccessScope($supervisorIcno);
        if (!(bool) ($scope['authorized'] ?? false) || trim($staffIcno) === '') {
            return false;
        }

        [$where, $params] = $this->supervisorStaffWhere($scope, []);
        if ($where === []) {
            return false;
        }

        $where[] = 'staff.ICNO = :staffIcno';
        $params[':staffIcno'] = $staffIcno;

        $row = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT staff.ICNO
                FROM hronline.tblprcobiodata staff
                LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
                WHERE %s
                LIMIT 1
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryOne();

        return is_array($row);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorStaffWbb(string $icno, array $filters = []): array
    {
        $staff = $this->supervisorStaff($icno, $filters);
        $icnos = $this->staffIcnoList($staff);
        $officers = $this->setPegawaiForStaff($icnos);
        $workingPatterns = $this->workingPatternLabelsFor($icnos);

        $rows = [];
        foreach ($staff as $person) {
            $staffIcno = (string) $person['icno'];
            $rows[] = [
                'staff' => $person,
                'peraku' => $officers[$staffIcno]['peraku'] ?? '-',
                'pelulus' => $officers[$staffIcno]['pelulus'] ?? '-',
                'wbb' => $workingPatterns[$staffIcno] ?? '--Tiada WBB--',
                'wbbUrl' => '/kehadiran/wbb-list?id=' . rawurlencode($staffIcno),
                'pegawaiUrl' => '/kehadiran/set-peg?id=' . rawurlencode($staffIcno),
            ];
        }

        return [
            'filters' => $filters,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function supervisorWbbList(string $supervisorIcno, string $staffIcno, ?int $wpId = null): ?array
    {
        if (!$this->supervisorCanViewStaff($supervisorIcno, $staffIcno)) {
            return null;
        }

        $staff = $this->staffProfile($staffIcno);
        if ($staff === null) {
            return null;
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT wp.id, wp.wp_id, wp.entry_dt, wp.start_date, wp.end_date, wp.status,
                   wp.remark, ref.jenis_wp, ref.detail
            FROM attendance.tbl_wp wp
            LEFT JOIN attendance.ref_wp ref ON ref.id = wp.wp_id
            WHERE wp.icno = :icno
            ORDER BY wp.start_date DESC, wp.id DESC
            SQL,
            [':icno' => $staffIcno],
        )->queryAll();

        $editRow = null;
        if ($wpId !== null) {
            $row = $this->db->createCommand(
                <<<SQL
                SELECT wp.id, wp.wp_id, wp.start_date, wp.end_date
                FROM attendance.tbl_wp wp
                WHERE wp.id = :id
                  AND wp.icno = :icno
                LIMIT 1
                SQL,
                [':id' => $wpId, ':icno' => $staffIcno],
            )->queryOne();
            $editRow = is_array($row) ? $row : null;
        }

        return [
            'staff' => $staff,
            'rows' => $this->mapRows($rows, fn (array $row): array => $this->mapWbbAssignmentRow($row)),
            'options' => $this->workingPatternOptions(),
            'form' => [
                'id' => $editRow === null ? null : (int) ($editRow['id'] ?? 0),
                'wpId' => $editRow === null ? '' : (string) ($editRow['wp_id'] ?? ''),
                'startDate' => $editRow === null ? '' : $this->dateOnly($editRow['start_date'] ?? null),
                'endDate' => $editRow === null ? '' : $this->dateOnly($editRow['end_date'] ?? null),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string}
     */
    public function saveSupervisorWbb(string $supervisorIcno, string $staffIcno, array $input, ?int $wpId = null): array
    {
        if (!$this->supervisorCanViewStaff($supervisorIcno, $staffIcno)) {
            return ['success' => false, 'message' => 'Kakitangan tidak dijumpai dalam skop penyelia.'];
        }

        $patternId = $this->positiveInt($input['wp_id'] ?? null);
        $startDate = $this->dateInput($input['start_date'] ?? null);
        $endDate = $this->dateInput($input['end_date'] ?? null);
        if ($patternId === null || !$this->workingPatternExists($patternId)) {
            return ['success' => false, 'message' => 'Pilih WBB yang sah.'];
        }
        if ($startDate === '') {
            return ['success' => false, 'message' => 'Tarikh mula diperlukan.'];
        }
        if ($endDate !== '' && $endDate < $startDate) {
            return ['success' => false, 'message' => 'Tarikh tamat tidak boleh lebih awal daripada tarikh mula.'];
        }

        $now = date('Y-m-d H:i:s');
        $fields = [
            'wp_id' => $patternId,
            'icno' => $staffIcno,
            'entry_dt' => $now,
            'remark' => 'Ditambah Oleh Penyelia J/F/P/I/U',
            'status' => 'APPROVED',
            'ver_by' => $supervisorIcno,
            'ver_dt' => $now,
            'ver_remark' => 'Ditambah Oleh Penyelia J/F/P/I/U',
            'app_by' => $supervisorIcno,
            'app_dt' => $now,
            'app_remark' => 'Ditambah Oleh Penyelia J/F/P/I/U',
            'start_date' => $startDate,
            'end_date' => $endDate === '' ? null : $endDate,
        ];

        if ($wpId !== null) {
            $exists = $this->db->createCommand(
                'SELECT id FROM attendance.tbl_wp WHERE id = :id AND icno = :icno LIMIT 1',
                [':id' => $wpId, ':icno' => $staffIcno],
            )->queryOne();
            if (!is_array($exists)) {
                return ['success' => false, 'message' => 'Rekod WBB tidak dijumpai.'];
            }

            $updated = (int) $this->db->createCommand()->update('attendance.tbl_wp', $fields, ['id' => $wpId, 'icno' => $staffIcno])->execute();

            return ['success' => $updated > 0, 'message' => $updated > 0 ? 'WBB telah dikemaskini.' : 'Tiada perubahan WBB disimpan.'];
        }

        $inserted = (int) $this->db->createCommand()->insert('attendance.tbl_wp', $fields)->execute();

        return ['success' => $inserted > 0, 'message' => $inserted > 0 ? 'WBB telah ditambah.' : 'WBB tidak berjaya ditambah.'];
    }

    /**
     * @return array{success: bool, message: string, staffIcno: string}
     */
    public function deleteSupervisorWbb(string $supervisorIcno, int $wpId): array
    {
        $row = $this->db->createCommand(
            'SELECT id, icno FROM attendance.tbl_wp WHERE id = :id LIMIT 1',
            [':id' => $wpId],
        )->queryOne();
        if (!is_array($row)) {
            return ['success' => false, 'message' => 'Rekod WBB tidak dijumpai.', 'staffIcno' => ''];
        }

        $staffIcno = (string) ($row['icno'] ?? '');
        if (!$this->supervisorCanViewStaff($supervisorIcno, $staffIcno)) {
            return ['success' => false, 'message' => 'Rekod WBB di luar skop penyelia.', 'staffIcno' => $staffIcno];
        }

        $deleted = (int) $this->db->createCommand()->delete('attendance.tbl_wp', ['id' => $wpId, 'icno' => $staffIcno])->execute();

        return ['success' => $deleted > 0, 'message' => $deleted > 0 ? 'WBB telah dibuang.' : 'WBB tidak berjaya dibuang.', 'staffIcno' => $staffIcno];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function supervisorOfficerSetup(string $supervisorIcno, string $staffIcno): ?array
    {
        if (!$this->supervisorCanViewStaff($supervisorIcno, $staffIcno)) {
            return null;
        }

        $staff = $this->staffProfile($staffIcno);
        if ($staff === null) {
            return null;
        }

        $officer = $this->db->createCommand(
            <<<SQL
            SELECT set_pegawai_id, pemohon_icno, peraku_icno, pelulus_icno, jenis_cuti_id, set_status
            FROM hrm.cuti_set_pegawai
            WHERE pemohon_icno = :icno
            ORDER BY set_status DESC, set_pegawai_id DESC
            LIMIT 1
            SQL,
            [':icno' => $staffIcno],
        )->queryOne();

        return [
            'staff' => $staff,
            'officer' => [
                'id' => is_array($officer) ? (int) ($officer['set_pegawai_id'] ?? 0) : null,
                'perakuIcno' => is_array($officer) ? $this->value($officer['peraku_icno'] ?? null, '') : '',
                'pelulusIcno' => is_array($officer) ? $this->value($officer['pelulus_icno'] ?? null, '') : '',
            ],
            'staffOptions' => $this->activeStaffOptions(),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string}
     */
    public function saveSupervisorOfficerSetup(string $supervisorIcno, string $staffIcno, array $input): array
    {
        if (!$this->supervisorCanViewStaff($supervisorIcno, $staffIcno)) {
            return ['success' => false, 'message' => 'Kakitangan tidak dijumpai dalam skop penyelia.'];
        }

        $perakuIcno = $this->value($input['peraku_icno'] ?? null, '');
        $pelulusIcno = $this->value($input['pelulus_icno'] ?? null, '');
        if ($pelulusIcno === '') {
            return ['success' => false, 'message' => 'Pegawai pelulus diperlukan.'];
        }
        if ($perakuIcno !== '' && !$this->activeStaffExists($perakuIcno)) {
            return ['success' => false, 'message' => 'Pegawai peraku tidak sah.'];
        }
        if (!$this->activeStaffExists($pelulusIcno)) {
            return ['success' => false, 'message' => 'Pegawai pelulus tidak sah.'];
        }

        $now = date('Y-m-d H:i:s');
        $fields = [
            'pemohon_icno' => $staffIcno,
            'jenis_cuti_id' => 1,
            'peraku_icno' => $perakuIcno === '' ? null : $perakuIcno,
            'pelulus_icno' => $pelulusIcno,
            'penyelia_set_icno' => $supervisorIcno,
            'set_datestamp' => $now,
            'set_status' => 1,
        ];

        $existing = $this->db->createCommand(
            <<<SQL
            SELECT set_pegawai_id
            FROM hrm.cuti_set_pegawai
            WHERE pemohon_icno = :icno
            ORDER BY set_status DESC, set_pegawai_id DESC
            LIMIT 1
            SQL,
            [':icno' => $staffIcno],
        )->queryOne();

        if (is_array($existing)) {
            $id = (int) ($existing['set_pegawai_id'] ?? 0);
            $updated = (int) $this->db->createCommand()->update('hrm.cuti_set_pegawai', $fields, ['set_pegawai_id' => $id])->execute();

            return ['success' => $updated > 0, 'message' => $updated > 0 ? 'Pegawai Peraku/Pelulus telah dikemaskini.' : 'Tiada perubahan pegawai disimpan.'];
        }

        $inserted = (int) $this->db->createCommand()->insert('hrm.cuti_set_pegawai', $fields)->execute();

        return ['success' => $inserted > 0, 'message' => $inserted > 0 ? 'Pegawai Peraku/Pelulus telah disimpan.' : 'Pegawai Peraku/Pelulus tidak berjaya disimpan.'];
    }

    /**
     * @return array<string, mixed>
     */
    public function supervisorMonthlyReport(string $icno, int $month, int $year, ?int $departmentId = null): array
    {
        $scope = $this->supervisorAccessScope($icno);
        $month = max(1, min(12, $month));
        if (!(bool) $scope['authorized']) {
            return ['scope' => $scope, 'month' => $month, 'year' => $year, 'rows' => [], 'totals' => []];
        }

        $totals = [
            'totalDay' => 0,
            'lateIn' => 0,
            'earlyOut' => 0,
            'incomplete' => 0,
            'absent' => 0,
            'external' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        $mapped = [];
        foreach ($this->supervisorStaff($icno, ['deptId' => $departmentId]) as $person) {
            $summary = $this->monthlySummaryForStaff((string) $person['icno'], $month, $year);
            $totals['totalDay'] += (int) $summary['completedDays'];
            $totals['lateIn'] += (int) $summary['lateIn'];
            $totals['earlyOut'] += (int) $summary['earlyOut'];
            $totals['incomplete'] += (int) $summary['incomplete'];
            $totals['absent'] += (int) $summary['absent'];
            $totals['external'] += (int) $summary['external'];

            $mapped[] = [
                'name' => $this->employees->displayName($person['name'] ?? null),
                'icno' => $this->value($person['icno'] ?? null),
                'department' => $this->value($person['departmentShort'] ?? null),
                'grade' => $this->value($person['grade'] ?? null),
                'totalDay' => (int) $summary['completedDays'],
                'lateIn' => (int) $summary['lateIn'],
                'earlyOut' => (int) $summary['earlyOut'],
                'incomplete' => (int) $summary['incomplete'],
                'absent' => (int) $summary['absent'],
                'external' => (int) $summary['external'],
                'approved' => 0,
                'rejected' => (int) $summary['lateIn'] + (int) $summary['earlyOut'] + (int) $summary['incomplete'] + (int) $summary['absent'] + (int) $summary['external'],
            ];
        }
        $totals['rejected'] = $totals['lateIn'] + $totals['earlyOut'] + $totals['incomplete'] + $totals['absent'] + $totals['external'];

        return [
            'scope' => $scope,
            'month' => $month,
            'monthName' => $this->monthName($month),
            'year' => $year,
            'departmentId' => $departmentId,
            'rows' => $mapped,
            'totals' => $totals,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorYearlyReport(string $icno, int $year, array $filters = []): array
    {
        $staff = $this->supervisorStaff($icno, $filters);
        $icnos = $this->staffIcnoList($staff);
        $summaries = $this->yearlyComplianceSummaries($icnos, $year);

        return [
            'year' => $year,
            'filters' => $filters,
            'rows' => array_map(function (array $person) use ($summaries): array {
                $summary = $summaries[(string) $person['icno']] ?? [
                    'workingDays' => 0,
                    'lateIn' => 0,
                    'earlyOut' => 0,
                    'incomplete' => 0,
                    'absent' => 0,
                    'external' => 0,
                ];

                return ['staff' => $person] + $summary;
            }, $staff),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorCardColorReport(string $icno, int $month, int $year, array $filters = []): array
    {
        $month = max(1, min(12, $month));
        $staff = $this->supervisorStaff($icno, $filters);
        $icnos = $this->staffIcnoList($staff);
        $cards = $this->cardColorRowsFor($icnos, $month, $year);
        $totals = ['GREEN' => 0, 'YELLOW' => 0, 'RED' => 0, '-' => 0];

        $rows = [];
        foreach ($staff as $person) {
            $card = $cards[(string) $person['icno']] ?? ['color' => '-', 'nonCompliance' => 0, 'approved' => 0, 'disapproved' => 0];
            $color = strtoupper((string) $card['color']);
            $totals[$color] = ($totals[$color] ?? 0) + 1;
            $rows[] = ['staff' => $person] + $card + ['color' => $color];
        }

        return [
            'month' => $month,
            'monthName' => $this->monthName($month),
            'year' => $year,
            'filters' => $filters,
            'totals' => $totals,
            'rows' => $rows,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorCardPerformanceReport(string $icno, int $year, array $filters = []): array
    {
        $staff = $this->supervisorStaff($icno, $filters);
        $icnos = $this->staffIcnoList($staff);
        $totalsByStaff = $this->cardColorYearTotalsFor($icnos, $year);

        $rows = [];
        foreach ($staff as $person) {
            $totals = $totalsByStaff[(string) $person['icno']] ?? ['GREEN' => 0, 'YELLOW' => 0, 'RED' => 0];
            $rows[] = [
                'staff' => $person,
                'green' => $totals['GREEN'],
                'yellow' => $totals['YELLOW'],
                'red' => $totals['RED'],
                'performance' => $this->cardPerformance((int) $totals['GREEN'], (int) $totals['RED']),
            ];
        }

        return [
            'year' => $year,
            'filters' => $filters,
            'rows' => $rows,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorPendingStaff(string $icno, array $filters = []): array
    {
        $staff = $this->supervisorStaff($icno, $filters);
        $icnos = $this->staffIcnoList($staff);
        if ($icnos === []) {
            return ['filters' => $filters, 'rows' => []];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.icno, r.tarikh, r.day, r.time_in, r.time_out, r.remark,
                   r.late_in, r.early_out, r.incomplete, r.absent, r.external, r.external_out,
                   staff.CONm AS staff_name, dept.shortname AS department_shortname, job.gred AS job_grade
            FROM attendance.tbl_rekod r
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
            WHERE r.icno IN ($inSql)
              AND r.remark_status = 'ENTRY'
            ORDER BY r.tarikh ASC, staff.CONm ASC
            LIMIT 1000
            SQL,
            $params,
        )->queryAll();

        return [
            'filters' => $filters,
            'rows' => $this->mapRows($rows, fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                'grade' => $this->value($row['job_grade'] ?? null),
                'department' => $this->value($row['department_shortname'] ?? null),
                'date' => $this->formatDate($row['tarikh'] ?? null),
                'day' => $this->value($row['day'] ?? null),
                'timeIn' => $this->formatTime($row['time_in'] ?? null),
                'timeOut' => $this->formatTime($row['time_out'] ?? null),
                'statusLabels' => $this->statusLabels($row),
                'remark' => $this->value($row['remark'] ?? null),
                'actionUrl' => self::LEGACY_BASE . '/kehadiran/kemaskini-peraku?id=' . rawurlencode((string) ($row['id'] ?? '')),
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorShieldStatus(string $icno, array $filters = []): array
    {
        $staff = $this->supervisorStaff($icno, $filters);
        $icnos = $this->staffIcnoList($staff);
        $shieldRows = $this->shieldRowsFor($icnos);

        $rows = [];
        foreach ($staff as $person) {
            $shield = $shieldRows[(string) $person['icno']] ?? null;
            $rows[] = [
                'staff' => $person,
                'color' => $shield['color'] ?? 'BELUM ISI SHIELDS',
                'takenAt' => $shield['takenAt'] ?? '-',
                'expiresAt' => $shield['expiresAt'] ?? '-',
                'liveUrl' => self::LEGACY_BASE . '/kehadiran/shield-status-list',
            ];
        }

        return [
            'filters' => $filters,
            'usesLegacyConnection' => $shieldRows === [],
            'rows' => $rows,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorShiftStaff(string $icno, array $filters = []): array
    {
        $params = [':icno' => $icno];
        $where = ['shift_staff.sup_icno = :icno'];
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(staff.CONm LIKE :search OR staff.COOldID LIKE :search OR staff.ICNO LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $rows = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT shift_staff.id, shift_staff.created_at, shift_staff.staff_icno,
                       staff.CONm AS staff_name, staff.COOldID, dept.shortname AS department_shortname, job.gred AS job_grade
                FROM attendance.tbl_staffs shift_staff
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = shift_staff.staff_icno
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
                WHERE %s
                ORDER BY staff.CONm ASC
                LIMIT 1000
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        return [
            'filters' => $filters,
            'rows' => $this->mapRows($rows, fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                'staffNo' => $this->value($row['COOldID'] ?? null),
                'icno' => $this->value($row['staff_icno'] ?? null),
                'grade' => $this->value($row['job_grade'] ?? null),
                'department' => $this->value($row['department_shortname'] ?? null),
                'createdAt' => $this->value($row['created_at'] ?? null),
                'removeUrl' => self::LEGACY_BASE . '/kehadiran/remove-staff?id=' . rawurlencode((string) ($row['id'] ?? '')),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function supervisorShiftSetup(string $icno, int $month, int $year): array
    {
        $month = max(1, min(12, $month));
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('last day of this month');
        $shiftStaff = $this->supervisorShiftStaff($icno)['rows'];
        $icnos = array_values(array_filter(array_map(static fn (array $row): string => (string) ($row['icno'] ?? ''), $shiftStaff)));
        $shiftMap = $this->shiftRowsFor($icnos, $start->format('Y-m-d'), $end->format('Y-m-d'));

        $days = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $days[] = [
                'date' => $cursor->format('Y-m-d'),
                'day' => $cursor->format('d'),
                'weekday' => $cursor->format('D'),
            ];
            $cursor = $cursor->modify('+1 day');
        }

        $rows = [];
        foreach ($shiftStaff as $person) {
            $cells = [];
            foreach ($days as $day) {
                $shift = $shiftMap[(string) $person['icno']][$day['date']] ?? null;
                $cells[] = [
                    'date' => $day['date'],
                    'label' => $shift['label'] ?? '-',
                    'url' => $shift === null
                        ? self::LEGACY_BASE . '/kehadiran/create-shift?icno=' . rawurlencode((string) $person['icno']) . '&tarikh=' . rawurlencode($day['date'])
                        : self::LEGACY_BASE . '/kehadiran/update-shift?id=' . rawurlencode((string) $shift['id']),
                ];
            }
            $rows[] = ['staff' => $person, 'cells' => $cells];
        }

        return [
            'month' => $month,
            'monthName' => $this->monthName($month),
            'year' => $year,
            'days' => $days,
            'rows' => $rows,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorManageShiftSupervisor(string $icno, array $filters = []): array
    {
        if (!$this->hasAccess($icno, 3)) {
            return ['authorized' => false, 'filters' => $filters, 'rows' => []];
        }

        $params = [];
        $where = ['1 = 1'];
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(staff.CONm LIKE :search OR supervisor.CONm LIKE :search OR shift_staff.staff_icno LIKE :search OR shift_staff.sup_icno LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $rows = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT shift_staff.id, shift_staff.staff_icno, shift_staff.sup_icno, shift_staff.created_at,
                       staff.CONm AS staff_name, supervisor.CONm AS supervisor_name,
                       dept.shortname AS department_shortname
                FROM attendance.tbl_staffs shift_staff
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = shift_staff.staff_icno
                LEFT JOIN hronline.tblprcobiodata supervisor ON supervisor.ICNO = shift_staff.sup_icno
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                WHERE %s
                ORDER BY supervisor.CONm ASC, staff.CONm ASC
                LIMIT 1000
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        return [
            'authorized' => true,
            'filters' => $filters,
            'rows' => $this->mapRows($rows, fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                'staffIcno' => $this->value($row['staff_icno'] ?? null),
                'supervisorName' => $this->employees->displayName($row['supervisor_name'] ?? null),
                'supervisorIcno' => $this->value($row['sup_icno'] ?? null),
                'department' => $this->value($row['department_shortname'] ?? null),
                'createdAt' => $this->value($row['created_at'] ?? null),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function supervisorWfhByMonth(string $icno, int $month, int $year, ?int $departmentId = null): array
    {
        $month = max(1, min(12, $month));
        $filters = ['deptId' => $departmentId];
        $staff = $this->supervisorStaff($icno, $filters);
        $icnos = $this->staffIcnoList($staff);
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('last day of this month');
        $counts = $this->wfhCountsByDate($icnos, $start->format('Y-m-d'), $end->format('Y-m-d'));
        $totalStaff = count($staff);

        $rows = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $date = $cursor->format('Y-m-d');
            $totalWfh = $counts[$date] ?? 0;
            $rows[] = [
                'date' => $date,
                'dateLabel' => $this->formatDate($date),
                'day' => $cursor->format('D'),
                'wfh' => $totalWfh,
                'wfo' => max(0, $totalStaff - $totalWfh),
                'totalStaff' => $totalStaff,
                'editUrl' => '/kehadiran/wfh-edit-day?date=' . rawurlencode($date) . ($departmentId === null ? '' : '&dept_id=' . rawurlencode((string) $departmentId)),
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return [
            'month' => $month,
            'monthName' => $this->monthName($month),
            'year' => $year,
            'departmentId' => $departmentId,
            'totalStaff' => $totalStaff,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function supervisorWfhDay(string $icno, string $date, ?int $departmentId = null): ?array
    {
        $date = $this->dateOnly($date);
        if ($date === '') {
            return null;
        }

        $staff = $this->supervisorStaff($icno, ['deptId' => $departmentId]);
        $icnos = $this->staffIcnoList($staff);
        $totalStaff = count($staff);
        if ($icnos === []) {
            return [
                'date' => $date,
                'dateLabel' => $this->formatDate($date),
                'departmentId' => $departmentId,
                'totalStaff' => 0,
                'totalWfh' => 0,
                'totalWfo' => 0,
                'percentWfh' => 0.0,
                'percentWfo' => 0.0,
                'wfhRows' => [],
                'availableStaff' => [],
            ];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $params[':date'] = $date;
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT wfh.id, wfh.icno, wfh.start_date, wfh.end_date, wfh.full_date, wfh.remark, wfh.status,
                   staff.CONm AS staff_name, staff.COOldID, dept.shortname AS department_shortname, job.gred AS job_grade
            FROM attendance.tbl_wfh wfh
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = wfh.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
            WHERE wfh.icno IN ($inSql)
              AND wfh.status = 'APPROVED'
              AND wfh.start_date <= :date
              AND wfh.end_date >= :date
            ORDER BY staff.CONm ASC
            SQL,
            $params,
        )->queryAll();

        $wfhRows = $this->mapRows($rows, fn (array $row): array => [
            'id' => (string) ($row['id'] ?? ''),
            'icno' => $this->value($row['icno'] ?? null, ''),
            'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
            'staffNo' => $this->value($row['COOldID'] ?? null),
            'grade' => $this->value($row['job_grade'] ?? null),
            'department' => $this->value($row['department_shortname'] ?? null),
            'dateRange' => $this->dateRangeText($row['start_date'] ?? null, $row['end_date'] ?? null),
            'remark' => $this->value($row['remark'] ?? null),
            'status' => $this->value($row['status'] ?? null),
        ]);

        $wfhIcnos = array_values(array_unique(array_filter(array_map(static fn (array $row): string => (string) ($row['icno'] ?? ''), $wfhRows))));
        $availableStaff = [];
        foreach ($staff as $person) {
            if (!in_array((string) ($person['icno'] ?? ''), $wfhIcnos, true)) {
                $availableStaff[] = $person;
            }
        }

        $totalWfh = count($wfhIcnos);
        $totalWfo = max(0, $totalStaff - $totalWfh);

        return [
            'date' => $date,
            'dateLabel' => $this->formatDate($date),
            'departmentId' => $departmentId,
            'totalStaff' => $totalStaff,
            'totalWfh' => $totalWfh,
            'totalWfo' => $totalWfo,
            'percentWfh' => $totalStaff === 0 ? 0.0 : round($totalWfh / $totalStaff * 100, 2),
            'percentWfo' => $totalStaff === 0 ? 0.0 : round($totalWfo / $totalStaff * 100, 2),
            'wfhRows' => $wfhRows,
            'availableStaff' => $availableStaff,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string}
     */
    public function saveSupervisorWfhDay(string $icno, string $date, array $input, ?int $departmentId = null): array
    {
        $day = $this->supervisorWfhDay($icno, $date, $departmentId);
        if ($day === null) {
            return ['success' => false, 'message' => 'Tarikh WFH tidak sah.'];
        }

        $mode = (string) ($input['mode'] ?? '');
        if ($mode === 'remove') {
            $ids = $this->intList($input['remove_id'] ?? []);
            if ($ids === []) {
                return ['success' => false, 'message' => 'Pilih rekod WFH untuk ditukar ke WFO.'];
            }

            $allowedIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $day['wfhRows']);
            $ids = array_values(array_intersect($ids, $allowedIds));
            if ($ids === []) {
                return ['success' => false, 'message' => 'Rekod WFH tidak dijumpai dalam skop penyelia.'];
            }

            $deleted = $this->db->transaction(function () use ($ids): int {
                $count = 0;
                foreach ($ids as $id) {
                    $count += (int) $this->db->createCommand()->delete('attendance.tbl_wfh', ['id' => $id])->execute();
                }

                return $count;
            });

            return ['success' => $deleted > 0, 'message' => $deleted > 0 ? 'Rekod WFH telah ditukar ke WFO.' : 'Tiada rekod WFH dikemaskini.'];
        }

        if ($mode === 'add') {
            $selectedIcnos = array_values(array_unique(array_filter(array_map('strval', (array) ($input['add_icno'] ?? [])))));
            if ($selectedIcnos === []) {
                return ['success' => false, 'message' => 'Pilih kakitangan untuk dimasukkan ke WFH.'];
            }

            $availableIcnos = array_map(static fn (array $row): string => (string) ($row['icno'] ?? ''), $day['availableStaff']);
            $selectedIcnos = array_values(array_intersect($selectedIcnos, $availableIcnos));
            if ($selectedIcnos === []) {
                return ['success' => false, 'message' => 'Kakitangan tidak dijumpai dalam skop penyelia atau sudah WFH.'];
            }

            $date = (string) $day['date'];
            $now = date('Y-m-d H:i:s');
            $fullDate = $this->formatDate($date) . ' to ' . $this->formatDate($date);
            $inserted = $this->db->transaction(function () use ($selectedIcnos, $date, $now, $fullDate, $icno): int {
                $count = 0;
                foreach ($selectedIcnos as $staffIcno) {
                    $count += (int) $this->db->createCommand()->insert('attendance.tbl_wfh', [
                        'icno' => $staffIcno,
                        'entry_dt' => $now,
                        'start_date' => $date,
                        'end_date' => $date,
                        'full_date' => $fullDate,
                        'tempoh' => 1,
                        'set_by' => $icno,
                        'status' => 'APPROVED',
                        'remark' => 'ADDED BY PENYELIA',
                    ])->execute();
                }

                return $count;
            });

            return ['success' => $inserted > 0, 'message' => $inserted > 0 ? 'Kakitangan telah dimasukkan ke senarai WFH.' : 'Tiada rekod WFH ditambah.'];
        }

        return ['success' => false, 'message' => 'Tindakan WFH tidak disokong.'];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function supervisorWfhSchedule(string $icno, int $month, int $year, array $filters = [], string $selectedStaffIcno = ''): array
    {
        $month = max(1, min(12, $month));
        $staff = $this->supervisorStaff($icno, $filters);
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('last day of this month');
        $selectedStaffIcno = trim($selectedStaffIcno);

        $days = [];
        $rows = [];
        $selectedStaff = null;
        if ($selectedStaffIcno !== '') {
            foreach ($staff as $person) {
                if ((string) ($person['icno'] ?? '') === $selectedStaffIcno) {
                    $selectedStaff = $person;
                    break;
                }
            }
        }

        if ($selectedStaff !== null) {
            $cursor = $start;
            while ($cursor <= $end) {
                $days[] = [
                    'date' => $cursor->format('Y-m-d'),
                    'day' => $cursor->format('d'),
                    'weekday' => $cursor->format('D'),
                ];
                $cursor = $cursor->modify('+1 day');
            }

            $wfhDays = $this->wfhDaysFor([$selectedStaffIcno], $start->format('Y-m-d'), $end->format('Y-m-d'));
            $cells = [];
            foreach ($days as $day) {
                $cells[] = [
                    'date' => $day['date'],
                    'status' => isset($wfhDays[$selectedStaffIcno][$day['date']]) ? 'WFH' : 'WFO',
                ];
            }
            $rows[] = ['staff' => $selectedStaff, 'cells' => $cells];
        } else {
            foreach ($staff as $person) {
                $rows[] = ['staff' => $person];
            }
        }

        return [
            'month' => $month,
            'monthName' => $this->monthName($month),
            'year' => $year,
            'departmentId' => is_numeric($filters['deptId'] ?? null) ? (int) $filters['deptId'] : null,
            'filters' => $filters,
            'selectedStaffIcno' => $selectedStaffIcno,
            'selectedStaff' => $selectedStaff,
            'days' => $days,
            'rows' => $rows,
        ];
    }

    /**
     * @param array<string, string> $filters
     * @return list<array<string, mixed>>
     */
    public function supervisorWfhApplications(string $icno, array $filters = []): array
    {
        $staff = $this->supervisorStaff($icno);
        $icnos = $this->staffIcnoList($staff);
        if ($icnos === []) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $where = [
            'YEAR(wfh.start_date) > 2021',
            "wfh.remark NOT LIKE '%ADDED BY PENYELIA%'",
            "wfh.icno IN ($inSql)",
        ];

        $status = strtoupper(trim($filters['status'] ?? ''));
        if (in_array($status, ['ENTRY', 'APPROVED', 'REJECTED', 'VERIFIED'], true)) {
            $where[] = 'wfh.status = :status';
            $params[':status'] = $status;
        }

        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $where[] = '(staff.CONm LIKE :search OR wfh.remark LIKE :search OR wfh.full_date LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $rows = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT wfh.id, wfh.icno, wfh.full_date, wfh.start_date, wfh.end_date, wfh.tempoh,
                       wfh.remark, wfh.entry_dt, wfh.app_dt, wfh.status,
                       staff.CONm AS staff_name, dept.shortname AS department_shortname, job.gred AS job_grade
                FROM attendance.tbl_wfh wfh
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = wfh.icno
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
                WHERE %s
                ORDER BY wfh.entry_dt DESC
                LIMIT 1000
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        return $this->mapRows($rows, fn (array $row): array => [
            'id' => (string) ($row['id'] ?? ''),
            'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
            'icno' => $this->value($row['icno'] ?? null),
            'grade' => $this->value($row['job_grade'] ?? null),
            'department' => $this->value($row['department_shortname'] ?? null),
            'date' => $this->value($row['full_date'] ?? null, $this->dateRangeText($row['start_date'] ?? null, $row['end_date'] ?? null)),
            'period' => $this->value($row['tempoh'] ?? null),
            'remark' => $this->value($row['remark'] ?? null),
            'entryDate' => $this->value($row['entry_dt'] ?? null),
            'approvalDate' => $this->value($row['app_dt'] ?? null),
            'status' => $this->value($row['status'] ?? null),
            'deleteUrl' => '/kehadiran/delete-wfh?id=' . rawurlencode((string) ($row['id'] ?? '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function supervisorThtcList(string $icno): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT thtc.id, thtc.icno, thtc.remark, thtc.date_entered, thtc.supporting_doc,
                   thtc.status, thtc.remark_verifier, thtc.remark_approver,
                   r.tarikh, r.day, r.remark AS absent_remark,
                   staff.CONm AS staff_name, dept.shortname AS department_shortname
            FROM attendance.tbl_thtc thtc
            LEFT JOIN attendance.tbl_rekod r ON r.id = thtc.tbl_rekod_id
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = thtc.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            WHERE thtc.icno_verifier LIKE :icno
              AND thtc.status <> 6
            ORDER BY r.tarikh DESC, thtc.date_entered DESC
            LIMIT 1000
            SQL,
            [':icno' => '%' . $icno . '%'],
        )->queryAll();

        return [
            'rows' => $this->mapRows($rows, function (array $row): array {
                $doc = $this->value($row['supporting_doc'] ?? null, '');

                return [
                    'id' => (string) ($row['id'] ?? ''),
                    'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                    'department' => $this->value($row['department_shortname'] ?? null),
                    'date' => $this->formatDate($row['tarikh'] ?? null),
                    'day' => $this->value($row['day'] ?? null),
                    'absentRemark' => $this->value($row['absent_remark'] ?? null),
                    'remark' => $this->value($row['remark'] ?? null),
                    'documentUrl' => $doc !== '' && $doc !== 'deleted' ? 'https://mediahost.ums.edu.my/api/v1/viewFile/' . rawurlencode($doc) : '',
                    'status' => $this->thtcStatus((int) ($row['status'] ?? 0)),
                    'entryDate' => $this->value($row['date_entered'] ?? null),
                    'verifierRemark' => $this->value($row['remark_verifier'] ?? null),
                    'approverRemark' => $this->value($row['remark_approver'] ?? null),
                    'actionUrl' => '/kehadiran/verify-thtc-sv?id=' . rawurlencode((string) ($row['id'] ?? '')),
                ];
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentChiefScope(string $icno): array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT dept.id, dept.fullname, dept.shortname
            FROM hronline.department dept
            WHERE dept.chief = :icno
              AND dept.isActive = 1
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        if (!is_array($row)) {
            return ['authorized' => false, 'department' => null];
        }

        return [
            'authorized' => true,
            'department' => [
                'id' => (int) ($row['id'] ?? 0),
                'name' => $this->value($row['fullname'] ?? null),
                'shortName' => $this->value($row['shortname'] ?? null),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function departmentChiefStaff(string $icno): array
    {
        $scope = $this->departmentChiefScope($icno);
        if (!(bool) $scope['authorized']) {
            return [];
        }

        $department = $scope['department'];
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT staff.ICNO, staff.CONm, staff.COOldID, staff.DeptId, staff.campus_id,
                   dept.fullname AS department_name, dept.shortname AS department_shortname,
                   campus.campus_name, job.fname AS position_name, job.gred AS job_grade
            FROM hronline.tblprcobiodata staff
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hronline.campus campus ON campus.campus_id = staff.campus_id
            LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
            WHERE staff.Status = 1
              AND staff.DeptId = :dept
            ORDER BY staff.CONm ASC
            LIMIT 1000
            SQL,
            [':dept' => (int) ($department['id'] ?? 0)],
        )->queryAll();

        return $this->mapRows($rows, fn (array $row): array => $this->mapStaffRow($row));
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentChiefDailyAttendance(string $icno, string $date): array
    {
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : (new DateTimeImmutable('today'))->format('Y-m-d');
        $staff = $this->departmentChiefStaff($icno);
        $records = $this->recordsForStaffDate($this->staffIcnoList($staff), $date);
        $rows = [];

        foreach ($staff as $person) {
            $record = $records[(string) $person['icno']] ?? null;
            $tags = $this->dateTags((string) $person['icno'], new DateTimeImmutable($date), new DateTimeImmutable($date), (int) $person['campusId']);
            $wbb = $record === null
                ? $this->value(($this->currentWorkingPattern((string) $person['icno'])['label'] ?? null), '-')
                : $this->value($record['jenis_wp'] ?? null);

            $rows[] = [
                'staff' => $person,
                'wbb' => $wbb,
                'timeIn' => $record === null ? '-' : $this->formatTime($record['time_in'] ?? null),
                'timeOut' => $record === null ? '-' : $this->formatTime($record['time_out'] ?? null),
                'leaveOutstation' => $tags[$date] ?? [],
                'statusLabels' => $record === null ? [] : $this->statusLabels($record),
                'locations' => $record === null ? [] : $this->locationLinks($record),
                'detailUrl' => $record === null ? '' : self::LEGACY_BASE . '/kehadiran/detailrekod?icno=' . rawurlencode((string) $person['icno']) . '&tarikh=' . rawurlencode($date),
                'reportUrl' => self::LEGACY_BASE . '/kehadiran/laporan_kehadiran?id=' . rawurlencode((string) $person['icno']),
                'historyUrl' => self::LEGACY_BASE . '/kehadiran/staff_history?icno=' . rawurlencode((string) $person['icno']),
            ];
        }

        return [
            'scope' => $this->departmentChiefScope($icno),
            'date' => $date,
            'dateLabel' => $this->formatDate($date),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentChiefWfhSchedule(string $icno, int $month, int $year): array
    {
        $month = max(1, min(12, $month));
        $staff = $this->departmentChiefStaff($icno);
        $icnos = $this->staffIcnoList($staff);
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('last day of this month');
        $wfhDays = $this->wfhDaysFor($icnos, $start->format('Y-m-d'), $end->format('Y-m-d'));
        $days = [];
        $rows = [];

        $cursor = $start;
        while ($cursor <= $end) {
            $days[] = [
                'date' => $cursor->format('Y-m-d'),
                'day' => $cursor->format('d'),
                'weekday' => $cursor->format('D'),
            ];
            $cursor = $cursor->modify('+1 day');
        }

        foreach ($staff as $person) {
            $cells = [];
            foreach ($days as $day) {
                $cells[] = [
                    'date' => $day['date'],
                    'status' => isset($wfhDays[(string) $person['icno']][$day['date']]) ? 'WFH' : 'WFO',
                ];
            }
            $rows[] = ['staff' => $person, 'cells' => $cells];
        }

        return [
            'scope' => $this->departmentChiefScope($icno),
            'month' => $month,
            'monthName' => $this->monthName($month),
            'year' => $year,
            'days' => $days,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentChiefThtcList(string $icno): array
    {
        $scope = $this->departmentChiefScope($icno);
        if (!(bool) $scope['authorized']) {
            return ['scope' => $scope, 'rows' => []];
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT thtc.id, thtc.icno, thtc.remark, thtc.date_entered, thtc.supporting_doc,
                   thtc.status, thtc.remark_verifier, thtc.remark_approver,
                   r.tarikh, r.day, r.remark AS absent_remark,
                   staff.CONm AS staff_name, dept.shortname AS department_shortname
            FROM attendance.tbl_thtc thtc
            LEFT JOIN attendance.tbl_rekod r ON r.id = thtc.tbl_rekod_id
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = thtc.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            WHERE thtc.icno_approver LIKE :icno
              AND thtc.status <> 6
            ORDER BY r.tarikh DESC, thtc.date_entered DESC
            LIMIT 1000
            SQL,
            [':icno' => '%' . $icno . '%'],
        )->queryAll();

        return [
            'scope' => $scope,
            'rows' => $this->mapRows($rows, function (array $row): array {
                $doc = $this->value($row['supporting_doc'] ?? null, '');

                return [
                    'id' => (string) ($row['id'] ?? ''),
                    'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                    'department' => $this->value($row['department_shortname'] ?? null),
                    'date' => $this->formatDate($row['tarikh'] ?? null),
                    'day' => $this->value($row['day'] ?? null),
                    'absentRemark' => $this->value($row['absent_remark'] ?? null),
                    'remark' => $this->value($row['remark'] ?? null),
                    'documentUrl' => $doc !== '' && $doc !== 'deleted' ? 'https://mediahost.ums.edu.my/api/v1/viewFile/' . rawurlencode($doc) : '',
                    'status' => $this->thtcStatus((int) ($row['status'] ?? 0)),
                    'entryDate' => $this->value($row['date_entered'] ?? null),
                    'verifierRemark' => $this->value($row['remark_verifier'] ?? null),
                    'approverRemark' => $this->value($row['remark_approver'] ?? null),
                    'actionUrl' => '/kehadiran/approve-thtc?id=' . rawurlencode((string) ($row['id'] ?? '')),
                ];
            }),
        ];
    }

    public function isAdministrator(string $icno): bool
    {
        return $this->hasAccess($icno, 3);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function administratorStaffList(string $icno, array $filters = []): array
    {
        if (!$this->isAdministrator($icno)) {
            return ['authorized' => false, 'filters' => $filters, 'rows' => []];
        }

        return [
            'authorized' => true,
            'filters' => $filters,
            'rows' => $this->supervisorStaff($icno, $filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function administratorRawAttendance(string $icno, array $filters = []): array
    {
        if (!$this->isAdministrator($icno)) {
            return ['authorized' => false, 'filters' => $filters, 'rows' => []];
        }

        $params = [];
        $where = ['1 = 1'];
        $date = trim((string) ($filters['date'] ?? ''));
        $search = trim((string) ($filters['q'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $where[] = 'r.tarikh = :date';
            $params[':date'] = $date;
        } else {
            $where[] = 'r.tarikh >= :date';
            $params[':date'] = (new DateTimeImmutable('today'))->modify('-7 days')->format('Y-m-d');
        }
        if ($search !== '') {
            $where[] = '(staff.CONm LIKE :search OR staff.COOldID LIKE :search OR r.icno LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $rows = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT r.id, r.icno, r.tarikh, r.day, r.time_in, r.time_out, r.remark_status,
                       r.late_in, r.early_out, r.incomplete, r.absent, r.external, r.external_out,
                       staff.CONm AS staff_name, dept.shortname AS department_shortname, ref.jenis_wp
                FROM attendance.tbl_rekod r
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                LEFT JOIN attendance.ref_wp ref ON ref.id = r.wp_id
                WHERE %s
                ORDER BY r.tarikh DESC, staff.CONm ASC
                LIMIT 1000
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        return [
            'authorized' => true,
            'filters' => $filters,
            'rows' => $this->mapRows($rows, fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                'icno' => $this->value($row['icno'] ?? null),
                'department' => $this->value($row['department_shortname'] ?? null),
                'date' => $this->formatDate($row['tarikh'] ?? null),
                'day' => $this->value($row['day'] ?? null),
                'wbb' => $this->value($row['jenis_wp'] ?? null),
                'timeIn' => $this->formatTime($row['time_in'] ?? null),
                'timeOut' => $this->formatTime($row['time_out'] ?? null),
                'statusLabels' => $this->statusLabels($row),
                'remarkStatus' => $this->value($row['remark_status'] ?? null),
                'detailUrl' => self::LEGACY_BASE . '/attendance/view?id=' . rawurlencode((string) ($row['id'] ?? '')),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function administratorWarningLetters(string $icno, int $month, int $year, string $color): array
    {
        if (!$this->isAdministrator($icno)) {
            return ['authorized' => false, 'month' => $month, 'year' => $year, 'color' => $color, 'rows' => []];
        }

        $month = max(1, min(12, $month));
        $color = strtoupper($color);
        if (!in_array($color, ['RED', 'YELLOW', 'GREEN'], true)) {
            $color = 'RED';
        }

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT card.id, card.icno, card.month_date, card.ketidakpatuhan, card.approved,
                   card.disapproved, card.color, staff.CONm AS staff_name,
                   dept.shortname AS department_shortname, job.gred AS job_grade
            FROM attendance.tbl_warnakad card
            INNER JOIN hronline.tblprcobiodata staff ON staff.ICNO = card.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
            WHERE MONTH(card.month_date) = :month
              AND YEAR(card.month_date) = :year
              AND UPPER(card.color) = :color
              AND COALESCE(job.gred_skim, '') NOT IN ('KP')
            ORDER BY staff.DeptId, staff.CONm
            LIMIT 1000
            SQL,
            [':month' => $month, ':year' => $year, ':color' => $color],
        )->queryAll();

        return [
            'authorized' => true,
            'month' => $month,
            'monthName' => $this->monthName($month),
            'year' => $year,
            'color' => $color,
            'rows' => $this->mapRows($rows, fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                'icno' => $this->value($row['icno'] ?? null),
                'grade' => $this->value($row['job_grade'] ?? null),
                'department' => $this->value($row['department_shortname'] ?? null),
                'nonCompliance' => (int) ($row['ketidakpatuhan'] ?? 0),
                'approved' => (int) ($row['approved'] ?? 0),
                'disapproved' => (int) ($row['disapproved'] ?? 0),
                'color' => strtoupper($this->value($row['color'] ?? null, '-')),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function administratorWfhStatistics(string $icno, array $filters = []): array
    {
        if (!$this->isAdministrator($icno)) {
            return ['authorized' => false, 'filters' => $filters, 'rows' => [], 'totalStaff' => 0];
        }

        $params = [];
        $where = [];
        $date = trim((string) ($filters['date'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $where[] = 'Tarikh = :date';
            $params[':date'] = $date;
        } elseif (
            trim((string) ($filters['department'] ?? '')) === ''
            && !is_numeric($filters['month'] ?? null)
            && !is_numeric($filters['year'] ?? null)
        ) {
            $where[] = 'Tarikh = :date';
            $params[':date'] = (new DateTimeImmutable('today'))->format('Y-m-d');
        }

        $department = trim((string) ($filters['department'] ?? ''));
        if ($department !== '') {
            $where[] = 'Nama_Penuh_Jabatan LIKE :department';
            $params[':department'] = '%' . $department . '%';
        }
        if (is_numeric($filters['month'] ?? null)) {
            $where[] = 'Bulan = :month';
            $params[':month'] = (int) $filters['month'];
        }
        if (is_numeric($filters['year'] ?? null)) {
            $where[] = 'Tahun = :year';
            $params[':year'] = (int) $filters['year'];
        }

        $rows = $this->db->createCommand(
            sprintf(
                'SELECT Tarikh, Bulan, Tahun, Nama_Jabatan, Nama_Penuh_Jabatan, Bilangan FROM attendance.vw_statistik_wfh WHERE %s ORDER BY Tarikh DESC, Nama_Penuh_Jabatan ASC LIMIT 1000',
                $where === [] ? '1 = 1' : implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        $mapped = $this->mapRows($rows, fn (array $row): array => [
            'date' => $this->formatDate($row['Tarikh'] ?? null),
            'month' => $this->value($row['Bulan'] ?? null),
            'year' => $this->value($row['Tahun'] ?? null),
            'departmentShort' => $this->value($row['Nama_Jabatan'] ?? null),
            'department' => $this->value($row['Nama_Penuh_Jabatan'] ?? null),
            'total' => (int) ($row['Bilangan'] ?? 0),
        ]);

        return [
            'authorized' => true,
            'filters' => $filters,
            'rows' => $mapped,
            'totalStaff' => array_sum(array_map(static fn (array $row): int => (int) $row['total'], $mapped)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function administratorThtcBpsmOverview(string $icno, array $filters = []): array
    {
        if (!$this->isAdministrator($icno)) {
            return ['authorized' => false, 'filters' => $filters, 'rows' => []];
        }

        $params = [':start' => '2025-01-01'];
        $where = ['r.tarikh >= :start', 'thtc.status <> 6'];
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(staff.CONm LIKE :search OR staff.ICNO LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        if (is_numeric($filters['deptId'] ?? null) && (int) $filters['deptId'] > 0) {
            $where[] = 'dept.id = :dept';
            $params[':dept'] = (int) $filters['deptId'];
        }
        if (is_numeric($filters['status'] ?? null) && (int) $filters['status'] > 0) {
            $where[] = 'thtc.status = :status';
            $params[':status'] = (int) $filters['status'];
        }

        $rows = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT thtc.icno, MAX(thtc.id) AS id, COUNT(*) AS total_records,
                       MAX(thtc.status) AS latest_status, MAX(r.tarikh) AS latest_date,
                       staff.CONm AS staff_name, dept.fullname AS department_name, dept.shortname AS department_shortname
                FROM attendance.tbl_thtc thtc
                LEFT JOIN attendance.tbl_rekod r ON r.id = thtc.tbl_rekod_id
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = thtc.icno
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                WHERE %s
                GROUP BY thtc.icno, staff.CONm, dept.fullname, dept.shortname
                ORDER BY latest_date DESC, staff.CONm ASC
                LIMIT 1000
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        return [
            'authorized' => true,
            'filters' => $filters,
            'departments' => $this->supervisorDepartmentOptions(true, []),
            'rows' => $this->mapRows($rows, fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'icno' => $this->value($row['icno'] ?? null),
                'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                'department' => $this->value($row['department_name'] ?? null, $this->value($row['department_shortname'] ?? null)),
                'latestDate' => $this->formatDate($row['latest_date'] ?? null),
                'status' => $this->thtcStatus((int) ($row['latest_status'] ?? 0)),
                'totalRecords' => (int) ($row['total_records'] ?? 0),
                'detailUrl' => self::LEGACY_BASE . '/attendance/list-bpsm?icno=' . rawurlencode((string) ($row['icno'] ?? '')),
            ]),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function administratorWfhApplications(string $icno, array $filters = []): array
    {
        if (!$this->isAdministrator($icno)) {
            return [];
        }

        $params = [];
        $where = ['YEAR(wfh.start_date) > 2021', "wfh.remark NOT LIKE '%ADDED BY PENYELIA%'"];
        $status = strtoupper(trim((string) ($filters['status'] ?? '')));
        if (in_array($status, ['ENTRY', 'APPROVED', 'REJECTED', 'VERIFIED'], true)) {
            $where[] = 'wfh.status = :status';
            $params[':status'] = $status;
        }
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(staff.CONm LIKE :search OR wfh.remark LIKE :search OR wfh.full_date LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $rows = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT wfh.id, wfh.icno, wfh.full_date, wfh.start_date, wfh.end_date, wfh.tempoh,
                       wfh.remark, wfh.entry_dt, wfh.app_dt, wfh.status,
                       staff.CONm AS staff_name, dept.shortname AS department_shortname, job.gred AS job_grade
                FROM attendance.tbl_wfh wfh
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = wfh.icno
                LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
                LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
                WHERE %s
                ORDER BY wfh.entry_dt DESC
                LIMIT 1000
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        return $this->mapRows($rows, fn (array $row): array => [
            'id' => (string) ($row['id'] ?? ''),
            'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
            'icno' => $this->value($row['icno'] ?? null),
            'grade' => $this->value($row['job_grade'] ?? null),
            'department' => $this->value($row['department_shortname'] ?? null),
            'date' => $this->value($row['full_date'] ?? null, $this->dateRangeText($row['start_date'] ?? null, $row['end_date'] ?? null)),
            'period' => $this->value($row['tempoh'] ?? null),
            'remark' => $this->value($row['remark'] ?? null),
            'entryDate' => $this->value($row['entry_dt'] ?? null),
            'approvalDate' => $this->value($row['app_dt'] ?? null),
            'status' => $this->value($row['status'] ?? null),
            'deleteUrl' => '/kehadiran/delete-wfh?id=' . rawurlencode((string) ($row['id'] ?? '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function administratorAdminPostList(string $icno, array $filters = []): array
    {
        if (!$this->isAdministrator($icno)) {
            return ['authorized' => false, 'filters' => $filters, 'rows' => []];
        }

        $params = [];
        $where = ['post.flag = 1', 'staff.Status = 1', 'position.position_type = 1'];
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(staff.CONm LIKE :search OR position.position_name LIKE :search OR post.description LIKE :search OR dept.shortname LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $rows = $this->db->createCommand(
            sprintf(
                <<<SQL
                SELECT post.id, post.ICNO, post.description, post.start_date, post.end_date,
                       staff.CONm AS staff_name, dept.shortname AS department_shortname,
                       position.position_name
                FROM hronline.tblrscoadminpost post
                INNER JOIN hronline.adminposition position ON position.id = post.adminpos_id
                LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = post.ICNO
                LEFT JOIN hronline.department dept ON dept.id = post.dept_id
                WHERE %s
                ORDER BY staff.CONm ASC
                LIMIT 1000
                SQL,
                implode(' AND ', $where),
            ),
            $params,
        )->queryAll();

        return [
            'authorized' => true,
            'filters' => $filters,
            'rows' => $this->mapRows($rows, fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
                'icno' => $this->value($row['ICNO'] ?? null),
                'position' => $this->value($row['position_name'] ?? null),
                'description' => $this->value($row['description'] ?? null),
                'department' => $this->value($row['department_shortname'] ?? null),
                'startDate' => $this->formatDate($row['start_date'] ?? null),
                'endDate' => $this->formatDate($row['end_date'] ?? null),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function attendanceReasonApproval(string $icno, int $id): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT r.id, r.icno, r.tarikh, r.day, r.time_in, r.time_out, r.remark,
                   r.remark_status, r.app_remark, r.late_in, r.early_out, r.incomplete,
                   r.absent, r.external, r.external_out, staff.CONm AS staff_name,
                   dept.shortname AS department_shortname, ref.jenis_wp
            FROM attendance.tbl_rekod r
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = r.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN attendance.ref_wp ref ON ref.id = r.wp_id
            WHERE r.id = :id
              AND r.app_by = :icno
              AND r.remark_status = 'ENTRY'
            LIMIT 1
            SQL,
            [':id' => $id, ':icno' => $this->approvalIdentity($icno)],
        )->queryOne();

        return is_array($row) ? $this->mapAttendanceReasonApproval($row) : null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string}
     */
    public function saveAttendanceReasonApproval(string $icno, int $id, array $input): array
    {
        $approvalIcno = $this->approvalIdentity($icno);
        $record = $this->attendanceReasonApproval($icno, $id);
        if ($record === null) {
            return ['success' => false, 'message' => 'Rekod pengesahan tidak dijumpai atau sudah selesai.'];
        }

        $status = strtoupper(trim((string) ($input['status'] ?? '')));
        if (!in_array($status, ['APPROVED', 'REJECTED'], true)) {
            return ['success' => false, 'message' => 'Status pengesahan diperlukan.'];
        }

        $now = date('Y-m-d H:i:s');
        $remark = trim((string) ($input['remark'] ?? ''));
        $updated = (int) $this->db->transaction(fn (): int => $this->db->createCommand()->update('attendance.tbl_rekod', [
            'remark_status' => $status,
            'app_remark' => $remark,
            'app_dt' => $now,
        ], [
            'id' => $id,
            'app_by' => $approvalIcno,
            'remark_status' => 'ENTRY',
        ])->execute());

        if ($updated < 1) {
            return ['success' => false, 'message' => 'Rekod tidak dikemaskini.'];
        }

        $this->notify(
            (string) $record['icno'],
            'Kehadiran',
            'Status pengesahan Ketidakpatuhan pada ' . (string) $record['date'] . ' telah disahkan.',
            $now,
        );
        $this->refreshPendingTask($approvalIcno, null);

        return ['success' => true, 'message' => 'Pengesahan ketidakpatuhan telah dihantar.'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function wfhApproval(string $icno, int $id): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT wfh.id, wfh.icno, wfh.full_date, wfh.start_date, wfh.end_date, wfh.tempoh,
                   wfh.remark, wfh.entry_dt, wfh.status, wfh.app_remark, wfh.doc_name, wfh.hashcode,
                   staff.CONm AS staff_name, dept.shortname AS department_shortname, job.gred AS job_grade
            FROM attendance.tbl_wfh wfh
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = wfh.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
            WHERE wfh.id = :id
              AND wfh.app_by = :icno
              AND wfh.status = 'ENTRY'
            LIMIT 1
            SQL,
            [':id' => $id, ':icno' => $icno],
        )->queryOne();

        return is_array($row) ? $this->mapWfhActionRow($row) : null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string}
     */
    public function saveWfhApproval(string $icno, int $id, array $input): array
    {
        $record = $this->wfhApproval($icno, $id);
        if ($record === null) {
            return ['success' => false, 'message' => 'Permohonan WFH tidak dijumpai atau sudah selesai.'];
        }

        $status = strtoupper(trim((string) ($input['status'] ?? '')));
        if (!in_array($status, ['APPROVED', 'REJECTED'], true)) {
            return ['success' => false, 'message' => 'Status kelulusan diperlukan.'];
        }

        $now = date('Y-m-d H:i:s');
        $remark = trim((string) ($input['remark'] ?? ''));
        $updated = (int) $this->db->transaction(fn (): int => $this->db->createCommand()->update('attendance.tbl_wfh', [
            'status' => $status,
            'app_remark' => $remark,
            'app_dt' => $now,
        ], [
            'id' => $id,
            'app_by' => $icno,
            'status' => 'ENTRY',
        ])->execute());

        if ($updated < 1) {
            return ['success' => false, 'message' => 'Permohonan WFH tidak dikemaskini.'];
        }

        $message = $status === 'APPROVED'
            ? 'Permohonan bekerja dari rumah / Work from Home (WFH) pada ' . (string) $record['date'] . ' telah diluluskan.'
            : 'Permohonan bekerja dari rumah / Work from Home (WFH) pada ' . (string) $record['date'] . ' ditolak.';
        $this->notify((string) $record['icno'], 'WFH', $message, $now);

        return ['success' => true, 'message' => 'Kelulusan permohonan WFH telah dihantar.'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function wfhDeleteRecord(string $icno, int $id): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT wfh.id, wfh.icno, wfh.full_date, wfh.start_date, wfh.end_date, wfh.tempoh,
                   wfh.remark, wfh.entry_dt, wfh.status, wfh.app_by, wfh.ver_by, wfh.set_by,
                   staff.CONm AS staff_name, dept.shortname AS department_shortname, job.gred AS job_grade
            FROM attendance.tbl_wfh wfh
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = wfh.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            LEFT JOIN hronline.gredjawatan job ON job.id = staff.gredJawatan
            WHERE wfh.id = :id
            LIMIT 1
            SQL,
            [':id' => $id],
        )->queryOne();

        if (!is_array($row) || !$this->canManageWfh($icno, $row)) {
            return null;
        }

        return $this->mapWfhActionRow($row);
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function deleteWfh(string $icno, int $id): array
    {
        $record = $this->wfhDeleteRecord($icno, $id);
        if ($record === null) {
            return ['success' => false, 'message' => 'Permohonan WFH tidak dijumpai atau tiada akses.'];
        }

        $deleted = (int) $this->db->transaction(fn (): int => $this->db->createCommand()->delete('attendance.tbl_wfh', ['id' => $id])->execute());
        if ($deleted < 1) {
            return ['success' => false, 'message' => 'Permohonan WFH tidak dipadam.'];
        }

        return ['success' => true, 'message' => 'Permohonan WFH telah dipadam.'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function thtcReview(string $icno, int $id, string $stage): ?array
    {
        $stage = $stage === 'approve' ? 'approve' : 'verify';
        $expectedStatus = $stage === 'approve' ? 2 : 1;
        $row = $this->db->createCommand(
            <<<SQL
            SELECT thtc.id, thtc.icno, thtc.remark, thtc.date_entered, thtc.supporting_doc,
                   thtc.status, thtc.icno_verifier, thtc.remark_verifier, thtc.dt_entered_verifier,
                   thtc.icno_approver, thtc.remark_approver, thtc.dt_entered_approver,
                   r.tarikh, r.day, r.remark AS absent_remark,
                   staff.CONm AS staff_name, dept.shortname AS department_shortname
            FROM attendance.tbl_thtc thtc
            LEFT JOIN attendance.tbl_rekod r ON r.id = thtc.tbl_rekod_id
            LEFT JOIN hronline.tblprcobiodata staff ON staff.ICNO = thtc.icno
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            WHERE thtc.id = :id
              AND thtc.status = :status
            LIMIT 1
            SQL,
            [':id' => $id, ':status' => $expectedStatus],
        )->queryOne();

        if (!is_array($row) || !$this->canReviewThtc($icno, $row, $stage)) {
            return null;
        }

        return $this->mapThtcActionRow($row, $stage);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string}
     */
    public function saveThtcReview(string $icno, int $id, string $stage, array $input): array
    {
        $stage = $stage === 'approve' ? 'approve' : 'verify';
        $record = $this->thtcReview($icno, $id, $stage);
        if ($record === null) {
            return ['success' => false, 'message' => 'Rekod THTC tidak dijumpai atau sudah selesai.'];
        }

        $status = (int) ($input['status'] ?? 0);
        $allowed = $stage === 'approve' ? [3, 5] : [2, 4];
        if (!in_array($status, $allowed, true)) {
            return ['success' => false, 'message' => 'Status THTC diperlukan.'];
        }

        $remark = trim((string) ($input['remark'] ?? ''));
        if ($remark === '') {
            return ['success' => false, 'message' => 'Catatan diperlukan.'];
        }

        $now = date('Y-m-d H:i:s');
        $fields = $stage === 'approve'
            ? ['status' => $status, 'remark_approver' => $remark, 'icno_approver' => $icno, 'dt_entered_approver' => $now]
            : ['status' => $status, 'remark_verifier' => $remark, 'dt_entered_verifier' => $now];
        $updated = (int) $this->db->transaction(fn (): int => $this->db->createCommand()->update('attendance.tbl_thtc', $fields, [
            'id' => $id,
            'status' => $stage === 'approve' ? 2 : 1,
        ])->execute());

        if ($updated < 1) {
            return ['success' => false, 'message' => 'Rekod THTC tidak dikemaskini.'];
        }

        $this->notify((string) $record['icno'], 'Kehadiran', 'Status THTC pada ' . (string) $record['date'] . ' dikemaskini kepada ' . $this->thtcStatus($status) . '.', $now);

        return ['success' => true, 'message' => 'Rekod THTC telah dikemaskini.'];
    }

    /**
     * @return array<string, int>
     */
    public function pendingCounts(string $icno): array
    {
        $approvalIcno = $this->approvalIdentity($icno);

        $pendingReason = (int) $this->db->createCommand(
            <<<SQL
            SELECT COUNT(*)
            FROM attendance.tbl_rekod
            WHERE icno = :icno
              AND remark_status IS NULL
              AND (late_in = 1 OR early_out = 1 OR incomplete = 1 OR absent = 1 OR external = 1 OR external_out = 1)
            SQL,
            [':icno' => $icno],
        )->queryScalar();

        $pendingApproval = (int) $this->db->createCommand(
            <<<SQL
            SELECT COUNT(*)
            FROM attendance.tbl_rekod
            WHERE app_by = :icno
              AND remark_status = 'ENTRY'
            SQL,
            [':icno' => $approvalIcno],
        )->queryScalar();

        $pendingWbbVerify = (int) $this->db->createCommand(
            "SELECT COUNT(*) FROM attendance.tbl_wp WHERE ver_by = :icno AND status = 'ENTRY'",
            [':icno' => $icno],
        )->queryScalar();

        $pendingWbbApproval = (int) $this->db->createCommand(
            "SELECT COUNT(*) FROM attendance.tbl_wp WHERE app_by = :icno AND status = 'VERIFIED'",
            [':icno' => $icno],
        )->queryScalar();

        return [
            'reason' => $pendingReason,
            'approval' => $pendingApproval,
            'wbb' => $pendingWbbVerify + $pendingWbbApproval,
            'all' => $pendingReason + $pendingApproval + $pendingWbbVerify + $pendingWbbApproval,
        ];
    }

    public function workMode(string $icno, string $date): string
    {
        $approvedWfh = (bool) $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM attendance.tbl_wfh
            WHERE icno = :icno
              AND :date BETWEEN start_date AND end_date
              AND status = 'APPROVED'
            LIMIT 1
            SQL,
            [':icno' => $icno, ':date' => $date],
        )->queryScalar();

        return $approvedWfh ? 'Work From Home' : 'Work At Office';
    }

    /**
     * @return array<string, bool>
     */
    public function roleFlags(string $icno): array
    {
        $admin = $this->hasAccess($icno, 3);
        $supervisor = $this->hasAccess($icno, 2);
        $departmentChief = (bool) $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM hronline.department
            WHERE chief = :icno
              AND isActive = 1
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryScalar();

        return [
            'staff' => true,
            'officer' => true,
            'departmentChief' => $departmentChief,
            'attendanceSupervisor' => $supervisor || $admin,
            'administrator' => $admin,
            'reports' => $admin,
        ];
    }

    /**
     * @param array<string, bool> $roleFlags
     * @param array<string, int> $pending
     * @return list<array<string, mixed>>
     */
    private function menuSections(array $roleFlags, array $pending): array
    {
        $sections = [
            [
                'key' => 'staff',
                'label' => 'Staff',
                'icon' => 'user',
                'visible' => $roleFlags['staff'],
                'badge' => $pending['reason'],
                'items' => [
                    $this->localItem('Attendance report', '/kehadiran/laporan-kehadiran', 'list'),
                    $this->localItem('Pending for reason', '/kehadiran/tindakan_ketidakpatuhan', 'alert-triangle', $pending['reason']),
                    $this->localItem('Card Color report', '/kehadiran/staf-color-list', 'book-open'),
                    $this->localItem('Card Colour info', '/kehadiran/kad-warna', 'info'),
                    ['label' => 'Outstation', 'url' => 'https://pilot-iics.ums.edu.my/app/travel-request', 'icon' => 'car', 'external' => true, 'legacy' => false, 'badge' => 0],
                    ['label' => 'Guideline', 'url' => '/files/garis_panduan_kehadiran.pdf', 'icon' => 'book-open', 'external' => true, 'legacy' => false, 'badge' => 0],
                    $this->localItem('Senarai WFH / WFO', '/kehadiran/wfh-list', 'list'),
                    $this->localItem('Tidak Hadir Tanpa Cuti (THTC)', '/kehadiran/absent-list', 'list'),
                ],
            ],
            [
                'key' => 'officer',
                'label' => 'Officer',
                'icon' => 'users',
                'visible' => $roleFlags['officer'],
                'badge' => $pending['approval'],
                'items' => [
                    $this->localItem('Approval (Reason)', '/kehadiran/senarai_tindakan', 'alert-triangle', $pending['approval']),
                    $this->localItem('Card Colour Report', '/kehadiran/staff-color-card', 'file'),
                    $this->localItem('Attendance report', '/kehadiran/pantau-kehadiran', 'list'),
                    $this->localItem('Monthly report', '/kehadiran/monthly-summary', 'area-chart'),
                    $this->localItem('Senarai Tindakan WFH', '/kehadiran/wfh-lulus', 'users'),
                    $this->localItem('Senarai Permohonan WFH', '/kehadiran/pantau-permohonan-wfh-pegawai', 'list'),
                    $this->legacyItem('Staff Seliaan', 'cuti/pegawai/staffonleave', 'list'),
                    $this->localItem('Tidak Hadir Tanpa Cuti (THTC)', '/kehadiran/absent-list-kp', 'list'),
                ],
            ],
            [
                'key' => 'departmentChief',
                'label' => 'Head of Department',
                'icon' => 'id-card',
                'visible' => $roleFlags['departmentChief'],
                'badge' => 0,
                'items' => [
                    $this->localItem('Staff attendance', '/kehadiran/kj-pantau-kehadiran', 'list'),
                    $this->localItem('Jadual WFH', '/kehadiran/wfh-jadual-kj', 'calendar-check'),
                    $this->localItem('Senarai Tindakan WFH', '/kehadiran/wfh-lulus', 'users'),
                    $this->localItem('Senarai Permohonan WFH', '/kehadiran/pantau-permohonan-wfh-pegawai', 'list'),
                    $this->localItem('Tidak Hadir Tanpa Cuti (THTC)', '/kehadiran/list-kj', 'list'),
                ],
            ],
            [
                'key' => 'attendanceSupervisor',
                'label' => 'Attendance Supervisor',
                'icon' => 'clipboard-pen',
                'visible' => $roleFlags['attendanceSupervisor'],
                'badge' => 0,
                'items' => [
                    $this->localItem('Staff lists', '/kehadiran/senarai_kakitangan', 'address-book'),
                    $this->localItem('Staff Working hours', '/kehadiran/staff-wbb', 'bar-chart'),
                    $this->localItem('Monthly Reports', '/kehadiran/sup-mth-rpt', 'flag'),
                    $this->localItem('Yearly Reports', '/kehadiran/sup-year-rpt', 'globe'),
                    $this->localItem('Card Colour Report', '/kehadiran/senarai-warna-kad', 'file'),
                    $this->localItem('Card Colour Performance', '/kehadiran/sup-year-performance-rpt', 'activity'),
                    $this->localItem('Pantau Staf', '/kehadiran/pantau-staf', 'list'),
                    $this->localItem('Shields Status', '/kehadiran/shield-status-list', 'shield'),
                    $this->localItem('Staff Shift List', '/kehadiran/staff-shift-list', 'users'),
                    $this->localItem('Shift Setup', '/kehadiran/shift-setup', 'wrench'),
                    $this->localItem('Manage Supervisor/Staff', '/kehadiran-admin/manage-shift-supervisor', 'wrench'),
                    $this->localItem('Senarai Bulanan WFH', '/kehadiran/wfh-by-mth', 'list'),
                    $this->localItem('Jadual WFH', '/kehadiran/wfh-jadual', 'calendar-check'),
                    $this->localItem('Pantau Permohonan WFH', '/kehadiran/pantau-permohonan-wfh', 'users'),
                    $this->localItem('Tidak Hadir Tanpa Cuti (THTC)', '/kehadiran/absent-list-sv', 'list'),
                ],
            ],
            [
                'key' => 'administrator',
                'label' => 'Administrator',
                'icon' => 'lock',
                'visible' => $roleFlags['administrator'],
                'badge' => 0,
                'items' => [
                    $this->localItem('Staff lists', '/kehadiran/staff-all', 'address-book'),
                    $this->localItem('Statistic (JFPIU)', '/kehadiran/admin-mth-rpt', 'bar-chart'),
                    $this->localItem('Warning Letters', '/kehadiran/color-rpt', 'list'),
                    $this->localItem('Raw Data', '/attendance/index', 'file'),
                    $this->legacyItem('Senarai Keluar Pejabat', 'kehadiran/senarai_keluar_pejabat', 'log-out'),
                    $this->localItem('Admin Post (Akad)', '/kehadiran/admin-post-list', 'briefcase'),
                    $this->localItem('Pantau Permohonan JAFPIB', '/kehadiran/pantau-permohonan-wfh-admin', 'home'),
                    $this->localItem('Jadual JAFPIB', '/kehadiran/wfh-jadual-admin', 'calendar'),
                    $this->localItem('Statistik WFH', '/kehadiran/statistik-wfh', 'bar-chart'),
                    $this->legacyItem('Vaccination Watchlist', 'vaksinasi/watch-list', 'syringe'),
                    $this->localItem('Tidak Hadir Tanpa Cuti (THTC)', '/attendance/absent-list-bpsm', 'list'),
                ],
            ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'icon' => 'file-text',
                'visible' => $roleFlags['reports'],
                'badge' => 0,
                'items' => [
                    $this->legacyItem('By Month (Range)', 'kehadiran/range-month', 'calendar-range'),
                    $this->localItem('Monthly Reports', '/kehadiran/sup-mth-rpt', 'flag'),
                    $this->localItem('Yearly Reports', '/kehadiran/sup-year-rpt', 'globe'),
                ],
            ],
        ];

        return array_values(array_filter($sections, static fn (array $section): bool => (bool) $section['visible']));
    }

    /**
     * @return array{label: string, url: string, icon: string, external: bool, legacy: bool, badge: int}
     */
    private function legacyItem(string $label, string $route, string $icon, int $badge = 0): array
    {
        return [
            'label' => $label,
            'url' => self::LEGACY_BASE . '/' . ltrim($route, '/'),
            'icon' => $icon,
            'external' => true,
            'legacy' => true,
            'badge' => $badge,
        ];
    }

    /**
     * @return array{label: string, url: string, icon: string, external: bool, legacy: bool, badge: int}
     */
    private function localItem(string $label, string $url, string $icon, int $badge = 0): array
    {
        return [
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
            'external' => false,
            'legacy' => false,
            'badge' => $badge,
        ];
    }

    private function shiftWorkingPatternId(string $icno, string $today): ?string
    {
        $hasShiftStaff = (bool) $this->db->createCommand(
            'SELECT 1 FROM attendance.tbl_staffs WHERE staff_icno = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryScalar();

        if (!$hasShiftStaff) {
            return null;
        }

        $wpId = $this->db->createCommand(
            'SELECT wp_id FROM attendance.tbl_shifts WHERE icno = :icno AND tarikh = :today LIMIT 1',
            [':icno' => $icno, ':today' => $today],
        )->queryScalar();

        return is_scalar($wpId) && (string) $wpId !== '' ? (string) $wpId : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function workingPatternById(string $wpId): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT id AS wp_id, jenis_wp, detail, start_time, end_time, next_day,
                   is_flexi, in_start_time, in_end_time, out_start_time, out_end_time,
                   total_hours
            FROM attendance.ref_wp
            WHERE id = :wpId
            LIMIT 1
            SQL,
            [':wpId' => $wpId],
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    private function ramadhanWorkingPatternId(string $icno, string $wpId, string $today): ?string
    {
        if ($wpId !== '40' || $today < '2026-02-19' || $today > '2026-03-19') {
            return null;
        }

        $religion = $this->db->createCommand(
            'SELECT ReligionCd FROM hronline.tblprcobiodata WHERE ICNO = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryScalar();

        return (string) $religion === '01' ? '49' : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function recordForDate(string $icno, string $date, bool $onlyOpen): ?array
    {
        $sql = 'SELECT * FROM attendance.tbl_rekod WHERE icno = :icno AND tarikh = :date';
        if ($onlyOpen) {
            $sql .= ' AND time_out IS NULL';
        }

        $sql .= ' LIMIT 1';
        $row = $this->db->createCommand($sql, [':icno' => $icno, ':date' => $date])->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed>|null $record
     * @param array<string, mixed>|null $workingPattern
     */
    private function hourComplete(?array $record, ?array $workingPattern): float
    {
        if ($record === null || $workingPattern === null || (float) ($workingPattern['totalHours'] ?? 0) <= 0) {
            return 0.0;
        }

        $hours = 0.0;
        if (($record['timeOutRaw'] ?? '') !== '' && is_numeric($record['totalHours'] ?? null)) {
            $hours = (float) $record['totalHours'];
        } elseif (($record['timeInRaw'] ?? '') !== '') {
            $start = $this->timeOnly((string) $record['timeInRaw']);
            $end = (new DateTimeImmutable())->format('H:i:s');
            $wpStart = (bool) ($workingPattern['isFlexi'] ?? false)
                ? (string) ($workingPattern['inStartTime'] ?? '')
                : (string) ($workingPattern['startTime'] ?? '');
            if ($wpStart !== '' && $start < $wpStart) {
                $start = $wpStart;
            }

            if ($start !== '') {
                $hours = abs(strtotime($end) - strtotime($start)) / 3600;
            }
        }

        return min(100.0, round(($hours / (float) $workingPattern['totalHours']) * 100, 2));
    }

    /**
     * @param array<string, mixed> $record
     * @return list<string>
     */
    private function statusLabels(array $record): array
    {
        $labels = [];
        foreach ([
            'late_in' => 'LATE IN',
            'early_out' => 'EARLY OUT',
            'absent' => 'ABSENT',
            'incomplete' => 'INCOMPLETE',
            'external' => 'EXTERNAL IN',
            'external_out' => 'EXTERNAL OUT',
        ] as $field => $label) {
            if ((int) ($record[$field] ?? 0) === 1) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    private function cardColorMonth(DateTimeImmutable $today): DateTimeImmutable
    {
        if ((int) $today->format('d') <= 9) {
            return $today->modify('first day of previous month');
        }

        return $today;
    }

    private function approvalIdentity(string $icno): string
    {
        $delegate = $this->db->createCommand(
            'SELECT icno_pemberi_kuasa FROM hrm.cuti_behalf_action WHERE icno_tindakan = :icno LIMIT 1',
            [':icno' => $icno],
        )->queryScalar();

        return is_scalar($delegate) && (string) $delegate !== '' ? (string) $delegate : $icno;
    }

    private function hasAccess(string $icno, int $access): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM hrm.cuti_access WHERE akses_cuti_icno = :icno AND akses_cuti_int = :access LIMIT 1',
            [':icno' => $icno, ':access' => $access],
        )->queryScalar();
    }

    /**
     * @param list<array<string, mixed>> $staff
     * @return list<string>
     */
    private function staffIcnoList(array $staff): array
    {
        $icnos = [];
        foreach ($staff as $person) {
            $icno = (string) ($person['icno'] ?? '');
            if ($icno !== '') {
                $icnos[] = $icno;
            }
        }

        return $icnos;
    }

    /**
     * @param list<int> $departmentIds
     * @return list<array<string, mixed>>
     */
    private function supervisorDepartmentOptions(bool $admin, array $departmentIds): array
    {
        $params = [];
        $where = 'isActive = 1';
        if (!$admin) {
            if ($departmentIds === []) {
                return [];
            }

            [$inSql, $params] = $this->inConditionParams('dept', $departmentIds);
            $where .= " AND id IN ($inSql)";
        }

        $rows = $this->db->createCommand(
            "SELECT id, fullname, shortname FROM hronline.department WHERE $where ORDER BY fullname ASC",
            $params,
        )->queryAll();

        return $this->mapRows($rows, fn (array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'name' => $this->value($row['fullname'] ?? null),
            'shortName' => $this->value($row['shortname'] ?? null),
        ]);
    }

    /**
     * @param array<string, mixed> $scope
     * @param array<string, mixed> $filters
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function supervisorStaffWhere(array $scope, array $filters): array
    {
        if (!(bool) ($scope['authorized'] ?? false)) {
            return [[], []];
        }

        $admin = (bool) ($scope['admin'] ?? false);
        $departmentIds = array_map('intval', $scope['departmentIds'] ?? []);
        $campusIds = array_map('intval', $scope['campusIds'] ?? []);
        $deptId = is_numeric($filters['deptId'] ?? null) ? (int) $filters['deptId'] : null;
        $where = ['staff.Status = 1'];
        $params = [];

        if ($deptId !== null && $deptId > 0) {
            if (!$admin && !in_array($deptId, $departmentIds, true)) {
                return [['0 = 1'], []];
            }
            $where[] = 'staff.DeptId = :deptId';
            $params[':deptId'] = $deptId;
        } elseif (!$admin) {
            if ($departmentIds === []) {
                return [[], []];
            }
            [$deptSql, $deptParams] = $this->inConditionParams('dept', $departmentIds);
            $where[] = "staff.DeptId IN ($deptSql)";
            $params += $deptParams;
        }

        if (!$admin && $campusIds !== []) {
            [$campusSql, $campusParams] = $this->inConditionParams('campus', $campusIds);
            $where[] = "staff.campus_id IN ($campusSql)";
            $params += $campusParams;
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(staff.CONm LIKE :search OR staff.COOldID LIKE :search OR staff.ICNO LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $grade = trim((string) ($filters['grade'] ?? ''));
        if ($grade !== '') {
            $where[] = '(job.gred LIKE :grade OR job.fname LIKE :grade)';
            $params[':grade'] = '%' . $grade . '%';
        }

        return [$where, $params];
    }

    /**
     * @param array<string, mixed> $scope
     * @param array<string, mixed> $filters
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function supervisorDepartmentWhere(array $scope, array $filters, string $column): array
    {
        if (!(bool) ($scope['authorized'] ?? false)) {
            return [['0 = 1'], []];
        }

        $admin = (bool) ($scope['admin'] ?? false);
        $departmentIds = array_map('intval', $scope['departmentIds'] ?? []);
        $deptId = is_numeric($filters['deptId'] ?? null) ? (int) $filters['deptId'] : null;

        if ($deptId !== null && $deptId > 0) {
            if (!$admin && !in_array($deptId, $departmentIds, true)) {
                return [['0 = 1'], []];
            }

            return [["$column = :deptId"], [':deptId' => $deptId]];
        }

        if ($admin) {
            return [['1 = 1'], []];
        }

        if ($departmentIds === []) {
            return [['0 = 1'], []];
        }

        [$deptSql, $params] = $this->inConditionParams('dept', $departmentIds);

        return [["$column IN ($deptSql)"], $params];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapStaffRow(array $row): array
    {
        return [
            'icno' => $this->value($row['ICNO'] ?? null, ''),
            'name' => $this->employees->displayName($row['CONm'] ?? null),
            'staffNo' => $this->value($row['COOldID'] ?? null),
            'position' => $this->value($row['position_name'] ?? null),
            'grade' => $this->value($row['job_grade'] ?? null, $this->value($row['position_name'] ?? null)),
            'department' => $this->value($row['department_name'] ?? null),
            'departmentShort' => $this->value($row['department_shortname'] ?? null),
            'campus' => $this->value($row['campus_name'] ?? null),
            'deptId' => (int) ($row['DeptId'] ?? 0),
            'campusId' => (int) ($row['campus_id'] ?? 0),
            'reportUrl' => self::LEGACY_BASE . '/kehadiran/laporan_kehadiran?id=' . rawurlencode((string) ($row['ICNO'] ?? '')),
        ];
    }

    /**
     * @param list<string> $icnos
     * @return array<string, array{peraku: string, pelulus: string}>
     */
    private function setPegawaiForStaff(array $icnos): array
    {
        if ($icnos === []) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT pegawai.pemohon_icno, peraku.CONm AS peraku_name, pelulus.CONm AS pelulus_name
            FROM hrm.cuti_set_pegawai pegawai
            LEFT JOIN hronline.tblprcobiodata peraku ON peraku.ICNO = pegawai.peraku_icno
            LEFT JOIN hronline.tblprcobiodata pelulus ON pelulus.ICNO = pegawai.pelulus_icno
            WHERE pegawai.pemohon_icno IN ($inSql)
            SQL,
            $params,
        )->queryAll();

        $officers = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $officers[(string) ($row['pemohon_icno'] ?? '')] = [
                'peraku' => $this->employees->displayName($row['peraku_name'] ?? null),
                'pelulus' => $this->employees->displayName($row['pelulus_name'] ?? null),
            ];
        }

        return $officers;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapWbbAssignmentRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'wpId' => (int) ($row['wp_id'] ?? 0),
            'label' => $this->value($row['jenis_wp'] ?? null, '--Tiada WBB--'),
            'detail' => $this->value($row['detail'] ?? null, ''),
            'startDate' => $this->formatDate($row['start_date'] ?? null),
            'endDate' => $this->formatDate($row['end_date'] ?? null),
            'status' => $this->wbbStatusLabel($row['status'] ?? null),
            'remark' => $this->value($row['remark'] ?? null),
        ];
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function workingPatternOptions(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, jenis_wp, detail
            FROM attendance.ref_wp
            WHERE status = 1
            ORDER BY jenis_wp ASC, id ASC
            SQL,
        )->queryAll();

        return $this->mapRows($rows, function (array $row): array {
            $jenis = $this->value($row['jenis_wp'] ?? null, '');
            $detail = $this->value($row['detail'] ?? null, '');
            $label = trim($jenis . ($detail === '' ? '' : ' - ' . $detail));

            return [
                'id' => (int) ($row['id'] ?? 0),
                'label' => $label === '' ? 'WP #' . (string) ($row['id'] ?? '') : $label,
            ];
        });
    }

    private function workingPatternExists(int $id): bool
    {
        $row = $this->db->createCommand(
            'SELECT id FROM attendance.ref_wp WHERE id = :id AND status = 1 LIMIT 1',
            [':id' => $id],
        )->queryOne();

        return is_array($row);
    }

    /**
     * @return list<array{icno: string, label: string}>
     */
    private function activeStaffOptions(): array
    {
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT staff.ICNO, staff.CONm, staff.COOldID, dept.shortname AS department_shortname
            FROM hronline.tblprcobiodata staff
            LEFT JOIN hronline.department dept ON dept.id = staff.DeptId
            WHERE staff.status = 1
            ORDER BY staff.CONm ASC
            LIMIT 5000
            SQL,
        )->queryAll();

        return $this->mapRows($rows, function (array $row): array {
            $name = $this->employees->displayName($row['CONm'] ?? null);
            $staffNo = $this->value($row['COOldID'] ?? null, '');
            $department = $this->value($row['department_shortname'] ?? null, '');
            $meta = array_values(array_filter([$staffNo, $department], static fn (string $value): bool => $value !== ''));

            return [
                'icno' => $this->value($row['ICNO'] ?? null, ''),
                'label' => $name . ($meta === [] ? '' : ' (' . implode(' / ', $meta) . ')'),
            ];
        });
    }

    private function activeStaffExists(string $icno): bool
    {
        $row = $this->db->createCommand(
            'SELECT ICNO FROM hronline.tblprcobiodata WHERE ICNO = :icno AND status = 1 LIMIT 1',
            [':icno' => $icno],
        )->queryOne();

        return is_array($row);
    }

    /**
     * @param list<string> $icnos
     * @return array<string, string>
     */
    private function workingPatternLabelsFor(array $icnos): array
    {
        if ($icnos === []) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $params[':today'] = $today;

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT wp.icno, ref.jenis_wp
            FROM attendance.tbl_wp wp
            INNER JOIN (
                SELECT icno, MAX(id) AS id
                FROM attendance.tbl_wp
                WHERE status = 'APPROVED'
                  AND start_date <= :today
                  AND (end_date >= :today OR end_date IS NULL)
                  AND icno IN ($inSql)
                GROUP BY icno
            ) latest ON latest.id = wp.id
            LEFT JOIN attendance.ref_wp ref ON ref.id = wp.wp_id
            SQL,
            $params,
        )->queryAll();

        $labels = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $labels[(string) ($row['icno'] ?? '')] = $this->value($row['jenis_wp'] ?? null, '--Tiada WBB--');
            }
        }

        return $labels;
    }

    /**
     * @param list<string> $icnos
     * @return array<string, array<string, int>>
     */
    private function yearlyComplianceSummaries(array $icnos, int $year): array
    {
        if ($icnos === []) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $params[':year'] = $year;
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT icno,
                   COALESCE(SUM(CASE WHEN absent = 0 THEN 1 ELSE 0 END), 0) AS working_days,
                   COALESCE(SUM(CASE WHEN remark_status = 'REJECTED' AND late_in = 1 THEN 1 ELSE 0 END), 0) AS late_in_total,
                   COALESCE(SUM(CASE WHEN remark_status = 'REJECTED' AND early_out = 1 THEN 1 ELSE 0 END), 0) AS early_out_total,
                   COALESCE(SUM(CASE WHEN remark_status = 'REJECTED' AND incomplete = 1 THEN 1 ELSE 0 END), 0) AS incomplete_total,
                   COALESCE(SUM(CASE WHEN remark_status = 'REJECTED' AND absent = 1 THEN 1 ELSE 0 END), 0) AS absent_total,
                   COALESCE(SUM(CASE WHEN remark_status = 'REJECTED' AND (external = 1 OR external_out = 1) THEN 1 ELSE 0 END), 0) AS external_total
            FROM attendance.tbl_rekod
            WHERE YEAR(tarikh) = :year
              AND icno IN ($inSql)
            GROUP BY icno
            SQL,
            $params,
        )->queryAll();

        $summaries = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $summaries[(string) ($row['icno'] ?? '')] = [
                'workingDays' => (int) ($row['working_days'] ?? 0),
                'lateIn' => (int) ($row['late_in_total'] ?? 0),
                'earlyOut' => (int) ($row['early_out_total'] ?? 0),
                'incomplete' => (int) ($row['incomplete_total'] ?? 0),
                'absent' => (int) ($row['absent_total'] ?? 0),
                'external' => (int) ($row['external_total'] ?? 0),
            ];
        }

        return $summaries;
    }

    /**
     * @param list<string> $icnos
     * @return array<string, array<string, mixed>>
     */
    private function cardColorRowsFor(array $icnos, int $month, int $year): array
    {
        if ($icnos === []) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $params[':month'] = $month;
        $params[':year'] = $year;
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT icno, color, ketidakpatuhan, approved, disapproved
            FROM attendance.tbl_warnakad
            WHERE icno IN ($inSql)
              AND MONTH(month_date) = :month
              AND YEAR(month_date) = :year
            ORDER BY month_date DESC, id DESC
            SQL,
            $params,
        )->queryAll();

        $cards = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $cards[(string) ($row['icno'] ?? '')] = [
                    'color' => strtoupper($this->value($row['color'] ?? null, '-')),
                    'nonCompliance' => (int) ($row['ketidakpatuhan'] ?? 0),
                    'approved' => (int) ($row['approved'] ?? 0),
                    'disapproved' => (int) ($row['disapproved'] ?? 0),
                ];
            }
        }

        return $cards;
    }

    /**
     * @param list<string> $icnos
     * @return array<string, array<string, int>>
     */
    private function cardColorYearTotalsFor(array $icnos, int $year): array
    {
        if ($icnos === []) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $params[':year'] = $year;
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT icno, UPPER(color) AS card_color, COUNT(*) AS total
            FROM attendance.tbl_warnakad
            WHERE icno IN ($inSql)
              AND YEAR(month_date) = :year
            GROUP BY icno, UPPER(color)
            SQL,
            $params,
        )->queryAll();

        $totals = [];
        foreach ($icnos as $staffIcno) {
            $totals[$staffIcno] = ['GREEN' => 0, 'YELLOW' => 0, 'RED' => 0];
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $staffIcno = (string) ($row['icno'] ?? '');
            $color = (string) ($row['card_color'] ?? '');
            if (isset($totals[$staffIcno][$color])) {
                $totals[$staffIcno][$color] = (int) ($row['total'] ?? 0);
            }
        }

        return $totals;
    }

    /**
     * @param list<string> $icnos
     * @return array<string, array<string, string>>
     */
    private function shieldRowsFor(array $icnos): array
    {
        if ($icnos === [] || !$this->mysqlTableExists('dbo', 'SelfRiskAssessmentResultStaff')) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT risk.icno, risk.riskGroupId, risk.assessmentTaken
            FROM `dbo`.`SelfRiskAssessmentResultStaff` risk
            INNER JOIN (
                SELECT icno, MAX(assessmentTaken) AS assessmentTaken
                FROM `dbo`.`SelfRiskAssessmentResultStaff`
                WHERE icno IN ($inSql)
                GROUP BY icno
            ) latest ON latest.icno = risk.icno AND latest.assessmentTaken = risk.assessmentTaken
            SQL,
            $params,
        )->queryAll();

        $shields = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $takenAt = $this->formatShieldDate($row['assessmentTaken'] ?? null, false);
            $shields[(string) ($row['icno'] ?? '')] = [
                'color' => $this->shieldColor((int) ($row['riskGroupId'] ?? 0)),
                'takenAt' => $takenAt,
                'expiresAt' => $this->formatShieldDate($row['assessmentTaken'] ?? null, true),
            ];
        }

        return $shields;
    }

    private function mysqlTableExists(string $schema, string $table): bool
    {
        return (bool) $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = :schema
              AND table_name = :table
            LIMIT 1
            SQL,
            [':schema' => $schema, ':table' => $table],
        )->queryScalar();
    }

    private function shieldColor(int $riskGroupId): string
    {
        return match ($riskGroupId) {
            2, 3 => 'RED',
            4 => 'YELLOW',
            6 => 'GREEN',
            default => 'BELUM ISI SHIELDS',
        };
    }

    private function formatShieldDate(mixed $value, bool $expiry): string
    {
        $value = $this->value($value, '');
        if ($value === '') {
            return '-';
        }

        $date = DateTimeImmutable::createFromFormat('m-d-Y H:i:sA', $value) ?: DateTimeImmutable::createFromFormat('m-d-Y H:i:s A', $value);
        if (!$date) {
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                return '-';
            }
            $date = (new DateTimeImmutable())->setTimestamp($timestamp);
        }

        if ($expiry) {
            $date = $date->modify('+1 week');
        }

        return $expiry ? $date->format('d/m/Y') : $date->format('d/m/Y h:i A');
    }

    /**
     * @param list<string> $icnos
     * @return array<string, array<string, array<string, string>>>
     */
    private function shiftRowsFor(array $icnos, string $start, string $end): array
    {
        if ($icnos === []) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $params[':start'] = $start;
        $params[':end'] = $end;
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT shift_row.id, shift_row.icno, shift_row.tarikh, ref.jenis_wp
            FROM attendance.tbl_shifts shift_row
            LEFT JOIN attendance.ref_wp ref ON ref.id = shift_row.wp_id
            WHERE shift_row.icno IN ($inSql)
              AND shift_row.tarikh BETWEEN :start AND :end
            SQL,
            $params,
        )->queryAll();

        $shifts = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $icno = (string) ($row['icno'] ?? '');
            $date = $this->dateOnly($row['tarikh'] ?? null);
            if ($icno !== '' && $date !== '') {
                $shifts[$icno][$date] = [
                    'id' => (string) ($row['id'] ?? ''),
                    'label' => $this->value($row['jenis_wp'] ?? null),
                ];
            }
        }

        return $shifts;
    }

    /**
     * @param list<string> $icnos
     * @return array<string, int>
     */
    private function wfhCountsByDate(array $icnos, string $start, string $end): array
    {
        $days = $this->wfhDaysFor($icnos, $start, $end);
        $counts = [];
        foreach ($days as $staffDays) {
            foreach (array_keys($staffDays) as $date) {
                $counts[$date] = ($counts[$date] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @param list<string> $icnos
     * @return array<string, array<string, bool>>
     */
    private function wfhDaysFor(array $icnos, string $start, string $end): array
    {
        if ($icnos === []) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('staff', $icnos);
        $params[':start'] = $start;
        $params[':end'] = $end;
        $rows = $this->db->createCommand(
            <<<SQL
            SELECT icno, start_date, end_date
            FROM attendance.tbl_wfh
            WHERE icno IN ($inSql)
              AND status = 'APPROVED'
              AND start_date <= :end
              AND end_date >= :start
            SQL,
            $params,
        )->queryAll();

        $days = [];
        $windowStart = new DateTimeImmutable($start);
        $windowEnd = new DateTimeImmutable($end);
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $staffIcno = (string) ($row['icno'] ?? '');
            $rowStartText = $this->dateOnly($row['start_date'] ?? null);
            $rowEndText = $this->dateOnly($row['end_date'] ?? null);
            if ($staffIcno === '' || $rowStartText === '' || $rowEndText === '') {
                continue;
            }

            $rowStart = new DateTimeImmutable($rowStartText);
            $rowEnd = new DateTimeImmutable($rowEndText);
            $cursor = $rowStart < $windowStart ? $windowStart : $rowStart;
            $last = $rowEnd > $windowEnd ? $windowEnd : $rowEnd;
            while ($cursor <= $last) {
                $days[$staffIcno][$cursor->format('Y-m-d')] = true;
                $cursor = $cursor->modify('+1 day');
            }
        }

        return $days;
    }

    /**
     * @param list<string> $icnos
     * @return array<string, array<string, mixed>>
     */
    private function recordsForStaffDate(array $icnos, string $date): array
    {
        if ($icnos === []) {
            return [];
        }

        [$inSql, $params] = $this->inConditionParams('icno', $icnos);
        $params[':date'] = $date;

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT r.*, ref.jenis_wp
            FROM attendance.tbl_rekod r
            LEFT JOIN attendance.ref_wp ref ON ref.id = r.wp_id
            WHERE r.icno IN ($inSql)
              AND r.tarikh = :date
            SQL,
            $params,
        )->queryAll();

        $records = [];
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['icno'])) {
                $records[(string) $row['icno']] = $row;
            }
        }

        return $records;
    }

    /**
     * @return array<string, int>
     */
    private function monthlySummaryForStaff(string $icno, int $month, int $year): array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT
                COUNT(CASE WHEN time_in IS NOT NULL AND time_out IS NOT NULL THEN 1 END) AS completed_days,
                COALESCE(SUM(CASE
                    WHEN time_in IS NOT NULL AND time_out IS NOT NULL AND time_out >= time_in
                    THEN TIMESTAMPDIFF(MINUTE, time_in, time_out)
                    ELSE 0
                END), 0) AS working_minutes,
                COALESCE(SUM(CASE WHEN late_in = 1 AND COALESCE(remark_status, '') <> 'APPROVED' THEN 1 ELSE 0 END), 0) AS late_in_total,
                COALESCE(SUM(CASE WHEN early_out = 1 AND COALESCE(remark_status, '') <> 'APPROVED' THEN 1 ELSE 0 END), 0) AS early_out_total,
                COALESCE(SUM(CASE WHEN incomplete = 1 AND COALESCE(remark_status, '') <> 'APPROVED' THEN 1 ELSE 0 END), 0) AS incomplete_total,
                COALESCE(SUM(CASE WHEN absent = 1 AND COALESCE(remark_status, '') <> 'APPROVED' THEN 1 ELSE 0 END), 0) AS absent_total,
                COALESCE(SUM(CASE
                    WHEN (external = 1 OR external_out = 1) AND COALESCE(remark_status, '') <> 'APPROVED'
                    THEN 1 ELSE 0
                END), 0) AS external_total
            FROM attendance.tbl_rekod
            WHERE icno = :icno
              AND MONTH(tarikh) = :month
              AND YEAR(tarikh) = :year
            SQL,
            [':icno' => $icno, ':month' => $month, ':year' => $year],
        )->queryOne();

        if (!is_array($row)) {
            return [
                'completedDays' => 0,
                'workingMinutes' => 0,
                'lateIn' => 0,
                'earlyOut' => 0,
                'incomplete' => 0,
                'absent' => 0,
                'external' => 0,
            ];
        }

        return [
            'completedDays' => (int) ($row['completed_days'] ?? 0),
            'workingMinutes' => (int) ($row['working_minutes'] ?? 0),
            'lateIn' => (int) ($row['late_in_total'] ?? 0),
            'earlyOut' => (int) ($row['early_out_total'] ?? 0),
            'incomplete' => (int) ($row['incomplete_total'] ?? 0),
            'absent' => (int) ($row['absent_total'] ?? 0),
            'external' => (int) ($row['external_total'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function departmentPpScope(string $icno): ?array
    {
        $row = $this->db->createCommand(
            <<<SQL
            SELECT dept.id, dept.fullname, dept.shortname, dept.pp
            FROM hronline.tblprcobiodata staff
            INNER JOIN hronline.department dept ON dept.id = staff.DeptId
            WHERE staff.ICNO = :icno
              AND dept.isActive = 1
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryOne();

        if (!is_array($row) || (string) ($row['pp'] ?? '') !== $icno) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => $this->value($row['fullname'] ?? null),
            'shortName' => $this->value($row['shortname'] ?? null),
        ];
    }

    /**
     * @param list<string> $values
     * @return array{0: string, 1: array<string, string>}
     */
    private function inConditionParams(string $prefix, array $values): array
    {
        $placeholders = [];
        $params = [];
        foreach (array_values($values) as $index => $value) {
            $placeholder = ':' . $prefix . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $value;
        }

        return [implode(', ', $placeholders), $params];
    }

    /**
     * @return list<int>
     */
    private function intList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $ids = [];
        foreach ($values as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapAttendanceReasonApproval(array $row): array
    {
        return [
            'id' => (string) ($row['id'] ?? ''),
            'icno' => $this->value($row['icno'] ?? null),
            'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
            'department' => $this->value($row['department_shortname'] ?? null),
            'date' => $this->formatDate($row['tarikh'] ?? null),
            'day' => $this->value($row['day'] ?? null),
            'wbb' => $this->value($row['jenis_wp'] ?? null),
            'timeIn' => $this->formatTime($row['time_in'] ?? null),
            'timeOut' => $this->formatTime($row['time_out'] ?? null),
            'statusLabels' => $this->statusLabels($row),
            'remark' => $this->value($row['remark'] ?? null),
            'appRemark' => $this->value($row['app_remark'] ?? null, ''),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapWfhActionRow(array $row): array
    {
        $docHash = $this->value($row['hashcode'] ?? null, '');

        return [
            'id' => (string) ($row['id'] ?? ''),
            'icno' => $this->value($row['icno'] ?? null),
            'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
            'grade' => $this->value($row['job_grade'] ?? null),
            'department' => $this->value($row['department_shortname'] ?? null),
            'date' => $this->value($row['full_date'] ?? null, $this->dateRangeText($row['start_date'] ?? null, $row['end_date'] ?? null)),
            'period' => $this->value($row['tempoh'] ?? null),
            'remark' => $this->value($row['remark'] ?? null),
            'status' => $this->value($row['status'] ?? null),
            'entryDate' => $this->value($row['entry_dt'] ?? null),
            'appRemark' => $this->value($row['app_remark'] ?? null, ''),
            'documentName' => $this->value($row['doc_name'] ?? null, ''),
            'documentUrl' => $docHash === '' ? '' : 'https://mediahost.ums.edu.my/api/v1/viewFile/' . rawurlencode($docHash),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapThtcActionRow(array $row, string $stage): array
    {
        $doc = $this->value($row['supporting_doc'] ?? null, '');

        return [
            'id' => (string) ($row['id'] ?? ''),
            'stage' => $stage,
            'icno' => $this->value($row['icno'] ?? null),
            'staffName' => $this->employees->displayName($row['staff_name'] ?? null),
            'department' => $this->value($row['department_shortname'] ?? null),
            'date' => $this->formatDate($row['tarikh'] ?? null),
            'day' => $this->value($row['day'] ?? null),
            'absentRemark' => $this->value($row['absent_remark'] ?? null),
            'remark' => $this->value($row['remark'] ?? null),
            'verifierRemark' => $this->value($row['remark_verifier'] ?? null, ''),
            'approverRemark' => $this->value($row['remark_approver'] ?? null, ''),
            'status' => $this->thtcStatus((int) ($row['status'] ?? 0)),
            'documentUrl' => $doc !== '' && $doc !== 'deleted' ? 'https://mediahost.ums.edu.my/api/v1/viewFile/' . rawurlencode($doc) : '',
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function canManageWfh(string $icno, array $row): bool
    {
        if ($this->isAdministrator($icno)) {
            return true;
        }

        foreach (['icno', 'app_by', 'ver_by', 'set_by'] as $field) {
            if ($this->value($row[$field] ?? null, '') === $icno) {
                return true;
            }
        }

        $targetIcno = $this->value($row['icno'] ?? null, '');
        if ($targetIcno === '') {
            return false;
        }

        foreach ($this->supervisorStaff($icno) as $person) {
            if ((string) ($person['icno'] ?? '') === $targetIcno) {
                return true;
            }
        }

        foreach ($this->departmentChiefStaff($icno) as $person) {
            if ((string) ($person['icno'] ?? '') === $targetIcno) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function canReviewThtc(string $icno, array $row, string $stage): bool
    {
        if ($this->isAdministrator($icno)) {
            return true;
        }

        $field = $stage === 'approve' ? 'icno_approver' : 'icno_verifier';
        $value = $this->value($row[$field] ?? null, '');

        return $value !== '' && str_contains($value, $icno);
    }

    private function notify(string $icno, string $title, string $content, string $dateTime): void
    {
        if ($icno === '') {
            return;
        }

        try {
            $this->notifications->create($icno, $title, $content, $this->notificationDate($dateTime));
        } catch (\Throwable) {
            // Notification failure must not block the attendance workflow update.
        }
    }

    private function notificationDate(string $dateTime): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    private function refreshPendingTask(string $icno, ?int $commandId): void
    {
        if ($icno === '') {
            return;
        }

        try {
            $condition = ['icno' => $icno];
            if ($commandId !== null) {
                $condition['command_id'] = $commandId;
            }
            $this->db->createCommand()->update('system_core.tbl_pending_task', [
                'count' => 0,
                'status' => 0,
            ], $condition)->execute();
        } catch (\Throwable) {
            // Pending-task refresh is best-effort parity with the Yii2 console command.
        }
    }

    /**
     * @param iterable<mixed> $rows
     * @return list<array<string, mixed>>
     */
    private function mapRows(iterable $rows, callable $callback): array
    {
        $mapped = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $mapped[] = $callback($row);
            }
        }

        return $mapped;
    }

    private function nonComplianceCount(string $icno, int $month, int $year, string $field): int
    {
        $allowed = ['late_in', 'early_out', 'incomplete', 'absent', 'external', 'external_out'];
        if (!in_array($field, $allowed, true)) {
            return 0;
        }

        return (int) $this->db->createCommand(
            <<<SQL
            SELECT COUNT(*)
            FROM attendance.tbl_rekod
            WHERE icno = :icno
              AND MONTH(tarikh) = :month
              AND YEAR(tarikh) = :year
              AND $field = 1
              AND COALESCE(remark_status, '') <> 'APPROVED'
            SQL,
            [':icno' => $icno, ':month' => $month, ':year' => $year],
        )->queryScalar();
    }

    /**
     * @return array<string, list<string>>
     */
    private function dateTags(string $icno, DateTimeImmutable $start, DateTimeImmutable $end, int $campusId): array
    {
        $tags = [];

        $leaveRows = $this->db->createCommand(
            <<<SQL
            SELECT records.start_date, records.end_date, leave_type.jenis_cuti_nama
            FROM hrm.cuti_tbl_records records
            LEFT JOIN hrm.cuti_leave_type leave_type ON leave_type.jenis_cuti_id = records.jenis_cuti_id
            WHERE records.icno = :icno
              AND records.start_date <= :end
              AND records.end_date >= :start
              AND records.status <> 'REJECTED'
            SQL,
            [':icno' => $icno, ':start' => $start->format('Y-m-d'), ':end' => $end->format('Y-m-d')],
        )->queryAll();
        foreach ($leaveRows as $row) {
            if (is_array($row)) {
                $this->addDateRangeTag($tags, $row['start_date'] ?? null, $row['end_date'] ?? null, $this->value($row['jenis_cuti_nama'] ?? null, 'Leave'));
            }
        }

        $holidayRows = $this->db->createCommand(
            <<<SQL
            SELECT nama_cuti, tarikh_cuti, sabah_sahaja, wilayah_sahaja
            FROM hrm.cuti_public_holiday
            WHERE tarikh_cuti BETWEEN :start AND :end
            SQL,
            [':start' => $start->format('Y-m-d'), ':end' => $end->format('Y-m-d')],
        )->queryAll();
        foreach ($holidayRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $sabahOnly = (int) ($row['sabah_sahaja'] ?? 0) === 1;
            $wilayahOnly = (int) ($row['wilayah_sahaja'] ?? 0) === 1;
            if (($wilayahOnly && $campusId !== 2) || ($sabahOnly && $campusId === 2)) {
                continue;
            }

            $this->addDateTag($tags, $row['tarikh_cuti'] ?? null, $this->value($row['nama_cuti'] ?? null, 'Public Holiday'));
        }

        $wfhRows = $this->db->createCommand(
            <<<SQL
            SELECT start_date, end_date
            FROM attendance.tbl_wfh
            WHERE icno = :icno
              AND start_date <= :end
              AND end_date >= :start
              AND status = 'APPROVED'
            SQL,
            [':icno' => $icno, ':start' => $start->format('Y-m-d'), ':end' => $end->format('Y-m-d')],
        )->queryAll();
        foreach ($wfhRows as $row) {
            if (is_array($row)) {
                $this->addDateRangeTag($tags, $row['start_date'] ?? null, $row['end_date'] ?? null, 'WFH');
            }
        }

        $cursor = $start;
        while ($cursor <= $end) {
            if (in_array($cursor->format('D'), ['Sat', 'Sun'], true)) {
                $this->addDateTag($tags, $cursor->format('Y-m-d'), 'Weekend');
            }
            $cursor = $cursor->modify('+1 day');
        }

        return $tags;
    }

    /**
     * @param array<string, list<string>> $tags
     */
    private function addDateRangeTag(array &$tags, mixed $start, mixed $end, string $label): void
    {
        $startDate = $this->dateOnly($start);
        $endDate = $this->dateOnly($end);
        if ($startDate === '' || $endDate === '') {
            return;
        }

        $cursor = new DateTimeImmutable($startDate);
        $last = new DateTimeImmutable($endDate);
        while ($cursor <= $last) {
            $this->addDateTag($tags, $cursor->format('Y-m-d'), $label);
            $cursor = $cursor->modify('+1 day');
        }
    }

    /**
     * @param array<string, list<string>> $tags
     */
    private function addDateTag(array &$tags, mixed $date, string $label): void
    {
        $date = $this->dateOnly($date);
        if ($date === '' || $label === '') {
            return;
        }

        $tags[$date] ??= [];
        if (!in_array($label, $tags[$date], true)) {
            $tags[$date][] = $label;
        }
    }

    private function hoursBetween(mixed $start, mixed $end): string
    {
        $minutes = $this->minutesBetween($start, $end);

        return $minutes <= 0 ? '-' : $this->formatMinutes($minutes);
    }

    private function minutesBetween(mixed $start, mixed $end): int
    {
        $startTime = strtotime((string) $start);
        $endTime = strtotime((string) $end);
        if ($startTime === false || $endTime === false || $endTime < $startTime) {
            return 0;
        }

        return (int) floor(($endTime - $startTime) / 60);
    }

    private function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $remaining);
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, string>
     */
    private function locationLinks(array $record): array
    {
        $links = [];
        foreach (['in_lat_lng' => 'IN', 'out_lat_lng' => 'OUT'] as $field => $label) {
            $latLng = $this->value($record[$field] ?? null, '');
            if ($latLng !== '') {
                $encoded = rawurlencode($latLng);
                $links[$label] = 'https://www.google.com/maps/dir/' . $encoded . '/' . $encoded . '/@' . $encoded . ',18z';
            }
        }

        return $links;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function remarkLabel(array $record): string
    {
        $status = $this->value($record['remark_status'] ?? null, '');
        if ($status === 'ENTRY') {
            return 'Remarked';
        }

        if ($status === 'APPROVED') {
            return 'Approved';
        }

        return $this->statusLabels($record) === [] ? '-' : 'No remark / not approved';
    }

    private function cardPerformance(int $green, int $red): string
    {
        if ($red >= 1) {
            return 'Sangat Tidak Memuaskan';
        }

        if ($green >= 3) {
            return 'Tidak Memuaskan';
        }

        if ($green === 1 || $green === 2) {
            return 'Memuaskan';
        }

        return 'Cemerlang';
    }

    private function thtcStatus(int $status): string
    {
        return match ($status) {
            1 => 'ENTRY',
            2 => 'VERIFIED',
            3 => 'APPROVED',
            4 => 'RETURNED',
            5 => 'REJECTED',
            default => '',
        };
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'January',
            2 => 'February',
            3 => 'Mac',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ][$month] ?? '-';
    }

    private function formatDate(mixed $date): string
    {
        $date = $this->dateOnly($date);
        if ($date === '') {
            return '-';
        }

        return date('d/m/Y', strtotime($date));
    }

    private function dateRangeText(mixed $start, mixed $end): string
    {
        $startDate = $this->formatDate($start);
        $endDate = $this->formatDate($end);

        return $startDate === '-' && $endDate === '-' ? '-' : $startDate . ' - ' . $endDate;
    }

    private function dateOnly(mixed $value): string
    {
        $value = $this->value($value, '');
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? '' : date('Y-m-d', $timestamp);
    }

    private function dateInput(mixed $value): string
    {
        $value = $this->value($value, '');
        if ($value === '') {
            return '';
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date instanceof DateTimeImmutable) {
                return $date->format('Y-m-d');
            }
        }

        return $this->dateOnly($value);
    }

    private function positiveInt(mixed $value): ?int
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }

    private function wbbStatusLabel(mixed $status): string
    {
        return match (strtoupper($this->value($status, ''))) {
            'ENTRY' => 'Menunggu Semakan',
            'VERIFIED' => 'Menunggu Kelulusan',
            'APPROVED' => 'Diluluskan',
            'REJECTED' => 'Ditolak',
            default => $this->value($status),
        };
    }

    private function value(mixed $value, string $fallback = '-'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }

    private function timeValue(mixed $value): string
    {
        return $this->value($value, '');
    }

    private function formatTime(mixed $value): string
    {
        $value = $this->value($value, '');
        if ($value === '') {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }

        return date('h:i A', $timestamp);
    }

    private function timeOnly(string $value): string
    {
        $timestamp = strtotime($value);

        return $timestamp === false ? '' : date('H:i:s', $timestamp);
    }
}
