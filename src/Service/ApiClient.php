<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Service;

use Skanva\MagentoOAuth\DTO\OauthCredentialDTO;
use Skanva\MagentoOAuth\DTO\RequestDTO;
use Skanva\MagentoOAuth\Exception\ClientErrorException;
use Skanva\MagentoOAuth\Exception\ServerErrorException;
use Skanva\MagentoOAuth\Exception\TransportException;
use Skanva\MagentoOAuth\Http\CurlTransport;
use Skanva\MagentoOAuth\Http\HttpTransportInterface;

class ApiClient
{
    public function __construct(
        private readonly OauthCredentialDTO $credentials,
        private readonly Signer $signer = new Signer(),
        private readonly HttpTransportInterface $transport = new CurlTransport(),
    ) {
    }

    /**
     * Signs and sends the request. The given RequestDTO is not modified.
     *
     * @return array<array-key, mixed> the decoded JSON body
     *
     * @throws \InvalidArgumentException when the URL is empty
     * @throws TransportException        when no HTTP response was received
     * @throws ClientErrorException      on HTTP 4xx
     * @throws ServerErrorException      on HTTP 5xx
     * @throws \Exception                when the response body is {"error": true, "message": "..."}
     */
    public function send(RequestDTO $request): array
    {
        if ('' === $request->url) {
            throw new \InvalidArgumentException('Request URL cannot be empty.');
        }

        $headers = $request->headers;
        $headers[] = $this->signer->sign($request, $this->credentials);
        $headers[] = 'Content-Type: application/json';

        $response = $this->transport->send(
            new RequestDTO($request->url, $request->method, $request->body, $headers)
        );
        $response = \json_decode($response, true);
        if (isset($response['error']) && true === $response['error']) {
            throw new \Exception($response['message']);
        }

        return $response;
    }
}
