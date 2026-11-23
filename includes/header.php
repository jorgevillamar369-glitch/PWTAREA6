<?php
/**
 * Encabezado del sistema (panel, pedidos, carta y usuarios).
 * Espera $usuario, $titulo y, opcionalmente, $activo y $subtitulo.
 */
$activo     = $activo ?? '';
$subtitulo  = $subtitulo ?? null;
$rol        = $usuario['role'] ?? 'Client';
$esCliente  = $rol === 'Client';

$dias  = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$hoy   = $dias[(int) date('w')] . ' ' . date('j') . ' ' . $meses[(int) date('n') - 1];

$grupos = [
    'Servicio' => [
        ['panel.php', 'Panel', 'panel', ['Administrator', 'Chef', 'Waiter', 'Client']],
        ['pedidos.php', $esCliente ? 'Mis pedidos' : 'Pedidos', 'pedidos', ['Administrator', 'Chef', 'Waiter', 'Client']],
        ['pedido_nuevo.php', 'Nuevo pedido', 'nuevo', ['Administrator', 'Waiter', 'Client']],
    ],
    'Administración' => [
        ['platos.php', 'Carta', 'carta', ['Administrator']],
        ['usuarios.php', 'Usuarios', 'usuarios', ['Administrator']],
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= APP_NOMBRE ?></title>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/fuentes.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="app">
<a class="salto-contenido" href="#contenido">Ir al contenido</a>

<div class="armazon">
    <aside class="lateral" id="lateral" aria-label="Menú del sistema">
        <a class="lateral__marca" href="panel.php">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.7" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/>
                <path d="M9.5 8h5M9.5 12h5"/>
            </svg>
            <span>
                <span class="lateral__nombre">A la Carta</span>
                <span class="lateral__sub">Sistema de servicio</span>
            </span>
        </a>

        <nav class="lateral__nav">
            <?php foreach ($grupos as $grupo => $enlaces): ?>
                <?php $visibles = array_filter($enlaces, fn ($e) => in_array($rol, $e[3], true)); ?>
                <?php if ($visibles): ?>
                    <p class="lateral__grupo"><?= e($grupo) ?></p>
                    <?php foreach ($visibles as [$ruta, $texto, $clave]): ?>
                        <a class="lateral__enlace <?= $activo === $clave ? 'activo' : '' ?>" href="<?= $ruta ?>"
                           <?= $activo === $clave ? 'aria-current="page"' : '' ?>>
                            <?= icono($clave === 'carta' ? 'carta' : $clave, 18) ?>
                            <span><?= e($texto) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="lateral__pie">
            <div class="lateral__usuario">
                <span class="inicial" aria-hidden="true"><?= e(mb_substr($usuario['full_name'], 0, 1)) ?></span>
                <span>
                    <strong><?= e($usuario['full_name']) ?></strong>
                    <span class="lateral__rol"><?= e(rol_etiqueta($rol)) ?></span>
                </span>
            </div>
            <a class="lateral__salir" href="index.php"><?= icono('sitio', 16) ?> Ver sitio</a>
            <a class="lateral__salir" href="logout.php"><?= icono('salir', 16) ?> Cerrar sesión</a>
        </div>
    </aside>

    <div class="lateral-fondo" data-cerrar-nav></div>

    <div class="area">
        <header class="barra">
            <button class="nav-boton nav-boton--oscuro" type="button" aria-label="Abrir menú"
                    aria-expanded="false" aria-controls="lateral" data-nav-toggle>
                <?= icono('menu', 20) ?>
            </button>

            <div class="barra__titulo">
                <h1><?= e($titulo) ?></h1>
                <?php if ($subtitulo): ?><p class="barra__sub"><?= e($subtitulo) ?></p><?php endif; ?>
            </div>

            <p class="barra__fecha"><?= $hoy ?></p>
        </header>

        <main class="contenido-app" id="contenido">
            <?php mostrar_flash(); ?>
