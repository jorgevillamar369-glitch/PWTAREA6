<?php
/**
 * Elimina un pedido pendiente. Los pedidos que ya entraron a
 * cocina se conservan como historial del servicio.
 */

require_once __DIR__ . '/includes/config.php';

requiere_rol(['Administrator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('pedidos.php');
}
csrf_validar();

$id = (int) ($_POST['id'] ?? 0);
$consulta = $pdo->prepare('SELECT id, status FROM orders WHERE id = :id');
$consulta->execute(['id' => $id]);
$pedido = $consulta->fetch();

if (!$pedido) {
    flash('error', 'El pedido indicado no existe.');
    redirigir('pedidos.php');
}

if ($pedido['status'] !== 'Pending') {
    flash('error', 'Solo se eliminan pedidos pendientes. Este ya entró a cocina.');
    redirigir('pedido_detalle.php?id=' . $id);
}

$pdo->prepare('DELETE FROM orders WHERE id = :id')->execute(['id' => $id]);
flash('ok', 'Pedido P-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT) . ' eliminado.');
redirigir('pedidos.php');
