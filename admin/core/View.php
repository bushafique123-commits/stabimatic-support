<?php

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/../views/' . $template . '.php';
        $content = ob_get_clean();
        if ($layout === null) {
            echo $content;
            return;
        }
        require __DIR__ . '/../views/' . $layout . '.php';
    }
}
