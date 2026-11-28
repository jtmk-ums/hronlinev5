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

final readonly class IndexAction
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

        $admin = $this->requireAdmin($legacySession, $this->access, $this->accessDenied);
        if ($admin instanceof ResponseInterface) {
            return $admin;
        }

        return $this->viewRenderer->render(__DIR__ . '/index', [
            'grid' => $this->profileService->searchStaff($request->getQueryParams()),
        ]);
    }
}
