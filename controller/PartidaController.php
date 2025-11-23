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

    //este metodo lo q hace es traerme los datos del usuario asi el header se ve bien
    private function addUserData($data = [])
    {
        if (isset($_SESSION['usuario_id'])) {
            $data['usuario'] = [
                'usuario_id' => $_SESSION['usuario_id'],
                'nombre' => $_SESSION['nombre'] ?? '',
                'email' => $_SESSION['email'] ?? ''
            ];
        }
        return $data;
    }

    public function base()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: /login/loginForm");
            exit;
        }

        // FORZAR limpieza de partida anterior
        if (isset($_SESSION['partida_id'])) {
            error_log("Limpiando partida anterior: " . $_SESSION['partida_id']);
            unset($_SESSION['partida_id']);
        }
        unset($_SESSION['puntaje']);
        unset($_SESSION['categoria_actual']);
        unset($_SESSION['pregunta_activa']);

        $partidaId = $this->model->crearPartida($_SESSION['usuario_id']);
        $_SESSION['partida_id'] = $partidaId;

        error_log("Nueva partida creada con ID: " . $partidaId);

        header("Location: /ruleta/base");
        exit;
    }

    function mostrarPartida()
    {
        // DEBUG - AGREGÁ ESTO TEMPORALMENTE
        error_log("=== MOSTRAR PARTIDA ===");
        error_log("partida_id en sesión: " . ($_SESSION['partida_id'] ?? 'NO EXISTE'));
        error_log("categoria_actual: " . ($_SESSION['categoria_actual'] ?? 'NO EXISTE'));

        // Si venimos de la ruleta, limpiar pregunta anterior
        if (isset($_SESSION['categoria_actual']) && isset($_SESSION['pregunta_activa'])) {
            unset($_SESSION['pregunta_activa']);
        }
        if (isset($_SESSION['pregunta_activa'])) {
            $inicio = $_SESSION['pregunta_activa']['inicio'];
            $duracion = 15;
            $transcurrido = time() - $inicio;

            if ($transcurrido > $duracion) {
                $preguntaId = $_SESSION['pregunta_activa']['id'];
                $this->model->registrarRespuesta($_SESSION['partida_id'], $preguntaId, 0);

                $respuestaCorrecta = $this->model->getRespuestaCorrecta($preguntaId);
                $respuestaCorrectaId = $respuestaCorrecta[0]['ID'] ?? 0;

                $data = $this->model->procesarRespuesta($preguntaId, $respuestaCorrectaId, true);

                $this->model->finalizarPartida($_SESSION['partida_id'], $_SESSION['puntaje']);
                unset($_SESSION['puntaje']);
                unset($_SESSION['partida_id']);
                unset($_SESSION['pregunta_activa']);

                $data = $this->addUserData([
                    'esCorrecta' => false,
                    'mensaje' => '¡Tiempo agotado!',
                    'puntaje' => 0,
                    'pregunta' => null,
                    'partida_terminada' => true
                ]);

                $this->renderer->render('partidaFinalizada', $data);
                unset($_SESSION['pregunta_activa']);
                return;
            }
        }
        //agregue la variable medalla y la agregue en los parametros de getpreguntarender
        //para que ya no sea mas aleatoria, esta es la linea que estaba antes
        //$preguntaRender = $this->model->getPreguntaRender($_SESSION['usuario_id'];
        $medallaId = $_SESSION['categoria_actual'] ?? null;
        $partidaId = $_SESSION['partida_id'] ?? null;

        error_log("Llamando getPreguntaRender con partidaId: $partidaId");

        $preguntaRender = $this->model->getPreguntaRender($_SESSION['usuario_id'], $medallaId, $partidaId);

        if (!$preguntaRender) {
            $data = $this->addUserData([
                'esCorrecta' => false,
                'mensaje' => 'No hay preguntas disponibles',
                'puntaje' => $_SESSION['puntaje'] ?? 0,
                'pregunta' => null,
                'partida_terminada' => true
            ]);

            $this->renderer->render('partidaFinalizada', $data);
            return;
        }

        error_log("Pregunta obtenida ID: " . $preguntaRender['id']);

        // llamo al model
        if ($this->model->verificarPreguntaYaVista($partidaId, $preguntaRender['id'])) {
            error_log("⚠️ ADVERTENCIA: Esta pregunta YA fue vista en esta partida!");
        }

        $medallas = $this->model->getMedallaDeLaPregunta($preguntaRender['id']);

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

        $data = ["pregunta" => $preguntaRender];

        if (isset($_SESSION['exito_reporte'])) {
            $data['exito_reporte'] = $_SESSION['exito_reporte'];
            unset($_SESSION['exito_reporte']);
        }

        if (isset($_SESSION['error_reporte'])) {
            $data['error_reporte'] = $_SESSION['error_reporte'];
            unset($_SESSION['error_reporte']);
        }

        $data = array_merge($data, [
            "medallas" => $medallas
        ]);

        $data = $this->addUserData($data);

        $this->renderer->render("crearPartida", $data);

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

        $tiempoAgotado = false;
        if (isset($_SESSION['pregunta_activa'])) {
            $inicio = $_SESSION['pregunta_activa']['inicio'];
            $transcurrido = time() - $inicio;
            if ($transcurrido > 18) {
                $tiempoAgotado = true;
            }
        }

        unset($_SESSION['pregunta_activa']);
        unset($_SESSION['categoria_actual']);

        $data = $this->model->procesarRespuesta($preguntaId, $respuestaId, $tiempoAgotado);

        // IMPORTANTE: Registrar ANTES de verificar si terminó
        error_log("Registrando respuesta: Partida=" . $_SESSION['partida_id'] . ", Pregunta=$preguntaId, Correcta=" . ($data['esCorrecta'] ? '1' : '0'));
        $this->model->registrarRespuesta($_SESSION['partida_id'], $preguntaId, $data['esCorrecta'] ? 1 : 0);

        if (isset($data['partida_terminada']) && $data['partida_terminada'] === true) {
            $this->model->finalizarPartida($_SESSION['partida_id'], $_SESSION['puntaje']);
            unset($_SESSION['puntaje']);
            unset($_SESSION['partida_id']);
        }

        $data = $this->addUserData($data);

        $this->renderer->render('partidaFinalizada', $data);
    }
    public function enviarReporte()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /lobby/base');
            exit;
        }

        $resultado = $this->model->enviarReporte(
            $_POST['pregunta_id'] ?? null,
            $_SESSION['usuario_id'] ?? null,
            trim($_POST['motivo'] ?? '')
        );

        $_SESSION[$resultado['ok'] ? 'exito_reporte' : 'error_reporte'] = $resultado['msg'];
        header('Location: /partida/base');
        exit;
    }

    public function actualizarPreguntaCompleta()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /editor/lobbyEditor");
            exit;
        }

        $resultado = $this->model->actualizarPreguntaCompleta(
            $_POST['pregunta_id'] ?? null,
            $_POST['texto'] ?? '',
            $_POST['respuestas'] ?? []
        );

        $_SESSION[$resultado['ok'] ? 'mensaje' : 'error'] = $resultado['msg'];
        header("Location: /editor/lobbyEditor");
        exit;
    }


}