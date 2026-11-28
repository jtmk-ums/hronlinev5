<?php

declare(strict_types=1);

namespace App\Web\Perkhidmatan\Sections\BebanPerkhidmatan;

use App\Shared\LegacySessionReader;
use App\Shared\Perkhidmatan\PerkhidmatanAccessService;
use App\Shared\Perkhidmatan\PerkhidmatanCrudService;
use App\Shared\Perkhidmatan\PerkhidmatanSectionService;
use App\Web\Perkhidmatan\ActionSupport;
use App\Web\Shared\AccessDeniedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    use ActionSupport;

    private const SLUG = 'beban-perkhidmatan';

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private PerkhidmatanAccessService $access,
        private AccessDeniedResponse $accessDenied,
        private PerkhidmatanSectionService $sections,
        private PerkhidmatanCrudService $crud,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $legacySession = $this->requireSession($request, $this->sessionReader, $this->responseFactory);
        if ($legacySession instanceof ResponseInterface) {
            return $legacySession;
        }

        $query = $request->getQueryParams();
        $path = $request->getUri()->getPath();
        $selfMode = str_ends_with($path, '/viewuser');
        $currentIcno = $this->access->icnoFromIdentity($legacySession['identityId']);
        $targetIcno = $selfMode ? $currentIcno : $this->cleanQueryValue($query['icno'] ?? $query['id'] ?? '');

        if ($targetIcno === '' || !$this->access->canView($currentIcno, $targetIcno)) {
            return $this->accessDenied->render(backUrl: $selfMode ? '/perkhidmatan/viewuser' : '/perkhidmatan/index');
        }

        $management = $this->crud->management(self::SLUG, $targetIcno, !$selfMode && $this->access->hasAdminAccess($currentIcno));
        $section = $this->sections->sectionForIcno(self::SLUG, $targetIcno);

        return $this->viewRenderer
            ->render(__DIR__ . '/view', [
                'section' => $section,
                'selfMode' => $selfMode,
                'backUrl' => $selfMode ? '/perkhidmatan/viewuser' : '/perkhidmatan/view?id=' . rawurlencode($targetIcno),
                'management' => $management,
                'crud' => $this->crud,
            ])
            ->withStatus($section === null ? 404 : 200);
    }
}
