<?php

declare(strict_types=1);

namespace Mini;

use Mini\Captcha;
use Mini\Database;

class Validator
{
    protected array $rules        = [];
    protected array $errors       = [];
    protected string $errorPrefix = '<p class="text-danger">';
    protected string $errorSuffix = '</p>';

    public function setRules(array $rules)
    {
        foreach ($rules as $rule) {
            $field = $rule['field'];
            $this->rules[$field] = $rule;
        }
    }

    public function setErrorDelimiters(string $prefix, string $suffix)
    {
        $this->errorPrefix = $prefix;
        $this->errorSuffix = $suffix;
    }

    public function run()
    {
        foreach ($this->rules as $rule) {
            $field = $rule['field'];
            $label = $rule['label'];
            $rules = explode('|', $rule['rules']);

            foreach ($rules as $rule) {
                $result = $this->checkRule($rule, $field, $label);
                if ($result !== true) {
                    $this->errors[$field] = $result;
                    break;
                }
            }
        }

        return empty($this->errors);
    }

    public function formError($field)
    {
        if (isset($this->errors[$field])) {
            return $this->errorPrefix . $this->errors[$field] . $this->errorSuffix;
        }

        return '';
    }

    public function getErrors()
    {
        return $this->errors;
    }


    private function checkRule($rule, $field, $label)
    {
        // Separate the rule name from parameters, if any
        $params = [];
        if (strpos($rule, '[') !== false) {
            preg_match('/\[(.*?)\]/', $rule, $params);
            $rule = substr($rule, 0, strpos($rule, '['));
        }

        // Call the corresponding rule function
        $method = 'validate_' . $rule;
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], [$field, $label, $params]);
        }

        return "Rule '{$rule}' is not supported.";
    }

    // Rule functions

    private function validate_required($field, $label, $params)
    {
        if (empty($_POST[$field])) {
            return "{$label} wajib di isi.";
        }

        return true;
    }

    private function validate_valid_email($field, $label, $params)
    {
        if (!filter_var($_POST[$field], FILTER_VALIDATE_EMAIL)) {
            return "{$label} tidak valid.";
        }

        return true;
    }

    private function validate_max($field, $label, $params)
    {
        $max_length = (int) str_replace(['[', ']'], ['', ''], $params[0]);

        if (strlen($_POST[$field]) > $max_length) {
            return "{$label} maksimal {$max_length} karakter.";
        }

        return true;
    }

    private function validate_min($field, $label, $params)
    {
        $min_length = (int) str_replace(['[', ']'], ['', ''], $params[0]);

        if (strlen($_POST[$field]) < $min_length) {
            return "{$label} minimal {$min_length} karakter.";
        }

        return true;
    }

    private function validate_allowed_only($field, $label, $params)
    {
        $allowed_values = explode(',', str_replace(['[', ']'], ['', ''], $params[0]));

        if (!in_array($_POST[$field], $allowed_values)) {
            return "Manipulasi {$label} tidak di ijinkan, refresh halaman ini.";
        }
        return true;
    }


    private function validate_required_file($field, $label, $params)
    {
        if (empty($_FILES[$field]['name'])) {
            return "File {$label} wajib di isi.";
        }

        return true;
    }

    private function validate_ext_file($field, $label, $params)
    {
        if ($_FILES[$field]['name']) {

            $allowed_extensions = explode(',', str_replace(['[', ']'], ['', ''], $params[0]));
            $file_extension = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
            // dd($file_extension);die;

            if (!in_array($file_extension, $allowed_extensions)) {
                return "File {$label} harus berekstensi: " . implode(', ', $allowed_extensions) . ".";
            }
        }

        return true;
    }

    private function validate_max_file($field, $label, $params)
    {
        if ($_FILES[$field]['name']) {
            $max_size = (int) str_replace(['[', ']'], ['', ''], $params[0]) * 1024; // Convert to bytes

            if ($_FILES[$field]['size'] > $max_size) {
                return "File {$label} maksimal {$params[0]} KB.";
            }
        }

        return true;
    }



    private function validate_matches($field, $label, $params)
    {
        $other_field = str_replace(['[', ']'], ['', ''], $params[0]);
        $other_label = isset($this->rules[$other_field]['label']) ? $this->rules[$other_field]['label'] : $other_field;

        if (isset($_POST[$field]) && isset($_POST[$other_field]) && $_POST[$field] !== $_POST[$other_field]) {
            return "{$label} harus sama dengan {$other_label}.";
        }

        return true;
    }

    private function validate_integer($field, $label, $params)
    {
        if (!is_numeric($_POST[$field]) || intval($_POST[$field]) != $_POST[$field]) {
            return "{$label} harus berupa angka.";
        }

        return true;
    }


    private function validate_unique($field, $label, $params)
    {
        $table_column = str_replace(['[', ']'], ['', ''], $params[0]);
        list($table, $column) = explode('.', $table_column);

        // Check if the value is unique in the specified table and column
        $value = $_POST[$field];
        $database = new Database;

        $db    = $database->connect();

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = ?");
        $stmt->execute([$value]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            return "{$label} sudah digunakan.";
        }

        return true;
    }


    private function validate_valid_captcha($field, $label, $params)
    {
        // Check if the field exists in POST data
        if (!isset($_POST[$field])) {
            return "{$label} wajib di isi";
        }

        $userInput = $_POST[$field];

        // Validate CAPTCHA
        if (!Captcha::validate($userInput)) {
            return "{$label} tidak cocok";
        }

        return true;
    }
}
