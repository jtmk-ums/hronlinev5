<?php

declare(strict_types=1);

namespace App\Web\Perkhidmatan\Sections\PihakBerkuasa;

use App\Shared\LegacySessionReader;
use App\Shared\Perkhidmatan\PerkhidmatanAccessService;
use App\Shared\Perkhidmatan\PerkhidmatanCrudService;
use App\Web\Perkhidmatan\ActionSupport;
use App\Web\Shared\AccessDeniedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class FormAction
{
    use ActionSupport;

    private const SLUG = 'pihak-berkuasa';

    public function __construct(
        private WebViewRenderer $viewRenderer,
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
        $icno = $id === '' ? $this->cleanQueryValue($query['icno'] ?? '') : '';
        if ($id !== '') {
            $form = $this->crud->form(self::SLUG, '', $id);
            $icno = is_array($form) ? (string) ($form['values']['ICNO'] ?? '') : '';
        } else {
            $form = $this->crud->form(self::SLUG, $icno);
        }

        if ($form === null || $icno === '') {
            return $this->accessDenied->render(backUrl: '/perkhidmatan/index');
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $result = $this->crud->save(self::SLUG, $icno, $id === '' ? null : $id, $body, $this->context($request, $actorIcno));
            if ($result['success']) {
                return $this->redirect('/perkhidmatan/' . self::SLUG . '/view?icno=' . rawurlencode($icno) . ($id === '' ? '&created=1' : '&updated=1'));
            }

            $form = $result['form'] ?? $form;
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/form', ['form' => $form])
            ->withStatus(200);
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
