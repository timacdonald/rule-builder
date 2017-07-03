<?php

namespace TiMacDonald\Validation;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Rule
{
    const LOCAL_RULES = [
        'alpha',
        'accepted',
        'alpha_num',
        'active_url',
        'alpha_dash',
        'array',
        'boolean',
        'character',
        'confirmed',
        'date',
        'distinct',
        'email',
        'file',
        'filled',
        'image',
        'integer',
        'ip',
        'json',
        'nullable',
        'numeric',
        'present',
        'required',
        'string',
        'timezone',
        'url',
        'after',
        'before',
        'between',
        'date_format',
        'different',
        'digits',
        'digits_between',
        'foreign_key',
        'in_array',
        'max',
        'mimetypes',
        'mimes',
        'min',
        'raw',
        'regex',
        'required_with',
        'required_with_all',
        'required_without',
        'required_without_all',
        'same',
        'size',
        'unique',
        'when',
        'required_if',
        'required_unless',
        'bail',
        'sometimes'
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

    protected function setSize($size)
    {
        return $this->set('size', $size);
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

    // custom rules

    protected function activeUrlRule($max = null)
    {
        return $this->applyRule('active_url')->setMax($max);
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

    protected function characterRule()
    {
        return $this->alpha()->max(1);
    }

    protected function emailRule($max = null)
    {
        return $this->applyRule('email')->setMax($max);
    }

    protected function fileRule($size = null)
    {
        return $this->applyRule('file')->setSize($size);
    }

    protected function foreignKeyRule($class)
    {
        $instance = $this->parseInstance($class);

        return $this->exists($instance->getTable(), $instance->getKeyName());
    }

    protected function imageRule($size = null)
    {
        return $this->applyRule('image')->setSize($size);
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
        return $this->applyRule($rules);
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
}
