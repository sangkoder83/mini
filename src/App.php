<?php

declare(strict_types=1);

namespace Mini;

use Mini\Request;

class App
{
    protected $routes = [];
    protected $request;
    protected $allowed_methods = [];
    protected $matched_route = null;

    public function __construct()
    {
        $this->request = new Request;
    }

    private function add(string $method, string $path, string $callback)
    {
        $modules = scandir(FCPATH . 'modules' . DS);

        $parts = explode('@', $callback);

        $path = preg_replace('/\{.*\}/', '([0-9a-zA-Z-]*)', $path);


        foreach ($modules as $module) {
            if (is_dir($module)) {
                continue;
            }

            $file_path = FCPATH . 'Modules' . DS . ucfirst($module) . DS . 'Controllers' . DS . $parts[0] . '.php';


            if (file_exists($file_path) && $path === $this->request->uri()) {
                $this->routes[] = [
                    'file_path' => $file_path,
                    'method'     => $method,
                    'path'       => $path,
                    'controller' => '\\Modules\\' . ucfirst($module) . '\\Controllers\\' . $parts[0],
                    'function'   => $parts[1]
                ];
                return;
            }
        }
    }

    public function get(string $path, string $callback)
    {
        $this->add('GET', $path, $callback);
    }

    public function post(string $path, string $callback)
    {
        $this->add('POST', $path, $callback);
    }

    public function run()
    {
        // dd($this->routes);
        $uri    = $this->request->uri();
        $method = $this->request->method();

        foreach ($this->routes as $route) {
            $pattern = "#^" . $route['path'] . "$#";

            if (preg_match($pattern, $uri, $params)) {
                if ($method == $route['method']) {
                    $this->matched_route = $route;
                    break;
                } else {
                    $this->allowed_methods[] = $route['method'];
                }
            }
        }

        if ($this->matched_route) {
            require $this->matched_route['file_path'];
            array_shift($params);
            $controller = new $this->matched_route['controller'];
            $function   = $this->matched_route['function'];



            if (method_exists($controller, $function)) {
                call_user_func_array([$controller, $function], $params);
            } else {
                echo ('Method "' . $function . '" not found');
                die;
            }
        } else {
            if (!empty($this->allowed_methods)) {
                http_response_code(403); // 403 Forbidden
                echo ('403 Forbidden - Access Denied');
                die;
            } else {
                // redirect('/404');
                echo ('404 Page Not found');
                die;
            }
        }
    }
}
