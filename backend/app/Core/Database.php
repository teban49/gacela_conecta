<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function init(array $config = []): PDO
    {
        self::$config = $config;
        return self::getConnection();
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host     = self::$config['host']     ?? $_ENV['DB_HOST']     ?? 'localhost';
            $port     = self::$config['port']     ?? $_ENV['DB_PORT']     ?? '3306';
            $database = self::$config['database'] ?? $_ENV['DB_DATABASE'] ?? 'gacela_conecta_beta';
            $username = self::$config['username'] ?? $_ENV['DB_USERNAME'] ?? 'root';
            $password = self::$config['password'] ?? $_ENV['DB_PASSWORD'] ?? '';
            $charset  = self::$config['charset']  ?? $_ENV['DB_CHARSET']  ?? 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

            $options = self::$config['options'] ?? [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                throw new RuntimeException("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}