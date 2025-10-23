<?php

class LoginController
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
        $this->login();
    }

    public function loginForm()
    {
        $this->renderer->render("login");
    }

   /* public function login()
    {
        $resultado = $this->model->getUserWith($_POST["usuario"], $_POST["password"]);

        if (sizeof($resultado) > 0) {
            $_SESSION["usuario"] = $_POST["usuario"];
            $this->redirectToIndex();
        } else {
            $this->renderer->render("login", ["error" => "Usuario o clave incorrecta"]);
        }
    }*/

    public function login()
    {
        $usuario = trim($_POST["usuario"] ?? '');
        $password = trim($_POST["password"] ?? '');

        // decidir si hay que traer los datos de usuario y contraseña o si el usuario los escribe
        if (empty($usuario) || empty($password)) {
            $this->renderer->render("login", ["error" => "Todos los campos son obligatorios"]);
            return;
        }

        $resultado = $this->model->getUserWith($usuario, $password);

        if (!empty($resultado)) {
            // Guardamos usuario en sesión
            $_SESSION["usuario"] = $usuario;

            // Redirigir al lobby
            $this->redirectToIndex();
        } else {
            $this->renderer->render("login", ["error" => "Usuario o contraseña incorrectos"]);
        }
    }

    public function logout()
    {
        session_destroy();
        $this->redirectToIndex();
    }


    public function registrarse()
    {
        $this->model->registrarUsuario($_POST["usuario"], $_POST["password"]);
        $this->redirectToIndex();
    }
    public function redirectToIndex()
    {
        header("Location: /lobby/base");
        exit;
    }



}

