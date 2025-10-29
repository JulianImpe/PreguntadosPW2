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
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION["usuario_id"])) {
            header("Location:login/loginForm");
            exit;
        }

        $usuario = $_SESSION["usuario_id"];

        // Obtener datos del usuario desde el modelo
        $datosUsuario = $this->model->obtenerDatosUsuario($usuario);

        // Si no se encuentran datos, usar valores por defecto
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

        // Obtener últimas partidas
        $partidasRecientes = $this->model->obtenerPartidasRecientes($usuario);

        $datosUsuario['partidas_recientes'] = $partidasRecientes;

        // Renderizar la vista del lobby
        $this->renderer->render("lobby", $datosUsuario);
    }

    public function crearPartidaVista()
    {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION["usuario_id"])) {
            header("Location: /login/loginForm");
            exit;
        }

        // Redirigir a la función mostrarPartida
        header("Location: /partida/base");
        exit;
    }

    //no cambie las urls porque no tenia el xampp configurado

}