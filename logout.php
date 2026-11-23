<?php
/**
 * Cierra la sesión y vuelve a la portada.
 */

require_once __DIR__ . '/includes/config.php';

sesion_cerrar();
header('Location: index.php');
exit;
