<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Exception;

/**
 * The request never got an HTTP response (DNS, connection refused, timeout, TLS, ...).
 */
class TransportException extends MagentoOAuthException
{
}
