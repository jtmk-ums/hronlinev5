<?php

declare(strict_types=1);

namespace App\Web\Perkhidmatan;

use App\Shared\LegacySessionReader;
use App\Shared\Perkhidmatan\PerkhidmatanAccessService;
use App\Web\Shared\AccessDeniedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait ActionSupport
{
    private function requireSession(ServerRequestInterface $request, LegacySessionReader $sessionReader, ResponseFactoryInterface $responseFactory): array|ResponseInterface
    {
        $legacySession = $sessionReader->read($request);
        if ($legacySession === null) {
            return $responseFactory->createResponse(302)->withHeader('Location', '/login');
        }

        return $legacySession;
    }

    /**
     * @param array{identityId: string, authKey: string, expiresAt: int} $legacySession
     */
    private function requireAdmin(array $legacySession, PerkhidmatanAccessService $access, AccessDeniedResponse $accessDenied): string|ResponseInterface
    {
        $currentIcno = $access->icnoFromIdentity($legacySession['identityId']);
        if ($currentIcno === '' || !$access->hasAdminAccess($currentIcno)) {
            return $accessDenied->render(backUrl: '/menu');
        }

        return $currentIcno;
    }

    private function cleanQueryValue(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return trim(strtok($value, '?&') ?: $value);
    }
}
