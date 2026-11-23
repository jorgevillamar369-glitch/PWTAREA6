<?php
/**
 * Detalle de un pedido: líneas, total, avance del estado y
 * acciones permitidas para el rol en sesión.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/comanda.php';

$usuario = requiere_sesion();
$rol     = $usuario['role'];

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'El pedido indicado no existe.');
    redirigir('pedidos.php');
}

$sql = 'SELECT o.id, o.user_id, o.status, o.table_number, o.notes, o.created_at, o.updated_at,
               u.full_name AS cliente, u.email AS cliente_email, w.full_name AS camarero
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN users w ON w.id = o.waiter_id
        WHERE o.id = :id';
$consulta = $pdo->prepare($sql);
$consulta->execute(['id' => $id]);
$pedido = $consulta->fetch();

if (!$pedido) {
    flash('error', 'El Pedido P-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT) . ' no existe.');
    redirigir('pedidos.php');
}

if ($rol === 'Client' && (int) $pedido['user_id'] !== (int) $usuario['id']) {
    http_response_code(403);
    mostrar_403($usuario, 'Este pedido está a nombre de otra cuenta. Abre los tuyos desde <strong>Mis pedidos</strong>.');
    exit;
}

$consulta = $pdo->prepare(
    'SELECT oi.quantity, oi.unit_price, d.name, d.category
     FROM order_items oi
     JOIN dishes d ON d.id = oi.dish_id
     WHERE oi.order_id = :id
     ORDER BY oi.id'
);
$consulta->execute(['id' => $id]);
$lineas = $consulta->fetchAll();

$total = array_sum(array_map(
    fn ($l) => (float) $l['quantity'] * (float) $l['unit_price'],
    $lineas
));

$siguiente = siguiente_estado($rol, $pedido['status']);
$pasos = ['Pending', 'Preparing', 'Ready to serve', 'Served'];
$indiceActual = array_search($pedido['status'], $pasos, true);
$volver = $_GET['volver'] ?? 'detalle';
$codigo = 'P-' . str_pad((string) $pedido['id'], 4, '0', STR_PAD_LEFT);

$titulo = 'Pedido ' . $codigo;
$subtitulo = 'Mesa ' . ($pedido['table_number'] ?: '—') . ' · ' . fecha_hora($pedido['created_at']);

require __DIR__ . '/includes/header.php';
?>

<div class="detalle">
    <div class="detalle__principal">
        <article class="tique tique--grande">
            <header class="tique__cabecera">
                <span class="tique__codigo"><?= e($codigo) ?></span>
                <span class="estado estado--<?= estado_clase($pedido['status']) ?>">
                    <?= e(estado_etiqueta($pedido['status'])) ?>
                </span>
            </header>

            <dl class="detalle__datos">
                <div>
                    <dt>Mesa</dt>
                    <dd><?= $pedido['table_number'] ? 'Mesa ' . (int) $pedido['table_number'] : 'Para llevar' ?></dd>
                </div>
                <div>
                    <dt>Cliente</dt>
                    <dd><?= e($pedido['cliente']) ?></dd>
                </div>
                <div>
                    <dt>Atendido por</dt>
                    <dd><?= $pedido['camarero'] ? e($pedido['camarero']) : 'Sin asignar' ?></dd>
                </div>
                <div>
                    <dt>Registrado</dt>
                    <dd><?= fecha_hora($pedido['created_at']) ?></dd>
                </div>
            </dl>

            <?php if (!empty($pedido['notes'])): ?>
                <p class="detalle__nota"><span>Nota del pedido</span><?= e($pedido['notes']) ?></p>
            <?php endif; ?>

            <table class="tabla tabla--detalle">
                <thead>
                    <tr>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Plato</th>
                        <th scope="col" class="der">Precio</th>
                        <th scope="col" class="der">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lineas as $linea): ?>
                        <tr>
                            <td class="cifra"><?= (int) $linea['quantity'] ?></td>
                            <td>
                                <?= e($linea['name']) ?>
                                <span class="tabla__meta"><?= e($linea['category']) ?></span>
                            </td>
                            <td class="der cifra"><?= dinero((float) $linea['unit_price']) ?></td>
                            <td class="der cifra"><?= dinero((float) $linea['quantity'] * (float) $linea['unit_price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td class="der cifra"><?= dinero($total) ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="detalle__acciones">
                <a class="boton boton--fantasma" href="pedidos.php">Volver a la lista</a>
                <button class="boton boton--fantasma" type="button" data-imprimir>Imprimir pedido</button>

                <?php if ($siguiente): ?>
                    <form method="post" action="pedido_estado.php" class="en-linea">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= (int) $pedido['id'] ?>">
                        <input type="hidden" name="volver" value="detalle">
                        <button class="boton boton--principal" type="submit"><?= e(accion_estado($pedido['status'])) ?></button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($rol === 'Administrator'): ?>
                <div class="detalle__zona-riesgo">
                    <?php if ($pedido['status'] === 'Pending'): ?>
                        <form method="post" action="pedido_eliminar.php" class="en-linea"
                              data-confirmar="¿Eliminar el pedido <?= e($codigo) ?>? Esta acción no se puede deshacer.">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int) $pedido['id'] ?>">
                            <button class="boton boton--peligro" type="submit"><?= icono('basura', 16) ?> Eliminar pedido</button>
                        </form>
                        <p class="nota-gris">Solo se eliminan pedidos que todavía no entraron a cocina.</p>
                    <?php else: ?>
                        <p class="nota-gris">Un pedido que ya entró a cocina no se elimina: queda en el historial del día.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
    </div>

    <aside class="detalle__lateral">
        <div class="panel-lateral">
            <p class="sobrelinea">Avance</p>
            <ol class="linea-tiempo">
                <?php foreach ($pasos as $i => $paso): ?>
                    <li class="<?= $i < $indiceActual ? 'hecho' : ($i === $indiceActual ? 'actual' : '') ?>">
                        <span class="linea-tiempo__punto" aria-hidden="true"></span>
                        <span class="linea-tiempo__texto"><?= e(estado_etiqueta($paso)) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
            <p class="nota-gris">
                Última actualización: <?= fecha_hora($pedido['updated_at']) ?>.
            </p>
        </div>

        <div class="panel-lateral">
            <p class="sobrelinea">Quién puede moverlo</p>
            <ul class="permisos-lista">
                <li><span>Chef</span> Pendiente → listo</li>
                <li><span>Camarero</span> Listo → servido</li>
                <li><span>Administrador</span> Todos los pasos</li>
                <li><span>Cliente</span> Solo consulta</li>
            </ul>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
