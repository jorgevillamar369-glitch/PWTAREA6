# A la Carta

Sistema de gestión de restaurante con cuatro roles y permisos, hecho con PHP 8, MySQL/MariaDB, Bootstrap 5, CSS y JavaScript. Tarea 6 de Programación Web Avanzada (UBE).

**Grupo 04:** Bruce Balarezo, Norka Mideros, Mauro Chinlle, Jorge Villamar y Liseth Criollo.

## Ejecutar en local

Necesitas PHP 8.1 con `pdo_mysql` y MySQL 8 o MariaDB 10.4.

1. Importa la base:

   ```bash
   mysql -u root -p --default-character-set=utf8mb4 < sql/pwatarea6.sql
   ```

2. Revisa `includes/config.php`. Por defecto usa `127.0.0.1:3307`, base `pwatarea6`, usuario `root` sin contraseña. Si tu servidor usa otros datos, crea `includes/config.local.php` (está fuera de Git):

   ```php
   <?php
   return [
       'host' => '127.0.0.1', 'puerto' => '3306',
       'base' => 'pwatarea6', 'usuario' => 'root', 'clave' => '',
   ];
   ```

3. Levanta el sitio con `iniciar-local.bat` o con:

   ```bash
   php -S localhost:8080 -t .
   ```

   Abre <http://localhost:8080>.

## Cuentas de prueba

Contraseña para todas: `ALaCarta2026`.

| Rol | Correo |
| --- | --- |
| Administrador | admin@alacarta.ec |
| Chef | cocina@alacarta.ec |
| Camarero | mesero@alacarta.ec |
| Cliente | andres@correo.ec |

`diana@correo.ec` también es una cuenta de cliente. El usuario de cada cuenta es la parte del correo antes de la arroba.

## Roles

| Puede | Quién |
| --- | --- |
| Ver y gestionar la carta, y administrar cuentas | Administrador |
| Ver todos los pedidos | Administrador, chef y camarero |
| Registrar pedidos | Administrador, camarero y cliente |
| Pasar pendiente → preparación → listo | Chef (el administrador también) |
| Pasar listo → servido | Camarero (el administrador también) |
| Ver solo sus propios pedidos | Cliente |

Los permisos se comprueban en el servidor en cada página, y cada cambio de estado solo acepta las transiciones permitidas.

## Estructura

```
├── index.php, carta.php, login.php, registro.php   Parte pública
├── panel.php, pedidos.php, pedido_*.php           Pedidos por rol
├── platos.php, plato_*.php                        Carta (administrador)
├── usuarios.php, usuario_*.php                    Cuentas (administrador)
├── includes/                                       Conexión, sesión, permisos y plantillas
├── assets/                                         CSS, JS, fuentes, fotos y Bootstrap local
└── sql/pwatarea6.sql                               Base con datos de prueba
```

## Notas

- PDO con consultas preparadas. Al guardar un pedido, los precios se leen otra vez de la base.
- Contraseñas con `password_hash`, token CSRF en los formularios y transacciones al crear pedidos.
- Un plato con pedidos o una cuenta con historial no se eliminan: se marcan como agotados o suspendidos.
- `order_items` amplía el esquema del enunciado para que un pedido lleve varios platos y conserve el precio de venta.
- Bootstrap, las fuentes y las fotos de Pexels están en el proyecto; no se carga nada desde CDN.

## Publicar

Importa `sql/pwatarea6.sql` en la base del hospedaje, crea allí `includes/config.local.php` con los datos de conexión y deja `APP_DEBUG` en `false` dentro de `includes/config.php`.

## Créditos

Fotografías de [Pexels](https://www.pexels.com). IBM Plex (SIL OFL) y Bootstrap 5 (MIT).
