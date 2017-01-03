<?php

namespace TiMacDonald\Rule;

use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class Builder
{
    /**
     * Rules we will handle.
     *
     * @var array
     */
    const HANDLES_RULES = [
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
     * Rules to proxy to Laravel rule class.
     *
     * @var array
     */
    const PROXY_RULES = [
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
        'allowed',
        'has',
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
     * New up instance and call method on it.
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
     * Delgate undefined method calls.
     *
     * @param  string  $calledMethod
     * @param  array  $arguments
     * @return $this
     */
    public function __call($calledMethod, $arguments)
    {
        $method = $this->removeMethodPrefix($calledMethod);

        if ($this->canHandleMethod($method)) {
            return $this->handleMethod($method, $arguments);
        }

        return $this->proxyMethod($method, $arguments);
    }

    /**
     * Proxy method.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     */
    protected function proxyMethod($method, $arguments)
    {
        $rule = $this->ruleForMethod($method);

        if ($this->canProxyRule($rule)) {
            return $this->addProxyRule($method, $arguments);
        }

        return $this->applyMethodToLatestProxy($method, $arguments);
    }

    /**
     * Apply method to latest proxied rule.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     *
     * @throws BadMethodCallException
     */
    protected function applyMethodToLatestProxy($method, $arguments)
    {
        $latestProxy = end($this->proxiedRules);

        if ($this->canCallMethodOnProxy($method, $latestProxy)) {

            call_user_func_array([$latestProxy, $method], $arguments);

            return $this;
        }

        throw new BadMethodCallException('Unable to handle or proxy the method '.$method.'()');
    }

    /**
     * Determine if method can be called on proxy.
     *
     * @param  string  $method
     * @param  mixed  $proxy
     * @return bool
     */
    protected function canCallMethodOnProxy($method, $proxy)
    {
        return null !== $proxy && method_exists($proxy, $method);
    }

    /**
     * Handle method.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     */
    protected function handleMethod($method, $arguments)
    {
        $rule = $this->ruleForMethod($method);

        return $this->addRule($rule, $arguments);
    }

    /**
     * Determine if method can be handled.
     *
     * @param  string  $method
     * @return bool
     */
    protected function canHandleMethod($method)
    {
        $rule = $this->ruleForMethod($method);

        return in_array($rule, static::HANDLES_RULES);
    }

    /**
     * The rule for the given method.
     *
     * @param  string  $method
     * @return string
     */
    protected function ruleForMethod($method)
    {
        return Str::snake($method);
    }

    /**
     * Removed method prefix.
     *
     * @param  string  $method
     * @return string
     */
    protected function removeMethodPrefix($method)
    {
        foreach (static::CHAINING_METHOD_PREFIXS as $prefix) {
            if (0 === strpos($method, $prefix)) {
                return lcfirst(ltrim($method, $prefix));
            }
        }

        return $method;
    }

    /**
     * Determine if rule can be proxied.
     *
     * @param  string  $rule
     * @return bool
     */
    protected function canProxyRule($rule)
    {
        return in_array($rule, static::PROXY_RULES);
    }

    /**
     * Add rule.
     *
     * @param  string  $rule
     * @param  array  $arguments
     * @return bool
     */
    public function addRule($rule, $arguments = [])
    {
        $this->localRules[$rule] = Arr::flatten($arguments);

        return $this;
    }

    /**
     * Add proxy rule.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     */
    public function addProxyRule($method, $arguments)
    {
        $this->proxiedRules[] = call_user_func_array([Rule::class, $method], $arguments);

        return $this;
    }

    /**
     * Convert parameters to string.
     *
     * @param  array  $parameters
     * @return string
     */
    protected function parametersToString($parameters)
    {
        if ([] !== $parameters) {
            return ':'.implode(',', $parameters);
        }

        return '';
    }

    /**
     * Retrieve the validation string.
     *
     * @return
     */
    public function get()
    {
        $rules = $this->compileLocalRules().$this->compileProxiedRules();

        return rtrim($rules, '|');
    }

    /**
     * Compile local rules to string.
     *
     * @return string
     */
    /**
     * Compile local rules to string.
     *
     * @return string
     */
    protected function compileLocalRules()
    {
        $compiledRules = '';

        foreach ($this->localRules as $key => $parameters) {
            $compiledRules .= $key.$this->parametersToString($parameters).'|';
        }

        return $compiledRules;
    }

    /**
     * Compile proxied rules.
     *
     * @string
     */
    protected function compileProxiedRules()
    {
        $compiledRules = '';

        foreach ($this->proxiedRules as $proxiedRule) {
            $compiledRules .= $proxiedRule.'|';
        }

        return $compiledRules;
    }
}
