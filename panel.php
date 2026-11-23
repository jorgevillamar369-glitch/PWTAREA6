<?php
/**
 * Panel principal. El contenido cambia según el rol:
 * el administrador supervisa, el chef cocina, el camarero
 * sirve y el cliente sigue sus pedidos.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/comanda.php';

$usuario = requiere_sesion();
$rol     = $usuario['role'];
$esStaff = $rol !== 'Client';

$titulo = match ($rol) {
    'Chef'   => 'Cocina',
    'Waiter' => 'Salón',
    default  => 'Panel del día',
};

$subtitulo = $esStaff
    ? 'Servicio del ' . date('j') . ' de ' . ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'][(int) date('n')]
    : 'Tus pedidos y el estado del servicio';

// ------------------------------------------------------------
// Indicadores
// ------------------------------------------------------------
if ($esStaff) {
    $resumen = $pdo->query(
        "SELECT
            SUM(DATE(created_at) = CURDATE())                                   AS hoy,
            SUM(DATE(created_at) = CURDATE() AND status = 'Pending')            AS pendientes,
            SUM(DATE(created_at) = CURDATE() AND status = 'Preparing')          AS preparando,
            SUM(DATE(created_at) = CURDATE() AND status = 'Ready to serve')     AS listos,
            SUM(DATE(created_at) = CURDATE() AND status = 'Served')             AS servidos,
            COUNT(DISTINCT CASE WHEN DATE(created_at) = CURDATE() AND status = 'Served' THEN table_number END) AS mesas
         FROM orders"
    )->fetch();

    $ventasHoy = (float) $pdo->query(
        "SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0)
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.status = 'Served' AND DATE(o.created_at) = CURDATE()"
    )->fetchColumn();
} else {
    $resumen = [];
    $consulta = $pdo->prepare(
        "SELECT
            SUM(status <> 'Served')                        AS en_curso,
            SUM(status = 'Served')                         AS entregados,
            COALESCE(SUM(CASE WHEN status <> 'Served' THEN 1 ELSE 0 END), 0) AS activos
         FROM orders WHERE user_id = :id"
    );
    $consulta->execute(['id' => $usuario['id']]);
    $resumen = $consulta->fetch();

    $consulta = $pdo->prepare(
        "SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0)
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.user_id = :id AND o.status = 'Served'
           AND YEAR(o.created_at) = YEAR(CURDATE())
           AND MONTH(o.created_at) = MONTH(CURDATE())"
    );
    $consulta->execute(['id' => $usuario['id']]);
    $gastoMes = (float) $consulta->fetchColumn();
}

// ------------------------------------------------------------
// Pedidos abiertos
// ------------------------------------------------------------
$sqlAbiertas = "SELECT o.id, o.status, o.table_number, o.notes, o.created_at,
                       u.full_name AS cliente, w.full_name AS camarero,
                       (SELECT COALESCE(SUM(quantity * unit_price), 0)
                        FROM order_items WHERE order_id = o.id) AS total
                FROM orders o
                JOIN users u ON u.id = o.user_id
                LEFT JOIN users w ON w.id = o.waiter_id
                WHERE o.status <> 'Served'";
$parametros = [];

if (!$esStaff) {
    $sqlAbiertas .= ' AND o.user_id = :id';
    $parametros['id'] = $usuario['id'];
}
$sqlAbiertas .= ' ORDER BY o.created_at ASC';

$consulta = $pdo->prepare($sqlAbiertas);
$consulta->execute($parametros);
$abiertas = $consulta->fetchAll();
$items = items_de_pedidos($pdo, array_column($abiertas, 'id'));

// ------------------------------------------------------------
// Pedidos cerrados recientes
// ------------------------------------------------------------
$sqlCerrados = "SELECT o.id, o.status, o.table_number, o.created_at,
                       u.full_name AS cliente,
                       (SELECT COALESCE(SUM(quantity * unit_price), 0)
                        FROM order_items WHERE order_id = o.id) AS total
                FROM orders o
                JOIN users u ON u.id = o.user_id
                WHERE o.status = 'Served'";
$parametros = [];
if (!$esStaff) {
    $sqlCerrados .= ' AND o.user_id = :id';
    $parametros['id'] = $usuario['id'];
}
$sqlCerrados .= ' ORDER BY o.created_at DESC LIMIT 5';

$consulta = $pdo->prepare($sqlCerrados);
$consulta->execute($parametros);
$cerrados = $consulta->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="kpis">
    <?php if ($rol === 'Administrator'): ?>
        <article class="kpi">
            <p class="kpi__rotulo">Pedidos hoy</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['hoy'] ?></p>
            <p class="kpi__nota"><?= (int) $resumen['servidos'] ?> ya servidos</p>
        </article>
        <article class="kpi">
            <p class="kpi__rotulo">En preparación</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['preparando'] ?></p>
            <p class="kpi__nota"><?= (int) $resumen['pendientes'] ?> esperando turno</p>
        </article>
        <article class="kpi">
            <p class="kpi__rotulo">Listos para servir</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['listos'] ?></p>
            <p class="kpi__nota">En la ventana de servicio</p>
        </article>
        <article class="kpi kpi--destacado">
            <p class="kpi__rotulo">Ventas servidas hoy</p>
            <p class="kpi__valor cifra"><?= dinero($ventasHoy) ?></p>
            <p class="kpi__nota"><?= (int) $resumen['mesas'] ?> mesas atendidas</p>
        </article>

    <?php elseif ($rol === 'Chef'): ?>
        <article class="kpi">
            <p class="kpi__rotulo">Pendientes</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['pendientes'] ?></p>
            <p class="kpi__nota">Sin empezar</p>
        </article>
        <article class="kpi">
            <p class="kpi__rotulo">En preparación</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['preparando'] ?></p>
            <p class="kpi__nota">En la línea caliente</p>
        </article>
        <article class="kpi">
            <p class="kpi__rotulo">Listos para servir</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['listos'] ?></p>
            <p class="kpi__nota">Esperando al camarero</p>
        </article>
        <article class="kpi kpi--destacado">
            <p class="kpi__rotulo">Servidos hoy</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['servidos'] ?></p>
            <p class="kpi__nota"><?= (int) $resumen['hoy'] ?> pedidos en total</p>
        </article>

    <?php elseif ($rol === 'Waiter'): ?>
        <article class="kpi">
            <p class="kpi__rotulo">Pedidos hoy</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['hoy'] ?></p>
            <p class="kpi__nota"><?= (int) $resumen['servidos'] ?> entregados</p>
        </article>
        <article class="kpi">
            <p class="kpi__rotulo">Por entregar</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['listos'] ?></p>
            <p class="kpi__nota">Listos en la ventana</p>
        </article>
        <article class="kpi">
            <p class="kpi__rotulo">Mesas atendidas</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['mesas'] ?></p>
            <p class="kpi__nota">Servicio de hoy</p>
        </article>
        <article class="kpi kpi--destacado">
            <p class="kpi__rotulo">Estado del salón</p>
            <p class="kpi__valor kpi__valor--texto"><?= (int) $resumen['pendientes'] + (int) $resumen['preparando'] ?> en cocina</p>
            <p class="kpi__nota">Aún sin llegar a la ventana</p>
        </article>

    <?php else: ?>
        <article class="kpi">
            <p class="kpi__rotulo">Pedidos en curso</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['en_curso'] ?></p>
            <p class="kpi__nota">Se actualizan desde el salón</p>
        </article>
        <article class="kpi">
            <p class="kpi__rotulo">Pedidos entregados</p>
            <p class="kpi__valor cifra"><?= (int) $resumen['entregados'] ?></p>
            <p class="kpi__nota">En tu historial</p>
        </article>
        <article class="kpi kpi--destacado">
            <p class="kpi__rotulo">Consumo de este mes</p>
            <p class="kpi__valor cifra"><?= dinero($gastoMes) ?></p>
            <p class="kpi__nota">Solo pedidos servidos</p>
        </article>
    <?php endif; ?>
</section>

<section class="bloque">
    <div class="bloque__cabecera">
        <div>
            <p class="sobrelinea"><?= $esStaff ? 'En curso' : 'Tus pedidos' ?></p>
            <h2>Pedidos abiertos</h2>
        </div>
        <div class="bloque__acciones">
            <a class="enlace-discreto" href="pedidos.php">Ver todas</a>
            <?php if (in_array($rol, ['Client', 'Waiter', 'Administrator'], true)): ?>
                <a class="boton boton--principal" href="pedido_nuevo.php"><?= icono('nuevo', 16) ?> Nuevo pedido</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($abiertas): ?>
        <div class="comandas">
            <?php foreach ($abiertas as $pedido): ?>
                <?php comanda_ticket($pedido, $items[$pedido['id']] ?? [], $rol, $esStaff, 'panel'); ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="vacio">
            <p class="vacio__titulo">No hay pedidos abiertos</p>
            <p class="vacio__texto">
                <?= $esStaff
                    ? 'Cuando entre un pedido aparecerá aquí con su mesa y sus platos.'
                    : 'Tu próximo pedido empezará a mostrarse aquí en cuanto lo envíes.' ?>
            </p>
            <?php if (!$esStaff): ?>
                <a class="boton boton--principal" href="pedido_nuevo.php">Pedir algo de la carta</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<section class="bloque">
    <div class="bloque__cabecera">
        <div>
            <p class="sobrelinea">Historial</p>
            <h2>Últimos pedidos servidos</h2>
        </div>
    </div>

    <?php if ($cerrados): ?>
        <div class="tabla-envoltura">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Registrado</th>
                        <th scope="col" class="celda-mesa">Mesa</th>
                        <?php if ($esStaff): ?><th scope="col" class="celda-cliente">Cliente</th><?php endif; ?>
                        <th scope="col" class="der">Total</th>
                        <th scope="col" class="der"><span class="visualmente-oculto">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cerrados as $pedido): ?>
                        <tr>
                            <td class="cifra">P-<?= str_pad((string) $pedido['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td class="celda-fecha"><?= fecha_hora($pedido['created_at']) ?></td>
                            <td class="celda-mesa"><?= $pedido['table_number'] ? 'Mesa ' . (int) $pedido['table_number'] : 'Para llevar' ?></td>
                            <?php if ($esStaff): ?><td class="celda-cliente"><?= e($pedido['cliente']) ?></td><?php endif; ?>
                            <td class="der cifra"><?= dinero((float) $pedido['total']) ?></td>
                            <td class="der">
                                <a class="enlace-discreto" href="pedido_detalle.php?id=<?= (int) $pedido['id'] ?>">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="nota-gris">Todavía no hay pedidos servidos.</p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
