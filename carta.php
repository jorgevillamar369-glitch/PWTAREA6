<?php
/**
 * Carta pública del restaurante. Los clientes con sesión pueden
 * iniciar un pedido desde cada plato.
 */

require_once __DIR__ . '/includes/config.php';

$titulo  = 'Carta';
$activo  = 'carta';
$usuario = usuario_actual();

$platos = $pdo->query(
    'SELECT id, name, description, price, category, image, available
     FROM dishes
     ORDER BY FIELD(category, "Entradas", "Platos fuertes", "Postres", "Bebidas"), name'
)->fetchAll();

$categorias = [];
foreach ($platos as $plato) {
    $categorias[$plato['category']] = true;
}

require __DIR__ . '/includes/sitio_header.php';
?>

<main id="contenido">
    <section class="contenedor carta-cabecera">
        <div>
            <p class="sobrelinea">Guayaquil · martes a domingo</p>
            <h1>La carta</h1>
            <p class="entrada">
                Cocina de mercado con producto del día. Los pedidos se preparan
                al momento; si algo se agota, aparece marcado en esta misma página.
            </p>
        </div>
        <div class="carta-cabecera__dato">
            <p class="etiqueta-mono">Actualizada</p>
            <p class="cifra"><?= date('j') ?> <?= ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'][(int) date('n')] ?> <?= date('Y') ?></p>
            <p class="carta-cabecera__nota">Precios en dólares.</p>
        </div>
    </section>

    <section class="contenedor carta-cuerpo">
        <div class="filtros" role="group" aria-label="Filtrar la carta por categoría">
            <button class="filtro activo" type="button" data-filtro="todos">Todo</button>
            <?php foreach (array_keys($categorias) as $categoria): ?>
                <button class="filtro" type="button" data-filtro="<?= e($categoria) ?>"><?= e($categoria) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="carta-lista" data-lista-carta>
            <?php foreach ($platos as $plato): ?>
                <article class="carta-plato <?= $plato['available'] ? '' : 'carta-plato--agotado' ?>"
                         data-categoria="<?= e($plato['category']) ?>">
                    <?php if ($plato['image']): ?>
                        <img src="assets/img/<?= e($plato['image']) ?>" alt="<?= e($plato['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="carta-plato__sinfoto" aria-hidden="true"></span>
                    <?php endif; ?>

                    <div class="carta-plato__texto">
                        <div class="carta-plato__titulo">
                            <h2><?= e($plato['name']) ?></h2>
                            <span class="cifra"><?= dinero((float) $plato['price']) ?></span>
                        </div>
                        <p class="carta-plato__desc"><?= e($plato['description']) ?></p>
                        <p class="carta-plato__meta">
                            <span><?= e($plato['category']) ?></span>
                            <?php if (!$plato['available']): ?>
                                <span class="estado estado--agotado">Agotado hoy</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="carta-plato__accion">
                        <?php if (!$plato['available']): ?>
                            <span class="enlace-discreto">No disponible</span>
                        <?php elseif ($usuario && $usuario['role'] === 'Client'): ?>
                            <a class="boton boton--pequeno boton--fantasma" href="pedido_nuevo.php?plato=<?= (int) $plato['id'] ?>">
                                Pedir
                            </a>
                        <?php elseif ($usuario): ?>
                            <a class="boton boton--pequeno boton--fantasma" href="pedido_nuevo.php?plato=<?= (int) $plato['id'] ?>">
                                Registrar
                            </a>
                        <?php else: ?>
                            <a class="boton boton--pequeno boton--fantasma" href="login.php">Pedir</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="carta-nota">
            Para pedir en línea necesitas una cuenta de cliente.
            <a href="registro.php">Crear cuenta</a> · <a href="login.php">Ya tengo cuenta</a>
        </p>
    </section>
</main>

<?php require __DIR__ . '/includes/sitio_footer.php'; ?>
