<?php

declare(strict_types=1);

namespace App\Web\Notification;

use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\NotificationCriteria;
use App\Shared\NotificationService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class SearchAction
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

        $criteria = NotificationCriteria::fromQuery($request->getQueryParams());

        return $this->viewRenderer->render(__DIR__ . '/search', [
            'title' => 'Carian Notifikasi',
            'notifications' => $this->notifications->searchForStaff($icno, $criteria),
            'criteria' => $criteria,
        ]);
    }
}
