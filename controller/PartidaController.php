<?php

class PartidaController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;

        // Inicializamos puntaje si no existe
        if (!isset($_SESSION['puntaje'])) {
            $_SESSION['puntaje'] = 0;
        }
    }

    public function base()
    {

        if (!isset($_SESSION['usuario_id'])) {
            // Redirigir a login si no hay usuario
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
            $duracion = 15; // segundos de limite
            $transcurrido = time() - $inicio;

            if ($transcurrido > $duracion) {

                $this->renderer->render('partidaFinalizada', [
                    'esCorrecta' => false,
                    'mensaje' => '¡Tiempo agotado!',
                    'puntaje' => $_SESSION['puntaje'],
                    'pregunta' => null,
                    'partida_terminada' => true
                ]);

                unset($_SESSION['pregunta_activa']); // limpiar estado
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

        // estilos para dificultad
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

        // Guardar el inicio de la nueva pregunta
        $_SESSION['pregunta_activa'] = [
            'id' => $preguntaRender['id'],
            'inicio' => time()
        ];

        // Renderizar la vista
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

        unset($_SESSION['pregunta_activa']);


        $data = $this->model->procesarRespuesta($preguntaId, $respuestaId);

        if (isset($data['partida_terminada']) && $data['partida_terminada'] === true) {
            $this->model->finalizarPartida($_SESSION['partida_id'], $_SESSION['puntaje']);
            unset($_SESSION['puntaje']);
            unset($_SESSION['partida_id']);
        }

        $this->renderer->render('partidaFinalizada', $data);
    }
}
