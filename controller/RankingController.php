<?php

class RankingController{
    private $model;
    private $renderer;

    public function __construct($model,$renderer){
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function verRanking(){
        $ranking = $this->model->obtenerRanking();
        $posicion = 1;

        foreach ($ranking as &$jugador) {
            $jugador['posicion'] = $posicion++;
        }

        $this->renderer->render("ranking",["ranking" => $ranking]);

    }

}