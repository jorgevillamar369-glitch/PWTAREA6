<?php
/**
 * Nuevo pedido. El cliente pide para sí mismo; el camarero y el
 * administrador registran pedidos en nombre de un cliente.
 *
 * El carrito se arma con JavaScript y el servidor vuelve a calcular
 * los precios desde la base antes de guardar.
 */

require_once __DIR__ . '/includes/config.php';

$usuario = requiere_rol(['Client', 'Waiter', 'Administrator']);
$rol     = $usuario['role'];
$esStaff = $rol !== 'Client';

$errores = [];
$inicial = [];   // dish_id => cantidad, para repintar el carrito si hay errores

// Plato que llega desde la carta (?plato=3).
$platoSugerido = (int) ($_GET['plato'] ?? 0);

$platos = $pdo->query(
    'SELECT id, name, description, price, category, image
     FROM dishes WHERE available = 1
     ORDER BY FIELD(category, "Entradas", "Platos fuertes", "Postres", "Bebidas"), name'
)->fetchAll();

$categorias = [];
foreach ($platos as $plato) {
    $categorias[$plato['category']][] = $plato;
}

$clientes = [];
if ($esStaff) {
    $clientes = $pdo->query(
        "SELECT u.id, u.full_name, u.email
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE r.name = 'Client' AND u.active = 1
         ORDER BY u.full_name"
    )->fetchAll();
}

$mesa     = $_POST['mesa'] ?? '';
$cliente  = (int) ($_POST['cliente'] ?? 0);
$notas    = trim($_POST['notas'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $items = $_POST['items'] ?? [];
    if (!is_array($items) || !$items) {
        $errores[] = 'Agrega al menos un plato al pedido.';
    }

    // Cliente válido para los pedidos que registra el personal.
    if ($esStaff) {
        $idsValidos = array_map(fn ($c) => (int) $c['id'], $clientes);
        if (!in_array($cliente, $idsValidos, true)) {
            $errores[] = 'Elige el cliente al que pertenece el pedido.';
        }
    } else {
        $cliente = (int) $usuario['id'];
    }

    if ($mesa !== '' && (!ctype_digit((string) $mesa) || (int) $mesa < 1 || (int) $mesa > 12)) {
        $errores[] = 'La mesa debe estar entre 1 y 12, o marcarse como para llevar.';
    }

    if (mb_strlen($notas) > 255) {
        $errores[] = 'La nota no puede pasar de 255 caracteres.';
    }

    // Precios y disponibilidad se leen de la base, no del formulario.
    $lineas = [];
    if (is_array($items)) {
        $consulta = $pdo->prepare('SELECT id, name, price FROM dishes WHERE id = :id AND available = 1');
        foreach ($items as $dishId => $cantidad) {
            $cantidad = (int) $cantidad;
            if ($cantidad < 1 || $cantidad > 20) {
                continue;
            }
            $consulta->execute(['id' => (int) $dishId]);
            $plato = $consulta->fetch();
            if ($plato) {
                $lineas[(int) $plato['id']] = [
                    'nombre'   => $plato['name'],
                    'precio'   => (float) $plato['price'],
                    'cantidad' => $cantidad,
                ];
                $inicial[(int) $plato['id']] = $cantidad;
            }
        }
    }

    if (!$lineas) {
        $errores[] = 'Los platos elegidos ya no están disponibles. Revisa la carta.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $insertar = $pdo->prepare(
                'INSERT INTO orders (user_id, waiter_id, table_number, status, notes)
                 VALUES (:cliente, :camarero, :mesa, "Pending", :notas)'
            );
            $insertar->execute([
                'cliente'  => $cliente,
                'camarero' => $esStaff ? $usuario['id'] : null,
                'mesa'     => $mesa === '' ? null : (int) $mesa,
                'notas'    => $notas === '' ? null : $notas,
            ]);

            $pedidoId = (int) $pdo->lastInsertId();

            $insertarLinea = $pdo->prepare(
                'INSERT INTO order_items (order_id, dish_id, quantity, unit_price)
                 VALUES (:pedido, :plato, :cantidad, :precio)'
            );
            foreach ($lineas as $dishId => $linea) {
                $insertarLinea->execute([
                    'pedido'   => $pedidoId,
                    'plato'    => $dishId,
                    'cantidad' => $linea['cantidad'],
                    'precio'   => $linea['precio'],
                ]);
            }

            $pdo->commit();
            flash('ok', 'Pedido P-' . str_pad((string) $pedidoId, 4, '0', STR_PAD_LEFT) . ' enviado a cocina.');
            redirigir('pedido_detalle.php?id=' . $pedidoId);
        } catch (PDOException $error) {
            $pdo->rollBack();
            error_log('Pedido no guardado: ' . $error->getMessage());
            $errores[] = 'No se pudo guardar el pedido. Inténtalo otra vez.';
        }
    }
}

