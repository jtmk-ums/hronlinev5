<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class TopbarService
{
    private const PROFILE_PHOTO_BASE = 'https://bkums.ums.edu.my/public/picstf/';
    private const CHANGE_PASSWORD_URL = 'https://drive.google.com/file/d/11E7IFgWxNnBiq1IuA2z8CoPDMvQz13vi/view';

    public function __construct(
        private ConnectionInterface $db,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private NotificationContentSanitizer $sanitizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function context(ServerRequestInterface $request): array
    {
        $legacySession = $this->sessionReader->read($request);
        if ($legacySession === null) {
            return $this->guestContext();
        }

        $icno = $this->employees->icnoFromIdentity($legacySession['identityId']);
        if ($icno === '') {
            return $this->guestContext();
        }

        $staff = $this->employees->byIcno($icno);
        $displayName = $staff === null
            ? $icno
            : $this->employees->displayName($staff['CONm'] ?? null);

        return [
            'authenticated' => true,
            'icno' => $icno,
            'displayName' => $displayName,
            'shortName' => $this->shortName($displayName),
            'profileImage' => self::PROFILE_PHOTO_BASE . strtoupper(sha1($icno)) . '.jpeg',
            'profileUrl' => '/biodata/userview',
            'changePasswordUrl' => self::CHANGE_PASSWORD_URL,
            'logoutUrl' => '/site/logout',
            'showDiary' => true,
            'diaryUrl' => LegacyUrl::to('diari/index'),
            'notificationsUrl' => '/site/notifikasi',
            'currentUrl' => $this->currentUrl($request),
            'notifications' => $this->unreadNotifications($icno, 5),
            'notificationCount' => $this->unreadNotificationCount($icno),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function guestContext(): array
    {
        return [
            'authenticated' => false,
            'notifications' => [],
            'notificationCount' => 0,
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function unreadNotifications(string $icno, int $limit): array
    {
        try {
            $rows = $this->db->createCommand(
                <<<SQL
                SELECT id, title, content, ntf_dt
                FROM system_core.tbl_notifications
                WHERE icno = :icno
                  AND status = 0
                ORDER BY ntf_dt DESC
                LIMIT {$limit}
                SQL,
                [':icno' => $icno],
            )->queryAll();
        } catch (\Throwable) {
            return [];
        }

        $notifications = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $notifications[] = $this->notificationSummary($row);
        }

        return $notifications;
    }

    private function unreadNotificationCount(string $icno): int
    {
        try {
            $count = $this->db->createCommand(
                'SELECT COUNT(*) FROM system_core.tbl_notifications WHERE icno = :icno AND status = 0',
                [':icno' => $icno],
            )->queryScalar();
        } catch (\Throwable) {
            return 0;
        }

        return max(0, (int) $count);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private function notificationSummary(array $row): array
    {
        $id = $this->value($row['id'] ?? null, '');
        $title = $this->value($row['title'] ?? null, 'Notification');
        $content = $this->value($row['content'] ?? null, '');
        $plainContent = $this->sanitizer->toPlainText($content);

        return [
            'id' => $id,
            'title' => $title,
            'shortTitle' => $this->firstToken($title),
            'content' => $content,
            'excerpt' => $this->firstSentence($plainContent),
            'date' => $this->formatDate($row['ntf_dt'] ?? null),
            'url' => '/site/read_noti?id=' . rawurlencode($id),
        ];
    }

    private function shortName(string $displayName): string
    {
        $parts = preg_split('/\s+/', trim($displayName)) ?: [];
        $parts = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));

        if ($parts === []) {
            return 'Staff';
        }

        return $parts[0];
    }

    private function firstToken(string $value): string
    {
        $token = strtok($value, ' ');

        return is_string($token) && $token !== '' ? $token : $value;
    }

    private function firstSentence(string $value): string
    {
        $sentence = strtok($value, '.');
        $sentence = is_string($sentence) ? trim($sentence) : trim($value);

        return $sentence === '' ? '-' : $sentence;
    }

    private function formatDate(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', trim((string) $value));
        if ($date === false) {
            return trim((string) $value);
        }

        return $date->format('d/m/Y h:ia');
    }

    private function currentUrl(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $path = $uri->getPath() !== '' ? $uri->getPath() : '/';
        $query = $uri->getQuery();

        return $path . ($query !== '' ? '?' . $query : '');
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
