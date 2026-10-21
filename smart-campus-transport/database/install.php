<?php
/**
 * Smart Campus Transportation System
 * Federal Polytechnic Ede
 *
 * database/install.php
 *
 * 1. Creates database if not exists (via schema.sql which DROPs/CREATEs)
 * 2. Runs schema.sql
 * 3. Updates all sample user passwords with password_hash()
 *
 * Usage (CLI):
 *   php database/install.php
 *
 * Optional env / overrides via CLI args:
 *   php database/install.php --host=127.0.0.1 --port=3306 --user=root --pass= --force
 */

declare(strict_types=1);

$config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '',
    'charset' => 'utf8mb4',
];

foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--host=')) {
        $config['host'] = substr($arg, 7);
    } elseif (str_starts_with($arg, '--port=')) {
        $config['port'] = substr($arg, 7);
    } elseif (str_starts_with($arg, '--user=')) {
        $config['user'] = substr($arg, 7);
    } elseif (str_starts_with($arg, '--pass=')) {
        $config['pass'] = substr($arg, 7);
    }
}

$schemaFile = __DIR__ . DIRECTORY_SEPARATOR . 'schema.sql';
$seedPasswordsFile = __DIR__ . DIRECTORY_SEPARATOR . 'seed_passwords.php';

if (!is_file($schemaFile)) {
    fwrite(STDERR, "[ERROR] schema.sql not found at: {$schemaFile}\n");
    exit(1);
}

echo "=== Smart Campus Transport — Database Installer ===\n";
echo "Host: {$config['host']}:{$config['port']}\n";
echo "User: {$config['user']}\n\n";

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    echo "[1/3] Connected to MySQL successfully.\n";

    $sql = file_get_contents($schemaFile);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('Unable to read schema.sql or file is empty.');
    }

    echo "[2/3] Executing schema.sql (DROP/CREATE database + tables + sample data)...\n";
    $pdo->exec($sql);
    echo "      Schema and sample data loaded.\n";

    // Switch to the new database for password updates
    $pdo->exec('USE `smart_campus_transport`');

    echo "[3/3] Updating sample user passwords with password_hash()...\n";

    /** @var array{ok:bool, updated:array<string,string>, message?:string} $result */
    $result = require $seedPasswordsFile;

    if (!is_array($result) || empty($result['ok'])) {
        $msg = is_array($result) ? ($result['message'] ?? 'Password seeding failed.') : 'Password seeding failed.';
        throw new RuntimeException($msg);
    }

    foreach ($result['updated'] as $email => $plain) {
        echo "      ✓ {$email}  =>  {$plain}\n";
    }

    echo "\n=== Install complete ===\n";
    echo "Database: smart_campus_transport\n";
    echo "Default logins (after password seed):\n";
    echo "  admin@fedpolyede.edu.ng     / Admin@123\n";
    echo "  student1@fedpolyede.edu.ng  / Student@123\n";
    echo "  student2@fedpolyede.edu.ng  / Student@123\n";
    echo "  staff1@fedpolyede.edu.ng    / Staff@123\n";
    echo "  driver1@fedpolyede.edu.ng   / Driver@123\n";
    echo "  driver2@fedpolyede.edu.ng   / Driver@123\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "\n[ERROR] " . $e->getMessage() . "\n");
    exit(1);
}
