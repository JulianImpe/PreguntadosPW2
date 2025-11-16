<?php

class EditorController
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
        $this->lobbyEditor();
    }

    public function lobbyEditor()
    {
        if (!isset($_SESSION["usuario_id"])) {
            header("Location: /login/loginForm");
            exit;
        }

        $usuarioId = $_SESSION["usuario_id"];
        $datosUsuario = $this->model->obtenerDatosUsuario($usuarioId);

        $preguntasSugeridas = $this->model->obtenerPreguntasSugeridas();
        $preguntasReportadas = $this->model->obtenerPreguntasReportadas();

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
            'preguntas_reportadas' => $preguntasReportadas,
            
            // ✅ Mensajes de notificación (ANTES de borrarlos)
            'exito_reporte' => $_SESSION['exito_reporte'] ?? null,
            'error_reporte' => $_SESSION['error_reporte'] ?? null
        ];

        // ✅ AHORA SÍ los borramos DESPUÉS de agregarlos a $data
        unset($_SESSION['exito_reporte']);
        unset($_SESSION['error_reporte']);

        $this->renderer->render("lobbyEditor", $data);
    }

    public function aprobarSugerida()
    {
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        
        $resultado = $this->model->aprobarPreguntaSugerida($preguntaId, $editorId);
        
        if ($resultado) {
            $_SESSION['exito_reporte'] = "Pregunta sugerida aprobada exitosamente";
        } else {
            $_SESSION['error_reporte'] = "Error al aprobar la pregunta sugerida";
        }

        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function rechazarSugerida()
    {
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        
        $resultado = $this->model->rechazarPreguntaSugerida($preguntaId, $editorId);
        
        if ($resultado) {
            $_SESSION['exito_reporte'] = "Pregunta sugerida rechazada exitosamente";
        } else {
            $_SESSION['error_reporte'] = "Error al rechazar la pregunta sugerida";
        }

        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function aprobarReportada()
    {
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        
        $resultado = $this->model->aprobarPreguntaReportada($preguntaId, $editorId);
        
        if ($resultado) {
            $_SESSION['exito_reporte'] = "Pregunta reportada aprobada exitosamente";
        } else {
            $_SESSION['error_reporte'] = "Error al aprobar la pregunta reportada";
        }

        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function eliminarReportada()
    {
        $preguntaId = $_POST['pregunta_id'] ?? $_POST['id'] ?? null;
        $editorId = $_SESSION['usuario_id'];
        
        $resultado = $this->model->eliminarPreguntaReportada($preguntaId, $editorId);
        
        if ($resultado) {
            $_SESSION['exito_reporte'] = "Pregunta reportada eliminada exitosamente";
        } else {
            $_SESSION['error_reporte'] = "Error al eliminar la pregunta reportada";
        }

        header("Location: /editor/lobbyEditor");
        exit;
    }

    public function actualizarPreguntaCompleta()
    {
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

        $resultado = $this->model->actualizarPreguntaCompleta($preguntaId, $textoPregunta, $respuestas);
        
        if ($resultado) {
            $_SESSION['exito_reporte'] = "Pregunta actualizada y aprobada exitosamente";
        } else {
            $_SESSION['error_reporte'] = "Error al actualizar la pregunta";
        }

        header("Location: /editor/lobbyEditor");
        exit;
    }


    public function gestionarMedallas()
    {
        $medallas = $this->model->getAllMedallas();


        $medallasNormalizadas = [];
        foreach ($medallas as $medalla) {
            $medallasNormalizadas[] = [
                'ID' => $medalla['ID'],
                'Nombre' => $medalla['Nombre'],
                'Color' => $medalla['Color'],
                'imagen_url' => $medalla['Imagen_url']
            ];
        }

        $this->renderer->render("medallas", [
            "medallas" => $medallasNormalizadas
        ]);
    }



    public function crearMedalla()
    {
        $this->renderer->render("crearMedallas");
    }




    public function editarMedalla()
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            header("Location: /editor/medallas");
            exit;
        }

        $medalla = $this->model->getMedallaById($id);

        if (!$medalla) {
            header("Location: /editor/medallas");
            exit;
        }

        $this->renderer->render("editarMedalla", [
            "es_creacion" => false,
            "medalla" => $medalla
        ]);
    }





    public function guardarMedalla()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /editor/medallas");
            exit;
        }

        $id = $_POST["id"] ?? null;
        $imagenUrl = null;

        if (isset($_FILES["Imagen"]) && $_FILES["Imagen"]["error"] === 0) {

            $carpetaDestino = "public/img/categorias/";

            if (!file_exists($carpetaDestino)) {
                mkdir($carpetaDestino, 0777, true);
            }

            $nombreArchivo = time() . "_" . basename($_FILES["Imagen"]["name"]);
            $rutaDestino = $carpetaDestino . $nombreArchivo;

            if (move_uploaded_file($_FILES["Imagen"]["tmp_name"], $rutaDestino)) {

                $imagenUrl = "img/categorias/" . $nombreArchivo;
            }
        } else {

            $imagenUrl = $_POST["Imagen_url_actual"] ?? null;
        }

        $data = [
            "Nombre" => $_POST["Nombre"] ?? "",
            "Color" => $_POST["Color"] ?? "",
            "Imagen_url" => $imagenUrl
        ];

        if ($id) {
            $this->model->updateMedalla($id, $data);
        } else {
            $this->model->createMedalla($data);
        }

        header("Location: /editor/medallas");
        exit;
    }





    public function eliminarMedalla()
    {
        if (!isset($_POST["id"])) {
            echo "Error: falta el ID";
            return;
        }

        $id = $_POST["id"];

        $this->model->eliminarMedallaPorId($id);

        header("Location: /editor/medallas");
        exit;
    }
        public function formatearFecha($fecha)
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
}
