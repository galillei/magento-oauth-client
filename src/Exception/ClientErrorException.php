<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Exception;

/**
 * HTTP 4xx — the request itself is wrong (bad signature, missing ACL, unknown entity, ...).
 * Retrying the same request will not help.
 */
class ClientErrorException extends HttpException
{
}
