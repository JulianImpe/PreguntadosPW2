<?php

class PartidaController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;

        if (!isset($_SESSION['puntaje'])) {
            $_SESSION['puntaje'] = 0;
        }
    }

    public function base()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: /login/loginForm");
            exit;
        }

        $partidaId = $this->model->crearPartida($_SESSION['usuario_id']);
        $_SESSION['partida_id'] = $partidaId;

        $this->mostrarPartida();
    }

    function mostrarPartida()
    {
        // Verificar si hay pregunta activa y si se agotó el tiempo
        if (isset($_SESSION['pregunta_activa'])) {
            $inicio = $_SESSION['pregunta_activa']['inicio'];
            $duracion = 15;
            $transcurrido = time() - $inicio;

            if ($transcurrido > $duracion) {
                // Registrar que falló por tiempo
                $preguntaId = $_SESSION['pregunta_activa']['id'];
                $this->model->registrarRespuesta($_SESSION['partida_id'], $preguntaId, 0);
                
                // Procesar como respuesta incorrecta por tiempo agotado
                $respuestaCorrecta = $this->model->getRespuestaCorrecta($preguntaId);
                $respuestaCorrectaId = $respuestaCorrecta[0]['ID'] ?? 0;
                
                $data = $this->model->procesarRespuesta($preguntaId, $respuestaCorrectaId, true);
                
                $this->model->finalizarPartida($_SESSION['partida_id'], $_SESSION['puntaje']);
                unset($_SESSION['puntaje']);
                unset($_SESSION['partida_id']);
                unset($_SESSION['pregunta_activa']);
                
                $this->renderer->render('partidaFinalizada', [
                    'esCorrecta' => false,
                    'mensaje' => '¡Tiempo agotado!',
                    'puntaje' => 0,
                    'pregunta' => null,
                    'partida_terminada' => true
                ]);
                return;
            }
        }

        // Obtener pregunta según nivel del jugador
        $preguntaRender = $this->model->getPreguntaRender($_SESSION['usuario_id']);

        if (!$preguntaRender) {
            $this->renderer->render('partidaFinalizada', [
                'esCorrecta' => false,
                'mensaje' => 'No hay preguntas disponibles',
                'puntaje' => $_SESSION['puntaje'] ?? 0,
                'pregunta' => null,
                'partida_terminada' => true
            ]);
            return;
        }

        // Estilos para dificultad
        $clase = 'bg-gray-200 text-gray-800 border-gray-300';
        $nivel = $preguntaRender['nivel_dificultad'] ?? null;
        if ($nivel === 'Fácil') {
            $clase = 'bg-green-100 text-green-800 border-green-300';
        } elseif ($nivel === 'Medio') {
            $clase = 'bg-yellow-100 text-yellow-800 border-yellow-300';
        } elseif ($nivel === 'Difícil') {
            $clase = 'bg-red-100 text-red-800 border-red-300';
        }

        $preguntaRender['dificultad_clase'] = $clase;

        $_SESSION['pregunta_activa'] = [
            'id' => $preguntaRender['id'],
            'inicio' => time()
        ];

        $this->renderer->render("crearPartida", [
            "pregunta" => $preguntaRender
        ]);
    }

    public function responder()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /partida/base');
            exit;
        }

        $respuestaId = $_POST['respuesta'] ?? null;
        $preguntaId = $_POST['pregunta_id'] ?? null;

        if (!$respuestaId || !$preguntaId) {
            header('Location: /partida/base');
            exit;
        }

        // Verificar tiempo
        $tiempoAgotado = false;
        if (isset($_SESSION['pregunta_activa'])) {
            $inicio = $_SESSION['pregunta_activa']['inicio'];
            $transcurrido = time() - $inicio;
            if ($transcurrido > 15) {
                $tiempoAgotado = true;
            }
        }

        unset($_SESSION['pregunta_activa']);

        // Registrar respuesta
        $data = $this->model->procesarRespuesta($preguntaId, $respuestaId, $tiempoAgotado);
        $this->model->registrarRespuesta($_SESSION['partida_id'], $preguntaId, $data['esCorrecta'] ? 1 : 0);

        if (isset($data['partida_terminada']) && $data['partida_terminada'] === true) {
            $this->model->finalizarPartida($_SESSION['partida_id'], $_SESSION['puntaje']);
            unset($_SESSION['puntaje']);
            unset($_SESSION['partida_id']);
        }

        $this->renderer->render('partidaFinalizada', $data);
    }
}