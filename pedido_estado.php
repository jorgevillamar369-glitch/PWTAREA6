<?php
/**
 * Cambia el estado de un pedido según el rol de la sesión.
 * Solo acepta las transiciones permitidas en funciones.php.
 */

require_once __DIR__ . '/includes/config.php';

$usuario = requiere_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('pedidos.php');
}

csrf_validar();

$id = (int) ($_POST['id'] ?? 0);
$volver = $_POST['volver'] ?? 'detalle';

if ($id <= 0) {
    flash('error', 'El pedido indicado no existe.');
    redirigir('pedidos.php');
}

$consulta = $pdo->prepare('SELECT id, status FROM orders WHERE id = :id');
$consulta->execute(['id' => $id]);
$pedido = $consulta->fetch();

if (!$pedido) {
    flash('error', 'El pedido indicado no existe.');
    redirigir('pedidos.php');
}

$siguiente = siguiente_estado($usuario['role'], $pedido['status']);

if ($siguiente === null) {
    http_response_code(403);
    flash('error', 'Tu rol no puede mover este pedido desde el estado actual.');
    redirigir('pedido_detalle.php?id=' . $id);
}

$actualizar = $pdo->prepare('UPDATE orders SET status = :estado WHERE id = :id');
$actualizar->execute(['estado' => $siguiente, 'id' => $id]);

flash('ok', 'Pedido P-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT) . ': ahora está '
    . mb_strtolower(estado_etiqueta($siguiente)) . '.');

$destinos = [
    'panel'   => 'panel.php',
    'pedidos' => 'pedidos.php',
    'detalle' => 'pedido_detalle.php?id=' . $id,
];
redirigir($destinos[$volver] ?? $destinos['detalle']);
