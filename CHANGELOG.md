# Changelog

All notable changes to this project are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-10-29

First public release, extracted from an in-house Magento integration.

### Added
- `Signer` — OAuth 1.0a HMAC-SHA256 `Authorization` header for Magento integration tokens.
- `ApiClient` — signs and sends requests, returns the decoded JSON body.
- `CurlTransport` with connect (10 s) and total (30 s) timeouts, behind `HttpTransportInterface`.
- `NonceGeneratorInterface` / `RandomNonceGenerator` for deterministic signatures in tests.
- Exception hierarchy under `MagentoOAuthException`: `TransportException`, `ClientErrorException`,
  `ServerErrorException`.

### Changed (compared to the in-house version)
- A failed cURL call throws `TransportException` instead of causing a `TypeError`.
- HTTP 4xx / 5xx responses throw `ClientErrorException` / `ServerErrorException` instead of being decoded.
- `ApiClient::send()` no longer appends headers to the caller's `RequestDTO`.
- Signatures are byte-for-byte identical to the in-house version.
