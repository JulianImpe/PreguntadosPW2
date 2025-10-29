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

        //le pongo estilos a las dificultades
        $clase = 'bg-gray-200 text-gray-800 border-gray-300'; // default 
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