<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Tests\Service;

use PHPUnit\Framework\TestCase;
use Skanva\MagentoOAuth\DTO\OauthCredentialDTO;
use Skanva\MagentoOAuth\DTO\RequestDTO;
use Skanva\MagentoOAuth\Service\RandomNonceGenerator;
use Skanva\MagentoOAuth\Service\Signer;

/**
 * The expected headers were produced by the original in-house implementation this package was extracted from
 * (same credentials, nonce and timestamp). They must never change: Magento validates these signatures.
 */
final class SignerTest extends TestCase
{
    private OauthCredentialDTO $credentials;

    protected function setUp(): void
    {
        $this->credentials = new OauthCredentialDTO('consumer-key', 'consumer-secret', 'access-token', 'access-token-secret');
    }

    public function testGetWithEncodedQueryParams(): void
    {
        $request = new RequestDTO(
            'https://magento.example.com/rest/V1/orders?searchCriteria%5BfilterGroups%5D%5B0%5D%5Bfilters%5D%5B0%5D%5Bfield%5D=increment_id&searchCriteria[pageSize]=10&q=a%20b+c'
        );

        self::assertSame(
            'Authorization: Oauth oauth_consumer_key="consumer-key",oauth_nonce="TESTNONCE",oauth_signature_method="HMAC-SHA256",oauth_timestamp="1700000000",oauth_token="access-token",oauth_signature="t6MMxX1UcVdMd/wnesum/WurwbKTrpazF/En3WdjpIU="',
            (new Signer(new FixedNonceGenerator()))->sign($request, $this->credentials),
        );
    }

    public function testPostWithBodyAndHeaders(): void
    {
        $request = new RequestDTO(
            'https://magento.example.com/rest/V1/tickets/123/comments',
            'POST',
            '{"comment":"Hello"}',
            ['Accept: application/json'],
        );

        self::assertSame(
            'Authorization: Oauth oauth_consumer_key="consumer-key",oauth_nonce="TESTNONCE",oauth_signature_method="HMAC-SHA256",oauth_timestamp="1700000000",oauth_token="access-token",oauth_signature="SabXLiS1ibATkcpUeyrMAy8erg/qCmt7YA3swWIGlMc="',
            (new Signer(new FixedNonceGenerator()))->sign($request, $this->credentials),
        );
    }

    public function testBareUrlWithLowercaseMethod(): void
    {
        $request = new RequestDTO('https://magento.example.com', 'get');

        self::assertSame(
            'Authorization: Oauth oauth_consumer_key="consumer-key",oauth_nonce="TESTNONCE",oauth_signature_method="HMAC-SHA256",oauth_timestamp="1700000000",oauth_token="access-token",oauth_signature="wQMA2P9CznwI3VEP3gZCsAAPsLcpgATtdK62b0S5mzY="',
            (new Signer(new FixedNonceGenerator()))->sign($request, $this->credentials),
        );
    }

    public function testBase64NonceIsEncodedInBaseString(): void
    {
        $request = new RequestDTO(
            'https://magento.example.com/rest/V1/orders?searchCriteria%5BfilterGroups%5D%5B0%5D%5Bfilters%5D%5B0%5D%5Bfield%5D=increment_id&searchCriteria[pageSize]=10&q=a%20b+c'
        );

        self::assertSame(
            'Authorization: Oauth oauth_consumer_key="consumer-key",oauth_nonce="q1+w2/e3==",oauth_signature_method="HMAC-SHA256",oauth_timestamp="1700000000",oauth_token="access-token",oauth_signature="4XtmHDPlA9z0Xd6wZFGQxgk/cACg0TccGmQyd/P8+mE="',
            (new Signer(new FixedNonceGenerator('q1+w2/e3==')))->sign($request, $this->credentials),
        );
    }

    public function testHexNonceMatchesCurrentImplementation(): void
    {
        $request = new RequestDTO(
            'https://magento.example.com/rest/V1/orders?searchCriteria%5BfilterGroups%5D%5B0%5D%5Bfilters%5D%5B0%5D%5Bfield%5D=increment_id&searchCriteria[pageSize]=10&q=a%20b+c'
        );

        self::assertSame(
            'Authorization: Oauth oauth_consumer_key="consumer-key",oauth_nonce="0123456789abcdef0123456789abcdef",oauth_signature_method="HMAC-SHA256",oauth_timestamp="1700000000",oauth_token="access-token",oauth_signature="uBHu100KHmNu1wbCNv7hhNOu6LdNgLl1HyCAgJD/a9E="',
            (new Signer(new FixedNonceGenerator('0123456789abcdef0123456789abcdef')))->sign($request, $this->credentials),
        );
    }

    public function testDoesNotModifyRequest(): void
    {
        $request = new RequestDTO('https://magento.example.com/rest/V1/orders', 'GET', null, ['Accept: application/json']);
        $before = clone $request;

        (new Signer(new FixedNonceGenerator()))->sign($request, $this->credentials);

        self::assertEquals($before, $request);
    }

    public function testDefaultGeneratorProducesFreshHexNonces(): void
    {
        $generator = new RandomNonceGenerator();

        self::assertNotSame($generator->nonce(), $generator->nonce());
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $generator->nonce());
        self::assertEqualsWithDelta(time(), $generator->timestamp(), 5);
    }
}
