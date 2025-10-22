<?php
class PartidaController {
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;


//esto va en el model?
        if (!isset($_SESSION['puntaje'])) $_SESSION['puntaje'] = 0;
        if (!isset($_SESSION['partidaJugada'])) $_SESSION['partidaJugada'] = [];
    }

    public function base()
    {
        $this->jugar();
    }

    public function jugar()
    {
        $this->renderer->render("crearPartida");
    }



    public function partidaFinalizada() // no redirige a la vista Finalizada
    {
        //no esto segura si las sesiones van acá
        $puntaje = $_SESSION['puntaje'] ?? 0;
        $_SESSION['puntaje'] = 0;
        $_SESSION['partidaJugada'] = [];

        $this->renderer->render("partidaFinalizada", ['puntaje' => $puntaje]);
    }
}
