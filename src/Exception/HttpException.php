<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Exception;

/**
 * Magento answered with an HTTP error status.
 */
abstract class HttpException extends MagentoOAuthException
{
    public function __construct(
        private readonly int $statusCode,
        private readonly string $responseBody,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Magento API responded with HTTP %d: %s', $statusCode, $responseBody),
            $statusCode,
            $previous,
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
