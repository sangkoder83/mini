<?php

declare(strict_types=1);

namespace Mini;

class View
{
    public function renderView(string $module, string $template, array $data = []): string
    {
        $data = $this->sanitizeData($data); // Ensure that the data is not sanitized again here
        extract($data, EXTR_SKIP);

        ob_start();

        require  FCPATH . 'modules' . DS . ucfirst($module) . DS . 'Views' . DS . $template . '.php';

        return ob_get_clean();
    }

    public function renderTemplate(string $template, array $data = []): string
    {
        $data = $this->sanitizeData($data); // Ensure that the data is not sanitized again here
        extract($data, EXTR_SKIP);

        ob_start();

        require FCPATH . 'app' . DS . 'Templates'  . DS . $template . '.layout.php';

        return ob_get_clean();
    }

    protected function sanitizeData(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($key === 'csrf') {
                continue;
            } elseif (is_string($value)) {
                $data[$key] = htmlspecialchars($value);
            }
        }
        return $data;
    }
}
