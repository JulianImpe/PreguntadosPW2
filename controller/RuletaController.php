<?php

class RuletaController
{
    private $model;
    private $renderer;
    private $partidaController;

    public function __construct($model, $renderer, $partidaController)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->partidaController = $partidaController;
    }

    public function base()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: /login/loginForm");
            exit;
        }

        $medallas = $this->model->getCategorias();
        $total = count($medallas);

        foreach ($medallas as $i => &$medalla) {
            $medalla['angulo'] = $i * (360 / $total);
        }

        $data = [
            'categorias' => $medallas,
            'usuario' => [
                'usuario_id' => $_SESSION['usuario_id'],
                'nombre' => $_SESSION['nombre'] ?? '',
                'email' => $_SESSION['email'] ?? ''
            ]
        ];

        // Pasar mensaje de cambio de categoría si existe
        if (isset($_SESSION['cambio_categoria'])) {
            $data['cambio_categoria'] = $_SESSION['cambio_categoria'];
            unset($_SESSION['cambio_categoria']); // Limpiar después de usarlo
        }

        $this->renderer->render("ruleta", $data);
    }

    public function seleccionarCategoria()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: /ruleta/base");
            exit;
        }
        $medallaId = $_POST['medalla_id'] ?? null;

        if (!$medallaId) {
            header("Location: /ruleta/base");
            exit;
        }

        // Guardamos la categoría elegida en sesión
        $_SESSION["categoria_actual"] = (int)$medallaId;

        //limpio lo de pregunta activa (esto hacia q me tire vista de incorrecta
        unset($_SESSION['pregunta_activa']);

        // NUEVO: Verificar si hay preguntas disponibles de esta categoría
        $partidaId = $_SESSION['partida_id'] ?? null;
        $usuarioId = $_SESSION['usuario_id'] ?? null;

        if ($partidaId && $usuarioId) {
            $hayPreguntas = $this->model->verificarPreguntasDisponibles($usuarioId, $medallaId, $partidaId);

            if (!$hayPreguntas) {
                // No hay preguntas de esta categoría, buscar de otra
                $_SESSION['cambio_categoria'] = [
                    'categoria_original' => $medallaId,
                    'mensaje' => 'No hay más preguntas disponibles de esta categoría en esta partida'
                ];
            }
        }

        // Vamos A PARTIDA pero SOLO con esa categoría
        header("Location: /partida/mostrarPartida");
        exit;

    }
}
