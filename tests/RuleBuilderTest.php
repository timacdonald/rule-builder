<?php

namespace Tests;

use stdClass;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use TiMacDonald\Validation\Rule;

class RuleBuilderTest extends TestCase
{
    function setUp()
    {
        // we'll use this to test rules, as if we rely on the actual rules we
        // might run into unexpected results as the rule may have a custom
        // rule method that changes the default behaviour. Just handy!
        Rule::extend('basic', 'basic_alternative');
    }

    function test_static_call_creates_an_instance()
    {
        $this->assertInstanceOf(Rule::class, Rule::string());
    }

    function test_can_extend_rules()
    {
        Rule::extend('extend_one');
        Rule::extendWithRules('extend_two');

        $this->assertEquals(
            ['extend_one', 'extend_two',],
            Rule::extendOne()->extendTwo()->get()
        );
    }

    function test_can_extend_rules_passing_as_arguments()
    {
        $rules = ['extended_rule_by_argument_one', 'extended_rule_by_argument_two'];

        Rule::extend(...$rules);

        $this->assertEquals(
            $rules,
            Rule::extendedRuleByArgumentOne()->extendedRuleByArgumentTwo()->get()
        );
    }

    function test_can_extend_rules_passing_as_array()
    {
        $rules = ['extended_rule_by_array_one', 'extended_rule_by_array_two'];

        Rule::extend($rules);

        $this->assertEquals(
            $rules,
            Rule::extendedRuleByArrayOne()->extendedRuleByArrayTwo()->get()
        );
    }

    function test_can_pass_arguments()
    {
        $this->assertEquals(
            ['basic:1,2'],
            Rule::basic(1, '2')->get()
        );
    }

    function test_can_pass_arguments_as_array()
    {
        $this->assertEquals(
            ['basic:1,2'],
            Rule::basic([1, '2'])->get()
        );
    }

    function test_can_pass_arguments_as_mix_of_arrays_and_arguments()
    {
        $this->assertEquals(
            Rule::basic([1], '2')->get(),
            Rule::basic([1, 2])->get()
        );
    }

    function test_applying_non_existant_rule_throws_exception()
    {
        $this->expectException(\Exception::class);

        Rule::nonExistantRule();
    }

    function test_to_string_returns_formatted_rules()
    {
        $this->assertEquals(
            'basic:1|basic_alternative',
            (string) Rule::basic(1)->basicAlternative()
        );
    }

    function test_get_method_returns_rules_as_array()
    {
        $this->assertEquals(
            ['basic:1', 'basic_alternative'],
            Rule::basic(1)->basicAlternative()->get()
        );
    }

    function test_can_proxy_dimensions_rule()
    {
        $constraints = ['height' => 100, 'width' => 100];

        $this->assertEquals(
            (string) \Illuminate\Validation\Rule::dimensions($constraints),
            (string) Rule::dimensions($constraints)
        );
    }

    function test_can_proxy_exists_rule()
    {
        $arguments = ['table_name', 'column_name'];

        $this->assertEquals(
            (string) \Illuminate\Validation\Rule::exists(...$arguments),
            (string) Rule::exists(...$arguments)
        );
    }

    function test_can_proxy_in_rule()
    {
        $arguments = [1, 2, 3];

        $this->assertEquals(
            (string) \Illuminate\Validation\Rule::in(...$arguments),
            (string) Rule::in(...$arguments)
        );
    }

    function test_can_proxy_not_in_rule()
    {
        $arguments = [1, 2, 3];

        $this->assertEquals(
            (string) \Illuminate\Validation\Rule::notIn(...$arguments),
            (string) Rule::notIn(...$arguments)
        );
    }

    function test_can_chain_proxied_rules()
    {
        $table = 'table_name';
        $id = 23;

        $this->assertEquals(
            (string) \Illuminate\Validation\Rule::Unique($table)->ignore($id),
            (string) Rule::unique($table)->ignore($id)
        );
    }

    function test_can_pass_string_as_date()
    {
        $date = 'tomorrow';

        $this->assertEquals(
            ['before:'.$date],
            Rule::before($date)->get()
        );
    }

    function test_can_pass_carbon_instance_as_date()
    {
        $date = Carbon::now();

        $this->assertEquals(
            ['before:'.$date->toIso8601String()],
            Rule::before($date)->get()
        );
    }

