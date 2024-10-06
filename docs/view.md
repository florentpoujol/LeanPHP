# Views

PHP started as and is still [a template language](https://www.php.net/manual/en/language.basic-syntax.phptags.php).
It is enough for most case, lean doesn't provide its own template language, and provide a basic PHP view render.

## PHP views

Your can write PHP within your HTML by surrounding the php code with the opening and closing tags like so :
```php
<html>
<body>
    <?php echo $content; ?>
    
    <ul>
    <?php foreach ($users as $user) { ?>
        <li><?php echo $user->name; ?></li>    
    <?php } ?>
    <ul>

</body>
</html>
```

Alternatively, instead of `<?php echo $content; ?>`, when they are enabled, you can use short tags that will automatically echo the value :
```php
<?= $user->name ?>
```

There is also alternative syntax instead of using braces : 
```php
<ul>
<?php foreach ($users as $user): ?>
    <li><?= $user->name ?></li>    
<?php endforeach; ?>
<ul>
```

## Rendering a view from a controller

A `PhpViewRenderer` instance needs a path to the folder in which views will be found as only constructor argument.  
Then call the `render(string $viewName, array $variables): string` method on it.

The first argument is the path of the view file (possibly nested in subdirectories), with or without the `.php` extension.  
The second argument is an associative array of variables that will exist in the view.

Example :
```php
<?php declare(strict_types=1);

namespace App\Http;

use LeanPHP\ViewRenderer;
use Nyholm\Psr7\Response;

final readonly class Controller
{
    public function __construct(
        private ViewRenderer $viewRenderer
    ) {
    }

    public function get(): Response
    {
        $html = $this->viewRenderer->render('some/views', [
            'content' => 'some content',
        ]);

        return new Response(body: $html);
    }
}
```

This will look for a `{base view path}/some/view.php` file.
