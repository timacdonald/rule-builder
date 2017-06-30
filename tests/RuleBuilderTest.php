<?php

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use TiMacDonald\Validation\Rule;

class RuleBuilderTest extends TestCase
{
    public function test_static_call_creates_an_instance()
    {
        $this->assertInstanceOf(Rule::class, Rule::string());
    }

    public function test_can_extend_userland_rules()
    {
        Rule::extend('one');
        Rule::extend('two', 'three');
        Rule::extendWithRules(['three', 'four'], 'five');

        $rules = Rule::one()->two()->three()->four()->five()->get();

        $this->assertEquals(['one', 'two', 'three', 'four', 'five'], $rules);
    }

    public function test_can_pass_parameters_as_mix_of_arrays_and_values()
    {
        $base = Rule::string(1, '2')->get();

        $this->assertEquals(
            Rule::string([1], '2')->get(), $base
        );

        $this->assertEquals(
            Rule::string([1, 2])->get(), $base
        );
    }

    public function test_applying_non_existant_rule_throws_exception()
    {
        $this->expectException(\Exception::class);

        Rule::doesNotExist();
    }

    // public function test_can_apply_all_basic_rules_without_arguments()
    // {
    //     $methods = array_map(function ($rule) {
    //         return Str::camel($rule);
    //     }, Rule::BASIC_RULES);

    //     foreach ($methods as $method) {
    //         Rule::$method();
    //     }
    // }
}
