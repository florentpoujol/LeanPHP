# Server request and form validation

## ServerRequest validators

Lean provide the `ServerRequestValidator` service to validates the body of an HTTP request and get the validated data.

You are expected to feed to the validator either the validation rules, or the entity from which the validation rules will be defined, with the `setRules(array $rules): self` or `setEntityFqcn(string $fqcn): self` methods.

Then you can call the `validate(): bool` or `validateOrThrow(): true|never` method to actually perform the validation.
If there is validation errors, they are automatically written in the session (if any), under the `validation_errors` keys.

Once validated, you can call either the `getValidatedData(): array` or the `getValidatedEntity(): object` methods.  

```php
// in a controller

final readonly class Controller
{
    public function validateRequestRawData(ServerRequestValidator $validator): Response
    {
        // ----------
        // configure...
        
        $validator->setRules([
            'email' => [Rule::notNull, Rule::email, 'maxlength' => 255],
            'password' => [Rule::notNull],
        ]);
        // or
        $validator->setEntityFqcn(LoginFormData::class);
        
        // ----------
        // validate...
        
        if (! $validator->validate()) {
            return new Response(422);
        }
        // or
        $validator->validateOrThrow(); // throws a ValidationException if not validated
        
        // ----------
        // get validated data...
        
        $arrayData = $validator->getValidatedData();
        // or
        $entity = $validator->getValidatedEntity();
        
        // ...
    }
}

final readonly class LoginFormData
{
    public function __construct(
        #[Validates([Rule::email, 'maxlength' => 255])]
        public string $email,
        public string $password,
    ) {
    }
}
```

## HTML validation builder

The `FormHtmlValidationBuilder` helps turn the configured validation rules into corresponding HTMl attributes when possible.

This helps keep the validation logic in sync between backend and front-end.

Similar to the ServerRequest validator, you have to feed them validation rules or an entity name with the `setRules(array $rules): self` or `setEntityFqcn(string $fqcn): self` methods.

The object is meant to be passed to the views, which can then use the `getHtmlValidationAttrs(string $fieldName): string` method.

```php
// in a controller

final readonly class Controller
{
    public function showLoginForm(FormHtmlValidationBuilder $form): Response
    {
        $form->setRules([
            'email' => [Rule::notNull, Rule::email, 'maxlength' => 255],
            'password' => [Rule::notNull],
        ]);
        // or
        $form->setEntityFqcn(LoginFormData::class);

        // them pass it to the view        
        $html = $this->viewRenderer->render('login', [
            'form' => $form,
        ]);

        return new Response(body: $html);
    }
}

// in the view
<form action="/auth/login" method="post">
    <div>
        <label for="email">Email: </label>
        <input type="email" id="email" name="email" <?= $form->getHtmlValidationAttrs('email') ?>/>
        <!-- this wil add the following attributes to the field 'required maxlength="255"' --> 
    </div>

    <div>
        <label for="password">Password: </label>
        <input type="password" id="password" name="password" <?= $form->getHtmlValidationAttrs('password') ?>/>
        <!-- this wil add the following attributes to the field 'required' -->
    </div>

    <div>
        <input type="submit" value="Login"/>
    </div>
</form>
```