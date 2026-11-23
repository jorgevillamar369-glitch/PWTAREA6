<?php
/**
 * Piezas compartidas para mostrar comandas: la tarjeta con forma
 * de tique que aparece en el panel, en la lista y en el detalle.
 */

declare(strict_types=1);

/** Trae las líneas de varios pedidos en una sola consulta. */
function items_de_pedidos(PDO $pdo, array $ids): array
{
    if (!$ids) {
        return [];
    }

    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT oi.order_id, oi.quantity, oi.unit_price, d.name
            FROM order_items oi
            JOIN dishes d ON d.id = oi.dish_id
            WHERE oi.order_id IN ($marcadores)
            ORDER BY oi.id";
    $consulta = $pdo->prepare($sql);
    $consulta->execute(array_values($ids));

    $agrupados = [];
    foreach ($consulta->fetchAll() as $linea) {
        $agrupados[(int) $linea['order_id']][] = $linea;
    }
    return $agrupados;
}

/**
 * Tarjeta de comanda.
 *
 * @param array  $pedido      Fila con id, status, table_number, created_at, cliente, camarero, total
 * @param array  $items       Líneas del pedido
 * @param string $rol         Rol del usuario en sesión
 * @param bool   $verCliente  Si se muestra el nombre del cliente
 * @param string $volver      Página a la que regresa el formulario de estado
 */
function comanda_ticket(
    array $pedido,
    array $items,
    string $rol,
    bool $verCliente = true,
    string $volver = 'detalle'
): void {
    $siguiente = siguiente_estado($rol, $pedido['status']);
    $codigo    = 'P-' . str_pad((string) $pedido['id'], 4, '0', STR_PAD_LEFT);
    $total     = (float) $pedido['total'];
    ?>
    <article class="tique">
        <header class="tique__cabecera">
            <span class="tique__codigo"><?= e($codigo) ?></span>
            <span class="tique__hora"><?= hora($pedido['created_at']) ?></span>
        </header>

        <p class="tique__meta">
            <?= $pedido['table_number'] ? 'Mesa ' . (int) $pedido['table_number'] : 'Para llevar' ?>
            <?php if ($verCliente): ?>
                · <?= e($pedido['cliente']) ?>
            <?php endif; ?>
        </p>

        <ul class="tique__lineas">
            <?php foreach ($items as $linea): ?>
                <li>
                    <span><?= (int) $linea['quantity'] ?> × <?= e($linea['name']) ?></span>
                    <span class="cifra"><?= dinero((float) $linea['quantity'] * (float) $linea['unit_price']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($pedido['notes'])): ?>
            <p class="tique__nota"><?= e($pedido['notes']) ?></p>
        <?php endif; ?>

        <div class="tique__total">
            <span>Total</span>
            <span class="cifra"><?= dinero($total) ?></span>
        </div>

        <footer class="tique__pie">
            <span class="estado estado--<?= estado_clase($pedido['status']) ?>">
                <?= e(estado_etiqueta($pedido['status'])) ?>
            </span>

            <span class="tique__acciones">
                <a class="enlace-discreto" href="pedido_detalle.php?id=<?= (int) $pedido['id'] ?>">Detalle</a>

                <?php if ($siguiente): ?>
                    <form method="post" action="pedido_estado.php" class="en-linea">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= (int) $pedido['id'] ?>">
                        <input type="hidden" name="volver" value="<?= e($volver) ?>">
                        <button class="boton boton--pequeno boton--fantasma" type="submit">
                            <?= e(accion_estado($pedido['status'])) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </span>
        </footer>
    </article>
    <?php
}
