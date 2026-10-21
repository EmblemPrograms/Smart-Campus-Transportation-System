<?php
/**
 * Smart Campus Transportation System
 * Federal Polytechnic Ede
 *
 * database/seed_passwords.php
 *
 * Updates sample user passwords using PHP password_hash() (bcrypt).
 * Called by install.php after schema load, or run standalone:
 *
 *   php database/seed_passwords.php
 *   php database/seed_passwords.php --host=127.0.0.1 --user=root --pass=
 */

declare(strict_types=1);

$passwordMap = [
    'admin@fedpolyede.edu.ng'    => 'Admin@123',
    'student1@fedpolyede.edu.ng' => 'Student@123',
    'student2@fedpolyede.edu.ng' => 'Student@123',
    'staff1@fedpolyede.edu.ng'   => 'Staff@123',
    'driver1@fedpolyede.edu.ng'  => 'Driver@123',
    'driver2@fedpolyede.edu.ng'  => 'Driver@123',
];

/**
 * When required by install.php, $pdo is already connected and selected.
 * When run standalone, establish connection here.
 */
$standalone = (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__));

if ($standalone || !isset($pdo) || !($pdo instanceof PDO)) {
    $config = [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '',
        'name' => 'smart_campus_transport',
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

    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        if ($standalone) {
            fwrite(STDERR, '[ERROR] ' . $e->getMessage() . "\n");
            exit(1);
        }
        return ['ok' => false, 'updated' => [], 'message' => $e->getMessage()];
    }
}

$updated = [];

try {
    $stmt = $pdo->prepare(
        'UPDATE `users` SET `password` = :password, `updated_at` = NOW() WHERE `email` = :email'
    );

    foreach ($passwordMap as $email => $plain) {
        $hash = password_hash($plain, PASSWORD_BCRYPT);
        if ($hash === false) {
            throw new RuntimeException("password_hash() failed for {$email}");
        }

        $stmt->execute([
            ':password' => $hash,
            ':email' => $email,
        ]);

        if ($stmt->rowCount() > 0) {
            $updated[$email] = $plain;
        } else {
            // Still record intent; row may already have same hash length / zero-row UPDATE edge case
            $check = $pdo->prepare('SELECT id FROM `users` WHERE `email` = :email LIMIT 1');
            $check->execute([':email' => $email]);
            if ($check->fetch()) {
                $updated[$email] = $plain;
            }
        }
    }

    $result = [
        'ok' => true,
        'updated' => $updated,
        'message' => count($updated) . ' user password(s) updated.',
    ];
} catch (Throwable $e) {
    $result = [
        'ok' => false,
        'updated' => $updated,
        'message' => $e->getMessage(),
    ];
}

if ($standalone) {
    if (!$result['ok']) {
        fwrite(STDERR, '[ERROR] ' . $result['message'] . "\n");
        exit(1);
    }
    echo "Password seed complete.\n";
    foreach ($result['updated'] as $email => $plain) {
        echo "  {$email} => {$plain}\n";
    }
    exit(0);
}

return $result;
