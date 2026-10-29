<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Tests\Service;

use PHPUnit\Framework\TestCase;
use Skanva\MagentoOAuth\DTO\OauthCredentialDTO;
use Skanva\MagentoOAuth\DTO\RequestDTO;
use Skanva\MagentoOAuth\Exception\ClientErrorException;
use Skanva\MagentoOAuth\Exception\TransportException;
use Skanva\MagentoOAuth\Service\ApiClient;
use Skanva\MagentoOAuth\Service\Signer;

final class ApiClientTest extends TestCase
{
    private const SIGNATURE_HEADER = 'Authorization: Oauth oauth_consumer_key="consumer-key",oauth_nonce="TESTNONCE",oauth_signature_method="HMAC-SHA256",oauth_timestamp="1700000000",oauth_token="access-token",oauth_signature="SabXLiS1ibATkcpUeyrMAy8erg/qCmt7YA3swWIGlMc="';

    public function testSendsSignedRequestAndDecodesResponse(): void
    {
        $transport = new FakeTransport('{"success":true,"items":[1,2]}');

        $result = $this->client($transport)->send($this->request());

        self::assertSame(['success' => true, 'items' => [1, 2]], $result);
        self::assertNotNull($transport->lastRequest);
        self::assertSame('https://magento.example.com/rest/V1/tickets/123/comments', $transport->lastRequest->url);
        self::assertSame('POST', $transport->lastRequest->method);
        self::assertSame('{"comment":"Hello"}', $transport->lastRequest->body);
        self::assertSame(
            ['Accept: application/json', self::SIGNATURE_HEADER, 'Content-Type: application/json'],
            $transport->lastRequest->headers,
        );
    }

    public function testDoesNotModifyCallersRequest(): void
    {
        $request = $this->request();

        $this->client(new FakeTransport('{}'))->send($request);
        $this->client(new FakeTransport('{}'))->send($request);

        self::assertSame(['Accept: application/json'], $request->headers);
    }

    public function testErrorFlagInBodyThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ticket not found');

        $this->client(new FakeTransport('{"error":true,"message":"Ticket not found"}'))
            ->send($this->request());
    }

    public function testTransportFailureIsPropagated(): void
    {
        $this->expectException(TransportException::class);

        $this->client(new FakeTransport(new TransportException('Connection refused')))->send($this->request());
    }

    public function testHttpErrorFromTransportIsPropagated(): void
    {
        $this->expectException(ClientErrorException::class);

        $this->client(new FakeTransport(new ClientErrorException(401, '{"message":"Unauthorized"}')))->send($this->request());
    }

    public function testEmptyUrlIsRejectedBeforeSending(): void
    {
        $transport = new FakeTransport('{}');

        try {
            $this->client($transport)->send(new RequestDTO(''));
            self::fail('InvalidArgumentException expected');
        } catch (\InvalidArgumentException) {
            self::assertNull($transport->lastRequest);
        }
    }

    private function client(FakeTransport $transport): ApiClient
    {
        return new ApiClient(
            new OauthCredentialDTO('consumer-key', 'consumer-secret', 'access-token', 'access-token-secret'),
            new Signer(new FixedNonceGenerator()),
            $transport,
        );
    }

    private function request(): RequestDTO
    {
        return new RequestDTO(
            'https://magento.example.com/rest/V1/tickets/123/comments',
            'POST',
            '{"comment":"Hello"}',
            ['Accept: application/json'],
        );
    }
}
