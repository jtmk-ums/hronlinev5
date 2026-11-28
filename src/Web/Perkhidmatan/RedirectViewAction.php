<?php

declare(strict_types=1);

namespace App\Web\Perkhidmatan;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class RedirectViewAction
{
    public function __construct(private ResponseFactoryInterface $responseFactory) {}

    public function __invoke(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', '/perkhidmatan/viewuser');
    }
}
