<?php

declare(strict_types=1);

namespace App\Web\Notification;

use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait ActionSupport
{
    private function icno(ServerRequestInterface $request, LegacySessionReader $sessionReader, EmployeeRepository $employees): ?string
    {
        $legacySession = $sessionReader->read($request);
        if ($legacySession === null) {
            return null;
        }

        $icno = $employees->icnoFromIdentity($legacySession['identityId']);

        return $icno === '' ? null : $icno;
    }

    private function redirect(ResponseFactoryInterface $responseFactory, string $url): ResponseInterface
    {
        return $responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }

    /**
     * @return list<int>
     */
    private function idsFromRequest(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $ids = [];

        if (is_array($body) && isset($body['ids']) && is_array($body['ids'])) {
            foreach ($body['ids'] as $id) {
                $ids[] = (int) $id;
            }
        }

        if (is_array($body) && isset($body['id']) && is_scalar($body['id'])) {
            $ids[] = (int) $body['id'];
        }

        if (isset($query['id'])) {
            $ids[] = (int) $query['id'];
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    }

    private function returnUrl(ServerRequestInterface $request): string
    {
        $body = $request->getParsedBody();
        if (is_array($body) && isset($body['returnUrl']) && is_scalar($body['returnUrl'])) {
            $url = trim((string) $body['returnUrl']);
            if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
                return $url;
            }
        }

        $referer = $request->getHeaderLine('Referer');
        if ($referer !== '') {
            $path = parse_url($referer, PHP_URL_PATH);
            $query = parse_url($referer, PHP_URL_QUERY);
            if (is_string($path) && $path !== '') {
                return $path . (is_string($query) && $query !== '' ? '?' . $query : '');
            }
        }

        return '/site/notifikasi';
    }

    private function queryInt(ServerRequestInterface $request, string $name, int $fallback = 0): int
    {
        $query = $request->getQueryParams();
        if (!isset($query[$name]) || !is_scalar($query[$name])) {
            return $fallback;
        }

        return (int) $query[$name];
    }
}
