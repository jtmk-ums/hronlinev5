<?php

declare(strict_types=1);

namespace App\Web\Perkhidmatan\Sections\PergerakanGaji;

use App\Shared\LegacySessionReader;
use App\Shared\Perkhidmatan\PerkhidmatanAccessService;
use App\Shared\Perkhidmatan\PerkhidmatanCrudService;
use App\Web\Perkhidmatan\ActionSupport;
use App\Web\Shared\AccessDeniedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteAction
{
    use ActionSupport;

    private const SLUG = 'pergerakan-gaji';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private PerkhidmatanAccessService $access,
        private AccessDeniedResponse $accessDenied,
        private PerkhidmatanCrudService $crud,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->requireSession($request, $this->sessionReader, $this->responseFactory);
        if ($legacySession instanceof ResponseInterface) {
            return $legacySession;
        }

        $actorIcno = $this->access->icnoFromIdentity($legacySession['identityId']);
        if (!$this->access->hasAdminAccess($actorIcno)) {
            return $this->accessDenied->render(backUrl: '/perkhidmatan/index');
        }

        $query = $request->getQueryParams();
        $id = $this->cleanQueryValue($query['id'] ?? '');
        $icno = $this->crud->delete(self::SLUG, $id, $this->context($request, $actorIcno));
        $backUrl = $icno !== '' ? '/perkhidmatan/' . self::SLUG . '/view?icno=' . rawurlencode($icno) . '&deleted=1' : '/perkhidmatan/index';

        return $this->redirect($backUrl);
    }

    /**
     * @return array<string, string>
     */
    private function context(ServerRequestInterface $request, string $actorIcno): array
    {
        $ip = $this->clientIp($request);

        return [
            'ip' => $ip,
            'computer' => $ip,
            'computerUser' => $actorIcno,
            'actorIcno' => $actorIcno,
        ];
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        foreach (['HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $value = $server[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim(explode(',', $value)[0]);
            }
        }

        return 'UNKNOWN';
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
