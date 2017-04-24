# Fluent Validation Rule Builder

A fluent interface to generate Laravel validation rules. It proxies to the built in Laravel validation rules where possible and also add some sugar such as `min`, `max` helpers, as well as a handy `when` method and `character` rule.

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/rule-builder)

```
composer require timacdonald/rule-builder
```

## Usage

```php

use TiMacDonald\Validation\Rule;

$rules = [
    'name' => Rule::required()
                  ->string()
                  ->max(255)
                  ->get(),

    'email' => Rule::required()
                   ->string()
                   ->email()
                   ->max(255)
                   ->unique('users')
                   ->get(),

    'password' => Rule::required()
                      ->string()
                      ->min(6)
                      ->confirmed()
                      ->get()
];
```

Don't forget you need to call the final `get()` method.

### Conditional Rules

You can add rules conditionally using the `when()` method.

```php

use TiMacDonald\Validation\Rule;

$rules = [
    'data' => Rule::when($requiresJson, function ($rule) {
        $rule->json();
    })->max(1000)->get()
];
```

### Character Rule

Handy little helper that allows you to validate a single alpha character.

```php
use TiMacDonald\Validation\Rule;

$rules = [
    'initial' => Rule::character()->get()
];

```

The `character` rule is equivalent to calling `Rule::alpha()->max(1)`.

### Min / Max Helpers

These methods allow for optional `$min` and / or `$max` arguments to help ensuring you can store the input properly in your database etc. Here is a list of the available helpers and their arguments:

```php
use TiMacDonald\Validation\Rule;

Rule::activeUrl($max)
    ->alpha($min, $max)
    ->alphaDash($min, $max)
    ->alphaNum($min, $max)
    ->email($max)
    ->file($size)
    ->image($size)
    ->integer($min, $max)
    ->numeric($min, $max)
    ->json($max)
    ->string($min, $max)
    ->url($max);
```

An example of these might be:

```php
use TiMacDonald\Validation\Rule;

$rules = [
    'age' => Rule::integer(21)->get(),
    'dollars' => Rule::numeric(0, 999.99)->get(),
    'email' => Rule::email(255)->get()
];
```

### Proxy to Laravel Rule Classes

Laravel comes with some built in rule classes. If one is present, we simply proxy to them and keep on rocking, it's seamless. The `unique` rule is a built in Laravel class with a `where` method - check this out:

```php
use TiMacDonald\Validation\Rule;

$rules = [
    'email' => Rule::unique('users')->where(function ($query) {
                   $query->where('account_id', 1);
               })->email(255)->get()
];
```

Just make sure you call any methods that apply to the proxied rule directly after the initla call to the proxy method.

### Raw Rules

You can utilise rules not use setup on the rule builder by using the `raw` helper. For the sake of example:

```
$rules = [
    'email' => Rule::email()->raw('string|max:255')->get()
];
```

## Contributing

Please feel free to suggest new ideas or send through pull requests to make this better. If you'd like to discuss the project, feel free to reach out on [Twitter](https://twitter.com/timacdonald87).

## What next?

- Tests, tests, tests!
- Add ability to set default `$min` and `$max` values for rules so when you call `->email()` it can default to include a `max(255)` rule.
- Ensure `min` and `max` do not conflict.
- Ensure only one or each rule can be added, i.e. if 2 max rules are set, the last value overwrites the first - perhaps a `strict` method that checks for duplicates?

## License

This package is under the MIT License. See [LICENSE](https://github.com/timacdonald/rule-builder/blob/master/LICENSE.txt) file for details.
