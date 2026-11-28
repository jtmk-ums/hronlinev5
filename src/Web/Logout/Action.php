<?php

declare(strict_types=1);

namespace App\Web\Logout;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class Action
{
    private const YII2_SESSION_NAME = 'hrv4';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $sessionId = $request->getCookieParams()[self::YII2_SESSION_NAME] ?? null;
        if (is_string($sessionId) && $sessionId !== '') {
            $this->destroyLegacySession($sessionId);
        }

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', '/login')
            ->withAddedHeader('Set-Cookie', $this->expiredCookieHeader($request));
    }

    private function destroyLegacySession(string $sessionId): void
    {
        $previousSessionName = session_name();
        $previousSessionId = session_id();
        $previousCacheLimiter = session_cache_limiter();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_name(self::YII2_SESSION_NAME);
        session_id($sessionId);
        session_cache_limiter('');
        session_start();
        $_SESSION = [];
        session_destroy();

        session_id($previousSessionId);
        session_name($previousSessionName);
        session_cache_limiter($previousCacheLimiter);
    }

    private function expiredCookieHeader(ServerRequestInterface $request): string
    {
        $path = $request->getUri()->getPath();
        $basePath = str_starts_with($path, '/hronline') ? '/hronline' : '/';

        return self::YII2_SESSION_NAME . '=deleted; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0; Path=' . $basePath . '; HttpOnly; SameSite=Lax';
    }
}
