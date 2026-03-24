<?php

declare(strict_types=1);

namespace Pandawa\ScoutAlgolia;

use Algolia\AlgoliaSearch\Http\HttpClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * HTTP client decorator that rewrites request URIs to point to a proxy server.
 *
 * Used for algolia/algoliasearch-client-php v3, where the ApiWrapper hardcodes
 * HTTPS scheme and provides no mechanism to override host/scheme/port.
 * This decorator intercepts the fully-constructed PSR-7 request and rewrites
 * the URI before delegating to the real HTTP client.
 */
class ProxyHttpClient implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $inner,
        private string $scheme,
        private string $host,
        private int $port,
    ) {
    }

    public function sendRequest(
        RequestInterface $request,
        $timeout,
        $connectTimeout,
    ) {
        $uri = $request->getUri()
            ->withScheme($this->scheme)
            ->withHost($this->host)
            ->withPort($this->port);

        return $this->inner->sendRequest(
            $request->withUri($uri),
            $timeout,
            $connectTimeout,
        );
    }
}
