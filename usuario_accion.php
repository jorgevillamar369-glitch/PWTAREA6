<?php
/**
 * Activa o suspende una cuenta.
 */

require_once __DIR__ . '/includes/config.php';

$usuario = requiere_rol(['Administrator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('usuarios.php');
}
csrf_validar();

$id = (int) ($_POST['id'] ?? 0);
$accion = $_POST['accion'] ?? '';

if ($id === (int) $usuario['id']) {
    flash('error', 'No puedes suspender tu propia cuenta.');
    redirigir('usuarios.php');
}

$consulta = $pdo->prepare('SELECT full_name FROM users WHERE id = :id');
$consulta->execute(['id' => $id]);
$cuenta = $consulta->fetch();

if (!$cuenta) {
    flash('error', 'Esa cuenta no existe.');
    redirigir('usuarios.php');
}

$activo = $accion === 'activar' ? 1 : 0;
$pdo->prepare('UPDATE users SET active = :activo WHERE id = :id')
    ->execute(['activo' => $activo, 'id' => $id]);

flash('ok', 'La cuenta de ' . $cuenta['full_name'] . ($activo ? ' quedó activa.' : ' quedó suspendida.'));
redirigir('usuarios.php');
