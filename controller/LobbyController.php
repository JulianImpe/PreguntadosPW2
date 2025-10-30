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

/**
 * Redirige al índice de la vista de Lobby.
 */

    public function base()
    {
        $this->index();
    }

    public function index()
    {

        if (!isset($_SESSION["usuario_id"])) {
            header("Location:login/loginForm");
            exit;
        }

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


        $partidasRecientes = $this->model->obtenerPartidasRecientes($usuario);

        $datosUsuario['partidas_recientes'] = $partidasRecientes;

        // Renderizar la vista del lobby
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