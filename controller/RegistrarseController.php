<?php

class RegistrarseController
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
        $this->registrarse();
    }

    public function registrarseForm()
    {
        $this->renderer->render("registrarse");
    }
    public function registrarse()
    {
        $this->model->registrarUsuario($_POST["usuario"], $_POST["password"], $_POST["email"], $_POST["fechaNacimiento"], $_POST["foto_perfil"], $_POST["nombre_completo"]);
        $this->redirectToIndex();
    }
        public function redirectToIndex()
    {
        header("Location: /Preguntados/index.php?controller=Pokemon&method=base");
        exit;
    }
}