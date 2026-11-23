<?php
/**
 * Encabezado del sitio público (portada, carta, acceso y registro).
 * Espera $titulo y, opcionalmente, $activo.
 */
$usuario = usuario_actual();
$activo  = $activo ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? APP_NOMBRE) ?> · <?= APP_NOMBRE ?></title>
    <meta name="description" content="A la Carta: cocina de mercado en Guayaquil y sistema de servicio para el salón, la cocina y los clientes.">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/fuentes.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="sitio">
<a class="salto-contenido" href="#contenido">Ir al contenido</a>

<header class="sitio-nav">
    <div class="contenedor sitio-nav__fila">
        <a class="marca" href="index.php" aria-label="A la Carta, inicio">
            <svg class="marca__sello" width="26" height="26" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/>
                <path d="M9.5 8h5M9.5 12h5"/>
            </svg>
            <span class="marca__texto">A la Carta</span>
        </a>

        <nav class="sitio-nav__enlaces" id="navPublica" aria-label="Secciones del sitio">
            <a href="carta.php" class="<?= $activo === 'carta' ? 'activo' : '' ?>">Carta</a>
            <a href="index.php#sistema">El sistema</a>
            <a href="index.php#grupo">Grupo 04</a>
            <a href="index.php#roles">Roles</a>
        </nav>

        <div class="sitio-nav__acciones">
            <?php if ($usuario): ?>
                <a class="boton boton--principal" href="panel.php">Abrir panel</a>
            <?php else: ?>
                <a class="enlace-discreto" href="login.php">Acceder</a>
                <a class="boton boton--principal sitio-nav__cta" href="carta.php">Pedir en línea</a>
            <?php endif; ?>
            <button class="nav-boton" type="button" aria-label="Abrir menú" aria-expanded="false"
                    aria-controls="navPublica" data-nav-toggle>
                <?= icono('menu', 20) ?>
            </button>
        </div>
    </div>
</header>
