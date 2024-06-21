<?php

declare(strict_types=1);

namespace mini;

use Mini\View;
use Mini\Request;


class Controller
{
    protected $module = null;
    protected View $view;
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request;
        $this->view = new View();
        if ($this->module == null) {
            $nameSpacedClass = get_called_class();
            $class = explode('\\', $nameSpacedClass);
            $this->module = lcfirst($class[1]);
        }
    }

    public function render(string $template, array $data = [])
    {

        // $data['csrf'] = generate_csrf_and_field();


        [$layoutName, $viewName] = explode('.', $template);
        $layout = $this->view->renderTemplate($layoutName, $data);
        $content = $this->view->renderView($this->module, $viewName, $data);


        echo strtr($layout, ['{{content}}' => $content]);
    }
}
