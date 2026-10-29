<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Tests\Service;

use Skanva\MagentoOAuth\Service\NonceGeneratorInterface;

final class FixedNonceGenerator implements NonceGeneratorInterface
{
    public function __construct(
        private readonly string $nonce = 'TESTNONCE',
        private readonly int $timestamp = 1700000000,
    ) {
    }

    public function nonce(): string
    {
        return $this->nonce;
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }
}
