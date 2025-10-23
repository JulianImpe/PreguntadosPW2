<?php

class PartidaController {
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;


        if (!isset($_SESSION['puntaje_actual'])) {
            $_SESSION['puntaje_actual'] = 0;
        }
    }

    public function base()
    {
        $this->mostrarPartida();
    }

    function mostrarPartida()
    {
        $preguntaRender = $this->model->getPreguntaRender();

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
        $data = $this->model->procesarRespuesta($preguntaId, $respuestaId);


            $this->renderer->render('partidaFinalizada', $data);
        }


}