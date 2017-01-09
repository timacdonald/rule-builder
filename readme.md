A fluent rule builder for Laravel validation rule generation. It will proxy to the built in Laravel validation rules where possible.

# Basic Usage

See my post: [https://timacdonald.me/blog/fluent-validation-rules-laravel/](https://timacdonald.me/blog/fluent-validation-rules-laravel/)

Update: `->get()` method now returns an array of rules instead of a pipe seperated string, but the interaction with the Validator remains the same as the validator splits the string into an array before performing the validation logic. If you would like to see the rules as a string you can simply cast the rule builder instance to an array e.g. `(string) Rule::isUnique('user')->ignore(2)`.

# Installation

`composer require timacdonald/rule-builder`

[View on Packagist](https://packagist.org/packages/timacdonald/rule-builder)
