<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Http;

use Skanva\MagentoOAuth\DTO\RequestDTO;
use Skanva\MagentoOAuth\Exception\ClientErrorException;
use Skanva\MagentoOAuth\Exception\ServerErrorException;
use Skanva\MagentoOAuth\Exception\TransportException;

final class CurlTransport implements HttpTransportInterface
{
    /**
     * @param int $connectTimeout seconds to establish the connection, 0 = no limit
     * @param int $timeout        seconds for the whole request, 0 = no limit
     */
    public function __construct(
        private readonly int $connectTimeout = 10,
        private readonly int $timeout = 30,
    ) {
    }

    public function send(RequestDTO $request): string
    {
        if ('' === $request->url) {
            throw new \InvalidArgumentException('Request URL cannot be empty.');
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $request->url,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $request->headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => '' !== $request->method ? $request->method : null,
            CURLOPT_POSTFIELDS => $request->body ?? '',
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $body = curl_exec($ch);
        if (!is_string($body)) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            throw new TransportException(sprintf('cURL request to %s failed: %s', $request->url, $error), $errno);
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($statusCode >= 500) {
            throw new ServerErrorException($statusCode, $body);
        }
        if ($statusCode >= 400) {
            throw new ClientErrorException($statusCode, $body);
        }

        return $body;
    }
}
