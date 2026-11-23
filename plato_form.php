<?php
/**
 * Alta y edición de platos. La fotografía se elige entre las
 * imágenes disponibles en assets/img.
 */

require_once __DIR__ . '/includes/config.php';

$usuario = requiere_rol(['Administrator']);

$id     = (int) ($_GET['id'] ?? 0);
$editar = $id > 0;
$errores = [];

$plato = [
    'name'        => '',
    'description' => '',
    'price'       => '',
    'category'    => 'Platos fuertes',
    'image'       => '',
    'available'   => 1,
];

if ($editar) {
    $consulta = $pdo->prepare('SELECT * FROM dishes WHERE id = :id');
    $consulta->execute(['id' => $id]);
    $encontrado = $consulta->fetch();
    if (!$encontrado) {
        flash('error', 'Ese plato no existe.');
        redirigir('platos.php');
    }
    $plato = $encontrado;
}

// Fotografías de platos disponibles en la carpeta del proyecto.
$fotos = array_values(array_filter(
    scandir(__DIR__ . '/assets/img'),
    fn ($archivo) => preg_match('/^plato-.*\.(jpe?g|png|webp)$/i', $archivo)
));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $plato['name']        = trim($_POST['name'] ?? '');
    $plato['description'] = trim($_POST['description'] ?? '');
    $plato['price']       = str_replace(',', '.', trim($_POST['price'] ?? ''));
    $plato['category']    = $_POST['category'] ?? 'Platos fuertes';
    $plato['image']       = $_POST['image'] ?? '';
    $plato['available']   = isset($_POST['available']) ? 1 : 0;

    $categoriasValidas = ['Entradas', 'Platos fuertes', 'Postres', 'Bebidas'];

    if (mb_strlen($plato['name']) < 2 || mb_strlen($plato['name']) > 50) {
        $errores[] = 'El nombre debe tener entre 2 y 50 caracteres.';
    }
    if (mb_strlen($plato['description']) > 255) {
        $errores[] = 'La descripción no puede pasar de 255 caracteres.';
    }
    if (!is_numeric($plato['price']) || (float) $plato['price'] <= 0 || (float) $plato['price'] > 999) {
        $errores[] = 'El precio debe ser mayor que 0 y menor que 999.';
    }
    if (!in_array($plato['category'], $categoriasValidas, true)) {
        $errores[] = 'Elige una categoría de la lista.';
    }
    if ($plato['image'] !== '' && !in_array($plato['image'], $fotos, true)) {
        $errores[] = 'La fotografía elegida no está en la carpeta del proyecto.';
    }

    if (!$errores) {
        $datos = [
            'nombre'      => $plato['name'],
            'descripcion' => $plato['description'] === '' ? null : $plato['description'],
            'precio'      => (float) $plato['price'],
            'categoria'   => $plato['category'],
            'imagen'      => $plato['image'] === '' ? null : $plato['image'],
            'disponible'  => $plato['available'],
        ];

        if ($editar) {
            $datos['id'] = $id;
            $sql = 'UPDATE dishes SET name = :nombre, description = :descripcion, price = :precio,
                        category = :categoria, image = :imagen, available = :disponible
                    WHERE id = :id';
            $pdo->prepare($sql)->execute($datos);
            flash('ok', 'Plato actualizado.');
        } else {
            $sql = 'INSERT INTO dishes (name, description, price, category, image, available)
                    VALUES (:nombre, :descripcion, :precio, :categoria, :imagen, :disponible)';
            $pdo->prepare($sql)->execute($datos);
            flash('ok', 'Plato agregado a la carta.');
        }

        redirigir('platos.php');
    }
}

$titulo    = $editar ? 'Editar plato' : 'Nuevo plato';
$subtitulo = 'Los cambios se ven de inmediato en la carta pública';

require __DIR__ . '/includes/header.php';
?>

<?php foreach ($errores as $mensaje): ?>
    <div class="aviso aviso--error" role="alert"><?= e($mensaje) ?></div>
<?php endforeach; ?>

<form method="post" class="formulario formulario--hoja">
    <?= csrf_campo() ?>

    <div class="campo">
        <label for="name">Nombre del plato</label>
        <input class="control" type="text" id="name" name="name" maxlength="50"
               value="<?= e($plato['name']) ?>" required>
    </div>

    <div class="campo">
        <label for="description">Descripción de la carta</label>
        <textarea class="control" id="description" name="description" rows="3" maxlength="255"
                  placeholder="Ingredientes y guarnición"><?= e($plato['description']) ?></textarea>
        <p class="campo__ayuda">Máximo 255 caracteres. Aparece bajo el nombre en la carta.</p>
    </div>

    <div class="campo-doble">
        <div class="campo">
            <label for="price">Precio (USD)</label>
            <input class="control" type="text" id="price" name="price" inputmode="decimal"
                   value="<?= e((string) $plato['price']) ?>" placeholder="9.50" required>
        </div>
        <div class="campo">
            <label for="category">Categoría</label>
            <select class="control" id="category" name="category">
                <?php foreach (['Entradas', 'Platos fuertes', 'Postres', 'Bebidas'] as $categoria): ?>
                    <option value="<?= $categoria ?>" <?= $plato['category'] === $categoria ? 'selected' : '' ?>>
                        <?= $categoria ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="campo">
        <label for="image">Fotografía</label>
        <select class="control" id="image" name="image">
            <option value="">Sin fotografía</option>
            <?php foreach ($fotos as $foto): ?>
                <option value="<?= e($foto) ?>" <?= $plato['image'] === $foto ? 'selected' : '' ?>>
                    <?= e($foto) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="campo__ayuda">Las fotos de platos viven en <code>assets/img</code> con el prefijo <code>plato-</code>. Si no hay foto, la tarjeta usa un fondo liso.</p>
    </div>

    <label class="casilla">
        <input type="checkbox" name="available" value="1" <?= $plato['available'] ? 'checked' : '' ?>>
        <span>Disponible hoy en la carta</span>
    </label>

    <div class="formulario__acciones">
        <a class="boton boton--fantasma" href="platos.php">Cancelar</a>
        <button class="boton boton--principal" type="submit">
            <?= $editar ? 'Guardar cambios' : 'Agregar a la carta' ?>
        </button>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
