<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Tests\Service;

use Skanva\MagentoOAuth\DTO\RequestDTO;
use Skanva\MagentoOAuth\Http\HttpTransportInterface;

final class FakeTransport implements HttpTransportInterface
{
    public ?RequestDTO $lastRequest = null;

    public function __construct(
        private readonly string|\Throwable $result,
    ) {
    }

    public function send(RequestDTO $request): string
    {
        $this->lastRequest = $request;
        if ($this->result instanceof \Throwable) {
            throw $this->result;
        }

        return $this->result;
    }
}
