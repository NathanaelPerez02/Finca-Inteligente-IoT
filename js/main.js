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