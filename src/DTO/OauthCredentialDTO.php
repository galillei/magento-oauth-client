<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\DTO;

/**
 * Magento integration credentials (System > Extensions > Integrations).
 */
class OauthCredentialDTO
{
    public function __construct(
        public string $consumerKey,
        public string $consumerSecret,
        public string $accessToken,
        public string $accessTokenSecret,
    ) {
    }
}
