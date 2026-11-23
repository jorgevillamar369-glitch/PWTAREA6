<?php
/**
 * Lista de pedidos. El cliente ve los suyos; el personal ve el
 * servicio completo, con filtros por estado y búsqueda por mesa
 * o nombre del cliente.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/comanda.php';

$usuario = requiere_sesion();
$rol     = $usuario['role'];
$esCliente = $rol === 'Client';

$estadoFiltro = $_GET['estado'] ?? 'todos';
if ($estadoFiltro !== 'todos' && !isset(ESTADOS[$estadoFiltro])) {
    $estadoFiltro = 'todos';
}
$busqueda = trim($_GET['q'] ?? '');
$soloMios = $rol === 'Waiter' && isset($_GET['mios']);

$titulo    = $esCliente ? 'Mis pedidos' : 'Pedidos';
$subtitulo = $esCliente
    ? 'Historial y estado de tus pedidos'
    : 'Servicio del día y pedidos pendientes de cierre';

// Condiciones comunes según el rol.
$comun  = '';
$params = [];
if ($esCliente) {
    $comun .= ' AND o.user_id = :uid';
    $params['uid'] = $usuario['id'];
}
if ($soloMios) {
    $comun .= ' AND o.waiter_id = :wid';
    $params['wid'] = $usuario['id'];
}

// Conteos por estado (sin búsqueda, para que las pestañas no bailen).
$consulta = $pdo->prepare("SELECT o.status, COUNT(*) AS total FROM orders o WHERE 1=1$comun GROUP BY o.status");
$consulta->execute($params);
$conteos = ['todos' => 0] + array_fill_keys(array_keys(ESTADOS), 0);
foreach ($consulta->fetchAll() as $fila) {
    $conteos[$fila['status']] = (int) $fila['total'];
    $conteos['todos'] += (int) $fila['total'];
}

// Lista filtrada.
$sql = "SELECT o.id, o.status, o.table_number, o.notes, o.created_at,
               u.full_name AS cliente, w.full_name AS camarero,
               (SELECT COALESCE(SUM(quantity * unit_price), 0)
                FROM order_items WHERE order_id = o.id) AS total
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN users w ON w.id = o.waiter_id
        WHERE 1=1$comun";

if ($estadoFiltro !== 'todos') {
    $sql .= ' AND o.status = :estado';
    $params['estado'] = $estadoFiltro;
}
if ($busqueda !== '') {
    $sql .= ' AND (u.full_name LIKE :texto OR o.table_number = :mesa)';
    $params['texto'] = '%' . $busqueda . '%';
    $params['mesa'] = ctype_digit($busqueda) ? (int) $busqueda : -1;
}

$sql .= ' ORDER BY FIELD(o.status, "Pending", "Preparing", "Ready to serve", "Served"), o.created_at DESC LIMIT 60';

$consulta = $pdo->prepare($sql);
$consulta->execute($params);
$pedidos = $consulta->fetchAll();
$items = items_de_pedidos($pdo, array_column($pedidos, 'id'));

$sumaLista = array_sum(array_column($pedidos, 'total'));

/** Enlace de filtro conservando búsqueda y "solo míos". */
function enlace_filtro(string $estado, string $busqueda, bool $soloMios): string
{
    $partes = ['estado' => $estado];
    if ($busqueda !== '') {
        $partes['q'] = $busqueda;
    }
    if ($soloMios) {
        $partes['mios'] = '1';
    }
    return 'pedidos.php?' . http_build_query($partes);
}

require __DIR__ . '/includes/header.php';
?>

<div class="barra-herramientas">
    <nav class="pestanas" aria-label="Filtrar por estado">
        <a class="pestana <?= $estadoFiltro === 'todos' ? 'activa' : '' ?>" href="<?= e(enlace_filtro('todos', $busqueda, $soloMios)) ?>">
            Todas <span class="pestana__cuenta cifra"><?= $conteos['todos'] ?></span>
        </a>
        <?php foreach (ESTADOS as $clave => $datos): ?>
            <a class="pestana <?= $estadoFiltro === $clave ? 'activa' : '' ?>" href="<?= e(enlace_filtro($clave, $busqueda, $soloMios)) ?>">
                <?= e($datos['etiqueta']) ?> <span class="pestana__cuenta cifra"><?= $conteos[$clave] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <form class="buscador" method="get" action="pedidos.php">
        <input type="hidden" name="estado" value="<?= e($estadoFiltro) ?>">
        <?php if ($soloMios): ?><input type="hidden" name="mios" value="1"><?php endif; ?>
        <label class="visualmente-oculto" for="q">Buscar por cliente o mesa</label>
        <?= icono('buscar', 16) ?>
        <input type="search" id="q" name="q" value="<?= e($busqueda) ?>"
               placeholder="<?= $esCliente ? 'Buscar en mis pedidos' : 'Cliente o mesa' ?>">
        <?php if ($busqueda !== ''): ?>
            <a class="buscador__limpiar" href="<?= e(enlace_filtro($estadoFiltro, '', $soloMios)) ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if ($rol === 'Waiter'): ?>
        <a class="interruptor <?= $soloMios ? 'activo' : '' ?>"
           href="<?= e('pedidos.php?estado=' . urlencode($estadoFiltro) . ($soloMios ? '' : '&mios=1')) ?>">
            Solo mis pedidos
        </a>
    <?php endif; ?>
</div>

<div class="lista-resumen">
    <p class="nota-gris">
        <?= count($pedidos) ?> <?= count($pedidos) === 1 ? 'pedido' : 'pedidos' ?>
        <?php if ($estadoFiltro !== 'todos'): ?>
            · <?= mb_strtolower(estado_etiqueta($estadoFiltro)) ?>
        <?php endif; ?>
        <?php if ($busqueda !== ''): ?>
            · búsqueda «<?= e($busqueda) ?>»
        <?php endif; ?>
    </p>
    <p class="nota-gris">Suma de la lista: <span class="cifra"><?= dinero($sumaLista) ?></span></p>
</div>

<?php if ($pedidos): ?>
    <div class="comandas">
        <?php foreach ($pedidos as $pedido): ?>
            <?php comanda_ticket($pedido, $items[$pedido['id']] ?? [], $rol, !$esCliente, 'pedidos'); ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="vacio">
        <p class="vacio__titulo">No hay pedidos con ese filtro</p>
        <p class="vacio__texto">Prueba con otra pestaña o quita la búsqueda.</p>
        <a class="boton boton--fantasma" href="pedidos.php">Ver todos los pedidos</a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
