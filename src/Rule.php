<?php

namespace TiMacDonald\Validation;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Rule
{
    const LOCAL_RULES = [
        'accepted',
        'active_url',
        'after', // custom
        'after_or_equal', // custom
        'alpha',
        'alpha_dash',
        'alpha_num',
        'array',
        'bail',
        'before', // custom
        'before_or_equal', // custom
        'between',
        'boolean',
        'character', // custom
        'confirmed',
        'date',
        'date_format',
        'different',
        'digits',
        'digits_between',
        'digits_max', // custom
        'distinct',
        'email',
        'file',
        'filled',
        'foreign_key', // custom
        'image',
        'in_array',
        'integer',
        'ip',
        'ipv4',
        'ipv6',
        'json',
        'max',
        'mimetypes',
        'mimes',
        'min',
        'nullable',
        'numeric',
        'optional', // custom
        'present',
        'raw', // custom
        'regex',
        'required',
        'required_if',
        'required_unless',
        'required_with',
        'required_with_all',
        'required_without',
        'required_without_all',
        'same',
        'size',
        'string',
        'timezone',
        'unique',
        'url',
        'when', // custom
    ];

    const PROXIED_RULES = [
        'dimensions',
        'exists',
        'in',
        'not_in'
    ];

    protected static $extendedRules = [];

    protected $appliedRules = [];

    public static function __callStatic($method, $arguments)
    {
        return (new static)->$method(...$arguments);
    }

    public static function extend(...$rules)
    {
        static::$extendedRules = array_merge(
            static::$extendedRules, Arr::flatten($rules)
        );
    }

    public static function extendWithRules()
    {
        return static::extend(func_get_args());
    }

    public function __call($method, $arguments)
    {
        $rule = $this->methodNameToRule($method);

        if ($this->isLocallyHandledRule($rule)) {
            return $this->applyLocalRule($rule, $arguments);
        }

        if ($this->isProxyRule($rule)) {
            return $this->applyProxyRule($method, $arguments);
        }

        if ($this->canApplyToLatestProxyRule($method)) {
            return $this->applyToLatestProxyRule($method, $arguments);
        }

        throw new Exception('Unable to locally handle or proxy the method '.$method.'(). If it is to be applied to a proxy rule, ensure it is called directly after the original proxy rule method call.');
    }

    protected function methodNameToRule($method)
    {
        return Str::snake($method);
    }

    protected function isLocallyHandledRule($rule)
    {
        return $this->isLocalRule($rule) || $this->isExtendedRule($rule);
    }

    protected function isExtendedRule($rule)
    {
        return in_array($rule, static::$extendedRules);
    }

    protected function isLocalRule($rule)
    {
        return in_array($rule, static::LOCAL_RULES);
    }

    protected function isProxyRule($rule)
    {
        return in_array($rule, static::PROXIED_RULES);
    }

    protected function applyLocalRule($rule, $arguments = [])
    {
        if ($this->hasCustomRule($rule)) {
            return $this->applyCustomRule($rule, $arguments);
        }

        return $this->applyRule($this->buildStringRule($rule, $arguments));
    }

    protected function applyCustomRule($rule, $arguments)
    {
        return $this->{$this->customRuleMethod($rule)}(...$arguments);
    }

    protected function applyRule($rule)
    {
        $this->appliedRules[] = $rule;

        return $this;
    }

    protected function buildStringRule($rule, $arguments)
    {
        return $rule.$this->buildArgumentString($arguments);
    }

    protected function buildArgumentString(array $arguments)
    {
        if ($arguments) {
            return ':'.implode(',', Arr::flatten($arguments));
        }
    }

    protected function applyProxyRule($method, $arguments)
    {
        return $this->applyRule($this->buildProxyRule($method, $arguments));
    }

    protected function buildProxyRule($method, $arguments)
    {
        return (\Illuminate\Validation\Rule::class)::$method(...$arguments);
    }

    protected function hasCustomRule($rule)
    {
        return method_exists($this, $this->customRuleMethod($rule));
    }

    protected function customRuleMethod($rule)
    {
        return Str::camel($rule).'Rule';
    }

    protected function canApplyToLatestProxyRule($method)
    {
        return method_exists($this->latestRule(), $method);
    }

