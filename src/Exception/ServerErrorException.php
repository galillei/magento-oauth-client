<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Exception;

/**
 * HTTP 5xx — Magento failed to process the request. Usually safe to retry.
 */
class ServerErrorException extends HttpException
{
}
