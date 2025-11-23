<?php

class LobbyController
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
        $this->index();
    }

    public function index()
    {
       // if (!isset($_SESSION["usuario_id"])) {
         //   header("Location:login/loginForm");
           // exit;
        //}

        $usuario = $_SESSION["usuario_id"];
        $datosUsuario = $this->model->obtenerDatosUsuario($usuario);

        if (empty($datosUsuario)) {
            $datosUsuario = [
                'usuario' => $usuario,
                'puntos' => 0,
                'partidas_jugadas' => 0,
                'partidas_ganadas' => 0,
                'nivel' => 1,
                'ranking' => '-'
            ];
        }
        $datosUsuario['usuario_id'] = $usuario;
        if (!empty($datosUsuario['foto_perfil'])) {
            $datosUsuario['foto_perfil'] = '/public/img/' . basename($datosUsuario['foto_perfil']);
            $datosUsuario['tiene_foto'] = true;
        } else {
            $datosUsuario['foto_perfil'] = '/img/default-avatar.png';
            $datosUsuario['tiene_foto'] = false;
        }

        $partidasRecientes = $this->model->obtenerPartidasRecientes($usuario);
        $datosUsuario['partidas_recientes'] = $partidasRecientes;


        // Estas líneas verifican si hay mensajes en la sesión
        if (isset($_SESSION['info_lobby'])) {
            $datosUsuario['info_lobby'] = $_SESSION['info_lobby'];  // Pasa el mensaje a la vista
            unset($_SESSION['info_lobby']);  // Lo elimina de la sesión
        }

        if (isset($_SESSION['error_lobby'])) {
            $datosUsuario['error_lobby'] = $_SESSION['error_lobby'];
            unset($_SESSION['error_lobby']);
        }


        $this->renderer->render("lobby", $datosUsuario);
    }
    public function crearPartidaVista()
    {

        if (!isset($_SESSION["usuario_id"])) {
            header("Location: /login/loginForm");
            exit;
        }
        header("Location: /partida/base");
        exit;
    }
}