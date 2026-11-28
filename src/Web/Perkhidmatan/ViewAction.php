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

final readonly class ViewAction
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

        $query = $request->getQueryParams();
        $icno = $this->cleanQueryValue($query['id'] ?? $query['icno'] ?? '');
        $profile = $icno === '' ? null : $this->profileService->profileForIcno($icno, 'admin');
        $historyPage = (int) ($query['history_page'] ?? 1);

        return $this->viewRenderer
            ->render(__DIR__ . '/view', [
                'profile' => $profile,
                'history' => $icno === '' ? ['rows' => [], 'page' => 1, 'pageCount' => 1, 'total' => 0] : $this->profileService->changeHistory($icno, $historyPage),
            ])
            ->withStatus($profile === null ? 404 : 200);
    }
}
