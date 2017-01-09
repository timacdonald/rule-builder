<?php

namespace TiMacDonald\Rule;

use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;

class Builder
{
    /**
     * Rules we will handle locally.
     *
     * @var array
     */
    const LOCALLY_HANDLED_RULES = [
        // rules
        'accepted',
        'active_url',
        'alpha',
        'alpha_dash',
        'alpha_num',
        'array',
        'boolean',
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
        // rules with arguments
        'after',
        'before',
        'between',
        'date_format',
        'different',
        'digits',
        'digits_between',
        'in_array',
        'max',
        'mimetypes',
        'mimes',
        'min',
        'regex',
        'required_with',
        'required_with_all',
        'required_without',
        'required_without_all',
        'same',
        'size',
        // rules with identifier and arguments
        'required_if',
        'required_unless',
        // flags
        'bail',
        'sometimes',
    ];

    /**
     * Rules to proxy to Laravel rule classes.
     *
     * @var array
     */
    const PROXIED_RULES = [
        'dimensions',
        'exists',
        'in',
        'not_in',
        'unique'
    ];

    /**
     * Method call prefixes that start a new rule.
     *
     * @var array
     */
    const CHAINING_METHOD_PREFIXS = [
        'is',
        'has',
        'allowed',
        'matches'
    ];

    /**
     * Local rules.
     *
     * @var array
     */
    protected $localRules = [];

    /**
     * Proxied rules.
     *
     * @var array
     */
    protected $proxiedRules = [];

    /**
     * New up instance and call method on it to start chaining.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return static
     */
    public static function __callStatic($method, $arguments)
    {
        return call_user_func_array([new static(), $method], $arguments);
    }

    /**
     * Delgate undefined method calls if we can handle to rules.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     */
    public function __call($method, $arguments)
    {
        $method = $this->removeMethodPrefix($method);

        $rule = Str::snake($method);

        if ($this->isNewLocalRule($rule)) {
            return $this->addNewLocalRule($rule, $arguments);

        } else if ($this->isNewProxyRule($rule)) {
            return $this->addNewProxyRule($method, $arguments);

        } else if ($this->canCallMethodOnLatestProxyRule($method)) {
            return $this->callMethodOnLatestProxyRule($method, $arguments);
        }

        throw new BadMethodCallException('Unable to handle or proxy the method '.$method.'(). If it\'s proxied, ensure it is called directly after the proxy rule.');
    }

    /**
     * Determine if rule is a new proxy rule to be created.
     *
     * @param  string  $rule
     * @return bool
     */
    protected function isNewProxyRule($rule)
    {
        return in_array($rule, static::PROXIED_RULES);
    }

    /**
     * Determine if rule is in our list of local rules.
     *
     * @param  string  $rule
     * @return bool
     */
    protected function isNewLocalRule($rule)
    {
        return in_array($rule, static::LOCALLY_HANDLED_RULES);
    }

    /**
     * Add a new local rule.
     *
     * @param  string  $rule
     * @param  array  $arguments
     * @return $this
     */
    protected function addNewLocalRule($rule, $arguments)
    {
        if ([] !== $arguments) {
            $rule .= ':'.implode(',', Arr::flatten($arguments));
        }

        $this->localRules[] = $rule;

        return $this;
    }

    /**
     * Call the method on the latest proxy rule we have stored.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     */
    protected function callMethodOnLatestProxyRule($method, $arguments)
    {
        $proxy = Arr::last($this->proxiedRules);

        call_user_func_array([$proxy, $method], $arguments);

        return $this;
    }

    /**
     * Determine if the given method can be called on latest proxy we have
     * stored.
     *
     * @param  string  $method
     * @return bool
     */
    protected function canCallMethodOnLatestProxyRule($method)
    {
        $proxy = Arr::last($this->proxiedRules);

        return null !== $proxy && method_exists($proxy, $method);
    }

    /**
     * Chaining method prefixes, as a collection.
     *
     * @return Illuminate\Support\Collection
     */
    protected function chainingMethodPrefixes()
    {
        static $prefixes;

        if (null === $prefixes) {
            $prefixes = Collection::make(static::CHAINING_METHOD_PREFIXS);
        }

        return $prefixes;
    }

    /**
     * Removed method prefix.
     *
     * @param  string  $method
     * @return string
     */
    protected function removeMethodPrefix($method)
    {
        $prefix = $this->chainingMethodPrefixes()->first(function ($prefix) use ($method) {
            return 0 === strpos($method, $prefix);
        });

        if (null === $prefix) {
            return $method;
        }

        return lcfirst(ltrim($method, $prefix));
    }

    /**
     * Create a new rule by proxying to Laravel built in validation rules, and
     * add store the new proxied rule.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     */
    protected function addNewProxyRule($method, $arguments)
    {
        $proxyRule = call_user_func_array([Rule::class, $method], $arguments);

        $this->proxiedRules[] = $proxyRule;

        return $this;
    }

    /**
     * Retrieve the validation string.
     *
     * @return
     */
    public function get()
    {
        return array_merge($this->localRules, $this->proxiedRules);
    }

    /**
     * Cast object to string.
     *
     * @return string
     */
    public function __toString()
    {
        $proxyRules = array_map(function ($rule) {
            return (string) $rule;
        }, $this->proxiedRules);

        return rtrim(implode('|', $this->localRules).'|'.implode('|', $proxyRules), '|');
    }
}
