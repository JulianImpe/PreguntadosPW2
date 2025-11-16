<?php
class SugerenciaController{
    private $model;
    private $renderer;
    public function __construct($model, $renderer){
        $this->model = $model;
        $this->renderer = $renderer;
    }
    public function form(){
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: /login/loginForm");
            exit;
        }
        $medallas = $this->model->obtenerMedallas();
        $data = [
            "medallas" => $medallas,
            "usuario" => true,
            "usuario_id" => $_SESSION['usuario_id']
        ];
        if (isset($_SESSION['exito'])) {
            $data['exito'] = $_SESSION['exito'];
            unset($_SESSION['exito']);
        }
        if (isset($_SESSION['error'])) {
            $data['error'] = $_SESSION['error'];
            unset($_SESSION['error']);
        }
        $this->renderer->render("sugerirPregunta", $data);
    }

    public function guardar(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /sugerencia/form");
            exit;
        }
        $texto = trim($_POST['texto']);
        $respuestas = $_POST['respuestas'] ?? [];
        $medallaId = intval($_POST['medallaId']);
        $correcta = intval($_POST['correcta'] ?? -1);

        if (empty($texto) || count($respuestas) < 4 || $correcta < 0) {
            $_SESSION['error'] = "Completa todos los campos correctamente.";
            header("Location: /sugerencia/form");
            exit;
        }
        $resultado = $this->model->guardarSugerencia($_SESSION['usuario_id'], $texto ,$medallaId, $respuestas, $correcta);
        $_SESSION[$resultado['ok'] ? 'exito' : 'error'] = $resultado['msg'];
        header("Location: /sugerencia/form");
        exit;
    }
}
