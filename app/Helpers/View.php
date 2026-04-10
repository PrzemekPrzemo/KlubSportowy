<?php

namespace App\Helpers;

class View
{
    private string $layout = 'main';

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function render(string $__template, array $__data = []): void
    {
        extract($__data, EXTR_SKIP);

        $viewFile = ROOT_PATH . '/app/Views/' . $__template . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $__template");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = ROOT_PATH . '/app/Views/layouts/' . $this->layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function partial(string $__template, array $__data = []): string
    {
        extract($__data, EXTR_SKIP);
        $viewFile = ROOT_PATH . '/app/Views/' . $__template . '.php';
        if (!file_exists($viewFile)) {
            return '';
        }
        ob_start();
        require $viewFile;
        return ob_get_clean();
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
