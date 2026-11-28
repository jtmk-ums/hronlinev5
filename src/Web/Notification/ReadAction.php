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

final readonly class ReadAction
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

        $id = $this->queryInt($request, 'id');
        $notification = $id > 0 ? $this->notifications->readForStaff($icno, $id) : null;

        return $this->viewRenderer
            ->render(__DIR__ . '/detail', [
                'title' => 'Notifikasi',
                'notification' => $notification,
                'backUrl' => '/site/notifikasi',
                'modeLabel' => 'Inbox',
            ])
            ->withStatus($notification === null ? 404 : 200);
    }
}
