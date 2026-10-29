# Magento OAuth Client

A small, dependency-free PHP client for the Magento 2 REST API that signs every request with
OAuth 1.0a (HMAC-SHA256) using the credentials of a Magento **Integration**.

- No framework dependency — plain PHP 8.1+, `ext-curl`, `ext-json`
- Typed exceptions for transport errors, HTTP 4xx / 5xx and Magento error payloads
- Pluggable HTTP transport and nonce generator, so signing and error handling are fully testable

## Installation

```bash
composer require galillei/magento-oauth-client
```

## Magento setup

In the Magento admin go to **System → Extensions → Integrations**, create an integration, grant it
the API resources you need and activate it. Magento then shows four values — these are the
constructor arguments of `OauthCredentialDTO`:

| Magento label | `OauthCredentialDTO` argument |
|---|---|
| Consumer Key | `consumerKey` |
| Consumer Secret | `consumerSecret` |
| Access Token | `accessToken` |
| Access Token Secret | `accessTokenSecret` |

## Usage

```php
use Skanva\MagentoOAuth\DTO\OauthCredentialDTO;
use Skanva\MagentoOAuth\DTO\RequestDTO;
use Skanva\MagentoOAuth\Service\ApiClient;

$client = new ApiClient(new OauthCredentialDTO(
    consumerKey: 'your-consumer-key',
    consumerSecret: 'your-consumer-secret',
    accessToken: 'your-access-token',
    accessTokenSecret: 'your-access-token-secret',
));

// GET — query parameters are part of the signature
$order = $client->send(new RequestDTO('https://shop.example.com/rest/V1/orders/42'));

// POST with a JSON body and extra headers
$result = $client->send(new RequestDTO(
    url: 'https://shop.example.com/rest/V1/orders/42/comments',
    method: 'POST',
    body: json_encode(['statusHistory' => ['comment' => 'Shipped', 'is_customer_notified' => 0]]),
    headers: ['Accept: application/json'],
));
```

`send()` returns the decoded JSON body as an `array`. Responses must be a JSON object or list — an empty
body or a bare scalar such as `true` causes a `TypeError`. The `Authorization`
and `Content-Type: application/json` headers are added automatically; the `RequestDTO` you pass in
is never modified, so it can be reused.

### Only signing

If you already have an HTTP client, use the signer on its own. It returns the complete header line:

```php
use Skanva\MagentoOAuth\Service\Signer;

$header = (new Signer())->sign($request, $credentials);
// "Authorization: Oauth oauth_consumer_key="...",oauth_nonce="...",...,oauth_signature="...""
```

## Error handling

The transport exceptions extend `Skanva\MagentoOAuth\Exception\MagentoOAuthException` (a `RuntimeException`).
An empty URL throws `\InvalidArgumentException`.

| Exception | When | Retry? |
|---|---|---|
| `TransportException` | No HTTP response: DNS, connection refused, TLS, timeout | Yes |
| `ServerErrorException` | HTTP 5xx | Yes |
| `ClientErrorException` | HTTP 4xx (bad signature, missing ACL, unknown entity, …) | No |
| `\Exception` | HTTP 2xx with a body of `{"error": true, "message": "…"}` | No |

`ClientErrorException` and `ServerErrorException` expose `getStatusCode()` and `getResponseBody()`.

```php
use Skanva\MagentoOAuth\Exception\ClientErrorException;
use Skanva\MagentoOAuth\Exception\ServerErrorException;
use Skanva\MagentoOAuth\Exception\TransportException;

try {
    $client->send($request);
} catch (ServerErrorException|TransportException $e) {
    // temporary problem — retry later
} catch (ClientErrorException $e) {
    // permanent problem — log $e->getStatusCode() and $e->getResponseBody()
}
```

## Timeouts and custom transports

The default `CurlTransport` uses a 10 s connect timeout and a 30 s total timeout. Pass your own
instance to change them:

```php
use Skanva\MagentoOAuth\Http\CurlTransport;
use Skanva\MagentoOAuth\Service\Signer;

$client = new ApiClient($credentials, new Signer(), new CurlTransport(connectTimeout: 5, timeout: 120));
```

To route requests through another HTTP library, implement `Skanva\MagentoOAuth\Http\HttpTransportInterface`.
A transport sends the already-signed request and returns the raw response body. It must throw
`TransportException` when no response was received, and `ClientErrorException` / `ServerErrorException`
for HTTP 4xx / 5xx.

## Symfony

The package has no bundle; register the services yourself:

```yaml
# config/services.yaml
services:
    Skanva\MagentoOAuth\DTO\OauthCredentialDTO:
        arguments:
            $consumerKey: '%env(MAGENTO_CONSUMER_KEY)%'
            $consumerSecret: '%env(MAGENTO_CONSUMER_SECRET)%'
            $accessToken: '%env(MAGENTO_ACCESS_TOKEN)%'
            $accessTokenSecret: '%env(MAGENTO_ACCESS_TOKEN_SECRET)%'

    Skanva\MagentoOAuth\Service\Signer: ~
    Skanva\MagentoOAuth\Http\HttpTransportInterface:
        class: Skanva\MagentoOAuth\Http\CurlTransport

    Skanva\MagentoOAuth\Service\ApiClient:
        autowire: true
```

## Testing your code

Inject a fixed `NonceGeneratorInterface` to get deterministic signatures, and a fake
`HttpTransportInterface` to avoid real HTTP calls:

```php
use Skanva\MagentoOAuth\Service\NonceGeneratorInterface;

final class FixedNonceGenerator implements NonceGeneratorInterface
{
    public function nonce(): string { return 'TESTNONCE'; }
    public function timestamp(): int { return 1700000000; }
}

$signer = new Signer(new FixedNonceGenerator());
```

## Signature details and limitations

The signature follows what Magento's integration authentication accepts and is kept byte-for-byte
compatible with the implementation this package was extracted from:

- The base string uses `scheme://host/path` — a **non-default port is not included**, so URLs like
  `https://shop.example.com:8443/...` will not validate.
- Query parameters are merged with the OAuth parameters and sorted by name; repeated keys
  (`a[]=1&a[]=2`) keep only the last value, and parameters without `=` are ignored.
- The request body is not part of the signature.

## Development

```bash
composer install
composer test          # PHPUnit
composer phpstan       # PHPStan, level 8
composer cs-check      # PHP-CS-Fixer (@Symfony), dry run — `composer cs-fix` to apply
composer code-quality  # all of the above
```

## License

MIT — see [LICENSE](LICENSE).
