<?php

declare(strict_types=1);

namespace App\Web\Perkhidmatan\Sections\JenisGaji;

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

final readonly class DetailAction
{
    use ActionSupport;

    private const SLUG = 'jenis-gaji';

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
        $id = $this->cleanQueryValue($query['id'] ?? '');
        $record = $this->sections->recordForId(self::SLUG, $id);
        if ($record === null) {
            return $this->accessDenied->render(backUrl: '/perkhidmatan/index');
        }

        $currentIcno = $this->access->icnoFromIdentity($legacySession['identityId']);
        $targetIcno = (string) ($record['staff']['icno'] ?? '');
        if ($targetIcno === '' || !$this->access->canView($currentIcno, $targetIcno)) {
            return $this->accessDenied->render(backUrl: '/perkhidmatan/index');
        }

        $adminMode = $this->access->hasAdminAccess($currentIcno) && $currentIcno !== $targetIcno;

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', [
                'detail' => $record,
                'backUrl' => '/perkhidmatan/' . self::SLUG . '/view?icno=' . rawurlencode($targetIcno),
                'management' => $this->crud->management(self::SLUG, $targetIcno, $adminMode),
                'crud' => $this->crud,
            ])
            ->withStatus(200);
    }
}
