<?php

declare(strict_types=1);

namespace Mini;

class Request
{

    public function uri()
    {
        return '/' . parse_url(trim(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL), '/'), PHP_URL_PATH);
    }

    public function method()
    {
        return $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
    }

    public function getVar($key = '')
    {
        $postData  = $_POST;
        $filesData = $_FILES;
        $getData   = $_GET;

        $data = [...$postData, ...$getData, ...$filesData];

        if (!empty($key) && $key != '') {
            return isset($data[$key]) ? $data[$key] : null;
        }

        return $data;
    }
}
