<?php
/**
 * Marca un plato como disponible o agotado.
 */

require_once __DIR__ . '/includes/config.php';

requiere_rol(['Administrator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('platos.php');
}
csrf_validar();

$id = (int) ($_POST['id'] ?? 0);
$consulta = $pdo->prepare('SELECT name, available FROM dishes WHERE id = :id');
$consulta->execute(['id' => $id]);
$plato = $consulta->fetch();

if (!$plato) {
    flash('error', 'Ese plato no existe.');
    redirigir('platos.php');
}

$nuevo = $plato['available'] ? 0 : 1;
$pdo->prepare('UPDATE dishes SET available = :estado WHERE id = :id')
    ->execute(['estado' => $nuevo, 'id' => $id]);

flash('ok', '«' . $plato['name'] . '» ' . ($nuevo ? 'vuelve a la carta.' : 'queda agotado por hoy.'));
redirigir('platos.php');
