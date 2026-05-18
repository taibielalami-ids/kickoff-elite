<?php

class Database
{
    private static ?mysqli $connection = null;

    public static function connection(): mysqli
    {
        if (self::$connection instanceof mysqli) {
            return self::$connection;
        }

        $db = config('database');
        if (!is_array($db)) {
            throw new RuntimeException('Database configuration missing.');
        }

        $connection = new mysqli(
            (string) $db['host'],
            (string) $db['username'],
            (string) $db['password'],
            (string) $db['database'],
            (int) $db['port']
        );

        if ($connection->connect_error) {
            throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
        }

        $connection->set_charset((string) $db['charset']);
        self::$connection = $connection;

        return self::$connection;
    }
}

