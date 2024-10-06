# Validation

The Validator component allow to make sure an array or an object contains some keys/properties and that their values match some constraints.

The component has some built-in rules, but you can provide your own as callable or objects that extends the `RuleInterface`.

Examples : 
```php
$validator = (new Validator())
    ->setData([
        'int' => 123,
        'uuid' => '0e9cb36e-a905-4f42-bbbe-9936353734d2',
        'email' => 'some.e+mail@site.ab.cd',
        'in' => '1',
        'array' => [1, 2],
    ])
    ->setRules([
        'int' => ['no-null', 'int'],
        'uuid' => ['uuid'],
        'email' => ['email'],
        'in' => ['in:1,2,3'],
        'array' => ['size:2'],
    ]);

if ($validator->isValid()) {
    //  \o/
} else {
    $validator->getMessages();
}
```

When the data is an object instance, you can validate any kind of property, including private or dynamic ones.

Example with a self-validating DTOs :
```php
final readonly class MyDTO
{
    public function __construct(
        public string $date,
        public array $arrayProp = [],
    ) {
        $rules = [
            'date' => ['date', fn (string $key, string $value): bool => preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value) === 1], // note that this can be achieved with the regex: rule and that the regex is slightly wrong if we want to validate the date format
            'email' => ['email', 'min-length:5', 'max-length:50'],
        ];
        
        (new Validator())
            ->setData($this)
            ->setRules($rules)
            ->throwIfNotValid();
    }
}

new MyDTO('2021-11-23', []);
```

Once you have passed your data and your rules, you must call either `isValid(): bool` or `throwIfNotValid(): self|nerver` method to actually perform the validation.

After calling `isValid()` you may call `getMessages(): array<string, array<string>>` that will give you an associative array where the keys are the keys/properties that failed at least one rule and the value the list of error messages.

If at least one rule failed, the `throwIfNotValid()` method throws a `ValidationException` that contain the validated data and the error messages in the public `$messages` property.

Finally, when validating an array, you can call `getValidatedData(): array|stdClass` that will call `throwIfNotValid()` and if it hasn't thrown, return the array or stdClass with only the keys or properties that have been validated.   
A typical use would be to validate and sanitize a request body, example :
```php
public function register(ServerRequest $request)
{
    $input = (new Validator())
        ->setData($request->getBodyAsArray())
        ->setRules([
            'username' => ['optional', 'string', 'min-length:5', 'max-length:50'],
            'email' => ['email', 'min-length:5', 'max-length:50'],
            'password' => ['min-length:10'],
            'password_confirmation' => ['same-as:password'],
        ])
        ->getValidatedData();
        
    // ...
}
```

## Rules

Rules are passed to the validator as an associative array where the keys must match the keys or the properties of the data to be validated.

The list of rules for each key must be an array of built-in rules, callable, or objects implementing the `RuleInterface` interface.

## RuleInterface

The `RuleInterface` has two methods : 
- `passes(string $key, mixed $value): bool`, that receive the key and its value. It must return a boolean, where false indicates that the value doesn't pass the validation. 
- `getMessage(string $key): ?string`, that receive the key and must return `null` or the error message used when the value doesn't pass validation. If `null` is return, the default message is used.

## Callable

Callable rules hall have this signature `callable(string $key, mixed $value): null|false|string`.  
Returning false or an error message as a string indicates that the value failed validation. When false is returned, the default error message is used.

## Built-in rules

Built-in rules are always strings and will get you most of the way.
Don't forget that type declaration are actually a very good way to validate that aa property if of a certain type. 

All the validation rule names can be found in the `Rule` and `ParametrizedRule` enums.

`optional`: this should be the first rule. Subsequent rules are only evaluated if the key/property exists and is not null.

`exists`: the key or property must exist, but its value may be `null`. Subsequent rules for that key/property are only evaluated when it indeed exists, but typically it is only needed when it is the only rule since all others rules will fail if the value is null or the key/property doesn't exist.

`not-null`: the value must be non-null.

`date`: the value must be a date(time) as a string, in any format or value that PHP's `strtotime()` understands.

`uuid`: the value must be a UUID. The hyphen separator are optional, so any 32 hexadecimal chars will pass the rule.

`email`: the value must be a valid email

`is_*` rules: PHP has a number of `is_*` functions, like `is_string`, `is_int`. You can use any of them for the value to be passed to it : `string`, `int`, `float`, `bool`, `callable`, `null`, `array`, `countable`, `iterable`, `numeric`, `object`, `resource`, `scalar`.    

Some rules can take one or several parameters, when separated by a semicolon.

`instanceof:\The\Class\Name` : the value must be an object, instance of the provided class name.

`regex:/^the-regex$/i` : the value must match the provided regex.

`in:value1,value2` : the value must be one of the provided ones (strictly compared as a string).

`length`, `minlength`, `maxlength` : the value must be a string or be countable and its size be equal, greater or equal, or less than or equal than the provided parameter.

`==`, `===` : the value must be equal (`==`) or strictly equal (`===`) to the provided parameter, without any explicit casting.

`>`, `>=`, `<`, `<=` : the value is compared to the provided parameter, without any explicit casting.

## Defining object properties validation with attribute

The second example above show how to use the validator by passing an object instance and then an array that contain the properties to validate and their rules.

Using the `Validates` attribute, it is possible to just pass the object instance. The validator will introspect the validation rules with the attributes.  

Non-nullable typed properties are always considered required even without expressly adding the `not-null` rule and will throw a validation exception if not initialized.  
Non-typed or nullable typed properties are always considered nullable even without expressly adding the `optional` rule.  

The same example can be rewritten like so: 
```php
final readonly class MyDTO
{
    public function __construct(
        #[Validates([Rule::date, 'regex' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/'])]
        public string $date,
        
        #[Validates(['email', 'minLength' => 5, 'maxLength' => 50])]
        public ?string $email = null,
    ) {
        (new Validator())
            ->setData($this)
            ->throwIfNotValid();
    }
}

new MyDTO('2021-11-23');
```
