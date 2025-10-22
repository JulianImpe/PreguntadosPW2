<?php

class PartidaController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function base()
    {
        $this->mostrarPartida();
    }

    public function mostrarPartida()
    {
        $resultado = $this->model->getPreguntaYSuRespuesta();

        if ($resultado && count($resultado) > 0) {
            $preguntaRender = [
                "texto" => $resultado[0]['preguntaTexto'],
                "id" => $resultado[0]['preguntaID'],
                "respuestas" => array_map(function($r, $i){
                    return [
                        "id" => $r['respuestaID'],
                        "texto" => $r['respuestaTexto'],
                        "letra" => chr(65 + $i)
                    ];
                }, $resultado, array_keys($resultado))
            ];

            $this->renderer->render("crearPartida", ["pregunta" => $preguntaRender]);
        } else {
            $this->renderer->render("crearPartida", ["pregunta" => null]);
        }
    }

 //faltaria redirigir a la vista cuando es correcta
}