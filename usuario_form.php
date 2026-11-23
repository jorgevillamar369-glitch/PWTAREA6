<?php
/**
 * Alta y edición de cuentas de usuario.
 */

require_once __DIR__ . '/includes/config.php';

$usuario = requiere_rol(['Administrator']);
$roles = $pdo->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();

$id     = (int) ($_GET['id'] ?? 0);
$editar = $id > 0;
$errores = [];

$cuenta = [
    'full_name' => '',
    'username'  => '',
    'email'     => '',
    'role_id'   => 4,
    'active'    => 1,
];

if ($editar) {
    $consulta = $pdo->prepare('SELECT id, full_name, username, email, role_id, active FROM users WHERE id = :id');
    $consulta->execute(['id' => $id]);
    $encontrada = $consulta->fetch();
    if (!$encontrada) {
        flash('error', 'Esa cuenta no existe.');
        redirigir('usuarios.php');
    }
    $cuenta = $encontrada;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $cuenta['full_name'] = trim($_POST['full_name'] ?? '');
    $cuenta['username']  = trim($_POST['username'] ?? '');
    $cuenta['email']     = trim($_POST['email'] ?? '');
    $cuenta['role_id']   = (int) ($_POST['role_id'] ?? 4);
    $cuenta['active']    = isset($_POST['active']) ? 1 : 0;
    $clave               = $_POST['clave'] ?? '';
    $repetir             = $_POST['repetir'] ?? '';

    if (mb_strlen($cuenta['full_name']) < 3) {
        $errores[] = 'Escribe el nombre completo.';
    }
    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $cuenta['username'])) {
        $errores[] = 'El usuario debe tener entre 3 y 50 caracteres válidos.';
    }
    if (!filter_var($cuenta['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no tiene un formato válido.';
    }
    if (!in_array($cuenta['role_id'], array_map('intval', array_column($roles, 'id')), true)) {
        $errores[] = 'Elige un rol de la lista.';
    }

    if (!$editar || $clave !== '' || $repetir !== '') {
        if (mb_strlen($clave) < 8) {
            $errores[] = 'La contraseña necesita al menos 8 caracteres.';
        }
        if ($clave !== $repetir) {
            $errores[] = 'Las dos contraseñas no coinciden.';
        }
    }

    // Nadie se quita a sí mismo el acceso de administrador.
    $esMiCuenta = $editar && $id === (int) $usuario['id'];
    if ($esMiCuenta) {
        $rolAdmin = (int) $pdo->query("SELECT id FROM roles WHERE name = 'Administrator'")->fetchColumn();
        if ($cuenta['role_id'] !== $rolAdmin) {
            $errores[] = 'No puedes cambiar tu propio rol de administrador.';
        }
        if (!$cuenta['active']) {
            $errores[] = 'No puedes suspender tu propia cuenta.';
        }
    }

    if (!$errores) {
        $duplicado = $pdo->prepare(
            'SELECT id FROM users WHERE (email = :email OR username = :usuario) AND id <> :id LIMIT 1'
        );
        $duplicado->execute([
            'email'   => $cuenta['email'],
            'usuario' => $cuenta['username'],
            'id'      => $editar ? $id : 0,
        ]);
        if ($duplicado->fetch()) {
            $errores[] = 'Ese correo o usuario ya pertenece a otra cuenta.';
        }
    }

    if (!$errores) {
        if ($editar) {
            $datos = [
                'nombre'   => $cuenta['full_name'],
                'usuario'  => $cuenta['username'],
                'email'    => $cuenta['email'],
                'rol'      => $cuenta['role_id'],
                'activo'   => $cuenta['active'],
                'id'       => $id,
            ];
            $sql = 'UPDATE users SET full_name = :nombre, username = :usuario, email = :email,
                        role_id = :rol, active = :activo';
            if ($clave !== '') {
                $sql .= ', password = :clave';
                $datos['clave'] = password_hash($clave, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $pdo->prepare($sql)->execute($datos);
            flash('ok', 'Cuenta actualizada.');
        } else {
            $sql = 'INSERT INTO users (full_name, username, email, password, role_id, active)
                    VALUES (:nombre, :usuario, :email, :clave, :rol, :activo)';
            $pdo->prepare($sql)->execute([
                'nombre'  => $cuenta['full_name'],
                'usuario' => $cuenta['username'],
                'email'   => $cuenta['email'],
                'clave'   => password_hash($clave, PASSWORD_DEFAULT),
                'rol'     => $cuenta['role_id'],
                'activo'  => $cuenta['active'],
            ]);
            flash('ok', 'Cuenta creada. Comparte la contraseña con la persona.');
        }

        redirigir('usuarios.php');
    }
}

$titulo    = $editar ? 'Editar cuenta' : 'Nueva cuenta';
$subtitulo = 'El rol define a qué páginas puede entrar la persona';

require __DIR__ . '/includes/header.php';
?>

<?php foreach ($errores as $mensaje): ?>
    <div class="aviso aviso--error" role="alert"><?= e($mensaje) ?></div>
<?php endforeach; ?>

<form method="post" class="formulario formulario--hoja">
    <?= csrf_campo() ?>

    <div class="campo">
        <label for="full_name">Nombre completo</label>
        <input class="control" type="text" id="full_name" name="full_name" value="<?= e($cuenta['full_name']) ?>" required>
    </div>

    <div class="campo-doble">
        <div class="campo">
            <label for="username">Usuario</label>
            <input class="control" type="text" id="username" name="username" value="<?= e($cuenta['username']) ?>" required>
        </div>
        <div class="campo">
            <label for="email">Correo</label>
            <input class="control" type="email" id="email" name="email" value="<?= e($cuenta['email']) ?>" required>
        </div>
    </div>

    <div class="campo-doble">
        <div class="campo">
            <label for="role_id">Rol</label>
            <select class="control" id="role_id" name="role_id">
                <?php foreach ($roles as $rol): ?>
                    <option value="<?= (int) $rol['id'] ?>" <?= (int) $cuenta['role_id'] === (int) $rol['id'] ? 'selected' : '' ?>>
                        <?= e(rol_etiqueta($rol['name'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="clave"><?= $editar ? 'Nueva contraseña (opcional)' : 'Contraseña' ?></label>
            <input class="control" type="password" id="clave" name="clave" autocomplete="new-password"
                   <?= $editar ? '' : 'required' ?>>
        </div>
    </div>

    <div class="campo">
        <label for="repetir">Repetir contraseña</label>
        <input class="control" type="password" id="repetir" name="repetir" autocomplete="new-password"
               <?= $editar ? '' : 'required' ?>>
        <p class="campo__ayuda">Mínimo 8 caracteres. Se guarda cifrada en la base.</p>
    </div>

    <label class="casilla">
        <input type="checkbox" name="active" value="1" <?= $cuenta['active'] ? 'checked' : '' ?>>
        <span>Cuenta activa</span>
    </label>

    <div class="formulario__acciones">
        <a class="boton boton--fantasma" href="usuarios.php">Cancelar</a>
        <button class="boton boton--principal" type="submit">
            <?= $editar ? 'Guardar cambios' : 'Crear cuenta' ?>
        </button>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
