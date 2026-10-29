<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Service;

/**
 * Source of the per-request oauth_nonce and oauth_timestamp. Swap it out to get deterministic signatures in tests.
 */
interface NonceGeneratorInterface
{
    public function nonce(): string;

    public function timestamp(): int;
}
