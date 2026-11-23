<?php
/**
 * Carta del restaurante para el administrador: alta, edición,
 * disponibilidad y eliminación de platos.
 */

require_once __DIR__ . '/includes/config.php';

$usuario = requiere_rol(['Administrator']);
$busqueda = trim($_GET['q'] ?? '');

$sql = 'SELECT d.*,
               (SELECT COUNT(*) FROM order_items oi WHERE oi.dish_id = d.id) AS pedidos
        FROM dishes d';
$params = [];
if ($busqueda !== '') {
    $sql .= ' WHERE d.name LIKE :nombre OR d.category LIKE :categoria';
    $params['nombre'] = '%' . $busqueda . '%';
    $params['categoria'] = '%' . $busqueda . '%';
}
$sql .= ' ORDER BY FIELD(d.category, "Entradas", "Platos fuertes", "Postres", "Bebidas"), d.name';

$consulta = $pdo->prepare($sql);
$consulta->execute($params);
$platos = $consulta->fetchAll();

$disponibles = count(array_filter($platos, fn ($p) => (int) $p['available'] === 1));

$titulo    = 'Carta';
$subtitulo = count($platos) . ' platos · ' . $disponibles . ' disponibles hoy';

require __DIR__ . '/includes/header.php';
?>

<div class="barra-herramientas">
    <form class="buscador" method="get" action="platos.php">
        <label class="visualmente-oculto" for="q">Buscar plato</label>
        <?= icono('buscar', 16) ?>
        <input type="search" id="q" name="q" value="<?= e($busqueda) ?>" placeholder="Nombre o categoría">
        <?php if ($busqueda !== ''): ?>
            <a class="buscador__limpiar" href="platos.php">Limpiar</a>
        <?php endif; ?>
    </form>

    <a class="boton boton--principal" href="plato_form.php"><?= icono('nuevo', 16) ?> Nuevo plato</a>
</div>

<?php if ($platos): ?>
    <div class="tabla-envoltura">
        <table class="tabla tabla--platos">
            <thead>
                <tr>
                    <th scope="col">Plato</th>
                    <th scope="col">Categoría</th>
                    <th scope="col" class="der">Precio</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="der">Pedidos</th>
                    <th scope="col" class="der"><span class="visualmente-oculto">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($platos as $plato): ?>
                    <tr>
                        <td>
                            <div class="celda-plato">
                                <?php if ($plato['image'] && is_file(__DIR__ . '/assets/img/' . $plato['image'])): ?>
                                    <img src="assets/img/<?= e($plato['image']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="celda-plato__sinfoto" aria-hidden="true"></span>
                                <?php endif; ?>
                                <div>
                                    <strong><?= e($plato['name']) ?></strong>
                                    <span class="tabla__meta"><?= e($plato['description']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?= e($plato['category']) ?></td>
                        <td class="der cifra"><?= dinero((float) $plato['price']) ?></td>
                        <td>
                            <span class="estado estado--<?= $plato['available'] ? 'listo' : 'agotado' ?>">
                                <?= $plato['available'] ? 'Disponible' : 'Agotado' ?>
                            </span>
                        </td>
                        <td class="der cifra"><?= (int) $plato['pedidos'] ?></td>
                        <td class="der">
                            <div class="acciones-fila">
                                <a class="boton boton--pequeno boton--fantasma" href="plato_form.php?id=<?= (int) $plato['id'] ?>">
                                    <?= icono('lapiz', 14) ?> Editar
                                </a>
                                <form method="post" action="plato_estado.php" class="en-linea">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $plato['id'] ?>">
                                    <button class="boton boton--pequeno boton--fantasma" type="submit">
                                        <?= $plato['available'] ? 'Marcar agotado' : 'Volver a la carta' ?>
                                    </button>
                                </form>
                                <form method="post" action="plato_eliminar.php" class="en-linea"
                                      data-confirmar="¿Eliminar «<?= e($plato['name']) ?>» de la carta?">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $plato['id'] ?>">
                                    <button class="boton boton--pequeno boton--peligro" type="submit"
                                            title="Eliminar plato">
                                        <?= icono('basura', 14) ?>
                                        <span class="visualmente-oculto">Eliminar <?= e($plato['name']) ?></span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="nota-gris">
        Un plato con pedidos registrados no se elimina: se marca como agotado para conservar el historial.
    </p>
<?php else: ?>
    <div class="vacio">
        <p class="vacio__titulo"><?= $busqueda !== '' ? 'Ningún plato coincide' : 'La carta está vacía' ?></p>
        <p class="vacio__texto"><?= $busqueda !== '' ? 'Prueba con otro nombre.' : 'Crea el primer plato para que aparezca en la carta pública.' ?></p>
        <a class="boton boton--principal" href="plato_form.php">Nuevo plato</a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
