<?php declare(strict_types=1);

namespace Tests\LeanPHP;

use LeanPHP\Validation\Rule;
use LeanPHP\Validation\RuleInterface;
use LeanPHP\Validation\Validates;
use LeanPHP\Validation\Validator;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;

final class ValidatorTest extends TestCase
{
    public function test_validate_array(): void
    {
        $validator = (new Validator())
            ->setData([
                'string' => 'the string',
                'int' => 123,
                'stdClass' => new stdClass(),
                'date' => '2021-11-21',
                'array' => [0, 1, 2],
            ])
            ->setRules([
                'string' => ['optional', 'string', 'length:10'],
                'int' => ['not-null', 'int', '>:122'],
                'stdClass' => ['exists', 'instanceof:stdClass'],
                'date' => ['date'],
                'array' => ['array', 'length:3'],
            ]);

        $isValid = $validator->isValid();

        self::assertSame([], $validator->getMessages());
        self::assertTrue($isValid);
    }

    public function test_validate_object(): void
    {
        $object = new TestEntityToValidate();
        $object->setProperties([
            'publicProperty' => 'publicProperty',
            'protectedProperty' => 'protectedProperty',
            'privateProperty' => 'privateProperty',

            'publicStaticProperty' => 'publicStaticProperty',
            'protectedStaticProperty' => 'protectedStaticProperty',
            'privateStaticProperty' => 'privateStaticProperty',
        ]);

        self::assertSame('publicProperty', $object->publicProperty);
        self::assertSame('publicStaticProperty', $object::$publicStaticProperty);

        $validator = (new Validator())
            ->setData($object)
            ->setRules([
                'publicProperty' => ['===:publicProperty'],
                'protectedProperty' => ['===:protectedProperty'],
                'privateProperty' => ['===:privateProperty'],

                'publicStaticProperty' => ['===:publicStaticProperty'],
                'protectedStaticProperty' => ['===:protectedStaticProperty'],
                'privateStaticProperty' => ['===:privateStaticProperty'],
            ]);

        $isValid = $validator->isValid();

        self::assertSame([], $validator->getMessages());
        self::assertTrue($isValid);
    }

    // --------------------------------------------------
    // test all the rules

    public function test_is_method_rules(): void
    {
        $validator = (new Validator())
            ->setData([
                'string' => '123',
                'int' => 123,
                'float' => 12.3,
                'bool' => true,
                'object' => new stdClass(),
                'closure' => function (): void {},
                'array' => [0, 1, 2],
                'null' => null,
            ])
            ->setRules([
                'string' => ['string', 'numeric', 'scalar'],
                'int' => ['int', 'scalar'],
                'float' => ['float', 'scalar'],
                'bool' => ['bool', 'scalar'],
                'object' => ['object'],
                'closure' => ['callable'],
                'array' => ['array', 'iterable', 'countable'],
                'null' => ['null', 'optional'],
            ]);

        $isValid = $validator->isValid();

        self::assertSame([], $validator->getMessages());
        self::assertTrue($isValid);
    }

    public function test_parametrized_rules(): void
    {
        $validator = (new Validator())
            ->setData([
                'instanceof' => new Validator(),
                'regex' => '123.abcd',

                '>=' => 12,
                '>' => 12,
                '<=' => 12,
                '<' => 12,

                'minlength_string' => '12',
                'minlength_countable' => [1, 2],
                'maxlength_string' => '12',
                'maxlength_countable' => [1, 2],
                'length_string' => '12',
                'length_countable' => [1, 2],

                '==' => 12,
                '===' => '12',

                'same-as' => '123.abcd',
                'same-as2' => 12,
            ])
            ->setRules([
                'instanceof' => ['instanceof:' . Validator::class],
                'regex' => ['regex:/^[0-9]{3}\.[a-z]{1}/'],

                '>=' => ['>=:12'],
                '>' => ['>:11'],
                '<=' => ['<=:12'],
                '<' => ['<:13'],

                'minlength_string' => ['minLength:2'],
                'minlength_countable' => ['minLength:2'],
                'maxlength_string' => ['maxLength:2'],
                'maxlength_countable' => ['maxLength:2'],
                'length_string' => ['length:2'],
                'length_countable' => ['length:2'],

                // '==' => ['==:12'],
                '===' => ['===:12'],

                'same-as' => ['sameAs:regex'],
                'same-as2' => ['sameAs:=='],
            ]);

        $isValid = $validator->isValid();

        self::assertSame([], $validator->getMessages());
        self::assertTrue($isValid);
    }

    public function test_other_builtin_rules(): void
    {
        $validator = (new Validator())
            ->setData([
                'string' => '123',
                'uuid' => '0e9cb36e-a905-4f42-bbbe-9936353734d2',
                'uuid2' => '0e9cb36ea9054f42bbbe9936353734d2',
                'email' => 'some.e+mail@site.ab.cd',
                'date' => '1970-01-01',
                'datetime' => '1970-01-01 00:00:00',
            ])
            ->setRules([
                'optional' => ['optional', 'exists'],
                'string' => ['not-null'],
                'uuid' => ['uuid'],
                'uuid2' => ['uuid'],
                'email' => ['email'],
                'date' => ['date'],
                'datetime' => ['date'],
            ]);

        $isValid = $validator->isValid();

        self::assertSame([], $validator->getMessages());
        self::assertTrue($isValid);
    }

