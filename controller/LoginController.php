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

public function login()
{
    $usuario = trim($_POST["usuario"] ?? '');
    $password = trim($_POST["password"] ?? '');

    if (empty($usuario) || empty($password)) {
        $this->renderer->render("login", ["error" => "Todos los campos son obligatorios"]);
        return;
    }

    $resultado = $this->model->getUserWith($usuario, $password);

    if (!empty($resultado)) {

        if ($resultado[0]['email_validado'] == 0) {
            $email = $resultado[0]['email'];
            $data = [
                'error' => "Tu cuenta aún no ha sido validada. Por favor revisa tu correo.",
                'email' => $email,
                'mostrar_reenvio' => true
            ];
            $this->renderer->render("login", $data);
            return;
        }


        $_SESSION["usuario_id"] = $resultado[0]['ID'];
        $_SESSION["usuario"] = $resultado[0]['usuario'];
        $_SESSION["rol"] = $resultado[0]['rol'];

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
    if ($_SESSION["rol"] === "admin") {
        header("Location: /admin/dashboard");
        exit;
    }

    if ($_SESSION["rol"] === "editor") {
        header("Location: /editor/lobbyEditor");
        exit;
    }


    header("Location: /lobby/base");
    exit;
}




}

