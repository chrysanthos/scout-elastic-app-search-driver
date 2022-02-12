# Elastic App Search Driver for Laravel Scout

This package is a work in progress. Avoid using this on production environments.

Integrate [Elastic App Search](https://www.elastic.co/enterprise-search) with [Laravel Scout](https://laravel.com/docs/8.x/scout).

This is an early but functional version. Tests to be added.

## Installation

You can install the package via composer:

```bash
composer require chrysanthos/scout-elastic-app-search-driver
```

## Usage

In order to use the package, you must set Laravel Scout to use the driver
```dotenv
SCOUT_DRIVER=elastic-app-search
```

Then set up the connection details for Elastic App Search

```dotenv
SCOUT_ELASTIC_APP_SEARCH_ENDPOINT=
SCOUT_ELASTIC_APP_SEARCH_API_KEY=
```

You will also need to adjust `config/scout.php` so that the chunk sizes are 100 records:

```php
'chunk' => [
    'searchable' => 100,
    'unsearchable' => 100,
],
```

Once you have added the Searchable Trait to your model. You will be able to search with:
```php 
 $result = Model::search($searchTerm)->get();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security

If you discover any security related issues, please email me@chrysanthos.xyz instead of using the issue tracker.

## Credits
- [Keoghan Litchfield](https://github.com/konsulting) (Initial work)

## Licence
This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/chrysanthos/scout-elastic-app-search-driver) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.