$titulo    = 'Nuevo pedido';
$subtitulo = $esStaff
    ? 'Registra lo que el cliente pidió en la mesa'
    : 'Arma tu pedido y envíalo a cocina';

require __DIR__ . '/includes/header.php';
?>

<?php foreach ($errores as $mensaje): ?>
    <div class="aviso aviso--error" role="alert"><?= e($mensaje) ?></div>
<?php endforeach; ?>

<form method="post" class="pedido-form" data-pedido-form
      data-inicial="<?= e(json_encode($inicial ?: new stdClass())) ?>"
      data-sugerido="<?= $platoSugerido ?>">
    <?= csrf_campo() ?>

    <div class="pedido-form__carta">
        <div class="ficha-cabecera">
            <div>
                <p class="sobrelinea">Paso 1</p>
                <h2>Elige los platos</h2>
            </div>
            <p class="ficha-cabecera__nota">Solo se muestran los platos disponibles.</p>
        </div>

        <?php foreach ($categorias as $categoria => $lista): ?>
            <h3 class="grupo-platos"><?= e($categoria) ?></h3>
            <div class="elegir-platos">
                <?php foreach ($lista as $plato): ?>
                    <div class="elegir" data-plato="<?= (int) $plato['id'] ?>"
                         data-nombre="<?= e($plato['name']) ?>"
                         data-precio="<?= (float) $plato['price'] ?>">
                        <?php if ($plato['image']): ?>
                            <img src="assets/img/<?= e($plato['image']) ?>" alt="" loading="lazy">
                        <?php endif; ?>
                        <div class="elegir__texto">
                            <p class="elegir__nombre"><?= e($plato['name']) ?></p>
                            <p class="elegir__desc"><?= e($plato['description']) ?></p>
                        </div>
                        <span class="cifra elegir__precio"><?= dinero((float) $plato['price']) ?></span>
                        <button class="boton boton--pequeno boton--fantasma" type="button" data-agregar>Añadir</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <aside class="carrito" aria-label="Resumen del pedido">
        <div class="carrito__caja">
            <div class="ficha-cabecera">
                <div>
                    <p class="sobrelinea">Paso 2</p>
                    <h2>Revisa el pedido</h2>
                </div>
            </div>

            <div class="carrito__vacio" data-carrito-vacio>
                <p>Todavía no hay platos.</p>
                <p class="nota-gris">Usa «Añadir» en la lista de la izquierda.</p>
            </div>

            <ul class="carrito__lista" data-carrito-lista hidden></ul>

            <div class="carrito__total">
                <span>Total estimado</span>
                <strong class="cifra" data-carrito-total>$0,00</strong>
            </div>

            <div class="campo">
                <label for="mesa">Mesa</label>
                <select class="control" id="mesa" name="mesa">
                    <option value="">Para llevar</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= (string) $mesa === (string) $i ? 'selected' : '' ?>>Mesa <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <?php if ($esStaff): ?>
                <div class="campo">
                    <label for="cliente">Cliente</label>
                    <select class="control" id="cliente" name="cliente" required>
                        <option value="">Elige una cuenta de cliente</option>
                        <?php foreach ($clientes as $cuenta): ?>
                            <option value="<?= (int) $cuenta['id'] ?>" <?= $cliente === (int) $cuenta['id'] ? 'selected' : '' ?>>
                                <?= e($cuenta['full_name']) ?> · <?= e($cuenta['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="campo__ayuda">El pedido queda a nombre del cliente y el camarero en sesión como responsable.</p>
                </div>
            <?php else: ?>
                <div class="campo">
                    <label>Cliente</label>
                    <p class="control control--solo"><?= e($usuario['full_name']) ?></p>
                </div>
            <?php endif; ?>

            <div class="campo">
                <label for="notas">Nota para la cocina</label>
                <textarea class="control" id="notas" name="notas" rows="2" maxlength="255"
                          placeholder="Sin cebolla, término medio, cubiertos para llevar…"><?= e($notas) ?></textarea>
            </div>

            <button class="boton boton--principal boton--ancho" type="submit" data-carrito-enviar disabled>
                Enviar pedido a cocina
            </button>
            <p class="campo__ayuda">
                Los precios se confirman en el servidor al guardar. El pedido entra como pendiente.
            </p>
        </div>
    </aside>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