    public function test_get_validated_array(): void
    {
        $validatedData = (new Validator())
            ->setData([
                'string' => '123',
                'uuid' => '0e9cb36e-a905-4f42-bbbe-9936353734d2',
                'uuid2' => '0e9cb36ea9054f42bbbe9936353734d2',
                'email' => 'some.e+mail@site.ab.cd',
                'date' => '1970-01-01',
                'datetime' => '1970-01-01 00:00:00',
            ])
            ->setRules([
                'uuid2' => ['uuid'],
                'email' => ['email'],
            ])
            ->getValidatedData();

        $expected = [
            'uuid2' => '0e9cb36ea9054f42bbbe9936353734d2',
            'email' => 'some.e+mail@site.ab.cd',
        ];
        self::assertSame($expected, $validatedData);
    }

    public function test_get_validated_stdclass(): void
    {
        $initialData = new stdClass();
        $initialData->uuid2 = '0e9cb36ea9054f42bbbe9936353734d2';
        $initialData->email = 'some.e+mail@site.ab.cd';
        $initialData->date = '1970-01-01';
        $initialData->datetime = '1970-01-01 00:00:00';

        $validatedData = (new Validator())
            ->setData($initialData)
            ->setRules([
                'email' => ['email'],
                'date' => ['date'],
            ])
            ->getValidatedData();

        $expected = new stdClass();
        $expected->email = 'some.e+mail@site.ab.cd';
        $expected->date = '1970-01-01';

        self::assertSame((array) $expected, (array) $validatedData);
        self::assertNotSame($initialData, $validatedData);
        self::assertNotSame($expected, $validatedData);
    }

    public function test_validates_attribute_typed_not_initialized(): void
    {
        $validator = (new Validator())
            ->setData(new TestValidatesAttributeTypedNotInitialized());

        self::assertFalse($validator->isValid());

        $expected = [
            'typedNotInitialized' => ["The value for key 'typedNotInitialized' doesn't pass the 'not-null' validation rule."],
        ];
        $actual = $validator->getMessages();
        self::assertSame($expected, $actual);
    }

    public function test_validates_attribute_typed_non_nullable_marked_optional(): void
    {
        $this->expectException(LogicException::class);

        (new Validator())
            ->setData(new TestValidatesAttributeTypedNonNullableMarkedOptional())
            ->isValid();
    }

    public function test_validates_attribute_typed_nullable_marked_not_null(): void
    {
        $this->expectException(LogicException::class);

        (new Validator())
            ->setData(new TestValidatesAttributeTypedNullableMarkedNotNull())
            ->isValid();
    }

    public function test_validates_attribute(): void
    {
        $data = new TestValidatesAttribute();
        $data->date = '2022-04-01';
        $data->email = 'florent@florent.fr';
        $validator = (new Validator())->setData($data);
        self::assertTrue($validator->isValid());

        $data = new TestValidatesAttribute();
        $data->date = '2022-04-01';
        $validator = (new Validator())->setData($data);
        self::assertTrue($validator->isValid());

        $data = new TestValidatesAttribute();
        $data->date = '2022-04-01';
        $data->email = 'not an email';
        $validator = (new Validator())->setData($data);
        self::assertFalse($validator->isValid());
    }
}

final class TestEntityToValidate
{
    public string $publicProperty = '';
    private string $protectedProperty = '';
    private string $privateProperty = '';

    public static string $publicStaticProperty = '';
    private static string $protectedStaticProperty = '';
    private static string $privateStaticProperty = '';

    /**
     * @param array<string, string> $data
     */
    public function setProperties(array $data): void
    {
        foreach ($data as $key => $value) {
            if (str_contains($key, 'Dynamic')) {
                $this->{$key} = $value; // @phpstan-ignore-line

                continue;
            }

            $reflectionProperty = new ReflectionProperty(self::class, $key);
            if ($reflectionProperty->isStatic()) {
                $reflectionProperty->setValue($this, $value);
            } else {
                $this->{$key} = $value; // @phpstan-ignore-line
            }
        }
    }
}

final class TestRule implements RuleInterface
{
    public function passes(string $key, mixed $value): bool
    {
        return true;
    }

    public function getMessage(string $key): ?string
    {
        return 'Some message';
    }
}

final class TestValidatesAttributeTypedNotInitialized
{
    #[Validates]
    public string $typedNotInitialized;
}

final class TestValidatesAttributeTypedNonNullableMarkedOptional
{
    #[Validates([Rule::optional])]
    public string $typedNonNullableMarkedOptional; // throws LogicException because illugical
}

final class TestValidatesAttributeTypedNullableMarkedNotNull
{
    #[Validates([Rule::notNull])]
    public ?string $typedNullableMarkedNotNull = null; // throws LogicException because illugical
}

final class TestValidatesAttribute
{
    #[Validates([Rule::date, 'regex' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/'])]
    public string $date;

    #[Validates([Rule::email, 'minLength' => 5, 'maxLength' => 50])]
    public ?string $email = null;
}
