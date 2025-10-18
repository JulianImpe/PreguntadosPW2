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
        $usuario = trim($_POST["usuario"]);
        $password = trim($_POST["password"]);
        $email = trim($_POST["email"]);
        $fecha = $_POST["fechaNacimiento"];
        $nombre = trim($_POST["nombre_completo"]);
        if (strlen($password) < 6 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>
                alert('Error: contraseña o email inválido');
                window.history.back();
            </script>";
            return;
        }
        $foto_perfil = $this->subirFoto();
        $this->model->registrarUsuario($usuario, $password, $email, $fecha, $nombre);


        $this->redirectToIndex();
    }
    public function redirectToIndex()
    {
        header("Location: /Preguntados/index.php?controller=Pokemon&method=base");
        exit;
    }
    private function subirFoto()
    {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $nombre = uniqid() . "_" . $_FILES['foto_perfil']['name'];
            $destino = "uploads/" . $nombre;
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino);
            return $destino;
        }
        return "uploads/default.png"; // imagen por defecto
    }
}
