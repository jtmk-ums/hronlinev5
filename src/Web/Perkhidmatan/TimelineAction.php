<?php

declare(strict_types=1);

namespace App\Web\Perkhidmatan;

use App\Shared\LegacySessionReader;
use App\Shared\Perkhidmatan\PerkhidmatanAccessService;
use App\Shared\Perkhidmatan\PerkhidmatanProfileService;
use App\Web\Shared\AccessDeniedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class TimelineAction
{
    use ActionSupport;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private PerkhidmatanAccessService $access,
        private AccessDeniedResponse $accessDenied,
        private PerkhidmatanProfileService $profileService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->requireSession($request, $this->sessionReader, $this->responseFactory);
        if ($legacySession instanceof ResponseInterface) {
            return $legacySession;
        }

        $currentIcno = $this->access->icnoFromIdentity($legacySession['identityId']);
        $query = $request->getQueryParams();
        $targetIcno = $this->cleanQueryValue($query['id'] ?? $query['icno'] ?? $currentIcno);
        if (!$this->access->canView($currentIcno, $targetIcno)) {
            return $this->accessDenied->render(backUrl: '/perkhidmatan/viewuser');
        }

        return $this->viewRenderer->render(__DIR__ . '/timeline', [
            'timeline' => $this->profileService->timeline($targetIcno),
        ]);
    }
}
