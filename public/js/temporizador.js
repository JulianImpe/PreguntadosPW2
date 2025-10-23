
document.addEventListener('DOMContentLoaded', () => {
    // tu código actual aquí


let tiempoRestante = 15;
const timerElement = document.getElementById('timer');
const progressBar = document.getElementById('progressBar');
const tiempoTotal = 15;
let countdownInterval;

// Iniciar countdown
countdownInterval = setInterval(() => {
    tiempoRestante--;
    timerElement.textContent = tiempoRestante;

    const porcentaje = (tiempoRestante / tiempoTotal) * 100;
    progressBar.style.width = porcentaje + "%";

    if (tiempoRestante <= 5) {
        timerElement.classList.add('text-red-600');
    }

    if (tiempoRestante <= 0) {
        clearInterval(countdownInterval);
        timerElement.textContent = '0';
        window.location.href = 'index.php?controller=Partida&method=partidaFinalizada';
    }
}, 1000);

// Función para mostrar feedback
function mostrarFeedback(esCorrecta) {
    const feedbackEl = document.getElementById('feedbackMessage');

    if (esCorrecta) {
        feedbackEl.textContent = '✓ ¡CORRECTO!';
        feedbackEl.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-50 px-12 py-8 rounded-3xl shadow-2xl text-white text-4xl font-bold border-4 border-white bg-green-500 animate-pulse';
    } else {
        feedbackEl.textContent = '✗ INCORRECTO';
        feedbackEl.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-50 px-12 py-8 rounded-3xl shadow-2xl text-white text-4xl font-bold border-4 border-white bg-red-500 animate-pulse';
    }

    feedbackEl.classList.remove('hidden');
}

// Función para marcar respuesta
function marcarRespuesta(boton, esCorrecta) {
    if (esCorrecta) {
        boton.classList.remove('bg-white', 'hover:bg-gray-50', 'border-gray-800');
        boton.classList.add('bg-green-400', 'respuesta-correcta', 'border-green-600');
        const circulo = boton.querySelector('.letra-circulo');
        circulo.classList.remove('bg-orange-500', 'border-gray-800');
        circulo.classList.add('bg-green-600', 'border-green-800');
    } else {
        boton.classList.remove('bg-white', 'hover:bg-gray-50', 'border-gray-800');
        boton.classList.add('bg-red-400', 'respuesta-incorrecta', 'border-red-600');
        const circulo = boton.querySelector('.letra-circulo');
        circulo.classList.remove('bg-orange-500', 'border-gray-800');
        circulo.classList.add('bg-red-600', 'border-red-800');
    }
}

// Manejador de clics en respuestas
document.querySelectorAll('.respuesta-btn').forEach(boton => {
    boton.addEventListener('click', function(e) {
        e.preventDefault();

        console.log('Click en respuesta'); // Para debug

        // Detener el timer
        clearInterval(countdownInterval);

        // Deshabilitar todos los botones
        document.querySelectorAll('.respuesta-btn').forEach(btn => {
            btn.disabled = true;
            btn.classList.remove('hover:bg-gray-50', 'hover:shadow-2xl', 'hover:-translate-y-1');
            btn.style.pointerEvents = 'none';
        });

        const respuestaId = this.getAttribute('data-respuesta-id');
        const dataCorrecta = this.getAttribute('data-correcta');
        const esCorrecta = (dataCorrecta === 'true' || dataCorrecta === '1' || dataCorrecta === 'True');

        console.log('Valor data-correcta:', dataCorrecta); // Para debug
        console.log('Es correcta:', esCorrecta); // Para debug

        // Marcar la respuesta seleccionada
        marcarRespuesta(this, esCorrecta);

        // Mostrar feedback
        mostrarFeedback(esCorrecta);

        // Guardar la respuesta para enviar
        document.getElementById('respuestaInput').value = respuestaId;

        // Esperar 2 segundos y enviar el formulario
        setTimeout(() => {
            document.getElementById('formRespuestas').submit();
        }, 2000);
    });
});

});