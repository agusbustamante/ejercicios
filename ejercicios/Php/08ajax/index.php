<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>GatoEncriptador 🐾</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="contenedor">
        <div class="input-box">
            <h3>😺 Ingrese dato de entrada:</h3>
            <input type="text" id="clave" placeholder="Ej: miau123">
        </div>

        <div class="boton-box" id="btnEncriptar">
            <h3>🐾 Encriptar</h3>
            <p>(Click y espera al gato trabajador...)</p>
        </div>

        <div class="resultado-box" id="resultado">
            <h3>Resultado:</h3>
            <p id="textoResultado">Aquí aparecerá la encriptación...</p>
        </div>

        <div class="estado-box" id="estado">
            <h3>Estado del requerimiento:</h3>
            <p id="estadoTexto">Listo para trabajar 😼</p>
        </div>
    </div>

    <script>
        document.getElementById('btnEncriptar').addEventListener('click', async () => {
            const clave = document.getElementById('clave').value.trim();
            const estado = document.getElementById('estadoTexto');
            const resultado = document.getElementById('textoResultado');

            if (!clave) {
                alert("🐱 Por favor ingresa una clave antes de encriptar.");
                return;
            }

            alert("📤 Enviando datos al gato encriptador...");

            // Mostrar estado de espera
            estado.textContent = "😺 El gato está procesando tu clave...";
            resultado.textContent = "Esperando respuesta...";

            // Simular retardo (3 segundos) antes del fetch
            await new Promise(r => setTimeout(r, 3000));

            try {
                const data = new URLSearchParams();
                data.append('clave', clave);

                const respuesta = await fetch('servidor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: data
                });

                const texto = await respuesta.text();
                alert("✅ La data ha llegado desde el servidor, ¡el gato terminó su trabajo!");
                resultado.innerHTML = texto;
                estado.textContent = "✔️ Promesa cumplida con éxito 🐈";
            } catch (error) {
                estado.textContent = "❌ Error al comunicarse con el gato servidor.";
                console.error(error);
            }
        });
    </script>
</body>
</html>
