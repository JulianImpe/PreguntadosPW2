document.addEventListener('DOMContentLoaded', () => {
    let tiempoRestante = 15;
    const timerElement = document.getElementById('timer');
    const progressBar = document.getElementById('progressBar');
    const tiempoTotal = 15;
    let countdownInterval;

    countdownInterval = setInterval(() => {
        tiempoRestante--;
        timerElement.textContent = tiempoRestante;

        const porcentaje = (tiempoRestante / tiempoTotal) * 100;
        progressBar.style.width = porcentaje + "%";

        if (tiempoRestante <= 5) {
            timerElement.classList.add('text-red-600');
            progressBar.classList.remove('bg-sky-400');
            progressBar.classList.add('bg-red-500');
        } else if (tiempoRestante <= 10) {
            progressBar.classList.remove('bg-sky-400');
            progressBar.classList.add('bg-yellow-500');
        }

        if (tiempoRestante <= 0) {
            clearInterval(countdownInterval);
            timerElement.textContent = '0';

            const feedbackEl = document.getElementById('feedbackMessage');
            feedbackEl.textContent = '⏱️ ¡TIEMPO AGOTADO!';
            feedbackEl.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-50 px-12 py-8 rounded-3xl shadow-2xl text-white text-4xl font-bold border-4 border-white bg-orange-500 animate-pulse';
            feedbackEl.classList.remove('hidden');

            setTimeout(() => {
                document.getElementById('formRespuestas').submit();
            }, 2000);
        }
    }, 1000);

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

    function marcarRespuesta(boton, esCorrecta) {
        if (esCorrecta) {
            boton.classList.remove('bg-white', 'hover:bg-gray-50', 'border-gray-400');
            boton.classList.add('bg-green-400', 'border-green-600');
            const circulo = boton.querySelector('.letra-circulo');
            circulo.classList.remove('bg-orange-500', 'border-gray-800');
            circulo.classList.add('bg-green-600', 'border-green-800');
        } else {
            boton.classList.remove('bg-white', 'hover:bg-gray-50', 'border-gray-400');
            boton.classList.add('bg-red-400', 'border-red-600');
            const circulo = boton.querySelector('.letra-circulo');
            circulo.classList.remove('bg-orange-500', 'border-gray-800');
            circulo.classList.add('bg-red-600', 'border-red-800');
        }
    }

    function mostrarRespuestaCorrecta() {
        document.querySelectorAll('.respuesta-btn').forEach(btn => {
            const dataCorrecta = btn.getAttribute('data-correcta');
            const esCorrecta = (dataCorrecta === '1');

            if (esCorrecta) {
                btn.classList.remove('bg-white', 'hover:bg-gray-50', 'border-gray-400');
                btn.classList.add('bg-green-400', 'border-green-600');
                const circulo = btn.querySelector('.letra-circulo');
                circulo.classList.remove('bg-orange-500', 'border-gray-800');
                circulo.classList.add('bg-green-600', 'border-green-800');
            }
        });
    }

    document.querySelectorAll('.respuesta-btn').forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();

            clearInterval(countdownInterval);

            document.querySelectorAll('.respuesta-btn').forEach(btn => {
                btn.disabled = true;
                btn.classList.remove('hover:bg-gray-50');
                btn.style.pointerEvents = 'none';
            });

            const respuestaId = this.getAttribute('data-respuesta-id');
            const dataCorrecta = this.getAttribute('data-correcta');
            const esCorrecta = (dataCorrecta === '1');

            marcarRespuesta(this, esCorrecta);

            if (!esCorrecta) {
                mostrarRespuestaCorrecta();
            }

            mostrarFeedback(esCorrecta);

            document.getElementById('respuestaInput').value = respuestaId;

            setTimeout(() => {
                document.getElementById('formRespuestas').submit();
            }, 2000);
        });
    });
});