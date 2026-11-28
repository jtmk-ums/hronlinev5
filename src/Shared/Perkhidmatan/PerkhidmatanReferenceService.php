<?php

declare(strict_types=1);

namespace App\Shared\Perkhidmatan;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class PerkhidmatanReferenceService
{
    public function __construct(private ConnectionInterface $db) {}

    /**
     * @return list<array{id: string, name: string}>
     */
    public function options(string $sql, string $idColumn, string $nameColumn): array
    {
        try {
            $rows = $this->db->createCommand($sql)->queryAll();
        } catch (\Throwable) {
            return [];
        }

        $options = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = $this->value($row[$idColumn] ?? null, '');
            $name = $this->value($row[$nameColumn] ?? null, '');
            if ($id === '' || $name === '') {
                continue;
            }

            $options[] = ['id' => $id, 'name' => $name];
        }

        return $options;
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
