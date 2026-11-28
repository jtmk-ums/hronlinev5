<?php

declare(strict_types=1);

namespace App\Shared;

use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class NotificationService
{
    public const STATUS_UNREAD = 0;
    public const STATUS_READ = 1;
    public const STATUS_ARCHIVED = 2;

    private const PAGE_SIZE = 5;

    public function __construct(
        private ConnectionInterface $db,
        private NotificationContentSanitizer $sanitizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function unreadInbox(string $icno, int $page, ?int $selectedId = null): array
    {
        $selected = $selectedId !== null && $selectedId > 0
            ? $this->readForStaff($icno, $selectedId)
            : null;

        $notifications = $this->queryForStaff(
            $icno,
            [self::STATUS_UNREAD],
            new NotificationCriteria(page: $page, pageSize: self::PAGE_SIZE),
            '/site/notifikasi?id=',
        );
        $notifications['selected'] = $selected;

        return $notifications;
    }

    /**
     * Kept for existing callers that expect the non-archived notification center.
     *
     * @return array<string, mixed>
     */
    public function listForStaff(string $icno, int $page): array
    {
        return $this->queryForStaff(
            $icno,
            [self::STATUS_UNREAD, self::STATUS_READ],
            new NotificationCriteria(page: $page, pageSize: self::PAGE_SIZE),
            '/site/read_noti?id=',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function searchForStaff(string $icno, NotificationCriteria $criteria): array
    {
        return $this->queryForStaff(
            $icno,
            [self::STATUS_UNREAD, self::STATUS_READ],
            $criteria,
            '/site/read_noti?id=',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function archiveForStaff(string $icno, NotificationCriteria $criteria): array
    {
        return $this->queryForStaff(
            $icno,
            [self::STATUS_ARCHIVED],
            $criteria,
            '/site/read-arc?id=',
        );
    }

    /**
     * @return array<string, string>|null
     */
    public function readForStaff(string $icno, int $id): ?array
    {
        $row = $this->rowForStaff($icno, $id);
        if ($row === null) {
            return null;
        }

        if ((int) ($row['status'] ?? 0) === self::STATUS_UNREAD) {
            $this->markRead($icno, [$id]);
            $row['status'] = self::STATUS_READ;
        }

        return $this->mapNotification($row, '/site/read_noti?id=');
    }

    /**
     * @return array<string, string>|null
     */
    public function readArchiveForStaff(string $icno, int $id): ?array
    {
        $row = $this->rowForStaff($icno, $id, [self::STATUS_ARCHIVED]);

        return $row === null ? null : $this->mapNotification($row, '/site/read-arc?id=');
    }

    public function create(string $icno, string $title, string $content, ?\DateTimeInterface $date = null): int
    {
        $icno = substr(trim($icno), 0, 15);
        if ($icno === '') {
            return 0;
        }

        $this->db->createCommand()->insert('system_core.tbl_notifications', [
            'icno' => $icno,
            'title' => substr(trim($title), 0, 100),
            'content' => substr(trim($content), 0, 1000),
            'ntf_dt' => ($date ?? new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'status' => self::STATUS_UNREAD,
        ])->execute();

        $id = $this->db->getLastInsertId();

        return is_numeric($id) ? (int) $id : 0;
    }

    /**
     * @param list<array{icno: string, title: string, content: string, date?: \DateTimeInterface|null}> $messages
     */
    public function createMany(array $messages): int
    {
        $created = 0;
        foreach ($messages as $message) {
            if ($this->create(
                $message['icno'],
                $message['title'],
                $message['content'],
                $message['date'] ?? null,
            ) > 0) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * @param list<int> $ids
     */
    public function markRead(string $icno, array $ids): int
    {
        return $this->setStatus($icno, $ids, self::STATUS_READ);
    }

    /**
     * @param list<int> $ids
     */
    public function archive(string $icno, array $ids): int
    {
        return $this->setStatus($icno, $ids, self::STATUS_ARCHIVED);
    }

    /**
     * @param list<int> $statuses
     * @return array<string, mixed>
     */
    private function queryForStaff(string $icno, array $statuses, NotificationCriteria $criteria, string $readUrlPrefix): array
    {
        $statuses = $this->effectiveStatuses($statuses, $criteria->status);
        $params = [':icno' => $icno];
        $where = ['icno = :icno'];

        $statusPlaceholders = [];
        foreach ($statuses as $index => $status) {
            $placeholder = ':status' . $index;
            $statusPlaceholders[] = $placeholder;
            $params[$placeholder] = $status;
        }
        $where[] = 'status IN (' . implode(', ', $statusPlaceholders) . ')';

        if ($criteria->title !== '') {
            $where[] = 'title LIKE :title';
            $params[':title'] = '%' . $criteria->title . '%';
        }

        if ($criteria->content !== '') {
            $where[] = 'content LIKE :content';
            $params[':content'] = '%' . $criteria->content . '%';
        }

        if ($criteria->date !== '') {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $criteria->date) === 1) {
                $where[] = 'DATE(ntf_dt) = :date';
                $params[':date'] = $criteria->date;
            } else {
                $where[] = 'ntf_dt LIKE :date';
                $params[':date'] = '%' . $criteria->date . '%';
            }
        }

        $whereSql = implode("\n              AND ", $where);
        $total = (int) $this->db->createCommand(
            <<<SQL
            SELECT COUNT(*)
            FROM system_core.tbl_notifications
            WHERE {$whereSql}
            SQL,
            $params,
        )->queryScalar();

        $limit = max(1, min(50, $criteria->pageSize));
        $pageCount = max(1, (int) ceil(max(0, $total) / $limit));
        $page = min(max(1, $criteria->page), $pageCount);
        $offset = ($page - 1) * $limit;

        $rows = $this->db->createCommand(
            <<<SQL
            SELECT id, title, content, ntf_dt, status
            FROM system_core.tbl_notifications
            WHERE {$whereSql}
            ORDER BY ntf_dt DESC
            LIMIT {$limit}
            OFFSET {$offset}
            SQL,
            $params,
        )->queryAll();

        $notifications = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $notifications[] = $this->mapNotification($row, $readUrlPrefix);
            }
        }

        return [
            'rows' => $notifications,
            'total' => max(0, $total),
            'count' => count($notifications),
            'limit' => $limit,
            'page' => $page,
            'pageCount' => $pageCount,
            'offset' => $offset,
            'criteria' => $criteria,
        ];
    }

    /**
     * @param list<int>|null $statuses
     * @return array<string, mixed>|null
     */
    private function rowForStaff(string $icno, int $id, ?array $statuses = null): ?array
    {
        $params = [
            ':id' => $id,
            ':icno' => $icno,
        ];
        $statusSql = '';
        if ($statuses !== null) {
            $statusPlaceholders = [];
            foreach ($statuses as $index => $status) {
                $placeholder = ':status' . $index;
                $statusPlaceholders[] = $placeholder;
                $params[$placeholder] = $status;
            }
            $statusSql = ' AND status IN (' . implode(', ', $statusPlaceholders) . ')';
        }

        $row = $this->db->createCommand(
            <<<SQL
            SELECT id, title, content, ntf_dt, status
            FROM system_core.tbl_notifications
            WHERE id = :id
              AND icno = :icno{$statusSql}
            LIMIT 1
            SQL,
            $params,
        )->queryOne();

        return is_array($row) ? $row : null;
    }

    /**
     * @param list<int> $allowedStatuses
     * @return list<int>
     */
    private function effectiveStatuses(array $allowedStatuses, ?int $requestedStatus): array
    {
        $allowedStatuses = array_values(array_unique(array_map('intval', $allowedStatuses)));
        if ($requestedStatus !== null && in_array($requestedStatus, $allowedStatuses, true)) {
            return [$requestedStatus];
        }

        return $allowedStatuses;
    }

    /**
     * @param list<int> $ids
     */
    private function setStatus(string $icno, array $ids, int $status): int
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return 0;
        }

        $params = [
            ':icno' => $icno,
            ':status' => $status,
        ];
        $placeholders = [];
        foreach ($ids as $index => $id) {
            $placeholder = ':id' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        return $this->db->createCommand(
            'UPDATE system_core.tbl_notifications SET status = :status WHERE icno = :icno AND id IN (' . implode(', ', $placeholders) . ')',
            $params,
        )->execute();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private function mapNotification(array $row, string $readUrlPrefix): array
    {
        $id = $this->value($row['id'] ?? null, '');
        $status = (int) ($row['status'] ?? 0);
        $title = $this->value($row['title'] ?? null, 'Notification');
        $content = $this->value($row['content'] ?? null, '');
        $plainContent = $this->sanitizer->toPlainText($content);

        return [
            'id' => $id,
            'title' => $title,
            'shortTitle' => $this->firstToken($title),
            'content' => $content,
            'contentText' => $plainContent,
            'contentHtml' => $this->sanitizer->toSafeHtml($content),
            'excerpt' => $this->excerpt($plainContent),
            'date' => $this->formatDate($row['ntf_dt'] ?? null, 'd/m/Y h:ia'),
            'dateShort' => $this->formatDate($row['ntf_dt'] ?? null, 'Y/m/d'),
            'status' => (string) $status,
            'statusLabel' => $this->statusLabel($status),
            'url' => $readUrlPrefix . rawurlencode($id),
        ];
    }

    private function firstToken(string $value): string
    {
        $token = strtok($value, ' ');

        return is_string($token) && $token !== '' ? $token : $value;
    }

    private function excerpt(string $value): string
    {
        $sentence = strtok($value, '.');
        $sentence = is_string($sentence) ? trim($sentence) : trim($value);
        if ($sentence === '') {
            return '-';
        }

        return strlen($sentence) > 50 ? substr($sentence, 0, 50) . '...' : $sentence;
    }

    private function formatDate(mixed $value, string $format): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', trim((string) $value));
        if ($date === false) {
            return trim((string) $value);
        }

        return $date->format($format);
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_UNREAD => 'Belum Dibaca',
            self::STATUS_READ => 'Dibaca',
            self::STATUS_ARCHIVED => 'Arkib',
            default => 'Tidak Diketahui',
        };
    }

    private function value(mixed $value, string $fallback = '-'): string
    {
        if ($value === null || (!is_scalar($value) && !$value instanceof \Stringable)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value === '' ? $fallback : $value;
    }
}
