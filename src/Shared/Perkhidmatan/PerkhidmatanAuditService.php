<?php

declare(strict_types=1);

namespace App\Shared\Perkhidmatan;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class PerkhidmatanAuditService
{
    public function __construct(private ConnectionInterface $db) {}

    /**
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $context
     */
    public function logCreate(string $tableName, string $icno, string $idValue, array $newValues, array $context = []): void
    {
        $payload = [];
        foreach ($newValues as $field => $value) {
            $payload[] = [$field, $value];
        }

        $this->insertUpdateStatus($tableName, $icno, $idValue, 0, $payload, $context);
    }

    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $context
     */
    public function logUpdate(string $tableName, string $icno, string $idValue, array $oldValues, array $newValues, array $context = []): void
    {
        $changed = [];
        foreach ($newValues as $field => $value) {
            if (($oldValues[$field] ?? null) === $value) {
                continue;
            }

            $changed[] = [$field, $oldValues[$field] ?? null, $value];
        }

        if ($changed === []) {
            return;
        }

        $this->insertUpdateStatus($tableName, $icno, $idValue, 1, $changed, $context);
    }

    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $context
     */
    public function logDelete(string $tableName, string $icno, string $idValue, array $oldValues, array $context = []): void
    {
        $payload = [];
        foreach ($oldValues as $field => $value) {
            $payload[] = [$field, $value];
        }

        $this->insertUpdateStatus($tableName, $icno, $idValue, 2, $payload, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function touchStatOnCreate(string $tableName, string $icno, string $idValue, array $context = []): void
    {
        $this->insertStat($tableName, $icno, $idValue, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function touchStatOnUpdate(string $tableName, string $icno, string $idValue, array $context = []): void
    {
        $exists = $this->db->createCommand(
            <<<SQL
            SELECT 1
            FROM hronline.tbl_stat
            WHERE ICNO = :icno
              AND `table` = :tableName
              AND idval = :idValue
            LIMIT 1
            SQL,
            [
                ':icno' => $icno,
                ':tableName' => $tableName,
                ':idValue' => $idValue,
            ],
        )->queryScalar();

        if ($exists === false || $exists === null) {
            $this->insertStat($tableName, $icno, $idValue, $context);
            return;
        }

        $this->db->createCommand(
            <<<SQL
            UPDATE hronline.tbl_stat
            SET status = :status,
                date_submit = :dateSubmit,
                `desc` = :description
            WHERE ICNO = :icno
              AND `table` = :tableName
              AND idval = :idValue
            SQL,
            [
                ':status' => (string) ($context['status'] ?? '0'),
                ':dateSubmit' => $this->now(),
                ':description' => (string) ($context['description'] ?? ''),
                ':icno' => $icno,
                ':tableName' => $tableName,
                ':idValue' => $idValue,
            ],
        )->execute();
    }

    public function deleteStat(string $tableName, string $icno, string $idValue): void
    {
        $this->db->createCommand(
            <<<SQL
            DELETE FROM hronline.tbl_stat
            WHERE ICNO = :icno
              AND `table` = :tableName
              AND idval = :idValue
            SQL,
            [
                ':icno' => $icno,
                ':tableName' => $tableName,
                ':idValue' => $idValue,
            ],
        )->execute();
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     */
    private function insertUpdateStatus(string $tableName, string $icno, string $idValue, int $activity, array $payload, array $context): void
    {
        $this->db->createCommand(
            <<<SQL
            INSERT INTO hronline.updatestatus
                (usern, COTableName, COActivity, COUpadteDate, COUpdateIP, COUpdateComp, COUpdateCompUser, COUpdateSQL, idval, con_action)
            VALUES
                (:icno, :tableName, :activity, :updatedAt, :ip, :computer, :computerUser, :sqlPayload, :idValue, :action)
            SQL,
            [
                ':icno' => $icno,
                ':tableName' => $tableName,
                ':activity' => $activity,
                ':updatedAt' => $this->now(),
                ':ip' => (string) ($context['ip'] ?? ''),
                ':computer' => (string) ($context['computer'] ?? ''),
                ':computerUser' => (string) ($context['computerUser'] ?? ($context['actorIcno'] ?? '')),
                ':sqlPayload' => serialize($payload),
                ':idValue' => $idValue,
                ':action' => (string) ($context['action'] ?? ''),
            ],
        )->execute();
    }

    /**
     * @param array<string, mixed> $context
     */
    private function insertStat(string $tableName, string $icno, string $idValue, array $context): void
    {
        $this->db->createCommand(
            <<<SQL
            INSERT INTO hronline.tbl_stat
                (ICNO, `table`, idval, status, date_submit, `desc`)
            VALUES
                (:icno, :tableName, :idValue, :status, :dateSubmit, :description)
            SQL,
            [
                ':icno' => $icno,
                ':tableName' => $tableName,
                ':idValue' => $idValue,
                ':status' => (string) ($context['status'] ?? '0'),
                ':dateSubmit' => $this->now(),
                ':description' => (string) ($context['description'] ?? ''),
            ],
        )->execute();
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}
