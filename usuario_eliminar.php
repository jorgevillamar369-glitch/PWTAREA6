<?php
/**
 * Elimina una cuenta sin historial. Si tiene pedidos o atendió
 * pedidos, solo se puede suspender.
 */

require_once __DIR__ . '/includes/config.php';

$usuario = requiere_rol(['Administrator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('usuarios.php');
}
csrf_validar();

$id = (int) ($_POST['id'] ?? 0);

if ($id === (int) $usuario['id']) {
    flash('error', 'No puedes eliminar tu propia cuenta.');
    redirigir('usuarios.php');
}

$consulta = $pdo->prepare(
    'SELECT u.full_name,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id OR o.waiter_id = u.id) AS pedidos
     FROM users u WHERE u.id = :id'
);
$consulta->execute(['id' => $id]);
$cuenta = $consulta->fetch();

if (!$cuenta) {
    flash('error', 'Esa cuenta no existe.');
    redirigir('usuarios.php');
}

if ((int) $cuenta['pedidos'] > 0) {
    flash('error', 'La cuenta de ' . $cuenta['full_name'] . ' tiene pedidos: suspéndela en lugar de eliminarla.');
    redirigir('usuarios.php');
}

$pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
flash('ok', 'La cuenta de ' . $cuenta['full_name'] . ' se eliminó.');
redirigir('usuarios.php');
