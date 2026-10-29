<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Http;

use Skanva\MagentoOAuth\DTO\RequestDTO;
use Skanva\MagentoOAuth\Exception\ClientErrorException;
use Skanva\MagentoOAuth\Exception\ServerErrorException;
use Skanva\MagentoOAuth\Exception\TransportException;

interface HttpTransportInterface
{
    /**
     * Sends the request as-is (headers already signed) and returns the raw response body.
     *
     * @throws TransportException   when no HTTP response was received
     * @throws ClientErrorException on HTTP 4xx
     * @throws ServerErrorException on HTTP 5xx
     */
    public function send(RequestDTO $request): string;
}
