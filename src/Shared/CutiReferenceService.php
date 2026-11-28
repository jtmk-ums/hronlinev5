<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class CutiReferenceService
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function selectableLeaveTypes(): array
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
            $types[] = [
                'id' => (string) ($row['jenis_cuti_id'] ?? ''),
                'name' => $this->value($row['jenis_cuti_catatan'] ?? $row['jenis_cuti_nama'] ?? null),
                'nameEn' => $this->value($row['jenis_cuti_catatan_bi'] ?? null, ''),
                'legacyRoute' => 'cuti/individu/' . $this->value($row['action'] ?? null, 'pilih'),
                'formType' => $this->value($row['form_type'] ?? null, ''),
            ];
        }

        return $types;
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
