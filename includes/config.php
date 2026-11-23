<?php
/**
 * Configuración general y conexión a MySQL.
 *
 * En local se usa la instancia de MariaDB del puerto 3307.
 * En el hospedaje solo se cambian los datos de conexión.
 *
 * Para no subir credenciales al repositorio, este archivo deja los
 * valores de ejemplo y, si existe includes/config.local.php, esos
 * valores tienen prioridad.
 */

declare(strict_types=1);

const APP_NOMBRE = 'A la Carta';
const APP_LUGAR  = 'Guayaquil, Ecuador';
const APP_DEBUG  = true; // En el hospedaje debe quedar en false.

date_default_timezone_set('America/Guayaquil');

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

// ------------------------------------------------------------
// Datos de conexión
// ------------------------------------------------------------
$config = [
    'host'    => '127.0.0.1',
    'puerto'  => '3307',
    'base'    => 'pwatarea6',
    'usuario' => 'root',
    'clave'   => '',
];

if (is_file(__DIR__ . '/config.local.php')) {
    $local = require __DIR__ . '/config.local.php';
    if (is_array($local)) {
        $config = array_merge($config, $local);
    }
}

// ------------------------------------------------------------
// Sesión
// ------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
    session_start();
}

// ------------------------------------------------------------
// Conexión PDO
// ------------------------------------------------------------
try {
    $pdo = new PDO(
        'mysql:host=' . $config['host'] . ';port=' . $config['puerto']
            . ';dbname=' . $config['base'] . ';charset=utf8mb4',
        $config['usuario'],
        $config['clave'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $error) {
    error_log('Conexión fallida: ' . $error->getMessage());

    if (PHP_SAPI === 'cli-server') {
        // El servidor interno de PHP muestra el detalle solo en local.
        exit('No se pudo conectar con la base de datos: ' . $error->getMessage());
    }

    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa includes/config.php.');
}

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/auth.php';
