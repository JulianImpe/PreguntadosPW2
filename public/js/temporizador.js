
    document.addEventListener('DOMContentLoaded', () => {
    let tiempoRestante = 15;
    const timerElement = document.getElementById('timer');
    const progressBar = document.getElementById('progressBar');
    const tiempoTotal = 15;
    let countdownInterval;

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
    const circulo = boton.querySelector('.letra-circulo');
    if (esCorrecta) {
    boton.classList.remove('bg-white', 'hover:bg-gray-50', 'border-gray-800');
    boton.classList.add('bg-green-400', 'border-green-600');
    circulo.classList.remove('bg-orange-500', 'border-gray-800');
    circulo.classList.add('bg-green-600', 'border-green-800');
} else {
    boton.classList.remove('bg-white', 'hover:bg-gray-50', 'border-gray-800');
    boton.classList.add('bg-red-400', 'border-red-600');
    circulo.classList.remove('bg-orange-500', 'border-gray-800');
    circulo.classList.add('bg-red-600', 'border-red-800');
}
}

    // Función para mostrar la respuesta correcta
    function mostrarRespuestaCorrecta() {
    document.querySelectorAll('.respuesta-btn').forEach(btn => {
    const esCorrecta = btn.getAttribute('data-correcta') === '1';
    if (esCorrecta) {
    btn.classList.remove('bg-white', 'hover:bg-gray-50', 'border-gray-800');
    btn.classList.add('bg-green-400', 'border-green-600');
    const circulo = btn.querySelector('.letra-circulo');
    circulo.classList.remove('bg-orange-500', 'border-gray-800');
    circulo.classList.add('bg-green-600', 'border-green-800');
}
});
}

    // Función para finalizar partida (tiempo agotado)
    function partidaFinalizada() {
    window.location.href = '/partida/partidaFinalizada'; // ruta limpia
}

    // Iniciar countdown
    countdownInterval = setInterval(() => {
    tiempoRestante--;
    timerElement.textContent = tiempoRestante;

    const porcentaje = (tiempoRestante / tiempoTotal) * 100;
    progressBar.style.width = porcentaje + "%";

    if (tiempoRestante <= 10) progressBar.classList.replace('bg-sky-400', 'bg-yellow-500');
    if (tiempoRestante <= 5) timerElement.classList.add('text-red-600'), progressBar.classList.replace('bg-yellow-500', 'bg-red-500');

    if (tiempoRestante <= 0) {
    clearInterval(countdownInterval);
    timerElement.textContent = '0';
    const feedbackEl = document.getElementById('feedbackMessage');
    feedbackEl.textContent = '⏱️ ¡TIEMPO AGOTADO!';
    feedbackEl.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-50 px-12 py-8 rounded-3xl shadow-2xl text-white text-4xl font-bold border-4 border-white bg-orange-500 animate-pulse';
    feedbackEl.classList.remove('hidden');

    setTimeout(() => {
    partidaFinalizada(); // redirige a partida finalizada
}, 2000);
}
}, 1000);

    // Manejar clicks en respuestas
    document.querySelectorAll('.respuesta-btn').forEach(boton => {
    boton.addEventListener('click', function(e) {
    e.preventDefault();
    clearInterval(countdownInterval);

    // Deshabilitar botones
    document.querySelectorAll('.respuesta-btn').forEach(btn => {
    btn.disabled = true;
    btn.style.pointerEvents = 'none';
    btn.classList.remove('hover:bg-gray-50', 'hover:shadow-2xl', 'hover:-translate-y-1');
});

    const respuestaId = this.getAttribute('data-respuesta-id');
    const esCorrecta = this.getAttribute('data-correcta') === '1';

    marcarRespuesta(this, esCorrecta);

    if (!esCorrecta) mostrarRespuestaCorrecta();

    mostrarFeedback(esCorrecta);

    document.getElementById('respuestaInput').value = respuestaId;

    setTimeout(() => {
    document.getElementById('formRespuestas').submit();
}, 2000);
});
});
});

