# Fluent Validation Rule Builder

[![Latest Stable Version](https://poser.pugx.org/timacdonald/rule-builder/v/stable)](https://packagist.org/packages/timacdonald/rule-builder) [![Total Downloads](https://poser.pugx.org/timacdonald/rule-builder/downloads)](https://packagist.org/packages/timacdonald/rule-builder) [![License](https://poser.pugx.org/timacdonald/rule-builder/license)](https://packagist.org/packages/timacdonald/rule-builder)

A fluent interface to generate Laravel validation rules with helpers. It proxies to the built in Laravel validation rules where possible and also adds some sugar such as `min` and `max` helpers, as well as a handy `when` method (inline that `sometimes` rule!), and `foreignKey` rule. I love it - get around it yo!

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/rule-builder)

```
composer require timacdonald/rule-builder
```

## Versioning

This package uses *Semantic Versioning*. You can find out more about what this is and why it matters by reading [the spec](http://semver.org) or for something more readable, check out [this post](https://laravel-news.com/building-apps-composer).

## Basic Usage

All the examples assume you have included the `use TiMacDonald\Validation\Rule;` statement already.

```php
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

Don't forget you need to call the final `get()` method. It's a little verbose, but I actually really enjoy using it this way - you might not ¯\\\_(ツ)_/¯

### Min / Max Helpers

These methods allow for optional `$min` and / or `$max` parameters to help validate size retrictions on the data. Here is a list of the available helpers and their parameters:

```php
Rule::activeUrl($max)
    ->alpha($min, $max)
    ->alphaDash($min, $max)
    ->alphaNum($min, $max)
    ->array($min, $max)
    ->email($max)
    ->file($max)
    ->image($max)
    ->integer($min, $max)
    ->json($max)
    ->numeric($min, $max)
    ->string($min, $max)
    ->url($max);
```

Example usage of these helper methods:

```php
$rules = [
    'age' => Rule::integer(21)->get(),
    'dollars' => Rule::numeric(0, 999.99)->get(),
    'email' => Rule::email(255)->get()
];
```

### Conditional Rules

You can add rules conditionally using the `when()` method. This is similar to Laravel's `sometimes` method, however it is inline with your rules.

```php
$rules = [
    'username' => Rule::required()->when($userNameIsEmail, function ($rule) {
        $rule->email();
    })->get()
];
```

### Optional or Nullable?!?

I'm always forgetting if it is `optional` or `nullable`. Just to be clear - it's `nullable`, but I added an `optional` rule so that I can use that as well - just because I can really.

```php
$rules = [
    'age' => Rule::optional()->integer(0)->get()
];
```

### Proxy to Laravel Rule Classes

Laravel comes with some built in rule classes. If one is present, we simply proxy to it and keep on rocking, it's seamless. The `unique` rule is a built in Laravel class with a `where` method - check this out:

```php
$rules = [
    'email' => Rule::unique('users')->where(function ($query) {
                   $query->where('account_id', 1);
               })->email(255)->get()
];
```

Just make sure you call any methods that apply to the proxied rule directly after the inital call to the proxy method.

### Foreign Key Validation

Want to stop using the `exists` rule and be able to rock those foreign key validation rules like a boss? We'll look no further:

```php
$rules [
  'subscription_id' => Rule::foreignKey(Subscription::class)->get()
];
```

You can even pass in an instance if you want! The class or instance will be queried to determine the table names etc for you #magic

### Unique Rule with Class or Instance.

As [suggested on internals](https://github.com/laravel/internals/issues/591#issuecomment-302018299) you are now able to apply the unique constraint using a models class name, or an instance, instead of passing in the table name as a plain string (similar to the `foreignKey` rule). This method still proxies to Laravel's built in unique rule, so you can continue to chain rules.

```php
$rules [
  'title' => Rule::unique(Post::class, 'title')->get()
];
```

### Extending with Custom Rules

If you are [creating your own validation rules](https://laravel.com/docs/5.4/validation#custom-validation-rules) and wish to use them with the rule builder you can simply extend the rule builder. You will want to do this in a [service provider](https://laravel.com/docs/5.4/providers).

```php
<?php

namespace App\Providers;

use TiMacDonald\Validation\Rule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('foo_bar', function ($attribute, $value, $parameters, $validator) {
            return $value == 'foo_bar';
        });

        Rule::extend(['foo_bar']);
    }
```

We reply on Laravel's validation rule naming convention, so please stick with snake_case rule names. Now we can use our `foo_bar` rule like so:

```php
$rules = [
    'name' => Rule::string()->fooBar()->get()
];
```

You can even pass in values like you normally would:

```php
$rules = [
    'name' => Rule::string()->fooBar('baz')->get()
];
```

This would be equivalent to `string|for_bar:baz`.

### Raw Rules

You can utilise rules not yet setup on the rule builder by using the `raw` helper. For the sake of example:

```php
$rules = [
    'email' => Rule::email()->raw('min:10|max:255')->get()
];
```

is equivalent to `email|min:10|max:255`...but don't set a min on email - thats crazy!

### Pipe Seperated String

By default an array is returned containing all the rules. If you want a pipe (`|`) seperated string instead, you can simple cast to a string, like so:

```php
$rules = [
    'email' => (string) Rule::required()->email(255)
];
```

## Contributing

Please feel free to suggest new ideas or send through pull requests to make this better. If you'd like to discuss the project, feel free to reach out on [Twitter](https://twitter.com/timacdonald87). I just throw my ideas for the project in the [issues list](https://github.com/timacdonald/rule-builder/issues) if you want to help implement anything.

## License

This package is under the MIT License. See [LICENSE](https://github.com/timacdonald/rule-builder/blob/master/LICENSE.txt) file for details.