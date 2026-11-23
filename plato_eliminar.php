<?php
/**
 * Elimina un plato solo si nunca se pidió. Si ya tiene pedidos,
 * se conserva para no romper el historial.
 */

require_once __DIR__ . '/includes/config.php';

requiere_rol(['Administrator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('platos.php');
}
csrf_validar();

$id = (int) ($_POST['id'] ?? 0);
$consulta = $pdo->prepare(
    'SELECT d.name, (SELECT COUNT(*) FROM order_items oi WHERE oi.dish_id = d.id) AS pedidos
     FROM dishes d WHERE d.id = :id'
);
$consulta->execute(['id' => $id]);
$plato = $consulta->fetch();

if (!$plato) {
    flash('error', 'Ese plato no existe.');
    redirigir('platos.php');
}

if ((int) $plato['pedidos'] > 0) {
    flash('error', '«' . $plato['name'] . '» tiene pedidos registrados. Márcalo como agotado en lugar de eliminarlo.');
    redirigir('platos.php');
}

$pdo->prepare('DELETE FROM dishes WHERE id = :id')->execute(['id' => $id]);
flash('ok', '«' . $plato['name'] . '» se eliminó de la carta.');
redirigir('platos.php');
