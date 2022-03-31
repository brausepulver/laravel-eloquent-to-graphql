# laravel-eloquent-to-graphql

[![Tests](https://github.com/brausepulver/laravel-eloquent-to-graphql/actions/workflows/tests.yml/badge.svg)](https://https://github.com/brausepulver/laravel-eloquent-to-graphql/actions)
[![Latest Stable Version](https://img.shields.io/packagist/v/brausepulver/laravel-eloquent-to-graphql)](https://packagist.org/packages/brausepulver/laravel-eloquent-to-graphql)
[![License](https://img.shields.io/packagist/l/brausepulver/laravel-eloquent-to-graphql?color=9cf)](https://packagist.org/packages/brausepulver/laravel-eloquent-to-graphql)

This package automatically turns your Eloquent models into a GraphQL schema.

It is suggested to be used with [lighthouse](https://lighthouse-php.com/).

## Setup

1. Install the package:
```
composer require --dev brausepulver/laravel-eloquent-to-graphql
```

2. Type-hint your models' relationships:
```php
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    public function cuteCats(): HasMany
    {
        ...
    }
}
```

3. _(Optional)_ Publish the configuration file:
```
php artisan vendor:publish --tag="eloquent_to_graphql.config"
```

## Usage

```
php artisan e2gql
```
This will generate a schema for all your models in a single file at `graphql/generated.graphql`.

There are several options for customizing the way the schema is generated. Please have a look at them with  
`php artisan e2gql --help`.

## Configuration

### Automatic Updates

To automatically update the schema every time one of your Eloquent models changes, you can register the command in your IDE of choice with the `--force` option, which disables all prompts.

If you are using VSCode, this can be achieved using the [Run on Save](https://marketplace.visualstudio.com/items?itemName=emeraldwalk.RunOnSave) extension. After installing the extension, you can add the following to `.vscode/settings.json`:
```json
    "emeraldwalk.runonsave": {
        "commands": [
            {
                "match": "app/Models/.*\\.php",
                "isAsync": true,
                "cmd": "php artisan e2gql --force"
            }
        ]
    }
```
With standard project structure, this will update your schema every time an Eloquent model changes.

### Custom Type Mappings

If your tables have columns with types unknown to DBAL, the command will let you know. In this case, you have two options:

1. Let the command know which type that is known by DBAL you would like to map the unkown type to. You can do this by adding both to `custom_type_mappings` in `config/eloquent_to_graphql.php`:
```php
    'custom_type_mappings' => [
        'from-type' => 'to-type'
    ]
```

2. Ignore the columns entirely using the `--exclude-columns` option. You can then later add them by hand.

## TODO

- [ ] Option to automatically update the schema after migrations are run
- [ ] Way to keep changes made by the user when regenerating a schema
- [ ] More customizable type resolution
