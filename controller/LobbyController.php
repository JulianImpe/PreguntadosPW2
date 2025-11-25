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



        if (isset($_SESSION['info_lobby'])) {
            $datosUsuario['info_lobby'] = $_SESSION['info_lobby'];
            unset($_SESSION['info_lobby']);
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