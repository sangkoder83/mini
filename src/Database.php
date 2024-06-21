<?php

declare(strict_types=1);

namespace Mini;

use PDO;
use PDOException;

class Database
{
    protected ?PDO $pdo = null;

    public function connect(): PDO
    {
        $dsn = "mysql:host={$_ENV['DBHOST']};dbname={$_ENV['DBNAME']};charset=utf8;port={$_ENV['DBPORT']}";

        try {
            if ($this->pdo === null) {
                return $this->pdo = new PDO($dsn, $_ENV['DBUSER'], $_ENV['DBPASS'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            }
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function getParamType($value)
    {

        switch (true) {
            case is_int($value):
                $type = PDO::PARAM_INT;
                break;
            case is_bool($value):
                $type = PDO::PARAM_BOOL;
                break;
            case is_null($value):
                $type = PDO::PARAM_NULL;
                break;
            default:
                $type = PDO::PARAM_STR;
        }

        return $type;
    }
}