    protected function latestRule()
    {
        return Arr::last($this->appliedRules);
    }

    protected function applyToLatestProxyRule($method, $arguments)
    {
        $this->latestRule()->$method(...$arguments);

        return $this;
    }

    protected function allRules()
    {
        return $this->appliedRules;
    }

    public function get()
    {
        return $this->allRules();
    }

    public function __toString()
    {
        return implode('|', $this->allRules());
    }

    // helpers...

    protected function setCarbonRule($rule, $date)
    {
        if ($date instanceof Carbon) {
            return $this->applyRule($rule.':'.$date->toIso8601String());
        }

        return $this->applyRule($rule.':'.$date);
    }

    protected function setMax($max)
    {
        return $this->set('max', $max);
    }

    protected function setMin($min)
    {
        return $this->set('min', $min);
    }

    protected function set($rule, $value)
    {
        return $value ? $this->$rule($value) : $this;
    }

    protected function evaluate($condition)
    {
        return is_callable($condition) ? call_user_func($condition) : $condition;
    }

    protected function parseInstance($instance)
    {
        return is_string($instance) ? new $instance : $instance;
    }

    protected function parseTableName($table)
    {
        if (method_exists($table, 'getTable')) {
            return $this->parseInstance($table)->getTable();
        }

        return $table;
    }

    // custom rules...

    protected function activeUrlRule($max = null)
    {
        return $this->applyRule('active_url')->setMax($max);
    }

    protected function afterRule($date)
    {
        return $this->setCarbonRule('after', $date);
    }

    protected function afterOrEqualRule($date)
    {
        return $this->setCarbonRule('after_or_equal', $date);
    }

    protected function alphaRule($min = null, $max = null)
    {
        return $this->applyRule('alpha')->setMin($min)->setMax($max);
    }

    protected function alphaDashRule($min = null, $max = null)
    {
        return $this->applyRule('alpha_dash')->setMin($min)->setMax($max);
    }

    protected function alphaNumRule($min = null, $max = null)
    {
        return $this->applyRule('alpha_num')->setMin($min)->setMax($max);
    }

    protected function arrayRule($min = null, $max = null)
    {
        return $this->applyRule('array')->setMin($min)->setMax($max);
    }

    protected function beforeRule($date)
    {
        return $this->setCarbonRule('before', $date);
    }

    protected function beforeOrEqualRule()
    {
        return $this->setCarbonRule('before_or_equal', $date);
    }

    // @deprecated will be removed in future versions.
    protected function characterRule()
    {
        return $this->alpha(1, 1);
    }

    protected function digitsMax($max)
    {
        return $this->digitsBetween(0, $max);
    }

    protected function emailRule($max = null)
    {
        return $this->applyRule('email')->setMax($max);
    }

    protected function fileRule($max = null)
    {
        return $this->applyRule('file')->setMax($max);
    }

    protected function foreignKeyRule($class)
    {
        $instance = $this->parseInstance($class);

        return $this->exists($instance->getTable(), $instance->getKeyName());
    }

    protected function imageRule($max = null)
    {
        return $this->applyRule('image')->setMax($max);
    }

    protected function integerRule($min = null, $max = null)
    {
        return $this->applyRule('integer')->setMin($min)->setMax($max);
    }

    protected function jsonRule($max = null)
    {
        return $this->applyRule('json')->setMax($max);
    }

    protected function numericRule($min = null, $max = null)
    {
        return $this->applyRule('numeric')->setMin($min)->setMax($max);
    }

    protected function optionalRule()
    {
        return $this->nullable();
    }

    protected function stringRule($min = null, $max = null)
    {
        return $this->applyRule('string')->setMin($min)->setMax($max);
    }

    protected function urlRule($max = null)
    {
        return $this->applyRule('url')->setMax($max);
    }

    protected function rawRule($rules)
    {
        foreach (explode('|', $rules) as $rule) {
            $this->applyRule($rule);
        }

        return $this;
    }

    protected function uniqueRule($table, $column = 'NULL')
    {
        return $this->applyProxyRule('unique', [
            $this->parseTableName($table), $column
        ]);
    }

    protected function whenRule($condition, $callback)
    {
        if ($this->evaluate($condition)) {
            $callback($this);
        }

        return $this;
    }
}
