<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Service;

final class RandomNonceGenerator implements NonceGeneratorInterface
{
    public function nonce(): string
    {
        return base64_encode(random_bytes(32));
    }

    public function timestamp(): int
    {
        return time();
    }
}
