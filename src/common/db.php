<?php
/**
 * Database Connection
 *
 * Provides a single shared PDO connection to the MySQL 'course' database.
 * Wrapped in function_exists so that test shims can define getDBConnection()
 * first and this file becomes a no-op when required_once afterwards.
 */

if (!function_exists('getDBConnection')) {
    function getDBConnection(): PDO
    {
        static $pdo = null;

        if ($pdo === null) {
            $host = 'localhost';
            $db   = 'course';
            $user = 'admin';
            $pass = 'password123';

            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,           PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return $pdo;
    }
}
