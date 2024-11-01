# Controller

Controllers are instantiated by the HTTP kernel, so their constructor argument can be autowired.

Each route action (the route target method) is also autowired.

This documentation shows how to do common tasks related to reading information from the incomming request

## Get the current HTTP server request, Route object, logged-in user

Type an argument with the correct class name, like `Request`, `Route` or whatever your authenticator consider a "User".

```php
final readonly class Controller
{
    public function index(
        ServerRequet $request, 
        Route $route, 
        ?User $user = null,
    ) {
        //    
    }
}
```

## Map route parameters to arguments

URI segments with a dynamic value by default can get their value injected to arguments.

By default, they are injected to arguments that match their name and have no other attributes.

When the argument name doesn't match the segment name, you can use the `MapRouteParameter` attribute and pass the segment name whose to inject to the argument.

```php
final readonly class Controller
{
    /**
     * Route: GET /articles/{slug}?page=1
     */
    public function index(
        string $slug,
        #[MapRouteParameter('slug')] string $slug2,
    ) {
    }
}
```

## Map query strings to arguments

Similarly to uri segments, query strings can get their value injected to arguments.

Individual query strings can be injected into scalar argument with the `MapQueryString` attribute. When the argument name doesn't match the query string name, you can pass the query string name whose to inject to the argument.

```php
final readonly class Controller
{
    /**
     * Route: GET /articles/{slug}?page=1
     */
    public function index(
        #[MapQueryString] string $page,
        #[MapQueryString('page')] string $currentPage,
    ) {
    }
}
```

When the argument is typed against an object, as much as query strings are mapped to the object's properties.

When there is a mismatch between the query string name and a property name, you can use the hydrator's `DataToPropertyMap` attribute on the class.

Objects built this way are validated by default, if the properties have the Validation attributes. When you don't want that behavior you can set the attribute's `validate` argument to false. 

```php
final readonly class Controller
{
    /**
     * Route: GET /articles/{slug}?page=1
     */
    public function index(
        #[MapQueryString] PaginationDTO $pagination,
        #[MapQueryString(map: ['sort' => 'sortDirection'], validate: false)] PaginationDTO $pagination2,
    ) {
    }
}

#[DataToPropertyMap(['sort' => 'sortDirection'])]
final readonly class PaginationDTO
{
    public function __construct(
        public int $page = 1,    
        public int $perPage = 50,
        public string $sortDirection = 'asc',   
    ) {}
}
```


## Map the request body to an object argument

When an argument with the `MapRequestBody` attribute is typed against an object, as much as the request body decoded data is mapped to the object's properties.

When there is a mismatch between the body data keys name and a property name, you can pass a map to the attribute's `map` argument.

Objects built this way are validated by default, if the properties have the Validation attributes. When you don't want that behavior you can set the attribute's `validate` argument to false.

```php
final readonly class Controller
{
    /**
     * Route: POST /articles
     */
    public function index(
        #[MapRequestBody] ArticleCreateDTO $newArticle,
        #[MapRequestBody(map: ['createdAt' => 'created_at'], validate: false)] ArticleCreateDTO $newArticle,
    ) {
    }
}

final readonly class ArticleCreateDTO
{
    public function __construct(
        public string $title,    
        public string $content,   
        public DateTimeImmutable $created_at,   
    ) {}
}
```

## Validation

Unhandled ValidationException during an HTTP request automatically produce an HTTP response with status 422 and the validation errors inserted in the body.

