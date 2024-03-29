# Fluent Validation Rule Builder for Laravel

[![Latest Stable Version](https://poser.pugx.org/timacdonald/rule-builder/v/stable)](https://packagist.org/packages/timacdonald/rule-builder) [![Total Downloads](https://poser.pugx.org/timacdonald/rule-builder/downloads)](https://packagist.org/packages/timacdonald/rule-builder) [![License](https://poser.pugx.org/timacdonald/rule-builder/license)](https://packagist.org/packages/timacdonald/rule-builder)

A fluent interface to generate Laravel validation rules with helpers. It proxies to the built in Laravel validation rules where possible and also adds some sugar such as `min` and `max` helpers, ability to pass `Carbon` instances to date rules, as well as a handy `when` method (inline that `sometimes` rule!). I've also add a `foreignKey` and `unique` rule that allows you to pass in classes or instances. I love it - get around it yo!

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/rule-builder)

```
$ composer require timacdonald/rule-builder
```

## Usage

All the examples assume you have included the `use TiMacDonald\Validation\Rule;` statement already.

```php
$rules = [
    'name' => Rule::required()
                  ->string(3, 255)
                  ->get(),

    'email' => Rule::required()
                   ->string()
                   ->email(255)
                   ->unique('users')
                   ->get(),

    'password' => Rule::required()
                      ->string(6)
                      ->confirmed()
                      ->get(),
];
```

Don't forget you need to call the final `get()` method. Using this instead of the standard Laravel 'stringy' way is a little verbose, but I actually really enjoy using it this way - you might not ¯\\\_(ツ)_/¯

### Min / Max Helpers

These methods allow for optional `$min` and / or `$max` parameters to help validate size restrictions on the data. Here is a list of the available helpers and their parameters:

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
    ->url($max)
```

Example usage of these helper methods:

```php
$rules = [
    'age' => Rule::integer(21)->get(),
    'dollars' => Rule::numeric(0, 999.99)->get(),
    'email' => Rule::email(255)->get(),
];
```

If you pass `null` as a `min` or `max` helper, the value will be skipped. This is mostly handy when there is both a `min` and `max` helper, but you do not want to add a `min` e.g. `Rule::string(null, 255)->get()`.

### Custom Validation Rules

Laravel has introduced some very handy [custom validation classes](https://laravel.com/docs/5.5/validation#custom-validation-rules). We've made it simple to add these rules as well. Chances are you would probably just implement all the required rules in a single validation class and not require the rule builder, but in case you do you can do the following:

```php
$rules = [
    'notifications' => Rule::add(new MyValidationRule)
                           ->add(new MyOtherValidationRule)
                           ->get(),
];
```

### Carbon with Date Rules

You can now pass a `Carbon` instance to the date rules: `after`, `after_or_equal`, `before`, `before_or_equal`.

```php
$rules = [
    'due_date' => Rule::after(Carbon::now()->addYear())->get()
];
```

Laravel's date rules utilise PHP's [`strtotime`](http://php.net/manual/en/function.strtotime.php) function to parse the provided date. As recommended by the PHP docs, the `Carbon` instance is formatted as ISO 8601 to avoid any date ambiguity.

### Conditional Rules

You can add rules conditionally using the `when()` method. This is similar to Laravel's `sometimes` method, however it is inline with your rules.

```php
$rules = [
    'username' => Rule::required()->when($userNameIsEmail, function ($rule) {
        $rule->email();
    })->get(),
];
```

### Proxy to Laravel Rule Classes

Laravel comes with some built in rule classes. If one is present, we simply proxy to it and keep on rocking, it's seamless. The `unique` rule is a built in Laravel class with a `where` method - check this out:

```php
$rules = [
    'email' => Rule::unique('users')->where(function ($query) {
                   $query->where('account_id', 1);
               })->email(255)->get(),
];
```

Just make sure you call any methods that apply to the proxied rule directly after the initial call to the proxy method.

### Foreign Key Validation

Want to stop using the `exists` rule and be able to rock those foreign key validation rules like a boss? We'll look no further:

```php
$rules = [
  'subscription_id' => Rule::foreignKey(Subscription::class)->get(),
];
```

You can even pass in an instance if you want! The class or instance will be queried to determine the table names etc for you #magic

### Unique Rule with Class or Instance.

As [suggested on internals](https://github.com/laravel/internals/issues/591#issuecomment-302018299) you are now able to apply the unique constraint using a models class name, or an instance, instead of passing in the table name as a plain string (similar to the `foreignKey` rule). This method still proxies to Laravel's built in unique rule, so you can continue to chain rules.

```php
$rules = [
  'title' => Rule::unique(Post::class, 'title')->get(),
];
```

### URL with specific hostname extension

If you need to validate that the URL has an extension (TLD or whatnot) or even a specific extension (.org.au) this validation rule is for you!

``` php
$rules = [
    'website' => Rule::urlWithHostExtension(['.org.au'])->get(), // only .org.au extensions allowed
    'domain' => Rule::urlWithHostExtension()->get(), //and extension
];
```

This rule first applied the `url` rule and then adds a regex pattern to check for the extensions existence.

### URL with specific scheme

It can be handy to check that a scheme is perhaps `https` or `fb` or some other URL scheme. This is a handy rule to ensure this is enforced.

``` php
$rules = [
    'profile' => Rule::urlWithScheme(['https', 'fb'])->get(),
];
```

### Max Digits Helper Rule

I've added a `maxDigits` rule after reading [this suggestion](https://github.com/laravel/internals/issues/673) over on internals. This is just an alias to the `digits_between` rule.

```
$rules = [
  'amount' => Rule::digitsMax(10)->get(),
];
```

Which is equivalent to `digits_between:0,10`.

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
            return $value === 'foo_bar';
        });

        Rule::extend(['foo_bar']);
    }
```

We reply on Laravel's validation rule naming convention, so please stick with snake_case rule names. Now we can use our `foo_bar` rule like so:

```php
$rules = [
    'name' => Rule::string()->fooBar()->get(),
];
```

You can even pass in values like you normally would:

```php
$rules = [
    'name' => Rule::string()->fooBar('baz')->get(),
];
```

This would be equivalent to `string|for_bar:baz`.

### Raw Rules

You can utilise rules not yet setup on the rule builder by using the `raw` helper. For the sake of example:

```php
$rules = [
    'email' => Rule::email()->raw('min:10|max:255')->get(),
];
```

is equivalent to `email|min:10|max:255`...but don't set a min on email - thats crazy!

### Pipe Seperated String

By default an array is returned containing all the rules. If you want a pipe (`|`) separated string instead, you can simple cast to a string, like so:

```php
$rules = [
    'email' => (string) Rule::required()->email(255),
];
```

## Misc Other Rules

Just some other rules I've used previously. Might be handy for someone.

### Not Empty HTML

I've used this when using a wysiwyg editor and needed to validate that the contents was not empty - but unfortunately the editor would add an empty `<p>` tag.

In your service provider:

```php
Validator::extend('not_empty_html', function ($attribute, $value) {
    return trim(strip_tags($value)) !== '';
});

Rule::extend(['not_empty_html']);
```

## Thanksware

You are free to use this package, but I ask that you reach out to someone (not me) who has previously, or is currently, maintaining or contributing to an open source library you are using in your project and thank them for their work. Consider your entire tech stack: packages, frameworks, languages, databases, operating systems, frontend, backend, etc.