    function test_custom_active_url_rule()
    {
        $this->assertEquals(
            ['active_url', 'max:10'],
            Rule::activeUrl(10)->get()
        );
    }

    function test_custom_add_rule_with_object()
    {
        $object = new stdClass;

        $this->assertEquals(
            [$object],
            Rule::add($object)->get()
        );
    }

    function test_custom_add_rule_with_string()
    {
        $this->assertEquals(
            ['basic'],
            Rule::add('basic')->get()
        );
    }

    function test_custom_add_rule_with_array()
    {
        $this->assertEquals(
            ['basic', 'basic_alternative'],
            Rule::add(['basic', 'basic_alternative'])->get()
        );
    }

    function test_custom_after_rule()
    {
        $date = Carbon::now();

        $this->assertEquals(
            ['after:'.$date->toIso8601String()],
            Rule::after($date)->get()
        );
    }

    function test_custom_after_or_equal_rule()
    {
        $date = Carbon::now();

        $this->assertEquals(
            ['after_or_equal:'.$date->toIso8601String()],
            Rule::afterOrEqual($date)->get()
        );
    }

    function test_custom_alpha_rule()
    {
        $this->assertEquals(
            ['alpha', 'min:1', 'max:10'],
            Rule::alpha(1, 10)->get()
        );
    }

    function test_custom_alpha_dash_rule()
    {
        $this->assertEquals(
            ['alpha_dash', 'min:1', 'max:10'],
            Rule::alphaDash(1, 10)->get()
        );
    }

    function test_custom_alpha_num_rule()
    {
        $this->assertEquals(
            ['alpha_num', 'min:1', 'max:10'],
            Rule::alphaNum(1, 10)->get()
        );
    }

    function test_custom_array_rule()
    {
        $this->assertEquals(
            ['array', 'min:1', 'max:10'],
            Rule::array(1, 10)->get()
        );
    }

    function test_custom_before_rule()
    {
        $date = Carbon::now();

        $this->assertEquals(
            ['before:'.$date->toIso8601String()],
            Rule::before($date)->get()
        );
    }

    function test_custom_before_or_equal_rule()
    {
        $date = Carbon::now();

        $this->assertEquals(
            ['before_or_equal:'.$date->toIso8601String()],
            Rule::beforeOrEqual($date)->get()
        );
    }

    function test_custom_character_rule()
    {
        $this->assertEquals(
            ['alpha', 'min:1', 'max:1'],
            Rule::character()->get()
        );
    }

    function test_custom_digits_max_rule()
    {
        $this->assertEquals(
            ['digits_between:0,10'],
            Rule::digitsMax(10)->get()
        );
    }

    function test_custom_email_rule()
    {
        $this->assertEquals(
            ['email', 'max:10'],
            Rule::email(10)->get()
        );
    }

    function test_custom_file_rule()
    {
        $this->assertEquals(
            ['file', 'max:10'],
            Rule::file(10)->get()
        );
    }

    function test_custom_foreign_key_rule_with_class()
    {
        $this->assertEquals(
            ['exists:'.EloquentDummy::TABLE_NAME.','.EloquentDummy::KEY_NAME],
            Rule::foreignKey(EloquentDummy::class)->get()
        );
    }

    function test_custom_foreign_key_rule_with_instance()
    {
        $this->assertEquals(
            ['exists:'.EloquentDummy::TABLE_NAME.','.EloquentDummy::KEY_NAME],
            Rule::foreignKey(new EloquentDummy)->get()
        );
    }

    function test_custom_image_rule()
    {
        $this->assertEquals(
            ['image', 'max:10'],
            Rule::image(10)->get()
        );
    }

    function test_custom_interger_rule()
    {
        $this->assertEquals(
            ['integer', 'min:1', 'max:10'],
            Rule::integer(1, 10)->get()
        );
    }

    function test_custom_set_helper_when_zero()
    {
        $this->assertEquals(
            ['integer', 'min:0'],
            Rule::integer(0)->get()
        );
    }

    function test_custom_json_rule()
    {
        $this->assertEquals(
            ['json', 'max:10'],
            Rule::json(10)->get()
        );
    }

