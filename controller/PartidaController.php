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
        if (isset($_SESSION['pregunta_activa'])) {
            $inicio = $_SESSION['pregunta_activa']['inicio'];
            $duracion = 15;
            $transcurrido = time() - $inicio;

            if ($transcurrido > $duracion) {
                $this->renderer->render('partidaFinalizada', [
                    'esCorrecta' => false,
                    'mensaje' => '¡Tiempo agotado!',
                    'puntaje' => $_SESSION['puntaje'],
                    'pregunta' => null,
                    'partida_terminada' => true
                ]);
                unset($_SESSION['pregunta_activa']);
                return;
            }
        }

        if (isset($_SESSION['pregunta_activa'])) {
            $this->renderer->render('partidaFinalizada', [
                'esCorrecta' => false,
                'mensaje' => 'Trampa detectada o recarga de página',
                'puntaje' => $_SESSION['puntaje'],
                'pregunta' => null,
                'partida_terminada' => true
            ]);
            unset($_SESSION['pregunta_activa']);
            return;
        }

        $preguntaRender = $this->model->getPreguntaRender();

        $clase = 'bg-gray-200 text-gray-800 border-gray-300';
        $nivel = $preguntaRender['nivel_dificultad'] ?? null;
        if ($nivel === 'Fácil') {
            $clase = 'bg-green-100 text-green-800 border-green-300';
        } elseif ($nivel === 'Medio') {
            $clase = 'bg-yellow-100 text-yellow-800 border-yellow-300';
        } elseif ($nivel === 'Difícil') {
            $clase = 'bg-red-100 text-red-800 border-red-300';
        }

        if ($preguntaRender) {
            $preguntaRender['dificultad_clase'] = $clase;
        }

        $_SESSION['pregunta_activa'] = [
            'id' => $preguntaRender['id'],
            'inicio' => time()
        ];

        // ESTO ES LO NUEVO 👇
        $data = ["pregunta" => $preguntaRender];

        // Agregar mensajes si existen
        if (isset($_SESSION['exito_reporte'])) {
            $data['exito_reporte'] = $_SESSION['exito_reporte'];
            unset($_SESSION['exito_reporte']);
        }

        if (isset($_SESSION['error_reporte'])) {
            $data['error_reporte'] = $_SESSION['error_reporte'];
            unset($_SESSION['error_reporte']);
        }

        $this->renderer->render("crearPartida", $data);
        // HASTA ACÁ 👆
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

        unset($_SESSION['pregunta_activa']);


        $data = $this->model->procesarRespuesta($preguntaId, $respuestaId);

        if (isset($data['partida_terminada']) && $data['partida_terminada'] === true) {
            $this->model->finalizarPartida($_SESSION['partida_id'], $_SESSION['puntaje']);
            unset($_SESSION['puntaje']);
            unset($_SESSION['partida_id']);
        }

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
