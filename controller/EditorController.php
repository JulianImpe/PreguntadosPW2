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

        // Procesar toast si existe
        $toast = null;
        if (isset($_SESSION['toast'])) {
            $toast = $_SESSION['toast'];
            unset($_SESSION['toast']);
        }

        $data = [
            // Datos del editor
            'ID' => $datosUsuario['ID'] ?? $usuarioId,
            'usuario_id' => $datosUsuario['usuario_id'] ?? $usuarioId,
            'usuario' => $datosUsuario['usuario'] ?? 'Editor',
            'nombre_completo' => $datosUsuario['nombre_completo'] ?? '',
            'email' => $datosUsuario['email'] ?? '',
            'fecha_nacimiento' => $this->formatearFecha($datosUsuario['fecha_nac'] ?? null),
            'foto_perfil' => !empty($datosUsuario['foto_perfil'])
                ? '/public/img/' . basename($datosUsuario['foto_perfil'])
                : '/public/img/default-avatar.png',
            'tiene_foto' => !empty($datosUsuario['foto_perfil']),
            
            // Datos de la vista
            'nombre_editor' => $datosUsuario['usuario'] ?? 'Editor',
            'total_sugeridas' => count($preguntasSugeridas),
            'total_reportadas' => count($preguntasReportadas),
            'preguntas_sugeridas' => $preguntasSugeridas,
            'preguntas_reportadas' => $preguntasReportadas
        ];
        unset($_SESSION['exito_reporte']);
        $this->renderer->render("lobbyEditor", $data);
    }

    public function aprobarSugerida(){
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        $this->model->aprobarPreguntaSugerida($preguntaId, $editorId);

        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function rechazarSugerida(){
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        $this->model->rechazarPreguntaSugerida($preguntaId, $editorId);

        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function aprobarReportada(){
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        $this->model->aprobarPreguntaReportada($preguntaId, $editorId);

        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function eliminarReportada(){
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        $this->model->eliminarPreguntaReportada($preguntaId, $editorId);

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

        header("Location: /editor/lobbyEditor");
        exit;
    }
    
    /**
     * Formatea la fecha de nacimiento al formato DD/MM/YYYY
     */
    private function formatearFecha($fecha)
    {
        if (empty($fecha)) {
            return '';
        }
        
        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            return $fecha;
        }
        
        return date('d/m/Y', $timestamp);
    }
    
    /**
     * Calcula la edad a partir de la fecha de nacimiento
     */
    private function calcularEdad($fechaNacimiento)
    {
        if (empty($fechaNacimiento)) {
            return 0;
        }
        
        $nacimiento = new DateTime($fechaNacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($nacimiento);
        
        return $edad->y;
    }
}