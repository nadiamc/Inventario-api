<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario en vivo</title>
    @vite(['resources/js/app.js'])
</head>
<body>
    <h1>Inventario en tiempo real</h1>
    <p>Abra esta página en dos pestañas y cambie el stock.</p>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="productos"></tbody>
    </table>

    <pre id="logs"></pre>

    <script>
        async function cargar() {
            const res = await fetch('/api/productos', { headers: { 'Accept': 'application/json' } });
            const productos = await res.json();

            const cuerpo = document.getElementById('productos');
            cuerpo.textContent = '';

            for (const p of productos) {
                const fila = document.createElement('tr');

                for (const valor of [p.id, p.nombre, p.precio, p.stock]) {
                    const celda = document.createElement('td');
                    celda.textContent = valor;
                    fila.appendChild(celda);
                }

                const celdaAccion = document.createElement('td');
                const boton = document.createElement('button');
                boton.textContent = '+1 stock';
                boton.dataset.id = p.id;
                boton.dataset.stock = p.stock;
                celdaAccion.appendChild(boton);
                fila.appendChild(celdaAccion);

                cuerpo.appendChild(fila);
            }
        }

        document.addEventListener('click', async (e) => {
            if (!e.target.matches('button[data-id]')) {
                return;
            }

            await fetch('/api/productos/' + e.target.dataset.id, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ stock: Number(e.target.dataset.stock) + 1 }),
            });

            await cargar();
        });

        window.addEventListener('load', cargar);

        window.addEventListener('inventario-cambio', (e) => {
            document.getElementById('logs').textContent =
                JSON.stringify(e.detail) + '\n' + document.getElementById('logs').textContent;

            cargar();
        });
    </script>
</body>
</html>
