<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Service;

final class RandomNonceGenerator implements NonceGeneratorInterface
{
    public function nonce(): string
    {
        // 32 hex characters: Magento stores the nonce in oauth_nonce.nonce, a varchar(32),
        // and truncates anything longer on insert while still looking it up by the full
        // value sent. A base64 nonce is 44 characters before the header even percent-encodes
        // its "+" and "/", so the stored and the looked-up value could never match and a
        // replayed request failed with the opaque "An error occurred validating the nonce"
        // instead of being reported as a reused nonce. Hex is url-safe and fits exactly.
        return bin2hex(random_bytes(16));
    }

    public function timestamp(): int
    {
        return time();
    }
}
