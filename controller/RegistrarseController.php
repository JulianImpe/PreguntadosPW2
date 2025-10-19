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
        $repetir = trim($_POST["repetir_password"]);
        $email = trim($_POST["email"]);
        $fecha = $_POST["fecha_nac"];
        $nombre = trim($_POST["nombre_completo"]);

        // --- LIBRERIA TOASTR---
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>';
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">';
        echo '<script>toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toast-top-right" }</script>';


        // 1. Campos vacíos
        if (empty($usuario) || empty($password) || empty($repetir) || empty($email) || empty($fecha) || empty($nombre)) {
            $data['error'] = "Todos los campos son obligatorios";
            $this->renderer->render("registrarse", $data);
            return;
        }

        // 2. Contraseñas iguales
        if ($password !== $repetir) {
            $data['error'] = "Las contraseñas no coinciden";
            $this->renderer->render("registrarse", $data);
            return;
        }


        // 3. Longitud y complejidad
        if (strlen($password) < 6 || !preg_match('/[A-Z]/', $password)) {
            $data['error'] = "La contraseña debe tener al menos 6 caracteres y contener una letra mayúscula";
            $this->renderer->render("registrarse", $data);
            return;
        }

        // 4. Email válido
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data['error'] = "El email no es válido";
            $this->renderer->render("registrarse", $data);
            return;
        }

        // 6. Ya existe usuario, email o nombre completo
        if ($this->model->existeUsuario($usuario)) {
            $data['error'] = "El nombre de usuario ya está en uso";
            $this->renderer->render("registrarse", $data);
            return;
        }

        if ($this->model->existeEmail($email)) {
            $data['error'] = "El email ya está en uso";
            $this->renderer->render("registrarse", $data);
            return;
        }


        // --- Si pasa todas las validaciones ---
        $foto_perfil = $this->subirFoto();
        $this->model->registrarUsuario($usuario, $password, $email, $fecha, $foto_perfil, $nombre);

        include 'helper/enviarEmail.php';
        $this->mostrarMailEnviado();
    }

    private function subirFoto()
    {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $nombre = uniqid() . "_" . $_FILES['foto_perfil']['name'];
            $directorio = __DIR__ . '/../imagenes/';
            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }
            $destino = $directorio . $nombre;
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino);
            return 'imagenes/' . $nombre;
        }
        return null;
    }


    public function mostrarMailEnviado()
    {
        $data['sucess'] = "Te hemos enviado un correo de confirmación. Por favor, revisa tu bandeja de entrada.";
        $this->renderer->render("mailEnviado", $data); // sin datos por ahora
    }
}