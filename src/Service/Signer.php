<?php

declare(strict_types=1);

namespace Skanva\MagentoOAuth\Service;

use Skanva\MagentoOAuth\DTO\OauthCredentialDTO;
use Skanva\MagentoOAuth\DTO\RequestDTO;

/**
 * Builds the OAuth 1.0a (HMAC-SHA256) Authorization header Magento expects for integration tokens.
 */
class Signer
{
    public function __construct(
        private readonly NonceGeneratorInterface $nonceGenerator = new RandomNonceGenerator(),
    ) {
    }

    /**
     * @return string the full header line: "Authorization: Oauth ..."
     */
    public function sign(
        RequestDTO $request,
        OauthCredentialDTO $credentials,
    ): string {
        $urlParts = parse_url($request->url) ?: [];
        $oauthParams = [
            'oauth_consumer_key' => $credentials->consumerKey,
            'oauth_nonce' => $this->nonceGenerator->nonce(),
            'oauth_signature_method' => 'HMAC-SHA256',
            'oauth_timestamp' => (string) $this->nonceGenerator->timestamp(),
            'oauth_token' => $credentials->accessToken,
        ];
        // Create the base string
        $signingUrl = ($urlParts['scheme'] ?? '').'://'.($urlParts['host'] ?? '').($urlParts['path'] ?? '');
        $paramString = $this->createParamString($urlParts['query'] ?? null, $oauthParams);
        $baseString = strtoupper($request->method).'&'.rawurlencode($signingUrl).'&'.rawurlencode($paramString);
        // Create the signature
        $signatureKey = $credentials->consumerSecret.'&'.$credentials->accessTokenSecret;
        $signature = base64_encode(hash_hmac('sha256', $baseString, $signatureKey, true));

        return $this->createOAuthHeader($oauthParams, $signature);
    }

    /**
     * @param array<string, string> $oauthParams
     */
    private function createParamString(?string $query, array $oauthParams): string
    {
        $params = $oauthParams;
        if (null !== $query && '' !== $query) {
            foreach (explode('&', $query) as $paramToValue) {
                $paramData = explode('=', $paramToValue);
                if (2 === count($paramData)) {
                    $params[rawurldecode($paramData[0])] = rawurldecode($paramData[1]);
                }
            }
        }
        ksort($params);
        $paramString = '';
        foreach ($params as $param => $value) {
            $paramString .= rawurlencode((string) $param).'='.rawurlencode($value).'&';
        }

        return rtrim($paramString, '&');
    }

    /**
     * @param array<string, string> $oauthParams
     */
    private function createOAuthHeader(array $oauthParams, string $signature): string
    {
        $oauthHeader = 'Authorization: Oauth ';
        foreach ($oauthParams as $param => $value) {
            $oauthHeader .= "$param=\"$value\",";
        }

        return $oauthHeader."oauth_signature=\"$signature\"";
    }
}
