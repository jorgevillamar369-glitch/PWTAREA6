<?php
/**
 * Registro público. Solo crea cuentas con el rol Cliente.
 */

require_once __DIR__ . '/includes/config.php';

if (usuario_actual()) {
    redirigir('panel.php');
}

$errores = [];
$datos = [
    'full_name' => '',
    'username'  => '',
    'email'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $datos['full_name'] = trim($_POST['full_name'] ?? '');
    $datos['username']  = trim($_POST['username'] ?? '');
    $datos['email']     = trim($_POST['email'] ?? '');
    $clave              = $_POST['clave'] ?? '';
    $repetir            = $_POST['repetir'] ?? '';

    if (mb_strlen($datos['full_name']) < 3) {
        $errores[] = 'Escribe tu nombre completo.';
    }
    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $datos['username'])) {
        $errores[] = 'El usuario debe tener entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.';
    }
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no tiene un formato válido.';
    }
    if (mb_strlen($clave) < 8) {
        $errores[] = 'La contraseña necesita al menos 8 caracteres.';
    }
    if ($clave !== $repetir) {
        $errores[] = 'Las dos contraseñas no coinciden.';
    }

    if (!$errores) {
        $consulta = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :usuario LIMIT 1');
        $consulta->execute(['email' => $datos['email'], 'usuario' => $datos['username']]);
        if ($consulta->fetch()) {
            $errores[] = 'Ese correo o usuario ya está registrado.';
        }
    }

    if (!$errores) {
        $rolCliente = $pdo->query("SELECT id FROM roles WHERE name = 'Client'")->fetchColumn();

        $insertar = $pdo->prepare(
            'INSERT INTO users (full_name, username, email, password, role_id)
             VALUES (:nombre, :usuario, :email, :clave, :rol)'
        );
        $insertar->execute([
            'nombre'  => $datos['full_name'],
            'usuario' => $datos['username'],
            'email'   => $datos['email'],
            'clave'   => password_hash($clave, PASSWORD_DEFAULT),
            'rol'     => $rolCliente,
        ]);

        iniciar_sesion((int) $pdo->lastInsertId());
        flash('ok', 'Tu cuenta está creada. Ya puedes pedir desde la carta.');
        redirigir('pedido_nuevo.php');
    }
}

$titulo = 'Crear cuenta';
require __DIR__ . '/includes/sitio_header.php';
?>

<main id="contenido" class="acceso">
    <div class="contenedor acceso__fila acceso__fila--invertida">
        <div class="acceso__panel">
            <p class="sobrelinea">Cuenta de cliente</p>
            <h1>Pide desde tu mesa.</h1>
            <p class="entrada">
                Con la cuenta abierta puedes armar tu pedido, seguir su estado
                y revisar lo que pediste en visitas anteriores.
            </p>

            <?php foreach ($errores as $mensaje): ?>
                <div class="aviso aviso--error" role="alert"><?= e($mensaje) ?></div>
            <?php endforeach; ?>

            <form method="post" class="formulario" novalidate>
                <?= csrf_campo() ?>

                <div class="campo">
                    <label for="full_name">Nombre completo</label>
                    <input class="control" type="text" id="full_name" name="full_name"
                           value="<?= e($datos['full_name']) ?>" autocomplete="name" required>
                </div>

                <div class="campo-doble">
                    <div class="campo">
                        <label for="username">Usuario</label>
                        <input class="control" type="text" id="username" name="username"
                               value="<?= e($datos['username']) ?>" autocomplete="username" required>
                    </div>
                    <div class="campo">
                        <label for="email">Correo</label>
                        <input class="control" type="email" id="email" name="email"
                               value="<?= e($datos['email']) ?>" autocomplete="email" required>
                    </div>
                </div>

                <div class="campo-doble">
                    <div class="campo">
                        <label for="clave">Contraseña</label>
                        <input class="control" type="password" id="clave" name="clave"
                               autocomplete="new-password" required>
                    </div>
                    <div class="campo">
                        <label for="repetir">Repetir contraseña</label>
                        <input class="control" type="password" id="repetir" name="repetir"
                               autocomplete="new-password" required>
                    </div>
                </div>

                <p class="campo__ayuda">Mínimo 8 caracteres. Se guarda cifrada en la base.</p>

                <button class="boton boton--principal boton--ancho" type="submit">Crear cuenta</button>
            </form>

            <p class="acceso__enlace">¿Ya tienes cuenta? <a href="login.php">Entrar al sistema</a></p>
        </div>

        <figure class="acceso__figura">
            <img src="assets/img/hero-mesa.jpg" width="1400" height="933"
                 alt="Mesa servida con varios platos en el salón">
            <figcaption>Mesa 6 · servicio de mediodía.</figcaption>
        </figure>
    </div>
</main>

<?php require __DIR__ . '/includes/sitio_footer.php'; ?>
