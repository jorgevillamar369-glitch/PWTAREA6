<?php
/**
 * Acceso al sistema. Acepta correo o nombre de usuario.
 */

require_once __DIR__ . '/includes/config.php';

if (usuario_actual()) {
    redirigir('panel.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $identificador = trim($_POST['identificador'] ?? '');
    $clave         = $_POST['clave'] ?? '';

    if ($identificador === '' || $clave === '') {
        $error = 'Escribe tu correo (o usuario) y tu contraseña.';
    } else {
        $sql = 'SELECT u.id, u.password, u.active, r.name AS role
                FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE u.email = :correo OR u.username = :usuario
                LIMIT 1';
        $consulta = $pdo->prepare($sql);
        $consulta->execute(['correo' => $identificador, 'usuario' => $identificador]);
        $cuenta = $consulta->fetch();

        if (!$cuenta || !password_verify($clave, $cuenta['password'])) {
            $error = 'El correo o la contraseña no coinciden.';
        } elseif (!(int) $cuenta['active']) {
            $error = 'Esta cuenta está suspendida. Habla con el administrador.';
        } else {
            iniciar_sesion((int) $cuenta['id']);
            flash('ok', 'Bienvenido de vuelta. Tu sesión está abierta.');
            redirigir('panel.php');
        }
    }
}

$titulo = 'Acceso';
require __DIR__ . '/includes/sitio_header.php';
?>

<main id="contenido" class="acceso">
    <div class="contenedor acceso__fila">
        <figure class="acceso__figura">
            <img src="assets/img/cocina-pase.jpg" width="1400" height="933"
                 alt="Cocineros trabajando en la línea de la cocina">
            <figcaption>La ventana de servicio, 13:40.</figcaption>
        </figure>

        <div class="acceso__panel">
            <p class="sobrelinea">Acceso al sistema</p>
            <h1>Entra con tu rol.</h1>
            <p class="entrada">Cada cuenta abre las páginas y los permisos que le corresponden.</p>

            <?php if ($error): ?>
                <div class="aviso aviso--error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
            <?php mostrar_flash(); ?>

            <form method="post" class="formulario" novalidate>
                <?= csrf_campo() ?>

                <div class="campo">
                    <label for="identificador">Correo o usuario</label>
                    <input class="control" type="text" id="identificador" name="identificador"
                           value="<?= e($_POST['identificador'] ?? '') ?>" autocomplete="username" required>
                </div>

                <div class="campo">
                    <label for="clave">Contraseña</label>
                    <input class="control" type="password" id="clave" name="clave"
                           autocomplete="current-password" required>
                </div>

                <button class="boton boton--principal boton--ancho" type="submit">Entrar</button>
            </form>

            <p class="acceso__enlace">
                ¿No tienes cuenta? <a href="registro.php">Regístrate como cliente</a>
            </p>

            <details class="acceso__demo">
                <summary>Cuentas de prueba del proyecto</summary>
                <p class="acceso__demo-nota">Todas usan la contraseña <code>ALaCarta2026</code>.</p>
                <ul>
                    <li><span>Administrador</span><code>admin@alacarta.ec</code></li>
                    <li><span>Chef</span><code>cocina@alacarta.ec</code></li>
                    <li><span>Camarero</span><code>mesero@alacarta.ec</code></li>
                    <li><span>Cliente</span><code>andres@correo.ec</code></li>
                </ul>
            </details>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/sitio_footer.php'; ?>
