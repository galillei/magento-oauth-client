<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Tests\Http;

use PHPUnit\Framework\TestCase;
use Skanva\MagentoOAuth\DTO\RequestDTO;
use Skanva\MagentoOAuth\Exception\ClientErrorException;
use Skanva\MagentoOAuth\Exception\ServerErrorException;
use Skanva\MagentoOAuth\Exception\TransportException;
use Skanva\MagentoOAuth\Http\CurlTransport;

/**
 * Runs against PHP's built-in web server (tests/Http/Fixtures/router.php).
 */
final class CurlTransportTest extends TestCase
{
    /** @var resource|null */
    private static $server;
    private static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($socket);
        $address = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        $process = proc_open(
            [PHP_BINARY, '-S', $address, __DIR__.'/Fixtures/router.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        self::$server = $process;
        self::$baseUrl = 'http://'.$address;

        [$host, $port] = explode(':', $address);
        for ($i = 0; $i < 50; ++$i) {
            $connection = @fsockopen($host, (int) $port);
            if (false !== $connection) {
                fclose($connection);

                return;
            }
            usleep(100_000);
        }
        self::fail('Built-in web server did not start on '.$address);
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
    }

    public function testReturnsBodyAndSendsMethodHeadersAndBody(): void
    {
        $body = (new CurlTransport())->send(new RequestDTO(
            self::$baseUrl.'/echo',
            'PUT',
            '{"a":1}',
            ['Authorization: Oauth test'],
        ));

        self::assertSame(
            ['method' => 'PUT', 'authorization' => 'Oauth test', 'body' => '{"a":1}'],
            json_decode($body, true),
        );
    }

    public function testClientErrorStatusThrowsClientErrorException(): void
    {
        try {
            (new CurlTransport())->send(new RequestDTO(self::$baseUrl.'/401'));
            self::fail('ClientErrorException expected');
        } catch (ClientErrorException $e) {
            self::assertSame(401, $e->getStatusCode());
            self::assertSame('{"message":"Unauthorized"}', $e->getResponseBody());
        }
    }

    public function testServerErrorStatusThrowsServerErrorException(): void
    {
        try {
            (new CurlTransport())->send(new RequestDTO(self::$baseUrl.'/503'));
            self::fail('ServerErrorException expected');
        } catch (ServerErrorException $e) {
            self::assertSame(503, $e->getStatusCode());
            self::assertSame('Service Unavailable', $e->getResponseBody());
        }
    }

    public function testTimeoutThrowsTransportException(): void
    {
        $this->expectException(TransportException::class);

        (new CurlTransport(connectTimeout: 1, timeout: 1))->send(new RequestDTO(self::$baseUrl.'/slow'));
    }

    public function testConnectionFailureThrowsTransportException(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessageMatches('/^cURL request to http:\/\/127\.0\.0\.1:1\/ failed: /');

        // Port 1 is reserved (tcpmux) and practically never listening, so the connection is refused immediately.
        (new CurlTransport(connectTimeout: 2, timeout: 2))->send(new RequestDTO('http://127.0.0.1:1/'));
    }

    public function testEmptyUrlIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new CurlTransport())->send(new RequestDTO(''));
    }
}
