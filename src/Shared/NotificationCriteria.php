<?php

declare(strict_types=1);

namespace App\Shared;

final readonly class NotificationCriteria
{
    public function __construct(
        public int $page = 1,
        public string $title = '',
        public ?int $status = null,
        public string $content = '',
        public string $date = '',
        public int $pageSize = 5,
    ) {}

    /**
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query, int $pageSize = 5): self
    {
        $legacy = isset($query['NotificationSearch']) && is_array($query['NotificationSearch'])
            ? $query['NotificationSearch']
            : [];

        return new self(
            page: self::intValue($query['page'] ?? null, 1),
            title: self::stringValue($query['title'] ?? $legacy['title'] ?? null),
            status: self::nullableInt($query['status'] ?? $legacy['status'] ?? null),
            content: self::stringValue($query['content'] ?? $legacy['content'] ?? null),
            date: self::stringValue($query['date'] ?? $query['ntf_dt'] ?? $legacy['ntf_dt'] ?? null),
            pageSize: max(1, min(50, $pageSize)),
        );
    }

    private static function intValue(mixed $value, int $fallback): int
    {
        if (!is_scalar($value)) {
            return $fallback;
        }

        $value = (int) $value;

        return $value > 0 ? $value : $fallback;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private static function stringValue(mixed $value): string
    {
        if ($value === null || (!is_scalar($value) && !$value instanceof \Stringable)) {
            return '';
        }

        return trim((string) $value);
    }
}
