# pandawa/scout-algolia

Custom Algolia engine for Laravel Scout with configurable base URL.

## Installation

```bash
composer require pandawa/scout-algolia
```

The package uses Laravel's auto-discovery, no manual provider registration needed.

## Configuration

Add `url` to the `algolia` array in `config/scout.php`:

```php
'algolia' => [
    'id' => env('ALGOLIA_APP_ID', ''),
    'secret' => env('ALGOLIA_SECRET', ''),
    'url' => env('ALGOLIA_URL'),
],
```

Then set the environment variable:

```
ALGOLIA_URL=http://your-host:9501
```

When `ALGOLIA_URL` is not set, Scout's default Algolia behavior is used unchanged.

## Requirements

- PHP >= 8.2
- Laravel >= 11.0
- Laravel Scout >= 10.0
- Algolia PHP client >= 4.0
