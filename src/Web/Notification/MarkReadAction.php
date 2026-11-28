<?php

declare(strict_types=1);

namespace App\Web\Notification;

use App\Shared\EmployeeRepository;
use App\Shared\LegacySessionReader;
use App\Shared\NotificationService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class MarkReadAction
{
    use ActionSupport;

    public function __construct(
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

        $this->notifications->markRead($icno, $this->idsFromRequest($request));

        return $this->redirect($this->responseFactory, $this->returnUrl($request));
    }
}
