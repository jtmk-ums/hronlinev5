<?php

declare(strict_types=1);

namespace App\Web\Perkhidmatan;

use App\Shared\LegacySessionReader;
use App\Shared\Perkhidmatan\PerkhidmatanAccessService;
use App\Shared\Perkhidmatan\PerkhidmatanProfileService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class SelfViewAction
{
    use ActionSupport;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private PerkhidmatanAccessService $access,
        private PerkhidmatanProfileService $profileService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->requireSession($request, $this->sessionReader, $this->responseFactory);
        if ($legacySession instanceof ResponseInterface) {
            return $legacySession;
        }

        $icno = $this->access->icnoFromIdentity($legacySession['identityId']);
        $profile = $icno === '' ? null : $this->profileService->profileForIcno($icno, 'user');
        $query = $request->getQueryParams();
        $historyPage = (int) ($query['history_page'] ?? 1);

        return $this->viewRenderer
            ->render(__DIR__ . '/viewuser', [
                'profile' => $profile,
                'history' => $icno === '' ? ['rows' => [], 'page' => 1, 'pageCount' => 1, 'total' => 0] : $this->profileService->changeHistory($icno, $historyPage, true),
            ])
            ->withStatus($profile === null ? 404 : 200);
    }
}
