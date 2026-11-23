<?php
/**
 * Gestión de cuentas. Solo el administrador crea usuarios, cambia
 * roles y suspende cuentas.
 */

require_once __DIR__ . '/includes/config.php';

$usuario = requiere_rol(['Administrator']);

$busqueda = trim($_GET['q'] ?? '');
$rolFiltro = $_GET['rol'] ?? 'todos';
$roles = $pdo->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();

$sql = "SELECT u.id, u.full_name, u.username, u.email, u.active, u.created_at, r.name AS role,
               (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS pedidos
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE 1=1";
$params = [];

if ($busqueda !== '') {
    $sql .= ' AND (u.full_name LIKE :nombre OR u.email LIKE :correo OR u.username LIKE :usuario)';
    $params['nombre'] = '%' . $busqueda . '%';
    $params['correo'] = '%' . $busqueda . '%';
    $params['usuario'] = '%' . $busqueda . '%';
}
if ($rolFiltro !== 'todos' && in_array($rolFiltro, array_column($roles, 'name'), true)) {
    $sql .= ' AND r.name = :rol';
    $params['rol'] = $rolFiltro;
}
$sql .= ' ORDER BY r.id, u.full_name';

$consulta = $pdo->prepare($sql);
$consulta->execute($params);
$cuentas = $consulta->fetchAll();

$activos = count(array_filter($cuentas, fn ($c) => (int) $c['active'] === 1));

$titulo    = 'Usuarios';
$subtitulo = count($cuentas) . ' cuentas · ' . $activos . ' activas';

require __DIR__ . '/includes/header.php';
?>

<div class="barra-herramientas">
    <form class="buscador" method="get" action="usuarios.php">
        <label class="visualmente-oculto" for="q">Buscar cuenta</label>
        <?= icono('buscar', 16) ?>
        <input type="search" id="q" name="q" value="<?= e($busqueda) ?>" placeholder="Nombre, correo o usuario">
        <input type="hidden" name="rol" value="<?= e($rolFiltro) ?>">
        <?php if ($busqueda !== ''): ?>
            <a class="buscador__limpiar" href="usuarios.php?rol=<?= e($rolFiltro) ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <nav class="pestanas pestanas--suaves" aria-label="Filtrar por rol">
        <a class="pestana <?= $rolFiltro === 'todos' ? 'activa' : '' ?>" href="usuarios.php">Todos</a>
        <?php foreach ($roles as $rol): ?>
            <a class="pestana <?= $rolFiltro === $rol['name'] ? 'activa' : '' ?>"
               href="usuarios.php?rol=<?= e($rol['name']) ?>"><?= e(rol_etiqueta($rol['name'])) ?></a>
        <?php endforeach; ?>
    </nav>

    <a class="boton boton--principal" href="usuario_form.php"><?= icono('nuevo', 16) ?> Nueva cuenta</a>
</div>

<?php if ($cuentas): ?>
    <div class="tabla-envoltura">
        <table class="tabla tabla--usuarios">
            <thead>
                <tr>
                    <th scope="col">Cuenta</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="der">Pedidos</th>
                    <th scope="col" class="der"><span class="visualmente-oculto">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cuentas as $cuenta): ?>
                    <?php $esMiCuenta = (int) $cuenta['id'] === (int) $usuario['id']; ?>
                    <tr>
                        <td>
                            <div class="celda-usuario">
                                <span class="inicial" aria-hidden="true"><?= e(mb_substr($cuenta['full_name'], 0, 1)) ?></span>
                                <div>
                                    <strong><?= e($cuenta['full_name']) ?><?= $esMiCuenta ? ' <span class="tabla__meta">(tú)</span>' : '' ?></strong>
                                    <span class="tabla__meta"><?= e($cuenta['email']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="cifra"><?= e($cuenta['username']) ?></td>
                        <td><?= e(rol_etiqueta($cuenta['role'])) ?></td>
                        <td>
                            <span class="estado estado--<?= $cuenta['active'] ? 'listo' : 'agotado' ?>">
                                <?= $cuenta['active'] ? 'Activa' : 'Suspendida' ?>
                            </span>
                        </td>
                        <td class="der cifra"><?= $cuenta['role'] === 'Client' ? (int) $cuenta['pedidos'] : '—' ?></td>
                        <td class="der">
                            <div class="acciones-fila">
                                <a class="boton boton--pequeno boton--fantasma" href="usuario_form.php?id=<?= (int) $cuenta['id'] ?>">
                                    <?= icono('lapiz', 14) ?> Editar
                                </a>

                                <?php if (!$esMiCuenta): ?>
                                    <form method="post" action="usuario_accion.php" class="en-linea">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id" value="<?= (int) $cuenta['id'] ?>">
                                        <input type="hidden" name="accion" value="<?= $cuenta['active'] ? 'suspender' : 'activar' ?>">
                                        <button class="boton boton--pequeno boton--fantasma" type="submit">
                                            <?= $cuenta['active'] ? 'Suspender' : 'Activar' ?>
                                        </button>
                                    </form>

                                    <form method="post" action="usuario_eliminar.php" class="en-linea"
                                          data-confirmar="¿Eliminar la cuenta de <?= e($cuenta['full_name']) ?>?">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id" value="<?= (int) $cuenta['id'] ?>">
                                        <button class="boton boton--pequeno boton--peligro" type="submit"
                                                title="Eliminar cuenta">
                                            <?= icono('basura', 14) ?>
                                            <span class="visualmente-oculto">Eliminar <?= e($cuenta['full_name']) ?></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="nota-gris">
        Una cuenta con pedidos no se elimina: se suspende, así el historial del servicio queda completo.
    </p>
<?php else: ?>
    <div class="vacio">
        <p class="vacio__titulo">Sin cuentas que coincidan</p>
        <p class="vacio__texto">Cambia la búsqueda o el filtro de rol.</p>
        <a class="boton boton--fantasma" href="usuarios.php">Ver todas</a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
