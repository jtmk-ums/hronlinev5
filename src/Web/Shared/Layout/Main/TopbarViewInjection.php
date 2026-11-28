<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Main;

use App\Shared\TopbarService;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Yii\View\Renderer\LayoutParametersInjectionInterface;

final readonly class TopbarViewInjection implements LayoutParametersInjectionInterface
{
    public function __construct(
        private RequestProviderInterface $requestProvider,
        private TopbarService $topbar,
    ) {}

    public function getLayoutParameters(): array
    {
        try {
            return [
                'topbar' => $this->topbar->context($this->requestProvider->get()),
            ];
        } catch (\Throwable) {
            return [
                'topbar' => [
                    'authenticated' => false,
                    'notifications' => [],
                    'notificationCount' => 0,
                ],
            ];
        }
    }
}
