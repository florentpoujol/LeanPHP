<?php declare(strict_types=1);

namespace LeanPHP;

final readonly class PhpViewRenderer
{
    public function __construct(
        private string $baseViewPath,
    ) {
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function render(string $viewPath, array $variables = []): string
    {
        $originalViewPath = $viewPath;
        $viewPath = "$this->baseViewPath/$viewPath";

        if (! str_ends_with($viewPath, '.php')) {
            $viewPath .= '.php';
        }

        if (! file_exists($viewPath)) {
            throw new \UnexpectedValueException("Couldn't find view '$originalViewPath' in path '$this->baseViewPath/'.");
        }

        extract($variables, \EXTR_OVERWRITE);
        ob_start();
        require $viewPath;
        $viewContent = ob_get_clean();

        if (! \is_string($viewContent)) {
            throw new \UnexpectedValueException("Couldn't get buffer output from view at path '$viewPath'.");
        }

        return $viewContent;
    }
}
