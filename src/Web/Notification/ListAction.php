<?php

declare(strict_types=1);

namespace App\Web\Notification;

use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\NotificationService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ListAction
{
    use ActionSupport;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private LegacySessionReader $sessionReader,
        private EmployeeRepository $employees,
        private NotificationService $notifications,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $icno = $this->icno($request, $this->sessionReader, $this->employees);
        if ($icno === null) {
            return $this->redirect($this->responseFactory, '/login');
        }

        $page = $this->queryInt($request, 'page', 1);
        $selectedId = $this->queryInt($request, 'id');

        return $this->viewRenderer->render(__DIR__ . '/inbox', [
            'title' => 'Pusat Notifikasi',
            'notifications' => $this->notifications->unreadInbox($icno, $page, $selectedId > 0 ? $selectedId : null),
        ]);
    }
}
