<?php
// Central database configuration and PDO connection factory used by every page.
declare(strict_types=1);

final class Database
{
    // Build one PDO connection with exceptions, associative rows, and native prepares.
    public static function connect(): PDO
    {
        $host = '127.0.0.1';
        $name = 'barangay_kusina';
        $user = 'root';
        $password = '';
        return new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}

// Expose the shared connection to files that include db.php and keep credentials private on failure.
try {
    $pdo = Database::connect();
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Could not connect to the database. Import database.sql and check the settings in db.php.');
}