    function test_custom_numeric_rule()
    {
        $this->assertEquals(
            ['numeric', 'min:1', 'max:10'],
            Rule::numeric(1, 10)->get()
        );
    }

    function test_custom_string_rule()
    {
        $this->assertEquals(
            ['string', 'min:1', 'max:10'],
            Rule::string(1, 10)->get()
        );
    }

    function test_custom_url_rule()
    {
        $this->assertEquals(
            ['url', 'max:10'],
            Rule::url(10)->get()
        );
    }

    function test_custom_url_with_hostname_extension_rule_no_extension()
    {
        $this->assertEquals(
            ['url', 'regex:/[^:\s]*:\/\/[^\#\?\/\.]*(\.)/'],
            Rule::urlWithHostExtension()->get()
        );
    }

    function test_custom_url_with_hostname_extension_rule_with_single_extension()
    {
        $this->assertEquals(
            ['url', 'regex:/[^:\s]*:\/\/[^\#\?\/\.]*(\.com\.au)/'],
            Rule::urlWithHostExtension('.com.au')->get()
        );
    }

    function test_custom_url_with_hostname_extension_rule_with_multiple_extension()
    {
        $this->assertEquals(
            ['url', 'regex:/[^:\s]*:\/\/[^\#\?\/\.]*(\.org\.au)|(\.com\.au)/'],
            Rule::urlWithHostExtension(['.org.au', '.com.au'])->get()
        );
    }

    function test_custom_url_with_scheme_rule_with_single_scheme()
    {
        $this->assertEquals(
            ['url', 'regex:/^(https:\/\/)/'],
            Rule::urlWithScheme('https')->get()
        );
    }

    function test_custom_url_with_scheme_rule_with_multiple_schemes()
    {
        $this->assertEquals(
            ['url', 'regex:/^(https:\/\/)|(fb:\/\/)/'],
            Rule::urlWithScheme(['https', 'fb'])->get()
        );
    }

    function test_custom_raw_rule_with_string()
    {
        $rules = 'string|min:1|max:10';

        $this->assertEquals(
            explode('|', $rules),
            Rule::raw($rules)->get()
        );
    }

    function test_custom_raw_rule_with_array()
    {
        $rules = ['string', 'min:1', 'max:10'];

        $this->assertEquals(
            $rules,
            Rule::raw($rules)->get()
        );
    }

    function test_custom_raw_rule_with_object()
    {
        $object = new stdClass;

        $this->assertEquals(
            [$object],
            Rule::raw($object)->get()
        );
    }

    function test_custom_unique_rule_with_class()
    {
        $column = 'column_name';

        $this->assertEquals(
            (string) \Illuminate\Validation\Rule::unique(EloquentDummy::TABLE_NAME, $column),
            (string) Rule::unique(EloquentDummy::class, $column)
        );
    }

    function test_custom_unique_rule_with_instance()
    {
        $column = 'column_name';

        $this->assertEquals(
            (string) \Illuminate\Validation\Rule::unique(EloquentDummy::TABLE_NAME, $column),
            (string) Rule::unique(new EloquentDummy, $column)
        );
    }

    function test_custom_unique_rule_with_table_name()
    {
        $column = 'column_name';

        $this->assertEquals(
            (string) \Illuminate\Validation\Rule::unique(EloquentDummy::TABLE_NAME, $column),
            (string) Rule::unique(EloquentDummy::TABLE_NAME, $column)
        );
    }

    function test_custom_when_rule_applies_if_condition_true_using_boolean()
    {
        $this->assertEquals(
            ['string'],
            Rule::when(true, function ($rule) {
                $rule->string();
            })->get()
        );
    }

    function test_custom_when_rule_doesnt_apply_if_condition_false_using_boolean()
    {
        $this->assertEquals(
            [],
            Rule::when(false, function ($rule) {
                $rule->string();
            })->get()
        );
    }

    function test_custom_when_rule_applies_if_condition_true_using_closure()
    {
        $this->assertEquals(
            ['string'],
            Rule::when(function () {
                return true;
            }, function ($rule) {
                $rule->string();
            })->get()
        );
    }

    function test_custom_when_rule_doesnt_apply_if_condition_false_using_closure()
    {
        $this->assertEquals(
            [],
            Rule::when(function () {
                return false;
            }, function ($rule) {
                $rule->string();
            })->get()
        );
    }
}
