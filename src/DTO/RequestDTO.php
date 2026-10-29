<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\DTO;

class RequestDTO
{
    /**
     * @param array<int, string> $headers raw header lines, e.g. "Accept: application/json"
     */
    public function __construct(
        public string $url,
        public string $method = 'GET',
        public ?string $body = null,
        public array $headers = [],
    ) {
    }
}
