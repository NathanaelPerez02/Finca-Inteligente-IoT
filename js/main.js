document.addEventListener("DOMContentLoaded", function() {
    const formulario = document.querySelector("form");
    
    if(formulario) {
        formulario.addEventListener("submit", function(evento) {
            const inputs = formulario.querySelectorAll("input");
            let valido = true;

            inputs.forEach(input => {
                if(!input.value.trim()) {
                    valido = false;
                    input.style.borderColor = "#f87171";
                } else {
                    input.style.borderColor = "#3f3f46";
                }
            });

            if(!valido) {
                evento.preventDefault();
                alert("Por favor, rellene todos los campos requeridos.");
            }
        });
    }
});

function actualizarSensores() {
    fetch('get_datos.php')
    .then(respuesta => respuesta.json())
    .then(datos => {
        if (!datos.error) {
            document.getElementById('valor_agua').innerText    = datos.agua_actual + '%';
            document.getElementById('valor_humedad').innerText = datos.humedad_actual + '%';
 
            let distancia         = parseInt(datos.acceso_actual)    || 100;
            let modo              = parseInt(datos.modo_actual)       || 0;
            let tranquera_abierta = parseInt(datos.estado_tranquera)  || 0;
 
            // MODO
            let uiModo      = document.getElementById('estado_modo');
            let tarjetaModo = document.getElementById('tarjeta_modo');
            if (modo === 0) {
                uiModo.innerText              = "AUTOMÁTICO";
                uiModo.style.color            = "#4ade80";
                tarjetaModo.style.borderColor = "#4ade80";
            } else {
                uiModo.innerText              = "MANUAL";
                uiModo.style.color            = "#fbbf24";
                tarjetaModo.style.borderColor = "#fbbf24";
            }
 
            // Mostrar u ocultar botones según modo
            // En AUTO: ocultar abrir/cerrar. En MANUAL: mostrar
            let btnAbrir  = document.getElementById('btn_abrir');
            let btnCerrar = document.getElementById('btn_cerrar');
            if (btnAbrir && btnCerrar) {
                btnAbrir.style.display  = (modo === 1) ? 'block' : 'none';
                btnCerrar.style.display = (modo === 1) ? 'block' : 'none';
            }
 
            // DISTANCIA Y ESTADO TRANQUERA
            let tarjetaAcceso  = document.getElementById('tarjeta_acceso');
            let tituloAcceso   = document.getElementById('titulo_acceso');
            let textoDistancia = document.getElementById('distancia_real');
            let textoPuerta    = document.getElementById('estado_puerta');
 
            textoDistancia.innerText = "Distancia detectada: " + distancia + " cm";
 
            if (distancia <= 30) {
                tituloAcceso.innerText        = "ZONA";
                tituloAcceso.style.color      = "#f87171";
                tarjetaAcceso.style.borderColor = "#f87171";
                tarjetaAcceso.classList.add("card-ocupada-anim");
            } else {
                tituloAcceso.innerText        = "ZONA";
                tituloAcceso.style.color      = "#4ade80";
                tarjetaAcceso.style.borderColor = "#4ade80";
                tarjetaAcceso.classList.remove("card-ocupada-anim");
            }
 
            if (tranquera_abierta === 1) {
                textoPuerta.innerText           = "ABIERTO";
                textoPuerta.style.color         = "#f87171";
                tarjetaAcceso.style.borderColor = "#f87171";
                tituloAcceso.style.color        = "#f87171";
            } else {
                textoPuerta.innerText           = "CERRADO";
                textoPuerta.style.color         = "#4ade80";
                tarjetaAcceso.style.borderColor = "#4ade80";
                tituloAcceso.style.color        = "#4ade80";
            }
        }
    })
    .catch(error => console.log('Error actualizando sensores:', error));
}
 
setInterval(actualizarSensores, 500);

function abrirModal(id) { document.getElementById(id).style.display = 'flex'; }
function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
 
function ejecutarAccion(tipoAccion, extraData) {
    let payload = { accion: tipoAccion };
 
    if (tipoAccion === 'abrir_porton') {
        payload.password = document.getElementById('pass_abrir').value;
        if (!payload.password) return alert("Ingrese la contraseña");
    }
    else if (tipoAccion === 'cerrar_porton') {
        // No requiere contraseña, enviar directo
    }
    else if (tipoAccion === 'cambiar_modo') {
        payload.modo = extraData;
    }
    else if (tipoAccion === 'agregar_tarjeta') {
        payload.uid         = document.getElementById('uid_tarjeta').value;
        payload.descripcion = document.getElementById('desc_tarjeta').value;
        payload.password    = document.getElementById('pass_tarjeta').value;
        if (!payload.uid || !payload.password) return alert("UID y Contraseña son obligatorios");
    }
 
    fetch('procesar_seguridad.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        if (data.exito) {
            cerrarModal('modalAbrir');
            cerrarModal('modalTarjeta');
            document.getElementById('pass_abrir').value   = '';
            document.getElementById('pass_tarjeta').value = '';
            document.getElementById('uid_tarjeta').value  = '';
            document.getElementById('desc_tarjeta').value = '';
        }
    });
}