/* ============================================================
   A la Carta · JavaScript del sitio y del sistema
   1. Menú móvil
   2. Carrito del nuevo pedido
   3. Filtro de la carta pública
   4. Confirmación antes de eliminar
   5. Impresión de comandas
   ============================================================ */

(function () {
    'use strict';

    // --------------------------------------------------------
    // 1. Menú móvil (sitio público y menú lateral del sistema)
    // --------------------------------------------------------
    const botonesNav = document.querySelectorAll('[data-nav-toggle]');
    const fondo = document.querySelector('.lateral-fondo');

    function cerrarNav() {
        document.querySelectorAll('.lateral.abierto, .sitio-nav__enlaces.abierto').forEach(function (el) {
            el.classList.remove('abierto');
        });
        if (fondo) {
            fondo.classList.remove('visible');
        }
        botonesNav.forEach(function (boton) {
            boton.setAttribute('aria-expanded', 'false');
        });
    }

    botonesNav.forEach(function (boton) {
        boton.addEventListener('click', function () {
            const destino = document.getElementById(boton.getAttribute('aria-controls'));
            if (!destino) {
                return;
            }
            const abierto = destino.classList.toggle('abierto');
            boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            if (fondo && destino.classList.contains('lateral')) {
                fondo.classList.toggle('visible', abierto);
            }
        });
    });

    if (fondo) {
        fondo.addEventListener('click', cerrarNav);
    }

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrarNav();
        }
    });

    // --------------------------------------------------------
    // 2. Carrito del nuevo pedido
    // --------------------------------------------------------
    const formulario = document.querySelector('[data-pedido-form]');

    if (formulario) {
        const lista = formulario.querySelector('[data-carrito-lista]');
        const vacio = formulario.querySelector('[data-carrito-vacio]');
        const totalTexto = formulario.querySelector('[data-carrito-total]');
        const enviar = formulario.querySelector('[data-carrito-enviar]');
        const carrito = new Map(); // id => { nombre, precio, cantidad }

        // Platos que ofrece la página, leídos de las filas de la carta.
        const catalogo = new Map();
        formulario.querySelectorAll('[data-plato]').forEach(function (fila) {
            catalogo.set(fila.dataset.plato, {
                nombre: fila.dataset.nombre,
                precio: Number.parseFloat(fila.dataset.precio)
            });
        });

        function formatear(valor) {
            return '$' + valor.toFixed(2).replace('.', ',');
        }

        function agregar(id, cantidad) {
            const plato = catalogo.get(String(id));
            if (!plato) {
                return;
            }
            const actual = carrito.get(String(id));
            const nueva = (actual ? actual.cantidad : 0) + cantidad;

            if (nueva <= 0) {
                carrito.delete(String(id));
            } else {
                carrito.set(String(id), {
                    nombre: plato.nombre,
                    precio: plato.precio,
                    cantidad: Math.min(nueva, 20)
                });
            }
            render();
        }

        function render() {
            lista.innerHTML = '';
            let total = 0;

            carrito.forEach(function (item, id) {
                total += item.precio * item.cantidad;

                const fila = document.createElement('li');
                fila.className = 'carrito__item';

                const nombre = document.createElement('span');
                nombre.className = 'carrito__item-nombre';
                nombre.textContent = item.nombre;

                const subtotal = document.createElement('span');
                subtotal.className = 'carrito__item-precio cifra';
                subtotal.textContent = formatear(item.precio * item.cantidad);

                const controles = document.createElement('div');
                controles.className = 'carrito__item-controles';

                const menos = document.createElement('button');
                menos.type = 'button';
                menos.textContent = '−';
                menos.setAttribute('aria-label', 'Quitar una unidad de ' + item.nombre);
                menos.addEventListener('click', function () {
                    agregar(id, -1);
                });

                const cantidad = document.createElement('span');
                cantidad.className = 'carrito__item-cantidad cifra';
                cantidad.textContent = item.cantidad;

                const mas = document.createElement('button');
                mas.type = 'button';
                mas.textContent = '+';
                mas.setAttribute('aria-label', 'Añadir una unidad de ' + item.nombre);
                mas.addEventListener('click', function () {
                    agregar(id, 1);
                });

                const quitar = document.createElement('button');
                quitar.type = 'button';
                quitar.className = 'carrito__quitar';
                quitar.textContent = 'Quitar';
                quitar.addEventListener('click', function () {
                    carrito.delete(id);
                    render();
                });

                controles.append(menos, cantidad, mas, quitar);

                const campo = document.createElement('input');
                campo.type = 'hidden';
                campo.name = 'items[' + id + ']';
                campo.value = item.cantidad;

                fila.append(nombre, subtotal, controles, campo);
                lista.append(fila);
            });

            totalTexto.textContent = formatear(total);
            lista.hidden = carrito.size === 0;
            vacio.hidden = carrito.size > 0;
            enviar.disabled = carrito.size === 0;
        }

        formulario.querySelectorAll('[data-agregar]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                const fila = boton.closest('[data-plato]');
                agregar(fila.dataset.plato, 1);
                boton.textContent = 'Añadido';
                setTimeout(function () {
                    boton.textContent = 'Añadir';
                }, 900);
            });
        });

        // Pedido que vuelve del servidor con errores.
        let inicial = {};
        try {
            inicial = JSON.parse(formulario.dataset.inicial || '{}');
        } catch (error) {
            inicial = {};
        }
        Object.keys(inicial).forEach(function (id) {
            agregar(id, Number.parseInt(inicial[id], 10) || 0);
        });

        // Plato elegido desde la carta pública (?plato=3).
        const sugerido = formulario.dataset.sugerido;
        if (sugerido && catalogo.has(sugerido) && !carrito.has(sugerido)) {
            agregar(sugerido, 1);
        }

        render();
    }

    // --------------------------------------------------------
    // 3. Filtro de la carta pública
    // --------------------------------------------------------
    const filtros = document.querySelectorAll('[data-filtro]');
    const platos = document.querySelector('[data-lista-carta]');

    if (filtros.length && platos) {
        filtros.forEach(function (boton) {
            boton.addEventListener('click', function () {
                const categoria = boton.dataset.filtro;
                filtros.forEach(function (otro) {
                    otro.classList.toggle('activo', otro === boton);
                });
                platos.querySelectorAll('[data-categoria]').forEach(function (plato) {
                    plato.hidden = categoria !== 'todos' && plato.dataset.categoria !== categoria;
                });
            });
        });
    }

    // --------------------------------------------------------
    // 4. Confirmación antes de eliminar
    // --------------------------------------------------------
    document.querySelectorAll('form[data-confirmar]').forEach(function (formularioRiesgo) {
        formularioRiesgo.addEventListener('submit', function (evento) {
            if (!window.confirm(formularioRiesgo.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });

    // --------------------------------------------------------
    // 5. Impresión de comandas
    // --------------------------------------------------------
    document.querySelectorAll('[data-imprimir]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            window.print();
        });
    });
})();
