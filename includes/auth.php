<?php
/**
 * Autenticación y control de permisos por rol.
 */

declare(strict_types=1);

/** Devuelve el usuario de la sesión, leído de la base en cada carga. */
function usuario_actual(): ?array
{
    global $pdo;
    static $usuario = null;

    if ($usuario !== null) {
        return $usuario;
    }

    $id = $_SESSION['usuario_id'] ?? null;
    if (!$id) {
        return null;
    }

    $sql = 'SELECT u.id, u.full_name, u.username, u.email, u.active, r.name AS role
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id';
    $consulta = $pdo->prepare($sql);
    $consulta->execute(['id' => $id]);
    $fila = $consulta->fetch();

    if (!$fila || !(int) $fila['active']) {
        sesion_cerrar();
        return null;
    }

    $usuario = $fila;
    return $usuario;
}

function iniciar_sesion(int $usuarioId): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuarioId;
}

function sesion_cerrar(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
    }
    session_destroy();
}

function redirigir(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

/** Exige sesión activa; si no hay, manda al acceso. */
function requiere_sesion(): array
{
    $usuario = usuario_actual();
    if (!$usuario) {
        flash('aviso', 'Inicia sesión para continuar.');
        redirigir('login.php');
    }
    return $usuario;
}

/** Exige uno de los roles indicados (por nombre de la tabla roles). */
function requiere_rol(array $roles): array
{
    $usuario = requiere_sesion();
    if (!in_array($usuario['role'], $roles, true)) {
        http_response_code(403);
        mostrar_403($usuario);
        exit;
    }
    return $usuario;
}

/** Página breve de acceso denegado, con el mismo estilo del sitio. */
function mostrar_403(array $usuario, string $detalle = ''): void
{
    $mensaje = $detalle !== ''
        ? $detalle
        : 'Entraste como <strong>' . e(rol_etiqueta($usuario['role'])) . '</strong>. '
          . 'Si necesitas este permiso, pídeselo al administrador del sistema.';

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Sin permiso · ' . APP_NOMBRE . '</title>'
        . '<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">'
        . '<link rel="stylesheet" href="assets/css/fuentes.css">'
        . '<link rel="stylesheet" href="assets/vendor/bootstrap.min.css">'
        . '<link rel="stylesheet" href="assets/css/estilos.css"></head>'
        . '<body class="pagina-error"><main class="error-caja">'
        . '<p class="etiqueta-mono">Error 403</p>'
        . '<h1>Tu rol no tiene acceso a esta página</h1>'
        . '<p>' . $mensaje . '</p>'
        . '<a class="boton boton--principal" href="panel.php">Volver al panel</a>'
        . '</main></body></html>';
}
