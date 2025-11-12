<?php

class EditorController{
    private $model;
    private $renderer;
    public function __construct($model, $renderer){
        $this->model = $model;
        $this->renderer = $renderer;
    }
    public function base(){
        $this->lobbyEditor();
    }
    public function lobbyEditor(){
        if (!isset($_SESSION["usuario_id"])) {
            header("Location: /login/loginForm");
            exit;
        }
        $usuarioId = $_SESSION["usuario_id"];
        $datosUsuario = $this->model->obtenerDatosUsuario($usuarioId);
        $preguntasSugeridas = $this->model->obtenerPreguntasSugeridas();
        $preguntasReportadas = $this->model->obtenerPreguntasReportadas();
        $estadisticas = $this->model->obtenerEstadisticasEditor($usuarioId);

        $data = [
            'usuario' => $datosUsuario['usuario'] ?? 'Editor',
            'total_sugeridas' => count($preguntasSugeridas),
            'total_reportadas' => count($preguntasReportadas),
            'preguntas_sugeridas' => $preguntasSugeridas,
            'preguntas_reportadas' => $preguntasReportadas,
            'exito_reporte' => $_SESSION['exito_reporte'] ?? null
        ];
        unset($_SESSION['exito_reporte']);
        $this->renderer->render("lobbyEditor", $data);
    }

    public function aprobarSugerida(){
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        $this->model->aprobarPreguntaSugerida($preguntaId, $editorId);
        $_SESSION['exito_reporte'] = " Pregunta sugerida aprobada correctamente.";
        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function rechazarSugerida(){
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        $this->model->rechazarPreguntaSugerida($preguntaId, $editorId);
        $_SESSION['exito_reporte'] = " Pregunta sugerida rechazada correctamente.";

        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function aprobarReportada(){
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        $this->model->aprobarPreguntaReportada($preguntaId, $editorId);
        $_SESSION['exito_reporte'] = " Pregunta reportada aprobada correctamente.";

        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function eliminarReportada(){
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        $this->model->eliminarPreguntaReportada($preguntaId, $editorId);
        $_SESSION['exito_reporte'] = "Pregunta reportada eliminada correctamente.";

        header("Location: /editor/lobbyEditor");
        exit;
    }
    public function actualizarPreguntaCompleta(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /editor/lobbyEditor');
            exit;
        }
        $preguntaId = $_POST['pregunta_id'] ?? 0;
        $textoPregunta = trim($_POST['texto'] ?? '');
        $respuestasPost = $_POST['respuestas'] ?? [];
        $respuestas = [];
        foreach ($respuestasPost as $respuestaId => $datos) {
            if (is_array($datos) && !empty($datos['texto'])) {
                $respuestas[$respuestaId] = [
                    'texto' => trim($datos['texto']),
                    'es_correcta' => !empty($datos['es_correcta'])
                ];
            }
        }
        $this->model->actualizarPreguntaCompleta($preguntaId, $textoPregunta, $respuestas);
        $_SESSION['exito_reporte'] = "Pregunta actualizada correctamente.";
        header("Location: /editor/lobbyEditor");
        exit;
    }
}
