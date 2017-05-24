<?php

namespace TiMacDonald\Validation;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Rule
{
    const SIMPLE_RULES = [
        'accepted',
        'active_url', // custom
        'alpha', // custom
        'alpha_dash', // custom
        'alpha_num', // custom
        'array',
        'boolean',
        'character', // custom
        'confirmed',
        'date',
        'distinct',
        'email', // custom
        'file', // custom
        'filled',
        'image', // custom
        'integer', // custom
        'ip',
        'json', // custom
        'nullable',
        'numeric', // custom
        'present',
        'required',
        'string', // custom
        'timezone',
        'url' // custom
    ];

    protected static $extendedRules = [];

    const RULES_WITH_ARGUMENTS = [
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
        'raw', // custom
        'regex',
        'required_with',
        'required_with_all',
        'required_without',
        'required_without_all',
        'same',
        'size',
        'unique', // custom
        'when', // custom,
    ];

    const RULES_WITH_ID_AND_ARGUMENTS = [
        'required_if',
        'required_unless'
    ];

    const FLAGS = [
        'bail',
        'sometimes'
    ];

    const PROXIED_RULES = [
        'dimensions',
        'exists',
        'in',
        'not_in'
    ];

    protected $localRules = [];

    protected $proxiedRules = [];

    public static function __callStatic($method, $arguments)
    {
        return call_user_func_array([new static, $method], $arguments);
    }

    public function __call($method, $arguments)
    {
        if ($this->isExtending($method)) {
            return $this->extendWith($arguments);
        }

        $rule = Str::snake($method);


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

    protected function isExtending($method)
    {
        return $method === 'extendWithRules';
    }

    protected function extendWith($rules)
    {
        static::$extendedRules = array_merge(static::$extendedRules, Arr::flatten($rules));

        return $this;
    }

    protected function isLocalRule($rule)
    {
        return in_array($rule, static::SIMPLE_RULES)
            || in_array($rule, static::RULES_WITH_ARGUMENTS)
            || in_array($rule, static::RULES_WITH_ID_AND_ARGUMENTS)
            || in_array($rule, static::FLAGS)
            || in_array($rule, static::$extendedRules);
    }

    protected function isProxyRule($rule)
    {
        return in_array($rule, static::PROXIED_RULES);
    }

    protected function applyLocalRule($rule, $arguments = [])
    {
        if ($this->isCustomRule($rule)) {
            return call_user_func_array([$this, $this->customRuleMethod($rule)], $arguments);
        }

        if (!empty($arguments)) {
            $rule .= ':'.implode(',', Arr::flatten($arguments));
        }

        $this->localRules[] = $rule;

        return $this;
    }

    protected function applyProxyRule($method, $arguments)
    {
        $this->proxiedRules[] = call_user_func_array(
            [\Illuminate\Validation\Rule::class, $method], $arguments
        );

        return $this;
    }

    protected function isCustomRule($rule)
    {
        return method_exists($this, $this->customRuleMethod($rule));
    }

    protected function customRuleMethod($rule)
    {
        return Str::camel($rule).'Rule';
    }

    protected function canApplyToLatestProxyRule($method)
    {
        $proxy = Arr::last($this->proxiedRules);

        return !is_null($proxy) && method_exists($proxy, $method);
    }

    protected function applyToLatestProxyRule($method, $arguments)
    {
        $proxy = Arr::last($this->proxiedRules);

        call_user_func_array([$proxy, $method], $arguments);

        return $this;
    }

    protected function allRules()
    {
        return array_merge($this->localRules, $this->proxiedRules);
    }

    protected static function whenRule($condition, callable $callback)
    {
        $shouldCall = is_callable($condition) ? call_user_func($condition) : $condition;

        if ($shouldCall) {
            call_user_func($callback, $this);
        }

        return $this;
    }

    public function get()
    {
        return $this->allRules();
    }

    public function __toString()
    {
        return implode('|', $this->allRules());
    }

    protected function setMax($max)
    {
        if (!is_null($max)) {
            $this->max($max);
        }

        return $this;
    }

    protected function setMin($min)
    {
        if (!is_null($min)) {
            $this->min($min);
        }

        return $this;
    }

    protected function setSize($max)
    {
        if (!is_null($max)) {
            $this->size($max);
        }

        return $this;
    }

    protected function applyMinAndMaxFromFunctionArguments($arguments)
    {
        if (empty($arguments)) {
            return $this;
        }

        $values = is_array($arguments[0]) ? $arguments[0] : $arguments;

        list($min, $max) = array_merge($values, [null, null]);

        return $this->setMin($min)->setMax($max);
    }

    /**
     * Custom Rules...
     */

    protected function emailRule($max = null)
    {
        $this->localRules[] = 'email';

        return $this->setMax($max);
    }

    protected function activeUrlRule($max = null)
    {
        $this->localRules[] = 'active_url';

        return $this->setMax($max);
    }

    protected function characterRule()
    {
        return $this->alpha()->max(1);
    }

    protected function alphaRule()
    {
        $this->localRules[] = 'alpha';

        return $this->applyMinAndMaxFromFunctionArguments(func_get_args());
    }

    protected function alphaDashRule()
    {
        $this->localRules[] = 'alpha_dash';

        return $this->applyMinAndMaxFromFunctionArguments(func_get_args());
    }

    protected function alphaNumRule()
    {
        $this->localRules[] = 'alpha_num';

        return $this->applyMinAndMaxFromFunctionArguments(func_get_args());
    }

    protected function fileRule($size = null)
    {
        $this->localRules[] = 'file';

        return $this->setSize($size);
    }

    protected function imageRule($size = null)
    {
        $this->localRules[] = 'image';

        return $this->setSize($size);
    }

    protected function jsonRule($max = null)
    {
        $this->localRules[] = 'json';

        return $this->setMax($max);
    }

    protected function urlRule($max = null)
    {
        $this->localRules[] = 'url';

        return $this->setMax($max);
    }

    protected function stringRule()
    {
        $this->localRules[] = 'string';

        return $this->applyMinAndMaxFromFunctionArguments(func_get_args());
    }

    protected function integerRule()
    {
        $this->localRules[] = 'integer';

        return $this->applyMinAndMaxFromFunctionArguments(func_get_args());
    }

    protected function numericRule()
    {
        $this->localRules[] = 'numeric';

        return $this->applyMinAndMaxFromFunctionArguments(func_get_args());
    }

    protected function uniqueRule($table, $column = 'NULL')
    {
        $table = $this->parseTableName($table);

        return $this->applyProxyRule('unique', [$table, $column]);
    }

    protected function parseTableName($table)
    {
        if ($table instanceof \Illuminate\Database\Eloquent\Model) {
            return $table->getTable();
        }

        if (is_string($table) && class_exists($table)) {
            return (new $table)->getTable();
        }

        return $table;
    }

    protected function rawRule($rules)
    {
        $this->localRules[] = $rules;

        return $this;
    }

    protected function foreignKeyRule($class)
    {
        $instance = is_string($class) ? new $class : $class;

        return $this->exists($instance->getTable(), $instance->getKeyName());
    }
}
