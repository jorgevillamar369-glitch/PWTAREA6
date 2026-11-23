# A la Carta

Sistema de gestión de restaurante con roles y permisos, hecho con HTML, CSS, JavaScript, Bootstrap 5, PHP 8 y MySQL/MariaDB. Corresponde a la Tarea 6 de Programación Web Avanzada (UBE).

**Grupo 04:** Bruce Balarezo, Norka Mideros, Mauro Chinlle, Jorge Villamar y Liseth Criollo.

El proyecto incluye la parte pública (portada, carta, registro y acceso) y la parte interna (panel, pedidos, carta administrativa y cuentas), con cuatro roles que solo ven lo que les corresponde.

## Requisitos

- PHP 8.1 o superior con PDO MySQL.
- MySQL 8 o MariaDB 10.4 o superior.
- phpMyAdmin (opcional, para revisar la base).
- Apache, Laragon o el servidor interno de PHP.

## Instalación en local

1. Importa la base desde la terminal:

   ```bash
   mysql -u root -p --default-character-set=utf8mb4 < sql/pwatarea6.sql
   ```

   O abre `sql/pwatarea6.sql` en phpMyAdmin y ejecútalo. El archivo crea la base `pwatarea6`, las tablas y los datos de prueba.

2. Revisa `includes/config.php`. Por defecto apunta a `127.0.0.1:3307`, base `pwatarea6`, usuario `root` sin contraseña.

   Si tu servidor usa otros datos, crea `includes/config.local.php` (está excluido de Git):

   ```php
   <?php
   return [
       'host'    => '127.0.0.1',
       'puerto'  => '3306',
       'base'    => 'pwatarea6',
       'usuario' => 'root',
       'clave'   => '',
   ];
   ```

3. Levanta el sitio:

   ```bash
   php -S localhost:8080 -t .
   ```

   Abre <http://localhost:8080>. También funciona dentro de Laragon o XAMPP copiando la carpeta a `www` o `htdocs`.

   En Windows, el archivo `iniciar-local.bat` hace los dos pasos (MariaDB en el 3307 y PHP en el 8080) y abre el navegador.

## Cuentas de prueba

Todas usan la contraseña `ALaCarta2026`.

| Rol | Correo | Usuario |
| --- | --- | --- |
| Administrador | admin@alacarta.ec | admin |
| Chef | cocina@alacarta.ec | cocina |
| Camarero | mesero@alacarta.ec | mesero |
| Cliente | andres@correo.ec | andres |
| Cliente | diana@correo.ec | diana |

## Roles y permisos

| Acción | Administrador | Chef | Camarero | Cliente |
| --- | :-: | :-: | :-: | :-: |
| Ver la carta pública | sí | sí | sí | sí |
| Ver todos los pedidos | sí | sí | sí | solo los propios |
| Registrar un pedido | sí | no | sí | sí |
| Pasar de pendiente a en preparación | sí | sí | no | no |
| Pasar de en preparación a listo | sí | sí | no | no |
| Pasar de listo a servido | sí | no | sí | no |
| Crear, editar y eliminar platos | sí | no | no | no |
| Crear cuentas y cambiar roles | sí | no | no | no |
| Eliminar un pedido pendiente | sí | no | no | no |

Los permisos se comprueban en el servidor en cada página (`includes/auth.php`), no solo al iniciar sesión. El cambio de estado solo acepta las transiciones permitidas (`siguiente_estado()` en `includes/funciones.php`).

## Estructura

```
├── index.php              Portada pública
├── carta.php              Carta del restaurante
├── login.php              Acceso
├── registro.php           Registro de clientes
├── logout.php
├── panel.php              Panel según el rol
├── pedidos.php            Lista con filtros y búsqueda
├── pedido_nuevo.php       Carrito y envío a cocina
├── pedido_detalle.php     Detalle, avance e impresión
├── pedido_estado.php      Cambio de estado validado
├── pedido_eliminar.php    Borrado de pendientes (admin)
├── platos.php             Carta administrativa
├── plato_form.php         Alta y edición de platos
├── plato_estado.php       Disponible / agotado
├── plato_eliminar.php     Borrado protegido
├── usuarios.php           Listado de cuentas
├── usuario_form.php       Alta y edición de cuentas
├── usuario_accion.php     Activar o suspender
├── usuario_eliminar.php   Borrado protegido
├── includes/
│   ├── config.php         Conexión, sesión y constantes
│   ├── funciones.php      Formatos, estados, CSRF e iconos
│   ├── auth.php           Sesión y permisos por rol
│   ├── comanda.php        Tarjeta de pedido reutilizable
│   ├── header.php         Armazón del sistema
│   ├── footer.php
│   ├── sitio_header.php   Cabecera pública
│   └── sitio_footer.php
├── assets/
│   ├── css/               Estilos propios y tipografías
│   ├── js/app.js          Carrito, menú, filtros y confirmaciones
│   ├── fonts/             IBM Plex Sans y Plex Mono (locales)
│   ├── img/               Fotografías de Pexels y favicon
│   └── vendor/            Bootstrap 5.3.3 local
└── sql/pwatarea6.sql      Base completa con datos de prueba
```

## Decisiones técnicas

- **PDO con consultas preparadas** en todas las páginas. Los precios del pedido se vuelven a leer de la base al guardar; el navegador solo envía cantidades.
- **Contraseñas cifradas** con `password_hash()` y verificadas con `password_verify()`.
- **Token CSRF** en todos los formularios que modifican datos (`csrf_campo()` y `csrf_validar()`).
- **Transacciones** al crear un pedido: se guarda la cabecera y sus líneas, o no se guarda nada.
- **Historial protegido**: un plato con pedidos o una cuenta con historial no se eliminan, se marcan como agotados o suspendidos.
- La tabla `order_items` amplía el esquema del enunciado, que tenía un solo plato por pedido. Así un pedido puede llevar varios platos y se conserva el precio del momento de la venta.
- Bootstrap 5 se usa para la retícula, los formularios y los componentes base; el diseño propio está en `assets/css/estilos.css`.

## Publicación en un hospedaje

1. Sube el proyecto sin `includes/config.local.php`.
2. Crea la base en phpMyAdmin e importa `sql/pwatarea6.sql` con codificación `utf8mb4`.
3. Edita `includes/config.php` con el host, la base, el usuario y la clave que entrega el proveedor, y deja `APP_DEBUG` en `false`.
4. Comprueba que el servidor tenga PHP 8 y la extensión `pdo_mysql`.

## Créditos

Fotografías de [Pexels](https://www.pexels.com), de uso libre. Tipografías IBM Plex con licencia SIL Open Font License. Bootstrap 5 con licencia MIT.
