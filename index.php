<?php
/**
 * Portada pública: qué es A la Carta, cómo funciona el sistema,
 * los cuatro roles y una muestra de la carta.
 */

require_once __DIR__ . '/includes/config.php';

$titulo = 'Cocina de mercado y servicio de salón';
$activo = 'inicio';

// Tres platos para la muestra de la carta.
$muestra = $pdo->query(
    'SELECT name, description, price, image
     FROM dishes
     WHERE available = 1 AND image IS NOT NULL
     ORDER BY FIELD(category, "Entradas", "Platos fuertes", "Bebidas"), id
     LIMIT 3'
)->fetchAll();

require __DIR__ . '/includes/sitio_header.php';
?>

<main id="contenido">

    <!-- Portada -->
    <section class="portada">
        <div class="contenedor portada__fila">
            <div class="portada__texto">
                <p class="sobrelinea">Guayaquil · martes a domingo</p>
                <h1>Cocina de mercado,<br>a la carta.</h1>
                <div class="portada__acciones">
                    <a class="boton boton--principal" href="carta.php">Ver la carta</a>
                    <a class="boton boton--fantasma" href="login.php">Entrar al sistema</a>
                </div>
                <p class="portada__pie">
                    Administrador, chef, camarero y cliente: cuatro accesos distintos
                    sobre la misma base de datos.
                </p>
            </div>

            <figure class="portada__figura">
                <img src="assets/img/hero-mesa.jpg" width="1400" height="933"
                     alt="Mesa servida con varios platos en el salón de A la Carta">
                <figcaption>Mesa 6 · servicio de mediodía</figcaption>
            </figure>
        </div>
    </section>

    <!-- Grupo de trabajo -->
    <section class="seccion-grupo" id="grupo" aria-labelledby="grupo-titulo">
        <div class="contenedor grupo-fila">
            <div class="grupo__intro">
                <p class="sobrelinea">Equipo</p>
                <h2 id="grupo-titulo">Grupo 04</h2>
                <p class="grupo__nota">
                    Cinco integrantes. Tarea 6 de Programación Web Avanzada, UBE.
                </p>
            </div>

            <ol class="grupo__lista">
                <li><span class="grupo__num">01</span><span class="grupo__nombre">Bruce Balarezo</span></li>
                <li><span class="grupo__num">02</span><span class="grupo__nombre">Norka Mideros</span></li>
                <li><span class="grupo__num">03</span><span class="grupo__nombre">Mauro Chinlle</span></li>
                <li><span class="grupo__num">04</span><span class="grupo__nombre">Jorge Villamar</span></li>
                <li><span class="grupo__num">05</span><span class="grupo__nombre">Liseth Criollo</span></li>
            </ol>
        </div>
    </section>

    <!-- Cómo funciona -->
    <section class="seccion" id="sistema">
        <div class="contenedor sistema__fila">
            <div class="sistema__texto">
                <p class="sobrelinea">El sistema</p>
                <h2>Un pedido que todos ven, cada uno desde su puesto.</h2>
                <p class="entrada">
                    Cuando se registra un pedido, cambia de estado en la medida en que
                    avanza el servicio. No hay llamadas entre la mesa y la cocina:
                    el estado del pedido es el aviso.
                </p>

                <ol class="pasos">
                    <li>
                        <span class="pasos__numero">01</span>
                        <div>
                            <h3>El cliente pide</h3>
                            <p>Arma su pedido desde la carta, indica la mesa y deja una nota si la necesita.</p>
                        </div>
                    </li>
                    <li>
                        <span class="pasos__numero">02</span>
                        <div>
                            <h3>La cocina prepara</h3>
                            <p>El chef ve el pedido en su tablero, lo toma y lo marca como listo para servir.</p>
                        </div>
                    </li>
                    <li>
                        <span class="pasos__numero">03</span>
                        <div>
                            <h3>El salón entrega</h3>
                            <p>El camarero confirma la entrega y el pedido queda en el historial.</p>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="sistema__muestra">
                <p class="etiqueta-mono">Pedido en curso</p>
                <article class="tique tique--demo" aria-label="Ejemplo de pedido">
                    <header class="tique__cabecera">
                        <span class="tique__codigo">P-0184</span>
                        <span class="tique__hora">13:42</span>
                    </header>
                    <p class="tique__meta">Mesa 4 · Ruth Quimí</p>
                    <ul class="tique__lineas">
                        <li><span>2 × Pollo guisado</span><span class="cifra">$15,60</span></li>
                        <li><span>1 × Jugo de naranja</span><span class="cifra">$2,50</span></li>
                    </ul>
                    <div class="tique__total">
                        <span>Total</span>
                        <span class="cifra">$18,10</span>
                    </div>
                    <footer class="tique__pie">
                        <span class="estado estado--preparando">En preparación</span>
                        <span class="tique__hora">Sin cebolla</span>
                    </footer>
                </article>
                <p class="sistema__nota">
                    Los estados posibles son cuatro: pendiente, en preparación,
                    listo para servir y servido.
                </p>
            </div>
        </div>
    </section>

    <!-- Roles -->
    <section class="seccion seccion--papel" id="roles">
        <div class="contenedor">
            <div class="seccion__cabecera">
                <div>
                    <p class="sobrelinea">Permisos</p>
                    <h2>Cada rol ve solo lo que le toca.</h2>
                </div>
            </div>

            <div class="roles">
                <article class="rol">
                    <p class="rol__numero">01</p>
                    <h3>Administrador</h3>
                    <p class="rol__alcance">Acceso completo</p>
                    <p>Crea, edita y elimina platos. Supervisa los pedidos y cambia roles o cuentas de usuario.</p>
                </article>
                <article class="rol">
                    <p class="rol__numero">02</p>
                    <h3>Chef</h3>
                    <p class="rol__alcance">Cocina</p>
                    <p>Ve los pedidos del día, los toma y los marca listos. No toca la carta ni las cuentas.</p>
                </article>
                <article class="rol">
                    <p class="rol__numero">03</p>
                    <h3>Camarero</h3>
                    <p class="rol__alcance">Salón</p>
                    <p>Registra pedidos en nombre del cliente, consulta la mesa y confirma la entrega.</p>
                </article>
                <article class="rol">
                    <p class="rol__numero">04</p>
                    <h3>Cliente</h3>
                    <p class="rol__alcance">Mesa y cuenta</p>
                    <p>Consulta la carta, pide desde su cuenta y sigue el estado de sus pedidos.</p>
                </article>
            </div>

            <p class="roles__nota">
                ¿Quieres verlo por dentro? En la pantalla de acceso están las cuentas de prueba
                de los cuatro roles.
            </p>
        </div>
    </section>

    <!-- Carta corta -->
    <section class="seccion">
        <div class="contenedor">
            <div class="seccion__cabecera">
                <div>
                    <p class="sobrelinea">Carta</p>
                    <h2>Lo que sale de la cocina hoy.</h2>
                </div>
                <a class="enlace-flecha" href="carta.php">
                    Ver la carta completa <?= icono('flecha', 16) ?>
                </a>
            </div>

            <div class="platos-muestra">
                <?php foreach ($muestra as $plato): ?>
                    <article class="plato-card">
                        <img class="plato-card__foto" src="assets/img/<?= e($plato['image']) ?>"
                             alt="<?= e($plato['name']) ?>">
                        <div class="plato-card__cuerpo">
                            <div class="plato-card__linea">
                                <h3><?= e($plato['name']) ?></h3>
                                <span class="cifra"><?= dinero((float) $plato['price']) ?></span>
                            </div>
                            <p><?= e($plato['description']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Cierre -->
    <section class="cierre">
        <div class="contenedor cierre__fila">
            <div>
                <p class="sobrelinea">Servicio</p>
                <h2>La cocina abre a las 12:00.<br>El sistema, cuando quieras.</h2>
            </div>
            <div class="cierre__acciones">
                <a class="boton boton--claro" href="login.php">Entrar al sistema</a>
                <a class="enlace-claro" href="registro.php">Crear cuenta de cliente</a>
            </div>
        </div>
    </section>

</main>

<?php require __DIR__ . '/includes/sitio_footer.php'; ?>

