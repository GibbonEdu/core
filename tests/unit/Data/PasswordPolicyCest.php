<?php
namespace Gibbon\Data;

class PasswordPolicyCest
{

    private static $perfectPassword = 'abcdABCD1234!@#$';

    public function _before(\UnitTester $I)
    {
    }

    public function testNilPolicy(\UnitTester $I)
    {
        $policy = PasswordPolicy::createNilPolicy();

        $I->expectThrowable(\Exception::class, function () use ($policy) {
            $policy->validate(static::$perfectPassword);
        });

        $I->expectThrowable(\Exception::class, function () use ($policy) {
            $policy->describe();
        });
    }

    public function testPolicyWithAlphaRule(\UnitTester $I)
    {
        $policy = new PasswordPolicy(
            true,
            false,
            false,
            0
        );

        $I->assertFalse(
            $policy->validate('abcd1234!@#$'),
            'Password with $alpha set to true should only allow password with both upper and lower cases'
        );
        $I->assertContains(
            'Require at least one uppercase letter.',
            $policy->evaluate('abcd1234!@#$')
        );

        $I->assertFalse(
            $policy->validate('ABCD1234!@#$'),
            'Password with $alpha set to true should only allow password with both upper and lower cases'
        );
        $I->assertContains(
            'Require at least one lowercase letter.',
            $policy->evaluate('ABCD1234!@#$')
        );

        $I->assertTrue(
            $policy->validate(static::$perfectPassword),
            'Should allow perfect password to pass'
        );
        $I->assertEmpty(
            $policy->evaluate(static::$perfectPassword),
            'Should have no complaint to perfect password'
        );

        $I->assertContains(
            'Contain at least one lowercase letter, and one uppercase letter.',
            $policy->describe(),
            '::describe should include policy description of the rule'
        );
    }

    public function testPolicyWithNumericRule(\UnitTester $I)
    {
        $policy = new PasswordPolicy(
            false,
            true,
            false,
            0
        );

        $I->assertFalse(
            $policy->validate('abcdABCD!@#$'),
            'Password with $numeric set to true should only allow password with at least 1 number character'
        );
        $I->assertContains(
            'Require at least one number.',
            $policy->evaluate('abcdABCD!@#$')
        );

        $I->assertTrue(
            $policy->validate(static::$perfectPassword),
            'Should allow perfect password to pass'
        );
        $I->assertEmpty(
            $policy->evaluate(static::$perfectPassword),
            'Should have no complaint to perfect password'
        );

        $I->assertContains(
            'Contain at least one number.',
            $policy->describe(),
            '::describe should include policy description of the rule'
        );
    }

    public function testPolicyWithPuntuationRule(\UnitTester $I)
    {
        $policy = new PasswordPolicy(
            false,
            false,
            true,
            0
        );

        $I->assertFalse(
            $policy->validate('abcdABCD1234'),
            'Password with $puntuation set to true should only allow password with at least 1 puncuation character'
        );

        $I->assertTrue(
            $policy->validate('abcdABCD1234!'),
            'Password with $puntuation set to allow password with a punctuation'
        );

        $I->assertTrue(
            $policy->validate('abcdABCD1234 '),
            'Password with $puntuation set to allow password with a space'
        );

        $I->assertTrue(
            $policy->validate(static::$perfectPassword),
            'Should allow perfect password to pass'
        );

        $I->assertContains(
            'Contain at least one non-alphanumeric character (e.g. a punctuation mark or space).',
            $policy->describe(),
            '::describe should include policy description of the rule'
        );
    }

    public function testPolicyWithMinlength(\UnitTester $I)
    {
        $minLength = 14;
        $policy = new PasswordPolicy(
            false,
            false,
            false,
            $minLength
        );

        $I->assertFalse(
            $policy->validate('abcdABCD1234'),
            sprintf('Password with $minLength set to %d should only allow password with at least %d characters', $minLength, $minLength)
        );

        $I->assertTrue(
            $policy->validate(static::$perfectPassword),
            'Should allow perfect password to pass'
        );

        $I->assertContains(
            sprintf('Must be at least %d characters in length.', $minLength),
            $policy->describe(),
            '::describe should include policy description of the rule'
        );
    }

}
