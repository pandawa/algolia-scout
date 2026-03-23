<?php

declare(strict_types=1);

namespace Pandawa\ScoutAlgolia;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\AlgoliaSearch\Configuration\SearchConfig;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Algolia4Engine;

class AlgoliaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $url = config('scout.algolia.url');

        if (empty($url)) {
            return;
        }

        $this->app->make(EngineManager::class)->extend('algolia', function () use ($url) {
            $config = SearchConfig::create(
                config('scout.algolia.id'),
                config('scout.algolia.secret'),
            );

            $parsed = parse_url($url);
            $scheme = $parsed['scheme'] ?? 'http';
            $host = $parsed['host'] ?? 'localhost';
            $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);

            $config->setFullHosts(["{$scheme}:{$host}:{$port}"]);

            return new Algolia4Engine(
                SearchClient::createWithConfig($config),
                config('scout.soft_delete'),
            );
        });
    }
}
