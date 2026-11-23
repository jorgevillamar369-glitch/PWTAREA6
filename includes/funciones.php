<?php
/**
 * Funciones de apoyo: formato, etiquetas de rol y estado,
 * mensajes flash, token CSRF e iconos de la interfaz.
 */

declare(strict_types=1);

/** Escapa texto para imprimirlo dentro del HTML. */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Formato de dinero usado en toda la aplicación: $9,50 */
function dinero(float $valor): string
{
    return '$' . number_format($valor, 2, ',', '.');
}

/** Hoy · 13:45, Ayer · 10:12 o 20 nov 2026 · 13:44 */
function fecha_hora(string $fecha): string
{
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $ts    = strtotime($fecha);
    $dia   = date('Y-m-d', $ts);

    if ($dia === date('Y-m-d')) {
        return 'Hoy · ' . date('H:i', $ts);
    }
    if ($dia === date('Y-m-d', strtotime('-1 day'))) {
        return 'Ayer · ' . date('H:i', $ts);
    }

    $anio = (int) date('Y', $ts) === (int) date('Y') ? '' : ' ' . date('Y', $ts);

    return date('j', $ts) . ' ' . $meses[(int) date('n', $ts) - 1] . $anio
        . ' · ' . date('H:i', $ts);
}

/** Solo la hora: 13:45 */
function hora(string $fecha): string
{
    return date('H:i', strtotime($fecha));
}

// ------------------------------------------------------------
// Roles
// ------------------------------------------------------------
const ROLES_ETIQUETA = [
    'Administrator' => 'Administrador',
    'Chef'          => 'Chef',
    'Waiter'        => 'Camarero',
    'Client'        => 'Cliente',
];

function rol_etiqueta(?string $rol): string
{
    return ROLES_ETIQUETA[$rol] ?? 'Sin rol';
}

// ------------------------------------------------------------
// Estados del pedido (los mismos del enunciado)
// ------------------------------------------------------------
const ESTADOS = [
    'Pending'        => ['etiqueta' => 'Pendiente', 'clase' => 'pendiente'],
    'Preparing'      => ['etiqueta' => 'En preparación', 'clase' => 'preparando'],
    'Ready to serve' => ['etiqueta' => 'Listo para servir', 'clase' => 'listo'],
    'Served'         => ['etiqueta' => 'Servido', 'clase' => 'servido'],
];

function estado_etiqueta(string $estado): string
{
    return ESTADOS[$estado]['etiqueta'] ?? $estado;
}

function estado_clase(string $estado): string
{
    return ESTADOS[$estado]['clase'] ?? 'pendiente';
}

/**
 * Devuelve el siguiente estado que el rol puede aplicar,
 * o null si ese rol no puede mover el pedido en ese punto.
 */
function siguiente_estado(string $rol, string $estado): ?string
{
    $flujo = [
        'Pending'        => 'Preparing',
        'Preparing'      => 'Ready to serve',
        'Ready to serve' => 'Served',
    ];

    $permisos = [
        // Administrador puede mover cualquier pedido.
        'Administrator' => ['Pending', 'Preparing', 'Ready to serve'],
        // El chef lleva A la Carta hasta la ventana de servicio.
        'Chef'          => ['Pending', 'Preparing'],
        // El camarero entrega lo que ya está listo en la ventana.
        'Waiter'        => ['Ready to serve'],
        // El cliente solo consulta.
        'Client'        => [],
    ];

    if (!in_array($estado, $permisos[$rol] ?? [], true)) {
        return null;
    }

    return $flujo[$estado] ?? null;
}

/** Texto del botón para pasar al siguiente estado. */
function accion_estado(string $estado): string
{
    return match ($estado) {
        'Pending'        => 'Empezar preparación',
        'Preparing'      => 'Marcar como listo',
        'Ready to serve' => 'Marcar servido',
        default          => 'Cambiar estado',
    };
}

// ------------------------------------------------------------
// Mensajes flash (una sola vez por carga)
// ------------------------------------------------------------
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function mostrar_flash(): void
{
    foreach ($_SESSION['flash'] ?? [] as $aviso) {
        $tipo = in_array($aviso['tipo'], ['ok', 'error', 'aviso'], true) ? $aviso['tipo'] : 'aviso';
        echo '<div class="aviso aviso--' . $tipo . '" role="status">' . e($aviso['mensaje']) . '</div>';
    }
    unset($_SESSION['flash']);
}

// ------------------------------------------------------------
// CSRF
// ------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_validar(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(400);
        exit('La sesión expiró. Vuelve a cargar la página e inténtalo otra vez.');
    }
}

// ------------------------------------------------------------
// Iconos de línea (mismo trazo de 1,6 px en todos)
// ------------------------------------------------------------
function icono(string $nombre, int $tamano = 18): string
{
    $trazos = [
        'panel'   => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'pedidos' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>',
        'carta'   => '<path d="M4 4h13a3 3 0 0 1 3 3v13H7a3 3 0 0 1-3-3V4z"/><path d="M8 8h8M8 12h5"/>',
        'usuarios'=> '<path d="M16 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"/><circle cx="9.5" cy="8" r="3.5"/><path d="M21 20v-1a4 4 0 0 0-3-3.8"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/>',
        'nuevo'   => '<path d="M12 5v14M5 12h14"/>',
        'salir'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'sitio'   => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'reloj'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'mesa'    => '<path d="M3 10h18"/><path d="M5 10v10M19 10v10"/><path d="M8 6h8"/>',
        'flecha'  => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
        'basura'  => '<path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/>',
        'lapiz'   => '<path d="M4 20h4l10-10a2.8 2.8 0 0 0-4-4L4 16v4z"/>',
        'buscar'  => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
        'candado' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'rayo'    => '<path d="M13 3L5 14h6l-1 7 8-11h-6l1-7z"/>',
    ];

    $trazo = $trazos[$nombre] ?? $trazos['panel'];

    return '<svg class="icono" width="' . $tamano . '" height="' . $tamano . '" viewBox="0 0 24 24" fill="none"'
        . ' stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true" focusable="false">' . $trazo . '</svg>';
}

/** Total de un pedido a partir de sus líneas. */
function total_pedido(PDO $pdo, int $pedidoId): float
{
    $sql = 'SELECT COALESCE(SUM(quantity * unit_price), 0) AS total
            FROM order_items WHERE order_id = :id';
    $consulta = $pdo->prepare($sql);
    $consulta->execute(['id' => $pedidoId]);
    return (float) $consulta->fetchColumn();
}
