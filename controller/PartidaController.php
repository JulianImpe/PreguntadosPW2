<?php

class PartidaController {
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;

        // Inicializar sesión si no existe
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Inicializar puntaje
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
        $resultado = $this->model->getPreguntaYSuRespuesta();

        if ($resultado && count($resultado) > 0) {
            // Encontrar la respuesta correcta
            $respuestaCorrecta = null;
            foreach ($resultado as $r) {
                if ($r['esCorrecta'] == 1) {
                    $respuestaCorrecta = $r['respuestaID'];
                    break;
                }
            }

            $preguntaRender = [
                "texto" => $resultado[0]['preguntaTexto'],
                "id" => $resultado[0]['preguntaID'],
                "puntaje_actual" => $_SESSION['puntaje_actual'] ?? 0,
                "respuesta_correcta_id" => $respuestaCorrecta,
                "respuestas" => array_map(function ($r, $i) {
                    $esCorrecta = ($r['esCorrecta'] == 1);

                    return [
                        "id" => $r['respuestaID'],
                        "texto" => $r['respuestaTexto'],
                        "letra" => chr(65 + $i),
                        "es_correcta" => $esCorrecta,
                        "es_correcta_str" => $esCorrecta ? '1' : '0'
                    ];
                }, $resultado, array_keys($resultado))
            ];

            $this->renderer->render("crearPartida", ["pregunta" => $preguntaRender]);
        } else {
            $this->renderer->render("crearPartida", ["pregunta" => null]);
        }
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

    // Obtener la respuesta correcta
    $respuestaCorrecta = $this->model->getRespuestaCorrecta($preguntaId);

    // Verificar si la respuesta es correcta
    $esCorrecta = false;
    if (!empty($respuestaCorrecta)) {
        $esCorrecta = ($respuestaId == $respuestaCorrecta[0]['ID']);
    }

    // Actualizar puntaje si es correcta
    if ($esCorrecta) {
        $_SESSION['puntaje'] = ($_SESSION['puntaje'] ?? 0) + 10;
    }

    // Obtener la pregunta actual para mostrar en el resultado
    $preguntaActual = $this->model->getPreguntaPorId($preguntaId);

    $data = [
        'esCorrecta' => $esCorrecta,
        'puntaje' => $_SESSION['puntaje'] ?? 0,
        'pregunta' => $preguntaActual
    ];

    // CORRECCIÓN: Cambiar $this->view por $this->renderer
    $this->renderer->render('partidaFinalizada', $data);
}

}