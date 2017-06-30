<?php

namespace TiMacDonald\Validation;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Rule
{
    const BASIC_RULES = [
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
        static::$extendedRules = array_merge(static::$extendedRules, Arr::flatten($rules));
    }

    // Deprecated
    public static function extendWithRules(...$rules)
    {
        return static::extend($rules);
    }

    public function __call($method, $arguments)
    {
        $rule = $this->methodNameToRule($method);

        if ($this->isLocalRule($rule)) {
            return $this->applyLocalRule($rule, $arguments);
        }

        if ($this->isProxyRule($rule)) {
            return $this->applyProxyRule($method, $arguments);
        }

        if ($this->canApplyToLatestProxyRule($method)) {
            return $this->applyToLatestProxyRule($method, $arguments);
        }

        throw new Exception('Unable to handle or proxy the method '.$method.'(). If it is to be applied to a proxy rule, ensure it is called directly after the original proxy rule.');
    }

    protected function methodNameToRule($method)
    {
        return Str::snake($method);
    }

    protected function isLocalRule($rule)
    {
        return $this->isBasicRule($rule) || $this->isExtendedRule($rule);
    }

    protected function isExtendedRule($rule)
    {
        return in_array($rule, static::$extendedRules);
    }

    protected function isBasicRule($rule)
    {
        return in_array($rule, static::BASIC_RULES);
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

        return $this->applyRule($this->buildStringBaeRule($rule, $arguments));
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

    protected function buildStringBaeRule($rule, $arguments)
    {
        return $rule.$this->buildArgumentString($arguments);
    }

    protected function buildArgumentString($arguments)
    {
        if (!empty($arguments)) {
            return ':'.implode(',', Arr::flatten($arguments));
        }
    }

    protected function applyProxyRule($method, $arguments)
    {
        return $this->applyRule($this->buildProxyRule($method, $arguments));
    }

    protected function buildProxyRule($method, $arguments)
    {
        return call_user_func_array(
            [\Illuminate\Validation\Rule::class, $method], $arguments
        );
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
        if (empty($this->proxiedRules)) {
            return false;
        }

        return method_exists($this->latestProxyRule(), $method);
    }

    protected function latestProxyRule()
    {
        return Arr::last($this->proxiedRules);
    }

    protected function applyToLatestProxyRule($method, $arguments)
    {
        $this->latestProxyRule()->$method(...$arguments);

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
        return $size ? $this->size($size) : $this;
    }

    protected function setMinAndMax($arguments)
    {
        if (empty($arguments)) {
            return $this;
        }

        list($min, $max) = array_merge(Arr::flatten($arguments), [null, null]);

        return $this->setMin($min)->setMax($max);
    }

    protected function setMax($max)
    {
        return $max ? $this->max($max) : $this;
    }

    protected function setMin($min)
    {
        return $min ? $this->min($min) : $this;
    }

    // custom rules

    protected function activeUrlRule($max = null)
    {
        return $this->applyRule('active_url')->setMax($max);
    }

    protected function alphaRule()
    {
        return $this->applyRule('alpha')->setMinAndMax(func_get_args());
    }

    protected function alphaDashRule()
    {
        return $this->applyRule('alpha_dash')->setMinAndMax(func_get_args());
    }

    protected function alphaNumRule()
    {
        return $this->applyRule('alpha_num')->setMinAndMax(func_get_args());
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
        $instance = is_string($class) ? new $class : $class;

        return $this->exists($instance->getTable(), $instance->getKeyName());
    }

    protected function imageRule($size = null)
    {
        return $this->applyRule('image')->setSize($size);
    }

    protected function integerRule()
    {
        $this->applyRule('integer')->setMinAndMax(func_get_args());
    }

    protected function jsonRule($max = null)
    {
        return $this->applyRule('json')->setMax($max);
    }

    protected function stringRule()
    {
        return $this->applyRule('string')->setMinAndMax(func_get_args());
    }

    protected function urlRule($max = null)
    {
        return $this->applyRule('url')->setMax($max);
    }

    protected function numericRule()
    {
        $this->applyRule('numeric')->setMinAndMax(func_get_args());
    }

    protected function rawRule($rules)
    {
        return $this->apply($rules);
    }

    protected function uniqueRule($table, $column = 'NULL')
    {
        return $this->applyProxyRule('unique', [
            $this->parseTableName($table), $column
        ]);
    }

    protected function parseTableName($table)
    {
        if ($table instanceof \Illuminate\Database\Eloquent\Model) {
            return $table->getTable();
        }

        if (class_exists($table)) {
            return (new $table)->getTable();
        }

        return $table;
    }

    protected function whenRule($condition, $callback)
    {
        if ($this->evaluate($condition)) {
            call_user_func($callback, $this);
        }

        return $this;
    }

    protected function evaluate($condition)
    {
        return is_callable($condition) ? call_user_func($condition) : $condition;
    }
}
