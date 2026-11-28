<?php

declare(strict_types=1);

namespace App\Shared\Perkhidmatan;

use App\Shared\EmployeeRepository;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class PerkhidmatanAccessService
{
    public function __construct(
        private ConnectionInterface $db,
        private EmployeeRepository $employees,
    ) {}

    public function icnoFromIdentity(string $identityId): string
    {
        return $this->employees->icnoFromIdentity($identityId);
    }

    public function hasAdminAccess(string $icno): bool
    {
        $exists = $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM system_core.tbl_user_access
            WHERE icno = :icno
              AND access IN (1, 2, 3)
            LIMIT 1
            SQL,
            [':icno' => $icno],
        )->queryScalar();

        return $exists !== false && $exists !== null;
    }

    public function canView(string $currentIcno, string $targetIcno): bool
    {
        return $currentIcno !== '' && ($currentIcno === $targetIcno || $this->hasAdminAccess($currentIcno));
    }
}
