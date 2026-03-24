<?php

declare(strict_types=1);

namespace Pandawa\ScoutAlgolia;

use Algolia\AlgoliaSearch\Algolia;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;

class AlgoliaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $url = config('scout.algolia.url');

        if (empty($url)) {
            return;
        }

        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? 'localhost';
        $port = (int) ($parsed['port'] ?? ($scheme === 'https' ? 443 : 80));

        // Use booted() to ensure this runs AFTER scout-extended's service provider.
        // Manager::customCreators is last-write-wins, so our override always takes precedence.
        $this->app->booted(function () use ($scheme, $host, $port) {
            $isV4 = version_compare(Algolia::VERSION, '4.0.0', '>=');

            // For v3: install proxy decorator globally BEFORE any SearchClient can be resolved.
            // scout-extended resolves SearchClient from the container via createAlgoliaDriver(),
            // bypassing our custom engine factory. Commands like scout:reimport, UpdateJob, and
            // DeleteJob all resolve SearchClient independently. The proxy must be active globally
            // so every SearchClient instance routes through the proxy.
            if (! $isV4) {
                $inner = Algolia::getHttpClient();
                Algolia::setHttpClient(new ProxyHttpClient($inner, $scheme, $host, $port));
            }

            $this->app->make(EngineManager::class)->extend('algolia', function () use ($scheme, $host, $port, $isV4) {
                return $isV4
                    ? $this->buildV4Engine($scheme, $host, $port)
                    : $this->buildV3Engine();
            });
        });
    }

    /**
     * Build engine for algolia/algoliasearch-client-php ^4.0.
     * Uses the native setFullHosts() API to redirect traffic to the proxy.
     */
    private function buildV4Engine(string $scheme, string $host, int $port): object
    {
        $config = \Algolia\AlgoliaSearch\Configuration\SearchConfig::create(
            config('scout.algolia.id'),
            config('scout.algolia.secret'),
        );

        $config->setFullHosts(["{$scheme}:{$host}:{$port}"]);

        $client = \Algolia\AlgoliaSearch\Api\SearchClient::createWithConfig($config);

        return new \Laravel\Scout\Engines\Algolia4Engine(
            $client,
            config('scout.soft_delete'),
        );
    }

    /**
     * Build engine for algolia/algoliasearch-client-php ^3.x.
     * ProxyHttpClient is already installed globally in the booted() callback.
     */
    private function buildV3Engine(): object
    {
        $config = \Algolia\AlgoliaSearch\Config\SearchConfig::create(
            config('scout.algolia.id'),
            config('scout.algolia.secret'),
        );

        $client = \Algolia\AlgoliaSearch\SearchClient::createWithConfig($config);

        $softDelete = config('scout.soft_delete');

        // Prefer scout-extended's engine if installed (preserves aggregators, settings sync, etc.)
        if (class_exists(\Algolia\ScoutExtended\Engines\AlgoliaEngine::class)) {
            return new \Algolia\ScoutExtended\Engines\AlgoliaEngine($client, $softDelete);
        }

        // Scout 10+ has Algolia3Engine; older versions use AlgoliaEngine
        if (class_exists(\Laravel\Scout\Engines\Algolia3Engine::class)) {
            return new \Laravel\Scout\Engines\Algolia3Engine($client, $softDelete);
        }

        return new \Laravel\Scout\Engines\AlgoliaEngine($client, $softDelete);
    }
}
